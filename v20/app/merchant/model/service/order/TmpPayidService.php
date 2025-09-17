<?php

/**
 * 用户支付码
 * Author: hengtingmei
 * Date Time: 2020/12/10 10:45
 */
namespace app\merchant\model\service\order;
use app\merchant\model\db\TmpPayid;
class TmpPayidService {
    public $tmpPayidModel = null;
    public function __construct()
    {
        $this->tmpPayidModel = new TmpPayid();
    }

    /**
     * 验证二维码是否有效并返回详情
     * @param $data array 数据
     * @return array
     */
    public function scanPayidCheck($payid){
        // 查找付款码
        $where = [
            'payid' => $payid
        ];
        $order = [
            'add_time' => 'DESC'
        ];
        $res = $this->getOne($where, true, $order);

        if(empty($res) || ($res['add_time']+60)<time()){
            throw new \think\Exception(L_("付款码错误或超时，请重新输入!"), 1003);
        }

        return $res;
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->tmpPayidModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->tmpPayidModel->id;

    }
    
    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array
     */
    public function getOne($where, $field = true, $order = []){
        $res = $this->tmpPayidModel->getOne($where,$field,$order);
        if(!$res) {
            return [];
        }
        return $res->toArray();
    }
}