<?php
class CacheSqlite {
	/**
	 * Options disponibles :
	 *
	 * ====> (string) stockage_chemin :
	 * Chemin vers le fichier contenant la base SQLite.
	 * 
	 *
	 * ====> (int) defragmentation_auto :
	 * - Désactive / Régler le processus de défragmentation automatique
	 * - Le processus de défragmentation automatiques réduit la taille du fichier contenant la base de données
	 *   quand un ajout ou une suppression de cache est réalisée :
	 *	 0			   => pas de défragmentation automatique
	 *	 1			   => défragmentation automatique systématique
	 *	 x (integer) > 1 => défragmentation automatique toutes les 1 fois (au hasard) sur x ajout ou suppression de cache
	 *
	 * @var array options disponibles
	 */
	protected $options = array(
		'stockage_chemin' => null,
		'defragmentation_auto' => 10
	);
	
	/**
	 * DB ressource
	 *
	 * @var mixed $db
	 */
	private $bdd = null;

	/**
	 * Boolean to store if the structure has benn checked or not
	 *
	 * @var boolean $structure_ok
	 */
	private $structure_ok = false;

	private $Cache = null;
	
	/**
	 * Constructor
	 *
	 * @param  array $options Associative array of options
	 * @throws Zend_cache_Exception
	 * @return void
	 */
	public function __construct(array $options = array(), Cache $cache) {
		$this->Cache = $cache;
		if (extension_loaded('sqlite')) {
			$this->initialiserOptionsParConfig();
			$this->setOptions($options);
		} else {
			$e = "Impossible d'utiliser le cache SQLITE car l'extenssion 'sqlite' n'est pas chargée dans l'environnement PHP courrant.";
			trigger_error($e, E_USER_ERROR);
		}
	}
	
	private function initialiserOptionsParConfig() {
		while (list($nom, $valeur) = each($this->options)) {
			if (Config::existe($nom)) {
				$this->options[$nom] = Config::get($nom);
			}
		}
	}
	
	/**
	 * Destructor
	 *
	 * @return void
	 */
	public function __destruct() {
		@sqlite_close($this->getConnexion());
	}
	
	private function setOptions($options) {
		while (list($nom, $valeur) = each($options)) {
			if (!is_string($nom)) {
				trigger_error("Nom d'option incorecte : $nom", E_USER_WARNING);
			}
			$nom = strtolower($nom);
			if (array_key_exists($nom, $this->options)) {
				$this->options[$nom] = $valeur;
			}
		}
	}
	
	public function setEmplacement($emplacement) {
	 	if (extension_loaded('sqlite')) {
			$this->options['stockage_chemin'] = $emplacement;
		} else {
			trigger_error("Impossible d'utiliser le mode de sotckage SQLite car l'extenssion 'sqlite' n'est pas chargé dans ".
				"l'environnement PHP courrant.", E_USER_ERROR);
		}
	}

	/**
	 * Test if a cache is available for the given id and (if yes) return it (false else)
	 *
	 * @param  string  $id					 Cache id
	 * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
	 * @return string|false Cached datas
	 */
	public function charger($id, $ne_pas_tester_validiter_du_cache = false) {
		$this->verifierEtCreerStructureBdd();
		$requete = "SELECT content FROM cache WHERE id = '$id'".
			(($ne_pas_tester_validiter_du_cache) ? '' : ' AND (expire = 0 OR expire > '.time().')');
		$resultat = $this->requeter($requete);
		$ligne = @sqlite_fetch_array($resultat);
		return ($ligne) ? $ligne['content'] : false;
	}

	/**
	 * Test if a cache is available or not (for the given id)
	 *
	 * @param string $id Cache id
	 * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
	 */
	public function tester($id) {
		$this->verifierEtCreerStructureBdd();
		$requete = "SELECT lastModified FROM cache WHERE id = '$id' AND (expire = 0 OR expire > ".time().')';
		$resultat = $this->requeter($requete);
		$ligne = @sqlite_fetch_array($resultat);
		return ($ligne) ? ((int) $ligne['lastModified']) : false;
	}

