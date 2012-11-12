<?php
// declare(encoding='UTF-8');
/**
 * Classe de base du Framework :
 *  - fournissant des infos sur l'application,
 *  - paramétrant l'environnement de l'appli et du framework,
 *  - réalisant des traitements sur les variables globales ($_GET, $_POST, $_COOKIE...)
 *
 * Cette classe contient la fonction de chargement automatique de classes.
 * Ce fichier doit toujours rester à la racine du framework car il initialise le chemin
 * de l'application en se basant sur son propre emplacement.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2
 * @version	$Id: Framework.php 391 2011-11-09 16:55:53Z jpm $
 * @since		0.3
 * @link		/doc/framework/
 */
class Framework {

	/** Variable statique indiquant que les tableaux _GET et _POST ont déjà été encodé au format de l'appli. */
	private static $encodage = false;

	/** Tableau d'informations sur l'application */
	private static $info = null;

	/** Chemin de base de l'application */
	private static $chemin = null;

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('chemin_controleurs', 'chemin_modeles', 'chemin_bibliotheque',
		'url_arg_separateur_entree', 'url_arg_separateur_sortie',
		'encodage_sortie', 'encodage_appli');

	/**
	 * Initialise l'environnement nécessaire au Framework : constantes globales, méthodeles autoload, séparateur d'arguments
	 * d'url.
	 * Cette méthode est appelée automatiquement suite à la définition du chemin de l'application via Application::setChemin().
	 */
	private static function initialiserEnvironnement() {
		self::enregistrerMethodesAutoload();
		self::initialiserUrl();
	}

	/**
	 * Initialise différentes classes du Framework nécessaires pour le fonctionnement de l'application.
	 * Ces classes sont ensuites controlées via les fichiers de config.ini.
	 * Elle est appelée automatiquement suite à la définition du chemin de l'application via Application::setChemin().
	 */
	private static function initialiserFramework() {
		GestionnaireException::configurer();
		//Log::configurer();
		self::verifierEtReencoderTableauRequete();
	}

	/**
	 * Redéfinit des constantes globales utiles pour le Framework et les applis.
	 */
	private static function definirConstantesGlobales() {
		if (!defined('DS')) {
			/** Redéfinition de la constante DIRECTORY_SEPARATOR en version abrégée DS */
			define('DS', DIRECTORY_SEPARATOR);
		}
		if (!defined('PS')) {
			/** Redéfinition de la constante PATH_SEPARATOR en version abrégée PS */
			define('PS', PATH_SEPARATOR);
		}
	}

	private static function definirCheminAppli($chemin) {
		if (is_file($chemin)) {
			self::$chemin = dirname($chemin).DS;
		} else if (is_dir($chemin)) {
			self::$chemin = $chemin;
		} else {
			throw new Exception("Le chemin indiqué '$chemin' n'est ni un fichier ni un dossier.");
		}
	}

	private static function enregistrerMethodesAutoload() {
		spl_autoload_register(array(get_class(), 'autoloadFw'));

		// Vérification des paramètres de configuration obligatoire pour assurer le fonctionnement du Framework
		Config::verifierPresenceParametres(self::$parametres_obligatoires);

		// Initialisation du gestionnaire d'erreur avant toute chose
		GestionnaireException::initialiser();

		spl_autoload_register(array(get_class(), 'autoloadAppliDefaut'));

		// Autoload défini par l'application
		if (function_exists('__autoload')) {
			spl_autoload_register('__autoload');
		}
	}

	/**
	 * Autoload pour le Framework.
	 */
	private static function autoloadFw($nom_classe_fw) {
		$dossiers_classes = array(	dirname(__FILE__).DS,
									dirname(__FILE__).DS.'utilitaires'.DS);
		foreach ($dossiers_classes as $chemin) {
			$fichier_a_tester = $chemin.$nom_classe_fw.'.php';
			if (file_exists($fichier_a_tester)) {
				include_once $fichier_a_tester;
				return null;
			}
		}
	}

	/**
	 * Autoload par défaut pour l'application
	 */
	private static function autoloadAppliDefaut($nom_classe) {
		$dossiers_classes = array(	Config::get('chemin_controleurs'),
									Config::get('chemin_modeles'),
									Config::get('chemin_bibliotheque'));

		foreach ($dossiers_classes as $chemin) {
			$fichier_a_tester = $chemin.$nom_classe.'.php';
			if (file_exists($fichier_a_tester)) {
				include_once $fichier_a_tester;
				return null;
			}
		}
	}

