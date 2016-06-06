<?php namespace yfwz100\wechat\internal;

/**
 * Crypto XML-based Request implementation.
 */
class Request extends \yfwz100\wechat\Request {

  public function __construct() {
    $postStr = file_get_contents('php://input');
    if (!empty($postStr)) {
      $postObj = simplexml_load_string($postStr);
      if (!$postObj) {
        throw new \yfwz100\wechat\InvalidException();
      }
      $encrypt = $postObj->Encrypt;
      $this->postObj = simplexml_load_string(
        \yfwz100\wechat\Prp::init(
          \yfwz100\wechat\Init::$wxEnKey,
          \yfwz100\wechat\Init::$wxAppId
        )->decrypt($encrypt)
      );
    } else {
      throw new \yfwz100\wechat\InvalidException();
    }
  }

}

/**
 * Crypto-XML-based Reply implementation.
 */
class Reply extends \yfwz100\wechat\Reply {

  private $part;

  public function __construct($part) {
    $this->part = $part;
  }

  public function __toString() {
    $timestamp = time();

    $postObj = Request::get();
    $this->part->addChild('ToUserName', $postObj->FromUserName);
    $this->part->addChild('FromUserName', $postObj->ToUserName);
    $this->part->addChild('CreateTime', $timestamp);
    $this->part->addChild('AgentID', $postObj->AgentID);
    $raw = $this->part->asXML();

    $encrypt = \yfwz100\wechat\Prp::init(
      \yfwz100\wechat\Init::$wxEnKey,
      \yfwz100\wechat\Init::$wxAppId
    )->encrypt($raw);

    $nonce = $_GET['nonce'];

    $signature = \yfwz100\wechat\wxSHA1(
      \yfwz100\wechat\Init::$wxToken,
      $timestamp,
      $nonce,
      $encrypt
    );

    $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";

    return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
  }

}

