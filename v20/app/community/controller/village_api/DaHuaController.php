<?php


namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\DaHuaService;
use app\community\model\service\FaceDeviceService;
use app\consts\DahuaConst;
use app\consts\DeviceConst;

class DaHuaController extends CommunityBaseController
{
    public function index()
    {
        $dahua_service = new DaHuaService();
        $authInfo = $dahua_service->getToken();
        $data = $dahua_service->cardInfo($authInfo,'','89898989');
        return api_output(0,$data);
    }

    public function openBatch()
    {
        $dahua_service = new DaHuaService();
        $authInfo = $dahua_service->getToken();
        $data = $dahua_service->openBatch($authInfo,4,12123436,'0','0','ACTIVE','2021-12-8','2022-12-8','1234565','1');
        return api_output(0,$data);
    }

    public function add()
    {
        $dahua_service = new DaHuaService();
        $authInfo = $dahua_service->getToken();
        $data = $dahua_service->addPerson($authInfo,'身份证','341281199004249241',$name='小明','66666666',12,$sex='男',$birthday='2018-11-03',$phone='17755159368',$status='在职',16);
        return api_output(0,$data);
    }

    /**
     * 设置 大华生成的楼栋单元楼层房屋  支持批量
     * POST
     * [
     *    'buildingNameStrings'          => '1号楼,2号楼', // 支持多个楼栋名称 英文逗号分隔
     *    'buildingNumberStrings'        => '001,999', // 支持多个楼栋编号 英文逗号分隔 注意需要和名称 数量相同位置对应一一匹配
     *    'unitNum'                      => 50, // 同一个楼栋下单元数量  [1,9]  由于生成不可变动 所以建议以数量多的为准
     *    'floorNum'                     => 50, // 同一个单元下楼层数量  [1,99] 由于生成不可变动 所以建议以数量多的为准
     *    'unitNum'                      => 50, // 同一个楼层下房间数量  [1,99] 由于生成不可变动 所以建议以数量多的为准
     * ]
     * @return array
     */
    public function setDHBuildingToDeviceCloud() {
        $village_id = $this->adminUser['village_id'];
        $faceDeviceService = new FaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $buildingNameStrings        = $this->request->post('buildingNameStrings');
        $buildingNames              = explode(',',$buildingNameStrings);
        $buildingNumberStrings      = $this->request->post('buildingNumberStrings');
        $buildingNumbers            = explode(',',$buildingNumberStrings);

        $unitNum                    = $this->request->post('unitNum');
        $floorNum                   = $this->request->post('floorNum');
        $houseNum                   = $this->request->post('houseNum');
        $auto_syn                   = $this->request->post('auto_syn', 0);
        if (count($buildingNames) != count($buildingNumbers)) {
            return api_output_error(1001, '请注意【楼栋序号】和【楼栋名称】需要一一对应匹配');
        }
        foreach ($buildingNumbers as $buildingNumber)  {
            if ($buildingNumber < 1 || $buildingNumber > 999) {
                return api_output_error(1001, '请注意【楼栋序号】限定1-999');
            }
        }
        $param = [
            'village_id'       => $village_id,
            'buildingNames'    => $buildingNames,
            'buildingNumbers'  => $buildingNumbers,
            'unitNum'          => $unitNum,
            'floorNum'         => $floorNum,
            'houseNum'         => $houseNum,
            'auto_syn'         => $auto_syn,
        ];
        $arr = $faceDeviceService->singleDHBuildingToDeviceCloud($param);
        if (isset($arr['code']) && $arr['code']>0 && $arr['msg']) {
            return api_output_error($arr['code'], $arr['msg']);
        }
        if (isset($arr['data']) && !empty($arr['data'])) {
            $arr = $arr['data'];
        } else {
            $arr = [];
        }
        return api_output(0,$arr);
    }
    
    /**
     * 直接获取大华生成的楼栋单元楼层房屋
     * POST
     * [
     *    'page'        => 1, // 页数 默认1
     *    'limit'       => 50, // 每页条数 默认50
     *    'orgType'     => 100, // 10-楼栋、11-单元、12-房屋  默认10
     *    'pOrgCode'    => 100, // 父组织编码过滤
     * ]
     * @return \json
     * @throws \think\Exception
     */
    public function getDHBuidingUnitRoomList() {
        $village_id = $this->adminUser['village_id'];
        $faceDeviceService = new FaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pageNum  = $this->request->post('page',1);
        $pageSize = $this->request->post('limit',20);
        $orgType  = $this->request->post('orgType',10);
        $pOrgCode = $this->request->post('pOrgCode','');
        $param = [
            'pageNum'       => $pageNum,
            'pageSize'      => $pageSize,
            'orgType'       => $orgType,
            'pOrgCode'      => $pOrgCode,
            'village_id'    => $village_id,
            'thirdProtocol' => DeviceConst::DH_YUNRUI,
        ];
        $arr = $faceDeviceService->getDhBuidingUnitRoomList($param);
        if (isset($arr['data']) && !empty($arr['data'])) {
            $arr = $arr['data'];
        } else {
            $arr = [];
        }
        return api_output(0,$arr);
    }

