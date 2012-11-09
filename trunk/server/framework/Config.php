<?php
// declare(encoding='UTF-8');
/**
 * Config permet de charger automatiquement les fichiers ini du Framework et de l'application.
 * Elle offre l'accès en lecture seule aux paramètres de config.
 * C'est une Singleton.
 * Si vous avez besoin de modifier dynamiquement des paramètres de configuration, utiliser le @see Registe, il est fait pour ça.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2
 * @version	$Id: Config.php 402 2011-12-29 10:44:54Z jpm $
 * @link		/doc/framework/
 */

class Config {

	/** Instance de la classe pointant sur elle même (pour le pattern singleton). */
	private static $instance = null;

	/** Paramètres de configuration. */
	private static $parametres = array();

	private function __construct() {
		// Définition de paramètres avant chargement du config.ini
		self::$parametres = array(
			'fichier_config' => 'config%s.ini',
			'chemin_framework' => dirname(__FILE__).DS
			);

		// Chargement du fichier config.ini du Framework
		$existe = self::parserFichierIni(self::$parametres['chemin_framework'].sprintf(self::$parametres['fichier_config'], ''));
		if ($existe === false) {
			trigger_error("Veuillez configurer le Framework en renommant le fichier config.defaut.ini en config.ini.", E_USER_ERROR);
		}

		// Chargement du fichier config.ini par défaut de l'application
		$chemin_config_defaut_appli = self::$parametres['chemin_configurations'].sprintf(self::$parametres['fichier_config'], '');
		self::parserFichierIni($chemin_config_defaut_appli);

		// Chargement des fichiers config.ini contextuels
		if (PHP_SAPI == 'cli') {// mode console
			foreach ($_SERVER['argv'] as $cle => $valeur) {
				if ($valeur == '-contexte') {
					self::chargerFichierContexte($_SERVER['argv'][($cle+1)]);
					break;
				}
			}
		} else {// mode web
			// Pour Papyrus
			if (defined('PAP_VERSION')) {
				self::chargerFichierContexte('papyrus');
			}
			// Via le fichie .ini par défaut de l'appli
			if (Config::existeValeur('info.contexte', self::$parametres)) {
				self::chargerFichierContexte(Config::get('info.contexte'));
			}

			// Chargement du contexte présent dans le GET
			if (isset($_GET['contexte'])) {
				$_GET['contexte'] = strip_tags($_GET['contexte']);
				self::chargerFichierContexte($_GET['contexte']);
			}

			// Chargement du contexte présent dans le POST
			if (isset($_POST['contexte'])) {
				$_POST['contexte'] = strip_tags($_POST['contexte']);
				self::chargerFichierContexte($_POST['contexte']);
			}
		}
	}

	/**
	 * Charge le fichier de config correspondant au contexte
	 * @param string $contexte le contexte
	 */
	private static function chargerFichierContexte($contexte) {
		$chemin_config_appli_contextuel = self::$parametres['chemin_configurations'];
		$chemin_config_appli_contextuel .= sprintf(self::$parametres['fichier_config'], '_'.$contexte);
		self::parserFichierIni($chemin_config_appli_contextuel);
	}

	/**
	 * Parse le fichier ini donné en paramètre
	 * @param string $fichier_ini nom du fichier ini à parser
	 * @return array tableau contenant les paramètres du fichier ini
	 */
	private static function parserFichierIni($fichier_ini) {
		$retour = false;
		if (file_exists($fichier_ini)) {
			$ini = parse_ini_file($fichier_ini, true);
			$ini = self::analyserTableauIni($ini);
			$retour = true;
		}
		return $retour;
	}

	/**
	 * Fusionne un tableau de paramètres avec le tableau de paramètres global
	 * @param array $ini le tableau à fusionner
	 */
	private static function fusionner(array $ini) {
		self::$parametres = array_merge(self::$parametres, $ini);
	}

	/**
	 * Renvoie la valeur demandée grâce une chaîne de paramètres
	 * @param string $param la chaine de paramètres
	 * @param array $config le tableau de paramètre
	 * @return string la valeur de la chaine demandée
	 */
	private static function getValeur($param, $config) {
		if ($param === null) {
			return null;
		} else {
			if (isset($config[$param])) {
				return $config[$param];
			} else if (strpos($param, '.') !== false) {
				$pieces = explode('.', $param, 2);
				if (strlen($pieces[0]) && strlen($pieces[1])) {
					if (isset($config[$pieces[0]])) {
					   if (is_array($config[$pieces[0]])) {
					   		return self::getValeur($pieces[1], $config[$pieces[0]]);
					   }
					}
				}
			} else {
				return null;
			}
		}
	}

	/**
	 * Teste si param existe dans le tableau config
	 * @param string $param nom du paramètre
	 * @param array tableau de configuration
	 */
	private static function existeValeur($param, $config) {
		$retour = false;
		if (self::getValeur($param, $config) !== null) {
			$retour = true;
		}
		return $retour;
	}

