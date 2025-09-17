<?php

namespace app\common\controller\platform;

use app\common\controller\CommonBaseController;
use app\common\model\service\CombineCouponListService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\SearchHotCouponWordsService;

/**
 * 优惠券中心
 * @author: 张涛
 * @date: 2020/11/23
 */
class CouponController extends CommonBaseController
{
    /**
     * 获取热门搜索关键词
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function getSearchHotWords()
    {
        try {
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $rs = (new SearchHotCouponWordsService())->getLists($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 获取一条记录
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function getWordDetail()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $rs = (new SearchHotCouponWordsService())->getDetailById($id);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 保存
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function saveWords()
    {
        try {
            $param['id'] = $this->request->param('id', 0, 'intval');
            $param['name'] = $this->request->param('name', '', 'trim');
            $param['sort'] = $this->request->param('sort', 0, 'intval');
            $rs = (new SearchHotCouponWordsService())->saveWords($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 保存排序
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function saveWordsSort()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $sort = $this->request->param('sort', 0, 'intval');
            $rs = (new SearchHotCouponWordsService())->saveWordsSort($id, $sort);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 删除关键词
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function delWords()
    {
        try {
            $ids = $this->request->param('ids', []);
            $rs = (new SearchHotCouponWordsService())->delWords($ids);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 获取品牌精选优惠券列表
     * @author: 张涛
     * @date: 2020/11/24
     */
    public function getBrandSelectCoupon()
    {
        try {
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $param['keyword'] = $this->request->param('keyword','','trim');
            $rs = (new CombineCouponListService())->getBrandSelectCoupon($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 获取品牌精选可筛选优惠券
     * @author: 张涛
     * @date: 2020/11/24
     */
    public function chooseBrandSelectCoupon()
    {
        try {
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $param['keyword'] = $this->request->param('keyword','','trim');
            $param['coupon_type'] = $this->request->param('coupon_type','','trim');
            $rs = (new CombineCouponListService())->chooseBrandSelectCoupon($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 添加品牌精选优惠券
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function addBrandCoupon()
    {
        try {
            $param['coupon_id'] = $this->request->param('coupon_id', 0, 'intval');
            $param['coupon_type'] = $this->request->param('coupon_type', '', 'trim');

            $rs = (new CombineCouponListService())->addBrandCoupon($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 删除品牌精选
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function delBrandCoupon()
    {
        try {
            $ids = $this->request->param('ids', []);
            if ($ids) {
                $rs = (new CombineCouponListService())->delBrandCouponByIds($ids);
            } else {
                $param['coupon_id'] = $this->request->param('coupon_id', 0, 'intval');
                $param['coupon_type'] = $this->request->param('coupon_type', '', 'trim');
                $rs = (new CombineCouponListService())->delBrandCouponByCouponId($param);
            }
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 平台优惠券核销记录
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function sysCouponUseRecords()
    {
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
        $param['business_type'] = $this->request->param('business_type', '', 'trim');
        $param['is_discount'] = $this->request->param('is_discount', '-1','intval');
        $param['is_mobile_pay'] = $this->request->param('is_mobile_pay', '-1','intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $rs = (new SystemCouponService())->getUseRecords($param);
        return api_output(0, $rs);
    }

    /**
     * 平台优惠券领取记录
     */
    public function sysCouponGetRecords()
    {
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new SystemCouponService())->getHadpullRecords($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出平台优惠券领取记录
     */
    public function exportSysGetRecords()
    {
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new SystemCouponService())->exportSysGetRecords($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}