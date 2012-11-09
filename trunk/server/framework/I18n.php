<?php
// declare(encoding='UTF-8');
/**
 * I18n permet de traduire une application à partir de données stockées dans des fichiers ini.
 * Si vous souhaitez utiliser le fonctionnement par défaut vous devrez :
 * - déposer les fichiers ini dans le dossier définit par la variable de config "chemin_i18n".
 * - nommer les fichiers selon la forme "locale.ini" (Ex.: fr.ini ou fr_CH.ini ).
 * 
 * Elle offre l'accès en lecture seule aux paramètres des fichiers ini.
 * C'est une Singleton. Une seule classe de traduction peut être instanciée par Application.
 *
 * @category	PHP 5.2
 * @package		Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @since 		0.3
 * @version		$Id: I18n.php 330 2011-02-24 18:03:07Z jpm $
 * @link		/doc/framework/
 */
class I18n {
	/** Format de traduction utilisant les fichier .ini */
	const FORMAT_INI = '.ini';
	
	/** Instance de la classe pointant sur elle même (pour le pattern singleton) */
	private static $instance = null;
	
	/** Fichiers de traduction disponibles. */
	private static $traductions = array();
	
	/** Langue courrante utilisée par l'application. */
	private static $langue = null;
	
	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('chemin_i18n', 'i18n_url_parametre', 'i18n_langue_defaut', 'debogage');
	
	private function __construct() {
		Config::verifierPresenceParametres(self::$parametres_obligatoires);
		self::trouverLangue();
	}
	
	/**
	 * Accesseur pour la valeur d'une traduction
	 * @param string $param le nom du paramètre
	 * @return string la valeur du paramètre
	 */
	public static function get($identifiant, $langue = null) {
		self::verifierCreationInstance();
		$texte = '';
		
		// Récupération de la langue actuellement demandée
		$langue_a_charger = self::$langue;
		if (!is_null($langue)) {
			$langue_a_charger = $langue;
		}
		
		if (!isset(self::$traductions[$langue_a_charger])) {
			// Tentative de chargement du fichier de traduction
			$chargement = self::charger($langue_a_charger);
			if ($chargement === false) {
				$m = "Le fichier d'i18n pour la langue '$langue_a_charger' demandée n'a pas été trouvé.";
				self::ajouterErreur($m);
			}
		}
		
		// Recherche de la langue dans le tableau des traductions
		if (isset(self::$traductions[$langue_a_charger]) && self::$traductions[$langue_a_charger] !== false) {
			// Recherche de la traduction demandée
			$valeur = self::getValeur($identifiant, self::$traductions[$langue_a_charger]);
			if ($valeur !== false) {
				$texte = $valeur;
			} else {
				$m = "Le traduction n'existe pas pour l'identifiant '$identifiant' demandé.";
				self::ajouterErreur($m);
			}
		}
		
		return $texte;
	}
	
	/**
	 * Charge un fichier ini dans le tableau des paramètres de l'appli
	 * @param string $fichier_ini le nom du fichier à charger
	 * @return boolean true, si le fichier a été trouvé et correctement chargé, sinon false.
	 */
	public static function charger($langue, $fichier = null, $format = self::FORMAT_INI) {
		self::verifierCreationInstance();
		$ok = false;
		
		// Création du chemin vers le fichier de traduction par défaut
		if (is_null($fichier)) {
			$fichier = Config::get('chemin_i18n').$langue.$format;
		}

		// Chargement		
		if ($format == self::FORMAT_INI) {
			$ok = self::chargerFichierIni($fichier, $langue);
		} else {
			$m = "Le format '$format' de fichier de traduction n'est pas pris en compte par le Framework.";
			self::ajouterErreur($m);
		}
		
		return $ok;
	}
	
	/**
	 * Définit la langue utiliser pour rechercher une traduction.
	 * @param string $fichier_ini le nom du fichier à charger
	 * @return array le fichier ini parsé
	 */
	public static function setLangue($langue) {
		self::verifierCreationInstance();
		self::$langue = $langue;
	}
	
