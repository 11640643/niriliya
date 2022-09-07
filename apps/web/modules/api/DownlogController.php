<?php

namespace Api;

use C\L\Controller;

class DownlogController extends Controller
{
    public function init()
    {

    }

    public function statAction()
    {
        $url = $this->getValue('url', true, 'string');
        if ($ipExists = $this->filterIp()) {
            header("Location: {$url}");
            exit(0);
        }
        $serve_name = $this->di['config']->get('config')['serve_name'];
        $cur_date = date('Y-m-d');
        $log = $this->s_down_log->search(['sport' => $serve_name, 'date' => $cur_date]);
        if (empty($log)) {
            $add = [
                'sport' => $serve_name,
                'date' => $cur_date,
                'num' => 1,
            ];
            $r = $this->s_down_log->save($add);
        } else {
            $update = ['num' => $log['num']+1];
            $r = $this->s_down_log->update($log['id'], $update);
        }
        if ($r) {
            header("Location: {$url}");
        }
    }
    public function filterIp()
    {
        $ip = $this->di['s_ip']->getIp();
        $curDate = date('Ymd');
        $key = 'xxx:pull:new:'.$curDate.':'.$ip;
        $isExists = $this->cache->get($key);
        if ($isExists) {
            return true;
        }
        $ttl = 86400;
        $this->cache->setnx($key, 1, $ttl);
        return false;
    }
}
