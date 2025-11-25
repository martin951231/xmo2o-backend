<?php
/**
 * 店铺service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/11 18:05
 */

namespace app\merchant\model\service;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\merchant\model\db\MerchantUserRelation as MerchantUserRelationModel;
class MerchantUserRelationService {
    public $merchantUserRelationModel = null;
    public function __construct()
    {
        $this->merchantUserRelationModel = new MerchantUserRelationModel();
    }

    /**
     * 用户收藏
     * @param $param
     * @return array
     */
    public function userCollect($param) {
        $data['type'] = $param['type'] ?? ''; // 收藏类型
        $uid = $param['uid'] ?? '0'; // 用户id

        $returnArr = [];// 返回数据

        if(empty($data['type']) || empty($uid)){
            throw new  \think\Exception(L_('缺少参数'),1001);
        }

        $data['uid'] = $uid; // 用户id
        switch ($data['type']) {
            case 'foodshop':
            case 'store':
            case 'shop':
            case 'mall':
                $data['store_id'] = $param['collect_id'];
                break;
            case 'mallgoods':
                $data['goods_id'] = $param['collect_id'];
                break;
            case 'group':
                $data['group_id'] = $param['collect_id'];
                break;
            case 'appoint':
                $data['appoint_id'] = $param['collect_id'];
                break;
            case 'mer':
                $data['mer_id'] = $param['collect_id'];
                break;
            default:
                throw new  \think\Exception(L_('参数收藏类型错误'),1003);
                break;
        }

        $info = $this->getOne($data);

        // 取消收藏
        if($info){
            if($this->merchantUserRelationModel->where($data)->delete()){
                $returnArr['msg'] = L_('取消收藏成功');
                return $returnArr;
            }else{
                throw new  \think\Exception(L_('取消收藏失败！请重试'),1003);
            }
        }

        // 收藏
        $data['dateline'] = time();
        $res = $this->add($data);
        if($res){
            $returnArr['msg'] = L_('收藏成功');
        }else{
            throw new  \think\Exception(L_('收藏失败！请重试'),1003);
        }

        return $returnArr;
    }

    /**
     * 获得用户商城收藏列表
     * @param $uid int 用户id
     * @param $type string 收藏类型
     * @param $param array 其他参数
     * @return array
     */
    public function getCollectList($uid,$type,$param = []){

        $merchantWxapp = $param['merchant_wxapp'] ?? '0'; // 是否商家小程序
        $merId = $param['mer_id'] ?? '0'; // 商家is
        $where = [
            'uid'=>$uid,
            'type'=>$type
        ];
        $collectList = $this->getSome($where);
        $returnArr = [
            'list' => []
        ];

        if (!$collectList) {
            return $returnArr;
        }

        switch ($type) {
            case 'store':
                if($merchantWxapp){
                    // 商家小程序餐饮店铺的收藏
                    $storeIdArray = array_column($collectList, 'store_id');
                    $where['store_id'] = $storeIdArray;
                    $merId && $where['mer_id'] = $merId;
                    $where['merchant_wxapp'] = $merchantWxapp;
                    $where['sort'] = 'juli';
                    $where['pageSize'] = 1000;
                    $return = (new MerchantStoreFoodshopService())->getStoreList($where);
                    $returnArr['list'] = $return['store_list'];
                    $returnArr['Currency_txt'] = cfg('Currency_txt');
                }
                break;
        }
        return $returnArr ? $returnArr : array();
    }

	/**
     * 获取一条数据
     * @param $where 
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return [];
        }

        $data = $this->merchantUserRelationModel->getOne($where);
        if(!$data) {
            return [];
        }
        
        return $data->toArray(); 
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->merchantUserRelationModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->merchantUserRelationModel->id;
        
    }   
    /**
     * 增加商家粉丝
     * @param $merId int 商家id
     * @return array
     */
    public function saveRelation($openid, $merId, $fromMerchant)
    {
        if (empty($openid)) return false;

        $where = [
            'openid' => $openid, 
            'mer_id' => $merId
        ];
        $relation = $this->getOne($where);
        if ($relation) {
            return false;
        }
        
        // 添加
        $relation = [
            'openid' => $openid, 
            'mer_id' => $merId, 
            'dateline' => time(), 
            'from_merchant' => $fromMerchant
        ];
        $this->add($relation);
        
        // 更新商家粉丝数
        $merchent = (new MerchantService())->getMerchantByMerId($merId);
        $data = [
            'fans_count' => $merchent['fans_count']+1,
        ];
        (new MerchantService())->updateByMerId($merId, $data);
        return true;
    }

    /**
     * 根据条件获取列表
     * @author hengtingmei
     * @date 2020/12/04
     */
    public function getSome($where = [], $field = true, $order=true, $page=0, $limit=0)
    {
        $res = $this->merchantUserRelationModel->getSome($where, $field, $order, $page, $limit);

        if(!$res){
            return [];
        }

        return $res->toArray();
    }


    /**
     * 用户是否收藏店铺
     * @param $uid
     * @param $storeId
     * @author: 张涛
     * @date: 2021/05/10
     */
    public function isCollect($uid, $storeId)
    {
        $isCollect = $this->merchantUserRelationModel->where(['uid' => $uid, 'store_id' => $storeId, 'type' => 'store'])->findOrEmpty()->isExists();
        return $isCollect;
    }
}