	/**
	 * Save some string datas into a cache record
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param  string $data			 Datas to cache
	 * @param  string $id			   Cache id
	 * @param  array  $tags			 Array of strings, the cache record will be tagged by each string entry
	 * @param  int	$specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
	 * @throws Zend_Cache_Exception
	 * @return boolean True if no problem
	 */
	public function sauver($donnees, $id, $tags = array(), $duree_vie_specifique = false) {
		$this->verifierEtCreerStructureBdd();
		
		//FIXME : si l'extension n'est pas installée, le cache passe tout de même par cette fonction et s'arrête à cet endroit.
		$donnees = @sqlite_escape_string($donnees);
		$timestamp_courrant = time();
		$expiration = $this->Cache->getTimestampExpiration($duree_vie_specifique);

		$this->requeter("DELETE FROM cache WHERE id = '$id'");
		$sql = "INSERT INTO cache (id, content, lastModified, expire) VALUES ('$id', '$donnees', $timestamp_courrant, $expiration)";
		$resultat = $this->requeter($sql);
		if (!$resultat) {
			// TODO : ajouter un log sauver() : impossible de stocker le cache d'id '$id'
			Debug::printr("sauver() : impossible de stocker le cache d'id '$id'");
			$resultat =  false;
		} else {
			$resultat = true;
			foreach ($tags as $tag) {
				$resultat = $this->enregisterTag($id, $tag) && $resultat;
			}
		}
		return $resultat;
	}

	/**
	 * Remove a cache record
	 *
	 * @param  string $id Cache id
	 * @return boolean True if no problem
	 */
	public function supprimer($id) {
		$this->verifierEtCreerStructureBdd();
		$resultat = $this->requeter("SELECT COUNT(*) AS nbr FROM cache WHERE id = '$id'");
		$resultat_nbre = @sqlite_fetch_single($resultat);
		$suppression_cache = $this->requeter("DELETE FROM cache WHERE id = '$id'");
		$suppression_tags = $this->requeter("DELETE FROM tag WHERE id = '$id'");
		$this->defragmenterAutomatiquement();
		return ($resultat_nbre && $suppression_cache && $suppression_tags);
	}

	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * Zend_Cache::CLEANING_MODE_ALL (default)	=> remove all cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_OLD			  => remove too old cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_MATCHING_TAG	 => remove cache entries matching all given tags
	 *											   ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
	 *											   ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
	 *											   ($tags can be an array of strings or a single string)
	 *
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @return boolean True if no problem
	 */
	public function nettoyer($mode = Cache::NETTOYAGE_MODE_TOUS, $tags = array()) {
		$this->verifierEtCreerStructureBdd();
		$retour = $this->nettoyerSqlite($mode, $tags);
		$this->defragmenterAutomatiquement();
		return $retour;
	}

	/**
	 * Return an array of stored cache ids
	 *
	 * @return array array of stored cache ids (string)
	 */
	public function getIds() {
		$this->verifierEtCreerStructureBdd();
		$resultat = $this->requeter('SELECT id FROM cache WHERE (expire = 0 OR expire > '.time().')');
		$retour = array();
		while ($id = @sqlite_fetch_single($resultat)) {
			$retour[] = $id;
		}
		return $retour;
	}

	/**
	 * Return an array of stored tags
	 *
	 * @return array array of stored tags (string)
	 */
	public function getTags() {
		$this->verifierEtCreerStructureBdd();
		$resultat = $this->requeter('SELECT DISTINCT(name) AS name FROM tag');
		$retour = array();
		while ($id = @sqlite_fetch_single($resultat)) {
			$retour[] = $id;
		}
		return $retour;
	}

	/**
	 * Return an array of stored cache ids which match given tags
	 *
	 * In case of multiple tags, a logical AND is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of matching cache ids (string)
	 */
	public function getIdsAvecLesTags($tags = array()) {
		$this->verifierEtCreerStructureBdd();
		$premier = true;
		$ids = array();
		foreach ($tags as $tag) {
			$resultat = $this->requeter("SELECT DISTINCT(id) AS id FROM tag WHERE name='$tag'");
			if ($resultat) {
				$lignes = @sqlite_fetch_all($resultat, SQLITE_ASSOC);
				$ids_tmp = array();
				foreach ($lignes as $ligne) {
					$ids_tmp[] = $ligne['id'];
				}
				if ($premier) {
					$ids = $ids_tmp;
					$premier = false;
				} else {
					$ids = array_intersect($ids, $ids_tmp);
				}
			}
		}
		
		$retour = array();
		if (count($ids) > 0) {
			foreach ($ids as $id) {
				$retour[] = $id;
			}
		}
		return $retour;
	}

