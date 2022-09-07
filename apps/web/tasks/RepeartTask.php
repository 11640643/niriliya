<?php
class RepeartTask extends \C\L\Task
{
    public function repeartAction()
    {   
        $start = 3862;
        $end = 3949;
        for($i=$start;$i<=$end;$i++){
            // echo $i."\r\n";
            $this->cache->rPush('item_reward',$i);
        }
    }
}    