	/**
	 * Renvoie la valeur demandé grâce une chaine de paramètres
	 * @param string $param la chaine identifiante
	 * @param array $i18n le tableau de traductions
	 * @return mixed la valeur correspondante à la chaine identifiante si elle est trouvée, sinon false.
	 */
	private static function getValeur($param, $i18n) {
		if ($param === null) {
			return false;
		} else {
			if (isset($i18n[$param])) {
				return $i18n[$param];
			} else if (strpos($param, '.') !== false) {
				$pieces = explode('.', $param, 2);
				if (strlen($pieces[0]) && strlen($pieces[1])) {
					if (isset($i18n[$pieces[0]])) {
					   if (is_array($i18n[$pieces[0]])) {
					   		return self::getValeur($pieces[1], $i18n[$pieces[0]]);
					   }
					}
				}
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Parse le fichier ini donné en paramètre
	 * @param string $fichier_ini nom du fichier ini à parser
	 * @param string $langue la langue correspondant au fichier
	 * @return boolean true si le chargement c'est bien passé, sinon false.
	 */
	private static function chargerFichierIni($fichier_ini, $langue) {
		self::$traductions[$langue] = false;
		if (file_exists($fichier_ini)) {
			$ini = parse_ini_file($fichier_ini, true);
			$ini = self::analyserTableauIni($ini);
			self::$traductions[$langue] = $ini;
		}
		return (self::$traductions[$langue] === false) ?  false : true;
	}
	
	/**
	 * Analyse un tableau de traductions pour évaluer les clés.
	 * @param array $i18n le tableau de traductions
	 * @return array le tableau analysé et modifié si nécessaire.
	 */
	private static function analyserTableauIni($i18n = array()) {
		//ATTENTION : il est important de passer la valeur par référence car nous la modifions dynamiquement dans la boucle
		foreach ($i18n as $cle => &$valeur) {
			if (is_array($valeur)) {
				$i18n[$cle] = self::analyserTableauIni($valeur);
			} else {
				$i18n = self::evaluerCle($i18n, $cle, $valeur);
			}
		}
		return $i18n;
	}
	
	/**
	 * Dans le cas des chaines de traduction à sous clé (ex.: cle.souscle), cette méthode
	 * évalue les valeurs correspondantes et créée les sous tableaux associés.
	 * @param array $i18n tableau de traductions (par référence)
	 * @param string $cle la cle dans le tableau
	 * @param string $valeur la valeur à affecter
	 */
	private static function evaluerCle($i18n, $cle, $valeur) {
		if (strpos($cle, '.') !== false) {
			unset($i18n[$cle]);
			$pieces = explode('.', $cle, 2);
			if (strlen($pieces[0]) && strlen($pieces[1])) {
				if (isset($i18n[$pieces[0]]) && !is_array($i18n[$pieces[0]])) {
					$m = "Ne peut pas créer de sous-clé pour '{$pieces[0]}' car la clé existe déjà";
					trigger_error($m, E_USER_WARNING);
				} else {
					$i18n[$pieces[0]][$pieces[1]] = $valeur;
					$i18n[$pieces[0]] = self::evaluerCle($i18n[$pieces[0]], $pieces[1], $valeur);
				}
			} else {
				$m = "Clé invalide '$cle'";
				trigger_error($m, E_USER_WARNING);
			}
		} else {
			$i18n[$cle] = $valeur;
		}
		return $i18n;
	}
	
	/**
	 * Cherche l'information sur la langue demandée par l'application
	 */
	private static function trouverLangue() {
		if (isset($_GET[Config::get('i18n_url_parametre')])) {
			self::$langue = $_GET[Config::get('i18n_url_parametre')];
		} else {
			self::$langue = Config::get('i18n_langue_defaut');
		}
	}
	
	/**
	 * Vérifie si l'instance de classe à été crée, si non la crée
	 */
	private static function verifierCreationInstance() {
		if (empty(self::$instance)) {
			self::$instance = new I18n();
		}
	}
	
	/**
	 * Ajouter une message d'erreur
	 */
	private static function ajouterErreur($m, $e = E_USER_WARNING) {
		if (Config::get('debogage') === true) {
			trigger_error($m, $e);
		}
	}
}
?>