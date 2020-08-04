<?php

namespace SwoStar\Server\WebSocket;
use \Swoole\Table;

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
    protected static $uids = [];
//    protected $table;

    public static function init($fd, $addr)
    {
        self::$connections[$fd]['remote_addr'] = $addr;
        return self::$connections[$fd];
    }

    public static function get($table,$uids)
    {

        foreach ($uids as $uid){
            $toFd[$uid] = $table->get($uid,'fd');
        }
        return $toFd;
    }

    public static function set($table,$fd,$uid)
    {
        $table->set($uid,['fd'=>$fd]);
    }

    public static function del($fd)
    {
        $con = self::$connections[$fd];
        $uid = array_search($fd, $con);
        unset(self::$connections[$fd]);
        if (!empty($uid))
            unset(self::$uids[$uid]);
    }

    public static function delByFd($table,$fd)
    {
        $res=[];
        foreach ($table as $key => $row){
            if($row['fd']===$fd){
                if($table->del($key)){
                    $res = ['uid'=>$key];
                }else{
                    echo "del table data err uid:{$key}--fd:{$fd}";
                }
                break;
            }
        }
        return $res;
    }

}
