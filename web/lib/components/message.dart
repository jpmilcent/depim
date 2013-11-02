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

	enteredView() {
		super.enteredView();

		message = $['message'];
		print('Msg:${calledNber.toString()}');
		positionElement();
		addCloseEvent();
		addEscapeKeyEvent();
		addTimer();
	}

	positionElement() {
		if (calledNber == 0) {
			style.top = '50px';
		} else if (calledNber > 0) {
			style.top = (90 * calledNber + 50).toString()+'px';
			print(style.top.toString());
		}
		calledNber++;
	}

	addCloseEvent() {
		message.querySelector('.close').onClick.listen((e) => delete());
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