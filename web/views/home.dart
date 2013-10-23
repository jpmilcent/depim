import 'package:polymer/polymer.dart';
import 'dart:html';
import '../lib/components/message.dart';
import '../lib/components/message-overlay.dart';

@CustomTag('home-panel')
class Home extends PolymerElement {

	bool get applyAuthorStyles => true;

	Home.created() : super.created();

  void addMessage(e) {
    var now = new DateTime.now();

		HtmlElement msgElem = createElement('app-message');
		AppMessage message = msgElem.xtag
			..text = "Welcome to Dart polymer element ! $now"
			..type = 'success';

		shadowRoot.children.add(msgElem);
  }

  void addMessageOverlay(e) {
    var now = new DateTime.now();

		HtmlElement msgElem = createElement('app-message-overlay');
		AppMessageOverlay message = msgElem.xtag
			..isHtml = true
			..text = "<p>Welcome to Dart! $now<button>Un bouton</button></p>"
			..type = 'success';

		shadowRoot.children.add(msgElem);
  }
}