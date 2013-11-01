library datarequest;

import 'dart:convert';

class DataRequest extends Object {
	int utilisateurId = 0;
	String etat = '';
	String type = '';
	String commentaire = '';
	String source = '';
	Map tags = {};

	DataRequest.add() {
		etat = 'A';
	}

	setUserId(int id) {
		this.utilisateurId = id;
	}

	setType(String type) {
		this.type = type;
	}

	setComment(String comment) {
		this.commentaire = comment;
	}

	setSource(String source) {
		this.source = source;
	}

	setTags(Map tags) {
		this.tags = tags;
	}

	String getTag(String tag) => this.tags[tag];

	Map getMeta() {
		Map meta = {
		'utilisateurId' : utilisateurId,
		'tags' : {
				'etat' : etat,
				'type' : type,
				'commentaire' : commentaire,
				'source' : source
			}
		};
		return meta;
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