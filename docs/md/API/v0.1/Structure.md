Structure
=========
Created samedi 17 novembre 2012

##### Ajout
<http://localhost/dart/depim/server/services/0.1/structure>
application/json
	{
		"meta" : {
			"utilisateurId" : 1,
			"tags" : {
				"etat" : "A",
				"type" : "structure",
				"commentaire" : "Ajout d'une structure.",
				"source" : "http://fr.geneawiki.com/index.php/Archives_départementales_de_l%27Ardèche"
			}
		},
		"tags" : {
			"adresse" : "Place André Malraux ",
			"adresse:complement" : "BP 737 ",
			"code" : "AD007",
			"code_postal" : "07007",
			"courriel" : "archives@cg07.fr",
			"nom" : "Archives départementales de l'Ardèche",
			"note" : "Test Ajout",
			"telephone:fixe" : "+334 75 66  98 00",
			"telephone:fax" : "+334 75 66 98 18",
			"type" : "archive",
			"url" : "http://www.ardeche.fr/Culture/archives-departementales1861",
			"url:geneawiki" : "http://fr.geneawiki.com/index.php/Archives_départementales_de_l%27Ardèche",
			"ville" : "Privas"
		}
	}


##### Modification
<http://localhost/dart/depim/server/services/0.1/structure/1>
application/json
	{
		"meta" : {
			"utilisateurId" : 1,
			"tags" : {
				"etat" : "M",
				"type" : "structure",
				"commentaire" : "Modification de la structure #1.",
				"source" : "http://fr.geneawiki.com/index.php/Archives_départementales_de_l%27Ardèche"
			}
		},
		"tags" : {
			"adresse" : "Place André Malraux ",
			"adresse:complement" : "BP 737 ",
			"code" : "AD007",
			"code_postal" : "07007",
			"courriel" : "archives@cg07.fr",
			"nom" : "Archives départementales de l'Ardèche",
			"note" : "Test Modif",
			"telephone:fixe" : "+334 75 66  98 00",
			"telephone:fax" : "+334 75 66 98 18",
			"type" : "archive",
			"url" : "http://www.ardeche.fr/Culture/archives-departementales1861",
			"url:geneawiki" : "http://fr.geneawiki.com/index.php/Archives_départementales_de_l%27Ardèche",
			"ville" : "Privas"
		}
	}


##### Suppression
<http://localhost/dart/depim/server/services/0.1/structure/1>
application/json
{
"meta" : {
"utilisateurId" : 1,
"tags" : {
"etat" : "S",
"type" : "structure",
"commentaire" : "Suppression de la structure #1."
}
}
}
