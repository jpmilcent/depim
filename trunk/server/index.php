<?php
// Encodage : UTF-8
// Permet d'afficher le temps d'execution du service
$temps_debut = (isset($_GET['chrono']) && $_GET['chrono'] == 1) ? microtime(true) : '';
// +-------------------------------------------------------------------------------------------------------------------+
/**
* Serveur
*
* Description : initialise le chargement et l'exécution des services web.
*
//Auteur original :
* @author       Jean-Pascal Milcent <jpm@clapas.org>
* @copyright    Tela-Botanica 1999-2008
* @licence      GPL v3 & CeCILL v2
* @version      $Id$
*/
// +-------------------------------------------------------------------------------------------------------------------+

// Inclusion du Framework
require_once 'framework/Framework.php';
// Ajout d'information concernant cette application
Framework::setCheminAppli(__FILE__);// Obligatoire
Framework::setInfoAppli(Config::get('info'));
   
// Initialisation et lancement du serveur
$Serveur = new RestServeur();
$Serveur->executer();
   
// Affiche le temps d'execution du service
if (isset($_GET['chrono']) && $_GET['chrono'] == 1) {
	$temps_fin = microtime(true);
	echo 'Temps d\'execution : '.round($temps_fin - $temps_debut, 4);
}
?>