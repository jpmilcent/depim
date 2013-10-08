import 'package:polymer/polymer.dart';

@CustomTag('click-counter')
class CounterComponent extends PolymerElement with ObservableMixin {
	@observable int count = 0;
	void increment(e) { count++; }
}