<?php
// declare(encoding='UTF-8');
/**
 * Script est une classe abstraite qui doit être implémenté par les classes éxecutant des scripts en ligne de commande.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @author		Delphine CAUQUIL <delphine@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2
 * @since 		0.3 
 * @version	$Id: Script.php 299 2011-01-18 14:03:46Z jpm $
 * @link		/doc/framework/
 */

abstract class Script {
	/** Niveau de message de type LOG */
	const MSG_LOG = 0;
	/** Niveau de message de type ERREUR */
	const MSG_ERREUR = 1;
	/** Niveau de message de type AVERTISSEMENT */
	const MSG_AVERTISSEMENT = 2;
	/** Niveau de message de type INFORMATION */
	const MSG_INFO = 3;

	/** Inititulé des différents types de message. */
	private static $msg_niveaux_txt = array('LOG', 'ERREUR','AVERTISSEMENT', 'INFO');
	
	/**
	 * Le nom du script tel que passé dans la ligne de commande.
	 * @var string
	 */
	private $script_nom = null;
	
	/**
	 * Paramêtres par défaut disponibles pour la ligne de commande
	 * le tableau se construit de la forme suivante :	
	 * - clé =  nom du paramêtre '-foo'
	 * - value = contient un nouveau tableau composé de cette façon :
	 *  - booléen: true si le paramêtre est obligatoire
	 *  - booléen ou var : true si le paramêtre nécessite un valeur à sa suite ou la valeur par défaut
	 *  - string: description du contenu du paramêtre
	 * Les paramêtres optionels devraient être déclaré à la fin du tableau.
	 * Le dernier parametre du tableau peut avoir la valeur '...',
	 * il contiendra alors l'ensemble des paramêtres suivant trouvés sur la ligne de commande.
	 * @var array
	 */
	private $parametres_autorises_defaut = array(
		'-a' => array(true, true, 'Action à réaliser'),
		'-v' => array(false, '1', 'Mode verbeux : 1 ou 2'));
	
	/**
	 * Paramêtres autorisés par le script.
	 * le tableau est de la forme suivante :	
	 * - clé =  nom du paramêtre '-foo'
	 * - value = contient un nouveau tableau composé de cette façon :
	 *  - booléen: true si le paramêtre est obligatoire
	 *  - booléen ou var : true si le paramêtre nécessite un valeur à sa suite ou la valeur par défaut
	 *  - string: description du contenu du paramêtre
	 * Les paramêtres optionels devraient être déclaré à la fin du tableau.
	 * Le dernier parametre du tableau peut avoir la valeur '...',
	 * il contiendra alors l'ensemble des paramêtres suivant trouvés sur la ligne de commande.
	 * @var array
	 */
	protected $parametres_autorises = null;
	
	/**
	 * Contient les valeurs des paramêtres récupérés de la ligne de commande :
	 * le tableau se construit de la forme suivnate :	
	 * - clé =  nom du paramêtre '-foo'
	 * - valeur = la valeur récupérée sur la ligne de commande
	 * @var array
	 */
	private $parametres_cli = null;
	
	/**
	 * Contient le tableau des paramètres disponible après vérification :
	 * le tableau est de la forme suivante :	
	 * - clé =  nom du paramêtre '-foo'
	 * - valeur = la valeur récupérée sur la ligne de commande
	 * @var array
	 */
	protected $parametres = null;
	
	/** Tableau associatif permettant de stocker l'avancement dans une boucle.
	 * La clé est un md5 du message à afficher au démarrage de la boucle.
	 * @var array 
	 */
	private static $avancement = array();
	
	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('chemin_modules', 'log_script');
	
	public function __construct($script_nom, $parametres_cli) {
		$this->script_nom = $script_nom;
		$this->parametres_cli = $parametres_cli;
		
		Config::verifierPresenceParametres(self::$parametres_obligatoires);
		
		$fichier_ini_script = $this->getScriptChemin().'config.ini';
		Config::charger($fichier_ini_script);
		
		$this->chargerParametresAutorises();
		$this->chargerParametres();
	}
	
	private static function getMsgNiveauTxt($niveau) {
		return self::$msg_niveaux_txt[$niveau];
	}
	
	protected function getScriptNom() {
		return $this->script_nom;
	}
	
	protected function getScriptChemin($doit_exister = true) {
		$chemin = Config::get('chemin_modules').$this->getScriptNom().DS;
		if (!file_exists($chemin) && $doit_exister) {
			trigger_error("Erreur: le module '".$this->getScriptNom()."' n'existe pas ($chemin)\n", E_USER_ERROR);
		}
		return $chemin;
	}
	
	protected function getParametre($parametre) {
		$retour = false;
		if (!is_null($parametre)) {
			$parametre = ltrim($parametre, '-');
			
			if (isset($this->parametres[$parametre])) {
				$retour = $this->parametres[$parametre];
			} else {
				trigger_error("Erreur: la ligne de commande ne contenait pas le paramêtre '$parametre'\n", E_USER_WARNING);
			}
		}
		return $retour;
	}
	
	abstract public function executer();
	
	private function chargerParametresAutorises() {
		foreach ($this->parametres_autorises_defaut as $c => $v) {
			if (isset($this->parametres_autorises[$c])) {
				trigger_error("Erreur: le script '".$this->getScriptNom()."' ne peut définir le paramêtre '$c' car il existe déjà\n", E_USER_ERROR);
			} else {
				$this->parametres_autorises[$c] = $v;
			}
		}
	}
	
