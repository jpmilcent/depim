<?php
class ReponseHttp {

	private $resultatService = null;
	private $erreurs = array();

	public function __construct() {
		$this->resultatService = new ResultatService();
		if (function_exists('json_decode') == false){
			require_once (dirname(__FILE__).'/JSON.php');
			function json_decode($content, $assoc = false){
				if ($assoc) {
					$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
				} else {
					$json = new Services_JSON;
				}
				return $json->decode($content);
			}
		}

		if ( !function_exists('json_encode') ){
			function json_encode($content){
				$json = new Services_JSON;
				return $json->encode($content);
			}
		}
	}

	public function setResultatService($resultat) {
		if (!($resultat instanceof ResultatService)) {
			$this->resultatService->corps = $resultat;
		} else {
			$this->resultatService = $resultat;
		}
	}

	public function getCorps() {
		if ($this->etreEnErreur()) {
			$this->resultatService->corps = $this->erreurs[0]['message'];
		} else {
			$this->transformerReponseCorpsSuivantMime();
		}
		return $this->resultatService->corps;
	}

	public function ajouterErreur(Exception $e) {
		$this->erreurs[] = array('entete' => $e->getCode(), 'message' => $e->getMessage());
	}

	public function emettreLesEntetes() {
		$enteteHttp = new EnteteHttp();
		if ($this->etreEnErreur()) {
			$enteteHttp->code = $this->erreurs[0]['entete'];
			$enteteHttp->mime = 'text/html';
		} else {
			$enteteHttp->encodage = $this->resultatService->encodage;
			$enteteHttp->mime = $this->resultatService->mime;
		}
		header("Content-Type: $enteteHttp->mime; charset=$enteteHttp->encodage");
		RestServeur::envoyerEnteteStatutHttp($enteteHttp->code);
	}

	private function etreEnErreur() {
		$enErreur = false;
		if (count($this->erreurs) > 0) {
			$enErreur = true;
		}
		return $enErreur;
	}

	private function transformerReponseCorpsSuivantMime() {
		switch ($this->resultatService->mime) {
			case 'application/json' :
				if (isset($_GET['callback'])) {
					$contenu = $_GET['callback'].'('.json_encode($this->resultatService->corps).');';
				} else {
					$contenu = json_encode($this->resultatService->corps);
				}
				$this->resultatService->corps = $contenu;
				break;
		}
	}

}
?>