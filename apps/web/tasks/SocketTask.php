<?php

class SocketTask extends \C\L\Task
{
    public function mainAction()
    {
        $server = new swoole_websocket_server("0.0.0.0", 8080);

        $server->on('open', function ($server, $req) {
            $this->logs(['msg' => '连接成功', 'ip' => $req->server['remote_addr']]);
        });

        $server->on('message', function ($server, $frame) {
            $ip = $server->getClientInfo($frame->fd)['remote_ip'];

            $data = json_decode($frame->data, true);
            $this->sync($data, $ip);

            $send = [
                'show_anwser' => $this->checkAnwser($data['ssid'])
            ];

            $this->logs([
                'ip' => $ip,
                'data' => $frame->data,
                'send' => $send
            ]);

            $server->push($frame->fd, json_encode($send));
        });

        $server->on('close', function ($server, $fd) {
            $this->logs([
                'ip' => $server->getClientInfo($fd)['remote_ip'],
                'msg' => '已关闭'
            ]);
        });

        $server->start();
    }

    private function sync($data, $ip)
    {
        $uid = $this->cache->hget($data['ssid'], 'uid');
        if ($uid > 0) {
            $userLoginIp = $this->s_user->getValue('uid', ['uid' => $uid]);
            if ($ip && $ip != $userLoginIp) {
                $update = [
                    'last_login_ip' => $ip,
                    'last_login_addr' => $addr = $this->s_ip->getIpToLocation($ip),
                ];

                if (strlen($data['dev_no']) > 7) {
                    $this->cache->hset($data['ssid'], 'dev_no', $data['dev_no']);
                    $this->cache->hset($data['ssid'], 'dev_type', $data['dev_type']);
                    $update['dev_no'] = $data['dev_no'];
                    $update['dev_type'] = $data['dev_type'];
                }

                $this->s_user->update($uid, $update);
            }
        }
    }

    private function checkAnwser($ssid)
    {
        $config = $this->s_config->get('anwser');
        $key = 'anwser' . date('Ymd');
        if ($config['is_open'] == 'Y' && !$this->cache->hget($ssid, $key)) {
            $this->cache->hset($ssid, $key, 1);
            return true;
        }

        return false;
    }
}