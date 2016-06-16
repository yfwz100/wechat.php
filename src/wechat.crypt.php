<?php namespace yfwz100\wechat\internal;

function parseRequest($postStr) {
  $postObj = simplexml_load_string($postStr);
  if (!$postObj) {
    throw new \yfwz100\wechat\InvalidException();
  }
  $encrypt = $postObj->Encrypt;
  return simplexml_load_string(
    \yfwz100\wechat\Prp::init(
      \yfwz100\wechat\Init::$wxEnKey,
      \yfwz100\wechat\Init::$wxAppId
    )->decrypt($encrypt)
  );
}

function formatReply($part) {
  $timestamp = time();

  $postObj = \yfwz100\wechat\Request::get();
  $part->addChild('ToUserName', $postObj->FromUserName);
  $part->addChild('FromUserName', $postObj->ToUserName);
  $part->addChild('CreateTime', $timestamp);
  $part->addChild('AgentID', $postObj->AgentID);
  $raw = $part->asXML();

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

