<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/8/11 10:44
 */
namespace app\community\model\service\Park;

use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\InPark;
use app\community\model\db\NolicenceInPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkPassage;
use app\community\model\service\HouseVillageParkGarageConfigService;
use think\Exception;

class QinLinCloudService{
   //  public $appId = 'ckXbLj4y';//快鲸开发测试
    public $appId ='ckXbLj4y';//快鲸开发测试
    // public $appSecret = 'c8e3534971774497a87d96b4dc867ab7';//快鲸开发测试
    public $appSecret = 'c8e3534971774497a87d96b4dc867ab7';//快鲸开发测试
   //  public $url='https://gw.ft.lc666.vip/parking-open-api/parking/api';
    public $url='https://gw.ft.lc666.vip/parking-open-api/parking/api';
    public $month_car_type=['32','3B','3C','3D','51','52','53','54'];//月租车车辆类型
    public $temp_car_type=['36','37','38','39','43','44','45','46'];//临时车
    public $stored_car_type=['33','34','35','3A'];//储值车
    public $free_car_type=['41','3E'];//免费车

    /**
     * 获取签名
     * string  $data  业务参数对象
     * string $serviceName 业务名称
     * string $timeStamp 时间戳
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:36
     */
    public function get_sign($data,$serviceName,$timeStamp){
      // $data=json_encode($data);
        $data_str='data='.$data.'&serviceName='.$serviceName.'&timeStamp='.$timeStamp.'&secret='.$this->appSecret;
        //  print_r($data_str);die;
        $sign = md5($data_str);
        return $sign;
    }


    /*------------------下行接口start--------------------*/

    /**
     * 下行请求相关接口
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:28
     */
    public function download($param){
        $headers = [
            "appId:".$this->appId,
        ];
        $headers[] = "Content-type: application/json;charset=utf-8";
        $timeStamp=time().'00';
        $sign=$this->get_sign($param['data'],$param['serviceName'],$timeStamp);
        $post=[
            'data'=> $param['data'],
            'timeStamp'=> $timeStamp,
            'serviceName'=>$param['serviceName'],
            'sign'=> $sign,
        ];
       //  print_r(json_encode($post));die;
        $res = http_request($this->url, 'POST', json_encode($post,JSON_UNESCAPED_UNICODE), $headers);
        fdump_api([$res,$post,json_encode($post,JSON_UNESCAPED_UNICODE),$headers],'D7park/curl',1);
        if ($res[0]=200){
            return  json_decode($res[1],true);
        }
        return  $res;
    }

    /**
     * 查询车场信息
     * int $park_id 车场编号
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:18
     */
    public function getParkInfo($park_id=''){
       //  $park_id=10023061;
        $data=['parkId'=>$park_id];
        $serviceName='getParkInfo';
        $data=json_encode($data);
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }

    /**
     * 查询车场车道信息
     * int $park_id 车场编号
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:24
     */
    public function getChannels($park_id=''){
       //  $park_id=10023061;
        $data=['parkId'=>$park_id];
        $data=json_encode($data);
        $serviceName='getChannels';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }

