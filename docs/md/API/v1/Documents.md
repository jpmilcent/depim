Documents
=========
Created jeudi 28 février 2013

##### Ajout
URL : <http://localhost/dart/depim/server/services/v1/documents>
Content-type : application/json
Action : PUT
Content :
{
"meta" : {
"utilisateurId" : 1,
"tags" : {
"etat" : "A",
"type" : "document",
"commentaire" : "Ajout d'un document.",
"source" : "<http://fr.geneawiki.com/index.php/Archives_d%C3%A9partementales_de_l%27Ard%C3%A8che>"
}
},
"data" : {
"structureId" : 1,
"dateDebut" : "1786-01-01",
"dateFin" : "1792-12-31"
},
"tags" : {
"abreviation" : "Reg. Paroi. BMS 1774-1785",
"code" : "Doc747",
"code:insee" : "48080",
"commune" : "Luc",
"lieu" : "Luc",
"note" : "Test Ajout",
"support" : "numérique",
"titre" : "Registre paroissial des baptêmes, mariages et sépultures de 1786 à 1792"
}
}

##### Modification
URL : <http://localhost/dart/depim/server/services/v1/documents/1>
Content-type : application/json
Action : POST
Content :
{
"meta" : {
"utilisateurId" : 1,
"tags" : {
"etat" : "M",
"type" : "document",
"commentaire" : "Modification du document #1.",
"source" : "<http://fr.geneawiki.com/index.php/Archives_d%C3%A9partementales_de_Loz%C3%A8re>"
}
},
"data" : {
"structureId" : 1,
"dateDebut" : "1786-01-01",
"dateFin" : "1792-12-31"
},
"tags" : {
"abreviation" : "Reg. Paroi. BMS 1786-1792",
"code" : "Doc737",
"code:insee" : "48080",
"commune" : "Luc",
"lieu" : "Luc",
"note" : "Test Modif",
"support" : "numérique",
"titre" : "Registre paroissial des baptêmes, mariages et sépultures de 1786 à 1792"
}
}


##### Suppression
Note : il se peut que la suppression renvoie une erreur via Poster. Une requête HTTP DELETE n'est peut être pas sensée envoyer du contenu...
URL : <http://localhost/dart/depim/server/services/v1/documents/1>
Content-type : application/json
Action : DELETE
Content :
{
"meta" : {
"utilisateurId" : 1,
"tags" : {
"etat" : "S",
"type" : "document",
"commentaire" : "Suppression du document #1."
}
}
}
