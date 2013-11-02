<?php
// declare(encoding='UTF-8');
/**
 * Classe principale gérant les services web de type (@link(REST, http://fr.wikipedia.org/wiki/Rest).
 *
 * Elle contient  :
 *  - les constantes indiquant les différentes (@link(méthode HTTP, http://fr.wikipedia.org/wiki/Http) prises en compte.
 *  - les @link(codes HTTP des réponses, http://fr.wikipedia.org/wiki/Liste_des_codes_HTTP)
 *
 * Ce serveur REST accepte 4 types de méthodes HTTP : GET, PUT, POST, DELETE.
 * GET et POST ne pose généralement pas de problème pour les clients HTTP mais ce n'est pas forcément le cas pour PUT et DELETE.
 * Vous pouvez donc pour réaliser :
 *  - DELETE : utiliser la méthode POST avec action=DELETE dans le corps de la requête.
 *  - PUT : utiliser la méthode POST avec une url ne contenant aucune indication de ressource.
 * Une autre solution consiste à utiliser n'importe quelle méthode et à ajouter l'entête "X_HTTP_METHOD_OVERRIDE" avec pour
 * valeur le nom de la méthode que vous souhaitez utiliser. Exemple d'entête : "X_HTTP_METHOD_OVERRIDE: PUT".
 * Exemple : <code>curl -v -v -H "X_HTTP_METHOD_OVERRIDE: DELETE" "http://www.mondomaine.org/services/apiVersion/[mon-service]/"</code>
 * Cela fonctionne avec Apache.
 *
 * Les classes des services web doivent avoir un nom au format ChatMot "MonService" et être appelée dans l'url par le même nom
 * en minuscule où les mots sont séparés par des tirets "mon-service".
 *
 * Paramètres liés dans config.ini :
 *  - serveur.baseURL : morceau de l'url pour appeler le serveur relative au domaine. Exemple : pour http://www.tela-botanica.org/mon_serveur/
 *  	mettre : "/mon_serveur/"
 *  - serveur.baseAlternativeURL : sur le même principe que ci-dessus permet d'affecter une deuxième url (pour gérer des raccourci via htaccess)
 *
 * Encodage en entrée : utf8
 * Encodage en sortie : utf8
 *
 * @category	Php 5.2
 * @package		Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license		GPL v3 <http://www.gnu.org/licenses/gpl.txt>
 * @license		CECILL v2 <http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt>
 * @since 		0.3
 * @version		$Id: RestServeur.php 416 2012-05-15 09:23:45Z jpm $
 * @link		/doc/framework/
 */
// TODO : gerer les retours : dans ce controleur : code retour et envoi ...
class RestServeur {

	/** Nom de la méthode appelée dans un service pour éxécuter une requête de type GET. */
	const METHODE_GET = 'consulter';

	/** Nom de la méthode appelée dans un service pour éxécuter une requête de type POST. */
	const METHODE_POST = 'modifier';

	/** Nom de la méthode appelée dans un service pour éxécuter une requête de type DELETE. */
	const METHODE_DELETE = 'supprimer';

	/** Nom de la méthode appelée dans un service pour éxécuter une requête de type PUT. */
	const METHODE_PUT = 'ajouter';

	/** Code HTTP 200 indiquant le succès de l'accès à un service web par la méthode GET.
	 * L'utiliser lors d'une requète de type GET (consulter) pour indiquer le succès de l'opération.
	 * Sera renvoyée par défaut par PHP. */
	const HTTP_CODE_OK = '200';

	/** Code HTTP 201 indiquant que l'accès à un service web est un succès et que la ressource a été créée ou modifié.
	 * L'utiliser lors d'une requète de type PUT (ajouter) ou POST (modifier) pour indiquer le succès de l'opération. */
	const HTTP_CODE_CREATION_OK = '201';

	/** Code HTTP 204 indique que l'accès à un service web est un succès et qu'il n'y a pas de contenu à renvoyer.
	 * L'utiliser lors d'une requète de type DELETE (supprimer) pour indiquer le succès de l'opération. */
	const HTTP_CODE_SUPPRESSION_OK = '204';

