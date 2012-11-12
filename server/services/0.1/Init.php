<?php
/**
* Description :
* Classe d'initialisation.
*
* @package depim
* @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
* @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
* @version 0.1
* @copyright 1999-2011 Jean-Pascal Milcent (jpm@clapas.org)
*/
class Init extends RestService {

	/** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = true;


	public function consulter($ressources, $parametres) {
		$resultat = '';
		$reponseHttp = new ReponseHttp();
		try {
			$resultat = $this->chargerBdd();
			$reponseHttp->setResultatService($resultat);
		} catch (Exception $e) {
			$reponseHttp->ajouterErreur($e);
		}
		$reponseHttp->emettreLesEntetes();
		$corps = $reponseHttp->getCorps();
		return $corps;
		
	}
	
	private function chargerBdd() {
		$requete = "DROP TABLE personne";
		$this->getBdd()->requeter($requete);
		
		$requete = "CREATE TABLE IF NOT EXISTS personne (
			id INTEGER PRIMARY KEY, 
			nom TEXT, 
			prenom TEXT)";
		$this->getBdd()->requeter($requete);
		
		$this->chargerJeuxTest();
		
		$requete = "SELECT * FROM personne ORDER BY nom";
		$tables = $this->getBdd()->recupererTous($requete);
		return $tables;
	}
	
	private function chargerJeuxTest() {
		$jeuxTest = array([1, 'MARTIN', 'Pierre'],
				[2, 'Paul', 'DUPONT'],
				[3, 'Amélie', 'DUBOIS']);
		
		foreach ($jeuxTest as $personne) {
			list($id, $prenom, $nom) = $this->getBdd()->protegerTableau($personne);
			$requete = "INSERT INTO personne (id, nom, prenom) VALUES ($id, $nom, $prenom)";
			$this->getBdd()->requeter($requete);
		}
	}
}
?>