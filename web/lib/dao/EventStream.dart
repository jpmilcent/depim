import 'dart:async';

class EventStream<T> {
  var _controller = new StreamController<T>.broadcast();

  StreamController<T> get controller => _controller;
  Stream<T> get stream => controller.stream;

  signal(T value) {
    controller.add(value);
  }
}