	/** Code HTTP 400 indique que les paramètres envoyés au service contiennent des erreurs.
	 * L'utiliser pour indiquer l'échec de l'accès au service. La réponse pourra contenir un message expliquant la source
	 * de l'erreur. */
	const HTTP_CODE_MAUVAISE_REQUETE = '400';

	/** Code HTTP 401 indiquant que l'accès à un service web est refusé car l'authentification (obligatoire) a échoué pour
	 * accéder à la ressource. */
	const HTTP_CODE_ACCES_NON_AUTORISE = '401';

	/** Code HTTP 404 indiquant que la ressource indiquée par l'url est introuvable. */
	const HTTP_CODE_RESSOURCE_INTROUVABLE = '404';

	/** Code HTTP 405 indiquant soit :
	 *  - que le service web ne possède pas d'accès la ressource correspondant à la méthode HTTP employée.
	 *  - que la méthode HTTP enployée n'est pas en accord avec la ressource indiquée par l'url. */
	const HTTP_CODE_METHODE_NON_AUTORISE = '405';

	/** Code d'erreur HTTP 409 indiquant qu'un conflit est survenu vis à vis de la ressource.
	 * Par exemple, essayer de créer deux fois la même ressource ou bien tenter de modifier une ressource qui a été modifiée par
	 * ailleurs. */
	const HTTP_CODE_CONFLIT = '409';

	/** Code HTTP 411 indiquant que des paramètres passés dans le contenu de la requête sont nécessaires au service. */
	const HTTP_CODE_CONTENU_REQUIS = '411';

	/** Code d'erreur HTTP 500 Internal Server Error.
	 * L'utiliser quand le serveur ou un service soulève une erreur ou une exception. */
	const HTTP_CODE_ERREUR = '500';

	/** Motif de l'epression régulière vérfiant la version de l'API. */
	const MOTIF_API_VERSION = '/^v[0-9]+$/';

	/** Motif de l'epression régulière vérfiant le nom du service. */
	const MOTIF_SERVICE_NOM = '/^[a-z0-9]+(?:[-][a-z0-9]+)*$/';

	/** Mettre à true pour activer l'affichage des messages d'erreurs et de débogage.
	 * @var boolean */
	private static $debogageActivation = false;

	/** Indiquer le mode de débogage à utiliser (@see Debug).
	 * @var string */
	private static $debogageMode = '';

	/** La méthode de la requête HTTP utilisée.
	 * @var string */
	private $methode = 'GET';

	/** Le contenu brut du corps de la requête HTTP (s'il y en a).
	 * @var array */
	private $requeteDonnees = null;

	/** Le contenu sous forme de tableau de paires clés-valeurs du corps de la requête HTTP (s'il y en a).
	 * @var array */
	private $requeteDonneesParsees = null;

	/** Version de l'API demandée.
	 * Ex. http://www.mondomaine.org/services/[apiVersion]/mon-service/
	 * @var mixed Généralement deux nombres séparés par un point. Ex. : 1.0
	 */
	private $apiVersion = null;

	/** Nom du service demandé.
	 * Ex. http://www.mondomaine.org/services/apiVersion/[mon-service]/
	 * @var string par défaut vaut null.
	 */
	private $service = null;

	/** Morceaux de l'url servant à préciser la ressource concerné pour le service demandé.
	 * Ex. http://www.mondomaine.org/services/apiVersion/mon-service/[maRessource/maSousResource...]
	 * @var array
	 */
	private $ressources = array();

	/** Partie de l'url situé après le '?' servant à paramétrer le service demandé.
	 * Les données proviennent de $_GET où les caractères suivant ont été transformé en '_' undescrore dans les clés :
	 * - chr(32) ( ) (space)
	 * - chr(46) (.) (dot)
	 * - chr(91) ([) (open square bracket)
	 * - chr(128) - chr(159) (various)
	 * En outre nous appliquons la méthode nettoyerGet() qui effectue d'autres remplacement dans les valeurs.
	 * Ex. http://www.mondomaine.org/services/apiVersion/mon-service?monParametre1=maValeur1&monParametre2=maValeur2
	 * @see parametresBruts
	 * @var array
	 */
	private $parametres = array();

