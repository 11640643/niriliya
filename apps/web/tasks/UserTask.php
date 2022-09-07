<?php

class UserTask extends \C\L\Task
{

    private $publicUsersStatis = [];


    public function runAction()
    {
        // $this->runCheck('user_run', 3600);
        $this->isRun('user run');
        $path = APP_PUBLIC . '/upload/file/';
        if (!file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                return true;
            }
        }

        $i = 0;
        $id = 0;
        while (!$i) {

            if ($this->cache->get('start_user_csv') >= 2) {

                $name = 'user_' . date('YmdHis') . '.csv';
                $file = $path . $name;
                $this->cache->set('start_user_csv', 1);
                file_put_contents($file, '姓名,手机号,待返本金,总充值金额' . PHP_EOL);

                $this->s_export->save([
                    'file' => '/upload/file/' . $name
                ]);

                do {

                    $users = $this->s_user->searchPage([
                        'uid' => $id,
                        'status' => 'Y'
                    ], ['uid' => '>'], ['uid', 'name', 'mobile'], 'uid asc', 1, 1000);
                    if (!$users) {
                        $this->cache->set('start_user_csv', 0);
                        return true;
                    }

                    $id = $users[count($users) - 1]['uid'];

                    $this->logs(['msg' => '继续', 'id' => $id]);

                    foreach ($users as $user) {
                        $allMoney = $this->s_iam->getSum('money', ['uid' => $user['uid'], 'status' => 'D']);
                        $investMoney = $this->s_invest->getSum('money', ['uid' => $user['uid'], 'status' => 'Y']);
                        file_put_contents($file, "{$user['name']},{$user['mobile']},$allMoney,$investMoney" . PHP_EOL, FILE_APPEND);
                    }

                } while ($users);

                $this->logs(['msg' => '已完成', 'date' => date('Y-m-d H:i:s')]);
            }
            sleep(3);
        }
    }

    public function levelAction()
    {
        $items = $this->s_il->searchAll(['addtime' => 1574006400], ['addtime' => '>=']);
        foreach ($items as $item) {

            if ($item['level_apr'] > 0) {
                continue;
            }
            $moneySum = $this->s_il->getSum('money', ['uid' => $item['uid'], 'addtime' => $item['addtime']], ['addtime' => '<']);
            $level = $this->getLevel($moneySum);
            if ($level['apr'] > 0) {
                $aprs = $this->s_iam->searchAll(['cid' => $item['id']], [], ['id', 'apr', 'money', 'apr_money']);
                foreach ($aprs as $p) {
                    $sApr = $item['apr'] + $level['apr'];
                    $aprMoney = bcmul($item['money'] / 100, $sApr, 2);
                    $this->s_iam->update($p['id'], ['apr_money' => $aprMoney]);
                }

                $this->s_il->update($item['id'], ['level_apr' => $level['apr']]);

                echo $item['id'] . PHP_EOL;
            }
        }
    }


    public function getLevel($money = 0)
    {
        $config = $this->di['s_config']->get('vip')['vip'];
        if (!is_array($config)){
            return;
        }
        ksort($config);
        $vip = array_values($config)[count($config) - 1];
        foreach ($config as $c) {
            if ($c['score'] > $money) {
                break;
            }
            $vip = $c;
        }

        return $vip;
    }


    public function statisAction()
    {
        $this->runCheck('user_statis', 1800);
        ini_set('memory_limit', -1);
        $time = time();
        $uscs = $this->s_usc->searchPage(['status' => 'D'], [], [], '', 1, 20);
        if (empty($uscs)) {
            return true;
        }

        foreach ($uscs as $usc) {

            $userStatis = [];
            switch ($usc['type']) {
                case 'tj_b1':
                    $userStatis = $this->getSum('tj_b1');
                    break;
                case 'tj_a1':
                    $userStatis = $this->tja1();
                    break;
                case 'ip':
                    $userStatis = $this->ip();
                    break;
                case 'verify_a':
                    $userStatis = $this->getSum('verify_a');
                    break;
                case 'verify_b':
                    $userStatis = $this->getSum('verify_b');
                    break;
                case 'invest_a':
                    $userStatis = $this->getSum('invest_a');
                    break;
                case 'money_a':
                    $userStatis = $this->getSum('money_a');
                    break;
                case 'item_a':
                    $userStatis = $this->getSum('item_a');
                    break;
            }

            echo $usc['type'] . PHP_EOL;
            $this->s_usc->update($usc['id'], ['status' => 'Y', 'extime' => $time]);
            if ($userStatis) {

                $adds = [];
                arsort($userStatis);
                $userStatis = array_slice($userStatis, 0, 150);
                foreach ($userStatis as $k => $num) {
                    $adds[] = [
                        'cid' => $usc['id'],
                        'v1' => explode('_', $k)[1],
                        'v2' => $num,
                        'addtime' => $time,
                        'uptime' => $time,
                    ];
                }

                $this->s_uscd->saves($adds);
            }
        }

        return true;
    }

    public function getSum($type = 'verify_a')
    {
        if (!empty($this->publicUsersStatis[$type])) {
            return $this->publicUsersStatis[$type];
        }

        $pageNum = 1;
        $userStatis = [
            'verify_a' => [],
            'verify_b' => [],
            'invest_a' => [],
            'money_a' => [],
            'item_a' => [],
            'tj_b1' => []
        ];
        do {

            $users = $this->s_user->searchPage(['status' => 'Y'], [], ['uid', 't_uid'], 'uid asc', $pageNum, 1000);
            if (empty($users)) {
                break;
            }

            $pageNum++;
            foreach ($users as $user) {
                $key = 't_' . $user['uid'];
                $verifyCount = $this->s_funds->getCount(['uid' => $user['uid'], 'stype' => 'checkin']);
                if ($verifyCount > 0) {
                    $userStatis['verify_a'][$key] = $verifyCount;
                }

                $investMoney = $this->s_invest->getSum('money', ['uid' => $user['uid'], 'status' => 'Y']);
                if ($investMoney > 0) {
                    $userStatis['invest_a'][$key] = $investMoney;
                } else {
                    if ($verifyCount > 0) {
                        $userStatis['verify_b'][$key] = $verifyCount;
                    }
                }

                $userStatis['money_a'][$key] = $this->s_user->getValue('money', ['uid' => $user['uid']]);

                $itemMoneySum = bcadd($this->s_il->getSum('money', ['uid' => $user['uid']]), 0, 2);
                if ($itemMoneySum > 0) {
                    $userStatis['item_a'][$key] = $itemMoneySum;
                }

                if ($user['t_uid'] == 0) {

                    $count = count($this->s_usc->getNextUserNum($user['uid'], true));
                    if ($count > 0) {
                        $userStatis['tj_b1'][$key] = $count;
                    }

                }
            }

        } while ($users);

        $this->publicUsersStatis = $userStatis;

        return $userStatis[$type];
    }

    public function ip()
    {
        $ips = $this->s_usc->getIps();
        $userStatis = [];
        foreach ($ips as $ip) {
            $key = 't_' . $ip['reg_ip'];
            $userStatis[$key] = $this->s_user->getCount(['reg_ip' => $ip['reg_ip']]);
        }

        return $userStatis;
    }

    public function tja1()
    {
        $pageNum = 0;
        $userStatis = [];
        do {

            $users = $this->s_user->searchPage(['status' => 'Y'], [], ['uid'], 'uid asc', $pageNum, 1000);
            if (empty($users)) {
                break;
            }

            $pageNum++;
            foreach ($users as $user) {
                $key = 't_' . $user['uid'];
                $count = count($this->s_usc->getNextUserNum($user['uid']));
                if ($count > 0) {
                    $userStatis[$key] = $count;
                }
            }

        } while ($users);

        return $userStatis;
    }

    public function tjb1()
    {
        $pageNum = 0;
        $userStatis = [];
        do {

            $users = $this->s_user->searchPage(['t_uid' => 0, 'status' => 'Y'], [], ['uid'], 'uid asc', $pageNum, 1000);
            if (empty($users)) {
                break;
            }

            $pageNum++;
            foreach ($users as $user) {
                $key = 't_' . $user['uid'];
                $count = count($this->s_usc->getNextUserNum($user['uid'], true));
                if ($count > 0) {
                    $userStatis[$key] = $count;
                }
            }

        } while ($users);

        return $userStatis;
    }
}