<?php


namespace app\merchant\model\db;


use think\Model;

class MerchantStoreGiveCouponLog extends Model
{
    public function getList($where,$field='',$page=0,$pageSize=10){
        if(!$field){
            $field = 'l.*,c.discount,u.nickname,u.phone';
        }
        $query = $this->alias('l')->field($field)
            ->join('card_new_coupon c','l.coupon_id = c.coupon_id')
            ->join('user u','u.uid = l.uid','left')
            ->where($where)
            ->order('c.coupon_id desc,l.id desc');
        if($page>0){
            $list = $query->paginate($pageSize)->toArray();
        }else{
            $list = $query->select()->toArray();
        }
        return $list;
    }

    public function userCouponCount($where,$field=''){
        $data = $this->alias('l')->field($field)
            ->join('card_new_coupon c','l.coupon_id = c.coupon_id')
            ->join('user u','u.uid = l.uid','left')
            ->where($where)
            ->find()
            ->toArray();

        return $data;
    }

    public function userUseCouponCount($where,$field=''){
        $data = $this->alias('l')->field($field)
            ->join('card_new_coupon c','l.coupon_id = c.coupon_id')
            ->join('card_new_coupon_use_list ul','ul.type = 0 and ul.coupon_id = c.coupon_id')
            ->join('user u','u.uid = ul.uid','left')
            ->where($where)
            ->find()
            ->toArray();

        return $data;
    }

    public function getExpireList($where,$field=''){
        if(!$field){
            $field = 'l.*,u.nickname,u.phone,u.openid,count(h.coupon_id) as h_num,c.name as coupon_name';
        }
        $list = $this->alias('l')->field($field)
            ->join('user u','u.uid = l.uid')
            ->join('card_new_coupon_hadpull h','h.uid = l.uid and h.coupon_id = l.coupon_id')
            ->join('card_new_coupon c','l.coupon_id = c.coupon_id')
            ->where($where)
            ->order('l.id asc')
            ->group('l.id,l.coupon_id')
            ->select()->toArray();
        
        return $list;
    }
}