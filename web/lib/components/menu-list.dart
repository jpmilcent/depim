import 'package:polymer/polymer.dart';
import 'dart:html';
import '../models/ElementMenu.dart';

@CustomTag('menu-list')
class MenuList extends PolymerElement {

	bool get applyAuthorStyles => true;
	@published String title = '';
	@published List elements = new List();

	void onSelectedElement(Event e) {
  	HtmlElement clickedElem = e.target;
		shadowRoot.queryAll("#menu-list li").forEach((elem) {
			elem.classes.remove('active');
		});
		clickedElem.parent.classes.add('active');

		var id = clickedElem.attributes['data-id'];
		var elemMenu = new ElementMenu()
			..id = id;
		print(elemMenu.id.toString());
		// TODO : utiliser vraiment l'objet quand le bug sera résolut ! (écrire un test pour le vérifier)
		dispatchEvent(new CustomEvent('selectmenu', detail: elemMenu.id));
  }
}