    /**
     *查询⻋场套餐信息
     * int $park_id 车场编号
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:25
     */
    public function getParkRentMeal($park_id=''){
        $park_id=10023061;
        $data=['parkId'=>$park_id];
        $data=json_encode($data);
        $serviceName='getParkRentMeal';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     *黑名单操作（增删改）
     * int $park_id 车场编号
     * string $vehicleNo 车牌号
     * int  $operate 操作类型（1=添加 2=修改 3=删除）
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:25
     */
    public function getBlackList($park_id='',$vehicleNo='',$operate=1){
       /* $park_id=10023061;
        $vehicleNo='皖A12345';*/
        $data=['parkId'=>$park_id,'vehicleNo'=>$vehicleNo,'operate'=>$operate];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='blackList';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * 优惠计费查询
     * int $park_id 车场编号
     * string $orderNo 订单号
     * int  $freeTime 总优惠时⻓
     * int  $freeMoney 总优惠⾦额
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:25
     */
    public function reduceCharge($park_id,$orderNo,$freeTime,$freeMoney){
        $park_id=10023061;
        $data=['parkId'=>$park_id,'orderNo'=>$orderNo,'freeTime'=>$freeTime,'freeMoney'=>$freeMoney];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='reduceCharge';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }

    /**
     * 优惠券发放
     * int $park_id 车场编号
     * int $shopId 商户ID
     * int $couponType 优惠券类型（0-⾦额 1-时⻓ 2=全免）
     * int  $couponValue 优惠券额度（单位：元 或者 分钟）
     * int  $freeMoney 总优惠⾦额
     * int $stockId 库存ID（商户为购券商 户时必传）
     * string $remark 备注
     * sttring $carNumber ⻋牌号
     * int $source  数据来源（传固定值 2）
     * @author:zhubaodi
     * @date_time: 2022/8/11 13:25
     */
    public function coupon($park_id,$orderNo,$freeTime,$freeMoney){
        $park_id=10023061;
        $data=['parkId'=>$park_id,'orderNo'=>$orderNo,'freeTime'=>$freeTime,'freeMoney'=>$freeMoney];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='coupon';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * 访客预约
     * int $park_id 车场编号
     * int $shopId 商户ID
     * int $couponType 优惠券类型（0-⾦额 1-时⻓ 2=全免）
     * int  $couponValue 优惠券额度（单位：元 或者 分钟）
     * int  $freeMoney 总优惠⾦额
     * int $stockId 库存ID（商户为购券商 户时必传）
     * string $remark 备注
     * sttring $carNumber ⻋牌号
     * int $source  数据来源（传固定值 2）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function visitor($param){
       if (!empty($param)){
           $data=$param;
       }else{
           $data=[];
       }
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='visitor';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * ⽆牌⻋⼊场
     * int $park_id 车场编号
     * string $uid ⽤户ID（可为⼿机 号，也可为openid 等）
     * int $channelId ⻋道ID
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function noLicenseIn($park_id,$uid,$channelId){
        $data=['parkId'=>$park_id,'uid'=>$uid,'channelId'=>$channelId];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='noLicenseIn';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }

    /**
     *⽆牌⻋查询
     * int $park_id 车场编号
     * string $uid ⽤户ID（可为⼿机 号，也可为openid 等）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function noLicenseQuery($parkId,$uid){
       //  $park_id=10023061;
        $data=['parkId'=>$parkId,'uid'=>$uid];
        $data=json_encode($data);
        $serviceName='noLicenseQuery';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }

    /**
     * 多位多⻋发⾏
     * int $park_id 车场编号
     * string $carnumber ⻋牌号码（多个 ⻋牌以英⽂逗号 分隔）
     * int $ownerId  套餐ID
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function oneSpaceManyCars($parkId,$carnumber,$ownerId){
        $park_id=10023061;
        $data=['parkId'=>$park_id,'carnumber'=>$carnumber,'ownerId'=>$ownerId];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='oneSpaceManyCars';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * 发⾏⻋辆管理
     * int $park_id 车场编号
     * int $operate 操作类型（1添加 2修 改 3删除）
     * int $vehicleId  ⻋辆ID（修改 删除 必 传，该参数在添加⽉ 租⻋时会返回给第三 ⽅平台）
     * string  $vehicleNo ⻋牌号码
     * int  $carTypeCode ⻋类代码（⻅停⻋管 理平台⻋类管理）
     * string $userName ⼈员姓名
     * string $tels 联系电话
     * string $deptName 所属部⻔
     * int $ownerLots ⻋位数
     * int $payLots 付费⻋位数（查询⽉ 租⻋计费时已该⻋位 数计费⽤）
     * string $authChannel 授权⻋道（传ALL授权 所有⻋道，如需部分 ⻋道授权，请将⻋道 编号以英⽂逗号分隔 传递）
     * string $address  地址
     * string $idCard 身份证号
     * int $balance 余额（储值⻋余额）
     * int $beginTime 有效开始时间
     * int $endTime  有效结束时间
     * string $lotsNo ⻋位编号
     * int $userState ⽤户状态（0=正常 1= 报停）
     * int $buyMonth  购买⽉份（购买⽉ 份）
     * int $money  充值或续费⾦额（⽀ 付⾦额）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function whiteList($carData){
        fdump_api([$carData],'D7park/curl',1);
        $data['parkId']=$carData['park_id'];
        $data['operate']=$carData['operate'];
        $data['buyMonth']=1;
        $data['ownerLots']=1;
        $data['payLots']=1;
        $data['authChannel']='ALL';

        if (isset($carData['endTime'])&&!empty($carData['endTime'])){
            $data['beginTime']=$carData['beginTime'];
            $data['endTime']=$carData['endTime'];
            if (isset($carData['carTypeCode'])&&!empty($carData['carTypeCode'])&&in_array($carData['carTypeCode'],$this->month_car_type)){
                $data['carTypeCode']=$carData['carTypeCode'];
            }else{
                $data['carTypeCode']=32;
            }
        }else{
            $data['beginTime']=time();
            $data['endTime']=time();
        }
        if (isset($carData['balance'])&&!empty($carData['balance'])){
            $data['balance']=$carData['balance'];
            if (isset($carData['carTypeCode'])&&!empty($carData['carTypeCode'])&&in_array($carData['carTypeCode'],$this->stored_car_type)){
                $data['carTypeCode']=$carData['carTypeCode'];
            }else{
                $data['carTypeCode']=33;
            }
        }else{
            $data['balance']=0;
        }
        if (!isset($data['carTypeCode'])||empty($data['carTypeCode'])){
            if (isset($carData['carTypeCode'])&&!empty($carData['carTypeCode'])){
                $data['carTypeCode']=$carData['carTypeCode'];
            }else{
                $data['carTypeCode']=36;
            }
        }
        if (isset($data['operate'])&&!empty($data['operate'])&&$data['operate']!=1){
            $data['vehicleId']=$carData['vehicleId'];
            if (empty($data['vehicleId'])){
                return  false;
                throw new \think\Exception("第三方⻋辆ID不能为空，无法修改车辆");
            }
        }
        $data['vehicleNo']=$carData['vehicleNo'];
        if (isset($carData['carTypeCode'])&&!empty($carData['carTypeCode'])){
            $data['carTypeCode']=$carData['carTypeCode'];
        }else{
            if (isset($carData['endTime'])&&!empty($carData['endTime'])){
                $data['carTypeCode']=32;
            }else{
                $data['carTypeCode']=36;
            }
        }


        if (isset($carData['userName'])&&!empty($carData['userName'])){
            $data['userName']=$carData['userName'];
        }
        if (isset($carData['tels'])&&!empty($carData['tels'])){
            $data['tels']=$carData['tels'];
        }

        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='whiteList';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * ⽀付回调
     * int $park_id 车场编号
     * string $orderNo 订单编号
     * string $outTradeNo  外部订单编号
     * int  $tradeStatus ⽀付状态 （SUCCESS/FAIL）
     * int  $totalAmount ⽀付⾦额（单位： 分）
     * int $discountAmount 优惠⾦额（单位： 分）
     * int $pointDeductAmount 计费抵扣⾦额（但氛 围：分）
     * int $payTime ⽀付成功时间
     * int $payType ⽀付类型（0=现⾦⽀ 付 1=微信⽀付 2=⽀付宝⽀付 3=银联⽀付 4= 储值扣费 5=Etc）
     * int $payMethod ⽀付⽅式（CASH：现 ⾦⽀付 CASHSELF： ⾃助现⾦ JSAPIPAY：扫码⽀付 CODEPAY：条码付 AUTOPAY：⽆感⽀付 MINIPAY：⼩程序⽀ 付 ETC：etc扣费）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function notifyBill($pay_data){
        $data=[];
        $data['parkId']=$pay_data['parkId'];
        $data['orderNo']=$pay_data['orderNo'];
        $data['outTradeNo']=$pay_data['outTradeNo'];
        $data['tradeStatus']=$pay_data['tradeStatus'];
        $data['totalAmount']=$pay_data['totalAmount'];
        if (!empty($pay_data['discountAmount'])){
            $data['discountAmount']=$pay_data['discountAmount'];
        }
        if (!empty($pay_data['pointDeductAmount'])){
            $data['pointDeductAmount']=$pay_data['pointDeductAmount'];
        }
        $data['payTime']=$pay_data['payTime'];
        $data['payType']=$pay_data['payType'];
        $data['payMethod']=$pay_data['payMethod'];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='notifyBill';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * ⽉租⻋计费查询
     * int $parkId 车场编号
     * string $carnumber ⻋牌号码
     * string $packageId  套餐ID（5.2接⼝中获 取套餐ID）
     * int  $source 请求来源（固定传 1）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function queryRentBill($parkId,$operate,$vehicleId){
        $parkId=10023061;
        $data=['parkId'=>$parkId,'operate'=>$operate,'vehicleId'=>$vehicleId];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='queryRentBill';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * 发⾏⻋辆信息查询
     * int $parkId 车场编号
     * string $carnumber ⻋牌号码
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function queryRentVehicle($park_id='',$carnumber=''){
       /* $park_id=10023061;
        $carnumber='皖A12345';*/
        $data=['parkId'=>$park_id,'carnumber'=>$carnumber];
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='queryRentVehicle';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }


    /**
     * 临停计费查询
     * int $parkId 车场编号
     * int $queryType 查询场景（ 0=预付 1=出⼝⽀付）
     * string $channelId ⻋道ID（查询场景 为1时必传）
     * string $carnumber 查询⻋牌（查询场景为0时，必传）
     * int $source 查询来源（固定值 1）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function queryParkingBill($parkId,$queryType,$channelId='',$carnumber='',$uid='',$source=1){
        $data['parkId']=$parkId;
        $data['queryType']=$queryType;
        $data['source']=$source;
        if ($data['queryType']==1){
            if(empty($channelId)){
                $res=[];
                $res['error']=1;
                $res['msg']='车道id不能为空';
                return  $res;
            }
            $data['channelId']=$channelId;
            $data['uid']=$uid;
        }else{
            if (empty($carnumber)){
                $res=[];
                $res['error']=1;
                $res['msg']='车牌号不能为空';
                return  $res;
            }
            $data['carnumber']=$carnumber;
        }
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
        $serviceName='queryParkingBill';
        $post=[
            'data'=> $data,
            'serviceName'=> $serviceName,
        ];
        $res = $this->download($post);
        return  $res;
    }

    /*------------  下行接口end  --------*/

    /*------------  上行接口start  --------*/
    /**
     * ⻋辆⼊场回调
     * int $parkId 车场编号
     * int $queryType 查询场景（ 0=预付 1=出⼝⽀付）
     * string $channelId ⻋道ID（查询场景 为1时必传）
     * string $carnumber 查询⻋牌（查询场景为0时，必传）
     * string $uid  ⽤户ID
     * int $source 查询来源（固定值 1）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function inPark($data){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_bind_car=new HouseVillageBindCar();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_park_passage = new ParkPassage();
        $db_in_park = new InPark();
        $db_house_village=new HouseVillage();
        if (empty($data['parkId'])){
            return false;
            throw new Exception('停车场信息不存在');
        }
        /*
        $park_config=$db_house_village_park_config->getFind(['d7_park_id'=>$data['parkId'],'park_sys_type'=>'D7']);
        if (empty($park_config)||empty($park_config['d7_park_id'])){
            return false;
            throw new Exception('停车场信息不存在');
        }
        */
        if (!empty($data['carType'])){
            if (in_array($data['carType'],$this->month_car_type)){
                $c_type =5;
            }else{
                $c_type =9;
            }
        }else{
            $c_type =0;
        }
        $businessId='';
        $channel_info=$this->getChannels($data['parkId']);
        if (!empty($channel_info)&&!empty($channel_info['data'])){
            $channel_info['data']=json_decode($channel_info['data'],true);
            foreach ($channel_info['data'] as $v){
                if ($data['inChannelId']==$v['channelNo']){
                    $businessId=$v['businessId'];
                    break;
                }
            }
        }
        $passage_info=[];
        if (!empty($businessId)){
            $passage_info = $db_park_passage->getFind(['d7_channelId' => $businessId, 'status' => 1],'*');
        }
        if (empty($passage_info)){
            return '设备不存在';
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $whereArr=array();
        $whereArr[]=array('d7_park_id','=',$data['parkId']);
        $whereArr[]=array('park_sys_type','=','D7');
        $whereArr[]=array('village_id','=',$passage_info['village_id']);
        $park_config =$houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr,$garage_id);
        if (empty($park_config)||empty($park_config['d7_park_id'])){
            return false;
            throw new Exception('停车场信息不存在');
        }
        
        $in_park = [];
        $in_park['car_number'] = $data['carnumber'];
        $in_park['c_type'] = $c_type;
        $in_park['in_time'] = $data['intime'];
        $in_park['order_id'] = $data['orderId'];
        $in_park['empty_plot'] = $data['emptyPlot'];
        $in_park['in_channel_id'] = empty($passage_info)?0:$passage_info['id'];//通道号
        $in_park['park_id'] = $park_config['village_id'];//车场编号17317816787

        $insert_id=$db_in_park->insertOne($in_park);

        $accessType = 1;//	通行类型 1进  2出
        // 车场编号为小区 查询小区相关信息
       //  $village_info = M('House_village')->where(['village_id'=> $park_config['village_id']])->field('village_id,village_name')->find();
        $village_info =$db_house_village->getOne($park_config['village_id'],'village_id,village_name');

        // 查询对应车辆的现有绑定情况
        $province = mb_substr($data['carnumber'], 0, 1);
        $car_no = mb_substr($data['carnumber'], 1);
        $infos_car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']]);
        $bind_info=[];
        if ($infos_car_info && $infos_car_info['car_id']) {
            $bind_info= $db_house_village_bind_car->getFind(['car_id' => $infos_car_info['car_id']]);
        }
        // 记录车辆进入信息
        $car_access_record = [];
        $car_access_record['business_type'] = 0;
        $car_access_record['business_id'] = $park_config['village_id'];
        $car_access_record['car_number'] = $data['carnumber'];
        $car_access_record['accessType'] = $accessType;
        $car_access_record['accessImage'] = $data['inImageUrl'];
        $car_access_record['accessTime'] = $data['intime']?$data['intime']:time();
        $car_access_record['accessMode'] = $c_type;
        $car_access_record['park_sys_type'] = 'D7';
        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number']=empty($passage_info['channel_number'])?'未知':$passage_info['channel_number'];
        $car_access_record['channel_name']=empty($passage_info['passage_name'])?'未命名':$passage_info['passage_name'];
        $car_access_record['is_out'] = 0;
        $car_access_record['park_id'] = $park_config['village_id'];
        $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
        $car_access_record['empty_plot'] = $data['emptyPlot']?$data['emptyPlot']:'';
        $car_access_record['uid'] = ($bind_info&&$bind_info['uid'])?$bind_info['uid']:'';
        $car_access_record['car_id'] = ($infos_car_info&&$infos_car_info['car_id'])?$infos_car_info['car_id']:'';
        $car_access_record['bind_id'] = ($bind_info&&$bind_info['user_id'])?$bind_info['user_id']:'';
        $car_access_record['user_name'] = ($infos_car_info&&$infos_car_info['car_user_name'])?$infos_car_info['car_user_name']:'';
        $car_access_record['user_phone'] = ($infos_car_info&&$infos_car_info['car_user_phone'])?$infos_car_info['car_user_phone']:'';
        $car_access_record['order_id'] = $data['orderId'] ? $data['orderId']:'';
        $car_access_record['update_time'] = time();
        if ($insert_id) {
            $car_access_record['from_id'] = $insert_id;
        }

        $record_id=$db_house_village_car_access_record->addOne($car_access_record);
        if (!$record_id) {
            fdump_api(['添加车辆入场记录失败'.__LINE__,$data['carnumber'],$in_park,$car_access_record],'park/'.$park_config['village_id'].'/in_park_log',1);
        }
        $data1=[];
        $data1['state'] = 1;
        $data1['order_id'] = $data['orderId'];
        $data1['errmsg'] = '订单上传成功!';
        $data1['service_name'] = 'inpark';
        fdump_api(['订单上传成功'.__LINE__,$data,$in_park],'park/'.$park_config['village_id'].'/in_park_log',1);
       return $data1;
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * ⻋辆出场回调
     * int $parkId 车场编号
     * int $queryType 查询场景（ 0=预付 1=出⼝⽀付）
     * string $channelId ⻋道ID（查询场景 为1时必传）
     * string $carnumber 查询⻋牌（查询场景为0时，必传）
     * string $uid  ⽤户ID
     * int $source 查询来源（固定值 1）
     * @author:zhubaodi
     * @date_time: 2022/8/11 15:18
     */
    public function outPark($outParkData){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_house_village_parking_temp=new HouseVillageParkingTemp();
        $db_park_passage = new ParkPassage();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_bind_car=new HouseVillageBindCar();
        $db_house_village=new HouseVillage();
        if (empty($outParkData['parkId'])){
            return false;
            throw new Exception('停车场信息不存在');
        }
        /*
        $park_config=$db_house_village_park_config->getFind(['d7_park_id'=>$outParkData['parkId'],'park_sys_type'=>'D7']);
        if (empty($park_config)||empty($park_config['d7_park_id'])){
            return false;
            throw new Exception('停车场信息不存在');
        }
        */
        if (!empty($outParkData['carType'])){
            if (in_array($outParkData['carType'],$this->month_car_type)){
                $c_type =5;
            }else{
                $c_type =9;
            }
        }else{
            $c_type =0;
        }
        $businessId='';
        $channel_info=$this->getChannels($outParkData['parkId']);
        if (!empty($channel_info)&&!empty($channel_info['data'])){
            $channel_info['data']=json_decode($channel_info['data'],true);
            foreach ($channel_info['data'] as $v){
                if ($outParkData['outChannelId']==$v['channelNo']){
                    $businessId=$v['businessId'];
                    break;
                }
            }
        }
        $passage_info=[];
        if (!empty($businessId)){
            $passage_info = $db_park_passage->getFind(['d7_channelId' => $businessId, 'status' => 1],'*');
        }
        if (empty($passage_info)){
            return '设备不存在';
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $whereArr=array();
        $whereArr[]=array('d7_park_id','=',$outParkData['parkId']);
        $whereArr[]=array('park_sys_type','=','D7');
        $whereArr[]=array('village_id','=',$passage_info['village_id']);
        $park_config =$houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr,$garage_id);
        if (empty($park_config)||empty($park_config['d7_park_id'])){
            return false;
            throw new Exception('停车场信息不存在');
        }
        
        $car_number = $outParkData['carnumber'];
        $order_id = $outParkData['orderId'] ? $outParkData['orderId'] : '';
        $nowtime = date("ymdHis");
        $outParks = true;
        $house_village_parking_temp =  $db_house_village_parking_temp->getFind(['car_number'=>$outParkData['carnumber'],'village_id'=>$park_config['village_id'],'is_paid'=>1]);
        $where_in = [];
        if ($car_number) {
            $where_in['car_number'] = $car_number;
        }
        if ($order_id) {
            $where_in['order_id'] = $order_id;
        }
        $where_in['park_id'] = $park_config['village_id'];
        $where_in['is_out'] = 0;
        $in_park=$db_in_park->getOne1($where_in);
        if(empty($in_park)){
            fdump_api(['对应车辆无入场记录'.__LINE__,$where_in,$outParkData],'park/'.$park_config['village_id'].'/upload_outpark_log',1);
            $out_park = [];
            $out_park['park_id'] = $park_config['village_id'];
            $out_park['car_number'] = $car_number;
            $out_park['in_time'] = $outParkData['intime'];
            $out_park['out_time'] = $outParkData['outtime'];
            $out_park['total'] = $house_village_parking_temp['price'] ? $house_village_parking_temp['price'] : 0;
            $out_park['order_id'] = $outParkData['orderId'] ? $outParkData['orderId'] : '';
            $out_park['empty_plot'] = $outParkData['emptyPlot'] ? $outParkData['emptyPlot'] : '';
            $insert_id=$db_out_park->insertOne($out_park);
            fdump_api(['单独记录无入场车辆的出场记录'.__LINE__,$insert_id,$out_park],'park/'.$park_config['village_id'].'/upload_outpark_log',1);
        }else{
            if($in_park['in_time'] && $outParkData['intime'] && $in_park['in_time'] != $outParkData['intime']){
                // 两个入场时间不一致 以入场记录为准
                $in_time = $in_park['intime'];
                fdump_api(['两个入场时间不一致'.__LINE__,$outParkData,$in_park],'park/'.$park_config['village_id'].'/upload_outpark_log',1);
            } else {
                $in_time = $outParkData['intime'];
            }
            $out_park = [];
            $out_park['park_id'] = $in_park['park_id'];
            $out_park['car_number'] = $in_park['car_number'];
            $out_park['in_time'] = $in_time;
            $out_park['out_time'] =  $outParkData['outtime'];
            $out_park['total'] = $house_village_parking_temp['price'] ? $house_village_parking_temp['price'] : 0;
            $out_park['order_id'] = $outParkData['orderId'] ? $outParkData['orderId'] : '';
            $out_park['empty_plot'] = $outParkData['emptyPlot'] ? $outParkData['emptyPlot'] : '';
            $insert_id=$db_out_park->insertOne($out_park);
        }

        $accessType = 2;//通行类型 1进  2出
        $whereRecordIn = [];
        if ($car_number) {
            $whereRecordIn['car_number'] = $car_number;
        }
        if ($order_id) {
            $whereRecordIn['order_id'] = $order_id;
        }
        $whereRecordIn['park_id'] = $in_park['park_id'];
        $whereRecordIn['accessType'] = 1;
        $inRecordPark=$db_house_village_car_access_record->getOne($whereRecordIn);
        if ($inRecordPark && $inRecordPark['park_name']) {
            $park_name = $inRecordPark['park_name'];
        } else {
            // 车场编号为小区 查询小区相关信息
          //   $village_info = M('House_village')->where(['village_id'=> $in_park['park_id']])->field('village_id,village_name')->find();
            $village_info =$db_house_village->getOne($park_config['village_id'],'village_id,village_name');
            $park_name = $village_info['village_name']?$village_info['village_name']:'';
        }
        $uid = 0;
        if ($inRecordPark['uid']) {
            $uid = $inRecordPark['uid'];
        }
        $car_id = 0;
        if ($inRecordPark['car_id']) {
            $car_id = $inRecordPark['car_id'];
        }
        $bind_id = 0;
        if ($inRecordPark['bind_id']) {
            $bind_id = $inRecordPark['bind_id'];
        }
        $user_name = '';
        if ($inRecordPark['user_name']) {
            $user_name = $inRecordPark['user_name'];
        }
        $user_phone = '';
        if ($inRecordPark['user_phone']) {
            $user_phone = $inRecordPark['user_phone'];
        }
        // 查询对应车辆的现有绑定情况
        $bind_info=[];
        if (!$uid || !$car_id || !$bind_id || !$user_name || !$user_phone) {
            // 查询对应车辆的现有绑定情况
            $province = mb_substr($car_number, 0, 1);
            $car_no = mb_substr($car_number, 1);
            $infos_car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']]);
            if ($infos_car_info && $infos_car_info['car_id']) {
               //  $bind_info = D('House_village_bind_car')->where(['car_id' => $infos_car_info['car_id']])->find();
                $bind_info= $db_house_village_bind_car->getFind(['car_id' => $infos_car_info['car_id']]);
            }
            $uid = ($bind_info&&$bind_info['uid'])?$bind_info['uid']:'';
            $car_id = ($infos_car_info&&$infos_car_info['car_id'])?$infos_car_info['car_id']:'';
            $bind_id = ($bind_info&&$bind_info['user_id'])?$bind_info['user_id']:'';
            $user_name = ($infos_car_info&&$infos_car_info['car_user_name'])?$infos_car_info['car_user_name']:'';
            $user_phone = ($infos_car_info&&$infos_car_info['car_user_phone'])?$infos_car_info['car_user_phone']:'';
        }

        // 记录车辆出场信息
        $car_access_record = [];
        $car_access_record['business_type'] = 0;
        $car_access_record['business_id'] = $in_park['park_id'];
        $car_access_record['car_number'] = $car_number;
        $car_access_record['accessType'] = $accessType;
        $car_access_record['accessImage'] = $outParkData['outImageUrl'];
        $car_access_record['accessTime'] = $outParkData['outtime']?$outParkData['outtime']:time();
        $car_access_record['accessMode'] = $c_type ? $c_type:'3';
        $car_access_record['park_sys_type'] = 'D7';
        $car_access_record['is_out'] = 1;
        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number']=empty($passage_info['channel_number'])?'未知':$passage_info['channel_number'];
        $car_access_record['channel_name']=empty($passage_info['passage_name'])?'未命名':$passage_info['passage_name'];
        $car_access_record['park_id'] = $in_park['park_id'];
        $car_access_record['park_name'] = $park_name;
        $car_access_record['empty_plot'] = $outParkData['emptyPlot']?$outParkData['emptyPlot']:'';
        $car_access_record['uid'] = $uid;
        $car_access_record['car_id'] = $car_id;
        $car_access_record['bind_id'] = $bind_id;
        $car_access_record['user_name'] = $user_name;
        $car_access_record['user_phone'] = $user_phone;
        $car_access_record['order_id'] = $order_id;
        $car_access_record['total'] = $house_village_parking_temp['price'] ? $house_village_parking_temp['price'] : 0;
        // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
        // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
        //交易流水号(pay_type为wallet、scancode、sweepcode必传)
        $car_access_record['update_time'] = time();
        $car_access_record['trade_no'] = $nowtime . rand(10, 99) . sprintf("%08d", $in_park['park_id']);
        if ($insert_id) {
            $car_access_record['from_id'] = $insert_id;
        }
        $car_access_record['park_time'] = $outParkData['parkingTime']*60;
        $record_id=$db_house_village_car_access_record->addOne($car_access_record);
        if (!$record_id) {
            fdump_api(['添加车辆入场记录失败'.__LINE__,$car_number,$in_park,$car_access_record],'park/'.$in_park['park_id'].'/in_park_log',1);
        }
        if($insert_id || $record_id){
            if ($car_access_record['trade_no']) {
                $data['trade_no'] = $car_access_record['trade_no'];
            } elseif ($outParkData['trade_no']) {
                $data['trade_no'] = $outParkData['trade_no'];
            } else {
                $data['trade_no'] = $nowtime . rand(10, 99) . sprintf("%08d", $in_park['park_id']);//交易流水号(pay_type为wallet、scancode、sweepcode必传)
            }
            if (!empty($inRecordPark)){
                $db_house_village_car_access_record->saveOne(['record_id'=>$inRecordPark['record_id']],['is_out'=>1]);
            }
            if($in_park['car_number']){
                $house_village_parking_temp=$db_house_village_parking_temp->getOne(['car_number'=>$in_park['car_number'],'is_out_park'=>0]);
                if($house_village_parking_temp){
                    $db_house_village_parking_temp->saveOne(['car_number'=>$in_park['car_number'],'is_out_park'=>0],['is_out_park'=>1]);
                }
            }
            if ($outParks) {
                $db_out_park->saveOne(['id'=>$insert_id],['trade_no'=>$data['trade_no']]);
                $inPark_data=[];
                $inPark_data['is_out']=1;
                $inPark_data['is_paid']=1;
                $inPark_data['out_time']=$outParkData['outtime'];
                $inPark_data['park_name']=$park_name;
                $inPark_data['park_time']=$outParkData['parkingTime']*60;
                $inPark_data['out_channel_id'] = $passage_info['id'];
                $inPark_data['park_sys_type'] = 'D7';
                $db_in_park->saveOne(['id'=>$in_park['id']],$inPark_data);
            }

            $db_nolicence_in_park=new NolicenceInPark();
            // 如果是无牌车 记录出场
            if ($house_village_parking_temp['is_pay_scene']==2) {
                $db_nolicence_in_park->saveOne(['car_number' => $in_park['car_number']],['is_out_park'=>1]);
            } elseif ($db_nolicence_in_park->getOne(['car_number' => $in_park['car_number']])) {
                $db_nolicence_in_park->saveOne(['car_number' => $in_park['car_number']],['is_out_park'=>1]);
            }
        }
        $data1=[];
        $data1['state'] = 1;
        $data1['errmsg'] = '结算成功';
        $data1['service_name'] = 'outpark';
        $data1['order_id'] = $outParkData['orderId'];
        return $data1;
    }
    /*------------  上行接口end  --------*/



}