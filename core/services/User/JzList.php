<?php

namespace C\S\User;

use C\L\Service;

class JzList extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserJzList();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'D' => 'Xét duyệt',//'审核中',
                'Y' => '审核成功',
                'N' => $this->translate['auth_err'],
            ],
        ];
    }

    public function verify($id, $status)
    {
        try {

            $info = $this->search($id);
            if (empty($info) || $info['status'] != 'D') {
                throw new \Exception('当前订单不允许操作');
            }

            $update = [
                'status' => $status
            ];

            $this->di['db']->begin();

            if ($status == 'Y') {

                if (!$this->di['s_funds']->add($info['uid'], $info['money'], 'manure', 'image_share', "分享截图", $info['id'])) {
                    throw new \Exception('流水添加失败');
                }
            }

            if (!$this->update($id, $update)) {
                throw new \Exception('更新失败');
            }

            $this->di['db']->commit();
            return true;


        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }
}
