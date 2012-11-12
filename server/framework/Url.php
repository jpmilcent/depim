<?php
// declare(encoding='UTF-8');
/**
 * Classe Url, gérant le découpage des paramètres, leurs modification etc...
 * Traduction et conversion d'une classe (NET_Url2) issue de Pear
 *
 * @category	Php 5.2
 * @package	Framework
 * @author		Christian SCHMIDT <schmidt@php.net> (Auteur classe originale)
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	SVN: $Id: Url.php 404 2012-02-22 15:44:24Z gduche $
 * @link		/doc/framework/
*/
class Url {

	/**
	 * Répresenter les tableaux dans les requêtes en utilisant la notation php []. Par défaut à true.
	 */
	const OPTION_UTILISER_CROCHETS = 'utiliser_crochets';

	/**
	 * URL-encoder les clés des variables dans les requêtes. Par défaut à true.
	 */
	const OPTION_ENCODER_CLES = 'encoder_cles';
	
	/**
	* URL-encoder les valeurs des variables dans les requêtes. Par défaut à false.
	*/
	const OPTION_ENCODER_VALEURS = 'encoder_valeurs';

	/**
	 * Séparateurs de variables lors du parsing de la requête. Chaque caractère
	 * est considéré comme un séparateur. Par défaut, spécifié par le paramêtre
	 * arg_separator.input dans php.ini (par défaut "&").
	 */
	const OPTION_SEPARATEUR_ENTREE = 'separateur_entree';

	/**
	 * Séparateur de variables lors de la génération de la requête. Par défaut, spécifié
	 * par le paramètre arg_separator.output dans php.ini (par défaut "&").
	 */
	const OPTION_SEPARATEUR_SORTIE = 'separateur_sortie';

	/**
	 * Options par défaut correspondant au comportement de php
	 * vis à vis de $_GET
	 */
	private $options = array(
		self::OPTION_UTILISER_CROCHETS => true,
		self::OPTION_ENCODER_CLES => true,
		self::OPTION_ENCODER_VALEURS => false,
		self::OPTION_SEPARATEUR_ENTREE => '&',
		self::OPTION_SEPARATEUR_SORTIE => '&');

	/**
	 * @var  string|bool
	 */
	private $schema = false;

	/**
	 * @var  string|bool
	 */
	private $infoUtilisateur = false;

	/**
	 * @var  string|bool
	 */
	private $hote = false;

	/**
	 * @var  int|bool
	 */
	private $port = false;

	/**
	 * @var  string
	 */
	private $chemin = '';

	/**
	 * @var  string|bool
	 */
	private $requete = false;

	/**
	 * @var  string|bool
	 */
	private $fragment = false;

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private $parametres_obligatoires = array('url_arg_separateur_entree', 'url_arg_separateur_sortie');
	
	/**
	 * @param string $url	 une URL relative ou absolue
	 * @param array  $options
	 */
	public function __construct($url, $options = null) {
		Config::verifierPresenceParametres($this->parametres_obligatoires);
		
		$this->setOption(self::OPTION_SEPARATEUR_ENTREE, Config::get('url_arg_separateur_entree'));
		$this->setOption(self::OPTION_SEPARATEUR_SORTIE, Config::get('url_arg_separateur_sortie'));
		if (is_array($options)) {
			foreach ($options as $nomOption => $valeur) {
				$this->setOption($nomOption);
			}
		}

		if (preg_match('@^([a-z][a-z0-9.+-]*):@i', $url, $reg)) {
			$this->schema = $reg[1];
			$url = substr($url, strlen($reg[0]));
		}

		if (preg_match('@^//([^/#?]+)@', $url, $reg)) {
			$this->setAutorite($reg[1]);
			$url = substr($url, strlen($reg[0]));
		}

		$i = strcspn($url, '?#');
		$this->chemin = substr($url, 0, $i);
		$url = substr($url, $i);

		if (preg_match('@^\?([^#]*)@', $url, $reg)) {
			$this->requete = $reg[1];
			$url = substr($url, strlen($reg[0]));
		}

		if ($url) {
			$this->fragment = substr($url, 1);
		}
	}
	
	/**
	 * Renvoie la valeur de l'option specifiée.
	 *
	 * @param string $nomOption Nom de l'option demandée
	 *
	 * @return  mixed
	 */
	public function getOption($nomOption) {
		return isset($this->options[$nomOption]) ? $this->options[$nomOption] : false;
	}

