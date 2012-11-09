<?php
// declare(encoding='UTF-8');
/**
 * Classe fournissant des constantes correspondant à des expressions régulières de vérification très courrantes.
 *
 * @category	PHP 5.2
 * @package	Utilitaire
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2 
 * @version	$Id: Pattern.php 299 2011-01-18 14:03:46Z jpm $
 * @link		/doc/framework/
 */
class Pattern {
	const PRENOM = "[\p{L}-]+";// Pattern prénom
	const NOM = "[\p{Lu}]+";// Pattern nom
	const COURRIEL = "[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?";// Pattern courriel
	const URL =  "^(?:(?:ht|f)tp(?:s?)\\:\\/\\/|~/|/)?(?:\\w+:\\w+@)?(?:(?:[-\\w]+\\.)+(?:com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum|travel|[a-z]{2}))(?::[\\d]{1,5})?(?:(?:(?:/(?:[-\\w~!$+|.,=]|%[a-f\\d]{2})+)+|/)+|\\?|#)?(?:(?:\\?(?:[-\\w~!$+|.,*:]|%[a-f\\d{2}])+=(?:[-\\w~!$+|.,*:=]|%[a-f\\d]{2})*)(?:&(?:[-\\w~!$+|.,*:]|%[a-f\\d{2}])+=(?:[-\\w~!$+|.,*:=]|%[a-f\\d]{2})*)*)*(?:#(?:[-\\w~!$+|.,*:=]|%[a-f\\d]{2})*)?$";
	const HEURE_MINUTE = "^(?:[0-1][0-9]|2[0-4]):(?:[0-5][0-9]|60)$";// Heure au format 24h avec séparateur d'heure et minute ':' 
	const LATITUDE = "^-?([0-8]?[0-9]([.,][0-9]*)?|90)$"; // Nombre décimal positif ou négatif allant de 0 à 89 ou nombre entier valant 90 avec pour séparateur des décimales "." ou ","
	const LONGITUDE = "^-?((1[0-7][0-9]|[1-9]?[0-9])([.,][0-9]*)?|180)$"; // Nombre décimal positif ou négatif allant de 0 à 179 ou nombre entier valant 180 avec pour séparateur des décimales "." ou ","
}
?>