import 'package:polymer/polymer.dart';
import 'package:observe/observe.dart';
import 'dart:html';
import 'dart:convert';
import '../lib/components/message.dart';
import '../lib/dao/DocumentsDao.dart';
import '../lib/models/Doc.dart';
import '../lib/models/DataRequest.dart';

@CustomTag('doc-panel')
class DocView extends PolymerElement with Observable {

	bool get applyAuthorStyles => true;

	ObservableMap documents = toObservable({});
	DocumentsDao dao = new DocumentsDao();
	@observable Doc document = new Doc.init();
	@observable ObservableList docList = toObservable([]);
	@observable bool docListEmpty = true;
	@observable String supportChoices = 'Javascript,Dart,Java,Php,Python,C#';
	DocView.created() : super.created() {
		dao.onAdded.listen(addEnd);
		dao.onUpdated.listen(updateEnd);
		dao.onDeleted.listen(deleteEnd);

		documents.changes.listen(onDocumentsChanges);

		loadDocuments();
  }

	onDocumentsChanges(records) {
		print('> doc changes');
		docList
			..clear()
			..addAll(_docList);
			docListEmpty = _docListEmpty;
	}

  loadDocuments() {
		print('> loadDocuments');
		dao.loadAll().then(processingDocumentsLoad).catchError(handleError);
  }

  processingDocumentsLoad(responseText) {
		print('> processingDocumentsLoad');
    try {
      this.documents
				..clear()
				..addAll(JSON.decode(responseText));
    } catch(e) {
      handleError(e);
    }
  }

  List get _docList {
		var list = new List();

		if (! documents.isEmpty) {
			documents.forEach((var key, var wh) {
				var classCss = '';
				if (document.id != '' && document.id == wh['meta']['id']) {
					classCss = 'active';
				}

				try {
					if (wh['tags']['titre'] != '') {
						list.add({'id': wh['meta']['id'], 'nom': wh['tags']['abreviation'], 'classCss': classCss});
					} else {
						list.add({'id': wh['meta']['id'], 'nom': 'Sans nom', 'classCss': classCss});
					}
				} catch(e) {
					list.add({'id': wh['meta']['id'], 'nom': 'Sans nom', 'classCss': classCss});
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

	bool get _docListEmpty => _docList.isEmpty;

	void onSelectedDoc(CustomEvent e) {
		var elemMenu = e.detail;
		var id = elemMenu.id;//Utiliser elemMenu.id quand on pourra passer un vrai objet

  	this.loadDocDetails(id);
  	showCommands();
	}

  void loadDocDetails(id) {
    dao.loadDetails(id).then(processingLoadingForm).catchError(handleError);
  }

  processingLoadingForm(responseText) {
    var docInfos = JSON.decode(responseText);
		print(docInfos);
		this.document = new Doc(docInfos);
  }

  void addWarehouse(Event e) {
    e.preventDefault();
		if (! document.isEmpty()) {
			var data = new DataRequest.add()
				..userId = 1
				..tags = document.tags;
			dao.add(data);
		} else {
			showWarning("Veuillez saisir un contenu avant d'ajouter une structure");
		}
  }

  void addEnd(HttpRequest request) {
    if (request.status != 201) {
			showRequestError(request);
			print(request.toString());
    } else {
			var id = request.responseText;
      showSuccess("Un nouveau document avec l'id #$id a été ajouté.");
			print(request.responseText.toString());
			document.id = id;
			showCommands();
			this.loadDocuments();
    }
  }

  void updateDoc(Event e) {
    e.preventDefault();
		print('Update id : ${document.id}');
    var id = document.id;
		if (id != '') {
			var data = new DataRequest.update()
				..userId = 1
				..id = int.parse(document.id)
				..tags = document.tags;
			dao.update(data);
		}
  }

  void updateEnd(HttpRequest request) {
    if (request.status != 200) {
			showRequestError(request);
			print(request.toString());
    } else {
      showSuccess("Le document avec l'id #${request.responseText} a été modifié.");
			print(request.responseText.toString());
    }
  }

  void deleteDoc(Event e) {
		e.preventDefault();
		print('Delete id : ${document.id}');
    var id = document.id;
		if (id != '') {
			var data = new DataRequest.delete()
				..userId = 1
				..id = int.parse(document.id);
			dao.delete(data);
		}
  }

  deleteEnd(HttpRequest request) {
		if (request.status != 204) {
			showRequestError(request);
    } else {
			print('Delete End');
			this.loadDocuments();
			document.clear();
			hideCommands();
			showSuccess('un document a été supprimé');
    }
  }

  resetDoc(Event e) {
		// Reinitialiser l'objet Warehouse
		document.clear();
		hideCommands();
  }

	showCommands() {
		shadowRoot.querySelectorAll('.delete-doc-cmd, .update-doc-cmd').forEach((elem) {
	  	elem.classes.remove('hide');
	  });
	}

	hideCommands() {
	  shadowRoot.querySelectorAll('.delete-doc-cmd, .update-doc-cmd').forEach((elem) {
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