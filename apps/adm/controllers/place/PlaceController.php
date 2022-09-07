<?php

namespace Place;

use C\C\AdmController;
use Phalcon\Mvc\Model\Query;

class PlaceController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_place;
    }    
    /* 
     * 搜索前
     */
    protected function beforeGetlist()
    {
        return true;
    }
    /* 
     * 搜索前
     */
    protected function afterGetlist()
    {
        return true;
    }

    /**
     * 搜索
     * 
     */
    public function getlistAction()
    {

        $data = $this->s_place->findall();
        $this->success($data);
    }

    /**
     * 编辑
     */
     public function editAction($id=0)
    {   
        if ( \C\P\Helper::isPUT() ){
            if (empty($this->service) ) {
                $this->error();
            }

            $this->params = [
                'update' => [],
            ];
            $update = $this->getValue('data');
            $id = isset( $update['id'] )? $update['id'] :'' ;
            unset($update['id']);
            if (empty($id)) {
                $id = $this->params;
            }

            if (!$this->beforeUpdate($update)) {
                $this->error($this->translate['doerror']);
            }

            $data = ['id' => $id];
            if (empty($update)) {
                $this->success($data);
            }

            $update = $this->setUpdateVlaue2($update);
            unset($update['is_show_index_name']);
            unset($update['is_disable_name']);
            unset($update['channel_name']);
            unset($update['is_auth_name']);
            unset($update['freeze_login_time_date']);
            unset($update['last_login_time_date']);

            if (!$this->service->update($id, $update)) {
                $this->error($this->translate['nodata']);
            }

            if (!$this->afterUpdate($data)) {
                $this->error($this->translate['doerror']);
            }

            $this->success($data);
            
        }

        if (empty($this->service)) {
            $this->error();
        }
        if (!$this->beforeView()) {
            $this->error($this->translate['doerror']);
        }
        if (empty($id)) {
            $this->error($this->translate['nodata']);
        }
        if ( !is_numeric( $id )){ $this->error($this->translate['nodata']);}
        $view = $this->service->search($id);

        if (empty($view)) {
            $this->error($this->translate['nodata']);
        }

        $this->setHide($view);
        $this->setShow($view);
        $this->setStatusName($view);
        $this->autoTimeToDate($view);

        $data = [
            'view' => $view,
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterView($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }     

    /**
     * 添加
     */

    public function createAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'create' => [],
        ];


        $add = $_POST;
        $chars = md5(uniqid(mt_rand(), true));  
        $uuid = substr ( $chars, 0, 8 ) . '-'
                . substr ( $chars, 8, 4 ) . '-' 
                . substr ( $chars, 12, 4 ) . '-'
                . substr ( $chars, 16, 4 ) . '-'
                . substr ( $chars, 20, 12 );  
        $add['place_id'] = $uuid;
 

        if (!$this->beforeCreate($add)) {
            $this->error($this->translate['doerror']);
        }

        if (empty($add)) {
            $this->error();
        }

        if (!$id = $this->s_place->save($add)) {
            $this->error($this->translate['nodata']);
        }

        $data = ['id' => $id];
        if (!$this->afterCreate($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }    
}