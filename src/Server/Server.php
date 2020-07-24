<?php
namespace SwoStar\Server;

use Swoole\Server as SwooleServer;
use SwoStar\Foundation\Application;
//use SwoStar\RPC\Rpc;

/**
 * 所有服务的父类， 写一写公共的操作
 */
abstract class Server
{
    // 属性
    /**
     *
     * @var Swoole/Server
     */
    protected $swooleServer;

    protected $app ;

    protected $inotify = null;

    protected $port;

    protected $host;

    protected $process;

    protected $watchFile = false;
    /**
     * 用于记录系统pid的信息
     * @var string
     */
    protected $pidFile = "/runtime/swostar.pid";
    /**
     * 这是swoole服务的配置
     * @var [type]
     */
    protected $config = [];
    /**
     * 用于记录pid的信息
     * @var array
     */
    protected $pidMap = [
        'masterPid'  => 0,
        'managerPid' => 0,
        'workerPids' => [],
        'taskPids'   => []
    ];
    /**
     * 注册的回调事件
     * [
     *   // 这是所有服务均会注册的时间
     *   "server" => [],
     *   // 子类的服务
     *   "sub" => [],
     *   // 额外扩展的回调函数
     *   "ext" => []
     * ]
     *
     * @var array
     */
    protected $event = [
        // 这是所有服务均会注册的时间
        "server" => [
            // 事件   =》 事件函数
            "start"        => "onStart",
            "managerStart" => "onManagerStart",
            "managerStop"  => "onManagerStop",
            "shutdown"     => "onShutdown",
            "workerStart"  => "onWorkerStart",
            "workerStop"   => "onWorkerStop",
            "workerError"  => "onWorkerError",
        ],
        // 子类的服务
        "sub" => [],
        // 额外扩展的回调函数
        // 如 ontart等
        "ext" => []
    ];

    public function __construct(Application $app )
    {
        $this->app = $app;
        $this->initSetting();
        // 1. 创建 swoole server
        $this->createServer();
        // 3. 设置需要注册的回调函数
        $this->initEvent();
        // 4. 设置swoole的回调函数
        $this->setSwooleEvent();
    }
    /**
     * 创建服务
     *
     */
    protected abstract function createServer();
    /**
     * 初始化监听的事件
     *
     */
    protected abstract function initEvent();
    // 通用的方法

    public function start()
    {
//        $config = app('config');
        // 2. 设置配置信息
        $this->swooleServer->set($this->config);
        /*if ($config->get('server.http.tcpable')) {
            new Rpc($this->swooleServer, $config->get('server.http.rpc'));
        }*/
        // 5. 启动
        $this->swooleServer->start();
    }



    protected abstract function initSetting();
    /**
     * 设置swoole的回调事件
     *
     */
    protected function setSwooleEvent()
    {
        foreach ($this->event as $type => $events) {
            foreach ($events as $event => $func) {
                $this->swooleServer->on($event, [$this, $func]);
            }
        }
    }
   /* protected function watchEvent()
    {
        return function($event){
            // $action = 'file:';
            // switch ($event['mask']) {
            //     case IN_CREATE:
            //       $action = 'IN_CREATE';
            //       break;
            //     case IN_DELETE:
            //       $action = 'IN_DELETE';
            //       break;
            //     case \IN_MODIFY:
            //       $action = 'IN_MODIF';
            //       break;
            //     case \IN_MOVE:
            //       $action = 'IN_MOVE';
            //       break;
            // }
            // echo "因为什么重启";
            $this->swooleServer->reload();
        };
    }*/
    // 回调方法
    public function onStart(SwooleServer $server)
    {
        $this->pidMap['masterPid'] = $server->master_pid;
        $this->pidMap['managerPid'] = $server->manager_pid;

        /*if ($this->watchFile ) {
            $this->inotify = new Inotify($this->app->getBasePath(), $this->watchEvent());
            $this->inotify->start();
        }*/
    }

    public function onManagerStart(SwooleServer $server)
    {

    }
    public function onManagerStop(SwooleServer $server)
    {

    }
    public function onShutdown(SwooleServer $server)
    {

    }
    public function onWorkerStart(SwooleServer $server, int $worker_id)
    {
        $this->pidMap['workerPids'] = [
            'id'  => $worker_id,
            'pid' => $server->worker_id
        ];
    }
    public function onWorkerStop(SwooleServer $server, int $worker_id)
    {

    }
    public function onWorkerError(SwooleServer $server, int $workerId, int $workerPid, int $exitCode, int $signal)
    {
    }

    // GET | SET

    /**
     * @param array
     *
     * @return static
     */
    public function setEvent($type, $event)
    {
        // 暂时不支持直接设置系统的回调事件
        if ($type == "server") {
            return $this;
        }
        $this->event[$type] = $event;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return static
     */
    public function setConfig($config)
    {
        $this->config = array_map($this->config, $config);
        return $this;
    }

    public function watchFile($watchFile)
    {
        $this->watchFile = $watchFile;
    }
}
