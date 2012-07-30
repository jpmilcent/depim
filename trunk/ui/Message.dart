class Message {
  String type;
  Element elem;

  Message(this.type);

  void show(text) {
    this.elem = new Element.html('''
      <div class="alert alert-${type}">
        <button class="close" data-dismiss="alert">×</button>
        <h4 class="alert-heading">${type}</h4>
        ${text}.
      </div>''');

    elem.query('.close').on.click.add((e) {
      this.elem.remove();
    });

    query("#msg-bloc").nodes.add(elem);
  }
}