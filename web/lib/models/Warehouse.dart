library model;

import "package:polymer/polymer.dart";

class Warehouse extends Object with Observable {
	@observable String id;
	@observable Map tags;

	Warehouse(var infos) {
		clear();

		if (infos['meta']['id'] != null) {
			id = infos['meta']['id'];
		}

		if (infos['tags'] != null) {
			tags = toObservable(infos['tags']);
		}
	}

	Warehouse.init() {
		clear();
	}

	clear() {
		_initializeId();
		_initializeTag();
	}

	isEmpty() {
		bool empty = true;
		tags.forEach((key, value) {
			if (value != '') {
				empty = false;
			}
		});
		return empty;
	}

	_initializeId() {
		id = '';
	}

	_initializeTag() {
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