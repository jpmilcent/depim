Dpim
====
Created mercredi 17 juin 2009

##### Infos

* Git : git clone <https://github.com/jpmilcent/depim.git>
* Bogues : <https://github.com/jpmilcent/depim/issues>
* Liste des noms de version de la Roadmap : [liste des mois et jours du calendrier républicain](http://fr.wikipedia.org/wiki/Calendrier_r%C3%A9publicain)
* [Roadmap](./Roadmap.md)
* [Recherches](./Recherches.md)
* [Tags](./Tags.md)
* [Architecture](./Architecture.md)
* [API](./API.md)


#### Notes
 - Pour tous les actes, le formulaire doit comprendre trois onglets : acte, personnes, transcription.
 	- acte : toutes les infos sur l'acte lui même
 	- personnes : toutes les personnes citées dans l'acte
 	- transcription : une zone permettant de retranscrire le texte de l'acte.
 - Toutes les infos présentes dans la feuille excel de test doivent être accessible sur un même onglet!
 - Laisser la possibilité d'indiquer plusieurs noms, prénoms, lieu... pour une personne et le doute sur la donnée

#### Besoins
 - Dans tous les actes pouvoir lister toutes les personnes listées
 - Les champs les plus important sont les noms et prénoms.
 - Trouver un système pour gérer les noms inconnus, et les noms difficile à lire.
 - Export et import au format Nimegue
