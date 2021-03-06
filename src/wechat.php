<?php namespace yfwz100\wechat;

require_once dirname(__FILE__) . '/wechat.router.php';
require_once dirname(__FILE__) . '/wechat.util.php';

class Exception extends \Exception {}

class InvalidException extends Exception {}

abstract class Init {

  static $wxAppId;
  static $wxToken;
  static $wxEnKey;
  static $wxSignature;

  static function withToken($token) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $signature = $_GET['signature'];
      $timestamp = $_GET['timestamp'];
      $nonce = $_GET['nonce'];

      $tmpArr = array($token, $timestamp, $nonce);
      sort($tmpArr, SORT_STRING);
      $tmpStr = sha1(implode($tmpArr));

      if ($tmpStr == $signature) {
        exit($_GET['echostr']);
      } else {
        throw new InvalidException();
      }
    }
    require_once dirname(__FILE__) . '/wechat.plain.php';
  }

  static function withCorp($corpId, $token, $encodingKey) {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $msg_signature = $_GET['msg_signature'];
      $timestamp = $_GET['timestamp'];
      $nonce = $_GET['nonce'];
      $echoStr = $_GET['echostr'];
      
      $signature = wxSHA1($token, $timestamp, $nonce, $echoStr);
      if ($signature != $msg_signature) {
        exit();
      }

      $result = Prp::init($encodingKey, $corpId)->decrypt($echoStr);

      exit($result);
    }

    static::$wxAppId = $corpId;
    static::$wxToken = $token;
    static::$wxEnKey = $encodingKey;

    require_once dirname(__FILE__) . '/wechat.crypt.php';
  }

}

class Request {
  protected static $request;

  protected $postObj;

  protected function __construct() {
    $postStr = file_get_contents('php://input');
    if (!empty($postStr)) {
      $this->postObj = internal\parseRequest($postStr);
    } else {
      throw new InvalidException();
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
  
  public function __toString() {
    return internal\formatReply($this->part);
  }

  static function text($content) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'text');
    $post->addChildCData('Content', $content);
    return new Reply($post);
  }

  static function image($mediaId) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'image');
    $post->addChild('Image')->addChildCData('MediaId', $mediaId);
    return new Reply($post);
  }

  static function voice($mediaId) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'voice');
    $post->addChild('Voice')->addChildCData('MediaId', $mediaId);
    return new Reply($post);
  }

  static function video($mediaId, $title, $description) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'video');
    $videoInfo = $post->addChild('Video');
    $videoInfo->addChildCData('MediaId', $mediaId);
    $videoInfo->addChildCData('Title', $title);
    $videoInfo->addChildCData('Description', $description);
    return new Reply($post);
  }

  static function music($title, $description, $musicUrl, $hqMusicUrl, $mediaId) {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'music');
    $musicInfo = $post->addChild('Music');
    $musicInfo->addChildCData('Title', $title);
    $musicInfo->addChildCData('Description', $description);
    $musicInfo->addChildCData('MusicUrl', $musicUrl);
    $musicInfo->addChildCData('HQMusicUrl', $hqMusicUrl);
    $musicInfo->addChildCData('ThumbMediaId', $mediaId);
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

  static function transfer() {
    $post = new XMLElement('<xml/>');
    $post->addChild('MsgType', 'transfer_customer_service');
    return new Reply($post);
  }

  static function ok() {
    return "success";
  }

}

class Router {
  private static $router;

  protected $handlers;
  private $userHandlers;

  function __construct() {
    $this->handlers = array(
      'text'=> new router\TextHandler(),
      'event'=> new router\EventHandler()
    );
  }

  function on($msgType, $handler) {
    $this->userHandlers[$msgType] = $handler;
  }

  function __get($name) {
    if (array_key_exists($name, $this->handlers)) {
      return $this->handlers[$name];
    } else if (array_key_exists($name, $this->userHandlers)) {
      return $this->userHandlers[$name];
    }
  }

  function __invoke() {
    $postObj = Request::get();
    $msgType = strtolower($postObj->MsgType);
    if (!empty($msgType)) {
      $handle = $this->{$msgType};
      $handle();
    } else {
      throw new InvalidException();
    }
  }

  static function get() {
    if (!static::$router) {
      static::$router = new Router();
    }
    return static::$router;
  }
}

