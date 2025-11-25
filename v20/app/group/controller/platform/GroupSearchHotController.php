<?php
/**
 * 团购频道页子分类页热搜词装修
 * Author: 钱大双
 * Date Time: 2021年1月25日13:48:46
 */

namespace app\group\controller\platform;

use app\group\controller\platform\AuthBaseController;
use app\group\model\service\GroupSearchHotService;


class GroupSearchHotController extends AuthBaseController
{
    /**获得团购频道页子分类页热搜词列表
     * @return \json
     */
    public function getGroupSearchHotList()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupSearchHotService())->getList($param);
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**编辑团购频道页子分类页热搜词
     * @return \json
     */
    public function addGroupSearchHot()
    {
        $param = $this->request->param();
        try {
            (new GroupSearchHotService())->addGroupSearchHot($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**获取团购频道页子分类页热搜词基本信息
     * @return \json
     *
     */
    public function getGroupSearchHotInfo()
    {
        $param = $this->request->param();
        try {
            $info = (new GroupSearchHotService())->getInfo($param);
            return api_output(1000, $info, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**删除团购频道页子分类页热搜词
     * @return \json
     */
    public function delSearchHot()
    {
        $param = $this->request->param();
        try {
            (new GroupSearchHotService())->delSearchHot($param);
            return api_output(1000);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**保存团购频道页子分类页热搜词排序
     * @return \json
     */
    public function saveSearchHotSort()
    {
        $param = $this->request->param();
        try {
            (new GroupSearchHotService())->saveSort($param);
            return api_output(1000);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
