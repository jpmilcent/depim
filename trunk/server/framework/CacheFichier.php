<?php
class CacheFichier {
	/**
	 * Options disponibles
	 * 
	 * ====> (string) stockage_chemin :
	 * Chemin vers  le dossier devant contenir l'arborescence du cache.
	 * 
	 * =====> (boolean) fichier_verrou :
	 * - Active / Désactive le verrouillage des fichiers
	 * - Peut éviter la corruption du cache dans de mauvaises circonstances, mais cela ne fonctionne pas sur des serveur 
	 * multithread et sur les systèmes de fichiers NFS par exemple.
	 *
	 * =====> (boolean) controle_lecture :
	 * - Activer / désactiver le contrôle de lecture
	 * - S'il est activé, une clé de contrôle est ajoutée dans le fichier de cache et cette clé est comparée avec celle calculée
	 * après la lecture.
	 *
	 * =====> (string) controle_lecture_type :
	 * Type de contrôle de lecture (seulement si le contrôle de lecture est activé).
	 * Les valeurs disponibles sont:
	 * - «md5» pour un contrôle md5 (le meilleur mais le plus lent)
	 * - «crc32» pour un contrôle de hachage crc32 (un peu moins sécurisé, mais plus rapide, un meilleur choix)
	 * - «adler32» pour un contrôle de hachage adler32  (excellent choix aussi, plus rapide que crc32)
	 * - «strlen» pour un test de longueur uniquement (le plus rapide)
	 *
	 * =====> (int) dossier_niveau :
	 * - Permet de réglez le nombre de niveau de sous-dossier que contiendra l'arborescence des dossiers du cache.
	 * 0 signifie "pas de sous-dossier pour le cache", 
	 * 1 signifie "un niveau de sous-dossier", 
	 * 2 signifie "deux niveaux" ...
	 * Cette option peut accélérer le cache seulement lorsque vous avez plusieurs centaines de fichiers de cache. 
	 * Seuls des tests spécifiques peuvent vous aider à choisir la meilleure valeur possible pour vous.
	 * 1 ou 2 peut être est un bon début.
	 *
	 * =====> (int) dossier_umask :
	 * - Umask pour les sous-dossiers de l'arborescence du cache.
	 *
	 * =====> (string) fichier_prefixe :
	 * - préfixe pour les fichiers du cache
	 * - ATTENTION : faite vraiment attention avec cette option, car une valeur trop générique dans le dossier cache du système
	 * (comme /tmp) peut provoquer des catastrophes lors du nettoyage du cache.
	 *
	 * =====> (int) fichier_umask :
	 * - Umask pour les fichiers de cache
	 *
	 * =====> (int) metadonnees_max_taille :
	 * - taille maximum pour le tableau de métadonnées du cache (ne changer pas cette valeur sauf si vous savez ce que vous faite)
	 *
	 * @var array options disponibles
	 */
	protected $options = array(
		'stockage_chemin' => null,
		'fichier_verrou' => true,
		'controle_lecture' => true,
		'controle_lecture_type' => 'crc32',
		'dossier_niveau' => 0,
		'dossier_umask' => 0700,
		'fichier_prefixe' => 'tbf',
		'fichier_umask' => 0600,
		'metadonnees_max_taille' => 100
	);

	/**
	 * Array of metadatas (each item is an associative array)
	 *
	 * @var array
	 */
	protected $metadonnees = array();

	private $Cache = null;
	
	/**
	 * Constructor
	 *
	 * @param  array $options associative array of options
	 * @throws Zend_Cache_Exception
	 * @return void
	 */
	public function __construct(array $options = array(), Cache $cache) {
		$this->Cache = $cache;
		$this->initialiserOptionsParConfig();
		$this->setOptions($options);

		if (isset($this->options['prefixe_fichier'])) {
			if (!preg_match('~^[a-zA-Z0-9_]+$~D', $this->options['prefixe_fichier'])) {
				trigger_error("Préfixe de nom de fichier invalide : doit contenir seulement [a-zA-Z0-9_]", E_USER_WARNING);
			}
		}
		if ($this->options['metadonnees_max_taille'] < 10) {
			trigger_error("Taille du tableau des méta-données invalide, elle doit être > 10", E_USER_WARNING);
		}
		if (isset($options['dossier_umask']) && is_string($options['dossier_umask'])) {
			// See #ZF-4422
			$this->options['dossier_umask'] = octdec($this->options['dossier_umask']);
		}
		if (isset($options['fichier_umask']) && is_string($options['fichier_umask'])) {
			// See #ZF-4422
			$this->options['fichier_umask'] = octdec($this->options['fichier_umask']);
		}
	}
	
