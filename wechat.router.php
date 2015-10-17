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

  protected function textToProcess($postObj) {
    return $postObj->Content;
  }

  function __invoke($postObj) {
    $content = $postObj->Content;
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

  protected function textToProcess($postObj) {
    return $postObj->EventKey;
  }

}

class EventHandler extends \yfwz100\wechat\Router {

  function __construct() {
    $this->handlers = array(
      click=> new ClickHandler()
    );
  }

  function __invoke($postObj) {
    $eventType = strtolower($postObj->Event);
    $this->{$eventType}($postObj);
  }

}
