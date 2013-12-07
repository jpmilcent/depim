import 'package:polymer/polymer.dart';
import 'dart:html';
import '../models/BreadcrumbElement.dart';

@CustomTag('app-breadcrumb')
class Breadcrumb extends PolymerElement with Observable {

	bool get applyAuthorStyles => true;
	@observable List breadcrumb = toObservable([]);

	Map<String, String> entryPath = <String, String>{
		'Accueil': '#home',
		'Dépots': '#warehouse',
		'Documents': '#doc'
	};

	Map<String, List> siteMap = <String, List>{
		'': ['Accueil'],
		'#home': ['Accueil'],
		'#warehouse': ['Accueil', 'Dépots'],
		'#doc': ['Accueil', 'Documents']
	};

	Breadcrumb.created() : super.created() {
		print('Hash:'+window.location.hash);
		changeBreadcrumb(window.location.hash);
		window.onHashChange.listen((_) {
			changeBreadcrumb(window.location.hash);
		});
	}

	changeBreadcrumb(id) {
		print('BC id : $id');
		
		var breadcrumbLength = siteMap[id].length;
		breadcrumb.clear();
		for (var i = 0; i < breadcrumbLength; i++) {
			var entry = siteMap[id][i],
			position = i + 1;
			var breadcrumbElemt = new BreadcrumbElement();
			breadcrumbElemt.name = entry;
			breadcrumbElemt.href = (entryPath[entry] != null) ? entryPath[entry] : '';
			breadcrumbElemt.classCss = (position == breadcrumbLength) ? 'active' : '';
			breadcrumbElemt.divider = (position != breadcrumbLength);
			breadcrumb.add(breadcrumbElemt);
		}
	}
}