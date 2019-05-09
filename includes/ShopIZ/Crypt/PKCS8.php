<?php

namespace ShopIZ\Crypt;

/**
 * AES/ECB/PKCS7Padding
 * DES/ECB/PKCS7Padding
 *
 * @package ShopIZ\Crypt
 * @link http://www.shopiz.cn/
 * @author ShopIZ TEAM Jacky <myself.fervor@gmail.com>
 * @copyright 2014-2018
 */
class PKCS8 {
    /**
     * AES
     * @var string
     */
     protected $cipher = MCRYPT_RIJNDAEL_128;
//    /**
//     * DES
//     * @var string
//     */
//    protected $cipher = MCRYPT_DES;
    protected $mode = MCRYPT_MODE_ECB;
    protected $pad_method = NULL;
    protected $secret_key = '';
    protected $iv = '';

    public function __construct($key)
    {
        $this->setKey($key);
    }

    public function setCipher($cipher) {
        $this->cipher = $cipher;
    }
    
    public function setMode($mode) {
        $this->mode = $mode;
    }
    
    public function setIv($iv) {
        $this->iv = $iv;
    }
    
    public function setKey($key) {
        $this->secret_key = $key;
    }

    /**
     * PKCS补齐
     *
     * @param $text
     * @return string
     */
    public function pad($text)
    {
        $len        = strlen($text);
        $block_size = mcrypt_get_block_size($this->cipher, $this->mode);
        $pad        = $block_size - (($len + 4) % $block_size);
        $text       = pack("N", $len) . $text;
        $text      .= str_repeat(chr(0), $pad);
        return $text;
    }


    protected function unpad($text)
    {
        list(, $len) = unpack("N", substr($text, 0, 4));
        $text = substr($text, 4, $len);

        return $text;
    }

    /**
     * 加密
     *
     * @param $str
     * @return string
     */
    public function encrypt($str)
    {
        $str = $this->pad($str);
        $td = mcrypt_module_open($this->cipher, '', $this->mode, '');
        
        if (empty($this->iv)) {
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        } else {
            $iv = $this->iv;
        }
        
        mcrypt_generic_init($td, $this->secret_key, $iv);
        $crypt_text = mcrypt_generic($td, $str);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $crypt_text;
    }

    /**
     * 解密
     *
     * @param $str
     * @return bool|string
     */
    public function decrypt($str)
    {
        $td = mcrypt_module_open($this->cipher, '', $this->mode, '');
        
        if (empty($this->iv)) {
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        } else {
            $iv = $this->iv;
        }
        mcrypt_generic_init($td, $this->secret_key, $iv);
        $decrypted_text = mdecrypt_generic($td, $str);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $this->unpad($decrypted_text);
    }

    /**
     * @param $str
     * @return string
     */
    public function generate_text($str)
    {
        $str = $this->encrypt($str);
        return base64_encode($str);
    }
}