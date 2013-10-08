part of depim_ui;

class Message {
  String type;
  Element elem;
  Duration msgDuration = new Duration(seconds:3);

  Message(this.type);

  void show(text) {
    elem = new Element.html('''
      <div class="alert alert-${type}">
        <button class="close" data-dismiss="alert">&times;</button>
        <h4 class="alert-heading">${type}</h4>
        ${text}.
      </div>''');

    addCloseEvent();

    window.onKeyDown.listen((e) {
      if (e.keyCode == 27) { // Escape
        this.delete();
      }
    });

		document.query('#msg-bloc').nodes.add(elem);
    addTimer();
  }

  addCloseEvent() {
    elem.query('.close').onClick.listen((e) => delete());
  }

  addTimer() {
    new Timer(msgDuration, () => delete());
  }

  void delete() {
    elem.remove();
  }
}