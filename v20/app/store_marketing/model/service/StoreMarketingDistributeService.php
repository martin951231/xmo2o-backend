<?php
/**
 * 店铺分销
 * 
 */
namespace app\store_marketing\model\service;

use app\group\model\db\GroupStore;
use app\mall\model\db\MallGoods;
use app\store_marketing\model\db\StoreMarketingPerson;
use app\store_marketing\model\db\storeMarketingPersonStore; 
use app\group\model\service\GroupImageService;

class StoreMarketingDistributeService
{
    /**
     * 获取分销人员绑定店铺列表
     */ 
    public function getStoreList($uid, $params)
    {  
		$pageSize = $params['pageSize'] ?? 10;
        
        $person_id = (new storeMarketingPerson())->getFieldByUid($uid, 'id'); 

        if(!$person_id){
            //分销人员不存在
            throw new \think\Exception("");
        } 
        $list = (new storeMarketingPersonStore())->getPersonStoreList($person_id, $pageSize);
        $site_url = cfg('site_url');
        $list->each(function($item, $key) use($site_url){
            if(strpos($item['pic_info'], 'http') === false){ 
                $item['pic_info'] = $site_url . $item['pic_info']; 
            } 
            $item['group_main_url'] = $item['have_group'] ? $site_url.'/packapp/platn/pages/store/v1/home/index?store_id='.$item['store_id'] : '';
            $item['goods_main_url'] = $item['have_mall'] ? $site_url.'/packapp/plat/pages/shopmall_third/store_home/index?store_id='.$item['store_id'] : '';
            unset($item['have_group']);
            unset($item['have_mall']);
            return $item;
        }); 

        return $list;
    }

    /**
     * 分销店铺商品
     * 
     */
    public function getStoreGoods($uid, $params)
    { 
        $store_id = $params['store_id'];
        if(!$store_id){
            throw new \think\Exception("店铺id不能为空");
        }
        
        $pageSize = $params['pageSize'] ?? 10;
        $person_id = (new storeMarketingPerson())->getFieldByUid($uid, 'id'); 

        if(!$person_id){
            throw new \think\Exception("");
        } 

        if(!(new storeMarketingPersonStore())->checkPersonOfStore($person_id, $store_id)){
            throw new \think\Exception("此分销人员未绑定该店铺！");
        } 

        //图片处理类
        $groupImageService = new GroupImageService();

        $data = array();
        $data['goods_list'] = (new MallGoods())->getDistributeGoodsList($store_id, $pageSize)->each(function($item, $key){
            $item['pay_num'] = 0; //已售数量
            $item['image'] = cfg('site_url') . $item['image'];
            return $item;
        });
        $data['group_list'] = (new GroupStore())->getDistributeGroupList($store_id, $pageSize)->each(function($item, $key) use($groupImageService){
            $item['pay_num'] = 0; //已售数量
            $item['pic'] = $groupImageService->getImageByPath($item['pic']);
            return $item;
        });
        
        return $data;
       
    }

    /**
     * 绑定/解绑店铺
     * 
     */
    public function personBindStore($uid, $params)
    {
        $psid = (int)$params['psid'];
        if(!$psid){
            throw new \think\Exception("分销人关联店铺id不能为空");
        } 
         
        $person_id = (new storeMarketingPerson())->getFieldByUid($uid, 'id'); 

        if(!$person_id){
            throw new \think\Exception("没有此分销人员信息！");
        }  
       
        if(!(new storeMarketingPersonStore())->where('is_del', 0)->where('person_id', $person_id)->find($psid)){
            throw new \think\Exception("记录不存在！");
        }
        
        $info = (new storeMarketingPersonStore())->personBindStore($psid);
        
        $data['status'] = $info->status;
        $data['msg'] = $info->status == 1 ? '已解绑' : '已绑定';

        return $data;
    }

    /**
     * 删除绑定
     * 
     */
    public function delPersonStore($uid, $params)
    {
        $psid = (int)$params['psid'];
        if(!$psid){
            throw new \think\Exception("分销人关联店铺id不能为空");
        }

        $person_id = (new storeMarketingPerson())->getFieldByUid($uid, 'id'); 

        if(!$person_id){
            throw new \think\Exception("没有此分销人员信息！");
        }  

        if(!(new storeMarketingPersonStore())->where('is_del', 0)->where('person_id', $person_id)->find($psid)){
            throw new \think\Exception("记录不存在！");
        }

        if(!(new storeMarketingPersonStore())->delPersonStore($psid)){
            throw new \think\Exception("操作失败，请稍后重试！");
        }
        
        return [];
    }

}