SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `dpim_meta_utilisateur`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_meta_utilisateur` (
  `id_utilisateur` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant d\'un contact.' ,
  `fmt_nom_complet` VARCHAR(100) NULL COMMENT 'Nom complet du contact formaté pour l\'affichage.' ,
  `prenom` VARCHAR(100) NULL COMMENT 'Prénom de l\'utilisateur.' ,
  `nom` VARCHAR(100) NULL COMMENT 'Nom de l\'utilisateur.' ,
  `pseudo` VARCHAR(50) NULL COMMENT 'Login de la personne pour accÃ©der Ã  l\'application.' ,
  `courriel` VARCHAR(255) NOT NULL COMMENT 'Adresse(s) de courriel personnel.\nAjouter les adresses dans leur ordre d\'importance.\nLes séparer par un point virgule \';\'.' ,
  `mot_de_passe` VARCHAR(100) NULL COMMENT 'Mot de passe de la personne pour accÃ©der Ã  l\'application. EncodÃ© par SHA1.' ,
  `mark_licence` TINYINT(1) NULL DEFAULT 0 COMMENT 'Indique quand la valeur vaut 1 que l\'utilisateur a accepté la licence d\'utilisation de l\'application. ' ,
  `parametre` TEXT NULL COMMENT 'Parametres de l\'utilisateur vis à vis de l\'application.' ,
  `session_id` VARCHAR(100) NULL COMMENT 'Identifiant de session de la personne utilisatrice de l\'application.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des mÃ©tadonnÃ©es de l\'enregistrement.' ,
  PRIMARY KEY (`id_utilisateur`) )
COMMENT = 'Contient les informations sur les utilisateurs de l\'applicat' /* comment truncated */
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_meta_changement`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_meta_changement` (
  `id_changement` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l\'historique des lignes.' ,
  `date` DATETIME NOT NULL COMMENT 'Date de modification de la ligne.' ,
  `ce_utilisateur` BIGINT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id_changement`) )
COMMENT = 'Contient les métadonnées sur les changments des enregistreme' /* comment truncated */
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_structure`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_structure` (
  `id_structure` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la structure de dépôt.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des mÃ©tadonnÃ©es de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_structure`) )
COMMENT = 'INFO : ds_nom'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_document`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_document` (
  `id_document` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du document.' ,
  `ce_structure` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant de la structure de dépôt de ce document.' ,
  `date_debut` DATE NULL COMMENT 'Date de début du document.' ,
  `date_fin` DATE NULL COMMENT 'Date de fin du document.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des métadonnées de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_document`) )
