import 'package:polymer/polymer.dart';
import 'package:observe/observe.dart';
import 'dart:html';
import 'dart:json' as json;
import '../ui/ui.dart';

@CustomTag('doc-panel')
class Doc extends PolymerElement with ObservableMixin {

	bool get applyAuthorStyles => true;

  final urlBase = 'http://localhost/dart/depim/server/services/v1/documents';

	@observable Map documents = {};

  void created() {
		super.created();
    this.loadDocuments();
  }

  loadDocuments() {
    // call the web server asynchronously
    HttpRequest.getString(urlBase).then(processingDocumentsLoad);
  }

  processingDocumentsLoad(responseText) {
    print(responseText);
    try {
      this.documents = json.parse(responseText);
    } catch(e) {
      print(e);
    }
    //watchers.dispatch();
  }

  List get docList {
    var res = new List();
    if (! documents.isEmpty) {
      documents.forEach((var key, var doc) {
        try {
          if (doc['tags']['titre'] != '') {
            res.add({'id': doc['meta']['id'], 'abbr': doc['tags']['abreviation']});
          } else {
            res.add({'id': doc['meta']['id'], 'abbr': 'Sans nom'});
          }
        } catch(e) {
          res.add({'id': doc['meta']['id'], 'abbr': 'Sans nom'});
        }
      });
    }
    return res;
  }

  bool get docListEmpty => docList.isEmpty;

  void onSelectedDoc(Event e) {
    Element clickedElem = e.target;
    var id = clickedElem.attributes['data-id'];

    // Put doc infos in the form
    this.loadDocDetails(id);

    // Show delete command
    queryAll('.delete-doc-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
    queryAll('.update-doc-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
  }

  void loadDocDetails(id) {
    var url = '${urlBase}/$id';

    // call the web server asynchronously
    HttpRequest.getString(url).then((responseText) {
			InputElement idElmt = query('input.field[name="id"]');
			idElmt.value = id;
      var doc = json.parse(responseText);
      Map tags = doc['tags'];
      tags.forEach((key, value) {
        var field = query('.field[name="$key"]');
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
      encodedData = json.stringify(data);

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
      note = (query('textarea[name="note"]') as InputElement).value;
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
    var id = (query('input[name="id"]') as InputElement).value,
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
      encodedData = json.stringify(data);

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
      encodedData = json.stringify(data),
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
    // Show delete command
    queryAll('.delete-doc-cmd, .update-doc-cmd').forEach((elem) {
      elem.attributes.remove('data-id');
      elem.classes.add('hide');
    });
    query('input.field[name="id"]').attributes.remove('value');
  }

  void showError(HttpRequest request) {
    var msg = 'Une erreur de type ${request.status} est survenue. \n ${request.responseText}';
    new Message('error').show(msg);
  }

  void showSuccess(String msg) {
    new Message('success').show(msg);
    this.loadDocuments();
  }
}