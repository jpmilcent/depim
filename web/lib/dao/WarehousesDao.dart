library dao;

import 'dart:html';
import 'dart:async';
import '../config/config.dart' as config;
import '../models/DataRequest.dart';
import 'EventStream.dart';

class WarehousesDao extends Object {

	final EventStream _onAddEndEvent = new EventStream();
	final EventStream _onUpdateEndEvent = new EventStream();
	final EventStream _onDeleteEndEvent = new EventStream();

	Stream get onAdded => _onAddEndEvent.stream;
	Stream get onUpdated => _onUpdateEndEvent.stream;
	Stream get onDeleted => _onDeleteEndEvent.stream;

	Future loadDetails(String id) {
		var url = config.urlBaseStructure + '/$id';
		return HttpRequest.getString(url);
	}

	Future loadAll() {
		return HttpRequest.getString(config.urlBaseStructure);
	}

	add(DataRequest data) {
		print('WarehouseDao > add');

		data..type = 'structure'
				..comment = 'Ajout de la structure "${data.tags['nom']}".'
				..source = data.tags['url:geneawiki'];
		print(data.getData().toString());

		var httpRequest = new HttpRequest()
	  	..open('PUT', config.urlBaseStructure)
	  	..setRequestHeader('Content-type', 'application/json')
	  	..send(data.getDataEncoded());
		httpRequest.onLoadEnd.listen((e) => _onAddEndEvent.signal(httpRequest));
	}

	update(DataRequest data) {
		var id = data.id;
		print('WarehouseDao > update $id');

		data..type ='structure'
				..comment = 'Modification de la structure "$id".'
				..source =data.tags['urlGeneawiki'];
		print(data.getData().toString());

		var httpRequest = new HttpRequest()
	  	..open('POST', '${config.urlBaseStructure}/$id')
	  	..setRequestHeader('Content-type', 'application/json')
	  	..send(data.getDataEncoded());
		httpRequest.onLoadEnd.listen((e) => _onUpdateEndEvent.signal(httpRequest));
	}

	delete(DataRequest data) {
		var id = data.id;
		print('WarehouseDao > delete $id');

		data..type = 'structure'
			..comment = 'Suppression de la structure "$id".'
			..source = data.tags['urlGeneawiki'];
			print(data.getData().toString());

			var httpRequest = new HttpRequest()
		  	..open('DELETE', '${config.urlBaseStructure}/$id')
		  	..setRequestHeader('Content-type', 'application/json')
		  	..send(data.getMetaEncoded());
			httpRequest.onLoadEnd.listen((e) => _onDeleteEndEvent.signal(httpRequest));
	}

}