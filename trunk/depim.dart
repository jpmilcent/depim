#import('dart:core');
#import('dart:html');

#import('ui/ui.dart');

void main() {
  var pathBinder = new PathBinder();
  var path = '';
  var elem = new Element.html('''
    <p>
      Path : <span id="path">${path}</span>
      <button class="msg">Add msg</button>
      <button class="msg-overlay">Add msg overlay</button>
    </p>
  ''');

  query("#main-bloc").nodes.add(elem);
  query('button.msg').on.click.add((e) {
    var now = new Date.now();
    var msg = new Message('success').show('''Welcome to Dart! ${now}''');
    query('#path').text = pathBinder.getPath();
  });
  query('button.msg-overlay').on.click.add((e) {
    var now = new Date.now();
    var msg = new MessageOverlay('success').show('''<p>Welcome to Dart! ${now}</p><button>Un bouton</button>''');
  });
}