	private function chargerParametres() {
		$parametres_cli = $this->parametres_cli;

		// Récupération des paramêtresgetMsgNiveauTxt
		foreach ($this->parametres_autorises as $p_nom => $p_val) {
			if (count($parametres_cli) == 0) {
				if ($p_val[0]) {
					trigger_error("Erreur: paramêtre manquant '".$p_nom."' \n", E_USER_WARNING);
				}
			}
			if ($p_nom == '...') {
				$this->parametres['...'] = array();
				foreach ($parametres_cli as $arg) {
					$this->parametres['...'][] = $arg;
				}
				$parametres_cli = array();
				break;
			} else {
				if (isset($parametres_cli[$p_nom])) {
					// Attribution de la valeur issue de la ligne de commande
					$this->parametres[ltrim($p_nom, '-')] = $parametres_cli[$p_nom];
					unset($parametres_cli[$p_nom]);
				} else {
					// Attribution de la valeur par défaut
					if ($p_val[1] !== true) {
						$this->parametres[ltrim($p_nom, '-')] = $p_val[1];
						unset($parametres_cli[$p_nom]);
					}
				}
			}
		}
		
		// Gestion de l'excédant de paramêtres
		if (count($parametres_cli)) {
			trigger_error("Erreur: trop de paramêtres\n", E_USER_ERROR);
		}
	}
	
	/** 
	 * Affiche un message d'erreur formaté.
	 * Si le paramétre de verbosité (-v) vaut 1 ou plus, le message est écrit dans le fichier de log et afficher dans la console.
	 *  
	 * @param string le message d'erreur avec des %s.
	 * @param array le tableau des paramêtres à insérer dans le message d'erreur.
	 * @return void.
	 */
	protected function traiterErreur($message, $tab_arguments = array()) {
		$this->traiterMessage($message, $tab_arguments, self::MSG_ERREUR);
	}
	
	/** 
	 * Affiche un message d'avertissement formaté.
	 * Si le paramétre de verbosité (-v) vaut 1, le message est écrit dans le fichier de log.
	 * Si le paramétre de verbosité (-v) vaut 2 ou plus, le message est écrit dans le fichier de log et afficher dans la console.
	 * 
	 * @param string le message d'erreur avec des %s.
	 * @param array le tableau des paramêtres à insérer dans le message d'erreur.
	 * @return void.
	 */
	protected function traiterAvertissement($message, $tab_arguments = array()) {
		$this->traiterMessage($message, $tab_arguments, self::MSG_AVERTISSEMENT);
	}
	
	/** 
	 * Retourne un message d'information formaté.
	 * Si le paramétre de verbosité (-v) vaut 1 ou 2 , le message est écrit dans le fichier de log.
	 * Si le paramétre de verbosité (-v) vaut 3 ou plus, le message est écrit dans le fichier de log et afficher dans la console.
	 * 
	 * @param string le message d'information avec des %s.
	 * @param array le tableau des paramêtres à insérer dans le message d'erreur.
	 * @return void.
	 */
	protected function traiterInfo($message, $tab_arguments = array()) {
		$this->traiterMessage($message, $tab_arguments, self::MSG_INFO);
	}
	
	/** 
	 * Retourne un message formaté en le stockant dans un fichier de log si nécessaire.
	 * 
	 * @param string le message d'erreur avec des %s.
	 * @param array le tableau des paramêtres à insérer dans le message d'erreur.
	 * @param int le niveau de verbosité à dépasser pour afficher les messages.
	 * @return void.
	 */
	private function traiterMessage($message, $tab_arguments, $niveau = self::MSG_LOG) {
		$log = $this->formaterMsg($message, $tab_arguments, $niveau);
		if ($this->getParametre('v') > ($niveau - 1)) {
			echo $log;
			if (Config::get('log_script')) {
				// TODO : lancer le log
			}
		}
	}
	
	/** 
	 * Retourne un message d'information formaté.
	 * 
	 * @param string le message d'information avec des %s.
	 * @param array le tableau des paramêtres à insérer dans le message d'erreur.
	 * @return string le message d'erreur formaté.
	 */
	protected function formaterMsg($message, $tab_arguments = array(), $niveau = null) {
		$texte = vsprintf($message, $tab_arguments);
		$prefixe = date('Y-m-j_H:i:s', time());
		$prefixe .= is_null($niveau) ? ' : ' : ' - '.self::getMsgNiveauTxt($niveau).' : ';
		$log = $prefixe.$texte."\n";
		return $log;
	}
	
	/** 
	 * Utiliser cette méthode dans une boucle pour afficher un message suivi du nombre de tour de boucle effectué.
	 * Vous devrez vous même gérer le retour à la ligne à la sortie de la boucle. 
	 * 
	 * @param string le message d'information.
	 * @param int le nombre de départ à afficher.
	 * @return void le message est affiché dans la console.
	 */
	protected function afficherAvancement($message, $depart = 0) {
		if (! isset(self::$avancement[$message])) {
			self::$avancement[$message] = $depart;
			echo "$message : ";
			
			$actuel =& self::$avancement[$message];
			echo $actuel++;
		} else {
			$actuel =& self::$avancement[$message];
			
			// Cas du passage de 99 (= 2 caractères) à 100 (= 3 caractères)
			$passage = 0;
			if (strlen((string) ($actuel - 1)) < strlen((string) ($actuel))) {
				$passage = 1;				
			}
			
			echo str_repeat(chr(8), (strlen((string) $actuel) - $passage));
			echo $actuel++;
		}
	}
}
?>