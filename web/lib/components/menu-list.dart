import 'package:polymer/polymer.dart';
import 'dart:html';
import '../models/ElementMenu.dart';

@CustomTag('menu-list')
class MenuList extends PolymerElement {

	bool get applyAuthorStyles => true;
	@published String title = '';
	@published ObservableList elements = toObservable([]);

	MenuList.created() : super.created();

	void onSelectedElement(Event e) {
  	HtmlElement clickedElem = e.target;
		shadowRoot.querySelectorAll("#menu-list li").forEach((elem) {
			elem.classes.remove('active');
		});
		clickedElem.parent.classes.add('active');

		var id = clickedElem.attributes['data-id'];
		var elemMenu = new ElementMenu()
			..id = id;

		// TODO : utiliser vraiment l'objet quand le bug sera résolut ! (écrire un test pour le vérifier)
		dispatchEvent(new CustomEvent('selectmenu', detail: elemMenu));
  }
}