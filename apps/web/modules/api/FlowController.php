<?php

namespace Api;

use C\L\Controller;

class FlowController extends Controller
{
    public function kuaishouAction()

    {
        $params = $this->request->get();

        $log = $this->log->set('flow_ks_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));
        $imeiMD5 = $this->getValue('imeiMD5');
        $idfaMD5 = $this->getValue('idfaMD5');
        $imei = $this->getValue('imei') ?? $imeiMD5;
        $idfa = $this->getValue('idfa') ?? $idfaMD5;
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }
        $os = 'android';
        $where  = ['imei'=>$imeiMD5];
        if(!empty($idfa)){
            $os = 'ios';
            $where  = ['idfa'=>$idfa];
        }
        $is_exists = $this->s_flowks->search($where);
        if($is_exists){
            $this->success($this->translate['access_success']);
        }
        $add = [
            'aid' => $this->getValue('aid'),
            'cid' => $this->getValue('cid'),
            'did' => $this->getValue('did'),
            'dname' => $this->getValue('dname'),
            'imei' => $imei,
            'idfa' => $idfa,
            'call_url' => $this->getValue('callback'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }

    public function shuabaoAction()
    {
   
            $params = $this->request->get();
            $log = $this->log->set('flow_shuabao_' . date('Ymd') . '.log');
            $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));
    
            $imiemd5 = $this->getValue('device_id');
            $imei =  $imiemd5 ?? $this->getValue('oaid');
            //$idfa = $this->getValue('idfa');
            if (empty($imei)) {
                $this->error($this->translate['no_empty']);
            }
    
            $os = 'android';
            if($this->getValue('device_type')=='IDFA'){
                $os = 'ios';
            }
     
            $add = [
                'aid' => $this->getValue('advert_id'),
                'oaid' => $this->getValue('oaid'),
                'imei' => $os=='android'?md5($imei):'',
                'idfa' => $os=='ios'?md5($imei):'',
                'call_url' => $this->getValue('callback_url'),
                'ip' => $this->getValue('ip'),
                'time' => $this->getValue('ts')??time(),
                'os' => $os,
                'type' => 'shuabao',
            ];
    
            if ($this->s_flowks->save($add)) {
                $this->success($this->translate['access_success']);
            }
    
            $this->error($this->translate['access_error']);
        }
    

