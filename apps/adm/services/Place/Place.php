<?php

namespace C\S\Place;

use C\L\Service;

class Place extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\Place();
    }
    public function findall(){
        // 上个月
        $prev_month_start_date = date ('Y-m-01', strtotime ('-1 month'));
        $prev_month_end_date = date ('Y-m-t', strtotime ('-1 month'));

        // 当前月
        $current_month_start_date = date ('Y-m-01');
        $current_month_end_date = date ('Y-m-t');

        // 今天
        $today_date = date('Y-m-d');

        // 昨天
        $yesterday_date = date('Y-m-d',  strtotime ('-1 day') );

        $sql = "select count(id) as total from place ";
        $total =  $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $sql = "select * from place  limit 1000";
        $data =  $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
   
        foreach ($data as $key => &$value) {
            // 总计
            $sql = " SELECT count(id) as total  from user_place WHERE place_id='{$value['place_id']}' ";

            $data1 = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $value['total_registerd_num'] = $data1[0]['total'];

            // 当月
            $sql = " SELECT count(id) as total   from user_place WHERE place_id='{$value['place_id']}'  AND  FROM_UNIXTIME(addtime,'%Y-%m-%d')  Between '{$current_month_start_date}' AND '{$current_month_end_date}' ";
            $data1 = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $value['current_month_registerd_num'] = $data1[0]['total'];        

            // 上个月
            $sql = " SELECT count(id) as total   from user_place WHERE place_id='{$value['place_id']}'  AND  FROM_UNIXTIME(addtime,'%Y-%m-%d')  Between '{$prev_month_start_date}' AND '{$prev_month_end_date}' ";
            $data1 = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $value['prev_month_registerd_num'] = $data1[0]['total'];        

            
            $sql = " SELECT count(id) as total   from user_place WHERE place_id='{$value['place_id']}'  AND  FROM_UNIXTIME(addtime,'%Y-%m-%d')='{$today_date}' ";
            $data1 = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $value['today_registerd_num'] = $data1[0]['total'];  

            $sql = " SELECT count(id) as total   from user_place WHERE place_id='{$value['place_id']}'  AND  FROM_UNIXTIME(addtime,'%Y-%m-%d')='{$yesterday_date}' ";
            $data1 = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $value['yesterday_registerd_num'] = $data1[0]['total'];
            $value['place_id'] = 'https://www.cxfvqor.com/?qudao=' . $value['place_id'] ;                                        
        }



        $result = [
            'count' =>  count($data1),
            'page_num' => 1000,
            'page_current' => 1,
            'config' =>[],
            'list' =>$data,
        ];
        return $result;
    }
}