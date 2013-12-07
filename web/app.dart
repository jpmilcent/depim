import 'package:polymer/polymer.dart';
import 'dart:html';


@CustomTag('app-main')
class App extends PolymerElement {

	bool get applyAuthorStyles => true;
	Element msgBloc;
	@published String view = '';

	App.created() : super.created();

	// Déclenché par le on-click sur un onglet.
  void updateView(Event e, var detail, Node node) {
		// Réciupère la valeur de l'attribut data-name de l'élément
    var pg = (node as Element).dataset['view'];

    // Passe la valeur de l'attribut data-name au fragment de l'url
    window.location.hash = pg;
  }

	// Triggered by the on-change event for our observable
  // variable 'page'. Observe library is nice in which we only
  // have to create a callback which has the name of the variable
  // plus 'Changed' tacked onto it. Receives the old value it used
  // to contain.
  void viewChanged(oldValue) {
    // This check shouldn't be necessary as the observe library should
    // handle this for us. But I'm paranoid ;)
    if (view == oldValue) {
      return;
    } else if(view == '') {
      // If page is blank just remove anything in the container.
      var container =  $['main-bloc'];
      container.children.clear();
    } else {
			var element = view + '-panel';
      print('Element: $element view: $view');
      _addElement(element);
			switchMenu('#menu-$view');
    }
  }

  // Take the element we determined needed to be added, create it and add it.
  void _addElement(String elementName) {
    if (elementName == '') throw new ArgumentError('Must provide an element name');

    var content = new Element.tag(elementName);
    print('elementName: $elementName Element: ${content.tagName}');
    if (content != null) {
      var container = $['main-bloc'];
			print('Container : ${container.id}');
			container.children
				..clear()
				..add(content);
    } else {
			print('content null');
    }
  }

	switchMenu(id) {
		print('menu id: $id');
		shadowRoot.querySelectorAll('#menu .active').forEach((elem) {
			elem.classes.clear();
	  });
		shadowRoot.querySelector(id).classes.add('active');
	}
}