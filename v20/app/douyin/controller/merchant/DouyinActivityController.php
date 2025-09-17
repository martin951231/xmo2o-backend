<?php
/**
 * 抖音探店活动
 */

namespace app\douyin\controller\merchant;

use app\common\controller\platform\AuthBaseController;
use app\common\model\db\CardNewCoupon;
use app\common\model\db\MerchantStore;
use app\douyin\model\db\DouyinActivity;
use app\douyin\model\service\DouyinActivityService;
use app\douyin\model\service\DouyinActivitySourceMaterialService;
use think\Model;

class DouyinActivityController extends AuthBaseController
{

    /**
     * 获取列表
     * @return \json
     */
    public function getActivityList()
    {
        $param['name']     = $this->request->param('name', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['mer_id'] = $this->request->log_uid;
        try {
            $arr = (new DouyinActivityService())->getActivityList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动信息
     * @return \json
     */
    public function getActivityDetail()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new DouyinActivity())->getOne(['id' => $id, 'is_del' => 0])->toArray();
            if (empty($arr)) {
                throw new \think\Exception('参数有误');
            }
            $arr['coupon_ids'] = explode(',',$arr['coupon_ids']);
            $arr['material_ids'] = explode(',', $arr['material_ids']);
            $arr['content'] = replace_file_domain_content($arr['content']);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加或编辑活动
     * @return \json
     */
    public function addOrEditActivity()
    {
        $param['mer_id']       = $this->_uid;
        $param['id']           = $this->request->param('id', 0, 'intval');
        $param['name']         = $this->request->param('name', '', 'trim');
        $param['video_num']    = $this->request->param('video_num', 1, 'intval');
        $param['store_id']     = $this->request->param('store_id', 0, 'intval');
        $param['coupon_ids']   = $this->request->param('coupon_ids', '', 'trim');
        $param['material_ids'] = $this->request->param('material_ids', '', 'trim');
        $param['status']       = $this->request->param('status', 1, 'intval');
        $param['store_douyin_id'] = $this->request->param('store_douyin_id', '', 'trim');
        $param['poi_id'] = $this->request->param('poi_id', '', 'trim');
        $param['content'] = $this->request->param('content', '', 'trim');
        try {
            $arr = (new DouyinActivityService())->addOrEditActivity($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \json
     */
    public function delActivity()
    {
        $ids = $this->request->param('ids', '', 'trim');
        if (empty($ids)) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new DouyinActivity())->updateThis([['id', 'in', $ids]], ['is_del' => 1]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改活动状态
     * @return \json
     */
    public function setActivityStatus()
    {
        $id     = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        if (empty($id)) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new DouyinActivity())->updateThis([['id', '=', $id]], ['status' => $status]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取所有店铺
     * @return \json
     */
    public function getStoreList()
    {
        try {
            $arr = (new MerchantStore())->getSome([
                'mer_id' => $this->_uid,
                'status' => 1,
            ], 'store_id,name', 'sort desc');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取所有优惠券
     * @return \json
     */
    public function getCouponList()
    {
        try {
            $arr = (new CardNewCoupon())->getSome([
                ['mer_id', '=', $this->_uid],
                ['start_time', '<=', time()],
                ['end_time', '>', time()],
                ['status', '=', 1],
            ], 'coupon_id,name', 'coupon_id desc');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 素材列表
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function getSourceMaterialLists()
    {
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $params['mer_id'] = $this->request->log_uid;
        try {
            $arr = (new DouyinActivitySourceMaterialService())->getSourceMaterialLists($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加/编辑素材
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function saveSourceMaterial()
    {
        $params['id'] = $this->request->param('id', 0, 'intval');
        $params['mer_id'] = $this->request->log_uid;
        $params['material_name'] = $this->request->param('material_name', '', 'trim');
        $params['material_url'] = $this->request->param('material_url', '', 'trim');
        $params['material_type'] = $this->request->param('material_type', 'video', 'trim');
        $params['cover'] = $this->request->param('cover', '', 'trim');
        $params['share_desc'] = $this->request->param('share_desc', '', 'trim');
        $params['topic'] = $this->request->param('topic', '', 'trim');
        try {
            (new DouyinActivitySourceMaterialService())->saveSourceMaterial($params);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除素材
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function delSourceMaterial()
    {
        $ids = $this->request->param('ids', '', 'trim');
        try {
            (new DouyinActivitySourceMaterialService())->delSourceMaterial($ids);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取素材详情
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function showSourceMaterialDetail()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $detail = (new DouyinActivitySourceMaterialService())->getSourceMaterialById($id);
            return api_output(0, $detail, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}