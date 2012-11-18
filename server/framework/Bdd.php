<?php
// declare(encoding='UTF-8');
/**
 * Classe Bdd, d'accés au base de données.
 * Elle fait office d'abstraction légère de base de données en utilisant diverses possibilités d'accès aux
 * bases de données (PDO, mysql, mysqli, SQLite3).
 * Les valeurs pour le paramètre 'bdd_abstraction' du fichier config.ini sont : pdo, mysql, mysqli, sqlite3
 * Vous pouvez aussi utiliser : "php:Bdd::ABSTRACTION_PDO","php:Bdd::ABSTRACTION_MYSQL", "php:Bdd::ABSTRACTION_MYSQLI",
 * "php:Bdd::ABSTRACTION_SQLITE3".
 * Elle peut être étendue, pour ajouter le support d'autres bases de données où prendre en compte des méthodes spécifique à
 * un type d'abstraction.
 *
 * @category	php 5.2
 * @package	Framework
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: Bdd.php 411 2012-03-13 13:29:59Z aurelien $
 * @link		/doc/framework/
 */
class Bdd {
	/** Constante stockant le squelette du message en cas d'erreur de requête sql. */
	const ERREUR_REQUETE_TPL = 'Requête echec.\nFichier : %s.\nLigne : %s.\nMessage : %s.\nRequête : %s';

	/** Constante stockant le squelette du message en cas d'erreur de connexion à la base de données. */
	const ERREUR_CONNEXION_TPL = 'Erreur de connexion à la base de données, vérifiez les paramètres du fichier de configuration.\nMessage : %S.';

	/** Constante stockant le squelette du message en cas d'erreur de sélection de la base de données. */
	const ERREUR_SELECTION_BDD_TPL = 'Erreur de sélection de la base de données, vérifiez les paramètres du fichier de configuration.\nMessage : %S.';

	/** Constante stockant le code pour l'abstraction de PDO. */
	const ABSTRACTION_PDO = 'pdo';

	/** Constante stockant le code pour l'abstraction de mysql. */
	const ABSTRACTION_MYSQL = 'mysql';

	/** Constante stockant le code pour l'abstraction de mysqli. */
	const ABSTRACTION_MYSQLI = 'mysqli';

	/** Constante stockant le code pour l'abstraction de SQLite3. */
	const ABSTRACTION_SQLITE3 = 'sqlite3';

	/** Constante stockant le code pour le mode tableau associatif des résultats des requêtes. */
	const MODE_ASSOC = 'ASSOC';

	/** Constante stockant le code pour le mode objet des résultats des requêtes. */
	const MODE_OBJET = 'OBJECT';

	/** Mode de fetch associatif */
	protected $ASSOC = '';

	/** Mode de fetch objet */
	protected $OBJECT = '';

	/** abstraction de base de données utilisée */
	protected $abstraction;

	/** DSN pour accéder à la base de données */
	protected $dsn;

	/** Type de base de données (mysql, mysqli, etc ...) */
	protected $type;

	/** Hote herbergeant la base de données */
	protected $hote;

	/** Nom de la base de données à laquelle le modèle doit se connecter */
	protected $bdd_nom;

	/** Nom d'utilisateur */
	protected $utilisateur;

	/** Mot de passe */
	protected $pass;

	/** Encodage de la base de données */
	protected $encodage = null;

	/** Connexion à la base de données */
	protected $connexion = null;

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	protected $parametres_obligatoires = array('bdd_abstraction', 'bdd_protocole', 'bdd_serveur', 'bdd_nom',
		'bdd_utilisateur', 'bdd_mot_de_passe', 'bdd_encodage');

	/** Constructeur par défaut, appelé à l'initialisation. */
	public function __construct() {
		Config::verifierPresenceParametres($this->parametres_obligatoires);
		$this->abstraction = strtolower(Config::get('bdd_abstraction'));
		$this->type = Config::get('bdd_protocole');
		$this->hote = Config::get('bdd_serveur');
		$this->bdd_nom = Config::get('bdd_nom');
		$this->utilisateur = Config::get('bdd_utilisateur');
		$this->pass = Config::get('bdd_mot_de_passe');
		$this->encodage = Config::get('bdd_encodage');

		if ($this->type == 'sqlite' || $this->type == 'sqlite2') {
			$this->dsn = $this->type.':'.$this->hote;
		} else {
			$this->dsn = $this->type.':dbname='.$this->bdd_nom.';host='.$this->hote;
		}
		$this->initialiserProtocole();
	}

