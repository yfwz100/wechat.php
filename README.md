Wechat.php
================

Wechat.php is an effort to create an elegant utility to interact with Server of [Wechat Media Platform][WechatMP], in PHP. 

Wechat.php is designed to work well with modern PHP environment, including namespaces and composer. The API is strongly influenced by [a fluent package written in Node.js][wx], written in modern PHP syntax. We hope this collection of code could benefit anyone who works with an account of [Wechat Media Platform][WechatMP].

Installation
------------

Though 'Wehcat.php' is designed to work with composer, the library is not yet uploaded and is under active construction. Please add repository to your `composer.json`:

```
"repositories": [
    {
        "type": "vcs",
	"url": "https://git.oschina.net/zhi/wechat.php.git"
    }
]
```

Then add dependency:

```
"require": {
    "yfwz100/wechat": "dev-master"
}
```

Example
-------

```
<?php include_once './vendor/autoload.php'; // autoload by composer.

use yfwz100\wechat as wechat;

wechat\Init::withToken('...YOUR TOKEN...');

$router = wechat\Router::get();

// Subscription event.
$router->event->on('subscribe', function () {
    echo wechat\Reply::text("欢迎关注施阳作，请注册以便使用更多服务。");
});

// Keyword reply.
$router->text->match('hello', function ($matches) {
    echo wechat\Reply::text("Hello, world!");
});
```

Author
------

Zhi - yfwz100@yeah.net

License
-------

MIT.

[WechatMP]: http://mp.weixin.qq.com/ "The home page of WechatMP."
[wx]: http://www.weixinjs.org "The wx module for Node.js ."

