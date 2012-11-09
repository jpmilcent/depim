<?php
//declare(encoding='UTF-8');
/**
 * Classe permettant de logger des messages dans les fichier situés dans le dossier de log.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: Log.php 274 2010-12-28 15:37:22Z jpm $
 * @link		/doc/framework/
 */
class Log {

	/** Boolean indiquant si l'on doit utiliser les logs ou pas. */
	private static $logger = false;

	/** Tableau associatif stockant les descripteurs de fichiers. */
	private static $fichiersLog = array();

	/** Chemin de base du dossier log de l'application. */
	private static $cheminLogs = '';

	/** Booleen indiquant si l'on peut correctement écrire dans les fichiers de logs. */
	 private static $droitLogger = true;

	/** Zone horaire (pour éviter des avertissements dans les dates). */
	private static $timeZone = 'Europe/Paris';

	/** Taille maximum d'un fichier de log avant que celui ne soit archivé (en octets). */
	private static $tailleMax = 10000;

	/** séparateur de dossier dans un chemin. */
	private static $sd = DIRECTORY_SEPARATOR;

	/** Extension des fichiers de log. */
	private static $ext = '.log';
	
	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	private static $parametres_obligatoires = array('chemin_logs', 'i18n_timezone', 'log_taille_max', 'log_debogage');

	/** Initialiser les logs par défaut, sans tenir comptes des paramêtres de config. */
	public static function initialiser() {
		// gestion de la timezone pour éviter des erreurs
		if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
			date_default_timezone_set(self::$timeZone);
		}

		if (self::$logger && (!is_dir(self::$cheminLogs) || !is_writable(self::$cheminLogs))) {
			self::desactiverEcriture();
		}
	}
	
	/** Configure le Log à partir des paramêtres de config. */
	public static function configurer() {
		Config::verifierPresenceParametres(self::$parametres_obligatoires);
		self::$cheminLogs = Config::get('chemin_logs');
		self::$timeZone = (Config::get('i18n_timezone') != '') ? Config::get('i18n_timezone') : self::$timeZone;
		self::$tailleMax = (Config::get('log_taille_max') != '') ? Config::get('log_taille_max') : self::$tailleMax;
		self::$logger = (Config::get('log_debogage') != '') ? Config::get('log_debogage') : self::$logger;
		self::initialiser();
	}

	/**
	 * Ajoute une entrée au log spécifié par le paramètre $nomFichier
	 * @param string $nomFichier le nom du fichier dans lequel écrire
	 */
	public static function ajouterEntree($nomFichier, $entree, $mode = 'a+') {
		if (self::$droitLogger) {
			$date = "\n\n".date('d m Y H:i')."\n" ;

			if (self::verifierOuvrirFichier($nomFichier, $mode)) {
			   fwrite(self::$fichiersLog[$nomFichier], $date.$entree);
			   self::verifierTailleFichierOuArchiver($nomFichier);
			} else {
				self::desactiverEcriture($nomFichier);
			}
		}
	}

	/**
	 * Vide un fichier log indiqué
	 * @param string $nomFichier le nom du fichier à vider
	 */
	public static function viderLog($nomFichier) {
		self::ajouterEntree($nomFichier, '', 'w');
	}

	/**
	 * Vérifie la présence d'un fichier dans le tableau, ses droits d'écriture, l'ouvre si nécessaire.
	 * 
	 * @param string $nomFichier le nom du fichier dont on doit vérifier la présence
	 * @return boolean true si le fichier est ouvert ou maintenant accessible, false sinon
	 */
	public static function verifierOuvrirFichier($nomFichier,$mode) {
		if (in_array($nomFichier, self::$fichiersLog)) {
			if (is_writable(self::$cheminLogs.$nomFichier.self::$ext)) {
				return true;
			}
			return false;
		} else {
			$fp = @fopen(self::$cheminLogs.$nomFichier.self::$ext,$mode);
			if ($fp && is_writable(self::$cheminLogs.$nomFichier.self::$ext)) {
				self::$fichiersLog[$nomFichier] = $fp;
				return true;
			}
			return false;
		}
	}

	/**
	 * Vérifie la taille d'un fichier donné et si celle ci est trop importante
	 * archive le fichier de log
	 * @param string $nomFichier nom du fichier à vérifier
	 */
	private static function verifierTailleFichierOuArchiver($nomFichier) {
		if(filesize(self::$cheminLogs.$nomFichier.self::$ext) > self::$tailleMax) {
			rename(self::$cheminLogs.$nomFichier.self::$ext,self::$cheminLogs.$nomFichier.date('d_m_Y_H:i').self::$ext);
			self::ajouterEntree($nomFichier,'');
		}
	}

	/**
	 * Désactive l'écriture du log et envoie un message au gestionnaire d'erreurs
	 * @param string $nomFichier le nom du fichier qui a causé l'erreur
	 */
	private static function desactiverEcriture($nomFichier = '') {
		self::$droitLogger = false;
		if ($nomFichier != '') {
			$fichierDossier = 'fichier '.$nomFichier ;
		} else {
			$fichierDossier = 'dossier des logs';
		}
		$message = 'Écriture impossible dans le '.$fichierDossier.', Assurez-vous des droits du dossier et des fichiers';
		$e = new ErrorException($message, 0, E_USER_WARNING, __FILE__, __LINE__);
		GestionnaireException::gererException($e);
	}

	/** Destructeur de classe, ferme les descripteurs ouverts. */
	public function __destruct() {
		foreach(self::$fichiersLog as $nomFichier => $fp) {
			fclose($fp);
		}
	}
}
?>