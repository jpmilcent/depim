Architecture
============
Created samedi 12 juin 2010

##### Choix techniques

* [DART](https://www.dartlang.org/)


##### Nous devons utiliser

* des web components : [Polymer](https://www.dartlang.org/polymer-dart/)
* une gestion de l'historique du navigateur : [package route](http://pub.dartlang.org/packages/route)
* des tests unitaires : [package unittest](http://pub.dartlang.org/packages/unittest)
* le [patron Commande](http://fr.wikipedia.org/wiki/Commande_%28patron_de_conception%29) pour la gestion des services web
* éviter d'utiliser des bibliothèques tierces sinon essayer de les encapsuler avec le [patron Façade](http://fr.wikipedia.org/wiki/Fa%C3%A7ade_%28patron_de_conception%29)


##### Notes

* Réaliser des services web orientés par les besoins de l'interface.
* Dans les listes utiliser des objets "légés" contenant qu'une partie des champs de l'objet complet.
	* Exemple : 

 - **Contact** contient (id, nom, prénom, nom_complet, courriel, adresse...)
 - **ContactLight** contient (id, nom_complet)

* Pour tous les actes, le formulaire doit comprendre trois onglets : acte, personnes, transcription.
	* acte : toutes les infos sur l'acte lui même
	* personnes : toutes les personnes citées dans l'acte
	* transcription : une zone permettant de retranscrire le texte de l'acte.
* Toutes les infos présentes dans la feuille excel de test doivent être accessible sur un même onglet!
* Laisser la possibilité d'indiquer plusieurs noms, prénoms, lieu... pour une personne et le doute sur la donnée


#### Besoins

* Dans tous les actes pouvoir lister toutes les personnes listées
* Les champs les plus important sont les noms et prénoms.
* Trouver un système pour gérer les noms inconnus, et les noms difficile à lire.
* Export et import au format Nimegue

