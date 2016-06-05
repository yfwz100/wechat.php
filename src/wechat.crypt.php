<?php namespace yfwz100\wechat;

class PrpCryptException extends InvalidException {}

class EncryptAESException extends PrpCryptException {}
class DecryptAESException extends PrpCryptException {}
class IllegalBufferException extends PrpCryptException {}
class ValidateCropIdException extends PrpCryptException {}

/**
 * 字符串填充算法。
 *
 */
class PKCS7 {
  public static $block_size = 32;

  /**
   * Encode the text with padding character.
   * 
   * @param $text the text to encode.
   * @return encoded text.
   */
  public static function encode($text) {
    $block_size = static::$block_size;
    $text_length = strlen($text);
    $amount_to_pad = $block_size - $text_length % $block_size;
    if ($amount_to_pad == 0) {
      $amount_to_pad = PKCS7Encoder::block_size;
    }
    $pad_chr = chr($amount_to_pad);
    $pad = "";
    for ($i = 0; $i < $amount_to_pad; $i ++) {
      $pad .= $pad_chr;
    }
    return $text . $pad;
  }

  /**
   * Decode the text and delete the padding character.
   *
   * @param $text the text to delete.
   * @return the decoded text.
   */
  public static function decode($text) {
    $pad = ord(substr($text, -1));
    if ($pad < 1 || $pad > static::$block_size) {
      $pad = 0;
    }
    return substr($text, 0, strlen($text) - $pad);
  }

}

/**
 * 微信主要的加解密算法。
 *
 */
class Prp {

  private $key;
  private $corpId;

  private function __construct($k, $corpId) {
    $this->key = \base64_decode($k . '=');
    $this->corpId = $corpId;
  }

  public static function init($key, $corpId) {
    return new Prp($key, $corpId);
  }

  /**
   * 对明文进行加密
   *
   * @param string $text 需要加密的明文
   * @return string 加密后的密文
   */
  public function encrypt($text) {
    try {
      //获得16位随机字符串，填充到明文之前
      $random = $this->getRandomStr();
      $text = $random . pack("N", strlen($text)) . $text . $this->corpid;
      // 网络字节序
      $size = \mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
      $module = \mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
      $iv = substr($this->key, 0, 16);
      //使用自定义的填充方式对明文进行补位填充
      $text = PKCS7::encode($text);
      \mcrypt_generic_init($module, $this->key, $iv);
      //加密
      $encrypted = \mcrypt_generic($module, $text);
      \mcrypt_generic_deinit($module);
      \mcrypt_module_close($module);

      //使用BASE64对加密后的字符串进行编码
      return \base64_encode($encrypted);
    } catch (Exception $e) {
      throw new EncryptAESException();
    }
  }

  /**
   * 对密文进行解密
   *
   * @param string $encrypted 需要解密的密文
   * @return string 解密得到的明文
   */
  public function decrypt($encrypted) {
    try {
      //使用BASE64对需要解密的字符串进行解码
      $ciphertext_dec = \base64_decode($encrypted);
      $module = \mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
      $iv = substr($this->key, 0, 16);
      \mcrypt_generic_init($module, $this->key, $iv);

      //解密
      $decrypted = \mdecrypt_generic($module, $ciphertext_dec);
      \mcrypt_generic_deinit($module);
      \mcrypt_module_close($module);
    } catch (Exception $e) {
      throw new DecryptAESException();
    }

    try {
      //去除补位字符
      $result = PKCS7::decode($decrypted);
      //去除16位随机字符串,网络字节序和AppId
      if (strlen($result) < 16)
        throw new PrpCryptException();
      $content = substr($result, 16, strlen($result));
      $len_list = unpack("N", substr($content, 0, 4));
      $xml_len = $len_list[1];
      $xml_content = substr($content, 4, $xml_len);
      $from_corpid = substr($content, $xml_len + 4);
    } catch (Exception $e) {
      throw new IllegalBufferException();
    }

    if ($from_corpid != $this->corpId)
      throw new ValidateCorpidException();

    return $xml_content;
  }

  /**
   * 随机生成16位字符串
   *
   * @return string 生成的字符串
   */
  function getRandomStr() {
    $str = "";
    $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($str_pol) - 1;
    for ($i = 0; $i < 16; $i++) {
      $str .= $str_pol[mt_rand(0, $max)];
    }
    return $str;
  }

}

