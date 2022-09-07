<?php

class AnwserTask extends \C\L\Task
{
    public function addAction()
    {
        // $this->runCheck('anwser_add', 60);

        $config = $this->s_config->get('anwser');
        if ($config['begin_time'] > time() || time() > $config['begin_time'] + $config['stop_min'] * 60) {
            return true;
        }

        $config = $this->s_config->search(['type' => 'anwser']);
        $content = json_decode($config['content'], true);

        if ($content['add_num'] > 0) {
            $content['anw_sum'] += $content['add_num'];
        }

        if ($content['add_ok_num'] > 0) {
            $content['anw_ok_sum'] += $content['add_ok_num'];
        }

        $s = $this->s_config->update($config['id'], ['content' => json_encode($content)]);
        $this->logs(['msg' => '成功', 's' => $s]);
        // return true;
    }

    public function moneyAction()
    {
        $this->runCheck('anwser_money', 60);

        if ($this->cache->get('anwser_send') == 'D') {
            $this->cache->set('anwser_send', 'S');

            $config = $this->s_config->get('anwser');
            $uanws = $this->s_uanw->searchAll(['no' => $config['no'], 'status' => 'Y']);

            $anw_ok_num = $this->s_uanw->getCount(['status' => 'Y', 'no' => $config['no']]) + $config['anw_ok_sum'];
            $money = bcdiv($config['money'], $anw_ok_num, 2);

            foreach ($uanws as $anw) {

                if (!$this->s_funds->search(['cid' => $anw['id'], 'stype' => 'anwser'])) {

                    if($this->s_funds->add($anw['uid'], $money, 'money', 'anwser', '答题奖励', $anw['id'])){
                        $this->logs(['msg' => '成功', 'id' => $anw['id']]);
                    }
                }

            }

            $this->cache->set('anwser_send', '');
        }

        return true;
    }
}