	/** Partie de l'url situé après le '?' servant à paramétrer le service demandé.
	 * Les données proviennent de $_SERVER['QUERY_STRING'] et n'ont subies aucune transformation au niveau des clés.
	 * Cependant nous appliquons la méthode nettoyerGet() qui effectue d'autres remplacement dans les valeurs.
	 * Ex. http://www.mondomaine.org/services/apiVersion/mon-service?monParametre1=maValeur1&monParametre2=maValeur2
	 * @see parametres
	 * @var array
	 */
	private $parametresBruts = array();

	/** Tableau contenant les paramètres de configuration du serveur.
	 * @var array
	 */
	private static $config = array();

	/** Tableau contenant les messages d'erreur et/ou d'avertissement du Serveur.
	 * @var array
	 * */
	private static $messages = array();

	/** Codes HTTP. */
	private static $http10 = array(
		self::HTTP_CODE_OK => 'OK',
		self::HTTP_CODE_CREATION_OK => 'Created',
		self::HTTP_CODE_SUPPRESSION_OK => 'No Content',
		self::HTTP_CODE_MAUVAISE_REQUETE => 'Bad Request',
		self::HTTP_CODE_ACCES_NON_AUTORISE => 'Unauthorized',
		self::HTTP_CODE_RESSOURCE_INTROUVABLE => 'Not Found',
		self::HTTP_CODE_METHODE_NON_AUTORISE => 'Method Not Allowed',
		self::HTTP_CODE_CONFLIT => 'Conflict',
		self::HTTP_CODE_CONTENU_REQUIS => 'Length Required',
		self::HTTP_CODE_ERREUR => 'Internal Server Error'
	);

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private $parametres_obligatoires = array('debogage', 'debogage_mode', 'serveur.baseURL', 'chemin_services');

