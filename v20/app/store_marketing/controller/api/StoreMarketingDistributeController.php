<?php
/**
 * 店铺分销 
 * 
 */

namespace app\store_marketing\controller\api;

use app\store_marketing\model\service\StoreMarketingDistributeService;

class StoreMarketingDistributeController extends ApiBaseController
{
    /**
     * 获取分销店铺列表
     * 
     */
    public function getStoreList()
    { 
        $uid =$this->request->log_uid ?? 0;
        if($uid==0){
            return api_output_error(1002,"获取用户信息失败,请重新登录");
        }

        try{   
            $params = $this->request->param();  
            $data = (new StoreMarketingDistributeService())->getStoreList($uid, $params); 
            return api_output(0, $data, '获取成功'); 

        }catch(\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 分销店铺商品
     * 
     */
    public function getStoreGoods()
    {
        $uid = $this->request->log_uid ?? 0; 
        if($uid==0){
           return api_output_error(1002,"获取用户信息失败,请重新登录");
        }  
        try{   

            $params = $this->request->param();  

            $data = (new StoreMarketingDistributeService())->getStoreGoods($uid, $params); 
            return api_output(0, $data, '获取成功'); 

        }catch(\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 绑定店铺
     * 
     */
    public function personBindStore()
    { 
        $uid = $this->request->log_uid ?? 0;  
        if($uid==0){
           return api_output_error(1002,"获取用户信息失败,请重新登录");
        }  
        try{   

            $params = $this->request->param();  

            $data = (new StoreMarketingDistributeService())->personBindStore($uid, $params); 
            return api_output(0, $data, '请求成功'); 

        }catch(\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除绑定
     * 
     */
    public function delPersonStore()
    {
        $uid = $this->request->log_uid ?? 0;  
        if($uid==0){
           return api_output_error(1002,"获取用户信息失败,请重新登录");
        }  
        try{   

            $params = $this->request->param();
            $data = (new StoreMarketingDistributeService())->delPersonStore($uid, $params); 
            return api_output(0, $data, '删除成功'); 

        }catch(\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }
}