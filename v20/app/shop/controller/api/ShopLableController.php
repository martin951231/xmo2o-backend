<?php
/**
 * 店铺分类控制器
 * Created by subline.
 * Author: wangchen
 * Date Time: 2021/03/08 10:44
 */

namespace app\shop\controller\api;

use app\shop\model\service\lable\ShopCategoryService;
use app\shop\model\service\lable\ShopCategoryRelationService;
use app\shop\model\service\lable\MerchantStoreShopService;

class ShopLableController {

    /**
     * 获取全部分类列表
     */
    public function lableList(){

        $shopCategoryService = new ShopCategoryService();
        $shopCategoryRelationService = new ShopCategoryRelationService();

        // 分类列表
        // $rs = $shopCategoryService->getLableListService('cat_id,cat_fid,cat_name,cat_pic,cat_url');
        $rs = $shopCategoryService->getLableListService();
        foreach($rs as $v){
            $v['count'] = $shopCategoryRelationService->getShopNumerService($v['cat_id'], $v['cat_fid']);
           $lableList[] = $v;
        }
        $lableList = $this->arrayTree($lableList);

        return api_output(0, $lableList);
    }

    
    /**
     * 获取指定分类店铺列表
     */
    public function twoLableList($cat_id){

        $shopCategoryService = new ShopCategoryService();
        $shopCategoryRelationService = new ShopCategoryRelationService();
        $merchantStoreShopService = new MerchantStoreShopService();

        // 指定分类
        // $lable = $shopCategoryService->getOneLableService($cat_id,'cat_id,cat_fid,cat_name,cat_pic,cat_url');
        $lable = $shopCategoryService->getOneLableService($cat_id);

        // 店铺id列表
        $storeidlist = $shopCategoryRelationService->getShopStoreIdService($lable['cat_id'], $lable['cat_fid'],'store_id');
        
        // 店铺列表
        $shoplist =  $merchantStoreShopService->getShopListService($storeidlist);

        $lable['shoplist'] = $shoplist;

        return api_output(0, $lable);
    }



   /**
     * 利用递归法获取无限极类别的树状数组
     * @param array $ary 数据库读取数组
     * @param int $cat_fid 父级ID(顶级类别的cat_fid为0)
     * @param int $level 返回的树状层级
     * @param int $i 层级起始值
     * @return array 返回树状数组
     */

    public function arrayTree($ary = array(), $cat_fid = 0, $level = 2, $i = 1){

        $data_array = array();

        foreach($ary as $rs){
            $rs['list'] = array();

            if($rs['cat_fid'] == $cat_fid){
                if($i <= $level){
                    $data_array[$rs['cat_id']] = $rs;
                }else{
                    break;
                }
                $n = $i;
                $n++;
                $sub = $this->arrayTree($ary, $rs['cat_id'],  $level, $n);
                empty($sub) OR $data_array[$rs['cat_id']]['list'] = $sub;
            }else{
                continue;
            }
        }

        return $data_array;
    }

}
