<?php
/**
 * 团购商品
 * Author: hengtingmei
 * Date Time: 2020/11/17 17:09
 */

namespace app\group\controller\api;

use app\group\model\service\GroupService;
use app\group\model\service\order\GroupStartService;
use app\group\model\service\JobPersonService;
use app\grow_grass\model\service\GrowGrassArticleService;

class GroupController extends ApiBaseController
{

    /**
     * 获得团购商品列表
     */
    public function getGroupGoodsList()
    {
        $param = $this->request->param();
        // 获得列表
        $list = (new GroupService())->getGroupGoodsList($param);

        return api_output(0, $list);
    }


    /**
     * 获得可参与团购优惠组合的商品列表
     */
    public function getGroupCombineGoodsList()
    {
        $param = $this->request->param();
        // 获得列表
        $list = (new GroupService())->getGroupGoodsList($param);

        return api_output(0, $list);
    }

    /**
     * 筛选信息
     */
    public function screenList()
    {
        $param = $this->request->param();
        try {
            // 筛选信息
            $list = (new GroupService())->getScreenList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购模块全新改版 首页 频道页列表
     */
    public function getList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取 1：首页 优选商品 2：频道页：精选商品列表
     */
    public function getSelectGoods()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getSelectGoods($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取 1：首页 超值组合 2：频道页：超值联盟列表
     */
    public function getCombineGoods()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getCombineGoods($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取 特价拼团列表
     */
    public function getDiscountGoods()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getDiscountGoods($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取首页 自定义分类
     */
    public function getCustomList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getCustomList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取首页 自定义分类 团购商品列表
     */
    public function getCustomGroupList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getCustomGroupList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购模块全新改版 发现页列表
     */
    public function getSearchList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getSearchList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购模块全新改版 发现页团购分类列表
     */
    public function getSearchGroupList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getSearchGroupList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购模块全新改版 发现页买单人气榜列表
     */
    public function getSearchTopList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getSearchTopList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购模块全新改版 频道页团购分类商品列表
     */
    public function getChannelGroupList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupService())->getChannelGroupList($param,1,'group_by_store');
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    public function getPinLists()
    {
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['group_id'] = $this->request->param('group_id', 0, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['page_size'] = $this->request->param('page_size', 20, 'intval');
        $lists = (new GroupStartService())->getPinLists($param);
        return api_output(1000, $lists);
    }

    //技师主页
    public function jobPersonIndex(){
        $param = $this->request->param();
        $output = [
            'current_cate' => 0,
            'cates' => [],
            'recoms' => []
        ];
        $JobPersonService = new JobPersonService();
        $cates = $JobPersonService->getCate();
        if($cates){
            $output['cates'] = $cates;
            $output['current_cate'] = $cates[0]['cat_id'];
            $output['recoms'] = $JobPersonService->getJobPerson($cates[0]['pos_id'], 20);
        }

        return api_output(0, $output);
    }

    //技术主页-精选动态列表
    public function jobPersonList(){
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['cate_id'] = $this->request->param('cat_id', 0, 'intval');
        if($param['cate_id'] == '0'){
            return api_output_error(1003, 'cate_id参数必传');
        }
        $JobPersonService = new JobPersonService();
        $cate_info = $JobPersonService->getCateInfo($param['cate_id']);
        if(empty($cate_info)) return api_output(0, []);
        $person = $JobPersonService->getJobPerson(explode(',', $cate_info['pos_id']), 100);
        
        if(empty($person)) return api_output(0, []);
        $users = array_column($person, 'uid');

        $list = (new GrowGrassArticleService)->getArticleByUids($users, $param['page'], 10);
        foreach ($list as $key => $value) {
            foreach ($person as $p) {
                if($p['uid'] == $value['uid']){
                    $list[$key]['avatar'] = $p['headimg'];
                    $list[$key]['name'] = $p['name'];
                    $list[$key]['job_name'] = $p['job_name'];
                    $list[$key]['store_name'] = $p['store_name'];
                    break;
                }
            }
        }
        return api_output(0, $list);        
    }
}
