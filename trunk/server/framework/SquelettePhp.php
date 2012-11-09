<?php
// declare(encoding='UTF-8');
/**
 * Classe SquelettePhp, traitant les squelette Php utilisant la syntaxe courte php ou pas.
 * Ces méthodes sont statiques.
 *
 * @category	php5
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: SquelettePhp.php 241 2010-12-06 15:19:07Z jpm $
 * @link		/doc/framework/
 */
class SquelettePhp {

	/**
	 * Fonction prenant en paramètre un chemin de fichier squelette et un tableau associatif de données,
	 * en extrait les variables, charge le squelette et retourne le résultat des deux combinés.
	 * 
	 * @param String	$fichier	le chemin du fichier du squelette
	 * @param Array	$donnees	un tableau associatif contenant les variables a injecter dans le squelette.
	 * @return boolean false si la vue n'existe pas, sinon la chaine résultat.
	 */
	public static function analyser($fichier, Array &$donnees = array()) {
		$sortie = false;
		if (file_exists($fichier)) {
			// Extraction des variables du tableau de données
			extract($donnees);

			// Démarage de la bufferisation de sortie
			ob_start();
			// Si les tags courts sont activés
			if ((bool) @ini_get('short_open_tag') === true) {
				// Simple inclusion du squelette
				include $fichier;
			} else {
				// Sinon, remplacement des tags courts par la syntaxe classique avec echo
				$html_et_code_php = self::traiterTagsCourts($fichier);
				// Pour évaluer du php mélangé dans du html il est nécessaire de fermer la balise php ouverte par eval
				$html_et_code_php = '?>'.$html_et_code_php;
				// Interprétation du html et du php dans le buffer
				echo eval($html_et_code_php);
			}
			// Récupèration du contenu du buffer
			$sortie = ob_get_contents();
			// Suppression du buffer
			@ob_end_clean();
		} else {
			$msg = "Le fichier du squelette '$fichier' n'existe pas.";
			trigger_error($msg, E_USER_WARNING);
		}
		// Retourne le contenu
		return $sortie;
	}

	/**
	 * Fonction chargeant le contenu du squelette et remplaçant les tags court php (<?= ...) par un tag long avec echo.
	 * 
	 * @param	String	$chemin_squelette	le chemin du fichier du squelette
	 * @return	string	le contenu du fichier du squelette php avec les tags courts remplacés.
	 */
	private static function traiterTagsCourts($chemin_squelette) {
		$contenu = file_get_contents($chemin_squelette);
		// Remplacement de tags courts par un tag long avec echo
		$contenu = str_replace('<?=', '<?php echo ',  $contenu);
		// Ajout systématique d'un point virgule avant la fermeture php
		$contenu = preg_replace("/;*\s*\?>/", "; ?>", $contenu);
		return $contenu;
	}
}
?>