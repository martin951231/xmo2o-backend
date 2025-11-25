<?php
/**
 * 商家会员卡用户领取service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/09 14:15
 */

namespace app\merchant\model\service\card;
use app\merchant\model\db\CardUserlist as CardUserlistModel;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantStoreService;
use think\facade\Db;

class CardUserlistService {
    public $cardUserlistModel = null;
    public function __construct()
    {
        $this->cardUserlistModel = new CardUserlistModel();
    }
    
	/**
     * 根据merId，uid获取用户会员卡
     * @param $merId int 商家id
     * @param $uid int 用户uid
     * @return array
     */
    public function getUserCard($param, $uid) {
        if(empty($param)){
            return (object)[];
        }

        $keyword = $param['keyword'] ?? '';
        $storeId = $param['store_id'] ?? '';

        if (!$keyword) {
            return (object)[];
        }

        $store = (new MerchantStoreService())->getStoreByStoreId($storeId);
        $card = (new CardNewService())->getCardByMerId($store['mer_id']);
        if(empty($card)){
            throw new  \think\Exception(L_('商家会员卡不存在'));
        }
        if($card['status'] != 1){
            throw new  \think\Exception(L_('商家会员卡状态不可用'));
        }
        $keyword = addslashes($keyword);
        $where[] = ['uc.id' , 'exp', Db::raw('= "' . $keyword . '" OR u.phone = "'.$keyword.'"')];
        $where[] = ['uc.mer_id' , '=', $store['mer_id']];

        $userCard = $this->getOneAndUser($where);
        if(empty($userCard)){
            return '';
        }

        $return = [];
        if( $userCard ) {
            $return = array(
                'name' => $userCard['truename'] ? $userCard['truename'] : $userCard['nickname'],
                'sex' => $userCard['sex'] == 1 ? L_('男') : L_('女'),
                'phone' => $userCard['phone'],
                'uid' => $userCard['uid'],
            );

            $return['id'] = $userCard['id'];
            $return['uid'] = $userCard['uid'];
            $return['card_money'] = $userCard['card_money'] + $userCard['card_money_give'];
            $return['card_score'] = $userCard['card_score'];
            $return['physical_id'] = $userCard['physical_id'];
            $return['card_discount'] = $card['discount'];
            $return['score_count'] = $userCard['score_count'];
        }
        return $return;
    }

	/**
     * 根据merId，uid获取用户会员卡
     * @param $merId int 商家id
     * @param $uid int 用户uid
     * @return array
     */
    public function getCardByUidAndMerId($merId, $uid) {
        if(empty($merId) || empty($uid)){
           return [];
        }

        $card = $this->cardUserlistModel->getCardByUidAndMerId($merId, $uid);
        if(!$card) {
            return [];
        }

        return $card->toArray();
    }

    /**
     * 根据条件获取用户会员卡
     * @param $where array 
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return [];
        }

        $card = $this->cardUserlistModel->getOne($where);
        if(!$card) {
            return [];
        }
        
        return $card->toArray(); 
    }

    /**
     *
     * 获取会员卡信息
     * @param array $param 
     * @return array
     */
    public function getCardUserList($param) {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        
        $storeId = $param['store_id'] ?? 0;
        // 店铺id
        if($storeId){
            $store = (new MerchantStoreService())->getStoreByStoreId($storeId);
            $where[] = ['c.mer_id', '=', $store['mer_id']];
        }

        // 商家id
        if(isset($param['mer_id']) && $param['mer_id']){
            $where[] = ['c.mer_id', '=', $param['mer_id']];
        }

        // 商家id
        if(isset($param['keyword']) && $param['keyword']){
            $where[] = ['c.id', '=', $param['keyword']];
        }

        // 是否绑定用户
        if(isset($param['is_bind']) && $param['is_bind']){
            $where[] = ['c.uid', '>', 0];
        }

        // 排序
        $order = [
            'c.add_time' => 'desc',
            'c.id' => 'desc',
        ];

        $list = $this->cardUserlistModel->field('c.*,u.avatar,u.nickname')->alias('c')
                        ->leftJoin($this->cardUserlistModel->dbPrefix().'user u','u.uid=c.uid')
                        ->where($where)
                        ->group('u.uid')
                        ->order($order)
                        ->page($page,$pageSize)
                        ->select();
        if($list->isEmpty()){
            $returnArr['list'] = [];
            return $returnArr;
        }

        $list = $list->toArray();
        foreach($list as &$_user){
            $_user['deposit_num'] = 0;
            if(isset($param['is_deposit']) && $param['is_deposit']){// 返回寄存商品数量
                $where = [
                    'card_id' => $_user['id'],
                    'store_id' => $storeId,
                ];
                $_user['deposit_num'] = (new CardNewDepositGoodsBindUserService())->getSumCount($where, 'num');
            }
        }
       
        $returnArr['list'] = $list;
        return $returnArr;
    }

    /**
     *
     * 获取会员卡信息
     * @param where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0) {
        if(empty($where)){
            return [];
        }

        $card = $this->cardUserlistModel->getSome($where, $field,$order,$page,$limit);
        if(!$card) {
            return [];
        }

        return $card->toArray();
    }

    /**
     * 根据条件获取用户会员卡
     * @param $where array
     * @return array
     */
    public function getOneAndUser($where) {
        if(empty($where)){
           return [];
        }

        $card = $this->cardUserlistModel->getOneAndUser($where);
//        var_dump($this->cardUserlistModel->getLastSql());
        if(!$card) {
            return [];
        }

        return $card->toArray();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function addCard($data){
        
        $data['add_time'] = time();
        $data['status'] = 1;
        $result = $this->cardUserlistModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->cardUserlistModel->id;
        
    }
    
    /**
     * 更新数据
     * @param $data array 数据
     * @return array
     */
    public function updateByCondition($where, $data){
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->cardUserlistModel->where($where)->save($data);
        if(!$result) {
            return false;
        }
        return $result;
        
    } 

}