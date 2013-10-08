<?php
/**
* Personnes
*
* @package depim
* @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
* @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
* @version 0.1
* @copyright 1999-2011 Jean-Pascal Milcent (jpm@clapas.org)
*/
class Personnes extends RestService {

	/** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = true;


	public function consulter($ressources, $parametres) {
		$resultat = '';
		$reponseHttp = new ReponseHttp();
		try {
			$resultat = $this->getPersonnes();
			$reponseHttp->setResultatService($resultat);
		} catch (Exception $e) {
			$reponseHttp->ajouterErreur($e);
		}
		$reponseHttp->emettreLesEntetes();
		$corps = $reponseHttp->getCorps();
		return $corps;

	}

	private function getPersonnes() {
		$requete = "SELECT * FROM personne ORDER BY nom";
		$tables = $this->getBdd()->recupererTous($requete);
		return $tables;
	}
}
?>