//library depim;

import 'dart:html';
import 'package:polymer/polymer.dart';
import 'package:route/client.dart';

final HomeUrl = new UrlPattern(r'/depim/(web|build)/index.html');
final ViewUrl = new UrlPattern(r'/depim/(web|build)/index.html#(\w+)');

main() {
	initPolymer();
	route();
}

route() {
	var router = new Router()
		..addHandler(HomeUrl, openHomeByUrl)
		..addHandler(ViewUrl, openViewByUrl)
		..listen();
}

void openHomeByUrl(String path) {
	path = path + '#home';
	openViewByUrl(path);
}

void openViewByUrl(String path) {
	// Since we only have one match group, we're only worried about the
	// first result.
	var fragment = ViewUrl.parse(path)[1];
	print('path: $path - fragment : $fragment');

	// Véfifions l'existence de cette vue
	var view = '';
  switch(fragment.toLowerCase()) {
		case 'home':
		case '0':
			view = 'home';
			break;
		case 'doc':
  	case '1':
			view = 'doc';
  		break;
  	case 'warehouse':
  	case '2':
  		view = 'warehouse';
  		break;
  	default:
  		view = 'home';
  }
	print('View: $view - Fragment: $fragment');
	// Grab our custom element and assign to the page property.
	var App = document.querySelector('app-main');
	App.view = view;
}