library depim;

import 'package:web_components/web_components.dart';
import 'package:web_components/watcher.dart' as watchers;

import 'dart:html';

main() {}

void openHomeView(Event event) {
  switchMenu('#menu-home');
  switchView('x-home');
}

void openWareHouseView(Event event) {
  switchMenu('#menu-warehouse');
  switchView('x-warehouse');
}

void switchView(id) {
  query('#main-bloc').elements.forEach((elem) {
    elem.attributes['style'] = 'display:none';
  });
  query(id).attributes['style'] = '';
}

void switchMenu(id) {
  queryAll('#menu .active').forEach((elem) {
    elem.attributes['class'] = '';
  });
  query(id).attributes['class'] = 'active';
  changeBreadcrumb(id);
}

void changeBreadcrumb(id) {
  Map<String, String> entryPath = <String, String>{
    'Accueil': '#menu-home',
    'Dépots': '#menu-warehouse'
  };
  Map<String, List> breadcrumb = <String, List>{
    '#menu-home': ['Accueil'],
    '#menu-warehouse': ['Accueil', 'Dépots']
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
    breadcrumbHtml.add(html);
    print('i:$i/position:$position/length:$breadcrumbLength/html:$html');
  }
  query('#breadcrumb').addHtml(breadcrumbHtml.toString());
}