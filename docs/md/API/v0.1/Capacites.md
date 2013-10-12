Capacites
=========
Created dimanche 19 septembre 2010

### Capacites: GET /api/capacites
Ce service fournit des informations sur les capacités et limites de l'API courante.

##### Réponse
Content-type : text/xml.
Objet JSON "api"  au format :
	api : {
	  version : {

	    minimum : 0.1, 
	    maximum : 0.1},
	  service : {
	    timeOut : #duree_max_execution_service, 
	   maxElements : #max_elements_retournes}
	}


##### Notes
Noter que ce service est accessible depuis une url sans indication de numéro de version. Toutefois, il est aussi possible d'y accéder depuis une url de ce type : /api/0.1/capacites.
