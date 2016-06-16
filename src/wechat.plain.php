<?php namespace yfwz100\wechat\internal;

function parseRequest($postStr) {
  $postObj = simplexml_load_string($postStr);
  if (!$postObj) {
    throw new \yfwz100\wechat\InvalidException();
  }
  return $postObj;
}

function formatReply($part) {
  $postObj = \yfwz100\wechat\Request::get();
  $part->addChild('ToUserName', $postObj->FromUserName);
  $part->addChild('FromUserName', $postObj->ToUserName);
  $part->addChild('CreateTime', time());
  return $part->asXML();
}

