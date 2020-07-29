<?php

namespace SwoStar\Server\WebSocket;

use Controller\Chat\HttpIndex;
use Controller\Chat\WeChatIndex;
use Swoole\WebSocket\Server as SwooleServer;
use SwoStar\Event\Event;
use SwoStar\Server\Http\HttpServer;

class WebSocketServer extends HttpServer
{
    public $zk;
    public $swooleServer;
    public $table;

    public function createServer()
    {
        \Co::set(['hook_flags' => SWOOLE_HOOK_TCP]);
        $this->table = new \Swoole\Table(131072);
        $this->table->column('fd', \Swoole\Table::TYPE_INT, 4);
        $this->table->create();
        $this->swooleServer = new SwooleServer($this->host, $this->port);
    }

    protected function initEvent()
    {
        $this->setEvent('sub', [
            'request' => 'onRequest',
            'open' => "onOpen",
            'message' => "onMessage",
            'close' => "onClose",
            'task' => "onTask",
            'finish' => "onFinish"
        ]);
    }

    protected function initSetting()
    {
        $config = app('config');
        $this->port = $config->get('server.websocket.port');
        $this->host = $config->get('server.websocket.host');
        $this->config = $config->get('server.websocket.swoole');
        $this->process = $config->get('server.websocket.process');
    }

    public function start()
    {
        Event::collectEvent();
        $this->swooleServer->set($this->config);
        $this->swooleServer->start();

    }

    public function onOpen(SwooleServer $server, $request)
    {
        Event::trigger('open', [$request->fd, $request->server['remote_addr']]);
    }

    public function onMessage(SwooleServer $ws, $frame)
    {
        try {
            Event::trigger('message', [$ws, $frame, &$this->table]);
        } catch (\Throwable $e) {
            echo "error message :" . $e->getMessage() . PHP_EOL;
            echo "error file:" . $e->getFile() . PHP_EOL;
            echo "error line:" . $e->getLine() . PHP_EOL;
        }

    }

    public function onRequest($request, $response)
    {
//        echo 'run-ms-start:' . getMillisecond().PHP_EOL;
        try {
            Event::trigger('request', [$request, $response, $this->swooleServer]);
        } catch (\Throwable $e) {
            echo "error message :" . $e->getMessage() . PHP_EOL;
            echo "error file:" . $e->getFile() . PHP_EOL;
            echo "error line:" . $e->getLine() . PHP_EOL;
        }

//        echo 'run-ms-end:' . getMillisecond().PHP_EOL;
    }

    public function onTask($serv, \Swoole\Server\Task $task)
    {
        try {
            Event::trigger('task', [$serv, $task, $this->table]);
        } catch (\Throwable $e) {
            echo "error message :" . $e->getMessage() . PHP_EOL;
            echo "error file:" . $e->getFile() . PHP_EOL;
            echo "error line:" . $e->getLine() . PHP_EOL;
        }
    }

    public function onFinish($serv, $taskId, $data)
    {
    }

    public function onClose($ser, $fd)
    {
        Event::trigger('close', [&$this->table, $fd]);
    }

    public function onStart($ws)
    {

        echo "start" . PHP_EOL;
        cli_set_process_title("{$this->process}:master");
        Event::trigger('start', [&$this]);
    }

    public function onShutdown($ws)
    {
    }

    public function onManagerStart($ws)
    {

        cli_set_process_title("{$this->process}:manager");
        echo "ManagerStart" . PHP_EOL;
    }

    public function onWorkerStart($server, $worker_id)
    {
        cli_set_process_title("{$this->process}:work");
        echo "WorkerStart" . PHP_EOL;
    }
}
