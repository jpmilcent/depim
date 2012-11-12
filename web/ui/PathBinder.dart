part of depim_ui;

class PathBinder {
  var PATH = const RegExp(r'^[^#]*(#.+)$');
  var PATH_REPLACER = const RegExp(r'([^\/]+)');
  var PATH_NAME_MATCHER = const RegExp(r'/:([\w\d]+)/g');
  var QUERY_STRING_MATCHER = const RegExp(r'/\?([^#]*)$/');
  var SPLAT_MATCHER = const RegExp(r'/(\*)/');
  var SPLAT_REPLACER = const RegExp(r'(.+)');
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