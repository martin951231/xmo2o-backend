<?php


namespace app\merchant\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\db\DiypageFeedCategory;
use app\common\model\db\MerchantCategory;
use app\common\model\service\diypage\DiypageModelService;
use app\merchant\controller\api\ApiBaseController;
use app\merchant\model\db\IndustryProperty;
use app\merchant\model\service\MerchantCategoryService;
use think\facade\Config;

class MerchantStoreCategoryController extends AuthBaseController
{
    /**
     * @return \json
     * 系统后台店铺分类页面列表
     */
    public function getStoreCategoryList()
    {
        $database_group_category = new MerchantCategoryService();
        $cat_fid = $this->request->param('cat_fid', 0, 'intval');
        $condition_group_category = [['cat_fid', '=', $cat_fid]];
        $order = 'cat_sort DESC,cat_id DESC';
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = Config::get('api.page_size');
        try {
            $assign['list'] = $database_group_category->getStoreCategoryList($condition_group_category, $order, $page, $pageSize);
            if (!empty($assign['list'])) {
                foreach ($assign['list'] as $key => $val) {
                    $where = [['source_id', '=', $val['cat_id']]];
                    $status = (new DiypageModelService())->getDiyPage($where);
                    if (empty($status)) {
                        $assign['list'][$key]['diy_status'] = 0;
                    } else {
                        $assign['list'][$key]['diy_status'] = 1;
                    }
                }
            }
            return api_output(0, $assign);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 编辑系统后台店铺分类页面
     */
    public function editStoreCategory()
    {
        $cat_id = $this->request->param('cat_id', '', 'intval');
        $database_group_category = new MerchantCategoryService();
        $sel = $database_group_category->getSome([],'id asc');
        $assign['sel'] = $sel;
        if (empty($cat_id)) {
            return api_output(0, $assign);
        } else {
            $condition_group_category = [['cat_id', '=', $cat_id]];
            try {
                $assign['data'] = $database_group_category->editStoreCategory($condition_group_category);
                if (!empty($assign['data']['cat_adver'])) {
                    $assign['data']['cat_adver'] = replace_file_domain($assign['data']['cat_adver']);
                }
                if (!empty($assign['data']['cat_pic'])) {
                    $assign['data']['cat_pic'] = replace_file_domain($assign['data']['cat_pic']);
                }
                return api_output(0, $assign);
            } catch (\Exception $e) {
                //dd($e);
                return api_output_error(1003, $e->getMessage());
            }
        }
    }

    /**
     * @return \json
     * 系统后台店铺分类页面编辑新增
     */
    public function saveStoreCategory()
    {
        $data['cat_fid'] = $this->request->param('cat_fid', 0, 'intval');
        $data['cat_adver'] = $this->request->param('cat_adver', '', 'trim');
        $data['cat_id'] = $this->request->param('cat_id', 0, 'intval');
        $data['cat_industry'] = $this->request->param('cat_industry', 0, 'intval');
        $data['cat_info'] = $this->request->param('cat_info', "", 'trim');
        $data['cat_name'] = $this->request->param('cat_name', "", 'trim');
        $data['cat_pic'] = $this->request->param('cat_pic', "", 'trim');
        $data['cat_sort'] = $this->request->param('cat_sort', 0, 'intval');
        $data['cat_status'] = $this->request->param('cat_status', 0, 'intval');
        $data['cat_url'] = $this->request->param('cat_url', "", 'trim');
        $data['is_hot'] = $this->request->param('is_hot', 0, 'intval');
        try {
            if (!isset($data['cat_name']) || empty($data['cat_name'])) {
                return api_output_error(1003, "缺少分类名称");
            }
            if (!isset($data['cat_url']) || empty($data['cat_url'])) {
                return api_output_error(1003, "缺少短标记");
            }
            if (!isset($data['cat_pic']) || empty($data['cat_pic'])) {
                return api_output_error(1003, "缺少分类logo");
            }
            if (!isset($data['cat_industry'])) {
                return api_output_error(1003, "缺少绑定行业属性");
            }
            if ($data['cat_id']==0 || empty($data['cat_id'])) {//新增
                if (!isset($data['cat_fid']) || empty($data['cat_fid'])) {
                    $data['cat_fid'] = 0;
                }
                $ret = (new MerchantCategory())->add($data);
            } else {//修改
                $where = [['cat_id', '=', $data['cat_id']]];
                $ret = (new MerchantCategory())->updateThis($where, $data);
            }
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function updateSort(){
        $data['cat_sort'] = $this->request->param('cat_sort', 0, 'intval');
        $data['cat_id'] = $this->request->param('cat_id', 0, 'intval');
        try {
            if ($data['cat_id']==0 || empty($data['cat_id'])) {//新增
                return api_output_error(1003, "缺少分类id");
            } else {//修改
                $where = [['cat_id', '=', $data['cat_id']]];
                $ret = (new MerchantCategory())->updateThis($where, $data);
            }
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * @return \json
     * 删除
     */
    public function delStoreCategory()
    {
        $cat_id = $this->request->param('cat_id', '', 'intval');
        try {
            if (empty($cat_id)) {
                return api_output_error(1003, "分类ID缺失");
            } else if (empty((new MerchantCategory())->getSome([['cat_fid', '=', $cat_id], ['cat_status', '=', 1]]))) {
                return api_output_error(1003, "请先删除子分类");
            } else {
                $where = [['cat_id', '=', $cat_id]];
                $ret = (new MerchantCategory())->getDel($where);
                return api_output(0, $ret);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}