<?php
/**
 * 咨询 控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\service\LifeToolsInformationService;

class InformationController extends ApiBaseController
{
    /**
     * 分类列表
     */
    public function cateList()
    {
        $arr = [
            [
                'value' => 0,
                'title' => '全部',
            ],
            [
                'value' => 1,
                'title' => '人气排行',
            ],
        ];
        return api_output(0, $arr, 'success');
    }

    /**
     * 资讯列表
     */
    public function getList()
    {
        $param['sort_value']   = $this->request->param('sort_value', 0, 'intval');// 排序
        $param['type']   = $this->request->param('type', '', 'trim');// 类型
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['page_size'] = $this->request->param('pageSize', 10, 'intval');
        $param['from'] = 'user';
        try {
            $arr = (new LifeToolsInformationService())->getInformationList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 资讯详情
     */
    public function getDetail()
    {
        $param['pigcms_id']   = $this->request->param('id', 0, 'intval');
        $param['from'] = 'user';
        $arr = (new LifeToolsInformationService())->getInformationDetail($param);
        return api_output(0, $arr, 'success');       
    }
}
