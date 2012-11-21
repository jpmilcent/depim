part of depim_ui;

class MessageOverlay {
  var type;
  var elem;
  var backdrop;

  MessageOverlay(this.type);

  void show(text) {
    elem = new Element.html('''
      <div id="msg-overlay" class="modal hide fade" role="dialog" 
        aria-labelledby="msgDialogHeader" aria-hidden="true">

        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h3 id="msgDialogHeader">${type}</h3>
        </div>

        <div class="modal-body">
          ${text}
        </div>
        
      </div>''');

    elem.query('.close').on.click.add((e) {
      this.hide();
    });

    window.on.keyPress.add((e) {
      if (e.keyCode == 27) // Escape
        this.hide();
    });

    addBackdrop();
    displayMsg();
  }

  void displayMsg() {
    query('#dialog-overlay').nodes.add(elem);

    var msg = query('#msg-overlay');
    msg.attributes['style'] = 'display:block';
    msg.classes.add('in');
    msg.attributes['aria-hidden'] = 'false';
  }

  void addBackdrop() {
    backdrop = new Element.tag('div');
    backdrop.attributes['class'] = 'modal-backdrop fade in';
    document.body.nodes.add(backdrop);
  }

  void hide() {
    removeBackdrop();
    query('#dialog-overlay #msg-overlay').remove();
  }

  void removeBackdrop() {
    backdrop.remove();
  }

}