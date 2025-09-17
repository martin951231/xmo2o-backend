<?php

namespace app\channel_code\controller\platform;

use app\channel_code\model\service\ChanelMsgListService;
use app\common\controller\platform\AuthBaseController;
use Exception;

class IndexController extends AuthBaseController
{
    /**
     * 平台渠道码列表
     *
     * @return void
     * @author: zt
     * @date: 2024/03/23
     */
    public function index()
    {
        $param = $this->request->param();
        $lists = (new ChanelMsgListService())->getLists(1, $param);
        return api_output(0, $lists);
    }

    /**
     * 新增渠道码
     *
     * @return void
     * @author: zt
     * @date: 2024/03/23
     */
    public function add()
    {
        $param = $this->request->param();
        try {
            (new ChanelMsgListService())->add($param);
            return api_output(0, []);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑渠道码
     *
     * @return void
     * @author: zt
     * @date: 2024/03/23
     */
    public function edit()
    {
        $param = $this->request->param();
        try {
            (new ChanelMsgListService())->edit($param);
            return api_output(0, []);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     *
     * @return void
     * @author: zt
     * @date: 2024/03/25
     */
    public function del()
    {
        $channelId = $this->request->param('chanel_id', 0, 'intval');
        (new ChanelMsgListService())->delByChannelId($channelId);
        return api_output(0, []);
    }

    public function changeStatus(){
        $channelId = $this->request->param('chanel_id', 0, 'intval');
        (new ChanelMsgListService())->changeStatusByChannelId($channelId);
        return api_output(0, []);
    }

    /**
     * 渠道码详情
     *
     * @return void
     * @author: zt
     * @date: 2024/03/25
     */
    public function detail()
    {
        $channelId = $this->request->param('chanel_id', 0, 'intval');
        $result = (new ChanelMsgListService())->getDetailByChannelId($channelId);
        return api_output(0, $result);
    }

    /**
     * 获取统计数据
     *
     * @return void
     * @author: zt
     * @date: 2024/03/25
     */
    public function getCharts(){
        $param = $this->request->param();
        $result = (new ChanelMsgListService())->getCharts($param);
        return api_output(0, $result);
    }

     /**
     * 获取今日数据
     *
     * @return void
     * @author: zt
     * @date: 2024/03/25
     */
    public function getTodayStatistics(){
        $channelId = $this->request->param('chanel_id', 0, 'intval');
        $result = (new ChanelMsgListService())->getTodayStatistics($channelId);
        return api_output(0, $result);
    }
}
