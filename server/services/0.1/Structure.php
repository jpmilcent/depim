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
			if ($this->demanderUneStructure($ressources)) {
				$resultat = $this->getStructure($ressources[0]);
			} else {
				$resultat = $this->getStructures();
			}
			$reponseHttp->setResultatService($resultat);
		} catch (Exception $e) {
			$reponseHttp->ajouterErreur($e);
		}
		$reponseHttp->emettreLesEntetes();
		$corps = $reponseHttp->getCorps();
		return $corps;
	}
	
	private function demanderUneStructure($ressources) {
		return (isset($ressources[0]) && count($ressources) == 1 && preg_match('/^[0-9]$/', $ressources[0])) ? true : false;
	}
	
	private function getStructure($id) {
		$idStructure = $this->getBdd()->proteger($id);
		
		$requete = "SELECT s.id_structure, s.meta_version, s.meta_date, c.date, u.id_utilisateur, u.fmt_nom_complet, st.cle, st.valeur 
			FROM structure AS s 
				LEFT JOIN meta_changement AS c ON (s.ce_meta = c.id_changement) 
				LEFT JOIN meta_utilisateur AS u ON (c.ce_utilisateur = u.id_utilisateur) 
				LEFT JOIN structure_tags AS st ON (s.id_structure = st.id) 
			WHERE meta_visible = 1 
				AND s.id_structure = $idStructure";
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
				
				$infos['meta'] = $meta;
			}
			$infos['tags'][$table['cle']] = $table['valeur'];
		}
		return $infos;
	}
	
	private function getStructures() {
		$requete = 'SELECT s.id_structure, s.meta_version, s.meta_date, c.date, u.id_utilisateur, u.fmt_nom_complet, st.cle, st.valeur 
			FROM structure AS s 
				LEFT JOIN meta_changement AS c ON (s.ce_meta = c.id_changement) 
				LEFT JOIN meta_utilisateur AS u ON (c.ce_utilisateur = u.id_utilisateur) 
				LEFT JOIN structure_tags AS st ON (s.id_structure = st.id) 
			WHERE meta_visible = 1 
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
	
	public function modifier($ressources, $requeteDonnees) {
		$idStructure = $this->getBdd()->proteger($ressources[0]);
		
		$this->historiserTags($idStructure);
		
		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);
		
		$requete = 'UPDATE structure '.
			"SET ce_meta = $idChgment, ".
			'	meta_version = meta_version + 1, '.
			"	meta_date = datetime('now'), ".
			"	meta_visible = 1 ".
			"WHERE id_structure = $idStructure ";
		$this->getBdd()->requeter($requete);
		
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
	
	public function supprimer($ressources, $requeteDonnees) {
		$idStructure = $this->getBdd()->proteger($ressources[0]);
		$this->historiserTags($idStructure);
		
		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);
		
		$requete = 'UPDATE structure '.
			'SET meta_visible = 0, '.
			'	meta_version = meta_version + 1, '.
			"	meta_date = datetime('now'), ".
			"	ce_meta = $idChgment ".
			"WHERE id_structure = $idStructure ";
		$ok = $this->getBdd()->executer($requete);
		
		return ($ok !== false) ? true : false;
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
	
	private function historiserTags($idStructure) {
		$ok = $this->historiserStructure($idStructure);

		if ($ok !== false) {
			$requete = 'INSERT INTO '.
				'structure_tags_historique  '.
				'SELECT st.*, s.meta_version '.
				'FROM structure_tags AS st LEFT JOIN structure AS s ON (id = id_structure) '.
				"WHERE id = $idStructure ";
			$ok = $this->getBdd()->executer($requete);
		}
		
		if ($ok !== false) {
			$requete = 'DELETE FROM structure_tags '.
				"WHERE id = $idStructure ";
			$ok = $this->getBdd()->executer($requete);
		}
		
		return ($ok !== false) ? true : false;
	}
	
	private function historiserStructure($idStructure) {
		$requete = 'INSERT INTO structure_historique '.
			"SELECT * FROM structure WHERE id_structure = $idStructure ";
		$ok = $this->getBdd()->executer($requete);
		
		return ($ok !== false) ? true : false;
	}
}
?>
