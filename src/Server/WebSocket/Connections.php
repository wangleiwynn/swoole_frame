<?php
namespace SwoStar\Server\WebSocket;

class Connections
{
    /**
     * 记录用户的连接
     * [
     *    fd => [
     *        'path' => xxx,
     *        'xxx' => ooo
     *    ]
     * ]
     * @var array
     */
    protected static $connections = [];

    public static function init($fd, $addr)
    {
        self::$connections[$fd]['remote_addr'] = $addr;
        return self::$connections[$fd];
    }

    public static function get($fd)
    {
        return self::$connections[$fd];
    }
    public static function set($fd,$uid){
        self::$connections[$fd][$uid]=$fd;
        return self::$connections[$fd];
    }

    public static function del($fd)
    {
        unset(self::$connections[$fd]);
    }
}
