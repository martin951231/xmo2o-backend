<?php
/**
 * 图文管理分类controller
 * Author: wangchen
 * Date Time: 2021/05/21
 */

namespace app\atlas\controller\api;

use app\atlas\model\service\AtlasCategoryService;
use app\merchant\model\service\store\MerchantCategoryService;

class AtlasCategoryController extends ApiBaseController
{
    /**
     * 根据一级分类cat_id获取二级分类列表
     */
    public function getAtlasArticleSecond()
    {
        $cat_id = $this->request->param("cat_id", "0", "intval");
        if($cat_id < 1){
            return api_output(1000, []);
        }
        $res = (new AtlasCategoryService())->getAtlasArticleSecond($cat_id);
        $aotu = [['cat_id'=>0,'cat_name'=>'全部']];
        $result = array_merge($aotu,$res);
        return api_output(1000, $result);
    }

    /**
     * 获得图文管理分类列表
     */
    public function getAtlasCategoryList(){
        $result = (new AtlasCategoryService())->getAtlasCategoryList();
        if(!$result) {
            $res = [];
        }else{
            $arr = array_merge($this->aryTree2($result));
            foreach($arr as $v){
                if(!empty($v['children'])){
                    $lists = array_merge($v['children']);
                    $v['children'] = $lists;
                }
                $res[] = $v;
            }
        }
        return api_output(1000, $res);
    }

    /**
     * 获得图文管理一条分类数据
     */
    public function getAtlasCategoryInfo(){
        $cat_id = $this->request->param("cat_id", 0, "intval");
        if(!$cat_id){
            $res = array(
                'cat_id' => 0,
                'cat_fid' => 0,
                'cat_name' => '',
                'cat_status' => 1,
            );
            return api_output(1000, $res);
        }
        $result = (new AtlasCategoryService())->getAtlasCategoryInfo($cat_id);
        return api_output(1000, $result);
    }

    /**
     * 图文管理分类修改/添加
     */
    public function getAtlasCategoryCreate(){
        $cat_id = $this->request->param("cat_id", 0, "intval");
        $cat_fid = $this->request->param("cat_fid", 0, "intval");
        $cat_name = $this->request->param("cat_name");
        $cat_status = $this->request->param("cat_status", 0, "intval");
        $result = (new AtlasCategoryService())->getAtlasCategoryCreate($cat_id, $cat_fid, $cat_name, $cat_status);
        return api_output(1000, $result);
    }

    /**
     * 图文管理分类删除
     */
    public function getAtlasCategoryDel(){
        $cat_id = $this->request->param("cat_id", 0, "intval");
        $result = (new AtlasCategoryService())->getAtlasCategoryDel($cat_id);
        return api_output(1000, $result);
    }

    /**
     * 获得图文管理分类标签
     */
    public function atlasCategoryList(){
        $cat_id = $this->request->param("cat_id", 0, "intval");
        if(empty($cat_id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $result = (new AtlasCategoryService())->atlasCategoryList($cat_id);
        return api_output(1000, $result);
    }

    /**
     * 利用递归法获取无限极类别的树状数组
     * @param array $ary 数据库读取数组
     * @param int $cat_fid 父级ID(顶级类别的cat_fid为0)
     * @param int $level 返回的树状层级
     * @param int $i 层级起始值
     * @return array 返回树状数组
     */
    function aryTree2($ary = array(), $cat_fid = 0, $level = 3, $i = 1){
        $arr = array();
        foreach($ary as $rs){
            if($rs['cat_fid'] == $cat_fid){
                if($i <= $level){
                    $arr[$rs['cat_id']] = $rs;
                }else{
                    break;
                }
                $n = $i;
                $n++;
                $lists = $this->aryTree2($ary, $rs['cat_id'],  $level, $n);
                empty($lists) OR $arr[$rs['cat_id']]['children'] = $lists;
            }else{
                continue;
            }
        }
        return $arr;
    }

    /**
     * 利用递归法获取无限极类别的树状数组
     * @param array $ary 数据库读取数组
     * @param int $cat_fid 父级ID(顶级类别的cat_fid为0)
     * @param int $level 返回的树状层级
     * @param int $i 层级起始值
     * @return array 返回树状数组
     */
    function aryTree($ary = array(), $cat_fid = 0, $level = 3, $i = 1){
        $arr = array();
        foreach($ary as $rs){
            if($rs['cat_fid'] == $cat_fid){
                if($i <= $level){
                    $arr[$rs['cat_id']] = $rs;
                    $arr[$rs['cat_id']]['lists'] = [];
                }else{
                    break;
                }
                $n = $i;
                $n++;
                $lists = $this->aryTree($ary, $rs['cat_id'],  $level, $n);
                empty($lists) OR $arr[$rs['cat_id']]['lists'] = $lists;
            }else{
                continue;
            }
        }
        return $arr;
    }
}
