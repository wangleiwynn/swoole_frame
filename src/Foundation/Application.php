<?php
namespace SwoStar\Foundation;

use SwoStar\Container\Container;
use SwoStar\Routes\Route;

use SwoStar\Server\Http\HttpServer;
use SwoStar\Server\WebSocket\WebSocketServer;

class Application extends Container
{
    protected const SWOSTAR_WELCOME = "
      _____                     _____     ___
     /  __/             ____   /  __/  __/  /__   ___ __    __  __
     \__ \  | | /| / / / __ \  \__ \  /_   ___/  /  _`  |  |  \/ /
     __/ /  | |/ |/ / / /_/ /  __/ /   /  /_    |  (_|  |  |   _/
    /___/   |__/\__/  \____/  /___/    \___/     \___/\_|  |__|
    ";

    protected $basePath = "";

    public function __construct($path = null)
    {
        if (!empty($path)) {
            $this->setBasePath($path);
        }
        $this->registerBaseBindings();
//        $this->init();

        dd(self::SWOSTAR_WELCOME, "启动项目");
    }

    public function run()
    {
        $server = new WebSocketServer($this);
        // $httpServer->watchFile(true);
        $server->start();
    }

    public function registerBaseBindings()
    {
        self::setInstance($this);
        $binds = [
            // 标识  ， 对象
            'config'      => (new \SwoStar\Config\Config())
            /*'index'       => (new \SwoStar\Index()),
            'httpRequest' => (new \SwoStar\Message\Http\Request()),*/
        ];
        foreach ($binds as $key => $value) {
            $this->bind($key, $value);
        }
    }

    public function init()
    {
//        $this->bind('route', Route::getInstance()->registerRoute());
    }

    public function setBasePath($path)
    {
        $this->basePath = \rtrim($path, '\/');
    }
    public function getBasePath()
    {
        return $this->basePath;
    }
}
