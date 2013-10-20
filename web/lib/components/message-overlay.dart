library message;

import 'package:polymer/polymer.dart';
import 'dart:html';

@CustomTag('app-message-overlay')
class AppMessageOverlay extends PolymerElement {

	bool get applyAuthorStyles => true;

	@published String text = '';
	@published bool isHtml = false;
	@published String type = '';

	var backdrop;
	HtmlElement message;

	inserted() {
  	super.inserted();
		message = $['message-overlay'];
		addCloseEvent();
		addEscapeKeyEvent();

		addBackdrop();
    displayMsg();
	}

	addCloseEvent() {
		message.query('.close').onClick.listen((e) => delete());
	}

	delete() {
		removeBackdrop();
		message.remove();
	}

	addEscapeKeyEvent() {
		window.onKeyDown.listen((e) {
	  	if (e.keyCode == 27) { // Escape
	  		this.delete();
	 		}
	  });
	}

  addBackdrop() {
    backdrop = new Element.tag('div')
    	..classes.addAll(['modal-backdrop', 'fade', 'in']);
    shadowRoot.children.add(backdrop);
  }

	displayMsg() {
	  message
			..attributes['style'] = 'display:block'
			..classes.add('in')
			..attributes['aria-hidden'] = 'false';

		if (isHtml) {
			message.query('.modal-body').appendHtml(text);
		} else {
			message.query('.modal-body').appendText(text);
		}
  }

  removeBackdrop() {
    backdrop.remove();
  }
}