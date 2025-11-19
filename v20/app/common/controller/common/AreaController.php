<?php
/**
 * 公共区域controller
 * Author: 衡婷妹
 * Date Time: 2020/09/14
 */

namespace app\common\controller\common;
use app\common\controller\CommonBaseController;
use app\common\model\service\AreaService;

class AreaController extends CommonBaseController
{
    /**
     * 获得当前城市的中心位置信息
     * Author: hengtingmei
     * @return array
     */
    public function getCityCenter()
    {
        $param['now_city'] = $this->request->param('now_city', 0, 'intval');
        $result = (new AreaService())->getCityCenter($param);
        try {
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function getAllArea()
    {
        $type =  $this->request->param('type', 0, 'trim');
        $indexName =  $this->request->param('index_name', 'area_id', 'trim');
        $textName =  $this->request->param('text_name', 'area_name', 'trim');
        $areaService = new AreaService();
        try {
            $arr = $areaService->getAllArea($type, '', 'children', false, $indexName, $textName);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }
}
