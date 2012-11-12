<?php
// declare(encoding='UTF-8');
/**
 * Classe fournissant des méthodes statiques concernant l'encodage et le décodage des caractères de variable.
 *
 * @category	PHP 5.2
 * @package	Utilitaire
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2 
 * @version	$Id: Encodage.php 299 2011-01-18 14:03:46Z jpm $
 * @link		/doc/framework/
 */
class Encodage {
	/**
	 * Méthode permettant d'encoder par défaut de ISO-8859-15 vers UTF-8 une variable ou un tableau de variables.
	 *
	 * @param mixed la chaine ou le tableau à encoder en UTF-8 depuis ISO-8859-15.
	 * @param string l'encodage d'origine si ce n'est pas ISO-8859-15.
	 * @return mixed la chaine ou le tableau encodé en UTF-8.
	 * @access protected
	 */
	public static function encoderEnUtf8(&$variable, $encodage = 'ISO-8859-15') {
		//echo print_r($variable, true)."\n";
		if (is_array($variable)) {
			foreach ($variable as $c => $v) {
				$variable[$c] = self::encoderEnUtf8($v);
			}
		} else {
			// Nous vérifions si nous avons un bon encodage UTF-8
			if (!is_numeric($variable) && !empty($variable) && !self::detecterUtf8($variable)) { 
				// Les nombres, les valeurs vides et ce qui est déjà en UTF-8 ne sont pas encodés.
				$variable = mb_convert_encoding($variable, 'UTF-8', $encodage);
			}
		}
		return $variable;
	}
	
	/**
	 * Méthode permettant de détecter réellement l'encodage UTF-8.
	 * mb_detect_encoding plante si la chaine de caractère se termine par un caractère accentué.
	 * Provient de  PHPDIG.
	 * 
	 * @param string la chaine à vérifier.
	 * @return bool true si c'est de UTF-8, sinon false.
	 * @access private
	 */
	public static function detecterUtf8($chaine) {
		if ($chaine === mb_convert_encoding(mb_convert_encoding($chaine, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32')) {
			return true;
		} else {
			return false;
		}
	}
}
?>