	/**
	 * Met à jour la valeur de l'option spécifiée.
	 *
	 * @param string $nomOption une des constantes commençant par self::OPTION_
	 * @param mixed  $valeur	  valeur de l'option
	 *
	 * @return void
	 * @see  self::OPTION_STRICTE
	 * @see  self::OPTION_UTILISER_CROCHETS
	 * @see  self::OPTION_ENCODER_CLES
	 */
	public function setOption($nomOption, $valeur) {
		if (!array_key_exists($nomOption, $this->options)) {
			return false;
		}
		$this->options[$nomOption] = $valeur;
	}

	/**
	 * Renvoie la partie autorité, i.e. [ infoUtilisateur "@" ] hote [ ":" port ], ou
	 * false si celle-ci est absente.
	 *
	 * @return string|bool
	 */
	private function getAutorite() {
		if (!$this->hote) {
			return false;
		}

		$autorite = '';

		if ($this->infoUtilisateur !== false) {
			$autorite .= $this->infoUtilisateur . '@';
		}

		$autorite .= $this->hote;

		if ($this->port !== false) {
			$autorite .= ':' . $this->port;
		}

		return $autorite;
	}

	/**
	 * @param string|false $autorite
	 *
	 * @return void
	 */
	private function setAutorite($autorite) {
		$this->user = false;
		$this->pass = false;
		$this->hote = false;
		$this->port = false;
		if (preg_match('@^(([^\@]+)\@)?([^:]+)(:(\d*))?$@', $autorite, $reg)) {
			if ($reg[1]) {
				$this->infoUtilisateur = $reg[2];
			}

			$this->hote = $reg[3];
			if (isset($reg[5])) {
				$this->port = intval($reg[5]);
			}
		}
	}

	/**
	 * Renvoie vrai ou faux suivant que l'instance en cours représente une URL relative ou absolue.
	 *
	 * @return  bool
	 */
	private function etreAbsolue() {
		return (bool) $this->schema;
	}
	
	/**
	 * La suppression des segments à points est décrite dans la RFC 3986, section 5.2.4, e.g.
	 * "/foo/../bar/baz" => "/bar/baz"
	 *
	 * @param string $chemin un chemin
	 *
	 * @return string un chemin
	 */
	private static function supprimerSegmentsAPoints($chemin) {
		$sortie = '';

		// Assurons nous de ne pas nous retrouver piégés dans une boucle infinie due à un bug de cette méthode
		$j = 0;
		while ($chemin && $j++ < 100) {
			if (substr($chemin, 0, 2) == './') {// Étape A
				$chemin = substr($chemin, 2);
			} else if (substr($chemin, 0, 3) == '../') {
				$chemin = substr($chemin, 3);
			} else if (substr($chemin, 0, 3) == '/./' || $chemin == '/.') {// Étape B
				$chemin = '/' . substr($chemin, 3);
			} else if (substr($chemin, 0, 4) == '/../' || $chemin == '/..') {// Étape C
				$chemin = '/' . substr($chemin, 4);
				$i = strrpos($sortie, '/');
				$sortie = $i === false ? '' : substr($sortie, 0, $i);
			} else if ($chemin == '.' || $chemin == '..') {// Étape D
				$chemin = '';
			} else {// Étape E
				$i = strpos($chemin, '/');
				if ($i === 0) {
					$i = strpos($chemin, '/', 1);
				}
				if ($i === false) {
					$i = strlen($chemin);
				}
				$sortie .= substr($chemin, 0, $i);
				$chemin = substr($chemin, $i);
			}
		}

		return $sortie;
	}
	
	/**
	 * (Re-)Création de la partie requête de l'URL à partir des données du tableau (passé en paramètre).
	 * 
	 * @param array (nom => valeur) tableau de clés & valeurs pour la partie requête de l'url.
	 * @return void (Re-)Création de la partie requête.
	 */
	public function setRequete(Array $parametres) {
		if (!$parametres) {
			$this->requete = false;
		} else {
			foreach ($parametres as $nom => $valeur) {
				if ($this->getOption(self::OPTION_ENCODER_CLES)) {
					$nom = rawurlencode($nom);
				}
				
				if ($this->getOption(self::OPTION_ENCODER_VALEURS)) {
					$valeur = rawurlencode($valeur);
				}

				if (is_array($valeur)) {
					foreach ($valeur as $k => $v) {
						if ($this->getOption(self::OPTION_UTILISER_CROCHETS)) {
							$parties[] = sprintf('%s[%s]=%s', $nom, $k, $v);
						} else {
							$parties[] = $nom.'='.$v;
						}
					}
				} else if (!is_null($valeur)) {
					$parties[] = $nom . '=' . $valeur;
				} else {
					$parties[] = $nom;
				}
			}
			$this->requete = implode($this->getOption(self::OPTION_SEPARATEUR_SORTIE), $parties);
		}
	}
	
