<?php

class SmsTask extends \C\L\Task
{
    public function sendAction()
    {
        // $this->runCheck('sms_send', 60);
        $this->isRun('sms send');
        $i = 0;
        while (!$i) {
            while($str = $this->cache->lPop('sms_list')){

                try{

                    $strJson = json_decode($str, true);
                    if(empty($strJson)){
                        throw new Exception('字符串为空');
                    }

                    $user = $this->s_user->search($strJson['uid']);
                    if(!$this->s_code->send($user['mobile'], $strJson['type'], $strJson['params'])){
                        throw new Exception($this->message->getSerMsg());
                    }

                    $this->logs(['msg' => '成功', 'str' => $str]);

                }catch (Exception $e){

                    $this->logs(['msg' => $e->getMessage(), 'str' => $str]);
                    exit;
                }

            }
            sleep(3);
        }
    }
}