<?php

use SwoStar\Console\Input;

use SwoStar\Foundation\Application;

if (!function_exists('app')) {
    /**
     * 六星教育 @shineyork老师
     * @param  [type] $a [description]
     * @return Application
     */
    function app($a = null)
    {
        if (empty($a)) {
            return Application::getInstance();
        }
        return Application::getInstance()->make($a);
    }
}
if (!function_exists('dd')) {
    /**
     * 六星教育 @shineyork老师
     * @param  [type] $a [description]
     * @return Application
     */
    function dd($message, $description = null)
    {
        Input::info($message, $description);
    }
}
/**
 * 获取实例
 * @param $class
 * @return mixed
 */
function get_instance($class){
    return ($class)::get_instance();
}
/**
 * 获取配置参数
 * @param      $name        参数名 格式：文件名.参数名
 * @param null $default     错误默认返回值
 *
 * @return mixed|null
 */
function config($name,$default = NULL){
    return get_instance('\Six\Lib\Config')->get($name,$default);
}

/**
 * 写入日志
 * @param       $type       EMERGENCY,ALERT,CRITICAL,ERROR,WARNING,NOTICE,INFO,DEBUG
 * @param array ...$log     标量参数，可多个
 */
/*function logs($type,...$log){
    get_instance('\Piz\Log')->write($type,...$log);
}*/

function createUUID(){
    return str_replace('-','', uuid_create(1));
}
//毫秒
function getMillisecond(){
    list($msec, $sec) = explode(' ', microtime());
    $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectimes = substr($msectime,0,13);
}

/*
 * 判断json类型
 * */
function is_json($data = '', $assoc = false) {
    $data = json_decode($data, $assoc);
    if (($data && (is_object($data))) || (is_array($data) && !empty($data))) {
        return true;
    }
    return false;
}