	/**
	 * Vérifie si l'instance de classe à été crée, si non la crée
	 */
	private static function verifierCreationInstance() {
		if (empty(self::$instance)) {
			self::$instance = new Config();
		}
	}

	/**
	 * Analyse un tableau de paramètres.
	 * @param array $config le tableau de paramètres
	 * @return array le tableau analysé
	 */
	private static function analyserTableauIni($config = array()) {
		foreach ($config as $cle => &$valeur) {
			if (is_array($valeur)) {
				$config[$cle] = self::analyserTableauIni($valeur);
			} else {
				self::evaluerReferences($config, $cle);
				self::evaluerPhp($config, $cle);
				self::evaluerCle($config, $cle, $config[$cle]);
			}
			self::fusionner($config);
		}
		return $config;
	}

	/**
	 * Dans le cas des chaine de configuration à sous clé (ex.: cle.souscle)
	 * évalue les valeurs correspondantes et crée les sous tableaux associés.
	 * @param array $config tableau de configuration (par référence)
	 * @param string $cle la cle dans le tableau
	 * @param string $valeur la valeur à affecter
	 */
	private static function evaluerCle(&$config, $cle, $valeur) {
		if (strpos($cle, '.') !== false) {
			unset($config[$cle]);
			$pieces = explode('.', $cle, 2);
			if (strlen($pieces[0]) && strlen($pieces[1])) {
				if (isset($config[$pieces[0]]) && !is_array($config[$pieces[0]])) {
					$m = "Ne peut pas créer de sous-clé pour '{$pieces[0]}' car la clé existe déjà";
					trigger_error($m, E_USER_WARNING);
				} else {
					$config[$pieces[0]][$pieces[1]] = $valeur;
					$config[$pieces[0]] = self::evaluerCle($config[$pieces[0]], $pieces[1], $valeur);
				}
			} else {
				$m = "Clé invalide '$cle'";
				trigger_error($m, E_USER_WARNING);
			}
		} else {
			$config[$cle] = $valeur;
		}
		return $config;
	}

	/**
	 * Évalue les valeurs de références à une clé dans le tableau config (cas du ref:cle).
	 * @param array $config tableau de configuration
	 * @param string $cle la clé dont il faut évaluer les références
	 */
	private static function evaluerReferences(&$config, $cle) {
		if (preg_match_all('/{ref:([A-Za-z0-9_.-]+)}/', $config[$cle], $correspondances,  PREG_SET_ORDER)) {
			foreach ($correspondances as $ref) {
				$config[$cle] = str_replace($ref[0], self::getValeur($ref[1], self::$parametres), $config[$cle]);
			}
		}
	}

	/**
	 * Évalue le code php contenu dans un clé tu tableau config.
	 * @param array $config tableau de configuration (par référence)
	 * @param string $cle le clé du tableau dont il faut évaluer la valeur
	 */
	private static function evaluerPhp(&$config, $cle) {
		if (preg_match('/^php:(.+)$/', $config[$cle], $correspondances)) {
			eval('$config["'.$cle.'"] = '.$correspondances[1].';');
		}
	}

	/**
	 * Charge un fichier ini dans le tableau des paramètres de l'appli.
	 * @param string $fichier_ini le nom du fichier à charger
	 * @return array le fichier ini parsé
	 */
	public static function charger($fichier_ini) {
		self::verifierCreationInstance();
		return self::parserFichierIni($fichier_ini);
	}

	/**
	 * Accesseur pour la valeur d'un paramètre.
	 * @param string $param le nom du paramètre
	 * @return string la valeur du paramètre
	 */
	public static function get($param = null) {
		self::verifierCreationInstance();
		return self::getValeur($param, self::$parametres);
	}

	/**
	 * Vérifie si la valeur d'un paramètre existe.
	 * @param string $param le nom du paramètre
	 * @return boolean vrai si le paramètre existe, false sinon
	 */
	public static function existe($param) {
		self::verifierCreationInstance();
		return self::existeValeur($param, self::$parametres);
	}

	/**
	 * Vérifie que tous les paramêtres de config nécessaires au fonctionnement d'une classe existe dans les fichiers
	 * de configurations.
	 * L'utilisation de cette méthode depuis la classe Config évite de faire appel à une classe supplémentaire.
	 *
	 * @param array $parametres tableau des noms des paramètres de la config à verifier.
	 * @return boolean true si tous les paramétres sont présents sinon false.
	 */
	public static function verifierPresenceParametres(Array $parametres) {
		$ok = true;
		foreach ($parametres as $param) {
			if (is_null(self::get($param))) {
				$classe = get_class();
				$m = "L'utilisation de la classe $classe nécessite de définir '$param' dans un fichier de configuration.";
				trigger_error($m, E_USER_ERROR);
				$ok = false;
			}
		}
		return $ok;
	}
}
?>