library depim;

import "package:polymer/polymer.dart";
import 'dart:html';

main() {
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

		queryAll('#breadcrumb li').forEach((e) {
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
		query('#breadcrumb').appendHtml(breadcrumbHtml.toString());
	}

	switchMenu(id) {
		queryAll('#menu .active').forEach((elem) {
			elem.setAttribute('class', '');
	  });
		print(id);
		query(id).setAttribute('class', 'active');
	  changeBreadcrumb(id);
	}

	switchView(id) {
		var customElement = createElement(id);
		query('#main-bloc').children.clear();
		query('#main-bloc').append(customElement);
		print(query('#main-bloc').children[0].toString());
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