	private function initialiserOptionsParConfig() {
		while (list($nom, $valeur) = each($this->options)) {
			if (Config::existe($nom)) {
				$this->options[$nom] = Config::get($nom);
			}
		}
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
		if (!is_dir($emplacement)) {
			trigger_error("L'emplacement doit être un dossier.", E_USER_WARNING);
		}
		if (!is_writable($emplacement)) {
			trigger_error("Le dossier de stockage du cache n'est pas accessible en écriture", E_USER_WARNING);
		}
		$emplacement = rtrim(realpath($emplacement), '\\/').DS;
		$this->options['stockage_chemin'] = $emplacement;
	}

	/**
	 * Test if a cache is available for the given id and (if yes) return it (false else)
	 *
	 * @param string $id cache id
	 * @param boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
	 * @return string|false cached datas
	 */
	public function charger($id, $ne_pas_tester_validiter_du_cache = false) {
		$donnees = false;
		if ($this->tester($id, $ne_pas_tester_validiter_du_cache)) {
			$metadonnees = $this->getMetadonneesFichier($id);
			$fichier = $this->getFichierNom($id);
			$donnees = $this->getContenuFichier($fichier);
			if ($this->options['controle_lecture']) {
				$cle_secu_donnees = $this->genererCleSecu($donnees, $this->options['controle_lecture_type']);
				$cle_secu_controle = $metadonnees['hash'];
				if ($cle_secu_donnees != $cle_secu_controle) {
					// Probléme détecté par le contrôle de lecture !
					// TODO : loguer le pb de sécu
					$this->supprimer($id);
					$donnees = false;
				}
			}
		}
		return $donnees;
	}

