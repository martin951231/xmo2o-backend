<?php
/**
 * liuruofei
 * 2021/08/25
 * 人员提成结算相关接口
 */
namespace app\new_marketing\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\new_marketing\model\service\MarketingPersonService;
use app\new_marketing\model\service\MarketingOrderService;
use think\App;

class MarketingPersonSettleController extends AuthBaseController
{

    /**
     * 获取人员提成结算搜索所需数据
     */
    public function getSearchData(){
        $data = [
            'select_person' => [//选择成员
                0 => [
                    'name' => '区域代理',
                    'value' => []
                ],//区域代理
                1 => [
                    'name' => '业务经理',
                    'value' => []
                ],//业务经理
                2 => [
                    'name' => '团队',
                    'value' => []
                ],//团队
                3 => [
                    'name' => '技术人员',
                    'value' => []
                ],//技术人员
                4 => [
                    'name' => '技术主管',
                    'value' => []
                ]//技术主管
            ],
            'quit_person' => [//离职成员
                0 => [
                    'name' => '区域代理',
                    'value' => []
                ],//区域代理
                1 => [
                    'name' => '业务经理',
                    'value' => []
                ],//业务经理
                2 => [
                    'name' => '业务员',
                    'value' => []
                ],//业务员
                3 => [
                    'name' => '技术人员',
                    'value' => []
                ],//技术人员
                4 => [
                    'name' => '技术主管',
                    'value' => []
                ]//技术主管
            ],
            'order_type' => [//订单类型:-1=全部,0=新订单,1=续费订单
                [
                    'label' => '全部',
                    'value' => -1
                ],
                [
                    'label' => '新订单',
                    'value' => 0
                ],
                [
                    'label' => '续费订单',
                    'value' => 1
                ],
            ],
            'order_business' => [//订单业务:-1=全部,0=店铺,1=社区
                [
                    'label' => '全部',
                    'value' => -1
                ],
                [
                    'label' => '店铺',
                    'value' => 0
                ],
                [
                    'label' => '社区',
                    'value' => 1
                ],
            ]
        ];
        try {
            $data = (new MarketingPersonService)->getSearchData();
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取人员提成结算列表
     */
    public function getSearchList(){
        $param = [
            'name' => $this->request->param('name', '', 'trim'),
//            'area' => $this->request->param('area', ''),
            'member' => $this->request->param('member', 0, 'intval'),
            'member_type' => $this->request->param('member_type', 0, 'intval'),//0=区域代理，1=业务经理,2=团队/业务员,3=技术人员,4=技术主管
            'quitMember' => $this->request->param('quitMember', 0, 'intval'),
            'order_type' => $this->request->param('order_type', -1, 'intval'),//订单类型:-1=全部,0=新订单,1=续费订单
            'order_business' => $this->request->param('order_business', -1, 'intval'),//订单业务:-1=全部,0=店铺,1=社区
            'start_time' => $this->request->param('start_time', '', 'trim'),
            'end_time' => $this->request->param('end_time', '', 'trim')
        ];
        if ($param['member'] && $param['quitMember']) {
            return api_output(0, [], 'success');
        }
        if ($param['member_type'] > 2) {//0=营销人员，1=技术人员
            $param['member_type'] = 1;
        } else {
            $param['member_type'] = 0;
        }
        $limit = [
            'page' => $this->request->param('page', 1, 'intval') ?? 1,
            'list_rows' => $this->request->param('pageSize', 10, 'intval') ?? 10
        ];
        try {
            $data = (new MarketingOrderService)->getPersonSettleList($param, $limit);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}