<?php


namespace Taha20\Sms;



class SmsSender
{
    private static $config ;
    protected static $username ;
    protected static $password ;
    protected static $from ;
    protected static $BASE_HTTP_URL = "https://www.payam-resan.com/";
    protected static $WEB_SERVICE_URL = "http://sms-webservice.ir/v1/v1.asmx?WSDL";

    private static function init()
    {
        if (!empty(self::$config)) return;
        self::$config = include __DIR__ . '/Config.php';
        self::$username =  self::$config['username'];
        self::$password =  self::$config['password'];
        self::$from =  self::$config['from'];
    }


    /**
     * @param $to
     * @param $message
     * @return array
     */
    public static function sendUrl($to, $message)
    {
        self::init();
        $count = array();
        $url = self::$BASE_HTTP_URL.'APISend.aspx?'."Username=".urlencode(self::$username)."&Password=".urlencode(self::$password);
        if (is_array($message) && !is_array($to)) return false;
        if (is_array($message) && count($message) != count($to)) return false;
        if (is_array($to))
        {
            $i = 0;
            $while = true;
            if (!is_array($message)) {
                $message[0] = $message;
                $while = false;
            }
            foreach ($to as $number)
            {
                $tem_url = $url . "&From=".urlencode(self::$from)."&To=".urlencode($number)."&Text=".urlencode($message[$i]);
                $count[] = file_get_contents($tem_url);
                if ($while) $i++;
            }
            return $count;
        }
        $url .= "&From=".urlencode(self::$from)."&To=".urlencode($to)."&Text=".urlencode($message);
        $count[] = file_get_contents($url);
        return $count;
    }

    /**
     * @return false|string
     */
    public static function getCreditUrl()
    {
        self::init();
        $url = self::$BASE_HTTP_URL.'Credit.aspx?'."Username=".urlencode(self::$username)."&Password=".urlencode(self::$password);
        return file_get_contents($url);
    }

    /**
     * @param $to
     * @param $message
     * @param int $type
     * @return array
     */
    public static function send($to, $message, $type = 1)
    {
        try
        {
            $client = new \SoapClient(self::$WEB_SERVICE_URL);
            self::init();
            $count = array();
            if (is_array($message) && !is_array($to)) {
                $count['error'] = 'type';
                return $count;
            }
            if (is_array($message) && count($message) != count($to)) {
                $count['error'] = 'size';
                return $count;
            }
            $parameters['Username'] = self::$username;
            $parameters['PassWord'] = self::$password;
            $parameters['SenderNumber'] = self::$from;
            $parameters['Type'] = $type;
            $parameters['AllowedDelay'] = 0;
            if (!is_array($to)) $to[0] = $to;
            $i = 0;
            $while = false;
            $to = array_chunk($to,99);
            if (is_array($message)) {
                $message = array_chunk($message,99);
                $while = true;
            }

            foreach ($to as $key => $item)
            {
                $parameters['RecipientNumbers'] = $item;
                if ($while)
                {
                    $parameters['MessageBodie'] = $message[$i];
                    $i++;
                }
                else $parameters['MessageBodie'] = $message;
                //dd($parameters);
                $res = $client->SendMessage($parameters);
                if (!is_array($res->SendMessageResult->long)) $res->SendMessageResult->long = [$res->SendMessageResult->long];
                $count = array_merge($count,$res->SendMessageResult->long);
            }
            return $count;
        }
        catch (\SoapFault $ex)
        {
            $count['error'] = 'soap';
            $count['message'] = $ex->faultstring;
            return $count;
        }
    }

    /**
     * @param $massage_id
     * @return array
     */
    public static function getMessagesStatus($massage_id)
    {
        try
        {
            $client = new \SoapClient(self::$WEB_SERVICE_URL);
            self::init();
            $count = array();
            $parameters = array();
            $parameters['Username'] = self::$username;
            $parameters['PassWord'] = self::$password;
            if (!is_array($massage_id)) $massage_id[0] = $massage_id;
            $massage_id = array_chunk($massage_id,99);
            foreach ($massage_id as $key => $item)
            {
                $parameters['messageId'] = $item;
                $res = $client->getMessagesStatus($parameters);
                if (!is_array($res->GetMessagesStatusResult->long)) $res->GetMessagesStatusResult->long = [$res->GetMessagesStatusResult->long];
                $count = array_merge($count,$res->GetMessagesStatusResult->long);
            }
            return $count;
        }
        catch (\SoapFault $ex)
        {
            $count['error'] = 'soap';
            $count['message'] = $ex->faultstring;
            return $count;
        }
    }

