<?php

namespace app\common\controller\platform;


use app\common\model\service\PrivateActivityService;

/**
 * 私域流量控制器
 *
 * @author: 张涛
 * @date: 2021/02/20
 */
class PrivateDomainFlowController extends AuthBaseController
{

    public function pages()
    {
        $pages = [
            [
                'business_name' => '外卖业务',
                'pages' => [
                    ['id' => 1 << 0, 'name' => '外卖店铺主页'],
                    ['id' => 1 << 1, 'name' => '外卖订单详情页']
                ]
            ],
            /*[
                'business_name' => '团购业务',
                'pages' => [
                    ['id' => 1 << 2, 'name' => '团购商品详情页'],
                    ['id' => 1 << 3, 'name' => '团购订单电子凭证页'],
                    ['id' => 1 << 4, 'name' => '团购商品详情页'],
                    ['id' => 1 << 5, 'name' => '团购订单详情页']
                ]
            ],
            [
                'business_name' => '商城业务',
                'pages' => [
                    ['id' => 1 << 6, 'name' => '商城店铺主页'],
                    ['id' => 1 << 7, 'name' => '商城商品订单详情页']
                ]
            ]*/
        ];
        return api_output(0, $pages);
    }

    /**
     * 在线制图模板
     * @author: 张涛
     * @date: 2021/03/06
     */
    public function alertTemplates()
    {
        $tpls = [
            ['id' => 1, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/tpl1.png'],
            ['id' => 2, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/tpl2.png'],
            ['id' => 3, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/tpl3.png'],
            ['id' => 4, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/tpl4.png'],
        ];
        return api_output(0, $tpls);
    }

    public function hoverTemplates()
    {
        $tpls = [
            ['id' => 1, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/hover1.png'],
            ['id' => 2, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/hover2.png'],
            ['id' => 3, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/hover3.png'],
            ['id' => 4, 'pic' => cfg('site_url') . '/static/images/qiye_private_flow/hover4.png'],
        ];
        return api_output(0, $tpls);
    }

    /**
     * 活动列表
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function activityLists()
    {
        try {
            $params['is_del'] = 0;
            $params['page'] = $this->request->param('page', 1, 'intval');
            $params['pageSize'] = $this->request->param('page_size', 20, 'intval');
            $rs = (new PrivateActivityService())->activityLists($params);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 活动列表
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function showActivity()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $rs = (new PrivateActivityService())->showActivity($id);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 创建活动
     *
     * @return void
     * @author: 张涛
     * @date: 2021/02/20
     */
    public function saveActivity()
    {
        try {
            $rs = (new PrivateActivityService())->saveActivity($this->request->param());
            return api_output(0, ['id' => $rs]);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 删除活动
     *
     * @return void
     * @author: 张涛
     * @date: 2021/02/20
     */
    public function delActivity()
    {
        try {
            $ids = $this->request->param('ids');
            (new PrivateActivityService())->delActivityByIds($ids);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 指定区域
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function assignArea()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $areaIds = $this->request->param('area_ids');
            (new PrivateActivityService())->assignArea($id, $areaIds);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 指定店铺
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function assignStore()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $storeIds = $this->request->param('store_ids');
            $operate = $this->request->param('operate', '', 'trim');
            (new PrivateActivityService())->assignStore($id, $storeIds, $operate);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 店铺列表
     * @author: 张涛
     * @date: 2021/03/11
     */
    public function storeLists()
    {
        $params['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        $params['is_selected'] = $this->request->param('is_selected', -1, 'intval');
        $params['search_type'] = $this->request->param('search_type', '', 'trim');
        $params['keyword'] = $this->request->param('keyword', '', 'trim');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['pageSize'] = $this->request->param('pageSize', 20, 'intval');
        $rs = (new PrivateActivityService())->storeLists($params);
        return api_output(0, $rs);
    }

    /**
     * 生成弹层图片
     * @author: 张涛
     * @date: 2021/03/11
     */
    public function buildAlertPic()
    {
        try {
            $params['title'] = $this->request->param('title', '', 'trim');
            $params['sub_title'] = $this->request->param('sub_title', '', 'trim');
            $params['tpl_id'] = $this->request->param('tpl_id', 0, 'intval');
            $rs = (new PrivateActivityService())->buildAlertPic($params);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
