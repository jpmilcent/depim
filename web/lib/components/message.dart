library messageOverlay;

import 'package:polymer/polymer.dart';
import 'dart:html';
import 'dart:async';

@CustomTag('app-message')
class AppMessage extends PolymerElement {

	bool get applyAuthorStyles => true;

	@published String text = '';
	@published String type = '';

	Duration msgDuration = new Duration(seconds:5);
	HtmlElement message;
	static int calledNber = 0;

	AppMessage.created() : super.created();

	inserted() {
  	super.inserted();

		message = $['message'];

		positionElement();
		addCloseEvent();
		addEscapeKeyEvent();
		addTimer();
	}

	positionElement() {
		if (calledNber == 0) {
			host.style.top = '50px';
		} else if (calledNber > 0) {
			host.style.top = (90 * calledNber + 50).toString()+'px';
			print(host.style.top.toString());
		}
		calledNber++;
	}

	addCloseEvent() {
		message.query('.close').onClick.listen((e) => delete());
	}

	addEscapeKeyEvent() {
		window.onKeyDown.listen((e) {
			if (e.keyCode == 27) { // Escape
				this.delete();
			}
	  });
	}

	addTimer() {
		new Timer(msgDuration, () => delete());
	}

	delete() {
		message.remove();
		calledNber--;
	}
}