	/**
	 * (Re-)Création de la partie requête de l'URL à partir de la fusion du tableau (passé en paramètre) et 
	 * les valeurs présentes dans $_GET.
	 * 
	 * @param array (nom => valeur) tableau de clés & valeurs pour la partie requête de l'url.
	 * @return void (Re-)Création de la partie requête.
	 */
	public function fusionnerRequete(Array $parametres) {
		if ($parametres) {
			$requete = $parametres + $_GET;
			$this->setRequete($requete);
		}
	}

	/**
	 * Normalise les données de l'instance d'Url faisant appel à cette méthode.
	 *
	 * @return  void l'instance d'Url courrante est normalisée.
	 */
	public function normaliser() {
		// Voir RFC 3886, section 6

		// les cchémas sont insesibles à la casse
		if ($this->schema) {
			$this->schema = strtolower($this->schema);
		}

		// les noms d'hotes sont insensibles à la casse
		if ($this->hote) {
			$this->hote = strtolower($this->hote);
		}

		// Supprimer le numéro de port par défaut pour les schemas connus (RFC 3986, section 6.2.3)
		if ($this->port && $this->schema && $this->port == getservbyname($this->schema, 'tcp')) {
			$this->port = false;
		}

		// normalisation dans le cas d'un encodage avec %XX pourcentage (RFC 3986, section 6.2.2.1)
		foreach (array('infoUtilisateur', 'hote', 'chemin') as $partie) {
			if ($this->$partie) {
				$this->$partie  = preg_replace('/%[0-9a-f]{2}/ie', 'strtoupper("\0")', $this->$partie);
			}
		}

		// normalisation des segments du chemin (RFC 3986, section 6.2.2.3)
		$this->chemin = self::supprimerSegmentsAPoints($this->chemin);

		// normalisation basée sur le schéma (RFC 3986, section 6.2.3)
		if ($this->hote && !$this->chemin) {
			$this->chemin = '/';
		}
	}

	/**
	 * Renvoie une instance d'objet Url representant l'URL canonique du script PHP en cours d'éxécution.
	 *
	 * @return Url retourne un objet Url ou null en cas d'erreur.
	 */
	public static function getCanonique() {
		$url = null;
		if (!isset($_SERVER['REQUEST_METHOD'])) {
			trigger_error("Le script n'a pas été appellé à travers un serveur web", E_USER_WARNING);
		} else {
			// À partir d'une URL relative
			$url = new self($_SERVER['PHP_SELF']);
			$url->schema = isset($_SERVER['HTTPS']) ? 'https' : 'http';
			$url->hote = $_SERVER['SERVER_NAME'];
			$port = intval($_SERVER['SERVER_PORT']);
			if ($url->schema == 'http' && $port != 80 || $url->schema == 'https' && $port != 443) {
				$url->port = $port;
			}
		}
		return $url;
	}

	/**
	 * Renvoie une instance d'objet Url representant l'URL utilisée pour récupérer la requête en cours.
	 *
	 * @return Url retourne un objet Url ou null en cas d'erreur.
	 */
	public static function getDemande() {
		$url = null;
		if (!isset($_SERVER['REQUEST_METHOD'])) {
			trigger_error("Le script n'a pas été appellé à travers un serveur web", E_USER_WARNING);
		} else {
			// On part d'une URL relative
			$url = new self($_SERVER['REQUEST_URI']);
			$url->schema = isset($_SERVER['HTTPS']) ? 'https' : 'http';
			// On met à jour les valeurs de l'hôte et si possible du port
			$url->setAutorite($_SERVER['HTTP_hote']);
		}
		return $url;
	}

	
	/**
	 * Renvoie un représentation sous forme de chaine de l'URL.
	 *
	 * @return  string l'url
	 */
	public function getURL() {
		// Voir RFC 3986, section 5.3
		$url = '';
		
		if ($this->schema !== false) {
			$url .= $this->schema . ':';
		}

		$autorite = $this->getAutorite();
		if ($autorite !== false) {
			$url .= '//' . $autorite;
		}
		$url .= $this->chemin;

		if ($this->requete !== false) {
			$url .= '?' . $this->requete;
		}

		if ($this->fragment !== false) {
			$url .= '#' . $this->fragment;
		}

		return $url;
	}
}
?>