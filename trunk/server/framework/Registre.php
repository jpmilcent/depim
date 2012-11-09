<?php
// declare(encoding='UTF-8');
/**
 * Classe Registre, qui permet un accès à différentes variables et paramètres à travers les autres classes.
 * C'est un remplaçant à la variable magique $_GLOBALS de Php.
 * C'est un singleton.
 * Si vous voulez paramètré votre application via un fichier de configuration, utilisez plutôt la classe @see Config.
 *
 * @category	php 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jmp@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: Registre.php 301 2011-01-18 14:23:52Z jpm $
 * @link		/doc/framework/
 *
*/
class Registre {

	/** Tableau associatif stockant les variables. */
	private static $stockage = array();
	
	/**
	 * Ajoute un objet au tableau selon un intitulé donné.
	 * @param string l'intitulé sous lequel l'objet sera conservé
	 * @param mixed l'objet à conserver
	 */
	public static function set($intitule, $objet) {
		if (is_array($objet) && isset(self::$stockage[$intitule])) {
			self::$stockage[$intitule] = array_merge((array) self::$stockage[$intitule], (array) $objet);
			$message = "Le tableau $intitule présent dans le registre a été fusionné avec un nouveau tableau de même intitulé !";
			trigger_error($message, E_USER_WARNING);
		} else {
			self::$stockage[$intitule] = $objet;
		}
	}

	/**
	 * Renvoie le contenu associé à l'intitulé donné en paramètre.
	 * @return mixed l'objet associé à l'intitulé ou null s'il n'est pas présent
	 */
	public static function get($intitule) {
		$retour = (isset(self::$stockage[$intitule])) ? self::$stockage[$intitule] : null;
		return $retour;
	}

	/**
	 * Détruit l'objet associé à l'intitulé, n'a pas d'effet si il n'y a pas d'objet associé.
	 * @param string l'intitulé de l'entrée du registre à détruire.
	 */
	public static function detruire($intitule) {
		if (isset(self::$stockage[$intitule])) {
			unset(self::$stockage[$intitule]);
		}
	}

	/**
	 * Teste si le registre contient une donnée pour un intitulé d'entrée donné.
	 * @param string l'intitulé de l'entrée du registre à tester.
	 * @return boolean true si un objet associé à cet intitulé est présent, false sinon
	 */
	public static function existe($intitule) {
		$retour = (isset(self::$stockage[$intitule])) ? true : false;
		return $retour;
	}
}
?>