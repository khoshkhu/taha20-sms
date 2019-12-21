<?php


namespace Taha20\Sms;



use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SmsSender
{
    private $config ;
    private $db;
    protected $username ;
    protected $password ;
    protected $senderNumber ;
    protected $BASE_HTTP_URL = "https://www.payam-resan.com/";
    protected $WEB_SERVICE_URL = "http://sms-webservice.ir/v1/v1.asmx?WSDL";

    public function __construct()
    {
        $this->db = app('db');
        $this->init();
    }

    private function init()
    {
        $this->config = app('config');
        $this->username = $this->config->get('sms.payamresan.username');
        $this->password = $this->config->get('sms.payamresan.password');
        $this->senderNumber = $this->config->get('sms.payamresan.sender_number');
        $this->BASE_HTTP_URL = $this->config->get('sms.payamresan.base_http_url');
        $this->WEB_SERVICE_URL = $this->config->get('sms.payamresan.web_service_url');
       // dd($this->config);
    }

    /**
     * @param $to
     * @param $message
     * @return array
     */
    public function sendUrl($to, $message)
    {
        $count = array();
        $url = $this->BASE_HTTP_URL.'APISend.aspx?'."Username=".$this->username."&Password=".$this->password;
        if (is_array($message) && !is_array($to)) return false;
        if (is_array($message) && count($message) != count($to)) return false;
        $i = 0;
        if (is_array($to))
        {
            $while = true;
            if (!is_array($message)) {
                $message[0] = $message;
                $while = false;
            }
            foreach ($to as $number)
            {
                $tem_url = $url . "&From=".$this->senderNumber."&To=".$number."&Text=".urlencode($message[$i]);
                $count[$i]['messageId'] = file_get_contents($tem_url);
                $count[$i]['mobile'] = $number;
                $count[$i]['text'] = $message[$i];
                $count[$i]['method'] = 'url';
                $count[$i]['senderNumber'] = $this->senderNumber;
                $count[$i]['flash'] = 1;
                $count[$i]['status'] = 11;
                $count[$i]['send_at'] = Carbon::now($this->config->get('app.timezone'));
                $count[$i]['type'] = 'send';
                if ($while) $i++;
            }
            $this->save($count);
            return $count;
        }
        $url .= "&From=".$this->senderNumber."&To=".$to."&Text=".urlencode($message);
        $count['messageId'] = file_get_contents($url);
        $count['mobile'] = $to;
        $count['text'] = $message;
        $count['method'] = 'url';
        $count['senderNumber'] = $this->senderNumber;
        $count['flash'] = 1;
        $count['status'] = 11;
        $count['send_at'] = Carbon::now($this->config->get('app.timezone'));
        $count['type'] = 'send';
        $this->save($count);
        return $count;
    }

    /**
     * @return false|string
     */
    public function getCreditUrl()
    {
        $url = $this->BASE_HTTP_URL.'Credit.aspx?'."Username=".$this->username."&Password=".$this->password;
        return file_get_contents($url);
    }

    /**
     * @param $to
     * @param $message
     * @param int $type
     * @return array
     */
    public function send($to, $message, $type = 1)
    {
        try
        {
            $client = new \SoapClient($this->WEB_SERVICE_URL);
            $count = array();
            if (is_array($message) && !is_array($to)) {
                $count['error'] = 'type';
                return $count;
            }
            if (is_array($message) && count($message) != count($to)) {
                $count['error'] = 'size';
                return $count;
            }
            $parameters['Username'] = $this->username;
            $parameters['PassWord'] = $this->password;
            $parameters['SenderNumber'] = $this->senderNumber;
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
            $this->save($count);
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
    public function getMessagesStatus($massage_id)
    {
        try
        {
            $client = new \SoapClient($this->WEB_SERVICE_URL);
            $count = array();
            $parameters = array();
            $parameters['Username'] = $this->username;
            $parameters['PassWord'] = $this->password;
            if (!is_array($massage_id)) $massage_id[0] = $massage_id;
            $massage_id = array_chunk($massage_id,99);
            foreach ($massage_id as $key => $item)
            {
                $parameters['messageId'] = $item;
                $res = $client->getMessagesStatus($parameters);
                if (!is_array($res->GetMessagesStatusResult->long)) $res->GetMessagesStatusResult->long = [$res->GetMessagesStatusResult->long];
                $count = array_merge($count,$res->GetMessagesStatusResult->long);
            }
            $this->update();
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
    public function getCredit()
    {
        try
        {
            $client = new \SoapClient($this->WEB_SERVICE_URL);
            $parameters = array();
            $parameters['Username'] = $this->username;
            $parameters['PassWord'] = $this->password;
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
    public function getReceiveMessage($numberOfMessages = 99 , $desNumber = '' )
    {
        try
        {
            $client = new \SoapClient($this->WEB_SERVICE_URL);
            $parameters = array();
            $parameters['Username'] = $this->username;
            $parameters['PassWord'] = $this->password;
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

    private function getTable()
    {
        return $this->db->table('sms');
    }
    private function save($data)
    {
       $this->getTable()->insert($data);
        //Sms::create($data);
    }

    private function update()
    {

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
            case 11:
                $message = 'ارسال به سرویس پیامک';
                break;
            case 12:
                $message = 'دریافتی';
                break;
        }
        return $message;
    }
}
