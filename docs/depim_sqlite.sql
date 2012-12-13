PRAGMA legacy_file_format = TRUE;

CREATE TABLE IF NOT EXISTS meta_utilisateur (
  id_utilisateur INTEGER PRIMARY KEY AUTOINCREMENT ,
  fmt_nom_complet TEXT ,
  prenom TEXT ,
  nom TEXT ,
  pseudo TEXT,
  courriel TEXT NOT NULL ,
  mot_de_passe TEXT ,
  mark_licence INTEGER DEFAULT 0 ,
  parametre TEXT ,
  session_id TEXT ,
  ce_meta INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS meta_ontologie (
  id_concept INTEGER PRIMARY KEY AUTOINCREMENT ,
  ce_parent INTEGER NOT NULL ,
  nom TEXT ,
  abreviation TEXT ,
  description TEXT
);

CREATE TABLE IF NOT EXISTS meta_changement (
  id_changement INTEGER PRIMARY KEY AUTOINCREMENT ,
  date TEXT NOT NULL ,
  ce_utilisateur INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS meta_changement_tags (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  PRIMARY KEY (id, cle)
);

CREATE TABLE IF NOT EXISTS structure (
  id_structure INTEGER PRIMARY KEY AUTOINCREMENT ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS structure_tags (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  PRIMARY KEY (id, cle)
);

CREATE TABLE IF NOT EXISTS document (
  id_document INTEGER PRIMARY KEY AUTOINCREMENT ,
  ce_structure INTEGER NOT NULL ,
  date_debut TEXT ,
  date_fin TEXT ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS document_tags (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT NOT NULL ,
  PRIMARY KEY (id, cle)
);

CREATE TABLE IF NOT EXISTS acte (
  id_acte INTEGER PRIMARY KEY AUTOINCREMENT ,
  ce_document INTEGER NOT NULL ,
  ordre INTEGER ,
  date TEXT ,
  transcription TEXT ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS acte_tags (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  PRIMARY KEY (id, cle)
);

CREATE TABLE IF NOT EXISTS personne_citation (
  id_personne_citation INTEGER PRIMARY KEY AUTOINCREMENT ,
  ce_acte INTEGER NOT NULL ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS personne_citation_tags (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT NOT NULL ,
  PRIMARY KEY (id, cle)
);

CREATE TABLE IF NOT EXISTS geographie (
  id_geographie INTEGER PRIMARY KEY AUTOINCREMENT ,
  ce_inclu_dans INTEGER NOT NULL ,
  latitude FLOAT ,
  longitude FLOAT ,
  altitude INTEGER ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS geographie_tags (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  PRIMARY KEY (id, cle)
);

CREATE TABLE IF NOT EXISTS structure_historique (
  id_structure INTEGER NOT NULL ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL,
  PRIMARY KEY (id_structure, meta_version)
);

CREATE TABLE IF NOT EXISTS structure_tags_historique (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  meta_version INTEGER NOT NULL ,
  PRIMARY KEY (id, cle, meta_version)
);

CREATE TABLE IF NOT EXISTS document_historique (
  id_document INTEGER NOT NULL ,
  ce_structure INTEGER NOT NULL ,
  date_debut DATE ,
  date_fin DATE ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL,
  PRIMARY KEY (id_document, meta_version)
);

CREATE TABLE IF NOT EXISTS document_tags_historique (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  meta_version INTEGER NOT NULL ,
  PRIMARY KEY (id, cle, meta_version)
);

CREATE TABLE IF NOT EXISTS acte_historique (
  id_acte INTEGER NOT NULL ,
  ce_document INTEGER NOT NULL ,
  ordre INTEGER ,
  date TEXT ,
  transcription TEXT ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL,
  PRIMARY KEY (id_acte, meta_version)
);

CREATE TABLE IF NOT EXISTS acte_tags_historique (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  meta_version INTEGER NOT NULL ,
  PRIMARY KEY (id, cle, meta_version)
);

CREATE TABLE IF NOT EXISTS personne_citation_historique (
  id_personne_citation INTEGER NOT NULL ,
  ce_acte INTEGER NOT NULL ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL,
  PRIMARY KEY (id_personne_citation, meta_version)
);

CREATE TABLE IF NOT EXISTS personne_citation_tags_historique (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  meta_version INTEGER NOT NULL ,
  PRIMARY KEY (id, cle, meta_version)
);

CREATE TABLE IF NOT EXISTS geographie_historique (
  id_geographie INTEGER NOT NULL ,
  ce_inclu_dans INTEGER NOT NULL ,
  latitude FLOAT ,
  longitude FLOAT ,
  altitude INTEGER ,
  ce_meta INTEGER NOT NULL ,
  meta_version INTEGER NOT NULL ,
  meta_date TEXT NOT NULL,
  meta_visible INTEGER NOT NULL,
  PRIMARY KEY (id_geographie, meta_version)
);

CREATE  TABLE IF NOT EXISTS geographie_tags_historique (
  id INTEGER NOT NULL ,
  cle TEXT NOT NULL ,
  valeur TEXT ,
  meta_version INTEGER NOT NULL ,
  PRIMARY KEY (id, cle, meta_version)
);
