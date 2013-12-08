import 'package:polymer/polymer.dart';
import 'dart:html';
import '../models/BreadcrumbElement.dart';
import 'dart:mirrors';

@CustomTag('app-breadcrumb')
class Breadcrumb extends PolymerElement {

	bool get applyAuthorStyles => true;
	// INFO : MirrorsUsed nécessaire pour éviter le bug "noSuchMethodError : method not found" lors du déploiment en JS
	@MirrorsUsed(targets: 'Element,Node', symbols: 'name,href,classCSS,divider')
	@observable ObservableList breadcrumb = toObservable([]);

	Map<String, String> entryPath = <String, String>{
		'Accueil': '#home',
		'Dépots': '#warehouse',
		'Documents': '#doc'
	};

	Map<String, List> siteMap = <String, List>{
		'#home': ['Accueil'],
		'#warehouse': ['Accueil', 'Dépots'],
		'#doc': ['Accueil', 'Documents']
	};
	
	Breadcrumb.created() : super.created();

	void enteredView() {
	  super.enteredView();
	  print('Hash:'+window.location.hash);
		
		window
		  ..onLoad.listen((_) {changeBreadcrumb(window.location.hash);})
		  ..onHashChange.listen((_) {changeBreadcrumb(window.location.hash);});
		if (window.location.hash == '') {
		  window.location.hash = '#home';
		}
	}

	void changeBreadcrumb(String id) {
	  print('BC id : $id');
		if (siteMap.containsKey(id)) {
  		var breadcrumbLength = siteMap[id].length;
  		print('BC breadcrumbLength : $breadcrumbLength');
  		breadcrumb.clear();
  		print('BC after clear');
  		for (var i = 0; i < breadcrumbLength; i++) {
  			var entry = siteMap[id][i],
          position = i + 1,
          breadcrumbElemt = new BreadcrumbElement();
        
  			breadcrumbElemt.name = entry;
  			breadcrumbElemt.href = (entryPath[entry] != null) ? entryPath[entry] : null;
  			breadcrumbElemt.classCss = (position == breadcrumbLength) ? 'active' : null;
  			breadcrumbElemt.divider = (position != breadcrumbLength);
  			print('BC before add :'+breadcrumbElemt.toString());
  			breadcrumb.add(breadcrumbElemt);
  		}
		}
	}
}