part of depim_ui;

class PathBinder {
  final PATH = new RegExp(r'^[^#]*(#.+)$');
  final PATH_REPLACER = new RegExp(r'([^\/]+)');
  final PATH_NAME_MATCHER = new RegExp(r'/:([\w\d]+)/g');
  final QUERY_STRING_MATCHER = new RegExp(r'/\?([^#]*)$/');
  final SPLAT_MATCHER = new RegExp(r'/(\*)/');
  final SPLAT_REPLACER = new RegExp(r'(.+)');
  var _currentPath;
  var _lastPath;
  var _pathInterval;

  PathBinder() {
    this._lastPath = '';
  }

  hashChanged() {
    _currentPath = getPath();
      // if path is actually changed from what we thought it was, then react
    if (_lastPath != _currentPath) {
      _lastPath = _currentPath;
      //return triggerOnPath(_currentPath);
      return _currentPath;
    }
  }

  getPath() {
    var uri = window.location.toString();
    print(PATH.firstMatch(uri).group(1));
    return PATH.hasMatch(uri) ? PATH.firstMatch(uri).group(1) : '';
  }

}