	/**
	 * Analyse les données envoyées au serveur, enregistre la méthode HTTP utilisée pour appeler le serveur et parse
	 * l'url appelée pour trouver le service demandé.
	 */
	public function __construct() {
		Config::verifierPresenceParametres($this->parametres_obligatoires);

		self::$debogageActivation = Config::get('debogage');
		self::$debogageMode = Config::get('debogage_mode');

		if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER['QUERY_STRING'])) {
			$this->initialiserMethode();
			$this->initialiserRequeteDonnees();

			$urlParts = $this->decouperUrlChemin();

			$this->initialiserApiVersion(array_shift($urlParts));
			$this->initialiserServiceNom(array_shift($urlParts));
			$this->initialiserRessource($urlParts);

			$this->initialiserParametres();
			// Enregistrement en première position des autoload de la méthode gérant les classes des services
			spl_autoload_register(array(get_class(), 'chargerClasse'));
		} else {
			self::envoyerEnteteStatutHttp(self::HTTP_CODE_ERREUR);
			$e = "La classe Serveur du TBFRamework nécessite, pour fonctionner, l'accès aux variables serveurs REQUEST_URI, REQUEST_METHOD et QUERY_STRING.";
			self::ajouterMessage($e);
		}
	}

	private function initialiserMethode() {
		if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && count(trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) > 0) {
			$this->methode = trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		} else {
			$this->methode = $_SERVER['REQUEST_METHOD'];
		}
	}

	private function initialiserRequeteDonnees() {
		if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
			$this->requeteDonnees = '';
			$httpContent = fopen('php://input', 'r');
			while ($data = fread($httpContent, 1024)) {
				$this->requeteDonnees .= $data;
			}
			fclose($httpContent);
		}
	}

	private function decouperUrlChemin() {
		if (isset($_SERVER['REDIRECT_URL']) && $_SERVER['REDIRECT_URL'] != '') {
			if (isset($_SERVER['REDIRECT_QUERY_STRING']) && !empty($_SERVER['REDIRECT_QUERY_STRING'])) {
				$url = $_SERVER['REDIRECT_URL'].'?'.$_SERVER['REDIRECT_QUERY_STRING'];
			} else {
				$url = $_SERVER['REDIRECT_URL'];
			}
		} else {
			$url = $_SERVER['REQUEST_URI'];
		}

		if (strlen($_SERVER['QUERY_STRING']) == 0) {
			$tailleURL = strlen($url);
		} else {
			$tailleURL = -(strlen($_SERVER['QUERY_STRING']) + 1);
		}

		$urlChaine = '';
		if (strpos($url, Config::get('serveur.baseURL')) !== false) {
			$urlChaine = substr($url, strlen(Config::get('serveur.baseURL')), $tailleURL);
		} else if (strpos($url, Config::get('serveur.baseAlternativeURL')) !== false) {
			$urlChaine = substr($url, strlen(Config::get('serveur.baseAlternativeURL')), $tailleURL);
		}
		return explode('/', $urlChaine);
	}

	private function initialiserApiVersion($apiVersion) {
		if ($this->verifierApiVersion($apiVersion)) {
			$this->apiVersion = $apiVersion;
			self::$config['chemins']['api'] = Config::get('chemin_services').$this->apiVersion.DS;
			self::$config['chemins']['api_bibliotheque'] = self::$config['chemins']['api'].Config::get('dossier_bibliotheque').DS;
		} else {
			self::envoyerEnteteStatutHttp(self::HTTP_CODE_MAUVAISE_REQUETE);
			$e = "Aucune version d'API n'a été spécifiée.\n".
				"La version doit respecter l'expression régulière suivante : ".self::MOTIF_API_VERSION.".\n".
			  	"L'url doit avoir la forme suivante : http://www.mondomaine.org/services/apiVersion/monService/";
			self::ajouterMessage($e);
			self::cloreAccesServeur();
		}
	}

	private function verifierApiVersion($apiVersion) {
		$apiOk = false;
		if (isset($apiVersion) && !empty($apiVersion) && preg_match(self::MOTIF_API_VERSION, $apiVersion)) {
			$apiOk = true;
		}
		return $apiOk;
	}

	private function initialiserServiceNom($serviceNom) {
		if ($this->verifierServiceNom($serviceNom)) {
			$this->service = $this->traiterNomService($serviceNom);
		} else {
			self::envoyerEnteteStatutHttp(self::HTTP_CODE_MAUVAISE_REQUETE);
			$e = "Aucune nom de service n'a été spécifié.\n".
				"La nom du service doit respecter l'expression régulière suivante : ".self::MOTIF_SERVICE_NOM.".\n".
			  	"L'url doit avoir la forme suivante : http://www.mondomaine.org/services/apiVersion/monService/";
			self::ajouterMessage($e);
			self::cloreAccesServeur();
		}
	}

	private function verifierServiceNom($serviceNom) {
		$serviceNomOk = false;
		if (isset($serviceNom) && !empty($serviceNom) && preg_match(self::MOTIF_SERVICE_NOM, $serviceNom)) {
			$serviceNomOk = true;
		}
		return $serviceNomOk;
	}

	private function traiterNomService($serviceNom) {
		return str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($serviceNom))));
	}

	private function initialiserRessource($urlParts) {
		if (is_array($urlParts) && count($urlParts) > 0) {
			foreach ($urlParts as $ressource) {
				// Ne pas utiliser empty() car valeur 0 acceptée
				if ($ressource != '') {
					$this->ressources[] = urldecode($ressource);
				}
			}
		}
	}

	private function initialiserParametres() {
		$this->parametres = $this->recupererParametresGet();
		$this->parametresBruts = $this->recupererParametresBruts();
	}

	private function recupererParametresGet() {
		$_GET = $this->nettoyerParametres($_GET);
		return $_GET;
	}

	private function nettoyerParametres(Array $parametres) {
		// Pas besoin d'utiliser urldecode car déjà fait par php pour les clés et valeur de $_GET
		if (isset($parametres) && count($parametres) > 0) {
			foreach ($parametres as $cle => $valeur) {
				// les quotes, guillements et points-virgules ont été retirés des caractères à vérifier car
				//ça n'a plus lieu d'être maintenant que l'on utilise protéger à peu près partout
				$verifier = array('NULL', "\\", "\x00", "\x1a");
				$parametres[$cle] = strip_tags(str_replace($verifier, '', $valeur));
			}
		}
		return $parametres;
	}

	private function recupererParametresBruts() {
		$parametres_bruts = array();
		if (!empty($_SERVER['QUERY_STRING'])) {
			$paires = explode('&', $_SERVER['QUERY_STRING']);
			foreach ($paires as $paire) {
				$nv = explode('=', $paire);
				$nom = urldecode($nv[0]);
				$valeur = urldecode($nv[1]);
				$parametres_bruts[$nom] = $valeur;
			}
			$parametres_bruts = $this->nettoyerParametres($parametres_bruts);
		}
		return $parametres_bruts;
	}

	/**
	* La méthode __autoload() charge dynamiquement les classes trouvées dans le code.
	* Cette fonction est appelée par php5 quand il trouve une instanciation de classe dans le code.
	*
	*@param string le nom de la classe appelée.
	*@return void le fichier contenant la classe doit être inclu par la fonction.
	*/
	public static function chargerClasse($classe) {
		if (class_exists($classe)) {
			return null;
		}
		$chemins = array('', self::$config['chemins']['api'], self::$config['chemins']['api_bibliotheque']);
		foreach ($chemins as $chemin) {
			$chemin = $chemin.$classe.'.php';
			if (file_exists($chemin)) {
				require_once $chemin;
			}
		}
	}

	/**
	 * Execute la requête.
	 */
	public function executer() {
		$retour = '';
		switch ($this->methode) {
			case 'GET':
				$retour = $this->get();
				break;
			case 'POST':
				$retour = $this->post();// Retour pour l'alternative PUT
				break;
			case 'DELETE':
				$this->delete();
				break;
			case 'PUT':
				$retour = $this->put();
				break;
			case 'OPTIONS':
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Headers:origin, content-type');
				header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
				header('Access-Control-Allow-Content-Type: application/json');
				header('Access-Control-Max-Age: 3628800');
				header('Access-Control-Allow-Credentials: false');
				break;
			default :
				self::envoyerEnteteStatutHttp(self::HTTP_CODE_METHODE_NON_AUTORISE);
				header('Allow: GET, POST, DELETE, PUT');
				$e = "La méthode HTTP '{$this->methode}' n'est pas prise en compte par ce serveur REST.\n".
					"Consulter l'entête Allow pour connaître les méthodes autorisées.";
				self::ajouterMessage($e);
		}
		$this->cloreAccesServeur($retour);
	}

	/**
	 * Execute a GET request. A GET request fetches a list of resource when no resource name is given, a list of element
	 * when a resource name is given, or a resource element when a resource and resource unique identifier are given. It does not change the
	 * database contents.
	 */
	private function get() {
		$retour = '';
		if ($this->service != null) {
			$Service = new $this->service(self::$config);
			if (method_exists($Service, self::METHODE_GET)) {
				$methodeGet = self::METHODE_GET;
				$parametres = $Service->utiliserParametresBruts() ? $this->parametresBruts : $this->parametres;
				$retour = $Service->$methodeGet($this->ressources, $parametres);
			} else {
				self::envoyerEnteteStatutHttp(self::HTTP_CODE_RESSOURCE_INTROUVABLE);
				$e = "Le service '{$this->service}' ne contient pas la méthode '".self::METHODE_GET."' nécessaire ".
					"lors de l'appel du service via la méthode HTTP GET.";
				self::ajouterMessage($e);
			}
		}
		return $retour;
	}

	private function post() {
		$retour = '';
		$paires = $this->parserDonneesRequete();
		if (count($paires) != 0) {
			if (isset($paires['action']) && $paires['action'] == 'DELETE') {// Alternative à l'utilisation de DELETE
				$this->delete();
			} else if (count($this->ressources) == 0) {// Alternative à l'utilisation de PUT
				$retour = $this->put();
			} else {
				if ($this->service != null) {
					$Service = new $this->service(self::$config);
					if (method_exists($Service, self::METHODE_POST)) {
						$methodePost = self::METHODE_POST;
						$retour = $Service->$methodePost($this->ressources, $paires);
						if ($retour !== false) {
							$this->envoyerEnteteStatutHttp(self::HTTP_CODE_OK);
						}
					} else {
						self::envoyerEnteteStatutHttp(self::HTTP_CODE_RESSOURCE_INTROUVABLE);
						$e = "Le service '{$this->service}' ne contient pas la méthode '".self::METHODE_POST."' nécessaire ".
							"lors de l'appel du service via la méthode HTTP GET.";
						self::ajouterMessage($e);
					}
				}
			}
		} else {
			$this->envoyerEnteteStatutHttp(self::HTTP_CODE_CONTENU_REQUIS);
			$e = "Le service '{$this->service}' requiert de fournir le contenu à modifier dans le corps ".
				"de la requête avec la méthode HTTP POST.";
			self::ajouterMessage($e);
		}
		return $retour;
	}

	private function put() {
		$retour = '';
		$paires = $this->parserDonneesRequete();
		if (count($paires) != 0) {
			if ($this->service != null) {
				$Service = new $this->service(self::$config);
				if (method_exists($Service, self::METHODE_PUT)) {
					$methodePut = self::METHODE_PUT;
					$retour = $Service->$methodePut($this->ressources, $paires);
					if ($retour !== false) {
						$this->envoyerEnteteStatutHttp(self::HTTP_CODE_CREATION_OK);
					}
				} else {
					self::envoyerEnteteStatutHttp(self::HTTP_CODE_RESSOURCE_INTROUVABLE);
					$e = "Le service '{$this->service}' ne contient pas la méthode '".self::METHODE_PUT."' nécessaire ".
						"lors de l'appel du service via la méthode HTTP GET.";
					self::ajouterMessage($e);
				}
			}
		} else {
			$this->envoyerEnteteStatutHttp(self::HTTP_CODE_CONTENU_REQUIS);
			$e = "Il est nécessaire de fournir du contenu dans le corps de la requête pour créer une nouvelle ressource.";
			self::ajouterMessage($e);
		}
		return $retour;
	}

	private function delete() {
		if (count($this->ressources) != 0) {
			$paires = $this->parserDonneesRequete();
			if ($this->service != null) {
				$Service = new $this->service(self::$config);
				if (method_exists($Service, self::METHODE_DELETE)) {
					$methodeDelete = self::METHODE_DELETE;
					$info = $Service->$methodeDelete($this->ressources, $paires);
					if ($info === true) {
						$this->envoyerEnteteStatutHttp(self::HTTP_CODE_SUPPRESSION_OK);
					} else if ($info === false) {
						$this->envoyerEnteteStatutHttp(self::HTTP_CODE_RESSOURCE_INTROUVABLE);
						$e = "La ressource à supprimer est introuvable. Il se peut qu'elle ait été préalablement supprimé.";
						self::ajouterMessage($e);
					}
				} else {
					self::envoyerEnteteStatutHttp(self::HTTP_CODE_RESSOURCE_INTROUVABLE);
					$e = "Le service '{$this->service}' ne contient pas la méthode '".self::METHODE_DELETE."' nécessaire ".
						"lors de l'appel du service via la méthode HTTP GET.";
					self::ajouterMessage($e);
				}
			}
		} else {
			$this->envoyerEnteteStatutHttp(self::HTTP_CODE_MAUVAISE_REQUETE);
			$e = "Il est nécessaire d'indiquer dans l'url la ressource à supprimer.";
			self::ajouterMessage($e);
		}
	}

	/**
	 * Parse les données contenu dans le corps de la requête HTTP (= POST) en :
	 *  - décodant les clés et valeurs.
	 *  - supprimant les espaces en début et fin des clés et des valeurs.
	 *
	 * @return array Tableau de paires clé et valeur.
	 */
	private function parserDonneesRequete() {
		$donnees = array();
		if ($this->requeteDonneesParsees != null) {
			$donnees = $this->requeteDonneesParsees;
		} else if ($this->requeteDonnees != null) {
			if (preg_match('/application\/json/', $_SERVER['CONTENT_TYPE'])) {
				$donnees = json_decode($this->requeteDonnees, true);
			} else {
				$paires = explode('&', $this->requeteDonnees);
				foreach ($paires as $paire) {
					list($cle, $valeur) = explode('=', $paire);
					$cle = (isset($cle)) ? trim(urldecode($cle)) : '';
					$valeur = (isset($valeur)) ? trim(urldecode($valeur)) : '';
					$donnees[$cle] = $valeur;
				}
			}
			$this->requeteDonneesParsees = $donnees;
		}
		return $donnees;
	}

	/**
	 * Envoyer un entête HTTP (version 1.0) de statut.
	 * Il remplacera systématiquement tout entête HTTP de statut précédement envoyé.
	 * @param int $code entier indiquant le code du statut de l'entête HTTP à envoyer.
	 */
	public static function envoyerEnteteStatutHttp($code) {
		if (isset(self::$http10[$code])) {
			$txt = self::$http10[$code];
			header("HTTP/1.0 $code $txt", true);
		}
	}

	/**
	 * Termine l'accès au serveur après envoir envoyer les messages.
	 */
	private static function cloreAccesServeur($retour = '') {
		// 	Gestion des exceptions et erreurs générées par les services
		$retour .= self::gererErreurs();

		// Envoie des messages d'erreur et d'avertissement du serveur
		$retour .= self::envoyerMessages();

		header('Access-Control-Allow-Origin: *');
		//header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		//header('Access-Control-Allow-Content-Type: application/json');
		//header('Access-Control-Allow-Credentials: false');

		// Envoie sur la sortie standard le contenu de la réponse HTTP
		print $retour;

		// Nous terminons le script
		exit(0);
	}

	/**
	 * Si des exceptions ou des erreurs sont soulevées par le serveur ou les services, elles sont gérées par cette méthode.
	 * Si nous avec des erreurs d'un type différent d'E_USER_NOTICE (réservé au débogage), elle sont renvoyées sur la sortie
	 * standard (via echo).
	 * Si seulement des erreurs de type E_USER_NOTICE, sont présentes, elle sont envoyées en fonction du contenu du paramètre de
	 * config "debogage_mode" :
	 *  - Debug::MODE_ECHO : les messages sont affichés en utilisant echo au moment où ils sont déclenchés dans le code.
	 *  - Debug::MODE_NOTICE : les message sont stockés par le gestionnaire d'exception sous forme d'erreur de type
	 *  E_USER_NOTICE et sont renvoyés sur la sortie standard à la fin de l'execution du programme (via echo).
	 *  - Debug::MODE_ENTETE_HTTP : les message sont stockés par le gestionnaire d'exception sous forme d'erreur de type
	 *  E_USER_NOTICE et sont renvoyés dans un entête HTTP (X_REST_DEBOGAGE_MESSAGES) à la fin de l'execution du programme.
	 *  - Autre valeur : les messages sont formatés puis retournés par la fonction de débogage (à vous de les afficher).
	 */
	public static function gererErreurs() {
		$retour = '';
		if (self::$debogageActivation && GestionnaireException::getExceptionsNbre() > 0) {

			$exceptionsTriees = GestionnaireException::getExceptionsTriees();
			reset($exceptionsTriees);
			$debogageSeulement = true;
			if (array_key_exists(E_USER_ERROR, $exceptionsTriees)) {
				self::envoyerEnteteStatutHttp(self::HTTP_CODE_ERREUR);
				$debogageSeulement = false;
			}

			$exceptionsFormatees = array();
			foreach ($exceptionsTriees as $exceptions) {
				foreach ($exceptions as $e) {
					if ($debogageSeulement && self::$debogageMode == Debug::MODE_ENTETE_HTTP) {
						$exceptionsFormatees[] = GestionnaireException::formaterExceptionDebug($e);
					} else {
						$retour = GestionnaireException::formaterExceptionXhtml($e);
					}
				}
			}

			if ($debogageSeulement && self::$debogageMode == Debug::MODE_ENTETE_HTTP) {
				header('X_REST_DEBOGAGE_MESSAGES: '.json_encode($exceptionsFormatees));
			}
		}
		return $retour;
	}


	/**
	 * Permet d'ajouter un message d'erreur ou d'avertissement qui sera envoyé au client.
	 * Le message doit être au format texte et en UTF-8.
	 * @param string $message le message à envoyer.
	 */
	public static function ajouterMessage($message) {
		if (isset($message) && !empty($message)) {
			self::$messages[] = $message;
		}
	}

	/**
	 * Envoie au client les éventuels messages d'erreur et d'avertissement du Serveur.
	 * Le format d'envoie est text/plain encodé en UTF-8.
	 */
	private static function envoyerMessages() {
		if (count(self::$messages) > 0) {
			header("Content-Type: text/plain; charset=utf-8");
			return implode("\n", self::$messages);
		}
	}
}
?>
