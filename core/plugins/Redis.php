<?php

namespace C\P;

class Redis
{

    // 是否使用 M/S 的读写集群方案
    private $_isUseCluster = false;

    // Slave 句柄标记
    private $_sn = 0;

    private $loginDb;

    // 服务器连接句柄
    private $_linkHandle = array(
        'master' => null,// 只支持一台 Master
        'slave' => array(),// 可以有多台 Slave
    );

    /**
     * 构造函数
     *
     * @param boolean $isUseCluster 是否采用 M/S 方案
     */
    public function __construct($config)
    {
        if (count($config) > 1) {
            $this->_isUseCluster = true;
        }

        foreach ($config as $key => $value) {
            $this->connect($value, $key == 'master' ? true : false);
        }

    }

    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     *
     * @param array $config Redis服务器配置
     * @param boolean $isMaster 当前添加的服务器是否为 Master 服务器
     * @return boolean
     */
    public function connect($config = array('host' => '127.0.0.1', 'port' => 6379), $isMaster = true)
    {
        // default port
        if (!isset($config['port'])) {
            $config['port'] = 6379;
        }
        // 设置 Master 连接
        if ($isMaster) {
            $this->_linkHandle['master'] = new \Redis();
            $ret = $this->_linkHandle['master']->connect($config['host'], $config['port']);
            if (!empty($config['select'])) {
                $this->_linkHandle['master']->select($config['select']);
            }

            //登录数据库
            $this->loginDb = new \Redis();
            $this->loginDb->connect($config['host'], $config['port']);
            $this->loginDb->select(15);
        } else {
            // 多个 Slave 连接
            $this->_linkHandle['slave'][$this->_sn] = new \Redis();
            $ret = $this->_linkHandle['slave'][$this->_sn]->connect($config['host'], $config['port']);
            ++$this->_sn;
        }
        return $ret;
    }

