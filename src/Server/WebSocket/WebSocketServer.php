<?php

namespace SwoStar\Server\WebSocket;

use Controller\Chat\HttpIndex;
use Controller\Zk\Zk;
use Swoole\WebSocket\Server as SwooleServer;
use SwoStar\Server\Http\HttpServer;
use Controller\Chat\WeChatIndex;

class WebSocketServer extends HttpServer
{
    private $zk;
    private $wi;
    private $hp;

    public function createServer()
    {
        \Co::set(['hook_flags' => SWOOLE_HOOK_TCP]);
        $this->table = new \Swoole\Table(131072);
        $this->table->column('fd',\Swoole\Table::TYPE_INT,4);
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
        $this->swooleServer->set($this->config);
        $this->swooleServer->start();

    }

    public function onOpen(SwooleServer $server, $request)
    {
//        $connect = Connections::init($request->fd, $request->server['remote_addr']);
        echo "connect fd:{$request->fd} addr:{$request->server['remote_addr']}" . PHP_EOL;
    }

    public function onMessage(SwooleServer $ws, $frame)
    {
//        $connets = (Connections::get($frame->fd));
        try {
            $wechatIndex = new WeChatIndex();
            $receive_data = json_decode($frame->data, true);
            $taskData = ['task' => 1, 'fd' => $frame->fd];
            if ($receive_data['type'] === 'bind') {

                if (!$ws->isEstablished($frame->fd)) {
                    return;
                }
                if (empty($receive_data['uid'])) {
                    echo "invalid uid :" . json_encode($receive_data) . 'IP:' . $clentInfo['remote_ip'] . PHP_EOL;
                    $ws->disconnect($frame->fd);
                    return;
                }
                go(function () use ($ws, $wechatIndex, $receive_data, $frame, $taskData) {
                    Connections::set($this->table,$frame->fd, $receive_data['uid']);
                    $finish = array_merge(['toFd' => [$frame->fd], 'msg' => json_encode(['name' => $receive_data['uid'], 'data' => 'bind ok'])], $taskData);
                    $finish['type'] = 'bind';
                    $finish['uid'] = $receive_data['uid'];
                    $ws->task($finish);
                });
            } else {

                //判断是否bind
                /*if ($wechatIndex->isBind($frame->fd)) {
                    $toFd = $wechatIndex->index($ws, $receive_data);
                    $toMsg = array_merge(['toFd' => $toFd, 'msg' => $frame->data], $taskData);
                    $ws->task($toMsg);
                } else {
                    $clentInfo = $this->ws->getClientInfo($frame->fd);
                    if (strcasecmp($clentInfo['remote_ip'], '127.0.0.1') == 0) {
                        $toFd = $wechatIndex->index($ws, $receive_data);
                        $toMsg = array_merge(['toFd' => $toFd, 'msg' => $frame->data], $taskData);
                        $ws->task($toMsg);
                    } else {
                        $finish = ['toFd' => [$frame->fd], 'msg' => json_encode(['message' => 'unbounded', 'from' => $receive_data])];
                        echo "unbounded:" . json_encode($finish) . 'IP:' . $clentInfo['remote_ip'] . PHP_EOL;
                        if ($ws->isEstablished($frame->fd)) {
                            $ws->disconnect($frame->fd);
                        }
                    }

                }*/
            }
        } catch (\Error $e) {
            echo "error message :" . $e->getMessage() . PHP_EOL;
            echo "error file:" . $e->getFile() . PHP_EOL;
            echo "error line:" . $e->getLine() . PHP_EOL;
        }
        unset($wechatIndex);
    }

    public function onRequest($request, $response)
    {
        echo 'run-ms-start:' . getMillisecond().PHP_EOL;
        if (strcmp($request->server['request_uri'], '/chatMsg/send') == 0) {
            $msgData = $request->rawContent();
            $msgData = $this->wi->checkSend($msgData);
            if (!empty($msgData['code'])) {
                $response->status(400);
                $response->end(json_encode($msgData));
                return;
            }
            $taskData = ['task' => 1, 'send' => 1, 'msg' => $msgData['data']];
            $this->swooleServer->task($taskData);
            $response->end(json_encode(['code' => 0, 'msg' => 'ok', 'data' => null]));
        } else {
            $this->hp->index($request, $response);
        }
        echo 'run-ms-end:' . getMillisecond().PHP_EOL;
    }

    public function onTask($serv, \Swoole\Server\Task $task)
    {
        try {
            $data = $task->data;
            if (isset($data['type'])) {
                //bind msg
                $serv->push($data['fd'], $data['msg']);
                //查询离线收到的信息
                $msg = $this->wi->getOfflineMsg($data['uid']);
                if (!empty($msg)) {
                    $serv->push($data['fd'], json_encode($msg));
                }
            } elseif (isset($data['send'])) {
                $msg = $data['msg'];
                $toFd = $this->wi->getFds($this->table,$msg['to_uid']);
                go(function () use ($serv, $toFd, $msg) {
                    foreach ($toFd as $uid => $value) {
                        if ($serv->isEstablished($value) and !empty($value)) {
                            $msg['to_uid'] = $uid;
                            $res = $serv->push($value, json_encode($msg));
                        } else {
                            //保存离线消息
                            $nlUids[] = $uid;
                        }
                    }
                    $this->wi->saveOfflineMsg($nlUids, $msg);
                });
            }
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
//        $connets = (Connections::get($fd));
        $res = Connections::delByFd($this->table,$fd);
        echo "close user: {$res['uid']} -- fd:{$fd} " . PHP_EOL;
    }

    public function onStart($ws)
    {
        swoole_set_process_name("{$this->process}:master");
        echo "start" . PHP_EOL;
        $this->zk = new Zk();
        $this->zk->zooNodeCreate(3);
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
        $this->wi = new WeChatIndex();
        $this->hp = new HttpIndex();
        cli_set_process_title("{$this->process}:work");
        echo "WorkerStart" . PHP_EOL;

    }

}
