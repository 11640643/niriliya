<?php

namespace C\S\User;

use C\L\Service;

class Bank extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserBank();
    }

    public function getStatusConfig()
    {
        return [
            'is_default' => [
                'N' => '默认',
                'Y' => '非默认'
            ],
            'code' => [
                'ICBC' => '中国工商银行',
                'ABC' => '中国农业银行',
                'CMBCHINA' => '招商银行',
                'CCB' => '中国建设银行',
                'BOCO' => '交通银行',
                'BOC' => '中国银行',
                'CMBC' => '中国民生银行',
                'CGB' => '广发银行',
                'HXB' => '华夏银行',
                'POST' => '中国邮政储蓄银行',
                'ECITIC' => '中信银行',
                'CEB' => '中国光大银行',
                'PINGAN' => '平安银行',
                'CIB' => '兴业银行',
                'SPDB' => '浦发银行',
                'BCCB' => '北京银行',
                'BON' => '南京银行',
                'NBCB' => '宁波银行',
                'BEA' => '东亚银行',
                'SRCB' => '上海农商银行',
                'SHB' => '上海银行',
                'CZB' => '浙商银行',
                'TCCB' => '天津银行',
                'HSBANK' => '徽商银行',
                'HFBANK' => '恒丰银行',
                'CBHB' => '渤海银行',
                'JSB' => '江苏银行',
                'CITI' => '花旗银行',
                'THX' => '贵阳银行',
                'HANGSENGBANK' => '恒生银行',
                'GDNYBANK' => '南粤银行',
                'LZBANK' => '兰州银行'
            ]
        ];
    }

    public function setDefault($uid, $id)
    {
        $bank = $this->search($id);
        if (empty($bank) || $bank['is_default'] == 'Y') {
            return true;
        }

        $this->update($uid, ['is_default' => 'N'], 'uid');
        if ($this->update($id, ['is_default' => 'Y'])) {
            return true;
        }

        return false;
    }

    public function getDefault($uid)
    {
        $bank = $this->search(['uid' => $uid, 'is_default' => 'Y']);
        if (!empty($bank)) {
            return $bank;
        }

        return $this->search($uid, 'uid');
    }

    public function getBankStar($card = '')
    {
        return substr($card, 0, 4) . ' **** **** ' . substr($card, -4, 4);
    }

}
