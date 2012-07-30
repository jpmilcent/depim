class MessageOverlay {
  String type;

  MessageOverlay(this.type);

  void show(text) {
    var elem = new Element.html('''
      <div id="dialog-bloc" class="alert alert-${type}">
        <button class="close" data-dismiss="alert">×</button>
        <h4 class="alert-heading">${type}</h4>
        ${text}.
      </div>''');

    elem.query('.close').on.click.add((e) {
      query("#dialog-overlay #dialog-bloc").remove();
      query("#dialog-overlay").attributes['style'] = 'display:none';
    });

    query("#dialog-overlay").nodes.add(elem);
    query("#dialog-overlay").attributes['style'] = 'display:block';
  }
}