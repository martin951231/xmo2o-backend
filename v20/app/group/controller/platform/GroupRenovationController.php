<?php
/**
 * 团购首页频道页装修
 * Author: 钱大双
 * Date Time: 2021-1-13 15:28:56
 */

namespace app\group\controller\platform;

use app\group\controller\platform\AuthBaseController;
use app\group\model\service\GroupConfigRenovationService;
use app\group\model\service\GroupConfigRenovationGoodsService;
use app\group\model\service\GroupRenovationCombineGoodsService;
use app\group\model\service\GroupRenovationCustomService;
use app\group\model\service\GroupRenovationCustomStoreSortService;
use app\group\model\service\GroupRenovationCustomGroupSortService;


class GroupRenovationController extends AuthBaseController
{
    /**获取基本信息
     * @return \json
     *
     */
    public function getInfo()
    {
        $param = $this->request->param();
        try {
            $info = (new GroupConfigRenovationService())->getGroupCfgInfo($param);
            return api_output(1000, $info, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**获得团购优选商品已装修的商品列表
     * @return \json
     */
    public function getRenovationGoodsList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupConfigRenovationService())->getRenovationGoodsList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *优选商品编辑装修
     */
    public function editCfgInfo()
    {
        $param = $this->request->param();
		
		unset($param['system_type']);
		
        try {
            $detail = (new GroupConfigRenovationService())->editGroupRenovation($param, $this->systemUser);
            return api_output(0, $detail);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *优选商品编辑排序
     */
    public function editCfgSort()
    {
        $param = $this->request->param();

        try {
            $res = (new GroupConfigRenovationGoodsService())->edit($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *超值组合编辑装修
     */
    public function editCombineCfgInfo()
    {
        $param = $this->request->param();

        try {
            $detail = (new GroupConfigRenovationService())->editGroupCombineRenovation($param, $this->systemUser);
            return api_output(0, $detail);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *超值组合编辑排序
     */
    public function editCombineCfgSort()
    {
        $param = $this->request->param();

        try {
            $res = (new GroupRenovationCombineGoodsService())->edit($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**获得团购超值组合已装修的商品列表
     * @return \json
     */
    public function getRenovationCombineGoodsList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupRenovationCombineGoodsService())->getRenovationCombineGoodsList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getRenovationCustomList()
    {
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");
        // 每页显示数量
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['group_activity_recommendations'] = $this->request->param("group_activity_recommendations", 0, "intval");
        try {
            $list = (new GroupRenovationCustomService())->getList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**编辑店铺活动推荐
     * @return \json
     */
    public function addRenovationCustom()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupRenovationCustomService())->addCustom($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**删除店铺活动推荐
     * @return \json
     */
    public function delRenovationCustom()
    {
        $param = $this->request->param();

        try {
            (new GroupRenovationCustomService())->delCustom($param);
            return api_output(1000);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**获取店铺活动推荐基本信息
     * @return \json
     */
    public function getRenovationCustomInfo()
    {
        $param = $this->request->param();
        try {
            $rs = (new GroupRenovationCustomService())->getRenovationCustomInfo($param);
            return api_output(1000, $rs);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**获得团购首页自定义活动推荐店铺管理列表
     * @return \json
     */
    public function getRenovationCustomStoreSortList()
    {
        $param['custom_id'] = $this->request->param("custom_id", "", "intval");
        // 每页显示数量
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['group_activity_recommendations'] = $this->request->param("group_activity_recommendations", 0, "trim,intval");

        try {
            if(!empty($param['group_activity_recommendations'])){
                $list = (new GroupRenovationCustomService())->getGoodsCustomTopList($param);
            }else{
                $list = (new GroupRenovationCustomStoreSortService())->getList($param);
            }
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *店铺活动推荐店铺管理店铺排序
     */
    public function editRenovationCustomStoreSort()
    {
        $param = $this->request->param();

        try {
            $res = (new GroupRenovationCustomStoreSortService())->edit($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**获得团购发现页团购分类商品管理列表
     * @return \json
     */
    public function getRenovationCustomGroupSortList()
    {
        $param['custom_id'] = $this->request->param("custom_id", "", "intval");
        // 每页显示数量
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['keyword'] = $this->request->param("keyword", "", "trim");

        try {
            $list = (new GroupRenovationCustomGroupSortService())->getList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *团购发现页团购分类商品管理排序
     */
    public function editRenovationCustomGroupSort()
    {
        $param = $this->request->param();

        try {
            $res = (new GroupRenovationCustomGroupSortService())->edit($param);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 商品活动推荐-添加置顶商品
     * @return \think\response\Json
     */
    public function addGoodsCustomGroup(){
        $params['custom_id'] = $this->request->param('custom_id',0,'trim,intval');
        $params['goods_arr'] = $this->request->param('goods_arr');
        try {
            $res = (new GroupRenovationCustomService())->addGoodsCustomGroup($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 商品活动推荐-删除置顶商品
     * @return \think\response\Json
     */
    public function delGoodsCustomGroup(){
        $params['custom_id'] = $this->request->param('custom_id',0,'trim,intval');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new GroupRenovationCustomService())->delGoodsCustomGroup($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 商品活动推荐-修改置顶商品排序值
     */
    public function changeGoodsCustomSort(){
        $params['sort'] = $this->request->param('sort',0,'trim,intval');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new GroupRenovationCustomService())->changeGoodsCustomSort($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
