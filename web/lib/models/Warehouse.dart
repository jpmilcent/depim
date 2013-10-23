library model;

import "package:polymer/polymer.dart";

class Warehouse extends Object with Observable {
	@observable String id;
	@observable Map tags;

	Warehouse(var infos) {
		id = infos['meta']['id'];
		if (infos['tags'] != null) {
			tags = toObservable(infos['tags']);
		} else {
			tags = toObservable({
				'nom': '',
				'type': '',
				'code': '',
				'adresse': '',
				'adresse:complement': '',
				'code_postal': '',
				'ville': '',
				'courriel': '',
				'url': '',
				'telephone:fixe': '',
				'telephone:fax': '',
				'url:geneawiki': '',
				'note': ''
			});
		}
	}
}