	/** Initialise les constantes de classe à leur bonne valeur et déclenche une erreur si le protocole n'est pas bien défini. */
	protected function initialiserProtocole() {
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				$this->ASSOC = PDO::FETCH_ASSOC;
				$this->OBJECT = PDO::FETCH_CLASS;
				break;
			case self::ABSTRACTION_MYSQL :
				$this->ASSOC = 'mysql_fetch_assoc';
				$this->OBJECT = 'mysql_fetch_object';
				break;
			case self::ABSTRACTION_MYSQLI :
				$this->ASSOC = 'fetch_assoc';
				$this->OBJECT = 'fetch_object';
				break;
			case self::ABSTRACTION_SQLITE3 :
				$this->ASSOC = 'SQLITE3_ASSOC';
				$this->OBJECT = 'SQLITE3_OBJECT';
				break;
			default:
				$m = "Erreur : l'abstraction '{$this->abstraction}' n'est pas prise en charge";
				trigger_error($m, E_USER_WARNING);
		}
	}

	/**
	 * Connection à la base de données en utilisant les informations fournies par
	 * le fichier de configuration.
	 * Cette méthode est private et final car elle n'a pas vocation a être appelée par l'utilisateur.
	 */
	protected function connecter() {
		if ($this->connexion == null) {
			switch ($this->abstraction) {
				case self::ABSTRACTION_PDO :
					try {
						$this->connexion = new PDO($this->dsn, $this->utilisateur, $this->pass);
						$this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					} catch (PDOException $e) {
						$e = sprintf(self::ERREUR_CONNEXION_TPL, $e->getMessage());
						trigger_error($e, E_USER_WARNING);
					}
					if ($this->encodage != null && $this->type == 'mysql') {
						$this->requeter("SET names '".$this->encodage."'");
					} else if ($this->type == 'sqlite') {
						$this->requeter("PRAGMA case_sensitive_like = false");
					}
					break;
				case self::ABSTRACTION_MYSQL :
					$this->connexion = mysql_connect($this->hote, $this->utilisateur, $this->pass);
					if ($this->connexion !== false) {
						$selection = mysql_select_db($this->bdd_nom, $this->connexion);
						if ($selection === false) {
							$e = sprintf(self::ERREUR_SELECTION_BDD_TPL, mysql_error());
							trigger_error($e, E_USER_WARNING);
						}
					} else {
						$e = sprintf(self::ERREUR_CONNEXION_TPL, mysql_error());
						trigger_error($e, E_USER_WARNING);
					}
					if ($this->encodage != null) {
						$this->requeter("SET names '".$this->encodage."'");
					}
					break;
				case self::ABSTRACTION_MYSQLI :
					$this->connexion = @new mysqli($this->hote, $this->utilisateur, $this->pass, $this->bdd_nom);
					if ($this->connexion->connect_errno) {
						$e = sprintf(self::ERREUR_CONNEXION_TPL, $this->connexion->connect_error);
						trigger_error($e, E_USER_WARNING);
					}
					if ($this->encodage != null) {
						$this->requeter("SET names '".$this->encodage."'");
					}
					break;
				case self::ABSTRACTION_SQLITE3 :
					// cas particulier de sqllite, on considère que le nom de la base de données correspond au fichier à ouvrir
					$this->connexion = new SQLite3($this->bdd_nom);
					if (!$this->connexion) {
						$e = sprintf(self::ERREUR_CONNEXION_TPL, '');
						trigger_error($e, E_USER_WARNING);
					}
					$this->requeter("PRAGMA case_sensitive_like = false");
					break;
				default:
					$this->connexion = null;
			}
		}
	}
	
	public function debuterTransaction() {
		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$retour = $this->connexion->beginTransaction();
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
		}
		return $retour;
	}
	
	public function validerTransaction() {
		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$retour = $this->connexion->commit();
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
		}
		return $retour;
	}
	
	public function annulerTransaction() {
		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$retour = $this->connexion->rollBack();
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
		}
		return $retour;
	}
	
	public function executer($requete) {
		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$retour = $this->connexion->exec($requete);
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
		}
		return $retour;		
	}
	
	/**
	 * Execute une requête et retourne le résultat tel que renvoyé par l'abstraction courante.
	 *
	 * @param string la requête à effectuer
	 * @return mixed un objet contenant le résultat de la requête
	 */
	public function requeter($requete) {
		$this->connecter();

		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$retour = $this->connexion->query($requete);
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
			case self::ABSTRACTION_MYSQL :
				$retour = mysql_query($requete, $this->connexion);
				break;
			case self::ABSTRACTION_MYSQLI :
				$retour = $this->connexion->query($requete);
				break;
			case self::ABSTRACTION_SQLITE3 :
				$retour = $this->connexion->exec($requete);
				break;
		}
		return $retour;
	}

	/**
	 * Execute une requête et retourne le premier résultat sous forme de tableau (par défaut) ou d'objet.
	 * Les noms des champs de la base de données correspondent aux noms des clés du tableau ou aux noms des attributs de l'objet.
	 *
	 * @param string la requête à effectuer
	 * @param string le mode de retour ASSOC (Bdd::MODE_ASSOC) pour un tableau ou OBJECT (Bdd::MODE_OBJET) pour un objet.
	 * @return mixed un objet ou un tableau contenant le résultat de la requête
	 */
	public function recuperer($requete, $mode = self::MODE_ASSOC) {
		$this->connecter();

		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$resultat = $this->connexion->query($requete);
					$retour = ($resultat !== false) ? $resultat->fetch($this->$mode) : $resultat;
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
			case self::ABSTRACTION_MYSQL :
				$res = mysql_query($requete, $this->connexion);
				$fonction_fetch = $this->$mode;
				$retour = $fonction_fetch($res);
				break;
			case self::ABSTRACTION_MYSQLI :
				$res = $this->connexion->query($requete);
				$fonction_fetch = $this->$mode;
				$retour = $res->$fonction_fetch();
				break;
			case self::ABSTRACTION_SQLITE3 :
				$retour = $this->connexion->querySingle($requete);
				break;
		}
		return $retour;
	}
	
	/**
	 * Execute une requête et retourne un tableau de résultats. Un résultat peut être présentés sous forme
	 * de tableau (par défaut) ou d'objet.
	 * Les noms des champs de la base de données correspondent aux noms des clés du tableau résultat ou
	 * aux noms des attributs de l'objet résultat.
	 *
	 * @param string la requête à effectuer
	 * @param string le mode de retour des résultats : ASSOC (Bdd::MODE_ASSOC) pour un tableau ou OBJECT (Bdd::MODE_OBJET) pour un objet.
	 * @return array un tableau contenant les résultats sous forme d'objets ou de tableau (par défaut).
	 */
	public function recupererTous($requete, $mode = self::MODE_ASSOC) {
		$this->connecter();

		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				try {
					$resultat = $this->connexion->query($requete);
					$retour = ($resultat !== false) ? $resultat->fetchAll($this->$mode) : $resultat;
				} catch (PDOException $e) {
					$m = sprintf(self::ERREUR_REQUETE_TPL, $e->getFile(), $e->getLine(), $e->getMessage(), $requete);
					trigger_error($m, E_USER_WARNING);
				}
				break;
			case self::ABSTRACTION_MYSQL :
				$resultat = mysql_query($requete, $this->connexion);
				$fonction_fetch = $this->$mode;
				while ($ligne = $fonction_fetch($resultat)) {
					$retour[] = $ligne;
				}
				break;
			case self::ABSTRACTION_MYSQLI :
				$resultat = $this->connexion->query($requete);
				$function_fetch = $this->$mode;
				while ($ligne = $resultat->$function_fetch()) {
					$retour[] = $ligne;
				}
				break;
			case self::ABSTRACTION_SQLITE3 :
				$resultat = $this->connexion->query($requete);
				while ($ligne = $resultat->fetch_array($this->ASSOC)) {
					if ($mode == self::MODE_OBJET) {
						// Cas particulier de sqllite qui n'a pas de fonction fetch_object
						$ligneObjet = new stdClass();
						foreach ($ligne as $colonne => $valeur) {
							$ligneObjet->$colonne = $valeur;
						}
						$ligne = $ligneObjet;
					}
					$retour[] = $ligne;
				}
				break;
		}
		return $retour;
	}

	/**
	 * Protège une chaine de caractères avant l'insertion dans la base de données (ajout de quotes ou guillemets).
	 * @param string la chaine à protéger
	 * @return string la chaine protégée
	 */
	public function proteger($chaine) {
		$this->connecter();
		$chaine = trim($chaine);
		$retour = $chaine;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				$retour = $this->connexion->quote($chaine);
				break;
			case self::ABSTRACTION_MYSQL :
				$retour = '"'.mysql_real_escape_string($chaine, $this->connexion).'"';
				break;
			case self::ABSTRACTION_MYSQLI :
				$retour = '"'.$this->connexion->real_escape_string($chaine).'"';
				break;
			case self::ABSTRACTION_SQLITE3 :
				$retour = $this->connexion->escapeString($chaine);
				break;
		}
		return $retour;
	}
	
	/**
	* Protège les chaines d'un tableau de caractères avant l'insertion dans la base de données (ajout de quotes ou guillemets).
	* @param array le tableau de chaines à protéger
	* @return array le tableau avec les chaines protégées
	*/
	public function protegerTableau($tableau) {
		$champsProteges = array();
		foreach($tableau as $champ => $valeur) {
			$champsProteges[$champ] = $this->proteger($valeur);
		}
		return $champsProteges;
	}

	/**
	 * Protège les chaines d'un tableau de caractères avant l'insertion dans la base de données (ajout de quotes ou guillemets).
	 * @param array le tableau de chaines à protéger
	 * @return array le tableau avec les chaines protégées
	 */
	public function protegerCleValeur(Array $tableau) {
		$champsProteges = array();
		foreach($tableau as $champ => $valeur) {
			$champsProteges[$this->proteger($champ)] = $this->proteger($valeur);
		}
		return $champsProteges;
	}
	
	/**
	 * Retourne l'identifiant de la dernière ligne insérée, ou la dernière valeur d'une séquence d'objets, dépendamment, dans
	 * le cas de PDO, du driver utilisé. Les méthodes utilisées pour retourner l'identifiant peuvent avoir des comportements
	 * différent. Consulter la documentation PHP correspondant à l'abstraction choisie avant de l'utiliser :
	 * @link(http://fr.php.net/manual/fr/pdo.lastinsertid.php, PDO::lastInsertId([ string $name = NULL ]))
	 * @link(http://php.net/manual/en/mysqli.insert-id.php, mysqli->insert_id())
	 * @link(http://fr.php.net/manual/fr/function.mysql-insert-id.php, mysql_insert_id())
	 * @link(http://fr.php.net/manual/fr/sqlite3.lastinsertrowid.php, SQLite3::lastInsertRowID())
	 * @param mixed un paramètre éventuel à transmettre (en fonction de l'abstraction de BDD utilisée).
	 * @return mixed le dernier identifiant de clé primaire ajouté dans la base de données (string ou int).
	 */
	public function recupererIdDernierAjout($parametres = null) {
		$this->connecter();

		$retour = null;
		switch ($this->abstraction) {
			case self::ABSTRACTION_PDO :
				$retour = $this->connexion->lastInsertId($parametres);
				break;
			case self::ABSTRACTION_MYSQL :
				$retour = mysql_insert_id($this->connexion);
				break;
			case self::ABSTRACTION_MYSQLI :
				$retour = $this->connexion->insert_id();
				break;
			case self::ABSTRACTION_SQLITE3 :
				$retour = $this->connexion->lastInsertRowID();
				break;
		}
		return $retour;
	}

	/**
	 * Destructeur de classe, se contente de fermer explicitement la connexion à la base de donnée.
	 */
	public function __destruct() {
		if (isset($this->connexion)) {
			switch ($this->abstraction) {
				case self::ABSTRACTION_PDO :
					$this->connexion = null;
					break;
				case self::ABSTRACTION_MYSQL :
					if (isset($this->connexion)) {
						return mysql_close($this->connexion);
					}
					break;
				case self::ABSTRACTION_MYSQLI :
					$this->connexion->close();
					break;
				case self::ABSTRACTION_SQLITE3 :
					$this->connexion->close();
					break;
			}
		}
	}
}
?>