<?php

namespace User;

use C\L\WebUserController;

class CmpController extends WebUserController
{
    public function dataAction()
    {
        $status = $this->getValue('status');
        $data = ['uid' => $this->uid];
        $dataType = [];
        // $dataType = ['status' => 'in'];
        // if ($status == 'B') {
        //     $data['status'] = ['Y', 'N'];
        // }
        $type = $this->getValue('type', true);
        $res = [];
        switch ($type) {
            case 'clo':
                $clos = $this->s_clolist->searchPage($data, $dataType, [], '', 1, 500);
                foreach ($clos as $clo) {
                    $res[] = [
                        'type' => 'clo',
                        'no_date' => $clo['no_date'],
                        'name' => '早起挑战赛',
                        'money' => $clo['money'],
                        'back_money' => $clo['back_money'],
                        'status' => $clo['status'],
                        'addtime_date' => date('Y年m月d日 H:i', $clo['addtime']),
                        'end_date' => date('Y年m月d日H:i', $clo['max_dk_time']),
                        'status_name' => $this->s_clolist->getStatusConfig()['status'][$clo['status']],
                        'addtime' => $clo['addtime']
                    ];
                }
                break;
            case 'pack':
                $packs = $this->s_packlist->searchPage($data, $dataType, [], '', 1, 500);
                foreach ($packs as $pack) {
                    $res[] = [
                        'type' => 'pack',
                        'no_date' => $pack['no_date'],
                        'name' => $this->translate['active_type_sport'],
                        'money' => $pack['money'],
                        'back_money' => $pack['back_money'],
                        'status' => $pack['status'],
                        'addtime_date' => date('Y年m月d日 H:i', $pack['addtime']),
                        'end_date' => date('Y年m月d日23:59', strtotime($pack['no_date'])),
                        'status_name' => $this->s_packlist->getStatusConfig()['status'][$pack['status']],
                        'addtime' => $pack['addtime']
                    ];
                }
                break;
            default:
                $this->error($this->translate['param_error']);
                break;
        }

        // $yebs = $this->s_yebin->searchPage($data, $dataType, [], '', 1, 100);
        // foreach ($yebs as $yeb) {
        //     $data[] = [
        //         'type' => 'yeb',
        //         'name' => '袋鼠宝',
        //         'money' => $yeb['money'],
        //         'addtime_date' => date('Y年m月d日 H:i', $yeb['addtime']),
        //         'end_date' => date('Y年m月d日 H:i', $yeb['out_time']),
        //         'status_name' => $this->s_yebin->getStatusConfig()['status'][$yeb['status']],
        //         'addtime' => $yeb['addtime']
        //     ];
        // }

        $this->success($res);
    }
}


