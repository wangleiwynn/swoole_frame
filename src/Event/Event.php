<?php
namespace SwoStar\Event;

class Event
{
    public static $events = [];
    //事件注册
    /*
     * $event 事件名
     * $callback 事件回调
     */
    public static function register($event, $callback)
    {
        $event = strtolower($event); //不区分大小写

        if (!isset(self::$events[$event])) {
            self::$events[$event] = [];
        }
        self::$events[$event] = ['callback' => $callback];
    }

    //事件的触发
    public static function trigger($event, $params = [])
    {
        $event = strtolower($event);
        if (isset(self::$events[$event])) {
            call_user_func(self::$events[$event]['callback'], $params);
            return true;
        }
        return false;
    }
     /**
     * 收集事件
     */
    public static function collectEvent(){
        $files = glob(EVENT_PATH."/*.php");
        if (!empty($files)) {
            foreach ($files as $dir => $fileName) {
                include $fileName;
                $fileName=explode('/',$fileName);
                $className=explode('.',end($fileName))[0];
                $nameSpace='App\\Listener\\'.$className;
                if(class_exists($nameSpace)){
                    $obj=new $nameSpace;
                    //希望得到自己定义的事件名称,通过反射读取类当中的文档注释
                    $re=new \ReflectionClass($obj);
                    $str=$re->getDocComment();

                    if(strlen($str)<2){
                            throw  new Exception("没有按照规则定义事件名称");
                    }else{
                        preg_match("/@Listener\((.*)\)/i",$str,$eventName);
                        if(empty($eventName)){
                            throw  new Exception("没有按照规则定义事件名称");
                        }
                        self::register($eventName[1],[$obj,'handle']);
                    }
                }
            }

        }
    }
}
