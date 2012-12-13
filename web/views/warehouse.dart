import 'package:web_ui/web_ui.dart';
import 'package:web_ui/watcher.dart' as watchers;
import 'dart:html';
import 'dart:json';
import 'dart:uri';
import '../ui/ui.dart';

class Warehouse extends WebComponent {

  Map warehouses = {};

  void created() {
    this.loadWarehouses();
  }

  void loadWarehouses() {
    var url = 'http://localhost/dart/depim/server/services/0.1/structure';

    // call the web server asynchronously
    var request = new HttpRequest.get(url, onSuccess(HttpRequest req) {
      this.warehouses = JSON.parse(req.responseText);
      watchers.dispatch();
    });
  }

  List get whList {
    var res = new List();
    if (! warehouses.isEmpty) {
      warehouses.forEach((var key, var wh) {
        try {
          if (wh['tags']['nom'] != '') {
            res.add({'id': wh['meta']['id'], 'nom': wh['tags']['nom']});
          } else {
            res.add({'id': wh['meta']['id'], 'nom': 'Sans nom'});
          }
        } catch(e) {
          res.add({'id': wh['meta']['id'], 'nom': 'Sans nom'});
        }
      });
    }
    return res;
  }

  bool get whListEmpty => whList.isEmpty;

  void onSelectedWarehouse(Event e) {
    Element clickedElem = e.target;
    var id = clickedElem.attributes['data-id'];

    // Put warehouse infos in the form
    this.loadWarehouseDetails(id);

    // Show delete command
    queryAll('.delete-warehouse-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
    queryAll('.update-warehouse-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
  }

  void loadWarehouseDetails(id) {
    var url = 'http://localhost/dart/depim/server/services/0.1/structure/$id';

    // call the web server asynchronously
    var request = new HttpRequest.get(url, onSuccess(HttpRequest req) {
      query('.field[name="id"]').value = id;
      var warehouse = JSON.parse(req.responseText);
      Map tags = warehouse['tags'];
      tags.forEach((key, value) {
        query('.field[name="$key"]').value = value;
      });
    });
  }

  void addWarehouse(Event e) {
    e.preventDefault();
    var dataUrl = 'http://localhost/dart/depim/server/services/0.1/structure',
      tags = getTags(),
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'A',
          'type' : 'structure',
          'commentaire' : 'Ajout de la structure "${tags['nom']}".',
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
    var nom = query('input[name="nom"]').value,
      type = query('input[name="type"]').value,
      code = query('input[name="code"]').value,
      adresse = query('input[name="adresse"]').value,
      adresseComplement = query('input[name="adresse:complement"]').value,
      codePostal = query('input[name="code_postal"]').value,
      ville = query('input[name="ville"]').value,
      courriel = query('input[name="courriel"]').value,
      url = query('input[name="url"]').value,
      telFixe = query('input[name="telephone:fixe"]').value,
      telFax = query('input[name="telephone:fax"]').value,
      urlGeneawiki = query('input[name="url:geneawiki"]').value,
      note = query('textarea[name="note"]').value;
    return {
      'nom': nom,
      'type': type,
      'code': code,
      'adresse': adresse,
      'adresse:complement': adresseComplement,
      'code_postal': codePostal,
      'ville': ville,
      'courriel': courriel,
      'url': url,
      'telephone:fixe': telFixe,
      'telephone:fax': telFax,
      'url:geneawiki': urlGeneawiki,
      'note': note
    };
  }

  void addEnd(HttpRequest request) {
    if (request.status != 201) {
      showError(request);
    } else {
      showSuccess('Une nouvelle structure avec l\'id #${request.responseText} a été ajoutée.');
    }
  }

  void updateWarehouse(Event e) {
    e.preventDefault();
    var id = query('input[name="id"]').value,
      dataUrl = 'http://localhost/dart/depim/server/services/0.1/structure/$id',
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'M',
          'type' : 'structure',
          'commentaire' : 'Modification de la structure #$id.'
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
      showSuccess('La structure avec l\'id #$id a été modifiée.');
    }
  }

  void deleteWarehouse(Event e) {
    Element clickedElem = e.target;
    var idStructure = clickedElem.attributes['data-id'],
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'S',
          'type' : 'structure',
          'commentaire' : 'Suppression de la strucutre $idStructure.'
        }
      },
      data = {'meta' : meta},
      encodedData = JSON.stringify(data),
      url = 'http://localhost/dart/depim/server/services/0.1/structure/$idStructure',
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
      showSuccess('Data has been deleted.');
    }
  }

  void resetWarehouse(Event e) {
    // Show delete command
    queryAll('.delete-warehouse-cmd, .update-warehouse-cmd').forEach((elem) {
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
    this.loadWarehouses();
  }
}