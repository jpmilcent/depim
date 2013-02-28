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
class Document extends RestService {

    /** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = true;

    public function consulter($ressources, $parametres) {
		$resultat = '';
		$reponseHttp = new ReponseHttp();
		try {
			if ($this->demanderUnDocument($ressources)) {
				$resultat = $this->getDocument($ressources[0]);
			} else {
				$resultat = $this->getDocuments();
			}
			$reponseHttp->setResultatService($resultat);
		} catch (Exception $e) {
			$reponseHttp->ajouterErreur($e);
		}
		$reponseHttp->emettreLesEntetes();
		$corps = $reponseHttp->getCorps();
		return $corps;
	}

	private function demanderUnDocument($ressources) {
		return (isset($ressources[0]) && count($ressources) == 1 && preg_match('/^[0-9]$/', $ressources[0])) ? true : false;
	}

	private function getDocument($id) {
		$idDocument = $this->getBdd()->proteger($id);

		$requete = "SELECT d.id_document, d.ce_structure, d.meta_version, d.meta_date, c.date, u.id_utilisateur, u.fmt_nom_complet,
                dt.cle, dt.valeur
			FROM document AS d
				LEFT JOIN meta_changement AS c ON (d.ce_meta = c.id_changement)
				LEFT JOIN meta_utilisateur AS u ON (c.ce_utilisateur = u.id_utilisateur)
				LEFT JOIN document_tags AS dt ON (d.id_document = dt.id)
			WHERE meta_visible = 1
				AND d.id_document = $idDocument";
		$tables = $this->getBdd()->recupererTous($requete);

		$infos = array();
		foreach ($tables as $table) {
			if (isset($infos[$table['id_document']]['meta']) == false) {
				$meta = array();
				$meta['id'] = $table['id_document'];
				$meta['version'] = $table['meta_version'];
				$meta['date'] = $table['meta_date'];
                $meta['structure:id'] = $table['ce_structure'];
				$meta['utilisateur:id'] = $table['id_utilisateur'];
				$meta['utilisateur:nom_complet'] = $table['fmt_nom_complet'];

				$infos['meta'] = $meta;
			}
			$infos['tags'][$table['cle']] = $table['valeur'];
		}
		return $infos;
	}

	private function getDocuments() {
		$requete = 'SELECT d.id_document, d.ce_structure, d.meta_version, d.meta_date, c.date, u.id_utilisateur, u.fmt_nom_complet,
                dt.cle, dt.valeur
			FROM document AS d
				LEFT JOIN meta_changement AS c ON (d.ce_meta = c.id_changement)
				LEFT JOIN meta_utilisateur AS u ON (c.ce_utilisateur = u.id_utilisateur)
				LEFT JOIN document_tags AS dt ON (d.id_document = dt.id)
			WHERE meta_visible = 1
			ORDER BY meta_date DESC';
		$tables = $this->getBdd()->recupererTous($requete);

		$infos = array();
		foreach ($tables as $table) {
			if (isset($infos[$table['id_document']]['meta']) == false) {
				$meta = array();
				$meta['id'] = $table['id_document'];
				$meta['version'] = $table['meta_version'];
				$meta['date'] = $table['meta_date'];
                $meta['structure:id'] = $table['ce_structure'];
				$meta['utilisateur:id'] = $table['id_utilisateur'];
				$meta['utilisateur:nom_complet'] = $table['fmt_nom_complet'];

				$infos[$table['id_document']]['meta'] = $meta;
			}
			$infos[$table['id_document']]['tags'][$table['cle']] = $table['valeur'];
		}
		return $infos;
	}

    public function ajouter($ressources, $requeteDonnees) {
		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);

        $data = $requeteDonnees['data'];
        $structureId = ($data['structureId'] != null) ? $this->getBdd()->proteger($data['structureId']) : 'NULL';
        $dateDebut = ($data['dateDebut'] != null) ? $this->getBdd()->proteger($data['dateDebut']) : 'NULL';
        $dateFin = ($data['dateFin'] != null) ? $this->getBdd()->proteger($data['dateFin']) : 'NULL';

        $requete = "INSERT INTO document (ce_structure, date_debut, date_fin, ce_meta, meta_version, meta_date)
			VALUES ($structureId, $dateDebut, $dateFin, $idChgment, 1, datetime('now'))";
		$this->getBdd()->requeter($requete);

		$requete = 'SELECT last_insert_rowid() AS id';
		$resultat = $this->getBdd()->recuperer($requete);
		$idDocument = $resultat['id'];

		$tags = $this->getBdd()->protegerCleValeur($requeteDonnees['tags']);
		$this->getBdd()->debuterTransaction();
		foreach ($tags as $cle => $valeur) {
			$cle = trim($cle);
			$valeur = trim($valeur);
			$requete = 'INSERT INTO document_tags (id, cle, valeur) '.
				"VALUES ($idDocument, $cle, $valeur)";
			$this->getBdd()->executer($requete);
		}
		$this->getBdd()->validerTransaction();
		return $idDocument;
	}

	public function modifier($ressources, $requeteDonnees) {
		$idDocument = $this->getBdd()->proteger($ressources[0]);

		$this->historiserTags($idDocument);

		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);

        $data = $requeteDonnees['data'];
        $structureId = ($data['structureId'] != null) ? $this->getBdd()->proteger($data['structureId']) : 'NULL';
        $dateDebut = ($data['dateDebut'] != null) ? $this->getBdd()->proteger($data['dateDebut']) : 'NULL';
        $dateFin = ($data['dateFin'] != null) ? $this->getBdd()->proteger($data['dateFin']) : 'NULL';

		$requete = 'UPDATE document '.
			"SET ce_structure = $structureId, ".
            "   date_debut = $dateDebut, ".
            "   date_fin = $dateFin, ".
            "   ce_meta = $idChgment, ".
			'	meta_version = meta_version + 1, '.
			"	meta_date = datetime('now'), ".
			"	meta_visible = 1 ".
			"WHERE id_document = $idDocument ";
		$this->getBdd()->requeter($requete);

		$tags = $this->getBdd()->protegerCleValeur($requeteDonnees['tags']);
		$this->getBdd()->debuterTransaction();
		foreach ($tags as $cle => $valeur) {
			$cle = trim($cle);
			$valeur = trim($valeur);
			$requete = 'INSERT INTO document_tags (id, cle, valeur) '.
					"VALUES ($idDocument, $cle, $valeur)";
			$this->getBdd()->executer($requete);
		}
		$this->getBdd()->validerTransaction();
		return $idDocument;
	}

	public function supprimer($ressources, $requeteDonnees) {
		$idDocument = $this->getBdd()->proteger($ressources[0]);
		$this->historiserTags($idDocument);

		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);

		$requete = 'UPDATE document '.
			'SET meta_visible = 0, '.
			'	meta_version = meta_version + 1, '.
			"	meta_date = datetime('now'), ".
			"	ce_meta = $idChgment ".
			"WHERE id_document = $idDocument ";
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

	private function historiserTags($idDocument) {
		$ok = $this->historiserDocument($idDocument);

		if ($ok !== false) {
			$requete = 'INSERT INTO '.
				'document_tags_historique  '.
				'SELECT dt.*, d.meta_version '.
				'FROM document_tags AS dt LEFT JOIN document AS d ON (id = id_document) '.
				"WHERE id = $idDocument ";
			$ok = $this->getBdd()->executer($requete);
		}

		if ($ok !== false) {
			$requete = 'DELETE FROM document_tags '.
				"WHERE id = $idDocument ";
			$ok = $this->getBdd()->executer($requete);
		}

		return ($ok !== false) ? true : false;
	}

	private function historiserDocument($idDocument) {
		$requete = 'INSERT INTO document_historique '.
			"SELECT * FROM document WHERE id_document = $idDocument ";
		$ok = $this->getBdd()->executer($requete);

		return ($ok !== false) ? true : false;
	}
}
?>