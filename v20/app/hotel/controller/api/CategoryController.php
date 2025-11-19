<?php
/**
 * 控制器
 */

namespace app\hotel\controller\api;

use app\hotel\model\service\TradeHotelCategoryService;

class CategoryController extends ApiBaseController
{
    public function getTradeHotelStock(){
        $param['group_id'] = $this->request->param('group_id', '0', 'intval');
        $param['dep_time'] = $this->request->param('dep_time', '', '');
        $param['end_time'] = $this->request->param('end_time', '', '');

        $res = (new TradeHotelCategoryService())->getTradeHotelStock($param);
        $data = array(
            'days' => ceil(abs((strtotime($param['end_time'])-strtotime($param['dep_time']))/3600/24)),
            'dep_time' => $param['dep_time']?strtotime($param['dep_time']):'',
            'end_time' => $param['end_time']?strtotime($param['end_time']):'',
            'show_dep_time' => $param['dep_time']?date('m-d',strtotime($param['dep_time'])):'',
            'show_end_time' => $param['end_time']?date('m-d',strtotime($param['end_time'])):'',
            'time_dep_time' => $param['dep_time']?date('Y-m-d',strtotime($param['dep_time'])):'',
            'time_end_time' => $param['end_time']?date('Y-m-d',strtotime($param['end_time'])):'',
        );
        $data['hotel_list'] = $res;
		
        return api_output(0, ['trade_hotel'=>$data]);
	}
}
