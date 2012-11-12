<?php
// declare(encoding='UTF-8');
/**
 * Classe fournissant des fonctions de débogage équivalante à var_dump et print_r.
 * L'affichage et l'utilisation de ces fonctions sont améliorés via cette classe.
 * Cette classe est inspirée de la classe Zend_Debug.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2 
 * @version	$Id: Debug.php 366 2011-09-30 08:20:45Z jpm $
 * @link		/doc/framework/
 */
class Debug {
	   
	/** Paramètrer le fichier de config avec "php:Debug::MODE_ECHO" : les messages sont affichés en utilisant echo au moment 
	 * où ils sont déclenchés dans le code.*/
	const MODE_ECHO = 'echo';
	
	/** Paramètrer le fichier de config avec "php:Debug::MODE_NOTICE" : les message sont stockés par le gestionnaire 
	* d'exception sous forme d'erreur de type E_USER_NOTICE et sont renvoyés sur la sortie standard à la fin de l'execution 
	* du programme (via echo).*/
	const MODE_NOTICE = 'e_user_notice';
	
	/** Paramètrer le fichier de config avec "php:Debug::MODE_ENTETE_HTTP" : les message sont stockés par le gestionnaire 
	 * d'exception sous forme d'erreur de type E_USER_NOTICE et sont renvoyés dans un entête HTTP (X_REST_DEBOGAGE_MESSAGES) 
	 * à la fin de l'execution du programme.
	 * Surtout utile pour le Serveur REST. */
	const MODE_ENTETE_HTTP = 'entete_http';
	
	/** Mode de php (cli ou sapi) */
	protected static $mode = null;
	
	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('debogage', 'debogage_mode');
	
	/**
	 * Accesseur pour le mode
	 * @return string le mode de php
	 */
	public static function getMode() {
		if (self::$mode === null) {
			self::$mode = PHP_SAPI;
		}
		return self::$mode;
	}

	/**
	 * Equivalent de var_dump
	 * @param mixed $variable la variable à dumper
	 * @param string $mot_cle le mot cle à associer à la variable
	 * @param boolean $echo si true on affiche le résultat, si false on ne renvoie que la chaine sans l'afficher
	 * @return string la chaine à afficher representant le dump ou null si echo
	 */
	public static function dump($variable, $mot_cle = null, $echo = false) {
		// var_dump de la variable dans un buffer et récupération de la sortie
		ob_start();
		var_dump($variable);
		$sortie = ob_get_clean();

		// Pré-traitement de la sortie
		$sortie = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $sortie);

		// Traitement général du débogage
		return self::traiterDebogage($mot_cle, $sortie, $echo);
	}

	/**
	 * Equivalent de print_r.
	 * @param mixed $variable la variable à afficher
	 * @param string $mot_cle le mot cle à associer
	 * @param boolean $echo faire un echo ou non
	 * @return string la chaine contenant la variable printée ou null si echo
	 */
	public static function printr($variable, $mot_cle = null, $echo = false) {
		// Récupération de la sortie
		$sortie = print_r($variable, true);

		// Traitement général du débogage
		return self::traiterDebogage($mot_cle, $sortie, $echo);
	}
	
	/**
	 * Affichage d'informations sur l'espace mémoire occupé par une variable
	 * 
	 * @link http://forum.webmaster-rank.info/developpement-site/code-taille-memoire-d-une-variable-en-php-t1344.html
	 * @since 0.3
	 * 
	 * @param mixed $var la variable dont on veut connaître l'empreinte mémoire.
	 * @param string $mot_cle le mot cle à associer
	 * @param boolean $echo faire un echo ou non
	 * 
	 * @return string la chaine d'information sur l'espace mémoire occupé ou bien null si echo
	 */
	public static function tailleMemoireVar($var, $mot_cle = null, $echo = false) {
		$memoire_depart = memory_get_usage();   
		$temp = unserialize(serialize($var));   
		$taille = memory_get_usage() - $memoire_depart;
		$sortie =  Fichier::convertirTaille($taille);
		return self::traiterDebogage($mot_cle, $sortie, $echo);
	}

	/**
	 * Affichage d'informations sur l'espace mémoire occupé par le script PHP
	 * 
	 * @link http://forum.webmaster-rank.info/developpement-site/code-taille-memoire-d-une-variable-en-php-t1344.html
	 * @since 0.3
	 * 
	 * @param string $mot_cle le mot cle à associer
	 * @param boolean $echo faire un echo ou non
	 * 
	 * @return string la chaine d'information sur l'espace mémoire occupé ou bien null si echo
	 */
	public static function tailleMemoireScript($mot_cle = null, $echo = false) {
		$sortie =  'Mémoire -- Utilisé : '.Fichier::convertirTaille(memory_get_usage(false)).
			' || Alloué : '.
			Fichier::convertirTaille(memory_get_usage(true)) .
			' || MAX Utilisé  : '.
			Fichier::convertirTaille(memory_get_peak_usage(false)).
			' || MAX Alloué  : '.
			Fichier::convertirTaille(memory_get_peak_usage(true)).
			' || MAX autorisé : '.
			ini_get('memory_limit');
		
		// Traitement général du débogage
		return self::traiterDebogage($mot_cle, $sortie, $echo);
	}
	
	/**
	 * Traite une chaine de débogage et les mots clés associés
	 * @param string  $mot_cle le mot à associer à la chaine
	 * @param string  $sortie le chaine de debogage
	 * @param boolean $echo faire un echo du resultat ou non
	 * @return string la chaine de debogage formatée ou bien null si echo
	 */
	private static function traiterDebogage($mot_cle, $sortie, $echo) {
		Config::verifierPresenceParametres(self::$parametres_obligatoires);
		$debogage = Config::get('debogage');
		$mode = Config::get('debogage_mode');
		
		$mot_cle = self::formaterMotCle($mot_cle);
		$sortie = self::traiterSortieSuivantMode($mot_cle, $sortie);

		// Affichage et/ou retour
		if ($debogage == true) {
			if ($echo === true || $mode == self::MODE_ECHO) {
				echo $sortie;
				return null;
			} else if ($mode == self::MODE_NOTICE || $mode == self::MODE_ENTETE_HTTP) {
				trigger_error($sortie, E_USER_NOTICE);
				return null; 
			} else {
				return $sortie;
			}
		}
	}

	/**
	 * formate un mot clé donné
	 * @param string $mot_cle le mot clé à formaté
	 * @return string le mot clé formaté ou bien un chaine vide le mot clé est null ou vide
	 */
	private static function formaterMotCle($mot_cle) {
		return ($mot_cle === null) ? '' : rtrim($mot_cle).' ';
	}

	/**
	 * traite la sortie de la chaine de débogage suivant le mode de php
	 * @param string $mot_cle le mot clé associé à la chaine
	 * @param string  $sortie la chaine de débogage
	 * @return string la sortie formatée pour le mode en cours
	 */
	private static function traiterSortieSuivantMode($mot_cle, $sortie) {
		$mode_actuel = Config::get('debogage_mode');
		if ($mode_actuel == self::MODE_ENTETE_HTTP) {
			$cle = (empty($mot_cle)) ? 'message' : $mot_cle;
			$sortie = "$cle:$sortie";
		} else {
			$corps = $mot_cle.PHP_EOL.$sortie;
			if (self::getMode() == 'cli') {
				$sortie = PHP_EOL.$corps.PHP_EOL;
			} else {
				$sortie = '<pre>'.$corps.'</pre>';
			}
		}
		return $sortie;
	}
}
?>