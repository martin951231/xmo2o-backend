<?php
/**
 * 商家会员卡记录model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/15 10:14
 */

namespace app\merchant\model\db;
use think\Model;
class CardNewRechargeOrder extends Model {
    /**
     * 根据merId获取商家
     * @param $merId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCardByMerId($merId) {
        if(empty($merId)) {
             return false;
        }
        
        $where = [
            'mer_id' => $merId
        ];

        $result = $this->where($where)->find();
        return $result;
    }
}