import 'package:web_ui/web_ui.dart';
import 'package:web_ui/watcher.dart' as watchers;
import 'dart:html';
import 'dart:json';
import 'dart:uri';
import '../ui/ui.dart';

class Doc extends WebComponent {

  Map documents = {};

  void created() {
    this.loadDocuments();
  }

  void loadDocuments() {
    var url = 'http://localhost/dart/depim/server/services/0.1/document';

    // call the web server asynchronously
    var request = new HttpRequest.get(url, onSuccess(HttpRequest req) {
      this.documents = JSON.parse(req.responseText);
      watchers.dispatch();
    });
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
    var url = 'http://localhost/dart/depim/server/services/0.1/document/$id';

    // call the web server asynchronously
    var request = new HttpRequest.get(url, onSuccess(HttpRequest req) {
      query('.field[name="id"]').value = id;
      var doc = JSON.parse(req.responseText);
      Map tags = doc['tags'];
      tags.forEach((key, value) {
        query('.field[name="$key"]').value = value;
      });
    });
  }

  void addDoc(Event e) {
    e.preventDefault();
    var dataUrl = 'http://localhost/dart/depim/server/services/0.1/document',
      tags = getTags(),
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
      encodedData = JSON.stringify(data);

    var httpRequest = new HttpRequest();
    httpRequest.open('POST', dataUrl);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.on.loadEnd.add((e) => addEnd(httpRequest));
    print(encodedData);
    httpRequest.send(encodedData);
  }

  Map getTags() {
    var titre = query('input[name="titre"]').value,
      support = query('input[name="support"]').value,
      code = query('input[name="code"]').value,
      abreviation = query('input[name="abreviation"]').value,
      codeInsee = query('input[name="code:insee"]').value,
      commune = query('input[name="commune"]').value,
      urlSource = query('input[name="url:source"]').value,
      note = query('textarea[name="note"]').value;
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
    var id = query('input[name="id"]').value,
      dataUrl = 'http://localhost/dart/depim/server/services/0.1/document/$id',
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
      encodedData = JSON.stringify(data);

    var httpRequest = new HttpRequest();
    httpRequest.open('POST', dataUrl);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.on.loadEnd.add((e) => updateEnd(httpRequest, id));
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
      encodedData = JSON.stringify(data),
      url = 'http://localhost/dart/depim/server/services/0.1/document/$idDoc',
      httpRequest = new HttpRequest();
    httpRequest.open('DELETE', url);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.on.loadEnd.add((e) => deleteEnd(httpRequest));
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
    query('.field[name="id"]').attributes.remove('value');
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