<?php

namespace User;

use C\L\WebUserController;

class JzController extends WebUserController
{
    protected function init()
    {
        $this->hideKeys = [
            'is_delete'
        ];
        $this->service = $this->s_jz;

    }

    public function beforeSearch()
    {
        $this->params['data']['status'] = 'Y';
        return true;
    }

    public function afterView(&$data)
    {
        $web = $this->s_config->get('jz');

        $codeFilePath = $this->s_user->getShareQrCode($this->uid);
        $bgFilePath = APP_PUBLIC . $web['image'];
        if(!file_exists($bgFilePath)){
            $this->error($this->translate['contact_kefu']);
        }

        $bgc = imagecreatefromstring(file_get_contents($bgFilePath));
        $qrc = imagecreatefromstring(file_get_contents($codeFilePath));

        //将水印图片复制到目标图片上
        imagecopymerge($bgc, $qrc, 309, 965, 0, 0, 231, 231, 100);
        //生成图片
        $haibaoPath = APP_PUBLIC . '/upload/haibao/';
        if (!file_exists($haibaoPath)) {
            if (!mkdir($haibaoPath, 0777, true)) {
                $this->error($this->translate['floder_err']);
            }
        }

        $filePath = '/upload/haibao/' . $this->uid . '.png';
        imagepng($bgc, APP_PUBLIC . $filePath);
        imagedestroy($bgc);
        imagedestroy($qrc);

        $md5 = @md5_file(APP_PUBLIC . '/../../sd-h5/dist/index.html');
        $data['view']['down_image'] = $filePath . '?x=' . $md5 . time();
        return true;
    }

    public function uploadAction()
    {   
        $id = $this->getValue('id', true, 'int');
        $uid = $this->uid;
        $date = date("Ymd");
        $key = "totay:share:$id:$uid:$date";
        $jz = $this->s_jz->search($id);
        if (!$jz || $jz['is_delete']==1 || $jz['status']=='N') {
            $this->error($this->translate['share_empty']);
        }
        $setnxkey =  "totay:share:setnx:$id:$uid:$date";
        if(!$this->cache->setnx($setnxkey,1,3600)){
            $this->error($this->translate['access_limit']);
        }    
        $ttl =$this->cache->ttl($key);
        if($ttl>-2){
            $this->error($this->translate['share_perday']);
        }
        if (!$this->request->hasFiles()) {
            $this->error($this->translate['upload_empty']);
        }

        $files = $this->request->getUploadedFiles();

        $file = $files[0];
        $extName = $file->getExtension();
        if (!in_array($extName, ['jpg', 'png', 'jpeg', 'gif'])) {
            $this->error($this->translate['format_error']);
        }

        $path = '/upload/' . date('Y/m/d');
        $filePath = APP_PUBLIC . $path;
        $fileNme = date('YmdHis') . '_' . substr(md5($file->getName() . rand(10, 99)), 0, 8) . '.' . $extName;
        if (!file_exists($filePath)) {
            if (!@mkdir($filePath, 0755, true)) {
                $this->error($this->translate['file_sys_err']);
            }
        }
        // $jz = $this->s_jz->search($id);
        // if (!$jz || $jz['is_delete']==1 || $jz['status']=='N') {
        //     $this->error('分享不存在');
        // }
        $jz_config = $this->s_config->get('jz');
        $add = [
            'cid' => $jz['id'],
            'uid' => $this->uid,
            'file' => $path . '/' . $fileNme,
            'money' => $jz['money'],
            'status'=>isset($jz_config['review']) && $jz_config['review'] == 2 ? 'Y':'D',
        ];
        
        if ($id = $this->s_jzlist->save($add)) {
            $file->moveTo(
                $filePath . '/' . $fileNme
            );
            if(isset($jz_config['review']) && $jz_config['review'] == 2){
                if (!$this->s_funds->add($this->uid, $jz['money'], 'manure', 'image_share', $this->translate['share-screentshots'], $id)) {
                    throw new \Exception($this->translate['fin_add_error']);
                }
            }
            $this->cache->set($key,1);
            $this->cache->expire($key,86400);
            $this->success();
        }

        $this->error();

    }
}


