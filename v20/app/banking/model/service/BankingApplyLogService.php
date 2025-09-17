<?php
/**
 * 金融产品修改记录service
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\model\service;

use app\banking\model\db\BankingApplyLog;

class BankingApplyLogService {
    public $bankingApplyLogModel = null;
    public function __construct()
    {
        $this->bankingApplyLogModel = new BankingApplyLog();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $data['add_time'] = time();
        $id = $this->bankingApplyLogModel->add($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
}