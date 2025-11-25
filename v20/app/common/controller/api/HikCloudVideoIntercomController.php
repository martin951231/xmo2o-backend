<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      代码功能描述
 */

namespace app\common\controller\api;

use app\common\controller\CommonBaseController;
use app\community\model\service\Device\DeviceHkNeiBuHandleService;
use think\App;

class HikCloudVideoIntercomController extends CommonBaseController
{

    /**
     * @var string 可视对讲操作类型：
     *      request    请求呼叫
     *      cancel    取消本次呼叫
     *      hangUp    结束本次通话
     */
    protected $cmdType = '';
    
    public function __construct(App $app)
    {
        parent::__construct($app);
    }
    
    // with client_id
    // domain + /v20/public/index.php/common/api.HikCloudVideoIntercom/invokeMsg
    public function invokeMsg()
    {
        
        $post = file_get_contents('php://input');
        if (!empty($post)){
            $post = \json_decode($post,true);
        }
        
        fdump_api(['$post'=>$post],'hikCloudVideoIntercomControllerLog',true);

//        $post = [
//            'deviceSerial' => 'J89631662', 'devicePath' => "9d2dfaa1561a48be80cca15c76ea7cfd/a3a73bc92e32474ea72f84e14d21b13e/8de8a5cef9ac48fea1d8a4f3a917da00",
//            'dateTime' => '2023-08-23T10:43:30+08:00', 'cmdType' => 'request', "periodNumber" => 1, "buildingNumber" => 3, "unitNumber" => 1, "floorNumber" => 3,
//            'roomNumber' => 1, 'unitType' => 'wall', 'communityId' => '9d2dfaa1561a48be80cca15c76ea7cfd', 'stageId' => '43dfa9afe6a34ebeba91392c62b72a2e',
//            'buildingId' => 'a3a73bc92e32474ea72f84e14d21b13e','unitId' => '8de8a5cef9ac48fea1d8a4f3a917da00','deviceName' => '三栋一单元',
//        ];
        $deviceSerial = $post['deviceSerial']; //设备序列号
        $dateTime = $post['dateTime']; //消息时间（UTC+08:00）
        $this->cmdType = $post['cmdType']; //操作类型
        $periodNumber = $post['periodNumber'];//期号
        $buildingNumber = $post['buildingNumber']; //楼号
        $unitNumber = $post['unitNumber']; //单元号
        $floorNumber = $post['floorNumber']; // 层号
        $roomNumber = $post['roomNumber']; // 房间号
        $devIndex = $post['devIndex'] ?? ''; //设备序号
        $unitType = $post['unitType']; //类型: outdoor门口机，wall围墙机
        $devicePath = $post['devicePath']; //设备位置路径
        $buildingId = $post['buildingId'];
        $unitId = $post['unitId'];

        //海康会推送呼叫（request）、接听（cancel）、挂断（hangUp）。如果不是呼叫，则不继续通知，原来挂断的也会呼叫导致BUG。
        if($this->cmdType != 'request'){
            return false;
        }
        $data = [
            'deviceSerial' => $deviceSerial,
            'dateTime' => $dateTime,
            'cmdType' => $this->cmdType,
            'periodNumber' => $periodNumber,
            'buildingNumber' => $buildingNumber,
            'unitNumber' => $unitNumber,
            'floorNumber' => $floorNumber,
            'roomNumber' => $roomNumber,
            'devIndex' => $devIndex,
            'unitType' => $unitType,
            'devicePath' => $devicePath,
        ];
        try {
            $info = (new DeviceHkNeiBuHandleService())->sendPush($data);
        } catch (\Exception $e) {
            fdump_api(['err'=>$e->getMessage(), 'line'=>$e->getLine(), 'code'=>$e->getCode()],'errHikCloudVideoIntercomControllerLog',true);
        }
        //TODO
        $msg = [
            'code' => 200,
            'message' => '操作成功'
        ];
        if (!$info) {
            $msg = [
                'code' => 201,
                'message' => '呼叫对象不存在'
            ];
        }
        echo \json_encode($msg);
    }
}