<?php
/**
 * DES加密和解密PHP，Java，ObjectC统一的方法
 * http://www.cnblogs.com/pengxl/archive/2012/03/18/2404696.html
 */
class DesComponent {
    var $key = '12345678';

    function encrypt($string) {

        $ivArray=array(0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF);
        $iv=null;
        foreach ($ivArray as $element)
            $iv.=CHR($element);


        $size = mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_CBC );
        $string = $this->pkcs5Pad ( $string, $size );

        $data =  mcrypt_encrypt(MCRYPT_DES, $this->key, $string, MCRYPT_MODE_CBC, $iv);

        $data = base64_encode($data);
        return $data;
    }

    function decrypt($string) {

        $ivArray=array(0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF);
        $iv=null;
        foreach ($ivArray as $element)
            $iv.=CHR($element);

        $string = base64_decode($string);
        //echo("****");
        //echo($string);
        //echo("****");
        $result =  mcrypt_decrypt(MCRYPT_DES, $this->key, $string, MCRYPT_MODE_CBC, $iv);
        $result = $this->pkcs5Unpad( $result );

        return $result;
    }


    function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen ( $text ) % $blocksize);
        return $text . str_repeat ( chr ( $pad ), $pad );
    }

    function pkcs5Unpad($text)
    {
        $pad = ord ( $text {strlen ( $text ) - 1} );
        if ($pad > strlen ( $text ))
            return false;
        if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
            return false;
        return substr ( $text, 0, - 1 * $pad );
    }
}

 ?>