	/**
	 * Initialise le format des urls.
	 */
	private static function initialiserUrl() {
		ini_set('arg_separator.input', Config::get('furl_arg_separateur_entree'));
		ini_set('arg_separator.output', Config::get('url_arg_separateur_sortie'));
	}

	/**
	 * Permet d'indiquer le chemin de base de l'Application.
	 * Cette méthode doit obligatoirement être utilisée par l'application pour que le Framework fonctionne correctement.
	 * @param string $chemin_fichier_principal chemin de base
	 */
	public static function setCheminAppli($chemin_fichier_principal) {
		if (self::$chemin === null) {
			if (!file_exists($chemin_fichier_principal)) {
				trigger_error("Le fichier indiqué n'existe pas. Utilisez __FILE__ dans la méthode setCheminAppli().", E_USER_ERROR);
			} else {
				self::definirConstantesGlobales();
				self::definirCheminAppli($chemin_fichier_principal);
				self::initialiserEnvironnement();
				self::initialiserFramework();
			}
		} else {
			trigger_error("Le chemin de l'application a déjà été enregistré auprès du Framework", E_USER_WARNING);
		}
	}

	/**
	 * accesseur pour le chemin
	 * @return string le chemin
	 */
	public static function getCheminAppli() {
		return self::$chemin;
	}

	/** Le tableau des informations sur l'application possède les clés suivantes :
	 * - nom : nom de l'application
	 * - abr : abréviation de l'application
	 * - encodage : encodage de l'application (ISO-8859-15, UTF-8...)
	 *
	 * @param array $info tableau fournissant des informations sur l'application
	 * @return void
	 */
	public static function setInfoAppli($info) {
		if (self::$info === null) {
			self::$info = $info;
		} else {
			trigger_error("Le informations de l'application ont déjà été enregistrées auprès du Framework", E_USER_WARNING);
		}
	}

	/**
	 * Accesseur pour le tableau d'infos sur l'application.
	 * @param string $cle la clé à laquelle on veut accéder
	 */
	public static function getInfoAppli($cle = null) {
		if ($cle !== null) {
			if (isset(self::$info[$cle])) {
				return self::$info[$cle];
			}
		} else {
			return self::$info;
		}
	}

	/**
	 * Procédure vérifiant l'encodage des tableaux $_GET et $_POST et les transcodant dans l'encodage de l'application
	 */
	protected static function verifierEtReencoderTableauRequete() {
		if (self::$encodage == false && Config::get('encodage_sortie') != Config::get('encodage_appli')) {
			$_POST = self::encoderTableau($_POST, Config::get('encodage_appli'), Config::get('encodage_sortie'));
			$_GET = self::encoderTableau($_GET, Config::get('encodage_appli'), Config::get('encodage_sortie'));

			// Traitement des magic quotes
			self::verifierEtTraiterSlashTableauRequete();

			self::$encodage = true;
		}
	}

	/**
	 * Procédure vérifiant l'activation des magic quotes et remplacant les slash dans les tableaux de requete
	 */
	private static function verifierEtTraiterSlashTableauRequete() {
		if (get_magic_quotes_gpc()) {
			if (!function_exists('nettoyerSlashProfond')) {
				function nettoyerSlashProfond($valeur) {
					return ( is_array($valeur) ) ? array_map('nettoyerSlashProfond', $valeur) : stripslashes($valeur);
				}
			}
			$_GET = array_map('nettoyerSlashProfond', $_GET);
			$_POST = array_map('nettoyerSlashProfond', $_POST);
			$_COOKIE = array_map('nettoyerSlashProfond', $_COOKIE);
		}
	}

	/**
	 * Fonction récursive transcodant toutes les valeurs d'un tableau de leur encodage d'entrée vers un encodage de sortie donné
	 * @param $tableau Array Un tableau de données à encoder
	 * @param $encodage_sortie String l'encodage vers lequel on doit transcoder
	 * @param $encodage_entree String l'encodage original des chaines du tableau (optionnel)
	 * @return Array Le tableau encodé dans l'encodage de sortie
	 *
	 */
	final static protected function encoderTableau($tableau, $encodage_sortie, $encodage_entree = null) {
		if (is_array($tableau)) {
			foreach ($tableau as $cle => $valeur) {
				if (is_array($valeur)) {
				 	$tableau[$cle] = self::encoderTableau($valeur, $encodage_sortie, $encodage_entree);
				} else {
					$tableau[$cle] = mb_convert_encoding($valeur, $encodage_sortie, $encodage_entree);
				}
			}
		}
		return $tableau;
	}
}
?>