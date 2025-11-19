<?php
/**
 * 团购优惠组合控制器
 * Author: hengtingmei
 * Date Time: 2020/11/16 11:16
 */

namespace app\group\controller\platform;
use app\group\controller\platform\AuthBaseController;
use app\group\model\service\group_combine\GroupCombineActivityService;

class GroupCombineController extends AuthBaseController
{
    
    /**
     * 获得店铺列表
     */
    public function groupCombinelist()
    {
        // 搜索条件
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['time_type'] = $this->request->param("time_type", "", "trim");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['cat_id'] = $this->request->param("cat_id", "", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['is_renovation'] = $this->request->param("is_renovation", "0", "intval");

        // 获得列表
        $list = (new GroupCombineActivityService())->getList($param,$this->systemUser);

        
        return api_output(0, $list);
    }

    /**
     * 获得活动详情
     */
    public function getGroupCombineDetail()
    {
        $param['combine_id'] = $this->request->param("combine_id", "", "intval");

        $detail = (new GroupCombineActivityService())->getGroupCombineDetail($param,$this->systemUser);
        return api_output(0, $detail);
    }
    /**
     *编辑活动
     */
    public function editGroupCombine()
    {
        $param = $this->request->param();

        $detail = (new GroupCombineActivityService())->editGroupCombine($param,$this->systemUser);
        return api_output(0, $detail);
    }
    /**
     * 保存店铺排序
     */
    public function saveSort()
    {
        
        $merchantStoreFoodshopService = new MerchantStoreFoodshopService();
        // 店铺ID
        $param['store_id'] = $this->request->param("store_id", "", "intval");
        // 排序值
        $param['sort'] = $this->request->param("sort", "", "intval");
        
        // 保存店铺排序
        $res = $merchantStoreFoodshopService->saveSort($param);
        return api_output(0, $res);
    }


    /**
     * 获得机器人列表
     */
    public function getRobotList()
    {
        $param = $this->request->param();
        // 获得机器人列表
        $res = (new GroupCombineActivityService())->getRobotList($param);
        return api_output(0, $res);
    }

    /**
     * 添加机器人
     */
    public function addRobot()
    {
        $param = $this->request->param();
        $res = (new GroupCombineActivityService())->addRobot($param);
        return api_output(0, $res);
    }

    /**
     * 删除机器人
     */
    public function delRobot()
    {
        $param = $this->request->param();
        $res = (new GroupCombineActivityService())->delRobot($param);
        return api_output(0, $res);
    }

    /**
     * 编辑机器人推荐人数
     */
    public function editSpreadNum()
    {
        $param = $this->request->param();
        $res = (new GroupCombineActivityService())->editSpreadNum($param);
        return api_output(0, $res);
    }

    /**
     * 评论列表
     * @return \think\response\Json
     */
    public function getReply(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        try {
            $res = (new GroupCombineActivityService())->getReply($param);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(0, $e->getMessage());
        }
    }

    /**
     * 评论详情
     * @return \think\response\Json
     */
    public function getReplyDetail(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new GroupCombineActivityService())->getReplyDetail($param);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(0, $e->getMessage());
        }
    }
}
