<?php
// declare(encoding='UTF-8');
/**
 * CLI permet de récupérer les paramètres passés en ligne de commande pour instancier une classe héritant de la classe abstraite
 * Script.
 * Elle va déclencher l'éxecution du script via l'appel de la méthode executer().
 * C'est une Singleton.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @author		Delphine CAUQUIL <delphine@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2
 * @since 		0.3
 * @version	$Id: Cli.php 386 2011-10-28 15:56:25Z jpm $
 * @link		/doc/framework/
 */

class Cli {

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('chemin_modules');


	/**
	 * Execute la ligne de commande en récupérant le nom du script à lancer et ses paramètres.
	 * Instancie la classe du script à lancer et appelle la méthode executer().
	 * @return void
	 */
	public static function executer() {
		if ($_SERVER['argc'] < 2){
		   trigger_error("Erreur: vous n'avez pas indiqué le nom du script. Voir '".$_SERVER['argv'][0]." help'.\n", E_USER_ERROR);
		}

		// Récupération de la ligne de commande
		$argv = $_SERVER['argv'];
		// Nous dépilons le nom du fichier qui initialise le framework et appele cette méthode.
		array_shift($argv);
		// Nous dépilons le nom du script à lancer
		$script = array_shift($argv);
		// Récupération des paramètres d'execution du script
		$parametres = self::getParametres($argv);

		// Chargement du script à lancer
		$Script = Cli::charger($script, $parametres);
		if (!is_null($Script)) {
			$Script->executer();
		}

		// 	Affichage des exceptions et erreurs générées par le script
		echo GestionnaireException::getExceptions();

		// Fin d'execution
		exit(0);
	}

	private static function charger($script_nom, $parametres) {
		$Script = null;
		Config::verifierPresenceParametres(self::$parametres_obligatoires);

		if (strpos($script_nom, DS)) {
			$decompoScriptNom = explode(DS, $script_nom);
			$script_nom = array_pop($decompoScriptNom);
			$dossier_nom = implode(DS, $decompoScriptNom);
		} else {
			$dossier_nom = strtolower($script_nom);
		}

		$classe_nom = self::obtenirNomClasse($script_nom);
		$fichier_script = Config::get('chemin_modules').$dossier_nom.DS.$classe_nom.'.php';

		if (!file_exists($fichier_script)){
			trigger_error("Erreur : script '$fichier_script' inconnu!\n", E_USER_ERROR);
		} else {
			require_once $fichier_script;
			if (!class_exists( $classe_nom)) {
				trigger_error("Erreur: impossible de trouver la classe de la commande : $classe_nom\n", E_USER_ERROR);
			} else {
				$Script = new $classe_nom($script_nom, $parametres);
			}
		}
		return $Script;
	}

	private static function obtenirNomClasse($script_nom) {
		$nom_classe = implode('', array_map('ucfirst', explode('_', strtolower($script_nom))));
		return $nom_classe;
	}

	private static function getParametres($argv) {
		$parametres = array();
		// Récupération des options
		while (count($argv)) {
			if (isset($argv[1]) && $argv[1]{0} != '-') {
				$param = array_shift($argv);
				$parametres[$param] = array_shift($argv);
			} elseif (!isset($argv[1]) || $argv[1]{0} == '-') {
				$parametres[array_shift($argv)] = null;
			} else {
				trigger_error("Erreur: valeur manquante pour le paramêtre '".$argv[0]."' \n", E_USER_ERROR);
			}
		}
		return $parametres;
	}
}
?>