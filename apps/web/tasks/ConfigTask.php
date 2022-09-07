<?php

class ConfigTask extends \C\L\Task
{
    public function packAction()
    {
        $this->runCheck('config_pack');

        $current_date = date('Ymd');
        $content = $this->s_config->get('pack');
        for ($i = 0; $i < 3; $i++) {

            $date = date('Ymd', strtotime("+ $i day"));
            $pack = $this->s_pack->search(['no_date' => $date]);
            // if (!empty($pack) && $date == $current_date) {
            //     continue;
            // }

            $add = [
                'no_date' => $date,
                'no_time' => strtotime($date),
                'set_task_step1' => $content['set_task_step1'],
                'set_task_apr1' => $content['set_task_apr1'],
                'set_task_step2' => $content['set_task_step2'],
                'set_task_apr2' => $content['set_task_apr2'],
                'set_task_step3' => $content['set_task_step3'],
                'set_task_apr3' => $content['set_task_apr3'],
                'set_task_step4' => $content['set_task_step4'],
                'set_task_apr4' => $content['set_task_apr4'],
                'set_task_money1' => $content['set_task_money1'],
                'set_task_money2' => $content['set_task_money2'],
                'set_task_money3' => $content['set_task_money3'],
                'set_task_money4' => $content['set_task_money4'],
                'auto_add_num' => $content['auto_add_num'],
                'auto_add_user_money' => $content['auto_add_user_money'],
                // 'min_money' => $content['min_money'],
                'not_apr' => $content['not_apr'],
            ];

            if (!empty($pack) && $this->s_pack->update($pack['id'], $add)) {
                $this->logs(['msg' => '更新成功', 'date' => $date, 'update' => $add]);
            }

            if (empty($pack) && $this->s_pack->save($add)) {
                $this->logs(['msg' => '生成成功', 'date' => $date, 'add' => $add]);
            }
        }

        return true;
    }

    public function cloAction()
    {
        $this->runCheck('config_clo');

        $current_date = date('Ymd');
        $content = $this->s_config->get('clo');
        for ($i = 0; $i < 3; $i++) {

            $date = date('Ymd', strtotime("+ $i day"));
            $pack = $this->s_clo->search(['no_date' => $date]);
            // if (!empty($pack) && $date == $current_date) {
            //     continue;
            // }
            $dkMinDate = $date . date('Hi00', $content['min_dk_time']);
            $dkMaxDate = $date . date('Hi00', $content['max_dk_time']);

            $add = [
                'no_date' => $date,
                'no_time' => strtotime($date),
                'apr' => $content['apr'],
                'min_dk_time' => strtotime($dkMinDate),
                'max_dk_time' => strtotime($dkMaxDate),
                'auto_add_num' => $content['auto_add_num'],
                'auto_add_user_money' => $content['auto_add_user_money'],
                'min_money' => $content['min_money'],
                // 'not_apr' => $content['not_apr'],
            ];

            if (!empty($pack) && $this->s_clo->update($pack['id'], $add)) {
                $this->logs(['msg' => '更新成功', 'date' => $date, 'update' => $add]);
            }

            if (empty($pack) && $this->s_clo->save($add)) {
                $this->logs(['msg' => '生成成功', 'date' => $date, 'add' => $add]);
            }
        }

        return true;
    }
}