    /**
     * @return mixed
     */
    public static function getCredit()
    {
        try
        {
            $client = new \SoapClient(self::$WEB_SERVICE_URL);
            self::init();
            $parameters = array();
            $parameters['Username'] = self::$username;
            $parameters['PassWord'] = self::$password;
            $res = $client->GetCredit($parameters);
            return $res->GetCreditResult;
        }
        catch (\SoapFault $ex)
        {
            $count['error'] = 'soap';
            $count['message'] = $ex->faultstring;
            return $count;
        }
    }

    /**
     * @param int $numberOfMessages
     * @param string $desNumber
     * @return mixed
     */
    public static function getReceiveMessage($numberOfMessages = 99 , $desNumber = '' )
    {
        try
        {
            $client = new \SoapClient(self::$WEB_SERVICE_URL);
            self::init();
            $parameters = array();
            $parameters['Username'] = self::$username;
            $parameters['PassWord'] = self::$password;
            $parameters['destNumber'] = $desNumber;
            $parameters['numberOfMessages'] = $numberOfMessages;
            $res = $client->GetAllMessages($parameters);
            return $res->GetAllMessagesResult;
        }
        catch (\SoapFault $ex)
        {
            $count['error'] = 'soap';
            $count['message'] = $ex->faultstring;
            return $count;
        }
    }

    /**
     * @param $code
     * @return string
     */
    public static function getError($code)
    {
        $message = 'خطاي داخلي ،درخواست مجددا ارسال شود';
        switch ($code)
        {
            case -1:
                $message = 'نام کاربري يا کلمه عبور وارد شده اشتباه است';
                break;
            case -2:
                $message = 'ارسال از طريق وب سرويس براي اين کاربر غیر فعال است';
                break;
            case -3:
                $message = 'سرويس موقتا غیر فعال است';
                break;
            case -4:
                $message = 'شماره فرستنده متعلق به اين کاربر نیست';
                break;
            case -5:
                $message = 'شماره تلفن همراه گیرنده اشتباه است';
                break;
            case -6:
                $message = 'اعتبار کاربر براي ارسال کافي نیست';
                break;
            case -7:
                $message = 'آرايه گیرندگان خالي است';
                break;
            case -8:
                $message = 'تعداد شماره هاي گیرنده موجود در آرايه بیشتر از تعداد مجاز است';
                break;
            case -9:
                $message = 'شماره فرستنده اشتباه است';
                break;
            case -10:
                $message = 'آرايه شناسه پیام خالي است';
                break;
            case -11:
                $message = 'حساب کاربر مسدود است';
                break;
            case -12:
                $message = 'تلفن همراه کاربر فعال نیست . (بايد با ورود به سامانه مراحل فعال سازي را طي کنید)';
                break;
            case -13:
                $message = 'ايمیل کاربر فعال نیست . (بايد با ورود به سامانه مراحل فعال سازي را طي کنید)';
                break;
            case -14:
                $message = 'شماره اختصاصي گیرنده پیام اشتباه است';
                break;
            case -15:
                $message = 'تعداد پیامک هاي درخواستي خارج از محدوده مجاز است';
                break;
            case -16:
                $message = 'تاخیر مجاز بايد يک عدد بین 0 تا 24 باشد';
                break;
            case -18:
                $message = 'متن پیام اشتباه است';
                break;
            case -19:
                $message = 'تعداد خانه هاي آرايه متن بايد برابر 1 يا به تعداد خانه هاي آرايه دريافت کننده باشد';
                break;
            case -20:
                $message = 'تعداد خانه هاي آرايه فرستنده بايد برابر 1 يا به تعداد خانه هاي آرايه دريافت کننده باشد';
                break;
            case -21:
                $message = 'تعداد خانه هاي آرايه تاخیر مجاز بايد برابر 1 يا به تعداد خانه هاي آرايه دريافت کننده باشد';
                break;
        }
        return $message;
    }

    /**
     * @param $code
     * @return string
     */
    public static function getMessagesStatusText($code)
    {
        $message = '';
        switch ($code)
        {
            case 0:
                $message = 'دريافت شده توسط پیام رسان (در صف ارسال)';
                break;
            case 1:
                $message = 'ارسال شده – هنوز وضعیتي از مخابرات اعلام نشده است';
                break;
            case 2:
                $message = 'در صف ارسال مخابرات';
                break;
            case 3:
                $message = 'رسیده به مخابرات';
                break;
            case 4:
                $message = 'رسیده به گوشي';
                break;
            case 5:
                $message = 'نرسیده به گوشي';
                break;
            case 6:
                $message = 'نرسیده به مخابرات';
                break;
            case 7:
                $message = 'نرسیده به مخابرات – گیرنده دريافت پیامک تبلیغاتي را غیر فعال کرده است';
                break;
            case 8:
                $message = 'پیامک يافت نشد';
                break;
            case 9:
                $message = 'منقضي شده';
                break;
            case 10:
                $message = 'نا مشخص';
                break;
        }
        return $message;
    }
}
