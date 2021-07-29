<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 藁城区创新网络电子商务中心 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/以获得更多细节。
// +----------------------------------------------------------------------

define('IA_ROOT', str_replace("\\", '/', dirname(dirname(__FILE__))));

use think\facade\Db;
//返回json
function jsonErrCode($msg){
    $result = [
        'code' => 0,
        'msg' => $msg,
    ];
    echo json_encode($result);exit;
}
function jsonSucCode($msg,$data=""){
    $result = [
        'code' => 1,
        'msg' => $msg,
        'data'=>$data
    ];
    echo json_encode($result);exit;
}

/**
 * 原生sql
 */

function pdo_execute($sql){
    $mysqlHostname = env('database.hostname');
    $mysqlHostport = env('database.hostport');
    $mysqlUsername = env('database.username');
    $mysqlPassword = env('database.password');
    $dbname = env('database.database');
    try {

        $pdo = new PDO("mysql:host={$mysqlHostname};port={$mysqlHostport};dbname=$dbname", $mysqlUsername, $mysqlPassword, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ));
        // 开始事务
        $pdo->beginTransaction();
        //新增
        $result = $pdo->exec($sql);
        $pdo->commit();// 提交事务
        return 'ok';
    }catch (PDOException $e){
        $pdo->rollback ();//回滚事务
        return $e->getMessage();
    }

}

/**
 * 获取ip地址
 * @return mixed|string
 */
function getip() {
    static $ip = '';
    $ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
        foreach ($matches[0] AS $xip) {
            if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                $ip = $xip;
                break;
            }
        }
    }
    if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip)) {
        return $ip;
    } else {
        return '127.0.0.1';
    }
}
//随机32位字符串
if (!function_exists('createNoncestr')) {
    function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}

/**
 * 创建二维码
 */
function createQrcode($url){
 
    if($url){
        require  IA_ROOT.'/qrcode/phpqrcode.php';
        $errorCorrectionLevel = 'L';
        $matrixPointSize = '6';
        QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize);
        die;
    }

}

/**
 * 生成随机数
 * @param $leng 长度
 * @return bool|string
 */
function redom($leng){
    $randStr = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz'.time());
    $rand = substr($randStr,0,$leng);
    return $rand;
}
function get_media_domain($storage = null){
    if(is_null($storage)){
        $storage = getSetting("atta_type");
    }
    $storageMap = [
        '2'=>'domain',
        '3'=>'tx_domain',
        '4'=>'al_domain',
        '5'=>'ftp_domain'
    ];
    return getSetting($storageMap[$storage] ?? '','setup');
}
function media($fileUrl,$storage =null, $domain = false){
    if(substr($fileUrl,0,4) == '/app'){
        // 如果是/app斜杠开头的都是本地
        return $fileUrl;
    }
    if(substr($fileUrl,0,8) == '/upload/'){
        return '/public'.$fileUrl;
    }
    if(substr($fileUrl,0,1) == '/' && !is_numeric(substr($fileUrl,1,1))){
        // 如果是/开头，并且第二位不是数字，直接返回
       return  '/public'.$fileUrl;
    }else if(substr($fileUrl,0,1) !== '/'){
        return $fileUrl;
        // 只要不是/开头都拼接上当前地址
        $storage = getSetting("atta_type");
        $storageMap = [
            '2'=>'domain',
            '3'=>'tx_domain',
            '4'=>'al_domain',
            '5'=>'ftp_domain'
        ];
        $domainStr = getSetting($storageMap[$storage] ?? '','setup');
        return $domainStr.str_replace("//","/",'/'.$fileUrl);
    }
    // 如果是https://,http://,//开头直接返回
    if(strpos($fileUrl, "http://") !== false
        || strpos($fileUrl, "https://") !== false
        || strpos($fileUrl, "//") !== false
    ){
        return $fileUrl;
    }
    // 如果$storage 不为空
    if(!is_null($storage)){
        // 如果 $storage == 'act'则取当前默认$storage
        if($storage == 'act'){
            $storage = getSetting("atta_type");
        }
        $storageMap = [
            '2'=>'domain',
            '3'=>'tx_domain',
            '4'=>'al_domain',
            '5'=>'ftp_domain'
        ];
        $name = $storageMap[$storage] ?? '';
        if($name){
            $domainStr = getSetting($name,'setup');
            // 如果有设置domain，则返回数组
            if($domain){
                return [$domainStr,$fileUrl];
            }
            // 如果域名是/结尾直接拼接
            if(substr($domainStr,strlen($domainStr)-1,1) == '/'){
                $fileSrc = $domainStr.$fileUrl;
            }else{
                // 否则加上斜杠再拼接
                $fileSrc =  $domainStr.str_replace("//","/",'/'.$fileUrl);
            }
            return $fileSrc;
        }
    }
    // 如果是https://,http://,//开头直接返回
    if(strpos($fileUrl, "http://") !== false
        || strpos($fileUrl, "https://") !== false
        || strpos($fileUrl, "//") !== false
    ){
        return $fileUrl;
    }else{
        // 如果是/app/开头的直接返回
        if(substr($fileUrl,0,5) === '/app/'){
            return $fileUrl;
        }
        // 否则拼接绝对路径
        return ROOT_PATH.$fileUrl;
    }

}

function is_admin(){
    $uid = session('admin.id');
    if(\request()->root() === '/index'){
        if(!is_null($uid) && (int)$uid === 1){
            return true;
        }else{
            return false;
        }
    }
    // 超管无惧
    if(!is_null($uid) && (int)$uid === 1){
        return true;
    }
    $app_uid = Db::table('vit_app')
        ->where('id', \request()->get('pid'))
        ->value('uid');
    if(!is_null($uid) && !is_null($app_uid) && (int)$app_uid === (int)$uid){
        return true;
    }else{
        return false;
    }
}

/**
 * 密码加密
 * @param $pass
 * @return false|string|null
 */
function pass_en($pass){
    $options =[
        "cost"=>config('admin.cost')
    ];

    return password_hash($pass,PASSWORD_DEFAULT, $options);
}

/**
 * 密码校验
 * @param $pass
 * @param $hash
 * @return bool
 */
function pass_compare($pass, $hash){
    return password_verify($pass, $hash);
}

/**
 * 唯一日期编码
 * @param integer $size
 * @param string $prefix
 * @return string
 */
  function uniqidDate($size = 16, $prefix = '')
{
    if ($size < 14) $size = 14;
    $string = $prefix . date('Ymd') . (date('H') + date('i')) . date('s');
    while (strlen($string) < $size) $string .= rand(0, 9);
    return $string;
}

/**
 * 获取日期编码
 *
 * @return string
 */
function getTDate()
{
    $string =  date('Ymd') . (date('H'));
    return $string;
}
/**
 * 权限校验
 * @param $path
 * @return mixed
 */
function auth($path){
    return \vitphp\admin\Auth::auth($path);
}
//获取系统配置appid
function getDefConfig(){
    $config = [];

    $wx_appid =  getSetting("wx_appid");
    $wx_appsecret =  getSetting("wx_appsecret");
    $config['appid']=$wx_appid;
    $config['appsecret'] =$wx_appsecret;

    return $config;
}