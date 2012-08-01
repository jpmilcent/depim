#import('dart:core');
#import('dart:html');

#import('ui/ui.dart');

void main() {
  query("#menu-home a").on.click.add(openHomeView);
  query("#menu-warehouse a").on.click.add(openWareHouseView);

  query("#menu-home a").click();
}

void openHomeView(Event event) {
  switchMenu('#menu-home');
  var pathBinder = new PathBinder();
  var path = '';
  var html = '''
    <h1>Accueil</h1>    
    <p>
      Path : <span id="path">${path}</span>
      <button class="msg">Add msg</button>
      <button class="msg-overlay">Add msg overlay</button>
    </p>
  ''';
  addMainBlocView(html);

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

void openWareHouseView(Event event) {
  switchMenu('#menu-warehouse');
  var html = '<div><h1>Warehouse</h1></div>';
  addMainBlocView(html);
}

void addMainBlocView(mainBlocHtml) {
  var mainBlocContainer = query("#main-bloc");

  if (mainBlocContainer is Element) mainBlocContainer.remove();

  var mainBlocContainerElemnt = new Element.html("""
      <section id="main-bloc">
        ${mainBlocHtml}
      </section>
    """);

  query("#main").nodes.add(mainBlocContainerElemnt);
}

void switchMenu(id) {
  queryAll('.nav-list .active').forEach((elem) {
    elem.attributes['class'] = '';
  });
  query(id).attributes['class'] = 'active';
}
