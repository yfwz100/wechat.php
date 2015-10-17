<?php namespace yfwz100\wechat;

class Exception extends \Exception {}

class InvalidException extends Exception {}

function init($token) {
  $signature = $_GET['signature'];
  $timestamp = $_GET['timestamp'];
  $nonce = $_GET['nonce'];
  $tmpArr = array($token, $timestamp, $nonce);
  sort($tmpArr, SORT_STRING);
  $tmpStr = sha1(implode($tmpArr));
  if ($tmpStr == $signature) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      echo $_GET['echostr'];
      exit();
    }
  } else {
    throw new InvalidException();
  }
}

class XMLElement extends \SimpleXMLElement {
  private function addCData($text) {
    $node = dom_import_simplexml($this);
    $no =  $node->ownerDocument;
    $node->appendChild($no->createCDATASection($text));
  }
  function addChildCData($name, $text) {
    $child = $this->addChild($name);
    $child->addCData($text);
    return $child;
  }
}

class Request {
  private static $request;

  private $postObj;

  function __construct() {
    $postStr = file_get_contents('php://input');
    if (!empty($postStr)) {
      $this->postObj = simplexml_load_string($postStr);
      if (!$this->postObj) {
        throw new InvalidException();
      }
    }
  }

  function __get($key) {
    return $this->postObj->{$key};
  }

  static function get() {
    if (!static::$request) {
      static::$request = new Request();
    }
    return static::$request;
  }
}

class Reply {

  private $part;

  protected function __construct($part) {
    $this->part = $part;
  }

  function __toString() {
    $postObj = Request::get();
    $this->part->addChild('ToUserName', $postObj->FromUserName);
    $this->part->addChild('FromUserName', $postObj->ToUserName);
    $this->part->addChild('CreateTime', time());
    return $this->part->asXML();
  }
  
  static function text($content) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'text');
    $post->addChildCData('Content', $content);
    return new Reply($post);
  }

  static function news($news) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'news');
    $post->addChild('ArticleCount', count($news));
    $articles = $post->addChild('Articles');
    foreach($news as $item) {
      $it = $articles->addChild('item');
      $it->addChildCData('Title', $item['Title']);
      $it->addChildCData('Description', $item['Description']);
      $it->addChildCData('PicUrl', $item['PicUrl']);
      $it->addChildCData('Url', $item['Url']);
    }
    return new Reply($post);
  }

  static function ok() {
    return "success";
  }

}

class Router {
  private static $router;

  private $textHandlers;

  function __construct() {
    $this->textHandlers = array();
  }

  function match($regexp, $callback=NULL) {
    if (!$callback) {
      $this->textHandlers['/.*/'] = $regexp;
    } else {
      if ($regexp[0] != '/') {
        $regexp = "/$regexp/";
      }
      $this->textHandlers[$regexp] = $callback;
    }
  }

  protected function processText($postObj) {
    $content = $postObj->Content;
    foreach($this->textHandlers as $regexp => $handler) {
      if (preg_match($regexp, $content, $matches)) {
        if (!$handler($matches)) {
          break;
        }
      }
    }
  }

  function __call($method, $args) {
  }

  function run() {
    $postObj = Request::get();
    $method = sprintf("process%s", ucwords($postObj->MsgType));
    $this->{$method}($postObj);
  }

  static function get() {
    if (!static::$router) {
      static::$router = new Router();
    }
    return static::$router;
  }
}