    /**
     * 关闭连接
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag = 2)
    {
        switch ($flag) {
            // 关闭 Master
            case 0:
                $this->getRedis()->close();
                break;
            // 关闭 Slave
            case 1:
                for ($i = 0; $i < $this->_sn; ++$i) {
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
            // 关闭所有
            case 1:
                $this->getRedis()->close();
                for ($i = 0; $i < $this->_sn; ++$i) {
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
        }
        return true;
    }

    /**
     * 得到 Redis 原始对象可以有更多的操作
     *
     * @param boolean $isMaster 返回服务器的类型 true:返回Master false:返回Slave
     * @param boolean $slaveOne 返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($isMaster = true, $slaveOne = true)
    {
        // 只返回 Master
        if ($isMaster) {
            return $this->_linkHandle['master'];
        } else {
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
    }

    /**
     * 写缓存
     *
     * @param string $key 组存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function set($key, $value, $params = [])
    {
        return $this->getRedis()->set($key, $value, $params);
    }


    public function setex($key, $value, $ttl = 10)
    {
        return $this->getRedis()->setex($key, $ttl, $value);
    }

    public function expire($key, $expire)
    {
        return $this->getRedis()->expire($key, $expire);
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function get($key)
    {
        // 是否一次取多个值
        $func = is_array($key) ? 'mGet' : 'get';
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            return $this->getRedis()->{$func}($key);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->{$func}($key);
    }

    /**
     * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function setnx($key, $value, $ttl = 0)
    {
        if ($ttl > 0) {

            return $this->getRedis()->set($key, $value, [
                'nx', 'ex' => $ttl
            ]);

        } else {

            return $this->getRedis()->setnx($key, $value);
        }

    }

    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function remove($key)
    {
        // $key => "key1" || array('key1','key2')
        return $this->getRedis()->del($key);
    }

    public function hSet($key, $filed, $value, $expire = 0)
    {
        $result = $this->getRedis()->hSet($key, $filed, $value);
        if ($expire !== 0) {
            $this->expire($key, $expire);
        }
        return $result;
    }

    public function hGet($key, $filed)
    {
        return $this->getRedis()->hGet($key, $filed);
    }

    public function hGetAll($key)
    {
        return $this->getRedis()->hGetAll($key);
    }

    public function hMset($key, $data, $expire = 0)
    {
        $result = $this->getRedis()->hMset($key, $data);
        if ($expire !== 0) {
            $this->expire($key, $expire);
        }
        return $result;
    }

    public function hMget($key, $fileds)
    {
        return $this->getRedis()->hMget($key, $fileds);
    }

    public function hDel($key, $filed)
    {
        return $this->getRedis()->hDel($key, $filed);
    }

    public function exists($key)
    {
        return $this->getRedis()->exists($key);
    }

    public function lPush($key, $value)
    {
        return $this->getRedis()->lPush($key, $value);
    }

    public function lGet($key, $value)
    {
        return $this->getRedis()->lGet($key, $value);
    }

    public function lPushx($key, $value)
    {
        return $this->getRedis()->lPushx($key, $value);
    }

    public function rPush($key, $value)
    {
        return $this->getRedis()->rPush($key, $value);
    }

    public function rPushx($key, $value)
    {
        return $this->getRedis()->rPushx($key, $value);
    }

    public function lPop($key)
    {
        return $this->getRedis()->lPop($key);
    }
    public function blPop($key,$timeout)
    {
        return $this->getRedis()->blPop($key,$timeout);
    }
    public function lRange($key, $start = 0, $end = -1)
    {
        return $this->getRedis()->lRange($key, $start, $end);
    }

    public function rPop($key)
    {
        return $this->getRedis()->rPop($key);
    }

    public function lSize($key)
    {
        return $this->getRedis()->lSize($key);
    }

    public function rpoplpush($key1, $key2)
    {
        return $this->getRedis()->rpoplpush($key1, $key2);
    }

    public function sAdd($key, $value)
    {
        return $this->getRedis()->sAdd($key, $value);
    }

    public function sRem($key, $value)
    {
        return $this->getRedis()->sRem($key, $value);
    }

    public function sIsMember($key, $value)
    {
        return $this->getRedis()->sIsMember($key, $value);
    }

    public function sMembers($key)
    {
        return $this->getRedis()->sMembers($key);
    }

    public function zAdd($key, $value, $score = 0)
    {
        return $this->getRedis()->zAdd($key, $score, $value);
    }

    public function zCount($key, $min = 0, $max = 0)
    {
        return $this->getRedis()->zCount($key, $min, $max);
    }

    public function zScore($key, $value)
    {
        return $this->getRedis()->zScore($key, $value);
    }

    public function zDelete($key, $value)
    {
        return $this->getRedis()->zDelete($key, $value);
    }

    public function zRange($key, $start, $end, $withscores = true)
    {
        return $this->getRedis()->zRange($key, $start, $end, $withscores);
    }

    public function zRevRange($key, $start, $end, $withscores = true)
    {
        return $this->getRedis()->zRevRange($key, $start, $end, $withscores);
    }

    public function zRevRangeByScore($key, $start, $end)
    {
        return $this->getRedis()->zRevRangeByScore($key, $start, $end);
    }

    public function zRangeByScore($key, $start, $end)
    {
        return $this->getRedis()->zRangeByScore($key, $start, $end);
    }

    public function zRemRangeByScore($key, $start, $end)
    {
        return $this->getRedis()->zRemRangeByScore($key, $start, $end);
    }

    public function zIncrBy($key, $increment, $value)
    {
        return $this->getRedis()->zIncrBy($key, $increment, $value);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function incr($key, $default = 1, $ttr = 0)
    {

        if ($default == 1) {
            $num = $this->getRedis()->incr($key);
        } else {
            $num = $this->getRedis()->incrBy($key, $default);
        }

        if ($ttr > 0) {
            $this->expire($key, $ttr);
        }

        return $num;
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function decr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->decr($key);
        } else {
            return $this->getRedis()->decrBy($key, $default);
        }
    }

    /**
     * 添空当前数据库
     *
     * @return boolean
     */
    public function clear()
    {
        return $this->getRedis()->flushDB();
    }

    /**
     * 位图, 设置
     */
    public function redisSetBit($key, $offset, $value)
    {
        return $this->getRedis()->setBit($key, $offset, $value);
    }

    /**
     * 位图，获取
     */
    public function redisGetBit($key, $offset)
    {
        return $this->getRedis()->getBit($key, $offset);
    }

    /**
     * 位图，计算
     */
    public function redisBitCount($key, $start=null, $end=null)
    {
        if (!is_null($start) && !is_null($end)) {
            return $this->getRedis()->bitCount($key, $start, $end);
        }
        return $this->getRedis()->bitCount($key);
    }

    /**
     * 查询剩余生存时间
     */
    public function ttl($key)
    {
        return $this->getRedis()->ttl($key);
    }

    /* =================== 以下私有方法 =================== */

    /**
     * 随机 HASH 得到 Redis Slave 服务器句柄
     *
     * @return redis object
     */
    private function _getSlaveRedis()
    {
        // 就一台 Slave 机直接返回
        if ($this->_sn <= 1) {
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = $this->_hashId(mt_rand(), $this->_sn);
        return $this->_linkHandle['slave'][$hash];
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     *
     * @param string $id
     * @param int $m
     * @return int
     */
    private function _hashId($id, $m = 10)
    {
        //把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for ($i = 0; $i < $l; $i++) {
            //相加模式HASH
            $h += substr($b, $i * 2, 2);
        }
        $hash = ($h * 1) % $m;
        return $hash;
    }

}
