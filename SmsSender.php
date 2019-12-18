<?php


namespace Taha20\Sms;


class SmsSender
{
    private static $config ;
    protected static $username ;
    protected static $password ;
    protected static $from ;
    protected static $BASE_HTTP_URL = "https://www.payam-resan.com/APISend.aspx?";
    public function __construct()
    {
        self::init();
    }
    private static function init()
    {
        self::$config = include __DIR__ . '/Config.php';
        self::$username =  self::$config['username'];
        self::$password =  self::$config['password'];
        self::$from =  self::$config['from'];
    }
    public static function send($to,$message)
    {
        self::init();
        $url = self::$BASE_HTTP_URL."Username=".self::$username."&Password=".self::$password."&From=".self::$from."&To=".$to."&Text=".$message;
        /*$ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;*/
        return file_get_contents($url);

    }
}
