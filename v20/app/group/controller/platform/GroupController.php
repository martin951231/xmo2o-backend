<?php
/**
 * 团购商品
 * Author: hengtingmei
 * Date Time: 2020/11/17 17:09
 */

namespace app\group\controller\platform;
use app\group\controller\platform\AuthBaseController;
use app\group\model\service\GroupCategoryService;
use app\group\model\service\GroupService;

class GroupController extends AuthBaseController
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
     * 获得团购优惠组合可选择的商品列表
     */
    public function getGroupCombineGoodsList()
    {
        $param = $this->request->param();
        $param['pin_num'] = 0;
        $param['has_spec'] = 0;
        $param['is_appoint_bind'] = 0;
        $param['trade_type'] = 0;
        $param['status'] = 1;
        // 获得列表
        $list = (new GroupService())->getGroupGoodsList($param);

        return api_output(0, $list);
    }

    /**
     * 获得团购商品有效分类
     */
    public function getGroupCategoryList()
    {
        $param = $this->request->param();
        //客户要求显示所有的团购分类
        $list = (new GroupCategoryService())->getGroupCategoryList($param);
        return api_output(0, $list);
    }


    /**
     * 获得团购前端地址
     */
    public function getUrl()
    {
        $param = $this->request->param();
        $rs = (new GroupService())->getUrl($param);
        return api_output(0, $rs);
    }
}
