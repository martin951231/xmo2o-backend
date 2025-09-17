<?php

namespace app\thirdRobot\controller\merchant;

use app\http\exceptions\ParametersException;
use app\merchant\model\db\MerchantStoreStaff;
use app\thirdRobot\model\db\ThirdRobotMerchantStoreRobot;
use app\thirdRobot\model\service\ThirdRobotDeviceCmdService;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\model\service\ThirdRobotMerchantStoreService;
use app\thirdRobot\validate\MerchantStoreRobot;
use think\helper\Arr;
use think\helper\Str;
use token\Token;

class RobotController extends AuthBaseController
{
    public function initialize()
    {
        if(!empty(input('staff_ticket'))){//员工角色请求数据
            $token = Token::checkToken(input('staff_ticket'));
            //员工信息
            $storeStaff = MerchantStoreStaff::where('id', $token['memberId'])
                ->field('id staff_id, token mer_id, store_id, name')
                ->find();
            if(empty($storeStaff['mer_id'])){
                throw_exception("未登录",\Exception::class, 1002);
            }
            request()->staff_info = $storeStaff;
            request()->log_uid = $storeStaff['mer_id'];
        }
        parent::initialize();
    }
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 参数验证
     * @param array $params
     * @param string $scenario
     * @return array
     */
    private function validateParameter(array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input('post.');

        $validate = validate(MerchantStoreRobot::class);
        if (!$validate->scene($scenario ?: 'merchant_' . Str::snake(request()->action()))->check($params)) {
            throw_exception($validate->getError(), ParametersException::class);
        }
        return $params;
    }
    //endregion
    
    //region 机器人店铺关系
    /**
     * 机器人列表
     */
    public function robotList()
    {
        $name = $this->request->post('name', '', 'trim');
        
        $noPointRobotId = app(ThirdRobotDeviceService::class)->noPointRobotId();
        $where = [
            ['del_time', '=', 0],
        ];
        $name && $where[] = ['name', 'like', '%' . $name . '%'];
        $noPointRobotId && $where[] =  ['id','NOT IN', $noPointRobotId];
        
        $result = (new ThirdRobotDeviceService())->getRobotList(
            $where, true, 
            input('page',0), 
            input('page_size',0), 
            'id DESC', true
        );
        
        return api_output(0, $result);
    }

    /**
     * 点位列表
     */
    public function robotBizPointList()
    {
        $robot_id = $this->request->post('robot_id', 0, 'intval');
        $page     = $this->request->post('page', 1, 'intval');
        $pageSize = $this->request->post('page_size', 20, 'intval');
        $name     = $this->request->post('name', '', 'trim');
        $map_code = $this->request->post('map_code', '', 'trim');
        if (!$robot_id) {
            return api_output_error(1001, "缺少机器人id");
        }
        try {
            (new ThirdRobotDeviceCmdService())->jobToGetRobotsBizPoints();
            $thirdRobotDeviceService = new ThirdRobotDeviceService();
            
            $whereThirdRobot = [
                ['id', '=', $robot_id]
            ];
            
            $robotInfo  = $thirdRobotDeviceService->getOneDataInfo($whereThirdRobot, 'id, robot_code, name, robot_type');
            $robot_code = isset($robotInfo['robot_code']) && $robotInfo['robot_code'] ? $robotInfo['robot_code'] : '';
            $where      = [];
            $where[]    = ['robot_code', '=', $robotInfo['robot_code']];
            $where[]    = ['type', '=', 1];
            $where[]    = ['delete_time', '=', 0];
          
            $name && $where[] = ['name', 'like', '%' . $name . '%'];
            $map_code && $where[] = ['map_code', '=', $map_code];

            $field  = 'id, point_id, name, map_name, phone, floor';
            $result = $thirdRobotDeviceService->getRobotBizPointList($where, $field, $page, $pageSize);
            
            $result['robot_name'] = $robotInfo['name'];
            $result['robot_img']  = $thirdRobotDeviceService->getRobotImg($robotInfo['robot_type']);

            $maps = $thirdRobotDeviceService->getRobotBizMapsList([
                ['robot_code', '=', $robot_code],
                $where[] = ['delete_time', '=', 0],
            ], true, 0);
            
            $dropdownOptions = [];
            if (count($maps['list']) > 1) {
                foreach ($maps['list'] as $item) {
                    $dropdownOptions[] = [
                        'label' => $item['map_name'],
                        'value' => strval($item['map_code']),
                    ];
                }
            }
            $result['dropdownOptions'] = $dropdownOptions;
            $result['dropdownValue']   = $map_code;
            $result['dropdownTitle']   = '地图名称';

            $static              = cfg('site_url') . '/v20/public/static/third_robot/tools/';
            $result['recordImg'] = $static . 'record.png';
            $result['recordUrl'] = get_base_url('pages/robot/call_history?robot_id=' . $robot_id);
            $result['backImg']   = $static . 'back.png?t=01';
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 添加机器人
     * @return \think\response\Json
     */
    public function saveStoreRobot()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'store_id',
            'robot_code',
            'map_point_id'
        ]));
        $params['mer_id'] = $this->merId;
        $data = app(ThirdRobotMerchantStoreService::class)->saveStoreRobot($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 店铺解绑机器人
     * @return \think\response\Json
     */
    public function delMerchantStoreRobot()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'store_id', 'store_robot_id', 'robot_code'
        ]));

        $storeRobot = ThirdRobotMerchantStoreRobot::where([
            'id'         => $params['store_robot_id'],
            'store_id'   => $params['store_id'],
            'robot_code' => $params['robot_code'],
            'is_del'     => ThirdRobotMerchantStoreRobot::IS_DEL_0
        ])->find();
        
        empty($storeRobot) && throw_exception("当前店铺机器人不存在！");
        
        $params['is_del'] = ThirdRobotMerchantStoreRobot::IS_DEL_1;
        $params['delete_time'] = date('Y-m-d H:i:s');
        $data = app(ThirdRobotMerchantStoreService::class)->saveStoreRobot($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 店铺机器人列表
     * @return \think\response\Json
     */
    public function merchantStoreRobotList()
    {
        $params = input('post.');
        $data = app(ThirdRobotMerchantStoreService::class)->storeRobotList($params);
        return api_output(self::STATUS_OK, $data);
    }
    //endregion
}