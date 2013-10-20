library depim;

import 'package:polymer/polymer.dart';
import 'dart:html';

@CustomTag('app-main')
class App extends PolymerElement with ObservableMixin {

	bool get applyAuthorStyles => true;
	Element msgBloc;

	void created() {
		super.created();

	}

	changeBreadcrumb(id) {
		Map<String, String> entryPath = <String, String>{
			'Accueil': '#menu-home',
			'Dépots': '#menu-warehouse',
			'Documents': '#menu-doc'
		};
		Map<String, List> breadcrumb = <String, List>{
			'#menu-home': ['Accueil'],
			'#menu-warehouse': ['Accueil', 'Dépots'],
			'#menu-doc': ['Accueil', 'Documents']
		};

		shadowRoot.queryAll('#breadcrumb li').forEach((e) {
			e.remove();
		});
		var breadcrumbLength = breadcrumb[id].length;

		var breadcrumbHtml = new StringBuffer();
		for (var i = 0; i < breadcrumbLength; i++) {
			var entry = breadcrumb[id][i],
			position = i + 1,
			classCss = (position == breadcrumbLength) ? 'class="active"' : '',
			href = (entryPath[entry] != null) ? 'href="${entryPath[entry]}"' : '',
			divider = (position != breadcrumbLength) ? '<span class="divider">></span>' : '',
			html = '<li $classCss><a $href>$entry</a>$divider</li>';
			breadcrumbHtml.write(html);
			print('i:$i/position:$position/length:$breadcrumbLength/html:$html');
		}
		shadowRoot.query('#breadcrumb').appendHtml(breadcrumbHtml.toString());
	}

	switchMenu(id) {
		shadowRoot.queryAll('#menu .active').forEach((elem) {
			elem.classes.clear();
	  });
		shadowRoot.query(id)
			..classes.add('active');
	  changeBreadcrumb(id);
	}

	switchView(id) {
		var customElement = createElement(id);
		shadowRoot.query('#main-bloc')
			..children.clear()
			..append(customElement);
	}

	openHomeView(Event event) {
	  switchMenu('#menu-home');
	  switchView('home-panel');
	}

	openWareHouseView(Event event) {
	  switchMenu('#menu-warehouse');
	  switchView('warehouse-panel');
	}

	openDocView(Event event) {
	  switchMenu('#menu-doc');
	  switchView('doc-panel');
	}
}