<?php
/**
 * 访问地址 /v20/public/index.php/community/common.EasyMeterApi/receiveMeterInfo
 * 
 */

namespace app\community\controller\common;
use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\NbLotEasyMeterApiService;

class EasyMeterApiController extends CommunityBaseController
{
    
    public function receiveMeterInfo(){

        $input = file_get_contents("php://input");
        $returnArr=array('status'=>'success','message'=>'推送成功');
        try{
            $nbLotEasyMeterApiService=new NbLotEasyMeterApiService();
            fdump_api(['_Line_' . __LINE__, 'input' => $input], 'easymeter/receiveMeterInfo' . date('H') , 1);
            $meterInfo=array();
            if($input){
                $meterInfo=json_decode($input,true);
            }
            if(empty($meterInfo)){
                $returnArr['status']='fail';
                $returnArr['message']=$input ?  '推送失败，数据格式错误！':'推送失败，数据为空！';
                echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
                exit();
            }
            $nbLotEasyMeterApiService->receiveMeterData($meterInfo);
        }catch (\Exception $e){
            fdump_api(['_Line_' . __LINE__, 'dataArr' => $e->getMessage(), 'input' => $input], 'easymeter/receiveMeterInfo' . date('H') , 1);
        }
        echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
        exit();
    }

}