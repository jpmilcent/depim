<?php
// declare(encoding='UTF-8');
/**
 * Classe Cache permettant de mettre en cache des données.
 * Basée sur les principes de Zend_Cache (Copyright (c) 2005-2010, Zend Technologies USA, Inc. All rights reserved.)
 *
 * @category	php 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://framework.zend.com/license/new-bsd Licence New BSD
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: Cache.php 299 2011-01-18 14:03:46Z jpm $
 * @link		/doc/framework/
 */
class Cache {
	/** Socke les enregistrements du cache dans des fichiers textes de façon extremement simple. */
	const STOCKAGE_MODE_SIMPLE = "FichierSimple";
	/** Socke les enregistrements du cache dans des fichiers textes. */
	const STOCKAGE_MODE_FICHIER = "Fichier";
	/** Socke les enregistrements du cache dans une base de données SQLite. */
	const STOCKAGE_MODE_SQLITE = "Sqlite";
	
	/** 'tous' (par défaut) : supprime tous les enregistrements. */
	const NETTOYAGE_MODE_TOUS = "tous";
	/** 'expiration' : supprime tous les enregistrements dont la date d'expériration est dépassée. */
	const NETTOYAGE_MODE_EXPIRATION = "expiration";
	/** 'avecLesTags' : supprime tous les enregistrements contenant tous les tags indiqués. */
	const NETTOYAGE_MODE_AVEC_LES_TAGS = "avecLesTags";
	/** 'sansLesTags' : supprime tous les enregistrements contenant aucun des tags indiqués. */
	const NETTOYAGE_MODE_SANS_LES_TAGS = "sansLesTags";
	/** 'avecUnTag' : supprime tous les enregistrements contenant au moins un des tags indiqués. */
	const NETTOYAGE_MODE_AVEC_UN_TAG = "avecUnTag";
	
	/**
	 * Dernier identifiant de cache utilisé.
	 *
	 * @var string $dernier_id
	 */
	private $dernier_id = null;
	
	/**
	 * Les options disponibles pour le cache :
	 * ====> (string) stockage_mode :
	 * Indique le mode de stockage du cache à utiliser parmis :
	 * - Cache::STOCKAGE_MODE_FICHIER : sous forme d'une arborescence de fichiers et dossier
	 * - Cache::STOCKAGE_MODE_SQLITE : sous forme d'une base de données SQLite
	 * 
	 * ====> (string) stockage_chemin :
	 * Chemin vers :
	 * - Cache::STOCKAGE_MODE_FICHIER : le dossier devant contenir l'arborescence.
	 * - Cache::STOCKAGE_MODE_SQLITE : le fichier contenant la base SQLite.
	 * 
	 * ====> (boolean) controle_ecriture :
	 * - Active / Désactive le controle d'écriture (le cache est lue jute après l'écriture du fichier pour détecter sa corruption)
	 * - Activer le controle d'écriture ralentira légèrement l'écriture du fichier de cache mais pas sa lecture
	 * Le controle d'écriture peut détecter la corruption de fichier mais ce n'est pas un système de controle parfait.
	 *
	 * ====> (boolean) mise_en_cache :
	 * - Active / Désactive la mise en cache
	 * (peut être très utile pour le débogage des scripts utilisant le cache
	 *
	 * =====> (string) cache_id_prefixe :
	 * - préfixe pour les identifiant de cache ( = espace de nom)
	 *
	 * ====> (boolean) serialisation_auto :
	 * - Active / Désactive la sérialisation automatique
	 * - Peut être utilisé pour sauver directement des données qui ne sont pas des chaines (mais c'est plus lent)
	 *
	 * ====> (int) nettoyage_auto :
	 * - Désactive / Régler le processus de nettoyage automatique
	 * - Le processus de nettoyage automatiques détruit les fichier trop vieux (pour la durée de vie donnée)
	 *   quand un nouveau fichier de cache est écrit :
	 *	 0			   => pas de nettoyage automatique
	 *	 1			   => nettoyage automatique systématique
	 *	 x (integer) > 1 => nettoyage automatique toutes les 1 fois (au hasard) sur x écriture de fichier de cache
	 *
	 * ====> (int) duree_de_vie :
	 * - Durée de vie du cache (en secondes)
	 * - Si null, le cache est valide indéfiniment.
	 *
	 * @var array $options les options disponibles pour le cache .
	 */
	protected $options = array(
		'stockage_mode'				 => self::STOCKAGE_MODE_FICHIER,
		'stockage_chemin'				 => null,	
		'controle_ecriture'			 => true,
		'mise_en_cache'		  		 => true,
		'cache_id_prefixe'		  		 => null,
		'serialisation_auto'		  	 => false,
		'nettoyage_auto'				 => 10,
		'duree_de_vie'			 		 => 3600,
	);
	
