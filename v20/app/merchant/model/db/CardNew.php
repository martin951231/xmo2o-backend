<?php
/**
 * 商家会员卡model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/09 14:08
 */

namespace app\merchant\model\db;
use think\Model;
class CardNew extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
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