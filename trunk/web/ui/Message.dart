part of depim_ui;

class Message {
  String type;
  Element elem;
  var msgDuration = 3000;

  Message(this.type);

  void show(text) {
    elem = new Element.html('''
      <div class="alert alert-${type}">
        <button class="close" data-dismiss="alert">&times;</button>
        <h4 class="alert-heading">${type}</h4>
        ${text}.
      </div>''');

    addCloseEvent();
    query('#msg-bloc').nodes.add(elem);
    addTimer();
  }

  addCloseEvent() {
    elem.query('.close').on.click.add((e) => delete());
  }

  addTimer() {
    new Timer(msgDuration, (Timer t) {
      delete();
    });
  }

  delete() {
    elem.remove();
  }
}