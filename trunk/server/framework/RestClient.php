<?php
// declare(encoding='UTF-8');
/**
* Classe client permettant d'interroger des services web REST.
*
* @category	php 5.2
* @package 	Framework
* @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
* @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
* @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
* @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
* @version		$Id: RestClient.php 353 2011-08-01 16:02:32Z jpm $
*/
class RestClient {
	const HTTP_URL_REQUETE_SEPARATEUR = '&';
	const HTTP_URL_REQUETE_CLE_VALEUR_SEPARATEUR = '=';
	private $http_methodes = array('GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'CONNECT', 'TRACE');
	protected $parametres = null;
	private $url = null;
	private $reponse_entetes = null;
	
	//+----------------------------------------------------------------------------------------------------------------+
	// ACCESSEURS
	
	public function getReponseEntetes() {
		return $this->reponse_entetes;
	}
	
	public function getParametre($cle) {
		$valeur = (isset($this->parametres[$cle])) ? $this->parametres[$cle] : null;
		return $valeur;
	}
	
	public function ajouterParametre($cle, $valeur) {
		$this->parametres[$cle] = $valeur;
	}
	
	public function supprimerParametre($cle) {
		unset($this->parametres[$cle]);
	}
	
	public function nettoyerParametres() {
		$this->parametres = null;
	}
	
	//+----------------------------------------------------------------------------------------------------------------+
	// MÉTHODES
	
	public function consulter($url) {
		$retour = $this->envoyerRequete($url, 'GET');
		return $retour;
	}
	
	public function ajouter($url, Array $donnees) {
		$retour = $this->envoyerRequete($url, 'PUT', $donnees);
		return $retour;
	}
	
	public function modifier($url, Array $donnees) {
		$retour = $this->envoyerRequete($url, 'POST', $donnees);
		return $retour;
	}
	
	public function supprimer($url) {
		$retour = $this->envoyerRequete($url, 'DELETE');
		return $retour;
	}
	
	public function envoyerRequete($url, $mode, Array $donnees = array()) {
		$this->url = $url;
		$contenu = false;
		if (! in_array($mode, $this->http_methodes)) {
			$e = "Le mode de requête '$mode' n'est pas accepté!";
			trigger_error($e, E_USER_WARNING);
		} else {
			if ($mode == 'GET') {
				$this->traiterUrlParametres();
			}
			$contexte = stream_context_create(array(
				'http' => array(
      				'method' => $mode,
					'header' => "Content-type: application/x-www-form-urlencoded\r\n",
      				'content' => http_build_query($donnees, null, self::HTTP_URL_REQUETE_SEPARATEUR))));
			$flux = @fopen($this->url, 'r', false, $contexte);
			if (!$flux) {
				$this->reponse_entetes = $http_response_header;
				$e = "L'ouverture de l'url '{$this->url}' par la méthode HTTP '$mode' a échoué!";
				trigger_error($e, E_USER_WARNING);
			} else {
				// Informations sur les en-têtes et métadonnées du flux
				$this->reponse_entetes = stream_get_meta_data($flux);
				
				// Contenu actuel de $url
				$contenu = stream_get_contents($flux);
				
				fclose($flux);
			}
			$this->traiterEntete();
		}
		$this->reinitialiser();
		return $contenu;
	}
	
	private function traiterUrlParametres() {
		$parametres = array();
		if (count($this->parametres) > 0) {
			foreach ($this->parametres as $cle => $valeur) {
				$cle = rawurlencode($cle);
				$valeur = rawurlencode($valeur);
				$parametres[] = $cle.self::HTTP_URL_REQUETE_CLE_VALEUR_SEPARATEUR.$valeur;
			}
			$url_parametres = implode(self::HTTP_URL_REQUETE_SEPARATEUR, $parametres);
			$this->url = $this->url.'?'.$url_parametres;
		}
	}
	
	private function traiterEntete() {
		$infos = $this->analyserEntete();
		$this->traiterEnteteDebogage($infos);
	}
	
	private function analyserEntete() {
		$entetes = $this->reponse_entetes; 
		$infos = array('date' => null, 'uri' => $this->url, 'debogages' => null);
		
		if (isset($entetes['wrapper_data'])) {
			$entetes = $entetes['wrapper_data'];
		}
		foreach ($entetes as $entete) {
			if (preg_match('/^X_REST_DEBOGAGE_MESSAGES: (.+)$/', $entete, $match)) {
				$infos['debogages'] = json_decode($match[1]);
			}
			if (preg_match('/^Date: .+ ([012][0-9]:[012345][0-9]:[012345][0-9]) .*$/', $entete, $match)) {
				$infos['date'] = $match[1];
			}
		}
		return $infos;
	}
	
	private function traiterEnteteDebogage($entetes_analyses) {
		if (isset($entetes['debogages'])) {
			$date = $entetes['date'];
			$uri = $entetes['uri'];
			$debogages = $entetes['debogages'];
			foreach ($debogages as $debogage) {
				$e = "DEBOGAGE : $date - $uri :\n$debogage";
				trigger_error($e, E_USER_NOTICE);
			}
		}
	}
	
	private function reinitialiser() {
		$this->nettoyerParametres();
	}
}