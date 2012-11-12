<?php
/**
* Description :
* Classe principale de chargement des services d'eFlore.
*
* @package depim
* @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
* @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
* @version 0.1
* @copyright 1999-2011 Jean-Pascal Milcent (jpm@clapas.org)
*/
class Test extends RestService {

	/** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = true;


	public function consulter($ressources, $parametres) {
		return json_encode('test');
	}
}
?>