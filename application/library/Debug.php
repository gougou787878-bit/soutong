<?php
/**
 * Sample file comment
 *
 * PHP version 7.1.0
 *
 * This file demonstrates the rich information that can be included in
 * in-code documentation through DocBlocks and tags.
 *
 * @file Debug.php
 * @author xiongba
 * @version 1.0
 * @package
 */

class Debug
{

    public static function getHttpContext()
    {
        $error = '';
        if (PHP_SAPI != "cli") {
            $error .= "\r\nEnvironment[Path]: " . $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                $error .= "\r\nEnvironment[Referer]: : " . $_SERVER['HTTP_REFERER'] ?? '';
            }
            $error .= "\r\nEnvironment[Protocol]: : " . $_SERVER['SERVER_PROTOCOL']??'';
            $error .= "\r\nEnvironment[UserAgent]: : " . ($_SERVER['HTTP_USER_AGENT'] ??'');
            $error .= "\r\nEnvironment[Method]: : " . $_SERVER['REQUEST_METHOD'] ??'get';
            $error .= "\r\nEnvironment[Post]: : " . json_encode($_POST);
            $error .= "\r\nEnvironment[Host]: " . ($_SERVER['HTTP_HOST'] ?? '');
            $error .= "\r\nEnvironment[Request_time]: " . $_SERVER['REQUEST_TIME'] ?? time();
            $error .= "\r\nEnvironment[Remote_addr]: " . $_SERVER['REMOTE_ADDR'] ?? '';
            $error .= "\r\n";
        }
        return $error;
    }
}