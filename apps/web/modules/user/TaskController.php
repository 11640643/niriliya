<?php

namespace User;

use C\L\WebUserController;

class TaskController extends WebUserController
{
    public function indexAction()
    {
        $config = $this->s_task->config();
        $statusConfig = [
            'S' => $this->translate['finish_go'],
            'Y' => $this->translate['finish_has'],
            'N' => $this->translate['finish_get']
        ];
        $todayOkNum = 0;
        foreach ($config as $k => &$item) {
            if($k == 'apply_item'){
                if(!$this->s_item->getShowItem($this->uid)){
                    unset($config[$k]);
                    continue;
                }
            }
            $status = 'S';

            $method = $item['key'];
            if (method_exists($this->s_task, $method) && $status == 'S') {
                $status = $this->s_task->$method($this->uid);
//                if($item['key'] ==  'reg'){
//                    $item['t2'] = sprintf($item['t2'], $this->s_task->count);
//                }

                if($item['key'] ==  'clos'){
                    $item['t3'] = sprintf($item['t3'], $this->s_task->count);
                }
            }

            if ($status == 'Y') {
                $todayOkNum++;
            }

            $item['status'] = $status;
            $item['status_name'] = $statusConfig[$item['status']];

        }

        $user = $this->s_user->search($this->uid);
        // 重设footer
        $this->s_item->resetFooter($this->uid);
        $public = $this->s_config->get('public');
        $this->success([
            'money' => $user['money'],
            'credit' => $user['credit'],
            'today_task_num' => count($config),
            'today_ok_num' => $todayOkNum,
            'mobile' => $this->ssid->get('mobile'),
            'list' => array_values($config),
            'notice' => [$public['task_notice']] ?? []
        ]);
    }


    public function okAction()
    {
        $uid = $this->uid;
        $type = $this->getValue("type", true);
        if (!$this->s_task->add($uid, $type)) {
            $this->error();
        }

        $this->success();
    }
}