	/**
	 * Return an array of stored cache ids which don't match given tags
	 *
	 * In case of multiple tags, a logical OR is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of not matching cache ids (string)
	 */
	public function getIdsSansLesTags($tags = array()) {
		$this->verifierEtCreerStructureBdd();
		$resultat = $this->requeter('SELECT id FROM cache');
		$lignes = @sqlite_fetch_all($resultat, SQLITE_ASSOC);
		$retour = array();
		foreach ($lignes as $ligne) {
			$id = $ligne['id'];
			$correspondance = false;
			foreach ($tags as $tag) {
				$resultat = $this->requeter("SELECT COUNT(*) AS nbr FROM tag WHERE name = '$tag' AND id = '$id'");
				if ($resultat) {
					$nbre = (int) @sqlite_fetch_single($resultat);
					if ($nbre > 0) {
						$correspondance = true;
					}
				}
			}
			if (!$correspondance) {
				$retour[] = $id;
			}
		}
		return $retour;
	}

	/**
	 * Return an array of stored cache ids which match any given tags
	 *
	 * In case of multiple tags, a logical AND is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of any matching cache ids (string)
	 */
	public function getIdsAvecUnTag($tags = array()) {
		$this->verifierEtCreerStructureBdd();
		$premier = true;
		$ids = array();
		foreach ($tags as $tag) {
			$resultat = $this->requeter("SELECT DISTINCT(id) AS id FROM tag WHERE name = '$tag'");
			if ($resultat) {
				$lignes = @sqlite_fetch_all($resultat, SQLITE_ASSOC);
				$ids_tmp = array();
				foreach ($lignes as $ligne) {
					$ids_tmp[] = $ligne['id'];
				}
				if ($premier) {
					$ids = $ids_tmp;
					$premier = false;
				} else {
					$ids = array_merge($ids, $ids_tmp);
				}
			}
		}
		
		$retour = array();
		if (count($ids) > 0) {
			foreach ($ids as $id) {
				$retour[] = $id;
			}
		}
		return $retour;
	}

	/**
	 * Return the filling percentage of the backend storage
	 *
	 * @throws Zend_Cache_Exception
	 * @return int integer between 0 and 100
	 */
	public function getPourcentageRemplissage() {
		$dossier = dirname($this->options['stockage_chemin']);
		$libre = disk_free_space($dossier);
		$total = disk_total_space($dossier);
		
		$pourcentage = 0;
		if ($total == 0) {
			trigger_error("Impossible d'utiliser la fonction disk_total_space", E_USER_WARNING);
		} else {
			$pourcentage = ($libre >= $total) ? 100 : ((int) (100. * ($total - $libre) / $total));
		}
		return $pourcentage;
	}

	/**
	 * Return an array of metadatas for the given cache id
	 *
	 * The array must include these keys :
	 * - expire : the expire timestamp
	 * - tags : a string array of tags
	 * - mtime : timestamp of last modification time
	 *
	 * @param string $id cache id
	 * @return array array of metadatas (false if the cache id is not found)
	 */
	public function getMetadonnees($id) {
		$this->verifierEtCreerStructureBdd();
		$tags = array();
		$resultat = $this->requeter("SELECT name FROM tag WHERE id = '$id'");
		if ($resultat) {
			$lignes = @sqlite_fetch_all($resultat, SQLITE_ASSOC);
			foreach ($lignes as $ligne) {
				$tags[] = $ligne['name'];
			}
		}
		$resultat = $this->requeter("SELECT lastModified, expire FROM cache WHERE id = '$id'");
		if ($resultat) {
			$ligne = @sqlite_fetch_array($resultat, SQLITE_ASSOC);
			$resultat = array(
				'tags' => $tags,
				'mtime' => $ligne['lastModified'],
				'expiration' => $ligne['expire']);
		} else {
			$resultat = false;
		}
		return $resultat;
	}