    public function kuaishou2Action()
    {
        $params = $this->request->get();

        $log = $this->log->set('flow_ks2_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));
        $imeiMD5 = $this->getValue('imeiMD5');
        $idfaMD5 = $this->getValue('idfaMD5');
        $imei = $this->getValue('imei') ?? $imeiMD5;
        $idfa = $this->getValue('idfa') ?? $idfaMD5;
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }
        $os = 'android';
        $where  = ['imei'=>$imeiMD5];
        if(!empty($idfa)){
            $os = 'ios';
            $where  = ['idfa'=>$idfa];
        }
        $is_exists = $this->s_flowks->search($where);
        if($is_exists){
            $this->success($this->translate['access_success']);
        }
        $add = [
            'aid' => $this->getValue('aid'),
            'cid' => $this->getValue('cid'),
            'did' => $this->getValue('did'),
            'dname' => $this->getValue('dname'),
            'imei' => $imei,
            'idfa' => $idfa,
            'call_url' => $this->getValue('callback'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type'=>'kuaishou2',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }


    public function shandianAction()

    {
        $params = $this->request->get();

        $log = $this->log->set('flow_sd_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));
        $imei = $this->getValue('imei');
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }
        $os = 'android';
        $where  = ['imei'=>$imei];
        if(!empty($idfa)){
            $os = 'ios';
        }
        $is_exists = $this->s_flowks->search($where);
        if($is_exists){
            $this->success($this->translate['access_success']);
        }
        $add = [
            'aid' => $this->getValue('aid'),
            'did' => $this->getValue('did'),
            'imei' => $imei,
            'call_url' => $this->getValue('callback'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'shandianhezi',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }
        $this->error($this->translate['access_error']);
    }



    public function qutoutiaoAction()
    {

        $params = $this->request->get();

        $log = $this->log->set('flow_qtt_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $imei = $this->getValue('imeimd5');
        $idfa = $this->getValue('idfa');
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }

        $os = 'android';
        $where  = ['imei'=>$imeiMD5];
        if(!empty($idfa)){
            $os = 'ios';
            $where  = ['idfa'=>$idfa];
        }
        $is_exists = $this->s_flowks->search($where);
        if($is_exists){
            $this->success($this->translate['access_success']);
        }
        

        $add = [
            'aid' => $this->getValue('oaid'),
            'cid' => $this->getValue('cid'),
            // 'dname' => $this->getValue('dname'),
            'imei' => $imei,
            'idfa' => $idfa,
            'unit' => $this->getValue('unit'),
            'plan' => $this->getValue('plan'),    
            'call_url' => $this->getValue('callback_url'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'qutoutiao',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }



    public function qutoutiao001Action()
    {

        $params = $this->request->get();

        $log = $this->log->set('flow_qtt001_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $imei = $this->getValue('imeimd5');
        $idfa = $this->getValue('idfa');
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }

        $os = 'android';
        $where  = ['imei'=>$imeiMD5];
        if(!empty($idfa)){
            $os = 'ios';
            $where  = ['idfa'=>$idfa];
        }
        $is_exists = $this->s_flowks->search($where);
        if($is_exists){
            $this->success($this->translate['access_success']);
        }
        

        $add = [
            'aid' => $this->getValue('oaid'),
            'cid' => $this->getValue('cid'),
            // 'dname' => $this->getValue('dname'),
            'imei' => $imei,
            'idfa' => $idfa,
            'unit' => $this->getValue('unit'),
            'plan' => $this->getValue('plan'),    
            'call_url' => $this->getValue('callback_url'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'qutoutiao001',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }


    public function qutoutiao000Action()
    {

        $params = $this->request->get();

        $log = $this->log->set('flow_qtt002_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $imei = $this->getValue('imeimd5');
        $idfa = $this->getValue('idfa');
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }

        $os = 'android';
        $where  = ['imei'=>$imeiMD5];
        if(!empty($idfa)){
            $os = 'ios';
            $where  = ['idfa'=>$idfa];
        }
        $is_exists = $this->s_flowks->search($where);
        if($is_exists){
            $this->success($this->translate['access_success']);
        }
        

        $add = [
            'aid' => $this->getValue('oaid'),
            'cid' => $this->getValue('cid'),
            // 'dname' => $this->getValue('dname'),
            'imei' => $imei,
            'idfa' => $idfa,
            'unit' => $this->getValue('unit'),
            'plan' => $this->getValue('plan'),    
            'call_url' => $this->getValue('callback_url'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'qutoutiao000',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }



    public function juliangAction()
    {

        $params = $this->request->get();

        $log = $this->log->set('flow_juliang_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $imei = $this->getValue('imei');
        $idfa = $this->getValue('idfa');
        $oaid = $this->getValue('oaid');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }

        $os = 'android';
        if(!empty($idfa)){
            $os = 'ios';
        }

        $add = [
            'aid' => $this->getValue('aid'),
            'cid' => $this->getValue('cid'),
//            'unit' => $this->getValue('unit'),
//            'plan' => $this->getValue('plan'),
            'dname' => $this->getValue('dname'),
            'imei' => $imei,
            'idfa' => $idfa,
            'call_url' => $this->getValue('call_url'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'juliang',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }


    public function dongfangAction()
    {

        $params = $this->request->get();

        $log = $this->log->set('flow_dongfangtoutiao_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $imei = $this->getValue('imei');
        $idfa = $this->getValue('idfa');
        $oaid = $this->getValue('oaid');
        $os = $this->getValue('os');
        if (empty($imei) && empty($idfa) && empty($oaid)) {
            $this->error($this->translate['no_empty']);
        }
        $add = [
            'imei' => $imei,
            'idfa' => $idfa,
            'call_url' => $this->getValue('call_url'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'dongfang',
            'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }

    public function phenixAction()
    {
        $params = $this->request->get();
        $log = $this->log->set('flow_phenix_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $imei = $this->getValue('imei');
        $idfa = $this->getValue('idfa');
        // $oaid = $this->getValue('oaid');
        $os = $this->getValue('os') ?? $_GET['os'];
        if (empty($imei) && empty($idfa)) {
            $this->error($this->translate['no_empty']);
        }
        $add = [
            'imei' => $imei,
            'idfa' => $idfa,
            'call_url' => $this->getValue('call_url'),
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('time'), 0, 10),
            'os' => $os,
            'type' => 'phenix',
            // 'oaid'=>$oaid
        ];

        if ($this->s_flowks->save($add)) {
            $this->success($this->translate['access_success']);
        }

        $this->error($this->translate['access_error']);
    }
}