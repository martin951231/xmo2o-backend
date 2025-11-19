<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/9 9:39
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\AreaService;

class AreaController extends BaseController{

    /**
     * 获取省份
     * @author: wanziyang
     * @date_time: 2020/5/9 9:55
     * @return \json
     */
    public function getProvince() {
        $service_area = new AreaService();
        $where = [];
        $where[] = ['area_type','=',1];
        $where[] = ['is_open','=',1];
        $field = 'area_id as id,area_name as name';
        $list = $service_area->getAreaList($where,$field);
        $out = [
            'list' => $list
        ];
        return api_output(0,$out);
    }

    /**
     * 获取城市
     * @author: wanziyang
     * @date_time: 2020/5/9 9:55
     * @return \json
     */
    public function getCity() {
        $area_pid = $this->request->param('id','','intval');
        if (empty($area_pid)) {
            return api_output(1001,[],'缺少对应省份id！');
        }
        $service_area = new AreaService();
        $where = [];
        $where[] = ['area_pid','=',$area_pid];
        $where[] = ['is_open','=',1];
        $field = 'area_id as id,area_name as name';
        $list = $service_area->getAreaList($where,$field);
        $out = [
            'list' => $list
        ];
        return api_output(0,$out);
    }
}