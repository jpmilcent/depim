<?php
// declare(encoding='UTF-8');
/** Chronometre permet de stocker et d'afficher les temps d'éxécution de script.
 *
 * Cette classe permet de réaliser un ensemble de mesure de temps prises à différents endroits d'un script.
 * Ces mesures peuvent ensuite être affichées au sein d'un tableau XHTML.
 *
 * @category	PHP 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2 
 * @version	$Id: Chronometre.php 327 2011-02-08 17:54:34Z jpm $
 * @link		/doc/framework/
 */
class Chronometre {
	/*** Attributs : ***/
	private static $pointArretNumero = 1;
	private static $temps = array();

	/** Accesseurs :
	 *
	 * @param string $cle la cle associée à un chronomètre particulier
	 *
	 * @return int le temps écoulé
	 */
	private static function getTemps($cle = null) {
		if (is_null($cle)) {
			$temps = self::$temps;
		} else {
			foreach (self::$temps as $temps_enrg) {
				if (isset($temps_enrg[$cle])) {
					$temps = $temps_enrg;
					break;
				}
			}
		}
		return $temps;
	}

	/** Setteur pour la variable temps
	 *
	 * @param array() $moment ajoute des points de chronométrage au tableau _temps
	 *
	 * @return null
	 */
	private static function setTemps($cle, $moment) {
		array_push(self::$temps, array($cle => $moment));
	}

	/*** Méthodes : ***/
	
	/** 
	 *	Effectue un chronometrage. 
	 * Vous pouvez indiquer le nom du point de chronométrage.
	 * Si vous n'indiquez rien, un nombre sera généré en débutant à 1.
	 *
	 * @param string le nom du point de chronométrage
	 * @return null
	 */
	public static function chrono($cle = null) {
		if ($cle == null) {
			$cle = (count(self::$temps) == 0) ? 'Début' : self::$pointArretNumero++;
		}
		$moment = microtime();
		self::setTemps($cle, $moment);
	}
	
	/** 
	* Permet d'afficher les temps d'éxécution de différentes parties d'un script.
	*
	* Cette fonction permet d'afficher un ensemble de  mesure de temps prises à différents endroits d'un script.
	* Ces mesures sont affichées au sein d'un tableau XHTML dont on peut controler l'indentation des balises.
	* Pour un site en production, il suffit d'ajouter un style #chrono {display:none;} dans la css.
	* De cette façon, le tableau ne s'affichera pas. Le webmaster lui pourra rajouter sa propre feuille de style 
	* affichant le tableau.
	* Le développeur initial de cette fonction est Loic d'Anterroches.
	* Elle a été modifiée par Jean-Pascal Milcent.
	*
	* @author Loic d'Anterroches
	* @author Jean-Pascal MILCENT <jpm@tela-botanica.org>
	* @param string l'eventuel nom du point de chronométrage de fin.
	* @return   string  la chaine XHTML de mesure des temps.
	*/
	public static function afficherChrono($cle = null) {
		if (count(self::$temps) == 0) {
			$sortie = "Aucun chronométrage à l'aide de Chronometre::chrono() n'a été réalisé.";
		} else {
			// Création du chrono de fin
			self::chrono('Fin');
			
			$total_tps_ecoule = 0;
			$tps_debut = null;
			$tbody = '';
			foreach (self::getTemps() as $temps) {
				foreach ($temps as $cle => $valeur) {
					// Récupération de la premiére mesure
					if (is_null($tps_debut)) {
						$tps_debut = self::getMicroTime($valeur);
					}
					// Récupération de la mesure courrante
					$tps_fin = self::getMicroTime($valeur);
	
					$tps_ecoule = abs($tps_fin - $tps_debut);
					$total_tps_ecoule += $tps_ecoule;
					$tps_debut = $tps_fin;
					
					// Gestion affichage
					$total_tps_ecoule_fmt = number_format($total_tps_ecoule, 3, ',', ' ');
					$tps_ecoule_fmt = number_format($tps_ecoule, 3, ',', ' ');
					$tbody .= '<tr><th>'.$cle.'</th><td>'.$tps_ecoule_fmt.'</td><td>'.$total_tps_ecoule_fmt.'</td></tr>'."\n";
				}
			}
			$total_tps_ecoule_final_fmt = number_format($total_tps_ecoule, 3, ',', ' ');
			// Début création de l'affichage
			$sortie = '<table id="chrono" lang="fr" summary="Résultat duchronométrage du programme affichant la page actuelle.">'."\n".
				'<caption>Chronométrage</caption>'."\n".
				'<thead>'."\n".
				'	<tr><th>Action</th><th title="Temps écoulé vis à vis de l\'action précédente">Temps écoulé (en s.)</th><th>Cumul du temps écoulé (en s.)</th></tr>'."\n".
				'</thead>'."\n".
				'<tbody>'."\n".
					$tbody.
				'</tbody>'."\n".
				'<tfoot>'."\n".
				'	<tr><th>Total du temps écoulé (en s.)</th><td colspan="2">'.$total_tps_ecoule_final_fmt.'</td></tr>'."\n".
				'</tfoot>'."\n".
				'</table>'."\n";
		}
		return $sortie;
	}
	
	private static function getMicroTime($utps) {
	    list($usec, $sec) = explode(' ', $utps);
	    return ((float)$usec + (float)$sec);
	}
}
?>