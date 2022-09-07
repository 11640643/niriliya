<?php

namespace User;

use C\C\AdmController;

class TopController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_user;
        $this->hideKeys = [
            'password', 'salt', 'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'freeze_login_time', 'last_login_time'
        ];
    }

    protected function beforeGetlist()
    {
        $keywordSearchValue = $this->getValue('keyword_search_value', false, 'string');
        if (!empty($keywordSearchValue)) {
            $user = $this->s_user->search(['mobile' => $keywordSearchValue]);
            $type = $this->getValue('search_type');
            if($type == 1){

                if (!empty($user)) {
                    $this->params['data']['uid'] = $user['t_uid'];
                }else{
                    $this->params['data']['uid'] = -1;
                }

            }else{
                if (!empty($user)) {
                    $this->params['data']['t_uid'] = $user['uid'];
                }else{
                    $this->params['data']['t_uid'] = -1;
                }
            }

        }

        return true;
    }

    public function getlistAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'data' => [],
            'data_type' => [],
            'columns' => [],
            'order' => '',
        ];

        if (empty($this->params['page_curren'])) {
            $this->params['page_curren'] = $this->getValue('page_curren', false, 'int') ?: 1;
        }
        if (empty($this->params['page_num'])) {
            $this->params['page_num'] = $this->getValue('page_num', false, 'int') ?: 10;
        }

        $this->setSearchParams();

        if (!$this->beforeGetlist()) {
            $this->error($this->translate['request_error']);
        }

        $data = empty($this->params['data']) ? [] : $this->params['data'];
        $dataType = empty($this->params['data_type']) ? [] : $this->params['data_type'];
        $columns = empty($this->params['columns']) ? [] : $this->params['columns'];
        $order = empty($this->params['order']) ? '' : $this->params['order'];
        
        $list = $this->service->searchPage($data, $dataType, $columns, $order, $this->params['page_curren'], $this->params['page_num']);
        $this->setHide($list);
        $this->setShow($list);
        $this->setStatusName($list);
        $this->setCategoryName($list);
        $this->autoTimeToDate($list);
        $data = [
            'list' => $list,
            'count' => $this->service->getCount($data, $dataType),
            'page_num' => $this->params['page_num'],
            'page_curren' => $this->params['page_curren'],
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterGetlist($data)) {
            $this->error($this->translate['request_error']);
        }

        $this->success($data);
    }

    protected function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$value) {
            $other = [
                'top_mobile' => $this->s_user->getTopMobile($value['t_uid']), //推荐人手机号
                'ds_money' => 0
            ];
            $value = array_merge($value, $other);
        }
        return true;
    }

}


