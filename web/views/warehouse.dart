import 'package:polymer/polymer.dart';
import 'package:observe/observe.dart';
import 'dart:html';
import 'dart:convert';
import '../lib/components/message.dart';
import '../lib/dao/WarehousesDao.dart';
import '../lib/models/Warehouse.dart';
import '../lib/models/DataRequest.dart';

@CustomTag('warehouse-panel')
class WarehouseView extends PolymerElement with Observable {

	bool get applyAuthorStyles => true;
  final urlBase = 'http://localhost/dart/depim/server/services/v1/structures';
	ObservableMap warehouses = toObservable({});
	WarehousesDao dao = new WarehousesDao();
	@observable Warehouse warehouse = new Warehouse.init();
	@observable ObservableList whList = toObservable([]);
	@observable bool whListEmpty = true;

	WarehouseView.created() : super.created() {
		warehouses.changes.listen((records) {
			print('> warehouses changes');
			whList
				..clear()
				..addAll(_whList);
			whListEmpty = _whListEmpty;
   	});

		loadWarehouses();
	}

  loadWarehouses() {
    // call the web server asynchronously
		print('> loadWarehouses');
		dao.loadAll().then(processingWarehousesLoad).catchError(handleError);
  }

  processingWarehousesLoad(responseText) {
    print('> processingWarehousesLoad');
    this.warehouses
			..clear()
			..addAll(JSON.decode(responseText));
  }

	List get _whList {
  	var list = new List();

  	if (! warehouses.isEmpty) {
			warehouses.forEach((var key, var wh) {
	  		try {
		  			if (wh['tags']['titre'] != '') {
							list.add({'id': wh['meta']['id'], 'nom': wh['tags']['nom']});
		  			} else {
							list.add({'id': wh['meta']['id'], 'nom': 'Sans nom'});
		  			}
	  		} catch(e) {
					list.add({'id': wh['meta']['id'], 'nom': 'Sans nom'});
	  		}
	  	});
  	}

		list.sort((elemA, elemB) {
	    var a = int.parse(elemA['id']);
			var b = int.parse(elemB['id']);
			if (a == b) {
	      return 0;
	    } else if (a > b) {
	      return 1;
	    } else {
	      return -1;
	    }
	  });

		return list;
	}

	bool get _whListEmpty {
		return _whList.isEmpty;
	}

  void onSelectedWarehouse(CustomEvent e) {
		var elemMenu = e.detail;
		var id = elemMenu.id;//Utiliser elemMenu.id quand on pourra passer un vrai objet

    // Put warehouse infos in the form
    this.loadWarehouseDetails(id);

    // Show delete command
    shadowRoot.querySelectorAll('.delete-warehouse-cmd, .update-warehouse-cmd').forEach((elem) {
      elem.classes.remove('hide');
    });
  }

  void loadWarehouseDetails(id) {
    dao.loadDetails(id).then(processingLoadingForm).catchError(handleError);
  }

  processingLoadingForm(responseText) {
    var warehouseInfos = JSON.decode(responseText);
		print(warehouseInfos);
		this.warehouse = new Warehouse(warehouseInfos);
  }

  void addWarehouse(Event e) {
    e.preventDefault();
		if (! warehouse.isEmpty()) {
			var data = new DataRequest.add()
				..setUserId(1)
				..setTags(warehouse.tags);

			dao.add(data, addEnd);
		} else {
			showWarning("Veuillez saisir un contenu avant d'ajouter une structure");
		}
  }

  void addEnd(HttpRequest request) {
    if (request.status != 201) {
			showRequestError(request);
			print(request.toString());
    } else {
      showSuccess('Une nouvelle structure avec l\'id #${request.responseText} a été ajoutée.');
			print(request.responseText.toString());
	    this.loadWarehouses();
    }
  }

  void updateWarehouse(Event e) {
    e.preventDefault();
		print('Update id : ${warehouse.id}');
    var id = warehouse.id;
		if (id != '') {
      var dataUrl = '${urlBase}/$id',
	      meta = {
	        'utilisateurId' : 1,
	        'tags' : {
	          'etat' : 'M',
	          'type' : 'structure',
	          'commentaire' : 'Modification de la structure #$id.'
	        }
	      },
	      tags = warehouse.tags,
	      data = {'meta' : meta, 'tags': tags},
	      encodedData = JSON.encode(data);
			print('Update url :'+dataUrl);
			print('Update data :'+tags.toString());
	    var httpRequest = new HttpRequest();
	    httpRequest.open('POST', dataUrl);
	    httpRequest.setRequestHeader('Content-type', 'application/json');
	    httpRequest.onLoadEnd.listen((e) => updateEnd(httpRequest, id));
	    httpRequest.send(encodedData);
		}
  }

  void updateEnd(HttpRequest request, String id) {
    if (request.status != 200) {
			showRequestError(request);
    } else {
      showSuccess('La structure avec l\'id #$id a été modifiée.');
    }
  }

  void deleteWarehouse(Event e) {
		e.preventDefault();
		print('Delete id : ${warehouse.id}');
    var id = warehouse.id;
		if (id != '') {
      var meta = {
	        'utilisateurId' : 1,
	        'tags' : {
	          'etat' : 'S',
	          'type' : 'structure',
	          'commentaire' : 'Suppression de la strucutre $id.'
	        }
	      },
	      data = {'meta' : meta},
	      encodedData = JSON.encode(data),
	      url = '${urlBase}/$id',
	      httpRequest = new HttpRequest();
	    httpRequest.open('DELETE', url);
	    httpRequest.setRequestHeader('Content-type', 'application/json');
	    httpRequest.onLoadEnd.listen((e) => deleteEnd(httpRequest));
	    print(encodedData);
	    httpRequest.send(encodedData);
		}
  }

  deleteEnd(HttpRequest request) {
		if (request.status != 204) {
			showRequestError(request);
    } else {
      showSuccess('une structure a été supprimée');
	    this.loadWarehouses();
    }
  }

  resetWarehouse(Event e) {
		// Reinitialiser l'objet Warehouse
		warehouse.clear();

		// Show delete command
    shadowRoot.querySelectorAll('.delete-warehouse-cmd, .update-warehouse-cmd').forEach((elem) {
      elem.classes.add('hide');
    });
  }

	handleError(e) {
		showError('Une erreur est survenue : ${e.toString()}');
	}

	showRequestError(HttpRequest request) {
  	var msg = 'Une erreur de type ${request.status} est survenue. \n' +
		'${request.responseText}';
		showError(msg);
	}

  showError(String msg) {
		_showMessage('error', msg);
  }

	showWarning(String msg) {
		_showMessage('warning', msg);
  }

  showSuccess(String msg) {
		_showMessage('success', msg);
  }

	_showMessage(String type, String msg) {
		HtmlElement msgElem = new Element.tag('app-message');
		AppMessage message = msgElem.xtag;
		message.text = msg;
		message.type = type;

		shadowRoot.children.add(msgElem);
	}
}