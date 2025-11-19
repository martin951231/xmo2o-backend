<?php
/**
 * 新版团购广告
 * Author: 钱大双
 * Date Time: 2021-1-13 15:28:56
 */

namespace app\group\controller\platform;


use app\group\model\service\GroupAdverService;


class GroupAdverController extends AuthBaseController
{
    /**
     * 获取列表
     * @return \json
     */
    /**轮播图、导航列表
     * @return \json
     */
    public function getAdverList()
    {
        $param = $this->request->param();

        try {
            $res = (new GroupAdverService())->getAdverList($param, $this->systemUser);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**团购添加广告
     * @return \think\response\Json
     */
    public function addGroupAdver()
    {
        $param = $this->request->param();

        try {
            (new GroupAdverService())->addGroupAdver($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**团购删除广告
     * @return \json
     */
    public function delGroupAdver()
    {
        $param = $this->request->param();

        try {
            (new GroupAdverService())->delGroupAdver($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**获取团购广告信息
     * @return \json
     */
    public function getEditAdver()
    {
        $param = $this->request->param();

        try {
            $detail = (new GroupAdverService())->getEditAdver($param);
            return api_output(0, $detail);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}
