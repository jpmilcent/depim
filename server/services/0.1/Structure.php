<?php
/**
* Structures
*
* @package depim
* @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
* @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
* @version 0.1
* @copyright 1999-2011 Jean-Pascal Milcent (jpm@clapas.org)
*/
class Structure extends RestService {

	/** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = true;


	public function consulter($ressources, $parametres) {
		$resultat = '';
		$reponseHttp = new ReponseHttp();
		try {
			$resultat = $this->getStructures();
			$reponseHttp->setResultatService($resultat);
		} catch (Exception $e) {
			$reponseHttp->ajouterErreur($e);
		}
		$reponseHttp->emettreLesEntetes();
		$corps = $reponseHttp->getCorps();
		return $corps;
	}
	
	private function getStructures() {
		$requete = "SELECT * FROM structure ORDER BY meta_date DESC";
		$tables = $this->getBdd()->recupererTous($requete);
		return $tables;
	}
	
	public function ajouter($ressources, $requeteDonnees) {
		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);
		
		$requete = 'INSERT INTO structure (ce_meta, meta_version, meta_date) '.
			"VALUES ($idChgment, 1, datetime('now'))";
		Debug::printr($requete);
		$this->getBdd()->requeter($requete);
		
		$requete = 'SELECT last_insert_rowid() AS id';
		$resultat = $this->getBdd()->recuperer($requete);
		$idStructure = $resultat['id'];
		
		$values = array();
		$tags = $this->getBdd()->protegerCleValeur($requeteDonnees['tags']);
		foreach ($tags as $cle => $valeur) {
			$cle = trim($cle);
			$valeur = trim($valeur);
			$values[] = "($idStructure, $cle, $valeur)";
		}
		$requete = 'INSERT INTO structure_tags (id, cle, valeur) '.
			'VALUES '.implode(',', $values);
		//$this->getBdd()->requeter($requete);
		
		return $idStructure;
	}
	
	private function ajouterChangement($meta) {
		$utilisateurId = $this->getBdd()->proteger($meta['utilisateurId']);
		$requete = 'INSERT INTO '.
			'meta_changement (date, ce_utilisateur) '.
			"VALUES (datetime('now'), $utilisateurId)";
		$resultat = $this->getBdd()->requeter($requete);
		
		$requete = "SELECT last_insert_rowid() AS id ";
		$resultat = $this->getBdd()->recuperer($requete);
		$id = $resultat['id'];
		
		$values = array();
		$changementTags = $this->getBdd()->protegerCleValeur($meta['tags']);
		foreach ($changementTags as $cle => $valeur) {
			$cle = trim($cle);
			$valeur = trim($valeur);
			$values[] = "($id, $cle, $valeur)";
		}
		$requete = 'INSERT INTO meta_changement_tags (id, cle, valeur) '.
			'VALUES '.implode(',', $values);
		$this->getBdd()->requeter($requete);
		
		return $id;
	}
}
?>