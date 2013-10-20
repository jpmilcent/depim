Architecture
============
Created samedi 12 juin 2010

##### Choix technique
 - DART

##### Nous devons utiliser
 - un MDV
 - un controlleur de l'application (= Présenteur principal)
 - une gestion de l'historique du navigateur
 - des tests unitaires
 - le patron Commande pour la gestion des services web
 - éviter d'utiliser des bibliothèques tierces

##### Notes
Réaliser des services web orientés par les besoins de l'interface.
Dans les listes utiliser des objets "légés" contenant qu'une partie des champs de l'objet complet.
Exemple : 
 - **Contact** contient (id, nom, prénom, nom_complet, courriel, adresse...)
 - **ContactLight** contient (id, nom_complet)
