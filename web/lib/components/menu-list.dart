import 'package:polymer/polymer.dart';
import 'dart:html';
import '../models/ElementMenu.dart';

@CustomTag('menu-list')
class MenuList extends PolymerElement {

	bool get applyAuthorStyles => true;
	@published String title = '';
	@published String selected = '';
	@published ObservableList elements = toObservable([]);

	MenuList.created() : super.created();

	void onSelectedElement(Event e) {
  	HtmlElement clickedElem = e.target;
		var id = clickedElem.attributes['data-id'];
		selectMenu(id);
  }

	selectMenu(String id) {
		shadowRoot.querySelectorAll("#menu-list li").forEach((elem) {
			elem.classes.remove('active');
		});
		print('>$id>'+shadowRoot.querySelectorAll('a[data-id="$id"]').toString());
		shadowRoot.querySelector('a[data-id="$id"]').parent.classes.add('active');
		dispatchClickedMenu(id);
	}

	dispatchClickedMenu(String id) {
		var elemMenu = new ElementMenu(id);
		dispatchEvent(new CustomEvent('selectmenu', detail: elemMenu));
	}
}