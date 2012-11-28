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
		$requete = 'SELECT s.id_structure, s.meta_version, s.meta_date, c.date, u.id_utilisateur, u.fmt_nom_complet, st.cle, st.valeur 
			FROM structure AS s 
			LEFT JOIN meta_changement AS c ON (s.ce_meta = c.id_changement) 
			LEFT JOIN meta_utilisateur AS u ON (c.ce_utilisateur = u.id_utilisateur) 
			LEFT JOIN structure_tags AS st ON (s.id_structure = st.id)  
			ORDER BY meta_date DESC';
		$tables = $this->getBdd()->recupererTous($requete);
		
		$infos = array();
		foreach ($tables as $table) {
			if (isset($infos[$table['id_structure']]['meta']) == false) {
				$meta = array();
				$meta['id'] = $table['id_structure'];
				$meta['version'] = $table['meta_version'];
				$meta['date'] = $table['meta_date'];
				$meta['utilisateur:id'] = $table['id_utilisateur'];
				$meta['utilisateur:nom_complet'] = $table['fmt_nom_complet'];
				
				$infos[$table['id_structure']]['meta'] = $meta;
			}
			$infos[$table['id_structure']]['tags'][$table['cle']] = $table['valeur'];
		}
		return $infos;
	}
	
	public function ajouter($ressources, $requeteDonnees) {
		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);
		
		$requete = 'INSERT INTO structure (ce_meta, meta_version, meta_date) '.
			"VALUES ($idChgment, 1, datetime('now'))";
		$this->getBdd()->requeter($requete);
		
		$requete = 'SELECT last_insert_rowid() AS id';
		$resultat = $this->getBdd()->recuperer($requete);
		$idStructure = $resultat['id'];
		
		$tags = $this->getBdd()->protegerCleValeur($requeteDonnees['tags']);
		$this->getBdd()->debuterTransaction();
		foreach ($tags as $cle => $valeur) {
			$cle = trim($cle);
			$valeur = trim($valeur);
			$requete = 'INSERT INTO structure_tags (id, cle, valeur) '.
				"VALUES ($idStructure, $cle, $valeur)";
			$this->getBdd()->executer($requete);
		}
		$this->getBdd()->validerTransaction();
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
	
		$meta['tags']['ip'] = $_SERVER['REMOTE_ADDR'];
		$changementTags = $this->getBdd()->protegerCleValeur($meta['tags']);
		$this->getBdd()->debuterTransaction();
		foreach ($changementTags as $cle => $valeur) {
			$requete = 'INSERT INTO meta_changement_tags (id, cle, valeur) '.
				"VALUES ($id, $cle, $valeur)";
			$this->getBdd()->executer($requete);
		}
		$this->getBdd()->validerTransaction();
	
		return $id;
	}
}
?>
