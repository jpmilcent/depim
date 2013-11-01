library dao;

import 'dart:html';
import 'dart:async';
import '../config/config.dart' as config;
import '../models/DataRequest.dart';

class WarehousesDao extends Object {

	Future loadDetails(String id) {
		var url = config.urlBaseStructure + '/$id';
		return HttpRequest.getString(url);
	}

	Future loadAll() {
		return HttpRequest.getString(config.urlBaseStructure);
	}

	add(DataRequest data, methode) {
		print('WarehouseDao > add');

		data..setType('structure')
				..setComment('Ajout de la structure "${data.getTag('nom')}".')
				..setSource(data.getTag('urlGeneawiki'));
		print(data.getData().toString());

		var httpRequest = new HttpRequest()
	  	..open('POST', config.urlBaseStructure)
	  	..setRequestHeader('Content-type', 'application/json')
	  	..send(data.getDataEncoded());
		httpRequest.onLoadEnd.listen((e) => methode(httpRequest));
	}

}