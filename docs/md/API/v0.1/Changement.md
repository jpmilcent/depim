Changement
==========
Created samedi 18 septembre 2010


### Query: GET /api/0.1/changement/#id
Retourne les informations sur le changement.

##### Paramêtres
**id :** l'identifiant du changement.

##### Réponse
Content-type : text/xml.
Objet JSON "changement"  au format :
	changement : {
	  id : #id
	  version : #version
	  utilisateur : #nom_complet
	  uid : #uid
	  date : #date
	  ip : #ip
	  tags : [
	    {c : #cle, v : #valeur},
	    {c : #cle, v : #valeur},
	  ]
	}


##### Code d'erreur
**HTTP status code 404 (Not Found)**
Quand aucun changement ne correspond au #id.

### Query: GET /api/0.1/changements
Permet d'intéroger les changements. Ce service accepte plusieurs paramêtres.
Quand plusieurs paramêtres sont passés, le résultat correspondra à tous les critères passés en paramêtre.
Le résultat de la requête contiendra toutes les changements leurs tags. Pour récupérer, les données correspondant à ces changements utiliser le service changement/#id/telecharger pour chaque #id changement.

##### Paramêtres
**changements=#id,#id,...**
Filtre les changements en fontion des identifiants de changements passés en paramêtre.
**utilisateur=#uid** or **utilisateur_nom=#nom_complet**
Filtre les changements en fonction de l'identifiant ou du nom complet de l'utilisateur. Utiliser ces deux paramêtres en même temps retourne une erreur.
**date=D1**
Filtre les changements ayant eu lieu à la date indiquée. La date doit être au format ISO 8601, quelque chose comme : aaaa-mm-jj ou aaaa-mm-jjThh:mm:ss
**dates=D1/D2**
Filtre les changements ayant eu lieu entre la date D1 et la date D2. La date doit être au format ISO 8601, quelque chose comme : aaaa-mm-jj/aaaa-mm-jj ou aaaa-mm-jjThh:mm:ss/aaaa-mm jjThh:mm:ss

__Format de date :__ la date doit respecter le [format ISO 8601](http://fr.wikipedia.org/wiki/ISO_8601), ce qui donne : 2010-09-18 ou 2010-09-18T12:08:05.


##### Réponse
Retourne un tableau JSON contenant tous les changements ordonnés par date. 
Le tableau peut être vide s'il n'y a aucun changement qui correspond à la requête. 
La réponse est retournée avec un type de contenu : text/xml.
	changements : [

	  changement : {
	    id : #id
	    version : #version
	    utilisateur : #nom_complet
	    uid : #uid
	    date : #date
	    ip : #ip
	    tags : [
	      {c : #cle, v : #valeur},
	      {c : #cle, v : #valeur},

	      ...
	    ]
	  },

	  changement : {
	    id : #id
	    version : #version
	    utilisateur : #nom_complet
	    uid : #uid
	    date : #date
	    ip : #ip
	    tags : [
	      {c : #cle, v : #valeur},
	      {c : #cle, v : #valeur},

	      ...
	    ]
	  },
	 ...

  ]

##### Codes d'erreur
**HTTP status code 400 (Bad Request) - text/plain**
En cas de paramêtre mal formé. Un message expliquant l'erreur est retourné. Si les paramêtres utilisateur=#uid et utilisateur_nom=#nom_complet sont utilisés conjointement ce type d'erreur sera retourné.
**HTTP status code 404 (Not Found)**
Quand aucun utilisateur ne correspond au #uid ou au #nom_complet. 

##### Notes

* Retourne au plus 100 changements


### Query: GET /api/0.1/changement/#id/telecharger
Retourne l'objet correspondant au changement.

##### Paramêtres
**id**
L'identifiant du changement.

##### Réponse
Content-type : text/xml.
Objet JSON "changement"  au format :

	changement : {
	  id : #id
	  donnees : [
	    {structure : {...}},
	    ...
	  ]
	}


##### Code d'erreur
**HTTP status code 404 (Not Found)**
Quand aucun changement ne correspond au #id.