	/**
	 * Give (if possible) an extra lifetime to the given cache id
	 *
	 * @param string $id cache id
	 * @param int $extraLifetime
	 * @return boolean true if ok
	 */
	public function ajouterSupplementDureeDeVie($id, $supplement_duree_de_vie) {
		$this->verifierEtCreerStructureBdd();
		$augmentation = false;
		$requete = "SELECT expire FROM cache WHERE id = '$id' AND (expire = 0 OR expire > ".time().')';
		$resultat = $this->requeter($requete);
		if ($resultat) {
			$expiration = @sqlite_fetch_single($resultat);
			$nouvelle_expiration = $expiration + $supplement_duree_de_vie;
			$resultat = $this->requeter('UPDATE cache SET lastModified = '.time().", expire = $nouvelle_expiration WHERE id = '$id'");
			$augmentation = ($resultat) ? true : false;
		}
		return $augmentation;
	}

	/**
	 * Return the connection resource
	 *
	 * If we are not connected, the connection is made
	 *
	 * @throws Zend_Cache_Exception
	 * @return resource Connection resource
	 */
	private function getConnexion() {
		if (!is_resource($this->bdd)) {
			if ($this->options['stockage_chemin'] === null) {
				$e = "L'emplacement du chemin vers le fichier de la base de données SQLite n'a pas été défini";
				trigger_error($e, E_USER_ERROR);
			} else {
				$this->bdd = sqlite_open($this->options['stockage_chemin']);
				if (!(is_resource($this->bdd))) {
					$e = "Impossible d'ouvrir le fichier '".$this->options['stockage_chemin']."' de la base de données SQLite.";
					trigger_error($e, E_USER_ERROR);
					$this->bdd = null;
				}
			}
		}
		return $this->bdd;
	}

	/**
	 * Execute une requête SQL sans afficher de messages d'erreur.
	 *
	 * @param string $requete requête SQL
	 * @return mixed|false resultats de la requête
	 */
	private function requeter($requete) {
		$bdd = $this->getConnexion();
		//Debug::printr($requete);
		$resultat = (is_resource($bdd)) ? @sqlite_query($bdd, $requete, SQLITE_ASSOC, $e_sqlite) : false;
		if (is_resource($bdd) && ! $resultat) {
			Debug::printr("Erreur SQLITE :\n$e_sqlite\nPour la requête :\n$requete\nRessource : $bdd");
		}
		return $resultat;
	}

	/**
	 * Deal with the automatic vacuum process
	 *
	 * @return void
	 */
	private function defragmenterAutomatiquement() {
		if ($this->options['defragmentation_auto'] > 0) {
			$rand = rand(1, $this->options['defragmentation_auto']);
			if ($rand == 1) {
				$this->requeter('VACUUM');
				@sqlite_close($this->getConnexion());
			}
		}
	}

	/**
	 * Register a cache id with the given tag
	 *
	 * @param  string $id  Cache id
	 * @param  string $tag Tag
	 * @return boolean True if no problem
	 */
	private function enregisterTag($id, $tag) {
		$requete_suppression = "DELETE FROM tag WHERE name = '$tag' AND id = '$id'";
		$resultat = $this->requeter($requete_suppression);
		$requete_insertion = "INSERT INTO tag(name,id) VALUES ('$tag','$id')";
		$resultat = $this->requeter($requete_insertion);
		if (!$resultat) {
			// TODO : ajouter un log -> impossible d'enregistrer le tag=$tag pour le cache id=$id");
			Debug::printr("Impossible d'enregistrer le tag=$tag pour le cache id=$id");
		}
		return ($resultat) ? true : false;
	}