	protected $stockage = null;
	
	public function __construct($options = array(), $options_stockage = array()) {
		$this->initialiserOptionsParConfig();
		$this->setOptions($options);
		if ($this->options['stockage_mode'] == self::STOCKAGE_MODE_FICHIER) {
			$this->stockage = new CacheFichier($options_stockage, $this);
			$this->stockage->setEmplacement($this->options['stockage_chemin']);
		} else if ($this->options['stockage_mode'] == self::STOCKAGE_MODE_SQLITE) {
			$this->stockage = new CacheSqlite($options_stockage, $this);
			$this->stockage->setEmplacement($this->options['stockage_chemin']);
		} else {
			trigger_error("Ce mode de stockage n'existe pas ou ne supporte pas la création par le constructeur", E_USER_WARNING);
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
	
	/**
	 * Permet de (re-)définir l'emplacement pour le stockage du cache.
	 * En fonction du mode de stockage utilisé , l'emplacement indiqué correspondra au chemin du :
	 *  - dossier où stocker les fichiers pour le mode "fichier".
	 *  - fichier de la base de données pour le mode "sqlite". 
	 * @param string $emplacement chemin vers dossier (Cache::STOCKAGE_MODE_FICHIER) ou fichier base Sqlite (Cache::STOCKAGE_MODE_SQLITE)
	 * @return void 
	 */
	public function setEmplacement($emplacement) {
		if ($emplacement != null) {
			$this->executerMethodeStockage('setEmplacement', array($emplacement));
		} else {
			trigger_error("L'emplacement ne peut pas être null.", E_USER_WARNING);
		}
	}
	
	public static function fabriquer($mode, $options = array()) {
		if ($mode == self::STOCKAGE_MODE_SIMPLE) {
			return new CacheSimple($options);
		} else {
			trigger_error("Le mode '$mode' de stockage n'existe pas ou ne supporte pas la création par fabrique", E_USER_WARNING);
		}
		return false;
	}
	
	/**
	 * Teste si un cache est disponible pour l'identifiant donné et (si oui) le retourne (false dans le cas contraire)
	 *
	 * @param  string  $id Identifiant de cache.
	 * @param  boolean $ne_pas_tester_validiter_du_cache Si mis à true, la validité du cache n'est pas testée
	 * @return mixed|false Cached datas
	 */
	public function charger($id, $ne_pas_tester_validiter_du_cache = false) {
		$donnees = false;
		if ($this->options['mise_en_cache'] === true) {
			$id = $this->prefixerId($id);
			$this->dernier_id = $id;
			self::validerIdOuTag($id);
			$donnees = $this->executerMethodeStockage('charger', array($id, $ne_pas_tester_validiter_du_cache));
			$donnees = $this->deserialiserAutomatiquement($donnees);
		}
		return $donnees;
	}
	
	/**
	 * Test if a cache is available for the given id
	 *
	 * @param  string $id Cache id
	 * @return int|false Last modified time of cache entry if it is available, false otherwise
	 */
	public function tester($id) {
		$resultat = false;
		if ($this->options['mise_en_cache'] === true) {
		  	$id = $this->prefixerId($id);
			self::validerIdOuTag($id);
			$this->dernier_id = $id;
			$resultat = $this->executerMethodeStockage('tester', array($id));
		}
		return $resultat;
	}
	
	/**
	 * Sauvegarde en cache les données passées en paramètre.
	 *
	 * @param  mixed $donnees Données à mettre en cache (peut être différent d'une chaine si serialisation_auto vaut true).
	 * @param  string $id	 Identifiant du cache (s'il n'est pas définit, le dernier identifiant sera utilisé).
	 * @param  array $tags Mots-clés du cache.
	 * @param  int $duree_de_vie_specifique Si != false, indique une durée de vie spécifique pour cet enregistrement en cache (null => durée de vie infinie)
	 * @return boolean True si aucun problème n'est survenu.
	 */
	public function sauver($donnees, $id = null, $tags = array(), $duree_de_vie_specifique = false) {
		$resultat = true;
		if ($this->options['mise_en_cache'] === true) {
			$id = ($id === null) ? $this->dernier_id : $this->prefixerId($id);
	
			self::validerIdOuTag($id);
			self::validerTableauDeTags($tags);
			$donnees = $this->serialiserAutomatiquement($donnees);
			$this->nettoyerAutomatiquement();
			
			$resultat = $this->executerMethodeStockage('sauver', array($donnees, $id, $tags, $duree_de_vie_specifique));
			
			if ($resultat == false) {
				// Le cache étant peut être corrompu, nous le supprimons
				$this->supprimer($id);
			} else {
				$resultat = $this->controlerEcriture($id, $donnees);
			}
		}
		return $resultat;
	}
	
	/**
	 * Supprime un enregistrement en cache.
	 *
	 * @param  string $id Identificant du cache à supprimer.
	 * @return boolean True si ok
	 */
	public function supprimer($id) {
		$resultat = true;
		if ($this->options['mise_en_cache'] === true) {
			$id = $this->prefixerId($id);
			self::validerIdOuTag($id);
		   $resultat = $this->executerMethodeStockage('supprimer', array($id));
		}
		return $resultat;
	}
	
	/**
	 * Nettoyage des enregistrements en cache
	 * 
	 * Mode de nettoyage disponibles :
	 * 'tous' (défaut)	=> supprime tous les enregistrements ($tags n'est pas utilisé)
	 * 'expiration'		=> supprime tous les enregistrements dont la date d'expériration est dépassée ($tags n'est pas utilisé)
	 * 'avecLesTag'		=> supprime tous les enregistrements contenant tous les tags indiqués
	 * 'sansLesTag'		=> supprime tous les enregistrements contenant aucun des tags indiqués
	 * 'avecUnTag'			=> supprime tous les enregistrements contenant au moins un des tags indiqués
	 *
	 * @param string $mode mode de nettoyage
	 * @param array|string $tags peut être un tableau de chaîne ou une simple chaine.
	 * @return boolean True si ok
	 */
	public function nettoyer($mode = self::NETTOYAGE_MODE_TOUS, $tags = array()) {
		$resultat = true;
		if ($this->options['mise_en_cache'] === true) {
			if (!in_array($mode, array(Cache::NETTOYAGE_MODE_TOUS,
				Cache::NETTOYAGE_MODE_EXPIRATION,
				Cache::NETTOYAGE_MODE_AVEC_LES_TAGS,
				Cache::NETTOYAGE_MODE_SANS_LES_TAGS,
				Cache::NETTOYAGE_MODE_AVEC_UN_TAG))) {
				trigger_error("Le mode de nettoyage du cache indiqué n'est pas valide", E_USER_WARNING);
			}
			self::validerTableauDeTags($tags);
			
			$resultat = $this->executerMethodeStockage('nettoyer', array($mode, $tags));
		}
		return $resultat;
	}

	/**
	 * Return an array of stored cache ids
	 *
	 * @return array array of stored cache ids (string)
	 */
	public function getIds() {
		$ids = $this->executerMethodeStockage('getIds');
		$ids = $this->supprimerPrefixe($ids);
		return $ids;
	}

	/**
	 * Return an array of stored tags
	 *
	 * @return array array of stored tags (string)
	 */
	public function getTags() {
		return $this->executerMethodeStockage('getTags');
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
		$ids = $this->executerMethodeStockage('getIdsAvecLesTags', array($tags));
		$ids = $this->supprimerPrefixe($ids);
		return $ids;
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
	   	$ids = $this->executerMethodeStockage('getIdsSansLesTags', array($tags));
		$ids = $this->supprimerPrefixe($ids);
		return $ids;
	}

	/**
	 * Return an array of stored cache ids which match any given tags
	 *
	 * In case of multiple tags, a logical OR is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of matching any cache ids (string)
	 */
	public function getIdsAvecUnTag($tags = array()) {
		$ids = $this->executerMethodeStockage('getIdsAvecUnTag', array($tags));
		$ids = $this->supprimerPrefixe($ids);
		return $ids;
	}

	/**
	 * Return the filling percentage of the backend storage
	 *
	 * @return int integer between 0 and 100
	 */
	public function getPourcentageRemplissage() {
		return $this->executerMethodeStockage('getPourcentageRemplissage');
	}

	/**
	 * Return an array of metadatas for the given cache id
	 *
	 * The array will include these keys :
	 * - expire : the expire timestamp
	 * - tags : a string array of tags
	 * - mtime : timestamp of last modification time
	 *
	 * @param string $id cache id
	 * @return array array of metadatas (false if the cache id is not found)
	 */
	public function getMetadonnees($id) {
		$id = $this->prefixerId($id);
		return $this->executerMethodeStockage('getMetadonnees', array($id));
	}

	/**
	 * Give (if possible) an extra lifetime to the given cache id
	 *
	 * @param string $id cache id
	 * @param int $extraLifetime
	 * @return boolean true if ok
	 */
	public function ajouterSupplementDureeDeVie($id, $supplement_duree_de_vie) {
		$id = $this->prefixerId($id);
		return $this->executerMethodeStockage('ajouterSupplementDureeDeVie', array($id, $supplement_duree_de_vie));
	}
	

	/**
	 * Fabrique et retourne l'identifiant du cache avec son préfixe.
	 *
	 * Vérifie l'option 'cache_id_prefixe' et retourne le nouvel id avec préfixe ou simplement l'id lui même si elle vaut null.
	 *
	 * @param  string $id Identifiant du cache.
	 * @return string Identifiant du cache avec ou sans préfixe.
	 */
	private function prefixerId($id) {
		$nouvel_id = $id;
		if (($id !== null) && isset($this->options['cache_id_prefixe'])) {
			$nouvel_id = $this->options['cache_id_prefixe'].$id;
		}
		return $nouvel_id;
	}
	
	private function executerMethodeStockage($methode, $params = null) {
		if (method_exists($this->stockage, $methode)) {
			if ($params == null) {
				$resultat = call_user_func(array($this->stockage, $methode));
			} else {
				$resultat = call_user_func_array(array($this->stockage, $methode), $params);
			}
		} else {
			$resultat = false;
			trigger_error("La méthode '$methode' n'existe pas dans la classe '".get_class($this)."'.", E_USER_WARNING);
		}
		return $resultat;
	}
	
	private function supprimerPrefixe($ids) {
		// Il est nécessaire de retirer les cache_id_prefixe des ids (voir #ZF-6178, #ZF-7600)
		if (isset($this->options['cache_id_prefixe']) && $this->options['cache_id_prefixe'] !== '') {
			$prefixe =& $this->options['cache_id_prefixe'];
			$prefixe_longueur = strlen($prefixe);
			foreach ($ids as &$id) {
				if (strpos($id, $prefixe) === 0) {
					$id = substr($id, $prefixe_longueur);
				}
			}
		}
		return $ids;
	}
	
	private function controlerEcriture($id, $donnees_avant_ecriture) {
		$resultat = true;
		if ($this->options['controle_ecriture']) {
			$donnees_apres_ecriture = $this->executerMethodeStockage('charger', array($id, true));
			if ($donnees_avant_ecriture != $donnees_apres_ecriture) {
				$this->executerMethodeStockage('supprimer', array($id));
				$resultat = false;
			}
		}
		return $resultat;
	}
	
	private function deserialiserAutomatiquement($donnees) {
		if ($donnees !== false && $this->options['serialisation_auto']) {
				// we need to unserialize before sending the result
				$donnees = unserialize($donnees);
		}
		return $donnees;
	}
	
	private function serialiserAutomatiquement($donnees) {
		if ($this->options['serialisation_auto']) {
			// we need to serialize datas before storing them
			$donnees = serialize($donnees);
		} else {
			if (!is_string($donnees)) {
				trigger_error("Les données doivent être une chaîne de caractères ou vous devez activez l'option serialisation_auto = true", E_USER_WARNING);
			}
		}
		return $donnees;
	}
	
	private function nettoyerAutomatiquement() {
		if ($this->options['nettoyage_auto'] > 0) {
			$rand = rand(1, $this->options['nettoyage_auto']);
			if ($rand == 1) {
				$this->nettoyer(self::NETTOYAGE_MODE_EXPIRATION);
			}
		}
	}
	
	/**
	 * Valide un identifiant de cache ou un tag (securité, nom de fichiers fiables, préfixes réservés...)
	 *
	 * @param  string $chaine Identificant de cache ou tag
	 * @return void
	 */
	protected static function validerIdOuTag($chaine) {
		if (!is_string($chaine)) {
			trigger_error('Id ou tag invalide : doit être une chaîne de caractères', E_USER_ERROR);
		}
		if (substr($chaine, 0, 9) == 'internal-') {
			trigger_error('"internal-*" identifiants ou tags sont réservés', E_USER_WARNING);
		}
		if (!preg_match('~^[a-zA-Z0-9_]+$~D', $chaine)) {
			trigger_error("Id ou tag invalide '$chaine' : doit contenir seulement [a-zA-Z0-9_]", E_USER_WARNING);
		}
	}

	/**
	 * Valide un tableau de tags  (securité, nom de fichiers fiables, préfixes réservés...)
	 *
	 * @param  array $tags tableau de tags
	 * @return void
	 */
	protected static function validerTableauDeTags($tags) {
		if (!is_array($tags)) {
			trigger_error("Tableau de tags invalide : doit être un tableau 'array'", E_USER_WARNING);
		}
		foreach ($tags as $tag) {
			self::validerIdOuTag($tag);
		}
		reset($tags);
	}
	
	/**
	 * Calcule et retourne le timestamp d'expiration
	 *
	 * @return int timestamp d'expiration (unix timestamp)
	 */
	public function getTimestampExpiration($duree_de_vie) {
		if ($duree_de_vie === false) {
			if (isset($this->options['duree_de_vie']) && is_int($this->options['duree_de_vie'])) {
				$duree_de_vie = (int) $this->options['duree_de_vie'];
			} else {
				$duree_de_vie = 3600;
			}
		}
		$timestamp = ($duree_de_vie === null) ? 9999999999 : (time() + $duree_de_vie);
		return $timestamp;
	}
	
}