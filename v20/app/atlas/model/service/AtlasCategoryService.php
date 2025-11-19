<?php
/**
 * 图文管理分类Service
 * Author: wangchen
 * Date Time: 2021/5/24
 */

namespace app\atlas\model\service;

use app\atlas\model\db\AtlasCategory;
use app\atlas\model\db\AtlasSpecial;
use app\atlas\model\db\AtlasSpecialOption;
use app\merchant\model\db\MerchantCategory;

class AtlasCategoryService {
    public $atlasCategoryModel = null;
    public function __construct()
    {
        $this->atlasCategoryModel = new AtlasCategory();
    }

    /**
     * 根据一级分类cat_id获取二级分类列表
     */
    public function getAtlasArticleSecond($cat_id){
        $order = 'cat_sort DESC, cat_id DESC';
        $where = ['cat_fid'=>$cat_id,'cat_status'=>1];
        $return = $this->atlasCategoryModel->getAtlasCategoryList($where, $order, 'cat_id,cat_name');
        return $return;
    }

    /**
     * 获得图文管理分类列表
     * @param $where array 条件
     * @return array
     */
    public function getAtlasCategoryList($where = []){
        $order = 'cat_sort DESC, cat_id DESC';
        $list = $this->atlasCategoryModel->getAtlasCategoryList($where, $order);
        $cate = (new MerchantCategory())->categoryOneList($where, $order);
        $return = array_merge($list, $cate);
        return $return;
    }

    /**
     * 获得图文管理一条分类数据
     */
    public function getAtlasCategoryInfo($cat_id){
        $where = array('cat_id'=>$cat_id);
        $result = $this->atlasCategoryModel->getAtlasCategoryInfo($where);
        return $result;
    }

    /**
     * 图文管理分类修改/添加
     */
    public function getAtlasCategoryCreate($cat_id, $cat_fid, $cat_name, $cat_status){
        $result = $this->atlasCategoryModel->getAtlasCategoryCreate($cat_id, $cat_fid, $cat_name, $cat_status);
        return $result;
    }
    
    /**
     * 图文管理分类删除
     */
    public function getAtlasCategoryDel($cat_id){
        $result = $this->atlasCategoryModel->getAtlasCategoryDel($cat_id);
        return $result;
    }

    /**
     * 获得图文管理分类标签
     * @param $where array 条件
     * @return array
     */
    public function atlasCategoryList($cat_id){
        $order = 'cat_sort DESC, cat_id DESC';
        $where = ['cat_id'=>$cat_id,'cat_status'=>1];
        $list = $this->atlasCategoryModel->atlasCategoryList($where, $order);
        if(!empty($list['cat_pic'])){
            $list['cat_pic'] = replace_file_domain($list['cat_pic']);
        }
        if($list){
            $wheres = ['cat_id'=>$cat_id,'status'=>0];
            $orders = 'sort DESC, id DESC';
            $cate = (new AtlasSpecial())->getAtlasSpecialList($wheres, $orders);
            $coption_all[] = ['id'=>0,'special_id'=>0,'name'=>'全部'];
            foreach($cate as $k=>$v){
                $option_list = (new AtlasSpecialOption())->getAtlasSpecialOptionList($v['id']);
                foreach($option_list as $ks=>$vs){
                    $option_list[$ks]['name'] =  $this->strsToArray($vs['name']);
                }
                $option = array_merge($coption_all, $option_list);
                $cate[$k]['option_list'] = $option;
            }
            $list['specual_list'] = $cate;
        }
        return $list;
    }

    /**
     * 数组转换
     */
    function strsToArray($strs) {
        $array = array();
        $strs = str_replace("\r\n", ',', $strs);
        $strs = str_replace("\n", ',', $strs);
        $strs = str_replace("\r", ',', $strs);
        $array = explode(',', $strs);
        return $array;
    }
}