import 'package:polymer/polymer.dart';
import 'package:observe/observe.dart';
import 'dart:html';
import 'dart:convert';
import '../ui/ui.dart';

@CustomTag('warehouse-panel')
class Warehouse extends PolymerElement with ObservableMixin {

	bool get applyAuthorStyles => true;
  final urlBase = 'http://localhost/dart/depim/server/services/v1/structures';
  Map warehouses = {};
	@observable List whList = toObservable(new List());

	void created() {
		super.created();

		this.loadWarehouses();
	}

  loadWarehouses() {
    // call the web server asynchronously
    HttpRequest.getString(urlBase).then(processingWarehousesLoad);
  }

  processingWarehousesLoad(responseText) {
    print(responseText);
    this.warehouses = JSON.decode(responseText);
		updateWhList();
  }

	void updateWhList() {
    if (! warehouses.isEmpty) {
      warehouses.forEach((var key, var wh) {
        try {
          if (wh['tags']['nom'] != '') {
            whList.add({'id': wh['meta']['id'], 'nom': wh['tags']['nom']});
          } else {
						whList.add({'id': wh['meta']['id'], 'nom': 'Sans nom'});
          }
        } catch(e) {
					whList.add({'id': wh['meta']['id'], 'nom': 'Sans nom'});
        }
      });
    }
		print("warehouses.isEmpty:"+warehouses.isEmpty.toString()+"-"+whList.toString());
  }

  void onSelectedWarehouse(Event e) {
    Element clickedElem = e.target;
    var id = clickedElem.attributes['data-id'];

    // Put warehouse infos in the form
    this.loadWarehouseDetails(id);

    // Show delete command
    shadowRoot.queryAll('.delete-warehouse-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
    shadowRoot.queryAll('.update-warehouse-cmd').forEach((elem) {
      elem.attributes['data-id'] = id;
      elem.classes.remove('hide');
    });
  }

  void loadWarehouseDetails(id) {
    var url = '${urlBase}/$id';

    // call the web server asynchronously
    HttpRequest.getString(url).then(processingLoadingForm);
  }

  processingLoadingForm(responseText) {
    shadowRoot.query('.field[name="id"]').attributes['value'] = id;
    var warehouse = JSON.decode(responseText);
    Map tags = warehouse['tags'];
    tags.forEach((key, value) {
      shadowRoot.query('.field[name="$key"]').attributes['value'] = value;
    });
  }

  void addWarehouse(Event e) {
    e.preventDefault();
    var tags = getTags(),
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
      encodedData = JSON.encode(data);

    var httpRequest = new HttpRequest();
    httpRequest.open('POST', urlBase);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.onLoadEnd.listen((e) => addEnd(httpRequest));
    print(encodedData);
    httpRequest.send(encodedData);
  }

  Map getTags() {
    var nom = (shadowRoot.query('input[name="nom"]') as InputElement).value,
      type = (shadowRoot.query('input[name="type"]') as InputElement).value,
      code = (shadowRoot.query('input[name="code"]') as InputElement).value,
      adresse = (shadowRoot.query('input[name="adresse"]') as InputElement).value,
      adresseComplement = (shadowRoot.query('input[name="adresse:complement"]') as InputElement).value,
      codePostal = (shadowRoot.query('input[name="code_postal"]') as InputElement).value,
      ville = (shadowRoot.query('input[name="ville"]') as InputElement).value,
      courriel = (shadowRoot.query('input[name="courriel"]') as InputElement).value,
      url = (shadowRoot.query('input[name="url"]') as InputElement).value,
      telFixe = (shadowRoot.query('input[name="telephone:fixe"]') as InputElement).value,
      telFax = (shadowRoot.query('input[name="telephone:fax"]') as InputElement).value,
      urlGeneawiki = (shadowRoot.query('input[name="url:geneawiki"]') as InputElement).value,
      note = (shadowRoot.query('textarea[name="note"]') as InputElement).value;
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
    var id = (shadowRoot.query('input[name="id"]') as InputElement).value,
      dataUrl = '${urlBase}/$id',
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
      encodedData = JSON.encode(data),
      url = '${urlBase}/$idStructure',
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
      showSuccess('une structure a été supprimée');
    }
  }

  void resetWarehouse(Event e) {
    // Show delete command
    shadowRoot.queryAll('.delete-warehouse-cmd, .update-warehouse-cmd').forEach((elem) {
      elem.attributes.remove('data-id');
      elem.classes.add('hide');
    });
    shadowRoot.query('.field[name="id"]').attributes.remove('value');
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