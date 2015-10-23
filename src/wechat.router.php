<?php namespace yfwz100\wechat\router;

class TextHandler {

  private $handlers;

  function __construct() {
    $this->handlers = array();
  }

  function match($regexp, $callback=NULL) {
    if (!$callback) {
      $this->handlers['/.*/'] = $regexp;
    } else {
      if ($regexp[0] != '/') {
        $regexp = "/$regexp/";
      }
      $this->handlers[$regexp] = $callback;
    }
  }

  protected function textToProcess() {
    return \yfwz100\wechat\Request::get()->Content;
  }

  function __invoke() {
    $content = $this->textToProcess();
    foreach ($this->handlers as $regexp => $callback) {
      if (preg_match($regexp, $content, $matches)) {
        if (!$callback($matches)) {
          break;
        }
      }
    }
  }

}

class ClickHandler extends TextHandler {

  protected function textToProcess() {
    return \yfwz100\wechat\Request::get()->EventKey;
  }

}

class EventHandler extends \yfwz100\wechat\Router {

  function __construct() {
    $this->handlers = array(
      'click'=> new ClickHandler()
    );
  }

  function __invoke() {
    $eventType = strtolower(\yfwz100\wechat\Request::get()->Event);
    if (!empty($eventType)) {
      $handle = $this->{$eventType};
      $handle();
    }
  }

}
