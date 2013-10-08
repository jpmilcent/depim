import 'package:polymer/polymer.dart';
import '../ui/ui.dart';

@CustomTag('home-panel')
class Home extends PolymerElement {

	bool get applyAuthorStyles => true;

	void created() {
		super.created();

	}

  void addMessage(e) {
    var now = new DateTime.now();
    new Message('success').show('''Welcome to Dart! ${now}''');
  }

  void addMessageOverlay(e) {
    var now = new DateTime.now();
    new MessageOverlay('success').show('''<p>Welcome to Dart! ${now}</p><button>Un bouton</button>''');
  }
}