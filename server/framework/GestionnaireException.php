<?php
// declare(encoding='UTF-8');
/**
 * Classe de gestion des exceptions.
 * C'est un Singleton.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jmp@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: GestionnaireException.php 367 2011-10-03 12:40:48Z jpm $$
 * @link		/doc/framework/
 *
 */
class GestionnaireException {
	
	const MODE_CLI = 'cli';
	
	/** Liste des exceptions enregistrées */
	private static $exceptions = array();

	/** Détermine si l'on affiche ou non le contexte */
	private static $contexte = false;

	 /** Détermine si l'on loggue ou non les erreurs */
	private static $logger = false;

	/** Détermine si l'affichage des erreurs est forcé (true) ou pas (false) à la destruction de la classe */
	private static $afficher = false;

	/** Definit si php est lancé en ligne de commande ou en mode serveur */
	private static $mode = null ;

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('debogage', 'debogage_contexte', 'log_debogage');
	
	/** Initialise le Gestionnaire d'exceptions et d'erreur sans tenir comptes des paramêtres de config. */
	public static function initialiser() {
		self::$mode = php_sapi_name();
		// Désactivation des balises HTML dans les messages d'erreur de PHP en mode ligne de commande
		if (self::$mode == self::MODE_CLI) {
			ini_set('html_errors', 0);
		} 
		
		set_exception_handler(array(get_class(),'gererException'));
		set_error_handler(array(get_class(),'gererErreur'));
	}
	
	/** Configure le Gestionnaire d'exceptions et d'erreur à partir des paramêtres de config. */
	public static function configurer() {
		Config::verifierPresenceParametres(self::$parametres_obligatoires);
		self::$contexte = Config::get('debogage_contexte');
		self::$logger = Config::get('log_debogage');
		self::$afficher = Config::get('debogage');
	}
	
	/**
	 * Renvoie le nombre d'exceptions et d'erreurs levées.
	 * @see getExceptions() pour obtenir les exceptions formatées.
	 * @since 0.3 
	 * @return int le nombre d'exception actuellement levées
	 */
	public static function getExceptionsNbre() {
		return count(self::$exceptions);
	}
	
	/** 
	 * Renvoie le booleen définissant si l'on affiche le contexte ou non 
	 * @return bool true si on affiche le contexte sinon false. 
	 */
	public static function getContexte() {
		return self::$contexte;
	}

	/**
	 * Definit si l'on veut afficher le contexte ou non
	 * @param bool true si on veut afficher le contexte, false sinon, par défaut vaut false
	 */
	public static function setContexte($contexte) {
		self::$contexte = $contexte;
	}

	/**
	 * Fonction de gestion des exceptions, remplace le handler par défaut.
	 * Si une boucle génère de multiple exception (ou erreur) identique une seule sera stockée.
	 * @param Exception $e l'exception à traiter
	 */
	public static function gererException(Exception $e) {
		$cle = hash('md5', $e->getMessage().'-'.$e->getFile().'-'.$e->getLine());
		if (!isset(self::$exceptions[$cle])) {
			self::$exceptions[$cle] = $e;
			self::loggerException($e);
		}
	}

	/**
	 * Gère les erreurs en les convertissant en exceptions (remplace la fonction gestion d'erreurs native de php)
	 * @param int $niveau le niveau de l'erreur
	 * @param string $message le message associé à l'erreur
	 * @param string $fichier le nom du fichier où l'erreur s'est produite
	 * @param int $ligne la ligne où l'erreur s'est produite
	 * @param string $contexte le contexte associé à l'erreur
	 */
	public static function gererErreur($niveau,  $message,  $fichier,  $ligne,  $contexte){
		// Si un rapport d'erreur existe, création d'une exception
		if (error_reporting() != 0) {
			$e = new ErrorException($message, 0, $niveau, $fichier, $ligne);
			self::gererException($e);
		}
		return null;
	}

	/**
	 * Renvoie les exceptions au format (X)HTML ou bien au format texte suivant le mode d'utilisation de PHP.
	 * @since 0.3
	 * @deprecated
	 * @see getExceptionsFormatees()
	 * @return string les exceptions formatées en texte ou (X)HTML.
	 */
	public static function getExceptions() {
		return self::getExceptionsFormatees();
	}
	
