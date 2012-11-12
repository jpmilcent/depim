<?php
// declare(encoding='UTF-8');
/**
 * Classe Cache permettant de mettre en cache des données de façon extremement simple.
 * Le cache est stocker dans des fichiers textes.
 * Le contrôle de la durée de vie du cache se fait avec la fonction PHP filemtime.
 * Si la durée de vie du cache est modifiée dans le constructeur ou le fichier de config, alors la durée de vie de l'ensemble
 * des fichiers de cache est modifiée en conséquence.
 * Les clés pour le tableau des options et les valeurs par défaut sont indiquées dans l'attribut options de la classe.
 * 
 * @category	php 5.2
 * @package	Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @author		Aurélien PERONNET <aurelien@tela-botanica.org> 
 * @copyright	Copyright (c) 2010, Tela Botanica (accueil@tela-botanica.org)
 * @license	http://framework.zend.com/license/new-bsd Licence New BSD
 * @license	http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license	http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version	$Id: CacheSimple.php 299 2011-01-18 14:03:46Z jpm $
 * @link		/doc/framework/
 */
class CacheSimple {
	
	private $mise_en_cache = null;
	private $stockage_chemin = null;
	private $duree_de_vie = null;
	
	private $options = array(
		'mise_en_cache' => 'true',
		'stockage_chemin' => 'Fichier::getDossierTmp()',
		'duree_de_vie' => '3600*24'
	);
	
	public function __construct($options = array()) {
		extract($options);
		$this->mise_en_cache = is_bool($mise_en_cache) ? $mise_en_cache : true;
		
		if ($this->mise_en_cache) {
			$this->stockage_chemin = isset($stockage_chemin) ? realpath($stockage_chemin) : Fichier::getDossierTmp();
			$this->duree_de_vie = isset($duree_de_vie) ? $duree_de_vie : 3600*24;
		}
	}
	
	private function initialiserOptionsParConfig() {
		while (list($nom, $valeur) = each($this->options)) {
			if (Config::existe($nom)) {
				$this->$nom = Config::get($nom);
			}
		}
	}
	
	/**
	 * Teste si le cache est disponible pour l'id donné et (si oui) le retourne (sinon renvoie false)
	 *
	 * @param  string  $id l'identifiant du Cache.
	 * @return string|false les données en cache.
	 */
	public function charger($id) {
		$contenu = false;
		if ($this->mise_en_cache) { 
			$chemin_fichier_cache = $this->stockage_chemin.DS.$id.'.txt';
			if (file_exists($chemin_fichier_cache ) && (time() - @filemtime($chemin_fichier_cache) < $this->duree_de_vie)) {
				$contenu = file_get_contents($chemin_fichier_cache);
			}
		}
		return $contenu;
	}
	
	/**
	 * Sauvegarde la chaine de données dans un fichier texte.
	 *
	 * Note : $contenu est toujours de type "string". C'est à vous de gérer la sérialisation.
	 *
	 * @param  string $contenu les données à mettre en cache.
	 * @param  string $id	l'identifiant du Cache.
	 * @return boolean true si aucun problème
	 */
	public function sauver($contenu, $id) {
		$ok = false;
		if ($this->mise_en_cache) {
			$chemin_fichier_cache = $this->stockage_chemin.DS.$id.'.txt';
		
			if (!file_exists($chemin_fichier_cache) || (time() - @filemtime($chemin_fichier_cache) > $this->duree_de_vie)) {
				$fh = fopen($chemin_fichier_cache,'w+');
				if ($fh) {
					if (fwrite($fh, $contenu)) {
						if (fclose($fh)) {
							$ok = true;
						}
					}
					// Voir #ZF-4422 pour la raison de l'utilisation de octdec()
					@chmod($chemin_fichier_cache,  octdec('0777'));
				}
			}
		}
		return $ok;
	}
}
?>