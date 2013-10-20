library depim;

import 'dart:html';
import 'package:route/client.dart';
import 'lib/models/urls.dart' as urls;

// TODO : router ne fonctionne pas avec polymer...
main() {
	print('main');
	var router = new Router()
		..addHandler(urls.homeUrl, openViewByUrl)
		..addHandler(urls.warehouseUrl, openViewByUrl)
		..addHandler(urls.docUrl, openViewByUrl)
		..listen();
}

openViewByUrl(String path) {
	print('path:'+path);
	var view = path.replaceAll(new RegExp(r'\/depim\/web\/index\.html#?'), '');
	view = (view == '') ? 'home' : view;
	window.dispatchEvent(new CustomEvent('openview', detail: view));
	print('Vue:'+view);
}