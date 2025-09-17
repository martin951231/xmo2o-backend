<?php

/**
 * @Author: jjc
 * @Date:   2020-06-16 13:49:39
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-16 14:30:23
 */
namespace app\mall\model\service;

use app\mall\model\db\MallCategory;
use app\mall\model\db\MallCategorySpec as  MallCategorySpecModel;
use app\mall\model\service\MallCategorySpecValService as  MallCategorySpecValService;
use think\db\concern\Transaction;

class MallCategorySpecService{

	public $MallCategorySpecModel = null;

    public function __construct()
    {
        $this->MallCategorySpecModel = new MallCategorySpecModel();
    }

    public function getAll(){
    	$list = $this->MallCategorySpecModel->getNormalList();
    	
    	//获取所有规格属性值
    	$MallCategorySpecValService = new MallCategorySpecValService();
    	$valList = $MallCategorySpecValService->getAll();
    	if($valList&&$list){
    		foreach ($list as $key => $val) {
    			if(isset($valList[$val['cat_spec_id']])){
    				$list[$key]['list'] = $valList[$val['cat_spec_id']];
    			}
    		}
    	}

    	return dealTree($list,'cat_id');
    }

    //获取当前二级分类id下设定的属性与属性值
    public function getCategorySpecBySecondId($cat_id){
        //获取当前参数id
        $list = $this->MallCategorySpecModel->getSpecListById($cat_id);
        //获取当前分类下规格属性值
        $MallCategorySpecValService = new MallCategorySpecValService();
        //拼装数据
        foreach ($list as $key=>$val){
            $valList = $MallCategorySpecValService->getSpecVal($val['cat_spec_id']);
            $list[$key]['cate_list']=$valList;
        }
        $result['list']=$list;
        return $result;
    }

    /**
     * auth zhumengqun
     * 平台后台-添加属性
     * @param $param
     */
    public function addOrEditProperty($param)
    {
        if (empty($param['cat_id'])) {
            throw new \think\Exception('缺少必传参数cat_id');
        }
        $where = ['cat_id' => $param['cat_id'], 'is_del' => 0];
        $cat_spec_ids = $this->MallCategorySpecModel->getSpecBYCondition('cat_spec_id', '', $where);
        $MallCategorySpecValService = new MallCategorySpecValService();
        //先删除以前的属性和属性值
        if (!empty($cat_spec_ids)) {
            foreach ($cat_spec_ids as $val) {
                $where_spec_val = ['cat_spec_id' => $val['cat_spec_id'], 'is_del' => 0];
                $MallCategorySpecValService->delSpecVals($where_spec_val);
            }
            $this->MallCategorySpecModel->delSpec($where);
        }
        if (empty($param['spec_list'])) {
            //新增不允许为空值
           //hrow new \think\Exception('缺少必传参数');
           return true;
        }
        $sort = 9999;
        foreach ($param['spec_list'] as $val) {
            //新增属性
            $spec = [
                'cat_spec_name' => $val['cat_spec_name'],
                'is_must' => $val['is_must'],
                //'is_filter' => $val['is_filter'],
                'cat_id' => $param['cat_id'],
                'create_time' => time(),
                'sort'=>$sort
            ];
            $sort--;
            $spec_id = $this->MallCategorySpecModel->addSpec($spec);
            foreach ($val['property_list'] as $value) {
                $data = ['name' => $value, 'cat_spec_id' => $spec_id, 'create_time' => time()];
                $res2 = $MallCategorySpecValService->addSpecVals($data);
                if ($res2 === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
        }
        return true;
    }

    /**
     * auth zhumengqun
     * 平台后台-查看属性
     * @param $cat_id
     */
    public function propertyList($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('缺少必传参数cat_id');
        }
        $cat_info = (new MallCategory())->where(['cat_id'=>$cat_id])->findOrEmpty()->toArray();
        if (empty($cat_info)) {
            throw new \think\Exception('分类不存在！');
        }
        if($cat_info['level']==3){
            $cat_id = $cat_info['cat_fid']; //只有二级菜单才有属性
        }
        $where = ['cat_id' => $cat_id, 'is_del' => 0];
        $arr = $this->MallCategorySpecModel->getSpecBYCondition('sort DESC', true, $where);
        $MallCategorySpecValService = new MallCategorySpecValService();
        if (!empty($arr)) {
            foreach ($arr as $val) {
                $where_spec_val = ['cat_spec_id' => $val['cat_spec_id'], 'is_del' => 0];
                $arr_spec_val = $MallCategorySpecValService->getSpecVals($where_spec_val);
                $spec_list[] = [
                    'cat_id'=>$val['cat_id'],
                    'cat_spec_id'=>$val['cat_spec_id'],
                    'cat_spec_name' => $val['cat_spec_name'],
                    'is_must' => $val['is_must'],
                    //'is_filter' => $val['is_filter'],
                    'property_list' => $arr_spec_val,
                ];
            }
            return $spec_list;
        } else {
            return [];
        }
    }

}