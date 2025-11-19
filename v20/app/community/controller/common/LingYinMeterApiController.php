<?php
/**
 * 访问地址 /v20/public/index.php/community/common.LingYinMeterApi/receiveMeterInfo
 * 
 */

namespace app\community\controller\common;
use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\LingYinPfMeterApiService;

class LingYinMeterApiController extends CommunityBaseController
{
    
    public function receiveMeterInfo(){

        $input = file_get_contents("php://input");
        $returnArr=array('code'=>0,'message'=>'Success','data'=>array("successCount"=>0,"failedCount"=>0, "waterFailedItems"=>array(),"eleFailedItems"=>array()));
        try{
            $lingYinPfMeterApiService=new LingYinPfMeterApiService();
            fdump_api(['_Line_' . __LINE__, 'input' => $input], 'lingyinmeter/receiveMeterInfo' . date('H') , 1);
            $meterInfo=array();
            if($input){
                $meterInfo=json_decode($input,true);
            }
            if(empty($meterInfo) || !isset($meterInfo['data'])){
                $returnArr['code']=1;
                $returnArr['message']=$input ?  '推送失败，数据格式错误！':'推送失败，数据为空！';
                echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
                exit();
            }
            $allCount=count($meterInfo['data']);
            $returnArr['data']['successCount']=$allCount;
            $lingYinPfMeterApiService->receiveMeterData($meterInfo['data']);
        }catch (\Exception $e){
            fdump_api(['_Line_' . __LINE__, 'dataArr' => $e->getMessage(), 'input' => $input], 'lingyinmeter/receiveMeterInfo' . date('H') , 1);
        }
        echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
        exit();
    }

}