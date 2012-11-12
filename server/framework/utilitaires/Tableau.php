<?php
// declare(encoding='UTF-8');
/**
 * Classe fournissant des méthodes statiques de manipulation des tableaux (Array).
 *
 * @category	PHP 5.2
 * @package	Utilitaire
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2
 * @version	$Id: Tableau.php 400 2011-11-25 16:26:26Z jpm $
 * @link		/doc/framework/
 */
class Tableau {

	/**
	 * Etend le tableau à étendre avec les données du tableau à copier. Si des clés sont identiques entre les deux tableaux
	 * une erreur est déclenchée et la valeur du tableau à étendre est gardée. Si les deux tableaux ont des clés numériques
	 * leurs valeurs sont gardées (à la différence de array_merge).
	 * Les tableaux sont passés par références et le tableau à copier est progressivement détruit pour éviter la consomation
	 * de mémoire.
	 *
	 * @param array $tableau_a_etendre
	 * @param array $tableau_a_copier
	 * @return void
	 */
	public static function etendre(Array &$tableau_a_etendre, Array &$tableau_a_copier) {
		$cles_existantes = null;
		foreach($tableau_a_copier as $cle => $val) {
			if (!isset($tableau_a_etendre[$cle])) {
				$tableau_a_etendre[$cle] = $val;
				unset($tableau_a_copier[$cle]);
			} else {
				$cles_existantes[] = $cle;
			}
		}
		if (is_array($cles_existantes)) {
			$e = "Le tableau a étendre contenait déjà les clés suivantes : ".implode(', ', $cles_existantes);
			trigger_error($e, E_USER_WARNING);
		}
	}

	/**
	 * @deprecated Utiliser la méthode trierMD()
	 * @see  trierMD()
	 */
	public static function trierTableauMd($array, $cols) {
		return self::trierMD($array, $cols);
	}

	/**
	 * Permet de trier un tableau multi-dimenssionnel en gardant l'ordre des clés.
	 *
	 * @param Array $array le tableau à trier
	 * @param Array $cols tableau indiquant en clé la colonne à trier et en valeur l'ordre avec SORT_ASC ou SORT_DESC
	 * @return Array le tableau trié.
	 * @author cagret at gmail dot com
	 * @see  http://fr.php.net/manual/fr/function.array-multisort.php Post du 21-Jun-2009 12:38
	 */
	public static function trierMD($array, $cols) {
		$colarr = array();
		foreach ($cols as $col => $order) {
		$colarr[$col] = array();
			foreach ($array as $k => $row) {
				$colarr[$col]['_'.$k] = strtolower($row[$col]);
			}
		}
		$params = array();
		foreach ($cols as $col => $order) {
			$params[] =& $colarr[$col];
			$orders = (array) $order;
			foreach($orders as $orderElement) {
				$params[] =& $orderElement;
			}
		}
		call_user_func_array('array_multisort', $params);
		$ret = array();
		$keys = array();
		$first = true;
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				if ($first) {
					$keys[$k] = substr($k, 1);
				}
				$k = $keys[$k];
				if (!isset($ret[$k])) {
					$ret[$k] = $array[$k];
				}
				$ret[$k][$col] = $array[$k][$col];
			}
			$first = false;
		}
		return $ret;
	}
}
?>