	/**
	 * Build the database structure
	 *
	 * @return false
	 */
	private function creerStructure() {
		$this->requeter('DROP INDEX IF EXISTS tag_id_index');
		$this->requeter('DROP INDEX IF EXISTS tag_name_index');
		$this->requeter('DROP INDEX IF EXISTS cache_id_expire_index');
		$this->requeter('DROP TABLE IF EXISTS version');
		$this->requeter('DROP TABLE IF EXISTS cache');
		$this->requeter('DROP TABLE IF EXISTS tag');
		$this->requeter('CREATE TABLE version (num INTEGER PRIMARY KEY)');
		$this->requeter('CREATE TABLE cache(id TEXT PRIMARY KEY, content BLOB, lastModified INTEGER, expire INTEGER)');
		$this->requeter('CREATE TABLE tag (name TEXT, id TEXT)');
		$this->requeter('CREATE INDEX tag_id_index ON tag(id)');
		$this->requeter('CREATE INDEX tag_name_index ON tag(name)');
		$this->requeter('CREATE INDEX cache_id_expire_index ON cache(id, expire)');
		$this->requeter('INSERT INTO version (num) VALUES (1)');
	}

	/**
	 * Check if the database structure is ok (with the good version)
	 *
	 * @return boolean True if ok
	 */
	private function verifierBddStructureVersion() {
		$version_ok = false;
		$resultat = $this->requeter('SELECT num FROM version');
		if ($resultat) {
			$ligne = @sqlite_fetch_array($resultat);
			if ($ligne) {
				if (((int) $ligne['num']) == 1) {
					$version_ok = true;
				} else {
					// TODO : ajouter un log CacheSqlite::verifierBddStructureVersion() : vielle version de la structure de la base de données de cache détectée => le cache est entrain d'être supprimé
				}
			}
		}
		return $version_ok;
	}

	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * Zend_Cache::CLEANING_MODE_ALL (default)	=> remove all cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_OLD			  => remove too old cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_MATCHING_TAG	 => remove cache entries matching all given tags
	 *											   ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
	 *											   ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
	 *											   ($tags can be an array of strings or a single string)
	 *
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @return boolean True if no problem
	 */
	private function nettoyerSqlite($mode = Cache::NETTOYAGE_MODE_TOUS, $tags = array()) {
		$nettoyage_ok = false;
		switch ($mode) {
			case Cache::NETTOYAGE_MODE_TOUS:
				$suppression_cache = $this->requeter('DELETE FROM cache');
				$suppression_tag = $this->requeter('DELETE FROM tag');
				$nettoyage_ok = $suppression_cache && $suppression_tag;
				break;
			case Cache::NETTOYAGE_MODE_EXPIRATION:
				$mktime = time();
				$suppression_tag = $this->requeter("DELETE FROM tag WHERE id IN (SELECT id FROM cache WHERE expire > 0 AND expire <= $mktime)");
				$suppression_cache = $this->requeter("DELETE FROM cache WHERE expire > 0 AND expire <= $mktime");
				return $suppression_tag && $suppression_cache;
				break;
			case Cache::NETTOYAGE_MODE_AVEC_LES_TAGS:
				$ids = $this->getIdsAvecLesTags($tags);
				$resultat = true;
				foreach ($ids as $id) {
					$resultat = $this->supprimer($id) && $resultat;
				}
				return $resultat;
				break;
			case Cache::NETTOYAGE_MODE_SANS_LES_TAGS:
				$ids = $this->getIdsSansLesTags($tags);
				$resultat = true;
				foreach ($ids as $id) {
					$resultat = $this->supprimer($id) && $resultat;
				}
				return $resultat;
				break;
			case Cache::NETTOYAGE_MODE_AVEC_UN_TAG:
				$ids = $this->getIdsAvecUnTag($tags);
				$resultat = true;
				foreach ($ids as $id) {
					$resultat = $this->supprimer($id) && $resultat;
				}
				return $resultat;
				break;
			default:
				break;
		}
		return $nettoyage_ok;
	}

	/**
	 * Check if the database structure is ok (with the good version), if no : build it
	 *
	 * @throws Zend_Cache_Exception
	 * @return boolean True if ok
	 */
	private function verifierEtCreerStructureBdd() {
		if (! $this->structure_ok) {
			if (! $this->verifierBddStructureVersion()) {
				$this->creerStructure();
				if (! $this->verifierBddStructureVersion()) {
					$e = "Impossible de construire la base de données de cache dans ".$this->options['stockage_chemin'];
					trigger_error($e, E_USER_WARNING);
					$this->structure_ok = false;
				}
			}
			$this->structure_ok = true;
		}
		return $this->structure_ok;
	}

}
?>