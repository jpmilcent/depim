<?php
/**
 * Classe principale gérant les services.
 * Paramètres liés dans config.ini :
 *  - serveur.baseURL
 * 
 * Encodage en entrée : utf8
 * Encodage en sortie : utf8
 * 
 * @category	Php 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	GPL v3 <http://www.gnu.org/licenses/gpl.txt>
 * @license	CECILL v2 <http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt>
 * @since 		0.3
 * @version	$Id: RestService.php 382 2011-10-14 10:36:41Z jpm $
 * @link		/doc/framework/
 */
abstract class RestService {
	
	/** Objet de connection à la base de données. 
	 * @deprecated Utiliser la methode getBdd().
	 * @see getBdd()
	 */
	protected $bdd = null;
	
	/** Objet Rest Client. */
	private $RestClient = null;
	
	/** Indique si oui (true) ou non (false), on veut utiliser les paramètres brutes. */
	protected $utilisationParametresBruts = false;
	
	public function __construct($config) {
		$this->config = $config;
		$this->bdd = new Bdd();
	}
	
	public function initialiser() {
		
	}
	
	/** 
	 * Méthode de connection à la base de données sur demande.
	 * Tous les services web n'ont pas besoin de s'y connecter.
	 */
	protected function getBdd() {
		if (! isset($this->bdd)) {
			$this->bdd = new Bdd();
		}
		return $this->bdd;
	}
	
	/** 
	 * Méthode permettant de faire appel à un client REST en fonction des besoins du service.
	 */
	protected function getRestClient() {
		if (! isset($this->RestClient)) {
			$this->RestClient = new RestClient();
		}
		return $this->RestClient;
	}
	
	/** 
	 * Méthode permettant de savoir si le service veut utiliser des paramètres bruts (issu de la QueryString)
	 * ou pas (issu de $_GET).
	 */
	public function utiliserParametresBruts() {
		return $this->utilisationParametresBruts;
	}
	
	/**
	 * Permet d'ajouter un message d'erreur ou d'avertissement qui sera envoyé au client.
	 * Le message doit être au format texte et en UTF-8.
	 * @param string $message le message à envoyer. 
	 */
	protected function ajouterMessage($message) {
		RestServeur::ajouterMessage($message);
	}
	
	/**
	 * Méthode appelée lors d'une requête HTTP de type GET.
	 * 
	 * Si la consultation est un succès, le code statut HTTP retourné sera 200. Dans tous les autres cas,
	 * la méthode devra retourner le code statut HTTP adéquate.
	 * 
	 * @param array Morceaux de l'url servant à préciser la ressource concerné pour le service demandé.
	 * @param array Partie de l'url situé après le '?' servant à paramétrer le service demandé.
	 * @return string une chaine indiquant le succès de l'opération et les données demandées.
	 */
	public function consulter($ressources, $parametres) {
		RestServeur::envoyerEnteteStatutHttp(RestServeur::HTTP_CODE_METHODE_NON_AUTORISE);
		RestServeur::ajouterMessage("Le service '".get_class($this)."' n'autorise pas la consultation.");
	}
	
	/**
	 * Méthode appelée lors d'une requête HTTP de type POST.
	 * 
	 * La ressource à modifier est indiquée via l'url. Les données devant servir à la mise à jours sont passées dans le corps
	 * de la requête.
	 * Si la modification est un succès, la méthode devra retourner "true" et le code statut HTTP retourné sera 201.
	 * 
	 * @param array Morceaux de l'url servant à préciser la ressource concerné pour le service demandé.
	 * @param array les données transférées dans le corps de la requête devant servir à la modification.
	 * @return mixed une chaine indiquant le succès de l'opération ou rien.
	 */
	public function modifier($ressources, $requeteDonnees) {
		RestServeur::envoyerEnteteStatutHttp(RestServeur::HTTP_CODE_METHODE_NON_AUTORISE);
		RestServeur::ajouterMessage("Le service '".get_class($this)."' n'autorise pas la modification.");
		return false;
	}
	
	/**
	 * Méthode appelée lors d'une requête HTTP de type PUT.
	 *
	 * L'identifiant de la ressource à ajouter est indiqué via l'url si on le connait par avance. Sinon, il doit être créé par 
	 * le service. Dans ce dernier cas, le nouvel identifiant devrait être renvoyé dans le corps de la réponse.
	 * Si l'ajout est un succès, la méthode devra retourner "true" ou l'identifiant.
	 * Le code statut HTTP retourné sera 201 en cas de succès.
	 * Dans le cas contraire, la méthode devra retourner false.
	 * 
	 * @param array Morceaux de l'url servant à préciser la ressource concerné pour le service demandé.
	 * @param array les données transférées dans le corps de la requête devant servir à l'ajout.
	 * @return string l'identifiant créé.
	 */
	public function ajouter($ressources, $requeteDonnees) {
		RestServeur::envoyerEnteteStatutHttp(RestServeur::HTTP_CODE_METHODE_NON_AUTORISE);
		RestServeur::ajouterMessage("Le service '".get_class($this)."' n'autorise pas la création.");
		return false;
	}
	
	/**
	 * Méthode appelée lors d'une requête HTTP de type DELETE (ou POST avec action=DELETE dans le corps de la requete).
	 * 
	 * Si la suppression est un succès, la méthode devra retourner "true" et le code statut HTTP retourné par
	 * RestServeur sera 204.
	 * Si la ressource à supprimer est introuvable, la méthode devra retourner "false" et le code statut HTTP
	 * retourné par RestServeur sera 404.
	 * Dans les autres cas de figure ou si vous souhaitez gérer vos propres codes de retour erreur, retourner
	 * la valeur null ou rien.
	 * 
	 * @param array Morceaux de l'url servant à préciser la ressource concerné pour le service demandé.
	 * @return mixed une chaine indiquant le succès de l'opération ou rien.
	 */
	public function supprimer($ressources) {
		RestServeur::envoyerEnteteStatutHttp(RestServeur::HTTP_CODE_METHODE_NON_AUTORISE);
		RestServeur::ajouterMessage("Le service '".get_class($this)."' n'autorise pas la suppression.");
		return null;
	}
}
?>