<?php
/**
 * 团购优惠组合控制器
 * Author: hengtingmei
 * Date Time: 2020/11/16 11:16
 */

namespace app\group\controller\api;
use app\group\model\service\order\GroupCombineActivityBuyLogService;
use app\group\model\service\group_combine\GroupCombineActivityService;

class GroupCombineController extends ApiBaseController
{
    
    /**
     * 获得店铺列表
     */
    public function groupCombinelist()
    {
        $param['goods_detail'] = 1;//获得商品详情
        $param['is_wap'] = 1;//是否用户
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        // 获得列表
        $list = (new GroupCombineActivityService())->getList($param);

        
        return api_output(0, $list);
    }

    /**
     * 获得活动详情
     */
    public function getGroupCombineDetail()
    {
        $param = $this->request->param();
        $param['user'] = $this->userInfo;

        $detail = (new GroupCombineActivityService())->getGroupCombineDetail($param);
        return api_output(0, $detail);
    }

    /**
     *团购业务的优惠组合购买日志
     */
    public function getGroupCombineBuyList()
    {
        $param = $this->request->param();

        $detail = (new GroupCombineActivityBuyLogService())->getGroupCombineBuyList($param);
        return api_output(0, $detail);
    }

    /**
     *团购业务的优惠组合购买日志详情
     */
    public function getGroupCombineBuyListDetail()
    {
        $param = $this->request->param();
        $param['pageSize'] = 6;
        $detail['list'] = (new GroupCombineActivityBuyLogService())->getGroupCombineBuyList($param);
        $where = [
            'combine_id' => $param['combine_id'] ?? 0
        ];
        $detail['total'] = (new GroupCombineActivityBuyLogService())->getCount($where);
        return api_output(0, $detail);
    }


    /**
     *团购优惠组合分享海报生成
     */
    public function getGroupCombineSharePoster()
    {
        $param = $this->request->param();

        if(empty($this->userInfo)){
            return api_output_error(1002, L_("未登录"));
        }

        $return = (new GroupCombineActivityService())->getGroupCombineSharePoster($param,$this->userInfo);

        return api_output(0, $return);
    }
}
