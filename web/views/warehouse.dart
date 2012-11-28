import 'package:web_components/web_components.dart';
import 'dart:html';
import 'dart:json';
import 'dart:uri';
import '../ui/ui.dart';

class Warehouse extends WebComponent {

  void addWarehouse(e) {
    var dataUrl = 'http://localhost/dart/depim/server/services/0.1/structure',
      nom = query('input[name="nom"]').value,
      type = query('input[name="type"]').value,
      code = query('input[name="code"]').value,
      meta = {
        'utilisateurId' : 1,
        'tags' : {
          'etat' : 'A',
          'type' : 'structure',
          'commentaire' : 'Ajout d\'une structure.',
          'source' : 'http://fr.geneawiki.com/index.php/Archives_d%C3%A9partementales_de_l%27H%C3%A9rault'
        }
      },
      tags = {
        'nom': nom,
        'type': type,
        'code': code
      },
      data = {'meta' : meta, 'tags': tags},
      encodedData = JSON.stringify(data);

    var httpRequest = new HttpRequest();
    httpRequest.open('POST', dataUrl);
    httpRequest.setRequestHeader('Content-type', 'application/json');
    httpRequest.on.loadEnd.add((e) => loadEnd(httpRequest));
    print(encodedData);
    httpRequest.send(encodedData);
  }

  void loadEnd(HttpRequest request) {
    if (request.status != 200) {
      new Message('success').show('Uh oh, there was an error of ${request.status}');
      return;
    } else {
      new Message('success').show('Data has been posted. ${request.responseText}');
    }
  }
}
