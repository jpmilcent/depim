import 'package:polymer/polymer.dart';
import 'package:observe/observe.dart';
import 'dart:html';
import 'dart:convert';
import '../lib/components/message.dart';

@CustomTag('doc-panel')
class Doc extends PolymerElement with Observable {

	bool get applyAuthorStyles => true;
  final urlBase = 'http://localhost/dart/depim/server/services/v1/documents';
	@observable Map documents = {};
	@observable List docList;
	@observable bool docListEmpty;

	Doc.created() : super.created() {
		//onPropertyChange(this, #docList, () {docList = _docList;});
		//onPropertyChange(this, #docListEmpty, () {docListEmpty = _docListEmpty;});
		this.loadDocuments();
  }

  loadDocuments() {
    // call the web server asynchronously
    HttpRequest.getString(urlBase).then(processingDocumentsLoad);
  }

  processingDocumentsLoad(responseText) {
    print(responseText);
    try {
      this.documents = JSON.decode(responseText);
    } catch(e) {
      print(e);
    }
  }

	@reflectable
  List get _docList {
    var res = new List();
    if (! documents.isEmpty) {
      documents.forEach((var key, var doc) {
        try {
          if (doc['tags']['titre'] != '') {
            res.add({'id': doc['meta']['id'], 'nom': doc['tags']['abreviation']});
          } else {
            res.add({'id': doc['meta']['id'], 'nom': 'Sans nom'});
          }
        } catch(e) {
          res.add({'id': doc['meta']['id'], 'nom': 'Sans nom'});
        }
      });
    }
    return res;
  }

	@reflectable
	bool get _docListEmpty => _docList.isEmpty;

  void onSelectedDoc(CustomEvent e) {
		var elemMenu = e.detail;
		var id = elemMenu;//Utiliser elemMenu.id quand on pourra passer un vrai objet

    // Put doc infos in the form
    this.loadDocDetails(id);

    // Show delete command
    shadowRoot.querySelectorAll('.delete-doc-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
		shadowRoot.querySelectorAll('.update-doc-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
  }

  void loadDocDetails(id) {
    var url = '${urlBase}/$id';

    // call the web server asynchronously
    HttpRequest.getString(url).then((responseText) {
			InputElement idElmt = shadowRoot.querySelector('input.field[name="id"]');
			idElmt.value = id;
      var doc = JSON.decode(responseText);
      Map tags = doc['tags'];
      tags.forEach((key, value) {
        var field = shadowRoot.querySelector('.field[name="$key"]');
        if (field != null) {
          field.attributes['value'] = value;
        } else {
          print('Not implemented => $key : $value');
        }
      });
    });
  }

  void addDoc(Event e) {
    e.preventDefault();
    var tags = getTags(),
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'A',
          'type' : 'document',
          'commentaire' : 'Ajout du document "${tags['titre']}".',
          'source' : tags['urlGeneawiki']
        }

      },
      data = {'meta' : meta, 'tags': tags},
      encodedData = JSON.encode(data);

    var httpRequest = new HttpRequest();
    httpRequest.open('POST', urlBase);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.onLoadEnd.listen((e) => addEnd(httpRequest));
    print(encodedData);
    httpRequest.send(encodedData);
  }

  Map getTags() {
    var titre = (query('input[name="titre"]') as InputElement).value,
      support = (query('input[name="support"]') as InputElement).value,
      code = (query('input[name="code"]') as InputElement).value,
      abreviation = (query('input[name="abreviation"]') as InputElement).value,
      codeInsee = (query('input[name="code:insee"]') as InputElement).value,
      commune = (query('input[name="commune"]') as InputElement).value,
      urlSource = (query('input[name="url:source"]') as InputElement).value,
      note = (query('textarea[name="note"]') as TextAreaElement).value;
    return {
      'titre': titre,
      'support': support,
      'code': code,
      'abreviation': abreviation,
      'code:insee': codeInsee,
      'commune': commune,
      'url:source': urlSource,
      'note': note
    };
  }

  void addEnd(HttpRequest request) {
    if (request.status != 201) {
      showError(request);
    } else {
      showSuccess('Un nouveau document avec l\'id #${request.responseText} a été ajouté.');
    }
  }

  void updateDoc(Event e) {
    e.preventDefault();
    var id = (shadowRoot.query('input[name="id"]') as InputElement).value,
      dataUrl = '${urlBase}/$id',
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'M',
          'type' : 'document',
          'commentaire' : 'Modification du document #$id.'
        }
      },
      tags = getTags(),
      data = {'meta' : meta, 'tags': tags},
      encodedData = JSON.encode(data);

    var httpRequest = new HttpRequest();
    httpRequest.open('POST', dataUrl);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.onLoadEnd.listen((e) => updateEnd(httpRequest, id));
    httpRequest.send(encodedData);
  }

  void updateEnd(HttpRequest request, String id) {
    if (request.status != 200) {
      showError(request);
    } else {
      showSuccess('Le document avec l\'id #$id a été modifié.');
    }
  }

  void deleteDoc(Event e) {
    Element clickedElem = e.target;
    var idDoc = clickedElem.attributes['data-id'],
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'S',
          'type' : 'document',
          'commentaire' : 'Suppression du document $idDoc.'
        }
      },
      data = {'meta' : meta},
      encodedData = JSON.encode(data),
      url = '${urlBase}/$idDoc',
      httpRequest = new HttpRequest();
    httpRequest.open('DELETE', url);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.onLoadEnd.listen((e) => deleteEnd(httpRequest));
    print(encodedData);
    httpRequest.send(encodedData);
  }

  void deleteEnd(HttpRequest request) {
    if (request.status != 204) {
      showError(request);
    } else {
      showSuccess('un document a été modifié.');
    }
  }

  void resetDoc(Event e) {
    // Show delete & update command
		shadowRoot.querySelectorAll('.delete-doc-cmd, .update-doc-cmd').forEach((elem) {
      elem.attributes.remove('data-id');
      elem.classes.add('hide');
    });
		shadowRoot.querySelectorAll('.field').forEach((elem) {
			elem.attributes.remove('value');
		});
  }

  void showError(HttpRequest request) {
    var msg = 'Une erreur de type ${request.status} est survenue. \n ${request.responseText}';
		HtmlElement msgElem = new Element.tag('app-message');
		AppMessage message = msgElem.xtag;
		message.text = msg;
		message.type = 'error';

		shadowRoot.children.add(msgElem);
  }

  void showSuccess(String msg) {
		HtmlElement msgElem = new Element.tag('app-message');
		AppMessage message = msgElem.xtag;
		message.text = msg;
		message.type = 'success';

		shadowRoot.children.add(msgElem);

    this.loadDocuments();
  }
}