	/**
	 * Teste si un enregistrement en cache est disponible ou pas (pour l'id passé en paramètre).
	 *
	 * @param string $id identifiant de cache.
	 * @return mixed false (le cache n'est pas disponible) ou timestamp (int) "de dernière modification" de l'enregistrement en cache
	 */
	public function tester($id) {
		clearstatcache();
		return $this->testerExistenceCache($id, false);
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
	 * @return boolean true if no problem
	 */
	public function sauver($donnees, $id, $tags = array(), $duree_vie_specifique = false) {
		clearstatcache();
		$fichier = $this->getFichierNom($id);
		$chemin = $this->getChemin($id);
		
		$resultat = true;
		if ($this->options['dossier_niveau'] > 0) {
			if (!is_writable($chemin)) {
				// maybe, we just have to build the directory structure
				$this->lancerMkdirEtChmodRecursif($id);
			}
			if (!is_writable($chemin)) {
				$resultat = false;
			}
		}
		
		if ($resultat === true) {
			if ($this->options['controle_lecture']) {
				$cle_secu = $this->genererCleSecu($donnees, $this->options['controle_lecture_type']);
			} else {
				$cle_secu = '';
			}
			
			$metadonnees = array(
				'hash' => $cle_secu,
				'mtime' => time(),
				'expiration' => $this->Cache->getTimestampExpiration($duree_vie_specifique),
				'tags' => $tags
			);

			if (! $resultat = $this->setMetadonnees($id, $metadonnees)) {
				// TODO : ajouter un log
			} else {
				$resultat = $this->setContenuFichier($fichier, $donnees);
			}
		}
		return $resultat;
	}

	/**
	 * Remove a cache record
	 *
	 * @param  string $id cache id
	 * @return boolean true if no problem
	 */
	public function supprimer($id) {
		$fichier = $this->getFichierNom($id);
		$suppression_fichier = $this->supprimerFichier($fichier);
		$suppression_metadonnees = $this->supprimerMetadonnees($id);
		return $suppression_metadonnees && $suppression_fichier;
	}

	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * 'all' (default)  => remove all cache entries ($tags is not used)
	 * 'old'			=> remove too old cache entries ($tags is not used)
	 * 'matchingTag'	=> remove cache entries matching all given tags
	 *					 ($tags can be an array of strings or a single string)
	 * 'notMatchingTag' => remove cache entries not matching one of the given tags
	 *					 ($tags can be an array of strings or a single string)
	 * 'matchingAnyTag' => remove cache entries matching any given tags
	 *					 ($tags can be an array of strings or a single string)
	 *
	 * @param string $mode clean mode
	 * @param tags array $tags array of tags
	 * @return boolean true if no problem
	 */
	public function nettoyer($mode = Cache::NETTOYAGE_MODE_TOUS, $tags = array()) {
		// We use this protected method to hide the recursive stuff
		clearstatcache();
		return $this->nettoyerFichiers($this->options['stockage_chemin'], $mode, $tags);
	}

	/**
	 * Return an array of stored cache ids
	 *
	 * @return array array of stored cache ids (string)
	 */
	public function getIds() {
		return $this->analyserCache($this->options['stockage_chemin'], 'ids', array());
	}

	/**
	 * Return an array of stored tags
	 *
	 * @return array array of stored tags (string)
	 */
	public function getTags() {
		return $this->analyserCache($this->options['stockage_chemin'], 'tags', array());
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
		return $this->analyserCache($this->options['stockage_chemin'], 'matching', $tags);
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
		return $this->analyserCache($this->options['stockage_chemin'], 'notMatching', $tags);
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
		return $this->analyserCache($this->options['stockage_chemin'], 'matchingAny', $tags);
	}

	/**
	 * Return the filling percentage of the backend storage
	 *
	 * @throws Zend_Cache_Exception
	 * @return int integer between 0 and 100
	 */
	public function getPourcentageRemplissage() {
		$libre = disk_free_space($this->options['stockage_chemin']);
		$total = disk_total_space($this->options['stockage_chemin']);
		
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
		if ($metadonnees = $this->getMetadonneesFichier($id)) {
			if (time() > $metadonnees['expiration']) {
				$metadonnees = false;
			} else {
				$metadonnees = array(
					'expiration' => $metadonnees['expiration'],
					'tags' => $metadonnees['tags'],
					'mtime' => $metadonnees['mtime']
				);
			}
		}
		
		return $metadonnees;
	}

	/**
	 * Give (if possible) an extra lifetime to the given cache id
	 *
	 * @param string $id cache id
	 * @param int $extraLifetime
	 * @return boolean true if ok
	 */
	public function ajouterSupplementDureeDeVie($id, $supplement_duree_de_vie) {
		$augmentation = true;
		if ($metadonnees = $this->getMetadonneesFichier($id)) {
			if (time() > $metadonnees['expiration']) {
				$augmentation = false;
			} else {
				$metadonnees_nouvelle = array(
					'hash' => $metadonnees['hash'],
					'mtime' => time(),
					'expiration' => $metadonnees['expiration'] + $supplement_duree_de_vie,
					'tags' => $metadonnees['tags']
				);
				$augmentation = $this->setMetadonnees($id, $metadonnees_nouvelle);
			}
		}
		return $augmentation;
	}

	/**
	 * Get a metadatas record
	 *
	 * @param  string $id  Cache id
	 * @return array|false Associative array of metadatas
	 */
	protected function getMetadonneesFichier($id) {
		$metadonnees = false;
		if (isset($this->metadonnees[$id])) {
			$metadonnees = $this->metadonnees[$id];
		} else {
			if ($metadonnees = $this->chargerMetadonnees($id)) {
				$this->setMetadonnees($id, $metadonnees, false);
			}
		}
		return $metadonnees;
	}

	/**
	 * Set a metadatas record
	 *
	 * @param  string $id		Cache id
	 * @param  array  $metadatas Associative array of metadatas
	 * @param  boolean $save	 optional pass false to disable saving to file
	 * @return boolean True if no problem
	 */
	protected function setMetadonnees($id, $metadonnees, $sauvegarde = true) {
		if (count($this->metadonnees) >= $this->options['metadonnees_max_taille']) {
			$n = (int) ($this->options['metadonnees_max_taille'] / 10);
			$this->metadonnees = array_slice($this->metadonnees, $n);
		}
		
		$resultat = true;
		if ($sauvegarde) {
			$resultat = $this->sauverMetadonnees($id, $metadonnees);
		}
		if ($resultat == true) {
			$this->metadonnees[$id] = $metadonnees;
		}
		return $resultat;
	}

	/**
	 * Drop a metadata record
	 *
	 * @param  string $id Cache id
	 * @return boolean True if no problem
	 */
	protected function supprimerMetadonnees($id) {
		if (isset($this->metadonnees[$id])) {
			unset($this->metadonnees[$id]);
		}
		$fichier_meta = $this->getNomFichierMeta($id);
		return $this->supprimerFichier($fichier_meta);
	}

	/**
	 * Clear the metadatas array
	 *
	 * @return void
	 */
	protected function nettoyerMetadonnees() {
		$this->metadonnees = array();
	}

	/**
	 * Load metadatas from disk
	 *
	 * @param  string $id Cache id
	 * @return array|false Metadatas associative array
	 */
	protected function chargerMetadonnees($id) {
		$fichier = $this->getNomFichierMeta($id);
		if ($resultat = $this->getContenuFichier($fichier)) {
			$resultat = @unserialize($resultat);
		}
		return $resultat;
	}

	/**
	 * Save metadatas to disk
	 *
	 * @param  string $id		Cache id
	 * @param  array  $metadatas Associative array
	 * @return boolean True if no problem
	 */
	protected function sauverMetadonnees($id, $metadonnees) {
		$fichier = $this->getNomFichierMeta($id);
		$resultat = $this->setContenuFichier($fichier, serialize($metadonnees));
		return $resultat;
	}

	/**
	 * Make and return a file name (with path) for metadatas
	 *
	 * @param  string $id Cache id
	 * @return string Metadatas file name (with path)
	 */
	protected function getNomFichierMeta($id) {
		$chemin = $this->getChemin($id);
		$fichier_nom = $this->transformaterIdEnNomFichier('interne-meta---'.$id);
		return $chemin.$fichier_nom;
	}

	/**
	 * Check if the given filename is a metadatas one
	 *
	 * @param  string $fileName File name
	 * @return boolean True if it's a metadatas one
	 */
	protected function etreFichierMeta($fichier_nom) {
		$id = $this->transformerNomFichierEnId($fichier_nom);
		return (substr($id, 0, 21) == 'interne-meta---') ? true : false;
	}

	/**
	 * Remove a file
	 *
	 * If we can't remove the file (because of locks or any problem), we will touch
	 * the file to invalidate it
	 *
	 * @param  string $file Complete file path
	 * @return boolean True if ok
	 */
	protected function supprimerFichier($fichier) {
		$resultat = false;
		if (is_file($fichier)) {
			if ($resultat = @unlink($fichier)) {
				// TODO : ajouter un log
			}
		}
		return $resultat;
	}

	/**
	 * Clean some cache records (protected method used for recursive stuff)
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
	 * @param  string $dir  Directory to clean
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @throws Zend_Cache_Exception
	 * @return boolean True if no problem
	 */
	protected function nettoyerFichiers($dossier, $mode = Cache::NETTOYAGE_MODE_TOUS, $tags = array()) {
		if (!is_dir($dossier)) {
			return false;
		}
		$resultat = true;
		$prefixe = $this->options['fichier_prefixe'];
		$glob = @glob($dossier.$prefixe.'--*');
		if ($glob === false) {
			// On some systems it is impossible to distinguish between empty match and an error.
			return true;
		}
		foreach ($glob as $fichier)  {
			if (is_file($fichier)) {
				$fichier_nom = basename($fichier);
				if ($this->etreFichierMeta($fichier_nom)) {
					// Pour le mode Cache::NETTOYAGE_MODE_TOUS, nous essayons de tous supprimer même les vieux fichiers méta
					if ($mode != Cache::NETTOYAGE_MODE_TOUS) {
						continue;
					}
				}
				$id = $this->transformerNomFichierEnId($fichier_nom);
				$metadonnees = $this->getMetadonneesFichier($id);
				if ($metadonnees === FALSE) {
					$metadonnees = array('expiration' => 1, 'tags' => array());
				}
				switch ($mode) {
					case Cache::NETTOYAGE_MODE_TOUS :
						if ($resultat_suppression = $this->supprimer($id)) {
							// Dans ce cas seulement, nous acception qu'il y ait un problème avec la suppresssion du fichier meta
							$resultat_suppression = $this->supprimerFichier($fichier);
						}
						$resultat = $resultat && $resultat_suppression;
						break;
					case Cache::NETTOYAGE_MODE_EXPIRATION :
						if (time() > $metadonnees['expiration']) {
							$resultat = $this->supprimer($id) && $resultat;
						}
						break;
					case Cache::NETTOYAGE_MODE_AVEC_LES_TAGS :
						$correspondance = true;
						foreach ($tags as $tag) {
							if (!in_array($tag, $metadonnees['tags'])) {
								$correspondance = false;
								break;
							}
						}
						if ($correspondance) {
							$resultat = $this->supprimer($id) && $resultat;
						}
						break;
					case Cache::NETTOYAGE_MODE_SANS_LES_TAGS :
						$correspondance = false;
						foreach ($tags as $tag) {
							if (in_array($tag, $metadonnees['tags'])) {
								$correspondance = true;
								break;
							}
						}
						if (!$correspondance) {
							$resultat = $this->supprimer($id) && $resultat;
						}
						break;
					case Cache::NETTOYAGE_MODE_AVEC_UN_TAG :
						$correspondance = false;
						foreach ($tags as $tag) {
							if (in_array($tag, $metadonnees['tags'])) {
								$correspondance = true;
								break;
							}
						}
						if ($correspondance) {
							$resultat = $this->supprimer($id) && $resultat;
						}
						break;
					default:
						trigger_error("Mode de nettoyage invalide pour la méthode nettoyer()", E_USER_WARNING);
						break;
				}
			}
			if ((is_dir($fichier)) and ($this->options['dossier_niveau'] > 0)) {
				// Appel récursif
				$resultat = $this->nettoyerFichiers($fichier.DS, $mode, $tags) && $resultat;
				if ($mode == Cache::NETTOYAGE_MODE_TOUS) {
					// Si mode == Cache::NETTOYAGE_MODE_TOUS, nous essayons de supprimer la structure aussi
					@rmdir($fichier);
				}
			}
		}
		return $resultat;
	}

	protected function analyserCache($dossier, $mode, $tags = array()) {
		if (!is_dir($dossier)) {
			return false;
		}
		$resultat = array();
		$prefixe = $this->options['fichier_prefixe'];
		$glob = @glob($dossier.$prefixe.'--*');
		if ($glob === false) {
			// On some systems it is impossible to distinguish between empty match and an error.
			return array();
		}
		foreach ($glob as $fichier)  {
			if (is_file($fichier)) {
				$nom_fichier = basename($fichier);
				$id = $this->transformerNomFichierEnId($nom_fichier);
				$metadonnees = $this->getMetadonneesFichier($id);
				if ($metadonnees === FALSE) {
					continue;
				}
				if (time() > $metadonnees['expiration']) {
					continue;
				}
				switch ($mode) {
					case 'ids':
						$resultat[] = $id;
						break;
					case 'tags':
						$resultat = array_unique(array_merge($resultat, $metadonnees['tags']));
						break;
					case 'matching':
						$correspondance = true;
						foreach ($tags as $tag) {
							if (!in_array($tag, $metadonnees['tags'])) {
								$correspondance = false;
								break;
							}
						}
						if ($correspondance) {
							$resultat[] = $id;
						}
						break;
					case 'notMatching':
						$correspondance = false;
						foreach ($tags as $tag) {
							if (in_array($tag, $metadonnees['tags'])) {
								$correspondance = true;
								break;
							}
						}
						if (!$correspondance) {
							$resultat[] = $id;
						}
						break;
					case 'matchingAny':
						$correspondance = false;
						foreach ($tags as $tag) {
							if (in_array($tag, $metadonnees['tags'])) {
								$correspondance = true;
								break;
							}
						}
						if ($correspondance) {
							$resultat[] = $id;
						}
						break;
					default:
						trigger_error("Mode invalide pour la méthode analyserCache()", E_USER_WARNING);
						break;
				}
			}
			if ((is_dir($fichier)) and ($this->options['dossier_niveau'] > 0)) {
				// Appel récursif
				$resultat_analyse_recursive = $this->analyserCache($fichier.DS, $mode, $tags);
				if ($resultat_analyse_recursive === false) {
					// TODO : ajoute un log
				} else {
					$resultat = array_unique(array_merge($resultat, $resultat_analyse_recursive));
				}
			}
		}
		return array_unique($resultat);
	}

	/**
	 * Make a control key with the string containing datas
	 *
	 * @param  string $data		Data
	 * @param  string $controlType Type of control 'md5', 'crc32' or 'strlen'
	 * @throws Zend_Cache_Exception
	 * @return string Control key
	 */
	protected function genererCleSecu($donnees, $type_de_controle) {
		switch ($type_de_controle) {
		case 'md5':
			return md5($donnees);
		case 'crc32':
			return crc32($donnees);
		case 'strlen':
			return strlen($donnees);
		case 'adler32':
			return hash('adler32', $donnees);
		default:
			trigger_error("Fonction de génération de clé de sécurité introuvable : $type_de_controle", E_USER_WARNING);
		}
	}

	/**
	 * Transform a cache id into a file name and return it
	 *
	 * @param  string $id Cache id
	 * @return string File name
	 */
	protected function transformaterIdEnNomFichier($id) {
		$prefixe = $this->options['fichier_prefixe'];
		$resultat = $prefixe.'---'.$id;
		return $resultat;
	}

	/**
	 * Make and return a file name (with path)
	 *
	 * @param  string $id Cache id
	 * @return string File name (with path)
	 */
	protected function getFichierNom($id) {
		$path = $this->getChemin($id);
		$fileName = $this->transformaterIdEnNomFichier($id);
		return $path . $fileName;
	}

	/**
	 * Return the complete directory path of a filename (including hashedDirectoryStructure)
	 *
	 * @param  string $id Cache id
	 * @param  boolean $decoupage if true, returns array of directory parts instead of single string
	 * @return string Complete directory path
	 */
	protected function getChemin($id, $decoupage = false) {
		$morceaux = array();
		$chemin = $this->options['stockage_chemin'];
		$prefixe = $this->options['fichier_prefixe'];
		if ($this->options['dossier_niveau'] > 0) {
			$hash = hash('adler32', $id);
			for ($i = 0 ; $i < $this->options['dossier_niveau'] ; $i++) {
				$chemin .= $prefixe.'--'.substr($hash, 0, $i + 1).DS;
				$morceaux[] = $chemin;
			}
		}
		return ($decoupage) ? 	$morceaux : $chemin;
	}

	/**
	 * Make the directory strucuture for the given id
	 *
	 * @param string $id cache id
	 * @return boolean true
	 */
	protected function lancerMkdirEtChmodRecursif($id) {
		$resultat = true;
		if ($this->options['dossier_niveau'] > 0) {
			$chemins = $this->getChemin($id, true);
			foreach ($chemins as $chemin) {
				if (!is_dir($chemin)) {
					@mkdir($chemin, $this->options['dossier_umask']);
					@chmod($chemin, $this->options['dossier_umask']); // see #ZF-320 (this line is required in some configurations)
				}
			}
		}
		return $resultat;
	}

	/**
	 * Test if the given cache id is available (and still valid as a cache record)
	 *
	 * @param  string  $id					 Cache id
	 * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
	 * @return boolean|mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
	 */
	protected function testerExistenceCache($id, $ne_pas_tester_validiter_du_cache) {
		$resultat = false;
		if ($metadonnees = $this->getMetadonnees($id)) {
			if ($ne_pas_tester_validiter_du_cache || (time() <= $metadonnees['expiration'])) {
				$resultat =  $metadonnees['mtime'];
			}
		}
		return $resultat;
	}

	/**
	 * Return the file content of the given file
	 *
	 * @param  string $file File complete path
	 * @return string File content (or false if problem)
	 */
	protected function getContenuFichier($fichier) {
		$resultat = false;
		if (is_file($fichier)) {
			$f = @fopen($fichier, 'rb');
			if ($f) {
				if ($this->options['fichier_verrou']) @flock($f, LOCK_SH);
				$resultat = stream_get_contents($f);
				if ($this->options['fichier_verrou']) @flock($f, LOCK_UN);
				@fclose($f);
			}
		}
		return $resultat;
	}

	/**
	 * Put the given string into the given file
	 *
	 * @param  string $file   File complete path
	 * @param  string $string String to put in file
	 * @return boolean true if no problem
	 */
	protected function setContenuFichier($fichier, $chaine) {
		$resultat = false;
		$f = @fopen($fichier, 'ab+');
		if ($f) {
			if ($this->options['fichier_verrou']) @flock($f, LOCK_EX);
			fseek($f, 0);
			ftruncate($f, 0);
			$tmp = @fwrite($f, $chaine);
			if (!($tmp === FALSE)) {
				$resultat = true;
			}
			@fclose($f);
		}
		@chmod($fichier, $this->options['fichier_umask']);
		return $resultat;
	}

	/**
	 * Transform a file name into cache id and return it
	 *
	 * @param  string $fileName File name
	 * @return string Cache id
	 */
	protected function transformerNomFichierEnId($nom_de_fichier) {
		$prefixe = $this->options['fichier_prefixe'];
		return preg_replace('~^' . $prefixe . '---(.*)$~', '$1', $nom_de_fichier);
	}
}
?>