library datarequest;

import 'dart:convert';

class DataRequest extends Object {
	int id = 0;
	int userId = 0;
	String state = '';
	String type = '';
	String comment = '';
	String source = '';
	Map tags = {};

	DataRequest.add() {
		state = 'A';
	}

	DataRequest.update() {
		state = 'M';
	}

	DataRequest.delete() {
		state = 'S';
	}

	Map getMeta() {
		Map meta = {
		'utilisateurId' : userId,
		'tags' : {
				'etat' : state,
				'type' : type,
				'commentaire' : comment,
				'source' : source
			}
		};
		return meta;
	}

	getMetaEncoded() {
		Map data = {'meta': getMeta()};
		var metaEncoded = JSON.encode(data);
		return metaEncoded;
	}

	Map getData() {
		Map data = {'meta': getMeta(), 'tags': this.tags};
		return data;
	}

	getDataEncoded() {
		var dataEncoded = JSON.encode(getData());
		return dataEncoded;
	}
}