COMMENT = 'INFO : dd_titre'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_acte`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_acte` (
  `id_acte` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l\'acte.' ,
  `ce_document` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant du document contenant l\'acte.' ,
  `ordre` INT(11) NULL COMMENT 'Ordre de relevé chronologique, numéro s\'incrémentant de 1 à ..... .\nQuel que soit le type de relevé, ce numéro correspond à l\'ordre dans lequel on relève, même si on relève des actes B.M.S. mélangés, l\'ordre sera celui de tous les baptèmes.\nCe numéro permet de classer selon l\'ordre originel du registre, même si les dates sont croisées.' ,
  `date` DATETIME NULL COMMENT 'Date et heure dans le calendrier moderne correspondant à une traduction de la date de l\'acte.\nCette date peut alors être comprise entre \'1000-01-01\' et \'9999-12-31\'.' ,
  `transcription` TEXT NULL COMMENT 'Transcription de l\'acte.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des métadonnées de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_acte`) )
COMMENT = 'Information sur les actes.'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_personne_citation`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_personne_citation` (
  `id_personne_citation` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant d\'une référence à une personne.' ,
  `ce_acte` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant de l\'acte dans lequel cette citation de personne figure.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des métadonnées de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_personne_citation`) )
COMMENT = 'Information sur la citation d\'une personne dans un acte.\nINF' /* comment truncated */
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_meta_ontologie`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_meta_ontologie` (
  `id_concept` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant d\'une valeur de liste. Notez que les listes sont des valeurs de la liste des listes.' ,
  `ce_parent` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant du concept parent.' ,
  `nom` VARCHAR(100) NULL COMMENT 'Nom de la valeur.' ,
  `abreviation` VARCHAR(50) NULL COMMENT 'Abréviation, code ou identifiant de la valeur.' ,
  `description` VARCHAR(255) NULL COMMENT 'Description de cette valeur.' ,
  PRIMARY KEY (`id_concept`) )
COMMENT = 'Liste l\'ensemble des valeurs des tables de type liste.\nUn ch' /* comment truncated */
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_personne_citation_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_personne_citation_tags` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`, `cle`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_document_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_document_tags` (
  `id` BIGINT NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`, `cle`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_acte_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_acte_tags` (
  `id` BIGINT NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`, `cle`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_structure_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_structure_tags` (
  `id` BIGINT UNSIGNED NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`, `cle`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_geographie`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_geographie` (
  `id_geographie` BIGINT NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l\'entitÃ© gÃ©ographique.' ,
  `ce_inclu_dans` BIGINT NOT NULL ,
  `latitude` FLOAT NULL DEFAULT NULL COMMENT 'Latitude en dÃ©grÃ©s dÃ©cimaux (WGS84).' ,
  `longitude` FLOAT NULL DEFAULT NULL COMMENT 'Longitude en dÃ©grÃ©s dÃ©cimaux (WGS84).' ,
  `altitude` INT(11) NULL DEFAULT NULL COMMENT 'Altitude en mÃ¨tres.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_geographie`) )
COMMENT = 'INFO : dg_nom_ascii'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_geographie_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_geographie_tags` (
  `id` BIGINT NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`, `cle`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_structure_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_structure_historique` (
  `id_structure` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la structure de dépôt.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des mÃ©tadonnÃ©es de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_structure`, `meta_version`) )
COMMENT = 'INFO : ds_nom'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_structure_tags_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_structure_tags_historique` (
  `id` BIGINT UNSIGNED NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  `meta_version` BIGINT NOT NULL ,
  PRIMARY KEY (`id`, `cle`, `meta_version`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_document_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_document_historique` (
  `id_document` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du document.' ,
  `ce_structure` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant de la structure de dépôt de ce document.' ,
  `date_debut` DATE NULL COMMENT 'Date de début du document.' ,
  `date_fin` DATE NULL COMMENT 'Date de fin du document.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des métadonnées de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_document`, `meta_version`) )
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_document_tags_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_document_tags_historique` (
  `id` BIGINT UNSIGNED NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NOT NULL ,
  `meta_version` BIGINT NOT NULL ,
  PRIMARY KEY (`id`, `cle`, `meta_version`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_meta_changement_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_meta_changement_tags` (
  `id` BIGINT UNSIGNED NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`, `cle`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_acte_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_acte_historique` (
  `id_acte` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l\'acte.' ,
  `ce_document` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant du document contenant l\'acte.' ,
  `ordre` INT(11) NULL COMMENT 'Ordre de relevé chronologique, numéro s\'incrémentant de 1 à ..... .\nQuel que soit le type de relevé, ce numéro correspond à l\'ordre dans lequel on relève, même si on relève des actes B.M.S. mélangés, l\'ordre sera celui de tous les baptèmes.\nCe numéro permet de classer selon l\'ordre originel du registre, même si les dates sont croisées.' ,
  `date` DATETIME NULL COMMENT 'Date et heure dans le calendrier moderne correspondant à une traduction de la date de l\'acte.\nCette date peut alors être comprise entre \'1000-01-01\' et \'9999-12-31\'.' ,
  `transcription` TEXT NULL COMMENT 'Transcription de l\'acte.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des métadonnées de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_acte`, `meta_version`) )
COMMENT = 'Information sur les actes.'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_acte_tags_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_acte_tags_historique` (
  `id` BIGINT UNSIGNED NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  `meta_version` BIGINT NOT NULL ,
  PRIMARY KEY (`id`, `cle`, `meta_version`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_personne_citation_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_personne_citation_historique` (
  `id_personne_citation` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identifiant d\'une référence à une personne.' ,
  `ce_acte` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant de l\'acte dans lequel cette citation de personne figure.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL COMMENT 'Identifiant des métadonnées de l\'enregistrement.' ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_personne_citation`, `meta_version`) )
COMMENT = 'Information sur la citation d\'une personne dans un acte.\nINF' /* comment truncated */
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_personne_citation_tags_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_personne_citation_tags_historique` (
  `id` BIGINT UNSIGNED NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NOT NULL ,
  `meta_version` BIGINT NOT NULL ,
  PRIMARY KEY (`id`, `cle`, `meta_version`) )
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `dpim_geographie_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_geographie_historique` (
  `id_geographie` BIGINT NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l\'entitÃ© gÃ©ographique.' ,
  `ce_inclu_dans` BIGINT NOT NULL ,
  `latitude` FLOAT NULL DEFAULT NULL COMMENT 'Latitude en dÃ©grÃ©s dÃ©cimaux (WGS84).' ,
  `longitude` FLOAT NULL DEFAULT NULL COMMENT 'Longitude en dÃ©grÃ©s dÃ©cimaux (WGS84).' ,
  `altitude` INT(11) NULL DEFAULT NULL COMMENT 'Altitude en mÃ¨tres.' ,
  `ce_meta` BIGINT UNSIGNED NOT NULL ,
  `meta_version` BIGINT NOT NULL ,
  `meta_date` DATETIME NOT NULL ,
  PRIMARY KEY (`id_geographie`, `meta_version`) )
COMMENT = 'INFO : dg_nom_ascii'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `dpim_geographie_tags_historique`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dpim_geographie_tags_historique` (
  `id` BIGINT NOT NULL ,
  `cle` VARCHAR(255) NOT NULL ,
  `valeur` VARCHAR(255) NULL ,
  `meta_version` BIGINT NOT NULL ,
  PRIMARY KEY (`id`, `cle`, `meta_version`) )
ENGINE = MyISAM;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
