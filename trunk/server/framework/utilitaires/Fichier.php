<?php
// declare(encoding='UTF-8');
/**
 * Classe fournissant des méthodes statiques de manipulation des fichiers.
 *
 * @category	PHP 5.2
 * @package	Utilitaire
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2 
 * @version	$Id: Fichier.php 351 2011-07-21 10:22:34Z jpm $
 * @link		/doc/framework/
 */
class Fichier {
	/** 
	 * Créer et stocke du contenu dans un fichier.
	 * 
	 * @param string le chemin et le nom du fichier.
	 * @param string le contenu à stocker dans le fichier.
	 * @return string true en cas de succès sinon false.
	 */
	public static function creerFichier($fichier, $contenu) {
		$erreur = null;
		
		// Début de l'écriture du fichier
		if ($resource = fopen($fichier, 'w')) {
			if (!fwrite($resource, $contenu)) {
				if (!fclose($resource)) {
					$erreur = "Le fichier '$fichier' n'a pas pu être fermé.";
				}
			} else {
				$erreur = "Le contenu texte n'a pas pu être écrit dans le fichier '$fichier'.";
			}
		} else {
			$erreur = "Le fichier '$fichier' n'a pas pu être ouvert.";
		}

		// Gestion des erreurs et du retour
		if (is_null($erreur)) {
			return true;
		} else {
			trigger_error($erreur, E_USER_WARNING);
			return false;
		}
	}
	
	/** 
	 * Créer et stocke du contenu dans un fichier compressé en Gzip.
	 * 
	 * @param string le chemin et le nom du fichier.
	 * @param string le contenu à stocker dans le fichier.
	 * @return string true en cas de succès sinon false.
	 */
	public static function creerFichierGzip($fichier, $contenu) {
		$erreur = null;
		
		// Ajout de l'extension gz
		if (substr($fichier, -3) != '.gz') {
			$fichier = $fichier.'.gz';
		}
		
		// Début de l'écriture du fichier compressé
		if ($resource = gzopen($fichier, 'w9')) {
			if (gzwrite($resource, $contenu)) {
				if (!gzclose($resource)) {
					$erreur = "Le fichier compressé '$fichier' n'a pas pu être fermé.";
				}
			} else {
				$erreur = "Le contenu texte n'a pas pu être écrit dans le fichier compressé '$fichier'.";
			}
		} else {
			$erreur = "Le fichier compressé '$fichier' n'a pas pu être ouvert.";
		}
		
		// Gestion des erreurs et du retour
		if (is_null($erreur)) {
			return true;
		} else {
			trigger_error($erreur, E_USER_WARNING);
			return false;
		}
	}
	
	/**
	 * Supprime récursivement un dossier et tout son contenu.
	 * 
	 * @param string $dossier le chemin vers le dossier à supprimer.
	 * @return void
	 */
	public static function supprimerDossier($dossier) {
		if (is_dir($dossier)) {
			$objets = scandir($dossier);
			foreach ($objets as $objet) {
				if ($objet != '.' && $objet != '..') {
					$chemin = $dossier.'/'.$objet;
					if (filetype($chemin) == 'dir') {
						$this->supprimerDossier($chemin);
					} else {
						unlink($chemin);
					}
				}
			}
			reset($objets);
			rmdir($dossier);
		}
	}
	
	/**
	 * Convertion d'un nombre d'octet en kB, MB, GB.
	 * @link http://forum.webmaster-rank.info/developpement-site/code-taille-memoire-d-une-variable-en-php-t1344.html
	 * @param integer $taille la taille en octet à convertir
	 * 
	 * @return string la chaine représentant la taille en octets.
	 */
	public static function convertirTaille($taille) {
		$unite = array('B', 'kB', 'MB', 'GB');
		return @round($taille / pow(1024, ($i = floor(log($taille,1024)))), 2).' '.$unite[$i];
	}
	
	/**
	 * Détermine le dossier système temporaire et détecte si nous y avons accès en lecture et écriture.
	 *
	 * Inspiré de Zend_File_Transfer_Adapter_Abstract & Zend_Cache
	 *
	 * @return string|false le chemine vers le dossier temporaire ou false en cas d'échec.
	 */
	public static function getDossierTmp() {
		$dossier_tmp = false;
		foreach (array($_ENV, $_SERVER) as $environnement) {
			foreach (array('TMPDIR', 'TEMP', 'TMP', 'windir', 'SystemRoot') as $cle) {
				if (isset($environnement[$cle])) {
					if (($cle == 'windir') or ($cle == 'SystemRoot')) {
						$dossier = realpath($environnement[$cle] . '\\temp');
					} else {
						$dossier = realpath($environnement[$cle]);
					}
					if (self::etreAccessibleEnLectureEtEcriture($dossier)) {
						$dossier_tmp = $dossier;
						break 2;
					}
				}
			}
		}
		
		if ( ! $dossier_tmp) {
			$dossier_televersement_tmp = ini_get('upload_tmp_dir');
			if ($dossier_televersement_tmp) {
				$dossier = realpath($dossier_televersement_tmp);
				if (self::etreAccessibleEnLectureEtEcriture($dossier)) {
					$dossier_tmp = $dossier;
				}
			}
		}
		
		if ( ! $dossier_tmp) {
			if (function_exists('sys_get_temp_dir')) {
				$dossier = sys_get_temp_dir();
				if (self::etreAccessibleEnLectureEtEcriture($dossier)) {
					$dossier_tmp = $dossier;
				}
			}
		}
		
		if ( ! $dossier_tmp) {
			// Tentative de création d'un fichier temporaire dans le dossier courrant
			$fichier_tmp = @tempnam(md5(uniqid(rand(), TRUE)), '');
			if ($fichier_tmp) {
				$dossier = @realpath(dirname($fichier_tmp));
				@unlink($fichier_tmp);
				if (self::etreAccessibleEnLectureEtEcriture($dossier)) {
					$dossier_tmp = $dossier;
				}
			}
		}
		
		if ( ! $dossier_tmp && self::etreAccessibleEnLectureEtEcriture('/tmp')) {
			$dossier_tmp = '/tmp';
		}
		
		if ( ! $dossier_tmp && self::etreAccessibleEnLectureEtEcriture('\\temp')) {
			$dossier_tmp = '\\temp';
		}
		
		return $dossier_tmp;
	}
	
	/**
	 * Vérifie si le fichier ou dossier est accessible en lecture et écriture.
	 *
	 * @param $ressource chemin vers le dossier ou fichier à tester
	 * @return boolean true si la ressource est accessible en lecture et écriture.
	 */
	protected static function etreAccessibleEnLectureEtEcriture($ressource){
		$accessible = false;
		if (is_readable($ressource) && is_writable($ressource)) {
			$accessible = true;
		}
		return $accessible;
	}
}
?>