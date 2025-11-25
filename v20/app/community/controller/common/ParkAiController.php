<?php
/**
 * /v20/public/index.php/community/common.ParkAi/indexOpt
 */

namespace app\community\controller\common;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewAiParkingService;

class ParkAiController extends CommunityBaseController
{


    public function indexOpt(){
        $input = file_get_contents("php://input");
        $postArr=array();
        if($input){
            $postArr= json_decode($input,true);
        }
        fdump_api(['input'=>$input,'postArr'=>$postArr],'parkAi/00indexOpt'.date('H'),1);
        $returnArr=array('code'=>'1','msg'=>'','rand'=>rand(1,9).'.'.date('His').rand(100,999),'sign'=>'');
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        if(empty($postArr)){
            $returnArr['code']='0';
            $returnArr['msg']='未收到数据';
            // $baseInfo=$houseNewAiParkingServic->getApiBaseInfo(0);
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        if(!isset($postArr['method']) || empty($postArr['method'])){
            $returnArr['code']='0';
            $returnArr['msg']='请求接口方法不存在！';
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        if(!isset($postArr['data']) || empty($postArr['data'])){
            $returnArr['code']='0';
            $returnArr['msg']='数据体为空';
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        $park_key=isset($postArr['data']['key']) ? $postArr['data']['key']:'';
        
        $parkingGarage=$houseNewAiParkingServic->getVillageParkingGarage($park_key);
        if($parkingGarage && $parkingGarage['village_id']<1){
            $returnArr['code']='0';
            $returnArr['msg']='车场不存在！';
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        $method=trim($postArr['method']);
        switch ($method){
            case 'Car_EnterCar':
                $this->carInPark($postArr,$parkingGarage);
                break;
            case 'Car_OutCar':
                $this->carOutPark($postArr,$parkingGarage);
                break;
            case 'Car_AboutCar':
                //异常出场
                $this->carOutParkException($postArr,$parkingGarage);
                break;
            case 'Car_MthCarIssueSend':
                $this->addNewCar($postArr,$parkingGarage);
                break;
            case 'Car_MthCarFailSend':
                $this->cancelCarTime($postArr,$parkingGarage);
                break;
            case 'Car_MthCarChargeSend':
                $this->updateCarTime($postArr,$parkingGarage);
                break;
            case 'Car_UpdateCar':
                $this->updateCarAccessRecordCar($postArr,$parkingGarage);
                break;
            case 'Park_SendParkSpace':
                try{
                    $res=$houseNewAiParkingServic->updateParkingGarageInfo($postArr['data'],$parkingGarage);
                    echo json_encode($res,JSON_UNESCAPED_UNICODE);
                }catch (\Exception $e){
                    $returnArr['code']='0';
                    $returnArr['msg']=$e->getMessage();
                    $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
                    $returnArr['sign']=$dataSign;
                    echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'Car_BlackList':
                //黑名单
                try{
                    $res=$houseNewAiParkingServic->updateParkCarBlack($postArr['data'],$parkingGarage);
                    echo json_encode($res,JSON_UNESCAPED_UNICODE);
                }catch (\Exception $e){
                    $returnArr['code']='0';
                    $returnArr['msg']=$e->getMessage();
                    $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
                    $returnArr['sign']=$dataSign;
                    echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
                }
                break;
            default:
                $returnArr['code']='1';
                $returnArr['msg']='未处理的请求！';
                $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
                $returnArr['sign']=$dataSign;
                echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
                exit();  
        }
        exit();
    }
    public function carInPark($postArr=array(),$parkingGarage=array())
    {
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        $returnArr=array('code'=>'1','msg'=>'','rand'=>rand(1,9).'.'.date('His').rand(100,999),'sign'=>'');
        if(empty($postArr)){
            $returnArr['code']='0';
            $returnArr['msg']='未收到数据';
           // $baseInfo=$houseNewAiParkingServic->getApiBaseInfo(0);
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        if(!isset($postArr['method']) || ($postArr['method']!='Car_EnterCar')){
            $returnArr['code']='0';
            $returnArr['msg']='数据错误，不是入场数据';
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        try{
            $res=$houseNewAiParkingServic->addInParkRecord($postArr);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    
    public function carOutParkException($postArr=array(),$parkingGarage=array()){
        $returnArr=array('code'=>'1','msg'=>'','rand'=>rand(1,9).'.'.date('His').rand(100,999),'sign'=>'');
        try{
            $houseNewAiParkingServic=new HouseNewAiParkingService();
            $postArr['exception_type']=2;
            $res=$houseNewAiParkingServic->addOutParkRecord($postArr);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    public function carOutPark($postArr=array(),$parkingGarage=array())
    {
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        $returnArr=array('code'=>'1','msg'=>'','rand'=>rand(1,9).'.'.date('His').rand(100,999),'sign'=>'');
        if(empty($postArr)){
            $returnArr['code']='0';
            $returnArr['msg']='未收到数据';
            // $baseInfo=$houseNewAiParkingServic->getApiBaseInfo(0);
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
        if(!isset($postArr['method']) || ($postArr['method']!='Car_OutCar')){
            $returnArr['code']='0';
            $returnArr['msg']='数据错误，不是出场数据';
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }

        try{
            $res=$houseNewAiParkingServic->addOutParkRecord($postArr);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    public function addNewCar($postArr=array(),$parkingGarage=array()){
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        try{
            $res=$houseNewAiParkingServic->addNewOneCar($postArr['data'],$parkingGarage);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    public function cancelCarTime($postArr=array(),$parkingGarage=array()){
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        try{
            $res=$houseNewAiParkingServic->cancelCarTime($postArr['data'],$parkingGarage);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
    public function updateCarTime($postArr=array(),$parkingGarage=array()){
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        try{
            $res=$houseNewAiParkingServic->updateCarTime($postArr['data'],$parkingGarage);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function updateCarAccessRecordCar($postArr=array(),$parkingGarage=array()){
        $houseNewAiParkingServic=new HouseNewAiParkingService();
        try{
            $res=$houseNewAiParkingServic->updateCarAccessRecordCar($postArr['data'],$parkingGarage);
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            exit();
        }catch (\Exception $e){
            $returnArr['code']='0';
            $returnArr['msg']=$e->getMessage();
            $dataSign=$houseNewAiParkingServic->getReturnThirdDataSign(0,$returnArr);
            $returnArr['sign']=$dataSign;
            echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
}