<?php
namespace SwoStar\Routes;

class Route
{
    protected static $instance = null;
    // 路由本质实现是会有一个容器在存储解析之后的路由
    protected $routes = [];
    // 定义了访问的类型
    protected $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    // 记录路由的文件地址
    protected $routeMap = [];

    protected function __construct( )
    {
        $this->routeMap = [
            'Http' => app()->getBasePath().'/route/http.php',
        ];
    }

    public static function getInstance()
    {
        if (\is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance ;
    }

    public function get($uri, $action)
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function any($uri, $action)
    {
        return $this->addRoute(self::$verbs, $uri, $action);
    }
    /**
     * 注册路由
     * 六星教育 @shineyork老师
     * @param [type] $methods [description]
     * @param [type] $uri     [description]
     * @param [type] $action  [description]
     */
    protected function addRoute($methods, $uri, $action)
    {
        foreach ($methods as $method ) {
            $this->routes[$method][$uri] = $action;
        }
        return $this;
    }
    /**
     * 根据请求校验路由，并执行方法
     * 六星教育 @shineyork老师
     * @return [type] [description]
     */
    public function match()
    {
        // 作业 1. 实现校验

        /*
        本质就是一个字符串的比对


        1. 获取请求的uripath
        2. 根据类型获取路由
        3. 根据请求的uri 匹配 相应的路由；并返回action
        4. 判断执行的方法的类型是控制器还是闭包
           4.1 执行闭包
           4.2 执行控制器


         */

        // 作业 2. websocket怎么接入 扩展
        //
        // 要求就是websocket的回调时间怎么运用到不同控制器中
        // websocket 配合与路由
    }

    public function registerRoute()
    {
        foreach ($this->routeMap as $key => $path) {
            require_once $path;
        }
        return $this;
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
