<?php

require "AipOcr.php";
$config = require 'conf.php';
class bon
{
    private $user;
    private $pass;
    private $ocr;
    
    private $rh;
    private $ch;

    public function __construct($user, $pass, $bceAppid, $apiKey, $secretKey)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->rh = curl_init();
        $this->rh = curl_init();
        $this->ocr = new AipOcr($bceAppid, $apiKey, $secretKey);
        if($this->testOcr())
        {
            echo date('Y-m-d H:i:s') . "\tOCR Start OK\r\n";
            $this->startLogin();
            $code = $this->getCaptcha();
            while($code == false){
                $code = $this->getCaptcha();
            }
            echo  date('Y-m-d H:i:s'). "\tcaptcha Test ". $code. "\r\n";
            while(true){
                $m = date('i');
                $s = date('s');
                if(($m == '59' && $s >29) || ($m == '00' && $s <40)){
                    echo date('Y-m-d H:i:s') . "\tStart get Code\r\n";
                    $this->getCode();
                    continue;
                }
                if($s%30 == 0){
                    $userInfo = $this->keepLogin();
                    if(@$userInfo['ret']['email'] ==  $this->user){
                        echo date('Y-m-d H:i:s') . "\tKeep Login {$userInfo['ret']['email']}" . $userInfo['message'] . "\r\n";
                    }else{
                        echo date('Y-m-d H:i:s') . "\tKeep Login Error " . $userInfo['message'] . "\r\n";
                        $this->startLogin();
                        $code = $this->getCaptcha();
                        while($code == false){
                            $code = $this->getCaptcha();
                        }
                        echo  date('Y-m-d H:i:s') . "\tcaptcha Test " . $code . "\r\n";
                    }
                    sleep(1);
                }
                
            }
        }
        else
        {
            echo date('Y-m-d H:i:s') . "\tOCR Start FILED\r\n";
        }
    }

    private function getCode(){
        $cookie_file = __DIR__ .DIRECTORY_SEPARATOR .'cookies.txt';
        $code = $this->getCaptcha();
        while($code == false){
            $code = $this->getCaptcha();
        }
        echo date('Y-m-d H:i:s') . "\tCaptcha is {$code}\r\n";
        $data['captcha'] = $code;
        curl_setopt_array($this->rh, [
            CURLOPT_HTTPHEADER => array("content-type: application/json"),
            CURLOPT_URL => "https://console.bonuscloud.io/api/bcode/get/",
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.92 Safari/537.36",
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);
        $data = curl_exec($this->rh);
        $data = json_decode($data, true);
        if(@$data['code'] == 200){
            echo date('Y-m-d H:i:s') . "\t Get Code Ok" . "\r\n";
            exit;
            return true;
        }
        echo date('Y-m-d H:i:s') . "\t" .$data['message'] . "\r\n";
        return false;
    }

    private function testOcr(){
        $data = $this->ocr->basicAccurate(file_get_contents('./download.png'));
        if(str_replace(' ','',$data['words_result'][0]['words']) == '88HRYW')
        {
            return true;
        }
        return false;
    }

    private function keepLogin(){
        $cookie_file = __DIR__ .DIRECTORY_SEPARATOR .'cookies.txt';
        curl_setopt_array($this->rh, [
            CURLOPT_URL => "https://console.bonuscloud.io/api/user/get_user_info/",
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.92 Safari/537.36",
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        $data = curl_exec($this->rh);
        $data = json_decode($data, true);
        return $data;
    }
    
    private function getCaptcha(){
        $cookie_file = __DIR__ .DIRECTORY_SEPARATOR .'cookies.txt';
        curl_setopt_array($this->rh, [
            CURLOPT_URL => "https://console.bonuscloud.io/api/web/captcha/get/",
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.92 Safari/537.36",
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        $data = curl_exec($this->rh);
        file_put_contents('captcha.png' ,$data);
        $code = $this->ocr->basicGeneral(file_get_contents('./captcha.png'));
        $code = str_replace(' ','',$code['words_result'][0]['words']);
        if(!preg_match("/^[A-Za-z0-9]+$/",$code)){
            return false;
        }
        return $code;
    }

    private function startLogin(){
        $cookie_file = __DIR__ .DIRECTORY_SEPARATOR .'cookies.txt';
        $user = [];
        $user['email'] = $this->user;
        $user['password'] = $this->pass;
        curl_setopt_array($this->rh, [
            CURLOPT_HTTPHEADER => array("content-type: application/json"),
            CURLOPT_URL => "https://console.bonuscloud.io/api/user/login/",
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.92 Safari/537.36",
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($user),
        ]);
        $data = curl_exec($this->rh);
        $data = json_decode($data, true);
        if(@$data['code'] == 200){
            echo date('Y-m-d H:i:s') . "\t" .$data['message'] . "\r\n";
            return true;
        }
        echo date('Y-m-d H:i:s') . "\t" .$data['message'] . "\r\n";
        return false;
    }
}
new bon($config['email'],$config['password'],$config['appid'], $config['apiKey'], $config['secretKey']);