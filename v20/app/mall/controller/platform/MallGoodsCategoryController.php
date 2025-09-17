<?php
/**
 * 平台分类管理
 * author 朱梦群
 * time   2020/9/7
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\MallCategoryService;
use app\mall\model\service\MallCategorySpecService;
use think\App;

class MallGoodsCategoryController extends AuthBaseController
{
    /**
     * 获取后台分类列表
     * @return \json
     */
    public function goodsCategoryList()
    {
        $pageSize = $this->request->param('pageSize', '', 'trim');
        $page = $this->request->param('page', '', 'trim');
        try {
            $GoodsCategory = new MallCategoryService();
            $arr = $GoodsCategory->goodsCategoryList($pageSize, $page);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @param:
     * @return :  array
     * @Desc:   编辑图片
     */
    public function saveImage()
    {
        $pic = $this->request->param('image', '', 'trim');
        $id = $this->request->param('cat_id', '', 'intval');
        $GoodsCategory = new MallCategoryService();
        try {
            $result = $GoodsCategory->saveImage($id, $pic);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param:
     * @return :  array
     * @Desc:   编辑排序
     */
    public function saveSort()
    {
        $sort = $this->request->param('sort', 0, 'intval');
        $id = $this->request->param('cat_id', '', 'intval');
        $GoodsCategory = new MallCategoryService();
        try {
            $result = $GoodsCategory->saveSort($id, $sort);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param:
     * @return :  array
     * @Desc:   编辑排序
     */
    public function saveStatus()
    {
        $status = $this->request->param('status', 0, 'intval');
        $id = $this->request->param('cat_id', '', 'intval');
        $GoodsCategory = new MallCategoryService();
        try {
            $result = $GoodsCategory->saveStatus($id, $status);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 添加或编辑分类
     * @return \json
     */
    public function addOrEditCategory()
    {
        $arr=$this->request->param();
        if (isset($arr['system_type'])) {
            unset($arr['system_type']);
        }
        $arr['create_time']=time();
        if(isset($arr['cat_id']) && $arr['cat_id'] > 0){
            unset($arr['cat_fid']);//如果修改某个分类，那就不能修改他的上级，因为前端没传递上级，这里会把上级改成0
        }
        try {
            $GoodsCategory = new MallCategoryService();
            $result = $GoodsCategory->addOrEditCategory($arr);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取编辑的分类
     * @return \json
     */
    public function getEditCategory()
    {
        $cat_id = $this->request->param('cat_id', '', 'intval');
        try {
            $GoodsCategory = new MallCategoryService();
            $arr = $GoodsCategory->getEditCategory($cat_id);
            return api_output(0, $arr, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除分类
     * @return \json
     */
    public function delCategory()
    {
        $cat_id = $this->request->param('cat_id', '', 'intval');
        $level = $this->request->param('level', '', 'intval');
        try {
            $GoodsCategory = new MallCategoryService();
            $arr = $GoodsCategory->delCategory($cat_id,$level);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 展示轮播图(点击列表中的 设置轮播图)
     * @return \json
     */
    public function bannerList()
    {
        $cat_id = $this->request->param('cat_id', '', 'intval');
        $type = $this->request->param('type', '', 'intval');
        try {
            $GoodsCategory = new MallCategoryService();
            $arr = $GoodsCategory->bannerList($cat_id, $type);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑/添加轮播图
     * @return \json
     */
    public function addOrEditBanner()
    {
        $arr = [
            'id' => $this->request->param('id', '', 'intval'),
            'cat_id' => $this->request->param('cat_id', '', 'intval'),
            'type' => $this->request->param('type', '', 'intval'),
            'image' => $this->request->param('image', '', 'trim'),
            'url' => $this->request->param('url', '', 'trim'),
            'sort' => $this->request->param('sort', 0, 'intval'),
            'create_time' => time()
        ];
        try {
            $GoodsCategory = new MallCategoryService();
            $result = $GoodsCategory->addOrEditBanner($arr);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除轮播图
     * @return \json
     */
    public function delBanner()
    {
        $id = $this->request->param('id', '', 'intval');
        try {
            $GoodsCategory = new MallCategoryService();
            $res = $GoodsCategory->delBanner($id);
            return api_output(0, $res, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取属性列表
     * @return \json
     */
    public function propertyList()
    {
        $cat_id = $this->request->param('cat_id', '', 'intval');
        try {
            $SpecService = new MallCategorySpecService();
            $arr = $SpecService->propertyList($cat_id);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 设置属性和属性值
     * @return \json
     */
    public function addOrEditProperty()
    {
        $param['cat_id'] = $this->request->param('cat_id', '', 'intval');
        $param['spec_list'] = $this->request->param('spec_list');
        try {
            $SpecService = new MallCategorySpecService();
            $result = $SpecService->addOrEditProperty($param);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}




//goodsCategoryList  分类展示列表
//addOrEditCategory  新增或编辑分类列表
//getEditCategory    获取编辑的分类
//delCategory        删除分类

//bannerList       展示轮播图(点击列表中的 设置轮播图)
//addOrEditBanner  编辑/添加轮播图
//delBanner        删除轮播图

//propertyList       属性列表
//addOrEditProperty  设置属性和属性值