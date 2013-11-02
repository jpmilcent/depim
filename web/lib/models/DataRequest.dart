library datarequest;

import 'dart:convert';

class DataRequest extends Object {
	int _id = 0;
	int _utilisateurId = 0;
	String _etat = '';
	String _type = '';
	String _commentaire = '';
	String _source = '';
	Map _tags = {};

	DataRequest.add() {
		_etat = 'A';
	}

	DataRequest.update() {
		_etat = 'M';
	}

	DataRequest.delete() {
		_etat = 'S';
	}

	setId(int id) => this._id = id;

	int getId() => this._id;

	setUserId(int id) => this._utilisateurId = id;

	setType(String type) => this._type = type;

	setComment(String comment) => this._commentaire = comment;

	setSource(String source) => this._source = source;

	setTags(Map tags) => this._tags = tags;

	String getTag(String tag) => this._tags[tag];

	Map getMeta() {
		Map meta = {
		'utilisateurId' : _utilisateurId,
		'tags' : {
				'etat' : _etat,
				'type' : _type,
				'commentaire' : _commentaire,
				'source' : _source
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
		Map data = {'meta': getMeta(), 'tags': this._tags};
		return data;
	}

	getDataEncoded() {
		var dataEncoded = JSON.encode(getData());
		return dataEncoded;
	}
}