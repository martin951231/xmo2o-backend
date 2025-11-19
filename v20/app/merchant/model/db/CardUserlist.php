<?php
/**
 * 商家会员卡用户领取model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/09 14:17
 */

namespace app\merchant\model\db;
use think\Model;
class CardUserlist extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据merId，uid获取用户会员卡
     * @param $merId int 商家id
     * @param $uid int 用户uid
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCardByUidAndMerId($merId, $uid) {
        if(empty($merId) || empty($uid)){
            return false;
        }
        
        $where = [
            'mer_id' => $merId,
            'uid' => $uid,
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOneAndUser($where) {
        if(!$where){
            return null;
        }

        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('uc')
                        ->field('uc.id,uc.card_id,uc.mer_id,uc.uid,uc.card_money,uc.card_money_give,uc.card_score,uc.qrcode_id,uc.physical_id,uc.add_time,uc.status,uc.ticket ,uc.gid,uc.wx_card_code,uc.cancel_wx,uc.name,uc.sex,uc.remark,uc.diy_field_select,uc.diy_field,u.phone,u.nickname,u.truename,u.score_count')
                        ->where($where)
                        ->leftJoin($prefix.'user u','u.uid=uc.uid')
                        ->find();
        return $result;
    }
}