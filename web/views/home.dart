import 'package:web_components/web_components.dart';
import '../ui/ui.dart';

class Home extends WebComponent {

  void addMessage(e) {
    var now = new Date.now();
    new Message('success').show('''Welcome to Dart! ${now}''');
  }

  void addMessageOverlay(e) {
    var now = new Date.now();
    new MessageOverlay('success').show('''<p>Welcome to Dart! ${now}</p><button>Un bouton</button>''');
  }
}