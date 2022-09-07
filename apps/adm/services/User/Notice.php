<?php

namespace C\S\User;

use C\L\Service;

class Notice extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserNotice();
    }

    public function getStatusConfig()
    {
        return [
            'type' => [
                'none' => '所有用户',
                'user' => '指定用户',
                'vip' => 'vip用户'
            ],
            'vip' => $this->di['s_level']->searchAll([], [], ['id', 'name'])
        ];
    }

    public function syncNotice($uid)
    {
        $lockKey = "uid:$uid:syncNotice";
        if (!$this->di['s_user']->lock($lockKey, 5) || !$uid) {
            return false;
        }

        $uid = intval($uid);
        $vipId = intval($this->di['s_level']->getLevel($uid)['id']);
        $lastNoticeId = intval($this->di['ssid']->get('last_notice_id'));
        $regTime = intval($this->di['ssid']->get('reg_time'));
        $sql = " id > $lastNoticeId and addtime >= $regTime and (type = 'none' or uid = $uid or vip_id = $vipId)";
        $notice = $this->searchAll(['uid' => $sql], ['uid' => 'sql']);

        if ($notice) {

            $time = time();
            $data = [];
            foreach ($notice as $item) {

                if($item['id'] > $lastNoticeId){
                    $lastNoticeId = $item['id'];
                }

                if($this->di['s_noticeread']->search(['uid' => $uid, 'cid' => $item['id']])){
                    continue;
                }

                $data[] = [
                    'uid' => $uid,
                    'cid' => $item['id'],
                    'uptime' => $time,
                    'addtime' => $time,
                ];
            }

            $this->di['ssid']->set('last_notice_id', $lastNoticeId);
            return $this->di['s_noticeread']->saves($data);
        }

        return false;
    }
}
