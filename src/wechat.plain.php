<?php namespace yfwz100\wechat\internal;

class Request extends \yfwz100\wechat\Request {

  public function __construct() {
    $postStr = file_get_contents('php://input');
    if (!empty($postStr)) {
      $this->postObj = simplexml_load_string($postStr);
      if (!$this->postObj) {
        throw new InvalidException();
      }
    }
  }

}

class Reply extends \yfwz100\wechat\Reply {

  private $part;

  function __construct($part) {
    $this->part = $part;
  }

  function __toString() {
    $postObj = Request::get();
    $this->part->addChild('ToUserName', $postObj->FromUserName);
    $this->part->addChild('FromUserName', $postObj->ToUserName);
    $this->part->addChild('CreateTime', time());
    return $this->part->asXML();
  }

}

