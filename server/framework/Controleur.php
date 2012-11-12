<?php
// declare(encoding='UTF-8');
/**
 * Classe Controleur, coeur d'une application, c'est normalement la seule classe d'une application
 * qui devrait être appelée de l'extérieur.
 * Elle est abstraite donc doit obligatoirement être étendue.
 *
 * @category	php 5.2
 * @package	Framework
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org>
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: Controleur.php 332 2011-03-22 16:54:23Z delphine $
 * @link		/doc/framework/
 *
 */
abstract class Controleur {
	/** Variable statique indiquant que les tableaux _GET et _POST ont déjà été encodé au format de l'appli. */
	private static $encodage = false;

	/** Chemin de base vers les classes métiers de la partie Modèle de l'application. */
	private $base_chemin_modele = null;
	
	/** Chemin de base vers les fichiers squelette de la partie Vue de l'application. */
	private $base_chemin_squelette = null;
	
	/** Chemin de base vers les classes controleurs de la partie Controleur de l'application. */
	private $base_chemin_controleur = null;
	
	/** Objet URL contant l'url de la base de l'application. */
	private $base_url_applicaton = null;

	/** Tableau des noms des paramètres à définir dans le fichier de config car obligatoirement nécessaire à cette classe.*/
	protected $parametres_obligatoires = array('chemin_modeles', 'chemin_squelettes', 'chemin_controleurs', 'base_url_application');
	
	/**
	 * Constructeur par défaut
	 */
	public function __construct() {
		Config::verifierPresenceParametres($this->parametres_obligatoires);
		$this->base_chemin_modele = Config::get('chemin_modeles');
		$this->base_chemin_squelette = Config::get('chemin_squelettes');
		$this->base_chemin_controleur = Config::get('chemin_controleurs');
		$this->base_url_application = new Url(Config::get('base_url_application'));
	}

	/**
	* Charge un modele donné et le rend disponible sous la forme $this->nom_modele
	*
	* @param string $nom_modele le nom du modèle à  charger
	*
	* @return boolean false si le chargement a échoué, sinon true.
	*/
	final public function chargerModele($nom_modele) {
		$sortie = true;
		if (!isset($this->$nom_modele)) {
			$modele = $this->getModele($nom_modele);
			if ($modele !== false) {
				$this->$nom_modele = $modele;
			} else {
				$sortie = false;
			}
		}
		return $sortie;
	}

	/**
	* Retourne un modele donné
	*
	* @param string $nom_modele	le nom du fichier modèle à charger sans son extension
	* @param String $ext			l'extension du fichier du modèel (par défaut : ".php"
	*
	* @return mixed false si le chargement a échoué, sinon l'objet du modèle demandé.
	*/
	final protected function getModele($nom_modele, $ext = '.php') {
		$sortie = false;
		
		$chemin_modele = $this->registre->get('base_chemin_modele').$nom_modele.$ext;
		if (file_exists($chemin_modele)) {
			include_once $chemin_modele;
			if (class_exists($nom_modele)) {
				$sortie = new $nom_modele;
			}
		}
		return $sortie;
	}

	/**
	 * Fonction prenant en paramètre le nom d'un squelette et un tableau associatif de données,
	 * en extrait les variables, charge le squelette et retourne le résultat des deux combinés.
	 *
	 * @param String $nom_squelette	le nom du squelette sans son extension
	 * @param Array  $donnees			un tableau associatif contenant les variables a injecter dans la vue
	 * @param String $ext				l'extension du fichier du squelette (par défaut : ".tpl.html"
	 *
	 * @return boolean false si la vue n'existe pas, sinon la chaine résultat.
	 */
	final protected function getVue($nom_squelette, &$donnees = array(), $ext = '.tpl.html') {
		$donnees = $this->preTraiterDonnees($donnees);
		
		$chemin_squelette = $this->base_chemin_squelette.$nom_squelette.$ext;
		$sortie = SquelettePhp::analyser($chemin_squelette, $donnees);

		return $sortie;
	}

	/**
	 * Fonction prenant en paramètre un tableau de données et effectuant un traitement dessus.
	 * Cette fonction est à surcharger dans les classes filles pour automatiser un traitement
	 * avant chaque chargement de vue.
	 *
	 * @param Array $donnees Le tableau de données à traiter
	 *
	 * @return Array $donnees Le tableau de données traité
	 */
	protected function preTraiterDonnees(&$donnees) {
		return $donnees;
	}
}
?>