	/**
	 * Renvoie les exceptions au format (X)HTML ou bien au format texte suivant le mode d'utilisation de PHP.
	 * @since 0.3
	 * @return string les exceptions formatées en texte ou (X)HTML.
	 */
	public static function getExceptionsFormatees() {
		$retour = '';
		if (self::getExceptionsNbre() > 0) {
			foreach (self::$exceptions as $cle => $e) {
				switch (self::$mode) {
					case self::MODE_CLI :
						$retour .= self::formaterExceptionTxt($e);
						break;
					default:
						$retour .= self::formaterExceptionXhtml($e);
				}
				// Nous vidons le tableau des exceptions au fur et à mesure pour éviter le réaffichage avec le destructeur.
				unset(self::$exceptions[$cle]);
			}
		}
		return $retour;
	}
	
	/**
	 * Renvoie le tableau d'objets Exception générées par le script PHP triées du niveau de sévérité le plus élevé au moins élevé.
	 * Format du tableau :
	 * array{sévérité_1 = array{Exception1, Exception2, Exception3,...}, sévérité_1 =  array{Exception1, Exception2, ...}, ...};
	 * ATTENTION : si vous utilisez cette méthode, c'est à vous de gérer l'affichage des Exceptions. Le gestionnaire d'exception
	 * n'enverra plus rien au navigateur ou à la console.
	 * @since 0.3
	 * @return array le tableau trié d'objet Exception.
	 */
	public static function getExceptionsTriees() {
		$retour = array();
		if (self::getExceptionsNbre() > 0) {
			foreach (self::$exceptions as $cle => $e) {
				$retour[$e->getSeverity()][] = $e;
				// Nous vidons le tableau des exceptions au fur et à mesure pour éviter le réaffichage avec le destructeur.
				unset(self::$exceptions[$cle]);
			}
			ksort($retour);
		}
		return $retour;
	}

	/**
	 * Logue une exception donnée sous une forme lisible si self::logger vaut true.
	 * @param Exception	$e l'exception à logger
	 */
	private static function loggerException(Exception $e) {
		if (self::$logger) {
			$message = self::formaterExceptionTxt($e);
			Log::ajouterEntree('erreurs', $message);
		}
	}
	
	/**
	 * Formate en texte une exception passée en paramètre.
	 * @since 0.3
	 * @param Exception l'exception à formater.
	 */
	public static function formaterExceptionDebug(Exception $e) {
		$txt = '';
		if ($e->getSeverity() == E_USER_NOTICE) {
			$txt = $e->getMessage();
		} else {
			$txt = self::formaterExceptionTxt($e);
		}
		return $txt;
	}
	
	/**
	 * Formate en texte une exception passée en paramètre.
	 * @since 0.3
	 * @param Exception l'exception à formater.
	 */
	public static function formaterExceptionTxt(Exception $e) {
		$message = '';
		$message .= $e->getMessage()."\n";
		$message .= 'Fichier : '.$e->getFile()."\n";
		$message .= 'Ligne : '.$e->getLine()."\n";
		if (self::getContexte()) {
			$message .= 'Contexte : '."\n".print_r($e->getTraceAsString(), true)."\n";
		}
		$message .= "\n";
		return $message;
	}
	
	/**
	 * Formate en (X)HTML une exception passée en paramètre.
	 * @since 0.3
	 * @param Exception l'exception à formater.
	 */
	public static function formaterExceptionXhtml(Exception $e) {
		$message = '';
		$message .= '<div class="debogage">'."\n";
		$message .= $e->getMessage()."\n";
		$message .= '<span class="debogage_fichier">'.'Fichier : '.$e->getFile().'</span>'."\n";
		$message .= '<span class="debogage_ligne">'.'Ligne : '.$e->getLine().'</span>'."\n";
		if (self::getContexte()) {
			$message .= '<pre>'."\n";
			$message .= '<strong>Contexte : </strong>'."\n".print_r($e->getTraceAsString(), true)."\n";
			$message .= '</pre>'."\n";
		}
		$message .= '</div>'."\n";
		return $message;
	}
	
	/**
	 * Lors de la destruction de la classe si des exceptions n'ont pas été affichées, et si le débogage est à true, elles sont
	 * affichées. 
	 */
	public function __destruct() {
		// Si des erreurs n'ont pas été affichée nous forçons leur affichage
		if (self::$afficher && self::getExceptionsNbre() > 0) {
			echo self::getExceptionsFormatees();
		}
	}

}
?>