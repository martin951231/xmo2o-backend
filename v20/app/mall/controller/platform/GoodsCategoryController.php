<?php
/**
 * 平台分类管理
 * author 朱梦群
 * time   2020/9/7
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\GoodsCategoryService;
use think\App;

class GoodsCategoryController extends AuthBaseController
{
    /**
     * 获取后台分类列表
     * @return \json
     */
    public function goodsCategoryList()
    {
        $pageSize = $this->request->param('pageSize', 10, 'trim');
        $page = $this->request->param('page', 1, 'trim');
        try {
            $GoodsCategory = new GoodsCategoryService();
            $arr = $GoodsCategory->goodsCategoryList($pageSize, $page);
            return api_output(0, $arr, "success");

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
        $arr = array();
        $arr = [
            'id' => $this->request->param('id', '', 'interval'),
            'fid' => $this->request->param('fid', '', 'interval'),
            'name' => $this->request->param('name', '', 'trim'),
            'status' => $this->request->param('status', 1, 'interval'),
            'image' => $this->request->param('logo', '', 'trim'),
            'url' => $this->request->param('url', '', 'trim'),
            'sort' => $this->request->param('sort', 0, 'interval')
        ];
        try {
            $GoodsCategory = new GoodsCategoryService();
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
        $id = $this->request->param('id', '', 'interval');
        try {
            $GoodsCategory = new GoodsCategoryService();
            $arr = $GoodsCategory->getEditCategory($id);
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
        $id = $this->request->param('id', '', 'interval');
        try {
            $GoodsCategory = new GoodsCategoryService();
            $arr = $GoodsCategory->delCategory($id);
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
        $cat_id = $this->request->param('cat_id', '', 'interval');
        $type = $this->request->param('type', '', 'interval');
        try {
            $GoodsCategory = new GoodsCategoryService();
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
        $arr = array();
        $arr = [
            'id' => $this->request->param('id', '', 'interval'),
            'cat_id' => $this->request->param('cat_id', '', 'interval'),
            'type' => $this->request->param('type', '', 'interval'),
            'image' => $this->request->file('logo'),
            'url' => $this->request->param('url', '', 'trim'),
            'sort' => $this->request->param('sort', 0, 'interval')
        ];
        try {
            $GoodsCategory = new GoodsCategoryService();
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
        $id = $this->request->param('id', '', 'interval');
        try {
            $GoodsCategory = new GoodsCategoryService();
            $arr = $GoodsCategory->delBanner($id);
            return api_output(0, $arr, "success");

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
        $cat_id = $this->request->param('cat_id', '', 'interval');
        try {
            $GoodsCategory = new GoodsCategoryService();
            $arr = $GoodsCategory->propertyList($cat_id);
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
        $cat_id = $this->request->param('cat_id', '', 'interval');
        $property = $this->request->param('property', '', 'trim');
        try {
            $GoodsCategory = new GoodsCategoryService();
            $result = $GoodsCategory->addOrEditProperty($cat_id, $property);
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
//getEditBanner    获取编辑的轮播图信息
//addOrEditBanner  编辑/添加轮播图
//delBanner        删除轮播图

//propertyList       属性列表
//addOrEditProperty  设置属性和属性值