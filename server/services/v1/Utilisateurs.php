<?php
/**
* Utilisateur
*
* @package depim
* @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
* @license GPL v3 <http://www.gnu.org/licenses/gpl.txt>
* @version 0.1
* @copyright 1999-2011 Jean-Pascal Milcent (jpm@clapas.org)
*/
class Utilisateurs extends RestService {

	private $salt = 'depim2012';

	/** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = true;


	public function consulter($ressources, $parametres) {
		$resultat = '';
		$reponseHttp = new ReponseHttp();
		try {
			$resultat = $this->getUtilisateurs();
			$reponseHttp->setResultatService($resultat);
		} catch (Exception $e) {
			$reponseHttp->ajouterErreur($e);
		}
		$reponseHttp->emettreLesEntetes();
		$corps = $reponseHttp->getCorps();
		return $corps;
	}

	private function getUtilisateurs() {
		$requete = 'SELECT u.*, c.* FROM meta_utilisateur AS u '.
				'LEFT JOIN meta_changement As c ON (ce_meta = id_changement) '.
				'ORDER BY fmt_nom_complet ASC';
		$tables = $this->getBdd()->recupererTous($requete);
		return $tables;
	}

	public function ajouter($ressources, $requeteDonnees) {
		$meta = $requeteDonnees['meta'];
		$idChgment = $this->ajouterChangement($meta);

		$infos = $requeteDonnees['utilisateur'];
		$utilisateur = $this->getBdd()->protegerTableau($infos);
		$prenom = $utilisateur['prenom'];
		$nom = $utilisateur['nom'];
		$nomComplet = $this->getBdd()->proteger($infos['nom'].' '.$infos['prenom']);
		$pseudo = $utilisateur['pseudo'];
		$courriel = $utilisateur['courriel'];
		$motDePasse = $this->getBdd()->proteger(hash('sha256', $this->salt.$infos['mot_de_passe']));
		$licence = $infos['licence'] == true ? 1 : 0;
		$sessionId = $this->getBdd()->proteger(hash('md5', rand()));
		$requete = 'INSERT INTO meta_utilisateur ('.
			'fmt_nom_complet, prenom, nom, pseudo, courriel, mot_de_passe, mark_licence, '.
  			'session_id, ce_meta ) '.
			'VALUES ( '.
			"$nomComplet, $prenom, $nom, $pseudo, $courriel, $motDePasse, $licence, ".
			"$sessionId, $idChgment)";
		$this->getBdd()->requeter($requete);

		$requete = 'SELECT last_insert_rowid() AS id';
		$resultat = $this->getBdd()->recuperer($requete);
		$id = $resultat['id'];

		return $id;
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