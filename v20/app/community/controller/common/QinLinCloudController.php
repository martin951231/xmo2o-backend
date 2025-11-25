<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/8/15 9:21
 */
namespace app\community\controller\common;


use app\community\controller\CommunityBaseController;
use app\community\model\service\Park\QinLinCloudService;

class QinLinCloudController extends CommunityBaseController{

    /**
     * D7亲邻停车系统车辆入场信息回调
     */
    public function inPark()
    {
       // $jsonData = file_get_contents('php://input');
       // fdump_api([$jsonData,json_decode($jsonData,true)],'D7park/inPark',true);
        $jsonData='{"data":"{\\"areaFlag\\":0,\\"carType\\":\\"36\\",\\"carnumber\\":\\"粤BF12345\\",\\"dbSign\\":1,\\"emptyPlot\\":100,\\"id\\":767741649987043344,\\"inChannelId\\":818,\\"inChannelName\\":\\"出口\\",\\"inImageUrl\\":\\"http://oss-cn-yuxi-ynlthyy-d01-a.res.7caiwo.com/zhql-oss/camera/20220820/79102e98-722e77d6/8b17867bcf9c42b9bc77b0152f947591.jpg\\",\\"inOptinfo\\":\\"-1\\",\\"intime\\":1660973433,\\"licensePlateColor\\":\\"绿色\\",\\"orderId\\":\\"a7418590-8b57-437c-968b-72e1d6455989\\",\\"parkId\\":10023061,\\"parkingArea\\":\\"地下停车场\\",\\"parkingAreaid\\":615,\\"status\\":0,\\"tableShard\\":202208}","serviceName":"inPark","sign":"f8f003af38507c8934ea0ede8fddcfe5","timeStamp":1660973473321}';
        if (!empty($jsonData)){
            $arrData = json_decode($jsonData,true);
            $data=json_decode($arrData['data'],true);
            $service_qinLinCloud=new QinLinCloudService();
            try {
                $res=$service_qinLinCloud->inPark($data);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
        }
        echo json_encode(['code' => 200, 'msg' => 'OK']);
        exit;
    }

    /**
     * D7亲邻停车系统车辆入场信息回调
     */
    public function outPark()
    {
        //$jsonData = file_get_contents('php://input');
      //  fdump_api([$jsonData,json_decode($jsonData,true)],'D7park/outPark',true);
        $jsonData ='{"data":"{\\"areaFlag\\":0,\\"carType\\":\\"36\\",\\"carnumber\\":\\"粤BF12345\\",\\"emptyPlot\\":100,\\"id\\":767741649987043325,\\"inChannelId\\":818,\\"inChannelName\\":\\"出口\\",\\"inImageUrl\\":\\"http://oss-cn-yuxi-ynlthyy-d01-a.res.7caiwo.com/zhql-oss/camera/20220820/79102e98-722e77d6/8b17867bcf9c42b9bc77b0152f947591.jpg\\",\\"inOptinfo\\":\\"-1\\",\\"intime\\":1660973493,\\"licensePlateColor\\":\\"绿色\\",\\"orderId\\":\\"a7418590-8b57-437c-968b-72e1d6455989\\",\\"outChannelId\\":818,\\"outChannelName\\":\\"出口\\",\\"outImageUrl\\":\\"http://oss-cn-yuxi-ynlthyy-d01-a.res.7caiwo.com/zhql-oss/camera/20220820/79102e98-722e77d6/5a148669254d409ba97a33e8e48fd120.jpg\\",\\"outOptinfo\\":\\"-1\\",\\"outtime\\":1660976199,\\"parkId\\":10023061,\\"parkingArea\\":\\"地下停车场\\",\\"parkingAreaid\\":615,\\"parkingTime\\":45,\\"parkingTimes\\":\\"45分\\",\\"status\\":1}","serviceName":"outPark","sign":"4a3a4c733b7729be25d9d09aa3036cfd","timeStamp":1660976475407}';
        if (!empty($jsonData)){
            $arrData = json_decode($jsonData,true);
            $data=json_decode($arrData['data'],true);
            $service_qinLinCloud=new QinLinCloudService();
            try {
                $res=$service_qinLinCloud->outPark($data);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
        }
        echo json_encode(['code' => 200, 'msg' => 'OK']);
        exit;
    }

}