    /**
     * 绑定楼栋单元楼层房屋
     * POST
     * [
     *    'bindId'        => 1, // 要绑定的对象id
     *    'relatedType'   => 'room', // 绑定类型
     *    'orgParam'      => [
     *                          ], // 从大华云睿获取的数据原样传参
     * ]
     * @return \json
     * @throws \think\Exception
     */
    public function bindDHBuildUnitRoom() {
        $village_id = $this->adminUser['village_id'];
        $faceDeviceService = new FaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $bindId             = $this->request->post('bindId');
        $relatedType        = $this->request->post('relatedType');
        $orgParam           = $this->request->post('orgParam');
        $param = [
            'village_id'            => $village_id,
            'relatedType'           => $relatedType,
        ];
        $arr = $faceDeviceService->bindDHBuildUnitRoom($bindId, $param, $orgParam);
        if (isset($arr['code']) && $arr['code']>0 && $arr['msg']) {
            return api_output_error($arr['code'], $arr['msg']);
        }
        if (isset($arr['data']) && !empty($arr['data'])) {
            $arr = $arr['data'];
        } else {
            $arr = [];
        }
        return api_output(0,$arr);
    }

    /**
     * 获取自身平台楼栋单元
     * @return \json
     */
    public function bindDHBuildUnitRoomsList() {
        //TODO 后期可以考虑分页。一般一个小区楼栋也不会太多。
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $orgType             = $this->request->post('orgType');
        $parent_bind_id      = $this->request->post('parent_bind_id');
        $arr = [];
        try{
            $faceDeviceService = new FaceDeviceService();
            $arr['list'] = $faceDeviceService->bindDHVillageSingleList($village_id, $orgType, $parent_bind_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$arr);
    }

    /**
     * 查询设备已经绑定的权限
     * @return \json
     */
    public function deviceBindAuthList() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id  = $this->request->post('device_id');
        if (empty($device_id)){
            return api_output(1001, [], '缺少设备id！');
        }
        $faceDeviceService = new FaceDeviceService();
        try{
            $param = [
                'village_id' => $village_id,
            ];
            $arr = $faceDeviceService->deviceBindAuthList($device_id, $param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$arr);
    }

    /**
     * 获取对应设备未绑定的小区工作人员
     * @return \json
     */
    public function deviceVillageWorksAuth() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id  = $this->request->post('device_id');
        if (empty($village_id)){
            return api_output(1001, [], '缺少设备id！');
        }
        $faceDeviceService = new FaceDeviceService();
        try{
            $param = [
                'village_id' => $village_id,
            ];
            $arr = $faceDeviceService->deviceVillageWorksAuth($device_id, $param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$arr);
    }

    /**
     * 添加设备相关权限
     * @return \json
     */
    public function addDeviceAuth() {
        $village_id   = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id    = $this->request->post('device_id');
        if (empty($device_id)){
            return api_output(1001, [], '缺少设备id！');
        }
        $workIds      = $this->request->post('workIds');
        $checkedKeys  = $this->request->post('checkedKeys');
        if (empty($workIds) && empty($checkedKeys)){
            return api_output(1001, [], '请最少选择一个绑定对象！');
        }
        $faceDeviceService = new FaceDeviceService();
        try{
            $param = [
                'village_id'  => $village_id,
                'workIds'     => $workIds,
                'checkedKeys' => $checkedKeys,
            ];
            $arr = $faceDeviceService->addDeviceAuth($device_id, $param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$arr);
    }

    /**
     * 删除权限
     * @return \json
     */
    public function delDeviceAuth() {
        $village_id   = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id    = $this->request->post('device_id');
        if (empty($device_id)){
            return api_output(1001, [], '缺少设备id！');
        }
        $auth_id      = $this->request->post('auth_id');
        if (empty($auth_id)){
            return api_output(1001, [], '权限id！');
        }
        $faceDeviceService = new FaceDeviceService();
        try{
            $param = [
                'village_id'  => $village_id,
                'auth_id'     => $auth_id,
            ];
            $arr = $faceDeviceService->delDeviceAuth($device_id, $param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$arr);
    }
}