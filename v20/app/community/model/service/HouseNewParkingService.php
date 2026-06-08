<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/3/8 17:32
 */
namespace app\community\model\service;

use app\common\model\db\Tempmsg;
use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UploadFileService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\BrandCars;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseMeterAdminElectric;
use app\community\model\db\HouseMeterElectricGroup;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HousePropertyDigit;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageDetailRecord;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLabel;
use app\community\model\db\HouseVillageLabelCat;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageMonthParkNoticeRecord;
use app\community\model\db\HouseVillagePark;
use app\community\model\db\HouseVillageParkBlack;
use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkChargeCartype;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkGarageConfig;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\HouseVillageParkFree;
use app\community\model\db\HouseVillageCarFlowCoupon;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseVillageConfig;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePrintTemplateNumber;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\InPark;
use app\community\model\db\InParkElectriccar;
use app\community\model\db\NolicenceInPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkCoupons;
use app\community\model\db\ParkCouponsShop;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPassageType;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkShopCoupons;
use app\community\model\db\ParkShowscreenLog;
use app\community\model\db\ParkSystem;
use app\community\model\db\ParkWhiteRecord;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\User;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\Car\HouseVillageCarFlowService;
use app\community\model\service\HouseVillageParkGarageConfigService;
use app\community\model\db\HouseVillageParkingCarBindPosition;
use app\community\model\service\Park\A11Service;
use app\community\model\service\Park\D5Service;
use app\community\model\service\Park\D6Service;
use app\community\model\service\Park\QinLinCloudService;
use app\foodshop\model\service\message\SmsSendService;
use app\community\model\service\HouseNewAiParkingService;
use app\merchant\model\db\MerchantStore;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
use app\traits\ParkPassageHouseTraits;
use error_msg\GetErrorMsg;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;
use think\facade\Db;
use think\facade\Cache;
use customization\customization;
require_once dirname(dirname(dirname(dirname(__DIR__)))).'/extend/phpqrcode/phpqrcode.php';

class HouseNewParkingService
{
    use CommonLogTraits;
    use AdminLoginTraits;
    use ParkPassageHouseTraits;
    use customization;
    public $park_sys_type = [
        0 => array(
            'park_sys_type' => 'A1',
            'name' => 'A1智慧停车',
            'park_sys_type_box' => 0
        ),
        1 => array(
            'park_sys_type' => 'D1',
            'name' => 'D1智慧停车',
            'park_sys_type_box' => 1
        ),
        3 => array(
            'park_sys_type' => 'D3',
            'name' => 'D3智慧停车',
            'park_sys_type_box' => 0
        ),
        4 => array(
            'park_sys_type' => 'D5',
            'name' => 'D5智慧停车',
            'park_sys_type_box' => 0
        ),
	5 => array(
            'park_sys_type' => 'D7',
            'name' => 'D7智慧停车',
            'park_sys_type_box' => 0
        ),
        6 => array(
            'park_sys_type' => 'A11',
            'name' => 'A11智慧停车',
            'park_sys_type_box' => 0
        ),
    ];

    public $one_park_sys_type=[
        'A1'=>'A1智慧停车',
        'D1'=>'D1智慧停车',
        'D3'=>'D3智慧停车',
        'D5'=>'D5智慧停车',
        'D7'=>'D7智慧停车',
        'A11'=>'A11智慧停车',
        'HXT85'=>'华夏T85智慧停车',
    ];
    public function get_park_sys_type() {
        $park_sys_type = [];
        $park_sys_type[] = [
            'park_sys_type'     => 'A1',
            'name'              => 'A1智慧停车',
            'park_sys_type_box' => 0
        ];
        $configCustomizationService = new ConfigCustomizationService();
        if ($configCustomizationService->getD1ParkJudge()) {
            $park_sys_type[] = [
                'park_sys_type'     => 'D1',
                'name'              => 'D1智慧停车',
                'park_sys_type_box' => 1
            ];
        }
        if ($configCustomizationService->getD3ParkJudge()) {
            $park_sys_type[] = [
                'park_sys_type' => 'D3',
                'name' => 'D3智慧停车',
                'park_sys_type_box' => 0
            ];
        }
        if ($configCustomizationService->getAnakeParkJudge()) {
            $park_sys_type[] = [
                'park_sys_type'     => 'D5',
                'name'              => 'D5智慧停车',
                'park_sys_type_box' => 0
            ];
        }
        return $park_sys_type;
    }
    
    
    public $relationship = ['1' => '业主', '2' => '父母', '3' => '配偶', '4' => '子女', '5' => '亲戚', '6' => '朋友', '7' => '其他'];
    public $label_function = [
        ['key'=>'power', 'value'=>'权限管理'],
        ['key'=>'outPark','value'=> '不在场车辆记录'],
        ['key'=>'passage','value'=> '车道管理'],
        ['key'=>'userLabel','value'=> '用户标签'],
    ];
    public $park_type = array('1' => '军车', '2' => '警车', '3' => '消防车');
    public $last_heart_time = 300;
    public $car_color = array(
        array(
            'id' => 'A',
            'lable' => '白色'
        ),
        array(
            'id' => 'B',
            'lable' => '灰色'
        ),
        array(
            'id' => 'C',
            'lable' => '黄色'
        ),
        array(
            'id' => 'D',
            'lable' => '粉色'
        ),
        array(
            'id' => 'E',
            'lable' => '红色'
        ),
        array(
            'id' => 'F',
            'lable' => '紫色'
        ),
        array(
            'id' => 'G',
            'lable' => '绿色'
        ),
        array(
            'id' => 'H',
            'lable' => '蓝色'
        ),
        array(
            'id' => 'I',
            'lable' => '棕色'
        ),
        array(
            'id' => 'J',
            'lable' => '黑色'
        ),
        array(
            'id' => 'Z',
            'lable' => '其他'
        ),

    );
    /**
     * 月租车A、B、C、D
     * 临时车A、B、C、D、E、F、G、H
     * 储值车A、B、C、D
     * 免费车A、B
     * 不可改动，只可以后续添加
     * @var array
     */
    public $parking_car_type_arr  = [
        1 => '月租车A',
        2 => '月租车B',
        3 => '月租车C',
        4 => '月租车D',
        5 => '临时车A',
        6 => '临时车B',
        7 => '临时车C',
        8 => '临时车D',
        9 => '临时车E',
        10 => '临时车F',
        11 => '临时车G',
        12 => '临时车H',
        13 => '储值车A',
        14 => '储值车B',
        15 => '储值车C',
        16 => '储值车D',
        17 => '免费车A',
        18 => '免费车B',
    ];
    public $parking_car_type_value  = [
        '月租车A'=>1,
        '月租车B'=>2,
        '月租车C'=>3,
        '月租车D'=>4,
        '临时车A'=>5,
        '临时车B'=>6,
        '临时车C'=>7,
        '临时车D'=>8,
        '临时车E'=>9,
        '临时车F'=>10,
        '临时车G'=>11,
        '临时车H'=>12,
        '储值车A'=>13,
        '储值车B'=>14,
        '储值车C'=>15,
        '储值车D'=>16,
        '免费车A'=>17,
        '免费车B'=>18,
    ];
    public $parking_d1_car_type_arr  = [
        1 => '月租车A',
        2 => '月租车B',
        3 => '月租车C',
        4 => '月租车D',
    ];
    public $parking_d3_car_type_arr  = [
        1 => '月租车',
        2 => '临时车',
    ];

    public $third_ai_park_sys_type=array(
        '3652' => '月租车A', '3653' => '月租车B', '3654' => '月租车C', '3655' => '月租车D', '3661' => '月租车E',
        '3662' => '月租车F', '3663' => '月租车G', '3664' => '月租车H','3651' => '临时车A', '3650' => '临时车B', '3649' => '临时车C', '3648' => '临时车D'
    );
    //A11停车卡类
    const  Month_A = 1;
    const  Month_B = 2;
    const  Month_C = 3;
    const  Month_D = 4;
    const  Month_E = 5;
    const  Month_F = 6;
    const  Month_G = 7;
    const  Month_H = 8;
    const  Temp_A = 9;
    const  Temp_B = 10;
    const  Temp_C = 11;
    const  Temp_D = 12;
    const  Temp_E = 13;
    const  Temp_F = 14;
    const  Temp_G = 15;
    const  Temp_H = 16;
    const  Stored_A = 17;
    const  Stored_B = 18;
    const  Stored_C = 19;
    const  Stored_D = 20;
    const  Free_A = 21;
    
    public $parking_a11_car_type_arr  = [
        self::Month_A => '月租车A',
        self::Month_B => '月租车B',
        self::Month_C => '月租车C',
        self::Month_D => '月租车D',
        self::Month_E => '月租车E',
        self::Month_F => '月租车F',
        self::Month_G => '月租车G',
        self::Month_H => '月租车H',
        self::Temp_A => '临时车A',
        self::Temp_B => '临时车B',
        self::Temp_C => '临时车C',
        self::Temp_D => '临时车D',
        self::Temp_E => '临时车E',
        self::Temp_F => '临时车F',
        self::Temp_G => '临时车G',
        self::Temp_H => '临时车H',
        self::Stored_A => '储值车A',
        self::Stored_B => '储值车B',
        self::Stored_C => '储值车C',
        self::Stored_D => '储值车D',
        self::Free_A => '免费车A',
    ];
    //A11月租车卡类
    public $A11_Month=[self::Month_A,self::Month_B,self::Month_C,self::Month_D,self::Month_E,self::Month_F,self::Month_G,self::Month_H];
    //A11临时车卡类
    public $A11_Temp=[self::Temp_A,self::Temp_B,self::Temp_C,self::Temp_D,self::Temp_E,self::Temp_F,self::Temp_G,self::Temp_H];
    //A11储值车卡类
    public $A11_Stored=[self::Stored_A,self::Stored_B,self::Stored_C,self::Stored_D];

    //A11车牌类型
    public $A11_type=[
        0=>'未知车牌',
        1=>'蓝牌小汽车',
        2=>'黑牌小汽车',
        3=>'单排黄牌',
        4=>'双排黄牌', 
        5=>'警车车牌',
        6=>'武警车牌',
        7=>'个性化车牌',
        8=>'单排军车牌',
        9=>'双排军车牌',
        10=>'使馆车牌',
        11=>'香港进出中国大陆车牌',
        12=>'农用车牌',
        13=>'教练车牌',
        14=>'澳门进出中国大陆车牌',
        15=>'双层武警车牌',
        16=>'武警总队车牌',
        17=>'双层武警总队车牌',
        18=>'民航车牌',
        19=>'新能源车牌'];
    

    /**
     * 月租车A、B、C、D
     * 临时车A、B、C、D、E、F、G、H
     * 储值车A、B、C、D
     * 免费车A、B
     * 不可改动，只可以后续添加
     * @var array
     */
    public $parking_d7_car_type_arr  = [
        1 => '月租车A',
        2 => '月租车B',
        3 => '月租车C',
        4 => '月租车D',
        5 => '月租车E',
        6 => '月租车F',
        7 => '月租车G',
        8 => '月租车H',
        9 => '临时车A',
        10 => '临时车B',
        11 => '临时车C',
        12 => '临时车D',
        13 => '临时车E',
        14 => '临时车F',
        15 => '临时车G',
        16 => '临时车H',
        17 => '储值车A',
        18 => '储值车B',
        19 => '储值车C',
        20 => '储值车D',
        //21 => '免费车A',
      //  22 => '免费车B',
    ];
    public $parking_D7_car_type_value  = [
        1 =>['title'=> '月租车A','value'=>'32'],
        2 => ['title'=> '月租车B','value'=>'3B'],
        3 => ['title'=> '月租车C','value'=>'3C'],
        4 => ['title'=> '月租车D','value'=>'3D'],
        5 => ['title'=> '月租车E','value'=>'51'],
        6 => ['title'=> '月租车F','value'=>'52'],
        7 => ['title'=> '月租车G','value'=>'53'],
        8 => ['title'=> '月租车H','value'=>'54'],
        9 =>  ['title'=> '临时车A','value'=>'36'],
        10 => ['title'=> '临时车B','value'=>'37'],
        11 => ['title'=> '临时车C','value'=>'38'],
        12 => ['title'=> '临时车D','value'=>'39'],
        13 => ['title'=> '临时车E','value'=>'43'],
        14 => ['title'=> '临时车F','value'=>'44'],
        15 => ['title'=> '临时车G','value'=>'45'],
        16 => ['title'=> '临时车H','value'=>'46'],
        17 => ['title'=> '储值车A','value'=>'33'],
        18 => ['title'=> '储值车B','value'=>'34'],
        19 => ['title'=> '储值车C','value'=>'35'],
        20 =>['title'=> '储值车D','value'=>'3A'],
       // 21 => ['title'=> '免费车A','value'=>'41'],
       // 22 => ['title'=> '免费车B','value'=>'3E'],
    ];

    public function __construct()
    {

    }

    public function getParkConfig(){
        $configCustomizationService=new ConfigCustomizationService();
        $isHxt85=$configCustomizationService->getFuncParkDevHxt85Customized();
        if($isHxt85){
            $this->park_sys_type[]=[
                'park_sys_type'     => 'HXT85',
                'name'              => '华夏T85智慧停车',
                'park_sys_type_box' => 0
            ];
        }
        return $this->park_sys_type;
    }

    public function getLabelFunction(){
        return $this->label_function;
    }

    /**
     * 新版添加车库
     * @author:zhubaodi
     * @date_time: 2022/3/8 17:34
     */
    public function addParkingGarage($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        if ($data['position_count']<$data['position_month_count']){
            throw new \think\Exception("车位总数不能小于月租车位总数");
        }
        $garage_data=[];
        $garage_data['fid']=$data['fid'];
        $garage_data['garage_num']=$data['garage_num'];
        $garage_data['garage_position']=$data['garage_position'];
        $garage_data['garage_remark']=$data['garage_remark'];
        $garage_data['village_id']=$data['village_id'];
        $garage_data['position_count']=$data['position_count'];
        $garage_data['position_month_count']=$data['position_month_count'];
        $garage_data['position_temp_count']=$data['position_count']-$data['position_month_count'];
        $garage_data['is_month_charge']=$data['is_month_charge'];
        $garage_data['is_month_access']=$data['is_month_access'];
        $garage_data['garage_addtime']=time();
        $insert_id=$db_house_village_parking_garage->addOne($garage_data);
        if ($insert_id>0){
            if ($data['add_position_type']==1){
                $position_num=explode('-',$data['rule_last'][0]);
                if ($position_num[1]>$data['position_count']){
                    $length=intval($data['position_count']);
                    for ($i=$position_num[0];$i<=$position_num[1];$i++){
                        if($length<=0){
                            break;
                        }
                        $position_data=[];
                        $position_data['garage_id']=$insert_id;
                        $position_data['position_num']=$data['rule_first'][0].$i;
                        $position_data['village_id']=$data['village_id'];
                        $db_house_village_parking_position->addOne($position_data);
                        $length--;
                    }
                }else{
                    $position_num1=$data['position_count'];
                    $length=intval($data['position_count']);
                    foreach ($data['rule_first'] as $k=>$vv){
                        $position_num=explode('-',$data['rule_last'][$k]);
                        for ($i=$position_num[0];$i<=$position_num[1];$i++){
                            if($length<=0){
                                break;
                            }
                            $position_data=[];
                            $position_data['garage_id']=$insert_id;
                            $position_data['position_num']=$vv.$i;
                            $position_data['village_id']=$data['village_id'];
                            $db_house_village_parking_position->addOne($position_data);
                            $length--;
                        }
                        if($length<=0){
                            break;
                        }
                    }
                }
            }
            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '新版停车-车库管理',
                    'table'  => 'house_village_parking_garage',
                    'client' => '小区后台',
                    'trigger_path' => '车库管理->添加',
                    'trigger_type' => $this->getAddLogName(),
                    'addtime'      => time(),
                    'village_id'   => $data['village_id'],
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $garage_data,
                'oldData' => []
            ];
            $this->laterLogInQueue($queuData);
        }

        return $insert_id;
    }


    /**
     * 新版编辑车库
     * @author:zhubaodi
     * @date_time: 2022/3/8 17:34
     */
    public function editParkingGarage($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        if ($data['position_count']<$data['position_month_count']){
            throw new \think\Exception("车位总数不能小于月租车位总数");
        }
        $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$data['garage_id']]);
        if (!empty($garage_info)){
            /*
            if ($garage_info['position_count']>$data['position_count']){
                throw new \think\Exception("车位总数不能小于当前车位总数");
            }
            */
        }
        $garage_data=[];
        $garage_data['fid']=$data['fid'];
        $garage_data['garage_num']=$data['garage_num'];
        $garage_data['garage_position']=$data['garage_position'];
        $garage_data['garage_remark']=$data['garage_remark'];
        $garage_data['village_id']=$data['village_id'];
        $garage_data['position_count']=$data['position_count'];
        $garage_data['position_month_count']=$data['position_month_count'];
        $garage_data['position_temp_count']=$data['position_count']-$data['position_month_count'];
        $garage_data['is_month_charge']=$data['is_month_charge'];
        $garage_data['is_month_access']=$data['is_month_access'];
        if(isset($data['lat'])){
            $garage_data['lat']=$data['lat'];
        }
        if(isset($data['lng'])){
            $garage_data['lng']=$data['lng'];
        }
        $garage_data['garage_addtime']=time();
        $insert_id=$db_house_village_parking_garage->saveOne(['garage_id'=>$data['garage_id']],$garage_data);
        (new CarHandleGetInfoService())->clearCarParkingGarageInfo($garage_data['village_id']);
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '新版停车-车库管理',
                'table'  => 'house_village_parking_garage',
                'client' => '小区后台',
                'trigger_path' => '车库管理->编辑',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $garage_data,
            'oldData' => ($garage_info && !$garage_info->isEmpty()) ? $garage_info->toArray() : []
        ];
        $this->laterLogInQueue($queuData);
        return $insert_id;
    }


    /**
     * 新版编辑车库
     * @author:zhubaodi
     * @date_time: 2022/3/8 17:34
     */
    public function delParkingGarage($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_park_passage=new ParkPassage();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $whereArr=array('garage_id'=>$data['garage_id']);
        $park_passage=$db_park_passage->getFind($whereArr);
        if($park_passage && !$park_passage->isEmpty()){
            throw new \think\Exception("请先删除该车库下的所有车道后，才可以删除此车库！");
        }
        $whereArr=array('area_type'=>2,'passage_area'=>$data['garage_id']);
        $park_passage=$db_park_passage->getFind($whereArr);
        if($park_passage && !$park_passage->isEmpty()){
            throw new \think\Exception("请先删除该车库下的所有车道后，才可以删除此车库！");
        }
        $position_info=$db_house_village_parking_position->getFind(['garage_id'=>$data['garage_id'],'position_pattern'=>1]);
        if (!empty($position_info['position_id'])){
            throw new \think\Exception("请先删除该车库对应的车位后，再删除车库");
        }
        $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$data['garage_id'], 'status' => 1]);
        if (isset($garage_info['fid']) && (!$garage_info['fid'] || $garage_info['fid'] <= 0)) {
            $garageChild = $db_house_village_parking_garage->getCount(['fid'=>$data['garage_id'], 'status' => 1]);
            if ($garageChild) {
                throw new \think\Exception("请先删除当前车库子级车库");
            }
        }
        
        $rr=['status'=>0];
        $whereDel=array('garage_id'=>$data['garage_id'],'village_id'=>$data['village_id']);
        $insert_id=$db_house_village_parking_garage->saveOne($whereDel,$rr);
        $houseVillageParkGarageConfigService->delParkGarageConfig($whereDel);
        (new CarHandleGetInfoService())->clearCarParkingGarageInfo($garage_info['village_id']);
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '新版停车-车库管理',
                'table'  => 'house_village_parking_garage',
                'client' => '小区后台',
                'trigger_path' => '车库管理->删除',
                'trigger_type' => $this->getDeleteName(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $rr,
            'oldData' => ($garage_info && !$garage_info->isEmpty()) ? $garage_info->toArray() : []
        ];
        $this->laterLogInQueue($queuData);
        return $insert_id;
    }

    public function addParkConfig($data,$garage_id=0){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $park_data=array();
        $park_data['park_versions']=$data['park_versions'];
        $park_data['park_show']=$data['park_show'];
        $park_data['is_park_month_type']=$data['is_park_month_type'];
        $park_data['is_temporary_park_type']=$data['is_temporary_park_type'];
        $park_data['visitor_money']=$data['visitor_money'];
        $park_data['park_sys_type']=$data['park_sys_type'];
        $park_data['village_id']=$data['village_id'];
        $park_data['free_park_time']=$data['free_park_time']>0 ? intval($data['free_park_time']):15;
        $park_data['park_position_type']=$data['park_position_type'];
        $park_data['in_park_type']=$data['in_park_type'];
        $park_data['temp_in_park_type']=$data['temp_in_park_type'];
        $park_data['free_open_gate']=$data['free_open_gate'];
        $park_data['not_inPark_money']=$data['not_inPark_money'];
        $park_data['park_month_day']=$data['park_month_day'];
        $park_data['children_position_type']=$data['children_position_type'];


        if ($data['park_sys_type'] == "D3" || $data['park_sys_type'] == "A11") {
            $park_data['register_day'] = $data['register_day'];//备注
            $park_data['register_type'] = $data['register_type'];//备注
            $park_data['out_park_time'] =(int)$data['out_park_time'];
            $park_data['temp_in_park_type'] =$data['temp_in_park_type'];
            $park_data['expire_month_car_type'] = $data['expire_month_car_type'];//月租车过期处理方式
            $park_data['expire_month_car_day'] = $data['expire_month_car_day'];//过期多少天后禁止入场
            
        }elseif ($data['park_sys_type'] == 'D5'){
            $park_data['d5_url'] = $data['d5_url'];
            $park_data['d5_name'] = $data['d5_name'];
            $park_data['d5_pass'] = $data['d5_pass'];
        }
        elseif ($data['park_sys_type'] == 'D7'){
            $park_data['d7_park_id'] = $data['d7_park_id'];
            $d7_park_info=(new QinLinCloudService())->getParkInfo($park_data['d7_park_id']);
            if (empty($d7_park_info)||$d7_park_info['state']!=200){
                if(isset($d7_park_info['errMsg']) && $d7_park_info['errMsg']){
                    throw new \think\Exception('亲邻停车场反馈：'.$d7_park_info['errMsg'].'，请找第三方车场核对检查。');
                }
                throw new \think\Exception("停车场信息不存在,请重新输入");
            }
            if($garage_id>0){
                $config_d7=$db_house_village_park_garage_config->getOneData(['garage_id'=>$garage_id]);
            }else{
                $config_d7=$db_house_village_park_config->getFind(['d7_park_id'=>$data['d7_park_id']]);
            }
            if (!empty($config_d7) && isset($config_d7['village_id']) &&$config_d7['village_id']!=$data['village_id']){
                throw new \think\Exception("停车场编号已存在,请重新输入");
            }
        }
        else {
            $park_data['comid'] =$data['comid'];
        }
        $park_data['union_id'] = $data['union_id'];
        $park_data['ckey'] = $data['ckey'];
        if(isset($data['car_type_month_to_temp'])){
            $data['car_type_month_to_temp']=is_array($data['car_type_month_to_temp']) ? json_encode($data['car_type_month_to_temp'],JSON_UNESCAPED_UNICODE):$data['car_type_month_to_temp'];
            $park_data['car_type_month_to_temp'] = $data['car_type_month_to_temp'];
        }
        $id=0;
        $oldData=array();
        if($garage_id>0){
            $whereArr=array('garage_id'=>$garage_id,'village_id'=>$data['village_id']);
            $park_data['garage_id']=$garage_id;
            $garage_config=$db_house_village_park_garage_config->getOneData($whereArr);
            if($garage_config && !$garage_config->isEmpty()){
                $oldData=$garage_config->toArray();
                $id=$db_house_village_park_garage_config->saveOneData($whereArr,$park_data);
            }else{
                $id=$db_house_village_park_garage_config->addOneData($park_data);
            }
        }else{
            $config=$db_house_village_park_config->getFind(['village_id'=>$data['village_id']]);
            if (!empty($config) && !$config->isEmpty()){
                $oldData=$config->toArray();
                $id=$db_house_village_park_config->save_one(['id'=>$config['id']],$park_data);
            }else{
                $id=$db_house_village_park_config->addOne($park_data);
            }
        }
        if ($id && $park_data['children_position_type']==0){
            $where_parent=[
                ['village_id','=',$data['village_id']],
                ['parent_position_id','>',0],
            ];
            $parent_position_count=$db_house_village_parking_position->getCounts($where_parent);
            if ($parent_position_count>0){
                $db_house_village_parking_position->saveOne($where_parent,['parent_position_id'=>0]);
            }
            $where_parent=[
                ['village_id','=',$data['village_id']],
                ['children_type','=',2],
            ];
            $parent_position_count=$db_house_village_parking_position->getCounts($where_parent);
            if ($parent_position_count>0){
                $db_house_village_parking_position->saveOne($where_parent,['children_type'=>1]);
            }
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '新版停车-车库管理',
                'table'  => 'house_village_park_config',
                'client' => '小区后台',
                'trigger_path' => '车库管理->车库功能设置',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $park_data,
            'oldData' => $oldData
        ];
        $this->laterLogInQueue($queuData);
        return $id;
    }


    /**
     * 查询车库列表
     * @author:zhubaodi
     * @date_time: 2022/3/9 15:05
     */
    public function getParkGarageList($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_new_charge_standard_bind=new HouseNewChargeStandardBind();
        $db_HouseNewChargeProject = new HouseNewChargeProject();
        $db_rule = new HouseNewChargeRule();
        $db_park_passage=new ParkPassage();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['status','=',1];
        if (!empty($data['garage_num'])){
            $where[]=['garage_num','=',$data['garage_num']];
        }
        $where_p = [];
        $where_p['c.charge_type'] = 'park_new';
        $where_p['p.village_id'] = $data['village_id'];
        $where_p['p.status'] = 1;
        $project_ids = $db_HouseNewChargeProject->getProjectColumn($where_p, 'p.id');
        $rule_id = $db_rule->getColumn(['village_id'=>$data['village_id'],'fees_type'=>4,'status'=>[1,2]],'id');
        if (!empty($data['garage_status'])&&!empty($project_ids)){
            $garage_id=$db_house_new_charge_standard_bind->getColumn(['village_id'=>$data['village_id'],'is_del'=>1,'rule_id'=>$rule_id,'project_id'=>$project_ids],'garage_id');
            //todo 查询是否绑定收费标准的车场
            if ($data['garage_status']==1){
                $where[]= ['garage_id','not in',$garage_id];
           }
            if ($data['garage_status']==2){
                $where[]= ['garage_id','in',$garage_id];
            }
        }
        $list=$db_house_village_parking_garage->getLists($where,'*',$data['page'],$data['limit']);

        if (!empty($list) && !$list->isEmpty()){
            $list=$list->toArray();
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $configCustomizationService=new ConfigCustomizationService();
        $isAihorseCustomizedJudge=$configCustomizationService->getAihorseCustomizedJudge();
        if (!empty($list)){
            $carThirdTypeNoArr = array('3652' => '月租车A', '3653' => '月租车B', '3654' => '月租车C', '3655' => '月租车D', '3661' => '月租车E', '3662' => '月租车F', '3663' => '月租车G', '3664' => '月租车H');
            foreach ($list as $kk=>&$vv){
                $position_count=$db_house_village_parking_position->getCounts(['village_id'=>$data['village_id'],'garage_id'=>$vv['garage_id'],'position_pattern'=>2]);
                $vv['virtual_parking']=$position_count;
                if (!empty($project_ids)){
                    $garage_bind= $db_house_new_charge_standard_bind->getOne(['village_id'=>$data['village_id'],'is_del'=>1,'rule_id'=>$rule_id,'project_id'=>$project_ids,'garage_id'=>$vv['garage_id']]);
                }else{
                    $garage_bind=[];
                }
                if (!empty($garage_bind)){
                    $garage_bind=$garage_bind->toArray();
                }
                if (!empty($garage_bind)){
                    $vv['status']='已绑定';
                }else{
                    $vv['status']='未绑定';
                }
                $vv['park_sys_type']='A1';
                $parkGarageConfig=$houseVillageParkGarageConfigService->getVillageParkConfig($vv['garage_id'],$data['village_id']);
                if($parkGarageConfig && $parkGarageConfig['park_sys_type']){
                    $vv['park_sys_type']=$parkGarageConfig['park_sys_type'];
                }
                if($vv['park_sys_type']=='A11'){
                    $vv['status']='';
                }
                $vv['is_third_park_garage']=0;
                $vv['carThirdTypeChargRules']=array();
                if($isAihorseCustomizedJudge && isset($vv['third_park_key']) && $vv['third_park_key']){
                    $vv['park_sys_type']='智慧停车服务商系统';
                    $vv['is_third_park_garage']=1;
                    if(isset($vv['extra_data']) && ($vv['extra_data'])) {
                        $extra_data = json_decode($vv['extra_data'], true);
                        unset($list[$kk]['extra_data']);
                        if (isset($extra_data['carTypeChargRules']) && !empty($extra_data['carTypeChargRules'])) {
                            foreach ($extra_data['carTypeChargRules'] as $k2k => $v2v) {
                                $carTypeNoStr = $v2v['carTypeNo'];
                                $extra_data['carTypeChargRules'][$k2k]['carTypeStr'] = '';
                                if (isset($carThirdTypeNoArr[$v2v['carTypeNo']])) {
                                    $carTypeNoStr = $v2v['carTypeNo'] . '（' . $carThirdTypeNoArr[$v2v['carTypeNo']] . '）';
                                    $extra_data['carTypeChargRules'][$k2k]['carTypeStr'] = $carThirdTypeNoArr[$v2v['carTypeNo']];
                                }
                                $extra_data['carTypeChargRules'][$k2k]['carTypeNoStr'] = $carTypeNoStr;
                            }
                            $vv['carThirdTypeChargRules']=$extra_data['carTypeChargRules'];
                        }
                    }
                }
                if ($vv['garage_addtime']>1){
                    $vv['garage_addtime']=date('Y-m-d',$vv['garage_addtime']);
                }
                $where1=[];
                $where1['village_id']=$data['village_id'];
                $where1['garage_id']=$vv['garage_id'];
                $vv['passage_count']=$db_park_passage->getCount($where1);
            }
        }
        $count = $db_house_village_parking_garage->getCount($where);
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type'] = $this->getAllParkSysType($data['village_id'],true);
        $data1['has_third_aihorse_platform'] = 0;
        if($isAihorseCustomizedJudge){
             $app_id=cfg('park_aicar_app_id');
             $app_id=$app_id ? trim($app_id):'';
             $app_secret=cfg('park_aicar_app_secret');
             $app_secret=$app_secret ? trim($app_secret):'';
             if($app_id && $app_secret){
                 $data1['has_third_aihorse_platform'] =1;
             }
        }
        return  $data1;
    }
    public function getAllParkSysType($village_id=0,$is_str=false){
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $park_sys_type=array();
        $garageConfig=$houseVillageParkGarageConfigService->getListData($whereArr,'park_sys_type');
        if($garageConfig){
            foreach ($garageConfig as $gvv){
                $park_sys_type[]=$gvv['park_sys_type'];
            }
            $park_sys_type=array_unique($park_sys_type);
            $park_sys_type=array_values($park_sys_type);
        }
        if($is_str){
            if($park_sys_type){
                $park_sys_type_str= implode(',',$park_sys_type);
                return $park_sys_type_str;
            }else{
                return '';
            }
        }
        return $park_sys_type;
    }
    public function getParkGarageInfo($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $where=array();
        $where['village_id']=$data['village_id'];
        $where['garage_id']=$data['garage_id'];
        $info=$db_house_village_parking_garage->getOne($where);
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=array();
        }
        $configCustomizationService=new ConfigCustomizationService();
        $isAihorseCustomizedJudge=$configCustomizationService->getAihorseCustomizedJudge();
        if($isAihorseCustomizedJudge && isset($info['third_park_key']) && !empty($info['third_park_key'])){
            $app_id=cfg('park_aicar_app_id');
            $app_id=$app_id ? trim($app_id):'';
            $app_secret=cfg('park_aicar_app_secret');
            $app_secret=$app_secret ? trim($app_secret):'';
            if($app_id && $app_secret){
                try{
                    $houseNewAiParkingService=new \app\community\model\service\HouseNewAiParkingService();
                    $res=$houseNewAiParkingService->handleAihorsePlatformGarageDetail($data['village_id'],$info['third_park_key'],$data['garage_id']);
                    $where=array();
                    $where['village_id']=$data['village_id'];
                    $where['garage_id']=$data['garage_id'];
                    $info=$db_house_village_parking_garage->getOne($where);
                    if($info && !$info->isEmpty()){
                        $info=$info->toArray();
                    }else{
                        $info=array();
                    }
                }catch (\Exception $e){
                    
                }
            }
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $parkGarageConfig=$houseVillageParkGarageConfigService->getVillageParkConfig($data['garage_id'],$data['village_id']);
        $info['park_sys_type']='';
        if($parkGarageConfig && isset($parkGarageConfig['park_sys_type'])){
            $info['park_sys_type']=$parkGarageConfig['park_sys_type'];
        }
        if(isset($info['extra_data']) && ($info['extra_data'])){
            $info['extra_data']=json_decode($info['extra_data'],true);
            $carTypeNoArr=array('3652'=>'月租车A','3653'=>'月租车B','3654'=>'月租车C','3655'=>'月租车D','3661'=>'月租车E','3662'=>'月租车F','3663'=>'月租车G','3664'=>'月租车H');
            $info['park_sys_type']='third_ai_park';
            if(isset($info['extra_data']['carTypeChargRules']) && !empty($info['extra_data']['carTypeChargRules'])){
                foreach ($info['extra_data']['carTypeChargRules'] as $kk=>$vv){
                    $carTypeNoStr=$vv['carTypeNo'];
                    $info['extra_data']['carTypeChargRules'][$kk]['carTypeStr']='';
                    if(isset($carTypeNoArr[$vv['carTypeNo']])){
                        $carTypeNoStr=$vv['carTypeNo'].'（'.$carTypeNoArr[$vv['carTypeNo']].'）';
                        $info['extra_data']['carTypeChargRules'][$kk]['carTypeStr']=$carTypeNoArr[$vv['carTypeNo']];
                    }
                    $info['extra_data']['carTypeChargRules'][$kk]['carTypeNoStr']=$carTypeNoStr;
                }
            }else{
                $info['extra_data']['carTypeChargRules']=array();
            }
            
        }
        return $info;
    }
    /**
     * 获取车库配置，兼容没有车库id的
     ***/
    public function getVillageParkConfig($village_id=0,$garage_id=0){
        $parkConfigInfo=array();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        if($garage_id>0){
            $garageConfigWhere=array('garage_id'=>$garage_id,'village_id'=>$village_id);
            if($village_id>0){
                $garageConfigWhere['village_id']=$village_id;
            }
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($garageConfigWhere);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $parkConfigInfo=$garage_config_obj->toArray();
            }
        }else if($village_id>0){
            $parkWhere=array();
            $parkWhere['village_id']=$village_id;
            $info=$db_house_village_park_config->getFind($parkWhere);
            if($info && !$info->isEmpty()){
                $parkConfigInfo=$info->toArray();
            }
        }
        return  $parkConfigInfo;
    }
    
    public function getParkConfigInfo($data,$garage_id=0){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        $village_id=$data['village_id'];
        $garage_config=array();
        if($garage_id>0){
            $garageConfigWhere=array('garage_id'=>$garage_id,'village_id'=>$village_id);
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($garageConfigWhere);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $garage_config=$garage_config_obj->toArray();
            }
        }
        if(!empty($garage_config)){
            $info=$garage_config;
        }else{
            $where=array();
            $where['village_id']=$data['village_id'];
            $info=$db_house_village_park_config->getFind($where);
            if($info && !$info->isEmpty()){
                $info=$info->toArray();
            }else{
                $info=array();
            }
            if (empty($info)){
                if($garage_id>0){
                    $park_config=[];
                    $park_config['garage_id']=$garage_id;
                    $park_config['village_id']=$village_id;
                    $db_house_village_park_garage_config->addOneData($park_config);
                    $garageConfigWhere=array('garage_id'=>$garage_id,'village_id'=>$village_id);
                    $garage_config_obj=$db_house_village_park_garage_config->getOneData($garageConfigWhere);
                    if($garage_config_obj && !$garage_config_obj->isEmpty()){
                        $info=$garage_config_obj->toArray();
                    }
                }else{
                    $park_data=[];
                    $park_data['village_id']=$data['village_id'];
                    $db_house_village_park_config->addOne($park_data);
                    $info=$db_house_village_park_config->getFind($where);
                    if($info && !$info->isEmpty()){
                        $info=$info->toArray();
                    }
                }
            }
        }
        if($info['free_park_time']<=0){
            $info['free_park_time']=15;
        }
        $info['meter_reading_price']=cfg('meter_reading_price')?cfg('meter_reading_price'):0;//判断是否日照访问
        $info['parking_a11_car_type']=array();
        $car_type_month_to_temp=array();
        if(isset($info['car_type_month_to_temp']) && !empty($info['car_type_month_to_temp'])){
            $car_type_month_to_temp=json_decode($info['car_type_month_to_temp'],1);
            unset($info['car_type_month_to_temp']);
        }
        $tmp_car_type_month_to_temp=array();
        if($car_type_month_to_temp){
            foreach ($car_type_month_to_temp as $vv){
                $tmp_car_type_month_to_temp[$vv['month_car_type']]=$vv;
            }
        }
        if(isset($info['park_sys_type']) && $info['park_sys_type']=='A11'){
            foreach ($this->parking_a11_car_type_arr as $key=>$kvv){
                if(strpos($kvv,'月租车')!==false){
                    $tmpArr=array('parking_car_type'=>$key,'value'=>$kvv,'type'=>'month','temp_parking_car_type'=>0);
                    if($tmp_car_type_month_to_temp && isset($tmp_car_type_month_to_temp[$key])){
                        $tmpArr['temp_parking_car_type']=$tmp_car_type_month_to_temp[$key]['temp_car_type'];
                    }
                    $info['parking_a11_car_type'][]=$tmpArr;
                }else if(strpos($kvv,'临时车')!==false){
                    $tmpArr=array('parking_car_type'=>$key,'value'=>$kvv,'type'=>'temp');
                    $info['parking_a11_car_type'][]=$tmpArr;
                }
            }
        }
        
        return $info;
    }
    /**
     * 新版添加车位
     * @author:zhubaodi
     * @date_time: 2022/3/9 13:47
     */
    public function addParkPosition($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $where['village_id']=$data['village_id'];
        $where['garage_id']=$data['garage_id'];
        $garage_info=$db_house_village_parking_garage->getOne($where);
        if (empty($garage_info)){
            throw new \think\Exception("车库信息不存在");
        }
        $where1['village_id']=$data['village_id'];
        $where1['garage_id']=$data['garage_id'];
        $where1['position_pattern']=1;
        $position_count=$db_house_village_parking_position->getCounts($where1);
        if ($garage_info['position_count']<=$position_count){
            throw new \think\Exception("当前车库车位数量已达到设置的车位总数，无法添加车位");
        }
        $where1['position_num']=$data['position_num'];
        $position_info=$db_house_village_parking_position->getFind($where1);
        if (!empty($position_info)){
            throw new \think\Exception("车位号重复，无法添加车位");
        }
        $position_data=[];
        $position_data['village_id']=$data['village_id'];
        $position_data['garage_id']=$data['garage_id'];
        $position_data['position_num']=$data['position_num'];
        $position_data['position_area']=$data['position_area'];
        $position_data['position_note']=$data['position_note'];
        $position_data['position_pattern']=1;

        if (!empty($data['children_type'])){
            $position_data['children_type']=$data['children_type'];
        }
        if(intval($data['pigcms_id']) > 0){
            $position_data['position_status'] = 2;

        }
        $configCustomizationService=new ConfigCustomizationService();
        $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
        if($isWsxCustomizedOpen && isset($data['opt_type'])){
            $position_data['opt_type']=$data['opt_type'];
        }
        $id=$db_house_village_parking_position->addOne($position_data);
        if ($id>0&&$data['pigcms_id']>0){
            $data_bind['position_id'] = $id;
            $data_bind['user_id'] = $data['pigcms_id'];
            $data_bind['village_id'] = $data['village_id'];
            $db_house_village_bind_position=new HouseVillageBindPosition();
            $db_house_village_bind_position->addOne($data_bind);
        }
        return $id;
    }
    public function updateBindCarEndTime($position_id=0,$village_id=0,$end_time=0){
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereRaw='(car_position_id ='.$position_id .' OR FIND_IN_SET('.$position_id.',bind_position_ids))';
        $car_list_obj=$db_house_village_parking_car->getNewList($whereArr,'car_id,village_id,end_time,start_time,examine_status',0,20,'car_id DESC',$whereRaw);
        if($car_list_obj && !$car_list_obj->isEmpty()){
            $car_list=$car_list_obj->toArray();
            foreach ($car_list as $cvv){
                $db_house_village_parking_car->editHouseVillageParkingCar(array('car_id'=>$cvv['car_id'],'village_id'=>$cvv['village_id']),['end_time'=>$end_time]);
            }
        }
        return true;
    }

    public function handlePositionBindCar($village_id = 0, $garage_id = 0, $position_id = 0, $bind_cars = array())
    {
        $houseVillageParkingCarBindPositionDb = new HouseVillageParkingCarBindPosition();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $positionBindcarArr = array();
        $nowtime = time();
        if ($position_id < 1 || $village_id < 1) {
            return false;
        }
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('garage_id', '=', $garage_id);
        $whereArr[] = array('position_id', '=', $position_id);
        $whereArr[] = array('del_time', '=', '0');
        $carBindPosition = $houseVillageParkingCarBindPositionDb->getListData($whereArr);
        if ($carBindPosition && !$carBindPosition->isEmpty()) {
            $carBindPositionTmp = $carBindPosition->toArray();
            if ($carBindPositionTmp) {
                foreach ($carBindPositionTmp as $vv) {
                    $positionBindcarArr[$vv['car_id']] = $vv;
                    if (empty($bind_cars) || !is_array($bind_cars) || !in_array($vv['car_id'], $bind_cars)) {
                        //被删掉的 车辆
                        $carWhere = array('car_id' => $vv['car_id'], 'village_id' => $village_id);
                        $oneCarObj = $db_house_village_parking_car->getOne($carWhere, 'car_id,village_id,car_position_id,garage_id,bind_position_ids');
                        if ($oneCarObj && !$oneCarObj->isEmpty()) {
                            $saveArr = array();
                            $oneCar = $oneCarObj->toArray();
                            if ($oneCar['car_position_id'] == $position_id) {
                                $saveArr['car_position_id'] = 0;
                            }
                            if (!empty($oneCar['bind_position_ids'])) {
                                $bind_position_ids = explode(',', $oneCar['bind_position_ids']);
                                $new_position_ids = array();
                                foreach ($bind_position_ids as $pkk => $pvv) {
                                    if ($pvv != $position_id) {
                                        $new_position_ids[] = $pvv;
                                    }
                                }
                                $saveArr['bind_position_ids'] = '';
                                if (!empty($new_position_ids)) {
                                    $saveArr['car_position_id']=$new_position_ids['0'];
                                    $saveArr['bind_position_ids'] = implode(',', $new_position_ids);
                                    $wherePositionArr = array(['village_id', '=', $village_id], ['position_id', 'in', $new_position_ids]);
                                    $positionsObj = $db_house_village_parking_position->getList($wherePositionArr, 'garage_id', 0, 100);
                                    if ($positionsObj && !$positionsObj->isEmpty()) {
                                        $positionInfos = $positionsObj->toArray();
                                        $garage_id_tmp = array();
                                        foreach ($positionInfos as $pvv) {
                                            $garage_id_tmp[] = $pvv['garage_id'];
                                        }
                                        $garage_id_tmp = array_unique($garage_id_tmp);
                                        if ($garage_id_tmp) {
                                            $saveArr['garage_id'] = implode(',', $garage_id_tmp);;
                                        } else {
                                            $saveArr['garage_id'] = 0;
                                        }
                                    } else {
                                        $saveArr['garage_id'] = 0;
                                    }
                                } else {
                                    $saveArr['bind_position_ids']='';
                                    $saveArr['garage_id'] = 0;
                                }

                            }
                            if ($saveArr) {
                                $carWhere = array('car_id' => $vv['car_id'], 'village_id' => $village_id);
                                $db_house_village_parking_car->editCar($carWhere, $saveArr);
                            }
                        }
                    }
                }
            }
            $houseVillageParkingCarBindPositionDb->updateAllData($whereArr, ['del_time' => $nowtime]);
        }
        if (!empty($bind_cars)) {
            foreach ($bind_cars as $c_id) {
                if ($positionBindcarArr && isset($positionBindcarArr[$c_id]) && !empty($positionBindcarArr[$c_id])) {
                    $whereArr = array();
                    $whereArr[] = array('id', '=', $positionBindcarArr[$c_id]['id']);
                    $whereArr[] = array('position_id', '=', $position_id);
                    $whereArr[] = array('village_id', '=', $village_id);
                    $houseVillageParkingCarBindPositionDb->saveData($whereArr, ['del_time' => 0]);
                } else {
                    $oneData = array('village_id' => $village_id, 'position_id' => $position_id, 'garage_id' => $garage_id);
                    $oneData['car_id'] = $c_id;
                    $oneData['bind_position_time'] = $nowtime;
                    $oneData['del_time'] = 0;
                    $houseVillageParkingCarBindPositionDb->addOneData($oneData);
                }
                $carWhere = array('car_id' => $c_id, 'village_id' => $village_id);
                $oneCarObj = $db_house_village_parking_car->getOne($carWhere, 'car_id,village_id,car_position_id,garage_id,bind_position_ids');
                if ($oneCarObj && !$oneCarObj->isEmpty()) {
                    $oneCar = $oneCarObj->toArray();
                    $saveArr = array();
                    if ($oneCar['car_position_id'] < 1) {
                        $saveArr['car_position_id'] = $position_id;
                    }
                    if (empty($oneCar['garage_id']) || $oneCar['garage_id'] == '0') {
                        $saveArr['garage_id'] = $garage_id;
                    } else if (!empty($oneCar['garage_id'])) {
                        $garage_id_arr = explode(',', $oneCar['garage_id']);
                        if (!in_array($garage_id, $garage_id_arr)) {
                            $garage_id_arr[] = $garage_id;
                        }
                        $saveArr['garage_id'] = implode(',', $garage_id_arr);
                    }
                    if (empty($oneCar['bind_position_ids'])) {
                        $saveArr['bind_position_ids'] = $position_id;
                    } else if (!empty($oneCar['bind_position_ids'])) {
                        $bind_position_ids = explode(',', $oneCar['bind_position_ids']);
                        if (!in_array($position_id, $bind_position_ids)) {
                            $bind_position_ids[] = $position_id;
                        }
                        $saveArr['bind_position_ids'] = implode(',', $bind_position_ids);
                    }
                    if ($saveArr) {
                        $carWhere = array('car_id' => $c_id, 'village_id' => $village_id);
                        $db_house_village_parking_car->editCar($carWhere, $saveArr);
                    }
                }
            }
        }
        return true;
    }
    /**
     * 新版编辑车位
     * @author:zhubaodi
     * @date_time: 2022/3/9 13:47
     */
    public function editParkPosition($data){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_new_pay_order=new HouseNewPayOrder();
        $db_house_new_charge_standard_bind=new HouseNewChargeStandardBind();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $house_village_park_config= $houseVillageParkGarageConfigService->getVillageParkConfig($data['garage_id'],$data['village_id']);
        $where['village_id']=$data['village_id'];
        $where['garage_id']=$data['garage_id'];
        $garage_info=$db_house_village_parking_garage->getOne($where);
        if (empty($garage_info)){
            throw new \think\Exception("车库信息不存在");
        }
        $where1=[
            ['village_id','=',$data['village_id']],
            ['garage_id','=',$data['garage_id']],
            ['position_num','=',$data['position_num']],
            ['position_id','<>',$data['position_id']],
        ];
        $position_info1=$db_house_village_parking_position->getFind($where1);
        if (!empty($position_info1)){
            throw new \think\Exception("车位号重复，无法编辑车位");
        }
        $where11=[
            ['village_id','=',$data['village_id']],
            ['position_id','=',$data['position_id']],
        ];
        $position_info=$db_house_village_parking_position->getFind($where11);
        if (empty($position_info)){
            throw new \think\Exception("车位信息不存在，无法编辑车位");
        }
        if ($house_village_park_config['children_position_type']==1){
            if ($position_info['children_type']==2&&!empty($position_info['parent_position_id'])){
                if ($data['garage_id']!=$position_info['garage_id']){
                    throw new \think\Exception("当前子车位已绑定母车位，请解除绑定关系后在修改所属车库");
                }
            }
            if ($position_info['children_type']==1){
                $where_parent=[];
                $where_parent['village_id']=$data['village_id'];
                $where_parent['parent_position_id']=$data['position_id'];
                $parent_position_count=$db_house_village_parking_position->getCounts($where_parent);
                if ($parent_position_count>0&&$data['garage_id']!=$position_info['garage_id']){
                    throw new \think\Exception("当前母车位已绑定子车位，请解除绑定关系后在修改所属车库");
                }
            }
            if ($data['children_type']==1&&$position_info['children_type']==2&&!empty($position_info['parent_position_id'])){
                $where_parent1=[];
                $where_parent1['village_id']=$data['village_id'];
                $where_parent1['garage_id']=$data['garage_id'];
                $where_parent1['parent_position_id']=$position_info['parent_position_id'];
                $position_info1=$db_house_village_parking_position->getFind($where_parent1);
                if (empty($position_info1)){
                    $db_house_village_parking_position->saveOne(['position_id'=>$data['position_id']],['parent_position_id'=>0]);
                }else{
                    throw new \think\Exception("当前车位有绑定母车位，请先解除绑定");
                }
            }
            if ($data['children_type']==2&&$position_info['children_type']==1){
                $where_order=[];
                $where_order['village_id']=$data['village_id'];
                $where_order['position_id']=$data['position_id'];
                $where_order['is_paid']=2;
                $where_order['is_discard']=1;
                $count_order=$db_house_new_pay_order->getCount($where_order);
                if ($count_order>0){
                    throw new \think\Exception("当前车位有待缴账单或作废账单审核中时，无法修改");
                }
                $where_bind=[];
                $where_bind['b.village_id']=$data['village_id'];
                $where_bind['b.position_id']=$data['position_id'];
                $where_bind['b.is_del']=1;
                $count_bind=$db_house_new_charge_standard_bind->getCount($where_bind);
                if ($count_bind>0){
                    throw new \think\Exception("当前车位有收费标准未解绑，无法修改");
                }
                if ($parent_position_count>0){
                    throw new \think\Exception("当前车位有绑定子车位，请先解除绑定");
                }

            }
        }
        if ($position_info['garage_id']!=$data['garage_id']){
            $where12['village_id']=$data['village_id'];
            $where12['garage_id']=$data['garage_id'];
            $where12['position_pattern']=1;
            $position_count=$db_house_village_parking_position->getCounts($where12);
            if ($garage_info['position_count']<=$position_count){
                throw new \think\Exception("当前车库车位数量已达到设置的车位总数，无法添加车位");
            }
        }
        $configCustomizationService = new ConfigCustomizationService();
        $isWsxCustomizedOpen = $configCustomizationService->getWSXCustomizedOpen();
        if(isset($data['bind_cars']) && $this->hasCloudIntercom()){
            //车位绑定车辆
           $this->handlePositionBindCar($data['village_id'], $data['garage_id'], $position_info['position_id'], $data['bind_cars']);
        }
        if ($data['children_type']==$position_info['children_type']&&$position_info['village_id']==$data['village_id']&&$position_info['garage_id']==$data['garage_id']&&$position_info['position_num']==$data['position_num']&&$position_info['position_area']==$data['position_area']&&$position_info['position_note']==$data['position_note']&&$position_info['end_time']==strtotime($data['end_time'].' 23:59:59') && $position_info['opt_type']==$data['opt_type']){
            $id=1;
            if(isset($position_info['end_time']) && $position_info['end_time']>0){
                $this->updateBindCarEndTime($position_info['position_id'],$position_info['village_id'],$position_info['end_time']);
            }
        }else{
            $position_data=[];
            $position_data['village_id']=$data['village_id'];
            $position_data['garage_id']=$data['garage_id'];
            $position_data['position_num']=$data['position_num'];
            $position_data['position_area']=$data['position_area'];
            $position_data['position_note']=$data['position_note'];
            if (!empty($data['children_type'])){
                $position_data['children_type']=$data['children_type'];
            }
            if (!empty($data['end_time'])&&$data['children_type']==1){
                $position_data['end_time']=strtotime($data['end_time'].' 23:59:59');
            }
            if (!empty($data['pigcms_id'])){
                $position_data['position_status']=2;
            }
            if($isWsxCustomizedOpen && isset($data['opt_type'])){
                $position_data['opt_type']=$data['opt_type'];
            }
            $id=$db_house_village_parking_position->saveOne(['position_id'=>$data['position_id']],$position_data);
            if(isset($position_data['end_time']) && $position_data['end_time']>0){
                 $this->updateBindCarEndTime($data['position_id'],$data['village_id'],$position_data['end_time']);
            }
        }
        if ($position_info['position_pattern']!=2 && $data['pigcms_id']>0){
            if ($house_village_park_config['children_position_type']==1&&!empty($data['end_time'])){
                $where_parent=[];
                $where_parent['village_id']=$data['village_id'];
                $where_parent['garage_id']=$data['garage_id'];
                $where_parent['parent_position_id']=$data['position_id'];
                $parent_position_arr=$db_house_village_parking_position->getColumn($where_parent,'position_id');
                if (!empty($parent_position_arr)){
                    $db_house_village_parking_position->saveOne(['position_id'=>$parent_position_arr],['end_time'=>$position_data['end_time']]);
                    $parent_position_arr[]=$data['position_id'];
                    $park_car_arr=$db_house_village_parking_car->get_column(['car_position_id'=>$parent_position_arr],'car_id');
                    if (!empty($park_car_arr)){
                        $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$park_car_arr],['end_time'=>$position_data['end_time']]);
                    }
                }
            }
            $db_house_village_bind_position=new HouseVillageBindPosition();
            $bind_where=[];
            $bind_where['position_id'] =$data['position_id'];
            $bind_where['village_id'] = $data['village_id'];
            $bind_info=$db_house_village_bind_position->getOne($bind_where);
            if (empty($bind_info)){
                $data_bind=[];
                $data_bind['position_id'] = $data['position_id'];
                $data_bind['user_id'] = $data['pigcms_id'];
                $data_bind['village_id'] = $data['village_id'];
                $db_house_village_bind_position->addOne($data_bind);
            }else{
                $data_bind=[];
                $data_bind['user_id'] = $data['pigcms_id'];
                $db_house_village_bind_position->saveOne($bind_where,$data_bind);
            }
            $db_house_village_parking_position->saveOne(['position_id'=>$data['position_id']],['position_status'=>2]);
        } elseif ($data['position_id'] && $data['village_id']) {
            $db_house_village_bind_position=new HouseVillageBindPosition();
            $bind_where=[];
            $bind_where['position_id'] =$data['position_id'];
            $bind_where['village_id'] = $data['village_id'];
            $bind_info = $db_house_village_bind_position->getOne($bind_where);
            $data['pigcms_id'] = isset($bind_info['user_id']) && $bind_info['user_id'] ? $bind_info['user_id'] : 0;
        }
        $third_import_data_switch_judge = (new ConfigCustomizationService())->getThirdImportDataSwitch();
        if ($position_info['position_pattern']!=2 && $third_import_data_switch_judge && isset($data['pigcms_id']) && $data['pigcms_id']) {
            $db_house_village_user_bind = new HouseVillageUserBind();
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $data['pigcms_id']];
            $userBind = $db_house_village_user_bind->getOne($whereUserBind, 'pigcms_id, vacancy_id, name, phone');
            if ($userBind && !is_array($userBind)) {
                $userBind = $userBind->toArray();
            }
            if (!empty($userBind)) {
                $whereOrderInfo = [];
                $whereOrderInfo[] = ['o.village_id',  '=', $data['village_id']];
                $whereOrderInfo[] = ['o.room_id',     '=', 0];
                $whereOrderInfo[] = ['o.position_id', '=', $data['position_id']];
                $orderList = $db_house_new_pay_order->getList($whereOrderInfo, 'o.order_id, o.pigcms_id, o.name, o.phone');
                if ($orderList && !is_array($orderList)) {
                    $orderList = $orderList->toArray();
                }
                $saveOrder1 = [
                    'room_id'   => $userBind['vacancy_id'],
                ];
                foreach ($orderList as $order) {
                    $saveOrder = $saveOrder1;
                    if (!$order['pigcms_id']) {
                        $saveOrder['pigcms_id'] = $data['pigcms_id'];
                    }
                    if (!$order['name'] && $userBind['name']) {
                        $saveOrder['name'] = $userBind['name'];
                    }
                    if (!$order['phone'] && $userBind['phone']) {
                        $saveOrder['phone'] = $userBind['phone'];
                    }
                    $whereOrder = [];
                    $whereOrder[] = ['order_id', '=', $order['order_id']];
                    $db_house_new_pay_order->saveOne($whereOrder, $saveOrder);
                }
                $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
                $whereSummary = [];
                $whereSummary[] = ['village_id',  '=', $data['village_id']];
                $whereSummary[] = ['room_id',     '=', 0];
                $whereSummary[] = ['position_id', '=', $data['position_id']];
                $saveSummary = [
                    'room_id'   => $userBind['vacancy_id'],
                    'pigcms_id' => $data['pigcms_id'],
                ];
                $db_house_new_pay_order_summary->saveOne($whereSummary, $saveSummary);
            }
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '车位管理-编辑',
                'table'  => 'house_village_parking_position',
                'client' => '小区后台',
                'trigger_path' => '停车管理->车位管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => $position_info
        ];
        $this->laterLogInQueue($queuData);
        return $id;
    }

    public function delParkPosition($data){
        $positionIds = $this->formatPositionIds($data['position_id']);
        if (empty($positionIds)) {
            return 0;
        }
        $positionBillIds = $this->getPositionBillIds($positionIds, $data['village_id']);
        if (!empty($positionBillIds)) {
            if (count($positionIds) == 1) {
                throw new \think\Exception('当前车位有账单');
            }
            $positionIds = array_values(array_diff($positionIds, $positionBillIds));
            if (empty($positionIds)) {
                return 0;
            }
        }

        $id = 0;
        foreach ($positionIds as $positionId) {
            $deleteData = $data;
            $deleteData['position_id'] = $positionId;
            $id += $this->deleteParkPosition($deleteData);
        }
        return $id;
    }

    private function formatPositionIds($positionId)
    {
        if (is_string($positionId)) {
            $positionId = explode(',', $positionId);
        } elseif (!is_array($positionId)) {
            $positionId = [$positionId];
        }
        $positionId = array_map('intval', $positionId);
        $positionId = array_filter($positionId, function ($id) {
            return $id > 0;
        });
        return array_values(array_unique($positionId));
    }

    private function getPositionBillIds($positionIds, $villageId)
    {
        $where = [
            ['position_id', 'in', $positionIds],
            ['village_id', '=', $villageId],
            ['is_paid', 'in', [1, 2]],
            ['is_discard', '=', 1],
        ];
        $positionBillIds = (new HouseNewPayOrder())->where($where)->column('position_id');
        if (empty($positionBillIds)) {
            return [];
        }
        $positionBillIds = array_map('intval', $positionBillIds);
        return array_values(array_unique($positionBillIds));
    }

    private function deleteParkPosition($data)
    {
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $bind_where=[];
        $bind_where['position_id'] =$data['position_id'];
        $bind_where['village_id'] = $data['village_id'];
        $bind_info=$db_house_village_parking_position->getFind($bind_where);
        if (!empty($bind_info)&&isset($bind_info['children_type'])&&$bind_info['children_type']==1){
            $parent_where=[];
            $parent_where['parent_position_id'] =$data['position_id'];
            $parent_where['village_id'] = $data['village_id'];
            $parent_where['children_type'] = 2;
            $parent_arr=$db_house_village_parking_position->getColumn($parent_where,'position_id');
            if (!empty($parent_arr)){
                $db_house_village_parking_position->saveOne([['position_id','in',$parent_arr]],['parent_position_id'=>0]);
            }
        }
        $id=$db_house_village_parking_position->delOne(['position_id'=>$data['position_id']]);
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        if ($village_id > 0) {
            (new CarHandleGetInfoService())->clearCarPositionInfo($village_id);
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '车位管理-删除',
                'table'  => 'house_village_parking_position',
                'client' => '小区后台',
                'trigger_path' => '停车管理->车位管理',
                'trigger_type' => $this->getDeleteName(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => $bind_info
        ];
        $this->laterLogInQueue($queuData);
        return $id;
    }


    /**
     * 查询车位信息
     * @author:zhubaodi
     * @date_time: 2022/3/9 14:50
     */
    public function getPositionInfo($data){
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_bind_position = new HouseVillageBindPosition();
        $position_info_obj=$db_house_village_parking_position->getOne(['pp.position_id'=>$data['position_id']],'pp.*,pg.garage_num,bp.user_id,ub.name,ub.phone,ub.status as bind_status');
        $position_info=array();
        if (!empty($position_info_obj) && !$position_info_obj->isEmpty()){
            $position_info=$position_info_obj->toArray();
            if ($position_info['position_status']==1){
                $position_info['position_status_txt']='未售出';
            }elseif($position_info['position_status']==2){
                $position_info['position_status_txt']='已售出';
            }
            if ($position_info['end_time']>1){
                $position_info['end_time']=date('Y-m-d',$position_info['end_time']);
            }
        }else{
            throw new \think\Exception("车位数据已经不存在了！");
        }
        if($position_info['bind_status']==4){
             //已删除了
            $user_id=$position_info['user_id'];
            $position_info['user_id']=0;
            $position_info['name']='';
            $position_info['phone']='';
            $whereDel=array(['position_id','=',$data['position_id']],['user_id','=',$user_id]);
             $db_house_village_bind_position->delOne($whereDel);
        }
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $garage_id=$position_info['garage_id'];
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        if (!empty($house_village_park_config)){
            $position_info['children_position_type']=$house_village_park_config['children_position_type'];
        }
        $houseVillageParkingCarBindPositionDb = new HouseVillageParkingCarBindPosition();
        $position_info['bind_cars']=array();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $position_info['village_id']);
        $whereArr[] = array('garage_id', '=', $position_info['garage_id']);
        $whereArr[] = array('position_id', '=', $position_info['position_id']);
        $whereArr[] = array('del_time', '=', '0');
        $carBindPosition = $houseVillageParkingCarBindPositionDb->getListData($whereArr);
        if ($carBindPosition && !$carBindPosition->isEmpty()) {
            $carBindPositionTmp = $carBindPosition->toArray();
            if ($carBindPositionTmp) {
                foreach ($carBindPositionTmp as $vv) {
                    $position_info['bind_cars'][]=$vv['car_id'];
                }
                $position_info['bind_cars']=array_unique($position_info['bind_cars']);
            }
        }
        return $position_info;
    }

    /**
     * 查询车库列表（不带分页）
     * @author:zhubaodi
     * @date_time: 2022/3/9 15:04
     */
    public function getGarageList($village_id,$type=0){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $where['village_id']=$village_id;
        $where['status']=1;
        $listObj=$db_house_village_parking_garage->getLists($where,'garage_id,garage_num');
        $list=array();
        if($listObj && !$listObj->isEmpty()){
            $list= $listObj->toArray();
        }
        $garageList=array();
        if($type==1){
            $garageList[]=array('garage_id'=>0, 'garage_num'=> "请选择车库");
        }
        if($list && is_array($list)){
            foreach ($list as $gvv){
                $garageList[]=$gvv;
            }
        }
        $data1=[];
        $data1['list'] = $garageList;
        return  $data1;
    }

    /**
     * 查询车位列表
     * @author:zhubaodi
     * @date_time: 2022/3/9 16:18
     */
    public function getPositionList($data){
        $db_house_village_parking_garage=new HouseVillageParkingPosition();
        $houseVillageParkingCarDb=new HouseVillageParkingCar();
        $where[] = [ 'pp.village_id','=', $data['village_id']];
        $where[] = [ 'pp.position_pattern','=', $data['position_pattern']];
        $where[] = [ 'pg.status','=', 1];
        if (!empty($data['garage_id'])){
            $where[] = [ 'pp.garage_id','=', $data['garage_id']];
        }
        if (!empty($data['position_num'])){
            $where[] = [ 'pp.position_num','=', $data['position_num']];
        }
        if (!empty($data['position_car_status'])){
            $where[] = [ 'pp.position_status','=', $data['position_car_status']];
        }
        if (!empty($data['children_type'])){
            $where['pp.children_type']=$data['children_type'];
        }
        if (!empty($data['position_status'])){
            if($data['position_status'] == 1){
                $where[] = ['c.car_id', 'exp', Db::raw('IS NULL')];
            }elseif ($data['position_status'] == 2){
                $where[] = ['c.car_id', 'exp', Db::raw('IS NOT NULL')];
            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $field='pp.garage_id,pp.start_time,pp.end_time,pg.garage_num,pp.position_id,pp.children_type,pp.position_note,pp.parent_position_id,pp.position_num,pp.position_area,pp.position_status,pp.position_status,pp.position_pattern,c.car_id as is_bind_car';
        $list=$db_house_village_parking_garage->getListss($where,$field,$data['page'],$data['limit'],'pp.position_id DESC','pp.position_id');
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $children_position_type=0;
        if (!empty($list)){
            $list=$list->toArray();
            if (!empty($list)){
                foreach ($list as &$vv){
                    $user_info=$db_house_village_parking_garage->getOne(['pp.position_id'=>$vv['position_id']],'ub.name,ub.phone');
                    $user_name='';
                    if (!empty($user_info) && isset($user_info['name'])){
                        $user_name=$user_info['name'] ? $user_info['name']:'';
                        if($user_info['phone']){
                            $phone_tmp=$houseVillageDb->phone_desensitization_v($user_info['phone'],$data['village_id'],$village_info);
                            $user_name=$user_name ? $user_name.' / '.$phone_tmp:$phone_tmp;
                        }
                    }
                    $vv['user_name']=$user_name;
                    if (empty($vv['position_area'])){
                        $vv['position_area']='0.00';
                    }
                    if ($vv['position_status']==1){
                        $vv['position_status_txt']='未售出';
                    }
                    if ($vv['position_status']==2){
                        $vv['position_status_txt']='已售出';
                    }
                    if ($vv['position_pattern']==1){
                        $vv['position_pattern_txt']='真实车位';
                    }else{
                        $vv['position_pattern_txt']='虚拟车位';
                    }
                    $whereArr=array(['car_position_id','=',$vv['position_id']]);
                    $whereArr[] = array('village_id','=', $data['village_id']);
                    $whereArr[] = array('examine_status','=', 1);
                    $whereArr[] = array('end_time','>', 100);
                   // $whereRaw='bind_position_ids IS NULL OR bind_position_ids=""';
                    $whereRaw='(bind_position_ids IS NULL  OR bind_position_ids="" OR FIND_IN_SET('.$vv['position_id'].',bind_position_ids))';
                    //$whereArr[]=array('bind_position_ids','exp', Db::raw('IS NULL'));
                    $bindCars= $houseVillageParkingCarDb->getNewList($whereArr,'car_id,village_id,car_position_id,car_user_name,car_user_phone,start_time,end_time',1,3,'car_id DESC',$whereRaw);
                    if($bindCars && !$bindCars->isEmpty()){
                        $bindCars=$bindCars->toArray();
                        /*
                        if(count($bindCars)==1){
                            $vv['start_time']=$bindCars['0']['start_time'];
                            $vv['end_time']=$bindCars['0']['end_time'];
                        }
                        */
                        if($vv['position_pattern']==2){
                            //虚拟
                            $phone_tmp=$houseVillageDb->phone_desensitization_v($bindCars['0']['car_user_phone'],$data['village_id'],$village_info);
                            $vv['user_name']=$bindCars['0']['car_user_name'].' / '.$phone_tmp;
                        }
                    }
                    $vv['start_time_str']='';
                    if ($vv['start_time']>86400){
                        $vv['start_time_str']=date('Y-m-d',$vv['start_time']);
                    }
                    if ($vv['end_time']>86400){
                        $vv['end_time']=date('Y-m-d',$vv['end_time']);
                    }else{
                        $vv['end_time']=''; 
                    }
                    $vv['parent_position_num']='';
                    $vv['parent_position_status']='';
                    
                    $garage_id=$vv['garage_id'];
                    $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
                    if (!empty($house_village_park_config)){
                        $children_position_type=$house_village_park_config['children_position_type'];
                    }
                    if ($children_position_type==1){
                        if (!empty($vv['parent_position_id'])&&$vv['children_type']==2){
                            $parent_position_info=$db_house_village_parking_garage->getFind(['position_id'=>$vv['parent_position_id'],'garage_id'=>$vv['garage_id']]);
                            if (!empty($parent_position_info)){
                                $vv['parent_position_num']=$parent_position_info['position_num'];
                                $vv['parent_position_status']='是否确定解绑车位号'.$parent_position_info['position_num'].'?';
                            }
                        }
                        $vv['children_type_txt']=$vv['children_type']==1?'母车位':($vv['children_type']==2?'子车位':'');
                    }else{
                        $vv['children_type_txt']='';
                    }
                    if ($vv['is_bind_car']){
                        $vv['car_status_txt']='已使用';
                    }else{
                        $vv['car_status_txt']='空置';
                    }
                }
            }
        }
        $count = $db_house_village_parking_garage->getCount($where,'distinct pp.position_id');
        $data1=[];
        $data1['list'] = $list;
        $data1['children_type'] = $children_position_type;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['village_id'] = $data['village_id'];
        return  $data1;

    }


    /**
     * 校验停车卡类 返回是否能修改车辆到期时间
     * @author : lkz
     * @date : 2022/11/22
     * @param $village_id
     * @param $type_id
     * @return array
     */
    public function checkParkingCarType($village_id,$type_id){
        $nowdate=date('Y-m-d');
        $data=[
            'status'=>false,
            'msg'=>'',
            'end_time'=>$nowdate,
        ];
        return $data;
    }

    public function getAddCarInfo($village_id=0,$car_type_is_kv=false){
        //$db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        /*
        $house_village_park_config=$db_house_village_park_config->getFind(['village_id' => $village_id]);
        if ($house_village_park_config['park_sys_type'] == 'D3') {
            $parking_car_type_arr =$this->parking_d3_car_type_arr;
        } elseif ($house_village_park_config['park_sys_type'] == 'D7') {
            $parking_car_type_arr =$this->parking_d7_car_type_arr;
        }elseif ($house_village_park_config['park_sys_type'] == 'A11'){
            $parking_car_type_arr=$this->parking_a11_car_type_arr;
        } else {
            $parking_car_type_arr =$this->parking_car_type_arr;
            if ($house_village_park_config['is_park_month_type'] != 1) {
                unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
            }
            if ($house_village_park_config['is_temporary_park_type'] != 1) {
                unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
            }
        }
        */
        $where=array();
        $where['village_id'] = $village_id;
        $info_list =$db_house_village_parking_position->getList($where);
        $city_arr = array('京', '津', '冀', '晋', '蒙', '辽', '吉', '黑', '沪', '苏', '浙', '皖', '闽', '赣', '鲁', '豫', '鄂', '湘', '粤', '桂', '琼', '渝', '川', '贵', '云', '藏', '陕', '甘', '青', '宁', '新');
        $where['status'] = 1;
        $garage_list=$db_house_village_parking_garage->getLists($where);
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $house_village_park_config='';
        $parking_car_type_arr=array();
        if($garage_list && !$garage_list->isEmpty()){
            $garage_list=$garage_list->toArray();
            foreach ($garage_list as $gkk=>$gvv){
                $garage_id=$gvv['garage_id'];
                if(isset($gvv['third_park_key']) && !empty($gvv['third_park_key'])){
                    $garage_list[$gkk]['park_sys_type']='third_ai_park';
                    $garage_list[$gkk]['parking_car_type_arr']=$this->third_ai_park_sys_type;
                    unset($garage_list[$gkk]['extra_data']);
                }else{
                    $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
                    $garage_list[$gkk]['park_sys_type']='A1';
                    $garage_list[$gkk]['parking_car_type_arr']=array();
                    if (!empty($house_village_park_config)){
                        $garage_list[$gkk]['park_sys_type']=$house_village_park_config['park_sys_type'];
                        if ($house_village_park_config['park_sys_type'] == 'D3' || $house_village_park_config['park_sys_type'] == 'HXT85') {
                            $parking_car_type_arr =$this->parking_d3_car_type_arr;
                        } elseif ($house_village_park_config['park_sys_type'] == 'D7') {
                            $parking_car_type_arr =$this->parking_d7_car_type_arr;
                        }elseif ($house_village_park_config['park_sys_type'] == 'A11'){
                            $parking_car_type_arr=$this->parking_a11_car_type_arr;
                        } else {
                            $parking_car_type_arr =$this->parking_car_type_arr;
                            if ($house_village_park_config['is_park_month_type'] != 1) {
                                unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
                            }
                            if ($house_village_park_config['is_temporary_park_type'] != 1) {
                                unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
                            }
                        }
                        if($car_type_is_kv){
                            $parking_car_type_kv_arr=array();
                            foreach ($parking_car_type_arr as $type_key=>$type_vv){
                                $parking_car_type_kv_arr[]=array('key'=>$type_key,'value'=>$type_vv);
                            }
                            $parking_car_type_arr=$parking_car_type_kv_arr;
                        }
                        $garage_list[$gkk]['parking_car_type_arr']=$parking_car_type_arr;
                    }  
                }
            }
        }else{
            $garage_list=array();
        }
        $db_brand_cars = new BrandCars();
        $brand_list = $db_brand_cars->getList([]);
       
        $data=[];
        $data['brand'] = $brand_list;
        $data['garage_list']=$garage_list;
        $data['city_arr']=$city_arr;
        $data['park_sys_type'] = $this->getAllParkSysType($village_id,true);
        $data['car_color_list']=$this->car_color;
        $data['relationship']=$this->relationship;
        $data['info_list']=$info_list;
        $data['parking_car_type_arr']=$parking_car_type_arr;

        return $data;
    }

    public function getUserInfo($data){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $where['village_id']=$data['village_id'];
        $where['type']=[0,1,2,3];
        $where['status']=1;
        $whereOr='name like "%'.$data['value'].'%" or phone like "%'.$data['value'].'%"';
        //$whereOr['phone']=$data['value'];
        $list=$db_house_village_user_bind->getLists($where,$whereOr,'pigcms_id,village_id,uid,usernum,name,phone','pigcms_id desc',1,50);
        return $list;
    }

    public function getCarInfo($data)
    {
        $db_house_village_ParkIng_car = new HouseVillageParkingCar();
        $db_house_village_ParkIng_garage = new HouseVillageParkingGarage();
        $db_house_village_ParkIng_position = new HouseVillageParkingPosition();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $where['c.village_id'] = $data['village_id'];
        $where['c.car_id'] = $data['car_id'];
        $field = 'c.*,b.user_id,b.relationship,u.pigcms_id,u.uid,u.name,u.phone,u.vacancy_id,u.status,u.type';
        $list = $db_house_village_ParkIng_car->getLists($where, $field);
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
        }
        $list['roomArr'] = [];
        if ($list && !in_array($list['type'], array(0, 3)) && $list['vacancy_id'] > 0) {
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $data['village_id']);
            $whereArr[] = array('vacancy_id', '=', $list['vacancy_id']);
            $whereArr[] = array('type', 'in', array(0, 3));
            $whereArr[] = array('status', '=', 1);
            $owner_user_bind_obj = $db_house_village_user_bind->getOne($whereArr, 'pigcms_id,name,phone');
            if ($owner_user_bind_obj && !$owner_user_bind_obj->isEmpty()) {
                $list['name'] = $owner_user_bind_obj['name'];
                $list['phone'] = $owner_user_bind_obj['phone'];
            }
        }
        if ((!isset($list['uid']) || $list['uid'] < 1) && $list['vacancy_id'] > 0 && $list['pigcms_id'] > 0) {
            $list['binding_type'] = 2;
        }
        if (!empty($list['room_id'])) {
            if (!isset($list['uid']) || $list['uid'] < 1) {
                $list['binding_type'] = 1;
            }
            $room_info = $db_house_village_user_vacancy->getOne(['pigcms_id' => $list['room_id']]);
            if (!empty($room_info)) {
                $list['roomArr'] = [$room_info['single_id'], $room_info['floor_id'], $room_info['layer_id'], $room_info['pigcms_id']];
            }
        }
        if ($list['binding_type'] < 1) {
            $list['relationship'] = "7";
        }
        if (!empty($list['car_brands'])) {
            $arr_brans = explode('-', $list['car_brands']);
            $list['brands_type'] = $arr_brans[0];
            $list['brands'] = $arr_brans[1];

        } else {
            $list['brands_type'] = '';
            $list['brands'] = '';
        }
        $list['stored_balance'] = isset($list['stored_balance']) && $list['stored_balance'] > 0 ? $list['stored_balance'] : 0;
        $list['relationship'] = strval($list['relationship']);
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $list['is_car_stored_func'] = 0;
        /*
        if (!empty($list['car_position_id']) && $list['garage_id']<1) {
            $garage_arr = $db_house_village_ParkIng_position->getFind(['position_id' => $list['car_position_id']], 'children_type,position_id,garage_id,position_num');
            if (!empty($garage_arr['garage_id'])) {
                $list['children_type'] = $garage_arr['children_type'];
                $garage_info = $db_house_village_ParkIng_garage->getOne(['garage_id' => $garage_arr['garage_id'], 'status' => 1]);
                if (empty($garage_info)) {
                    $list['garage_id'] = '';
                } else {
                    $list['garage_id'] = $garage_arr['garage_id'];
                }
            }
            if (!isset($garage_arr) || empty($garage_arr)) {
                $list['car_position_id'] = [];
            }
        }
        */
        $park_config = array();
        $list['park_sys_type'] = '';
        $garage_id_arr = array();
        if (!empty($list['garage_id']) && $list['garage_id'] != '0') {
            $list['garage_id'] = explode(',', $list['garage_id']);
            $list['garage_id'] = array_map('intval', $list['garage_id']);
            $garage_id_arr = $list['garage_id'];
        }
        if (!empty($list['car_position_id']) && (empty($list['garage_id']) || $list['garage_id'] != '0')) {
            $garage_arr = $db_house_village_ParkIng_position->getFind(['position_id' => $list['car_position_id']], 'children_type,position_id,garage_id,position_num');
            if (!empty($garage_arr['garage_id'])) {
                $list['children_type'] = $garage_arr['children_type'];
                $garage_info = $db_house_village_ParkIng_garage->getOne(['garage_id' => $garage_arr['garage_id'], 'status' => 1]);
                if (empty($garage_info)) {
                    $list['garage_id'] = '';
                } else {
                    $list['garage_id'] = $garage_arr['garage_id'];
                    $garage_id_arr[] = $garage_arr['garage_id'];
                }
            }
        }
        $bind_position_ids = array();
        if (isset($list['bind_position_ids']) && !empty($list['bind_position_ids'])) {
            $bind_position_ids = explode(',', $list['bind_position_ids']);
            $bind_position_ids = array_map('intval', $bind_position_ids);
        }
        if ($list['car_position_id']) {
            $bind_position_ids[] = intval($list['car_position_id']);
        }
        if ($bind_position_ids) {
            $bind_position_ids = array_unique($bind_position_ids);
        } else {
            $bind_position_ids = array();
        }
        $list['car_position_id'] = $bind_position_ids;
        $position_bind_car = array('text1' => '', 'text2' => '', 'text3' => '', 'text4' => '', 'text5' => '');
        $position_bind_info = array();
        $newPosition_ids = array();
        $end_time = $list['end_time'] > 0 ? $list['end_time'] : 0;

        if ($bind_position_ids) {
            $end_time_time = 0;
            $garage_id_arr = array();
            foreach ($bind_position_ids as $kk => $position_id) {
                $parking_position_obj = $db_house_village_ParkIng_position->getFind(array('position_id' => $position_id), 'position_id,garage_id,position_num,end_time');
                $position_num = '';
                if ($parking_position_obj && !$parking_position_obj->isEmpty()) {
                    $garage_id_arr[] = $parking_position_obj['garage_id'];
                    $newPosition_ids[] = $position_id;
                    $position_num = $parking_position_obj['position_num'];
                    $whereArr = array('village_id' => $data['village_id']);
                    $whereRaw = '(a.car_position_id =' . $position_id . ' OR FIND_IN_SET(' . $position_id . ',bind_position_ids))';
                    $bindCount = $db_house_village_ParkIng_car->getNewCount($whereArr, $whereRaw);
                    $bindCount = $bindCount > 0 ? $bindCount : 0;
                    $textStr = $position_num . ' 已绑定车辆数：' . $bindCount;
                    $kkadd = $kk + 1;
                    $position_bind_car['text' . $kkadd] = $textStr;
                    $position_bind_info[] = $textStr;
                    if ($parking_position_obj['end_time'] > $end_time_time) {
                        $end_time_time = $parking_position_obj['end_time'];
                    }
                }
            }
            $end_time = $end_time_time;
            $db_house_village_ParkIng_car->editHouseVillageParkingCar(array('car_id' => $data['car_id']), array('end_time' => $end_time));
            $list['car_position_id'] = $newPosition_ids;
        }
        $garage_id_arr = array_unique($garage_id_arr);
        $garage_id_arr = array_map('intval', $garage_id_arr);
        $garage_id_arr = array_values($garage_id_arr);
        $list['garage_id'] = $garage_id_arr;
        $park_sys_type_stored = array('A11', 'a11', 'A1', 'a1', 'D7', 'd7', 'D3', 'd3');
        $park_sys_type_arr = $this->getAllParkSysType($data['village_id']);
        $configCustomizationService = new ConfigCustomizationService();
        $isAihorseCustomizedJudge = $configCustomizationService->getAihorseCustomizedJudge();
        if ($isAihorseCustomizedJudge) {
            $garage_info = $db_house_village_ParkIng_garage->getOne(array(['garage_id', 'in', $garage_id_arr], ['status', '=', 1], ['third_park_key', '<>', '']));
            if ($garage_info && isset($garage_info['third_park_key']) && !empty($garage_info['third_park_key'])) {
                $park_sys_type_arr[] = 'third_ai_park';
            }
        }
        if (in_array('D7', $park_sys_type_arr)) {
            $parking_car_type_arr = $this->parking_d7_car_type_arr;
        } else if (in_array('D3', $park_sys_type_arr) || in_array('HXT85', $park_sys_type_arr)) {
            $parking_car_type_arr = $this->parking_d3_car_type_arr;
        } else if (in_array('A11', $park_sys_type_arr)) {
            $parking_car_type_arr = $this->parking_a11_car_type_arr;
        } elseif (in_array('third_ai_park', $park_sys_type_arr)) {
            $parking_car_type_arr = $this->third_ai_park_sys_type;
        } else {
            $parking_car_type_arr = $this->parking_car_type_arr;
        }
        $parking_car_type_kv_arr = array();
        foreach ($parking_car_type_arr as $type_key => $type_vv) {
            $parking_car_type_kv_arr[] = array('key' => intval($type_key), 'label' => $type_vv);
        }
        $list['parking_car_type_arr'] = $parking_car_type_kv_arr;
        $list['park_sys_type'] = $park_sys_type_arr;
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $list['is_car_stored_func'] = 0;
        $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id_arr, $data['village_id'], 'is_temporary_park_type desc');
        $park_sys_type_stored = array('A11', 'a11', 'A1', 'a1', 'D7', 'd7', 'D3', 'd3');
        if (in_array($park_config['park_sys_type'], $park_sys_type_stored) && ($park_config['is_temporary_park_type'] == 1)) {
            //有储值功能，储值功能开启了
            $list['is_car_stored_func'] = 1;
        }
        $list['end_time'] = $end_time > 0 ? date('Y-m-d', $end_time) : '';
        $list['position_bind_car'] = $position_bind_car;
        $list['position_bind_info'] = $position_bind_info;
        $list['check_parking_car_type'] = $this->checkParkingCarType($data['village_id'], $list['parking_car_type']);
        return $list;
    }

    public function addCar($data){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_bind_car=new HouseVillageBindCar();
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $db_house_village_ParkIng_garage=new HouseVillageParkingGarage();
        $db_park_system=new ParkSystem();
        if (!empty($data['end_time'])){
            $data['end_time']=strtotime($data['end_time'].' 23:59:59');
        }
        $car_type=isset($data['car_type']) ? $data['car_type']:0;
        $res =$db_house_village_parking_car->getFind(['car_number' => $data['car_number'], 'province' => $data['province'], 'village_id' => $data['village_id']]);
        if ($res) {
            throw new \think\Exception("车牌号已存在，无法添加车辆");
        }
        if ($data['car_stop_num']) {
            $res =$db_house_village_parking_car->getFind(['car_stop_num' => $data['car_stop_num'], 'village_id' => $data['village_id']]);
            if ($res) {
                throw new \think\Exception("停车卡号已存在，无法添加车辆");
            }
        }
        //todo 车位号为空是 为临时车
        /*
        if (empty($data['car_position_id'])) {
            $postData['garage_id'] =  $data['garage_id'];
            $postData['position_num'] = $data['car_number'];
            $postData['position_type'] = 3;
            $postData['village_id'] = $data['village_id'];
            $postData['start_time'] = time();
            $postData['end_time'] = $data['end_time'];
            $postData['position_pattern'] = 2;
            $position_id = $db_house_village_parking_position->addOne($postData);
            $data['car_position_id'] = $position_id;
        } 
        */
        $car_position_ids=array();
        if($data['car_position_id'] && is_array($data['car_position_id'])){
            $car_position_ids=$data['car_position_id'];
        }elseif($data['car_position_id'] && is_string($data['car_position_id'])){
            $car_position_ids[]=$data['car_position_id'];
        }
        if($car_position_ids){
            $data['car_position_id']=$car_position_ids['0'];
        }else{
            $data['car_position_id']=0;
        }
        if(!empty($data['car_position_id']) && $data['car_position_id']>0){
            $parking_position=$db_house_village_parking_position->getFind(['position_id' => $data['car_position_id'],'village_id' => $data['village_id']]);
            if(!empty($parking_position['position_id'])){
                $data['car_position_id'] =$parking_position['position_id'];
            }else{
                throw new \think\Exception("车位已经不存在，请重新选择车位！");
            }
        }
        $village_user_bind=array();
        $garage_id=isset($data['garage_id']) ? $data['garage_id']:0;
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        // 如果添加了到期时间 且 到期时间大于当前时间 且开启了智慧停车功能  同步至设备
        $park_versions = $house_village_park_config ? $house_village_park_config['park_versions']:0;
        $is_park_month_type =$house_village_park_config ? $house_village_park_config['is_park_month_type']:0;
        if ($data['binding_type']==2){
            $user_bind= $db_house_village_user_bind->getSumBills([ 'village_id' =>$data['village_id'], 'pigcms_id' => $data['pigcms_id'], 'type' =>  [0, 1, 2, 3], 'status' => 1],'pigcms_id,vacancy_id,uid,name,phone');
            if (empty($user_bind)) {
                throw new \think\Exception("该房间暂无业主，无法添加车辆");
            }
            if($data['relationship']==1 && $user_bind['name']){
                $data['car_user_name']=$user_bind['name'];
            }
            if($data['relationship']==1 && $user_bind['phone']){
                $data['car_user_phone']=$user_bind['phone'];
            }
            $village_user_bind[]=array('pigcms_id'=>$user_bind['pigcms_id'],'uid'=>$user_bind['uid']);
        }elseif($data['binding_type']==1){
            $user_bind= $db_house_village_user_bind->getSumBills([ 'village_id' =>$data['village_id'], 'vacancy_id' => $data['room_data'],  'type'=>[0,1,2,3], 'status' => 1],'pigcms_id,vacancy_id,uid,name,phone');
            if(empty($user_bind)){
                throw new \think\Exception("该房间暂无人员");
            }
            $data['room_id'] = $user_bind['vacancy_id'];
            if($data['relationship']==1 && $user_bind['name']){
                $data['car_user_name']=$user_bind['name'];
            }
            if($data['relationship']==1 && $user_bind['phone']){
                $data['car_user_phone']=$user_bind['phone'];
            }
            $village_user_bind[]=array('pigcms_id'=>$user_bind['pigcms_id'],'uid'=>$user_bind['uid']);
        }
        if (empty($data['car_position_id_path'])){
            $data['car_position_id_path']=$data['car_position_id'];
        }
        if(!empty($car_position_ids)){
            $data['bind_position_ids']=implode(',',$car_position_ids);
        }else{
            $data['bind_position_ids']='';
        }
     
        $relationship=$data['relationship'];
        unset($data['relationship']);
         unset($data['room_data']);
         unset($data['pigcms_id']);
         $data['car_addtime']=time();
        $data['examine_time']=time();
        //添加时没有时间了
        if($car_type<1){
            $data['end_time']=0;
        }
        if(!empty($car_position_ids) && $car_type<1){
            $wherePositionArr=array(['village_id' ,'=', $data['village_id']]);
            $wherePositionArr[]=array('position_id','in',$car_position_ids);
            $parking_position_car=$db_house_village_parking_position->getFind($wherePositionArr,'*','end_time desc');
            if($parking_position_car && !$parking_position_car->isEmpty()){
                $data['end_time']=$parking_position_car['end_time']  && $parking_position_car['end_time']>0 ? $parking_position_car['end_time']:0;
            }
        }
        $result = $db_house_village_parking_car->addHouseVillageParkingCar($data);
        if ($result){
            $car_id=$result;
            $garage_info = $db_house_village_ParkIng_garage->getOne(['garage_id' => $garage_id, 'status' => 1]);
            if ($garage_info && isset($garage_info['third_park_key']) && !empty($garage_info['third_park_key'])) {
                $queueData=array('village_id'=>$data['village_id'],'car_id'=>$result,'park_key'=>$garage_info['third_park_key'],'opt_type'=>'update_third_ai_park_car');
                $this->parkPassageQueuePushLaterToJob($queueData);
            }
            $this->handleCarBindPosition($data['village_id'],$garage_id,$car_position_ids,$result);

            $car_number = $data['province'] . $data['car_number'];
            if (count($car_position_ids) == 1 && $car_number) {
                $handleParams = ['village_id' => $data['village_id']];
                $car_position_id = reset($car_position_ids);
                $this->handleSingleCarNumberPosition($car_position_id, $car_number, $handleParams);
            }
            if (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D7'&&!empty($house_village_park_config['d7_park_id'])){
                $d7_data=[];
                $d7_data['park_id']=$house_village_park_config['d7_park_id'];
                $d7_data['operate']=1;
                $d7_data['vehicleNo']=$data['province'].$data['car_number'];
                if (!empty($data['car_user_name'])){
                    $d7_data['userName']=$data['car_user_name'];
                }
                if (!empty($data['car_user_phone'])){
                    $d7_data['tels']=$data['car_user_phone'];
                }
                if (!empty($data['end_time'])){
                    $d7_data['beginTime']=time();
                    $d7_data['endTime']=$data['end_time'];
                    if (isset($data['parking_car_type'])&&!empty($data['parking_car_type'])&&$data['parking_car_type']<9){
                    }else{
                        $data['parking_car_type']=1;
                    }
                }
                if (isset($data['parking_car_type'])&&!empty($data['parking_car_type'])){
                    $d7_data['carTypeCode']=$this->parking_D7_car_type_value[$data['parking_car_type']]['value'];
                }else{
                    $d7_data['carTypeCode']=36;
                    $data['parking_car_type']=9;
                }
               $d7_result= (new QinLinCloudService())->whiteList($d7_data);
                if (!empty($d7_result)&&$d7_result['state']=='200'&&!empty($d7_result['data'])){
                    $d7_result['data']=json_decode($d7_result['data'],true);
                    if (!empty($d7_result['data']['vehicleList'][0]['id'])){
                        $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$result],['parking_car_type'=>$data['parking_car_type'],'vehicleId'=>$d7_result['data']['vehicleList'][0]['id']]);
                    }
                }

            }
            elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D3'){
                //同步白名单到设备上
                if (!empty($data['end_time'])){
                    $white_record=[
                        'village_id'=>$data['village_id'],
                        'car_number'=>$data['province'].$data['car_number']
                    ];
                    (new HouseVillageParkingService())->addWhitelist($white_record);
                }
            }
            if (!empty($village_user_bind)) {
                foreach ($village_user_bind as $v) {
                    $db_house_village_bind_car->addOne(['village_id' => $data['village_id'], 'user_id' => $v['pigcms_id'],'uid'=>$v['uid'], 'car_id' => $result,'relationship'=>$relationship]);
                }
            }
            // 对应车位改为已使用
            if ($data['car_position_id']) {
//                $db_house_village_parking_position->saveOne(['position_id' => $data['car_position_id']],['position_status'=>2]);
            }
            if ($park_versions && 2 == $park_versions && isset($parking_position) && $parking_position) {
                $park_system=$db_park_system->getFind(['park_id' => $data['village_id']]);
                invoke_cms_model('House_village_parking_car/carToDevice', [$result, $data,$data['village_id'], $park_versions, $park_system, $parking_position,$house_village_park_config], true);
            }
            /*
            if($data['car_position_id'] && $data['end_time']) {
                $park_position_set = [
                    'end_time' => $data['end_time']
                ];
                $db_house_village_parking_position->saveOne(['position_id' => $data['car_position_id'],'village_id' => $data['village_id']],$park_position_set);
            }
            */
            if($house_village_park_config && $house_village_park_config['park_sys_type'] == 'D6'){
                (new D6Service())->d6synVehicle($data['village_id'],$result);
            }elseif ($house_village_park_config && $house_village_park_config['park_sys_type'] == 'A11'){
                $this->assembleEditCarMethod($data);
            }elseif (!empty($car_position_ids) && $house_village_park_config && $house_village_park_config['park_sys_type'] == 'HTX85'){
                $parkDevInfoHandleService=  new \app\community\model\service\Park\ParkDevInfoHandleService();
                $parkDevInfoHandleService->batchMonthCarToDev($data['village_id'],$car_id);
            }
        }
        return $result;
    }

    public function handleCarBindPosition($village_id = 0, $garage_id = 0, $car_position_ids = array(), $car_id = 0)
    {
        $houseVillageParkingCarBindPositionDb = new HouseVillageParkingCarBindPosition();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $carBindPositionArr = array();
        $nowtime = time();
        if ($car_id < 1 || $village_id < 1) {
            return false;
        }
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        /*
        if(is_array($garage_id)){
            $whereArr[] = array('garage_id', 'in', $garage_id);
        }else{
            $whereArr[] = array('garage_id', '=', $garage_id);
        }
        */
        $whereArr[] = array('car_id', '=', $car_id);
        $whereArr[] = array('del_time', '=', '0');
        $carBindPosition = $houseVillageParkingCarBindPositionDb->getListData($whereArr);
        if ($carBindPosition && !$carBindPosition->isEmpty()) {
            $carBindPositionTmp = $carBindPosition->toArray();
            if ($carBindPositionTmp) {
                foreach ($carBindPositionTmp as $vv) {
                    $carBindPositionArr[$vv['position_id']] = $vv;
                }
            }
            $houseVillageParkingCarBindPositionDb->updateAllData($whereArr, ['del_time' => $nowtime]);
        }
        if (!empty($car_position_ids)) {
            foreach ($car_position_ids as $p_id) {
                if ($carBindPositionArr && isset($carBindPositionArr[$p_id]) && !empty($carBindPositionArr[$p_id])) {
                    $whereArr = array();
                    $whereArr[] = array('id', '=', $carBindPositionArr[$p_id]['id']);
                    $whereArr[] = array('car_id', '=', $car_id);
                    $whereArr[] = array('village_id', '=', $village_id);
                    $houseVillageParkingCarBindPositionDb->saveData($whereArr, ['del_time' => 0]);
                } else {
                    $whereParr=array(['position_id','=',$p_id],['village_id','=',$village_id]);
                    
                    $parking_positionObj=$db_house_village_parking_position->getFind($whereParr,'position_id,garage_id');
                    $garage_id_tmp=0;
                    if($parking_positionObj && !$parking_positionObj->isEmpty()){
                        $garage_id_tmp=$parking_positionObj['garage_id'];
                    }
                    $oneData = array('village_id' => $village_id,'position_id' => $p_id,'garage_id' => $garage_id_tmp);
                    $oneData['car_id'] =  $car_id;
                    $oneData['bind_position_time'] = $nowtime;
                    $oneData['del_time'] = 0;
                    $houseVillageParkingCarBindPositionDb->addOneData($oneData);
                }
            }
        }
        return true;
    }

    
    public function editCar($data){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_bind_car=new HouseVillageBindCar();
        $db_house_village_bind_position=new HouseVillageBindPosition();
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $db_house_village_ParkIng_garage=new HouseVillageParkingGarage();
        $db_park_system=new ParkSystem();
        $data['examine_time']=time();
        if (!empty($data['end_time'])){
            $data['end_time']=strtotime($data['end_time'].' 23:59:59');
        }
        $car_type=isset($data['car_type']) ? $data['car_type']:0;
        $res =$db_house_village_parking_car->getFind(['car_id' => $data['car_id'], 'village_id' => $data['village_id']]);
        if (empty($res) || $res->isEmpty()) {
            throw new \think\Exception("车牌号不存在，无法修改车辆");
        }
        $res=$res->toArray();
        $parking_position=array();
        //todo 车位号为空是 为临时车

        /*
        if (empty($data['car_position_id']) && $data['examine_status']==1) {
            $postData['garage_id'] =$data['garage_id'];
            $postData['position_num'] = $data['car_number'];
            $postData['position_type'] = 3;
            $postData['village_id'] = $data['village_id'];
            $postData['start_time'] = time();
            $postData['end_time'] = $data['end_time'];
            $postData['position_pattern'] = 2;
            $position_id = $db_house_village_parking_position->addOne($postData);
            $data['car_position_id'] = $position_id;
        }
        */
        $car_position_ids=array();
        if($data['car_position_id'] && is_array($data['car_position_id'])){
            $car_position_ids=$data['car_position_id'];
        }elseif($data['car_position_id'] && is_string($data['car_position_id'])){
            $car_position_ids[]=$data['car_position_id'];
        }
        if($car_position_ids){
            $data['car_position_id']=$car_position_ids['0'];
        }else{
            $data['car_position_id']=0;
        }
        if(!empty($data['car_position_id']) && $data['car_position_id']>0  && $data['examine_status']==1){
            $parking_position=$db_house_village_parking_position->getFind(['position_id' => $data['car_position_id'],'village_id' => $data['village_id']]);
            if(!empty($parking_position['position_id'])){
                    $data['car_position_id'] =$parking_position['position_id'];
                }else{
                    throw new \think\Exception("车位已经不存在，请重新选择车位！");
            }
        }
        $garage_ids=isset($data['garage_id']) ? $data['garage_id']:0;
        if(is_array($garage_ids)){
            $data['garage_id']=implode(',',$garage_ids);
        }
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_ids,$data['village_id'],'park_versions desc');
        
        // 如果添加了到期时间 且 到期时间大于当前时间 且开启了智慧停车功能  同步至设备
        $park_versions =$house_village_park_config ?  $house_village_park_config['park_versions']:0;
        $is_park_month_type =$house_village_park_config ? $house_village_park_config['is_park_month_type']:0;
        $village_user_bind=array();
        if ($data['binding_type']==2){
            $user_bind= $db_house_village_user_bind->getSumBills([ 'village_id' =>$data['village_id'], 'pigcms_id' => $data['pigcms_id'], 'type' => [0, 1, 2, 3], 'status' => 1],'pigcms_id,vacancy_id,uid,name,phone');
            if (empty($user_bind) && $data['examine_status']==1) {
                throw new \think\Exception("该房间暂无业主，无法添加车辆");
            }
            if($user_bind && isset($user_bind['pigcms_id'])){
                $village_user_bind[]=$user_bind['pigcms_id'];
            }
            $data['room_id']=0;
            if($data['relationship']==1 && $user_bind['name']){
                $data['car_user_name']=$user_bind['name'];
            }
            if($data['relationship']==1 && $user_bind['phone']){
                $data['car_user_phone']=$user_bind['phone'];
            }
        }elseif($data['binding_type']==1){
            $user_bind= $db_house_village_user_bind->getSumBills([ 'village_id' =>$data['village_id'], 'vacancy_id' => $data['room_data'],  'type'=>[0,1,2,3], 'status' => 1],'pigcms_id,vacancy_id,uid,name,phone');
            if(empty($user_bind) && $data['examine_status']==1){
                throw new \think\Exception("该房间暂无人员");
            }
            $data['room_id'] = $data['room_data'];
            if($user_bind && isset($user_bind['pigcms_id'])){
                $village_user_bind[]=$user_bind['pigcms_id'];
                $data['pigcms_id']=$user_bind['pigcms_id'];
                $data['room_id']=$user_bind['vacancy_id'];
                if($data['relationship']==1 && $user_bind['name']){
                    $data['car_user_name']=$user_bind['name'];
                }
                if($data['relationship']==1 && $user_bind['phone']){
                    $data['car_user_phone']=$user_bind['phone'];
                }
            }
        }else{
            $data['room_id']=0;
        }
        if (empty($data['car_position_id_path'])){
            $data['car_position_id_path']=$data['car_position_id'];
        }
        $relationship=$data['relationship'];
        $pigcms_id=$data['pigcms_id'];
        unset($data['relationship']);
        unset($data['room_data']);
        unset($data['pigcms_id']);
        if (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D7'){
            if (!empty($data['end_time'])&&$res['parking_car_type']>=17){
                throw new \think\Exception("当前车辆为储值车类型，无法修改车辆到期时间");
            }
        }
        if ($house_village_park_config['children_position_type']==1&&$parking_position['children_type']==2&&!empty($data['end_time'])){
            $data['end_time']='';
        }
        if(isset($data['end_time']) && $car_type<1){
            //不允许修改到期时间 车位出统一修改
            unset($data['end_time']);
        }
        if(!empty($car_position_ids)){
            $data['bind_position_ids']=implode(',',$car_position_ids);   
        }else{
            $data['bind_position_ids']='';
            if($car_type<1){
                $data['end_time']=0;
            }
        }
        $configCustomizationService = new ConfigCustomizationService();
        $isAihorseCustomizedJudge = $configCustomizationService->getAihorseCustomizedJudge();
        if(!is_array($garage_ids)){
            $garage_ids=array($garage_ids);
        }
        if ($isAihorseCustomizedJudge) {
            $garage_info = $db_house_village_ParkIng_garage->getOne(array(['garage_id', 'in', $garage_ids], ['status', '=', 1], ['third_park_key', '<>', '']));
            if ($garage_info && isset($garage_info['third_park_key']) && !empty($garage_info['third_park_key'])) {
                unset($data['equipment_no']);
                $queueData = array('village_id' => $data['village_id'], 'car_id' => $data['car_id'], 'park_key' => $garage_info['third_park_key'], 'opt_type' => 'update_third_ai_park_car');
                $this->parkPassageQueuePushLaterToJob($queueData);
            }
        }
        fdump_api(['对应车辆储值余额变动【编辑车辆信息】'=>$res,'now_order' => $data], 'park_temp/stored_balance_' . $res['car_number'], 1);
        $result = $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$data['car_id'],'village_id' => $data['village_id']],$data);
        
        if ($result){
            $car_number = $res['province'] . $res['car_number'];
            $carCachekey='village:'.$data['village_id'].':car:'.$car_number;
            cache($carCachekey, null);
            $this->handleCarBindPosition($data['village_id'], $garage_ids, $car_position_ids, $data['car_id']);
            if (count($car_position_ids) == 1 && $car_number) {
                $handleParams = ['village_id' => $data['village_id']];
                $car_position_id = reset($car_position_ids);
                $this->handleSingleCarNumberPosition($car_position_id, $car_number, $handleParams);
            }
            $service_qinlin=new QinLinCloudService();
            if (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D7'&&!empty($house_village_park_config['d7_park_id'])&&!empty($res['vehicleId'])){
                if ($res['examine_status']==1||$data['examine_status']==1){
                    $d7_data=[];
                    $d7_data['park_id']=$house_village_park_config['d7_park_id'];
                    $d7_data['operate']=2;
                    $d7_data['vehicleId']=$res['vehicleId'];
                    $d7_data['vehicleNo']=$data['province'].$data['car_number'];
                    if (!empty($data['car_user_name'])){
                        $d7_data['userName']=$data['car_user_name'];
                    }
                    if (!empty($data['car_user_phone'])){
                        $d7_data['tels']=$data['car_user_phone'];
                    }
                    if (!empty($data['end_time'])){
                        $d7_data['beginTime']=time();
                        $d7_data['endTime']=$data['end_time'];
                        if (isset($data['parking_car_type'])&&!empty($data['parking_car_type'])&&$data['parking_car_type']<9){
                        }else{
                            $data['parking_car_type']=1;
                        }
                    }
                    if (isset($data['parking_car_type'])&&!empty($data['parking_car_type'])){
                        $d7_data['carTypeCode']=$this->parking_D7_car_type_value[$data['parking_car_type']]['value'];
                    }else{
                        $d7_data['carTypeCode']=36;
                        $data['parking_car_type']=9;
                    }
                    //  print_r([$res['parking_car_type'],$service_qinlin->temp_car_type,$data['parking_car_type'],$service_qinlin->temp_car_type]);die;
                    if ($res['parking_car_type']>8&&$res['parking_car_type']<17&&($data['parking_car_type']<9||$data['parking_car_type']>16)){
                        $d7_data['operate']=3;
                        $d7_result= $service_qinlin->whiteList($d7_data);
                        if (!empty($d7_result)&&$d7_result['state']=='200'&&!empty($d7_result['data'])){
                            $d7_data['operate']=1;
                            $d7_result1=  $service_qinlin->whiteList($d7_data);
                            if (!empty($d7_result1)&&$d7_result1['state']=='200'&&!empty($d7_result1['data'])){
                                $d7_result1['data']=json_decode($d7_result1['data'],true);
                                fdump_api([$d7_result1['data'],$d7_result1['data']['vehicleList'][0]['id']],'D7/addCar_0819',1);
                                if (!empty($d7_result1['data']['vehicleList'][0]['id'])){
                                    $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$data['car_id'],'village_id' => $data['village_id']],['parking_car_type'=>$data['parking_car_type'],'vehicleId'=>$d7_result1['data']['vehicleList'][0]['id']]);
                                }
                            }
                        }
                    }else{
                        $service_qinlin->whiteList($d7_data);
                        $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$data['car_id'],'village_id' => $data['village_id']],['parking_car_type'=>$data['parking_car_type']]);
                    }
                }
            }
            elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D7'&&!empty($house_village_park_config['d7_park_id'])&&empty($res['vehicleId'])) {
                if ($res['examine_status']==1||$data['examine_status']==1) {
                    $d7_data = [];
                    $d7_data['park_id'] = $house_village_park_config['d7_park_id'];
                    $d7_data['operate'] = 1;
                    $d7_data['vehicleNo'] = $data['province'] . $data['car_number'];

                    if (!empty($data['car_user_name'])) {
                        $d7_data['userName'] = $data['car_user_name'];
                    }
                    if (!empty($data['car_user_phone'])) {
                        $d7_data['tels'] = $data['car_user_phone'];
                    }
                    if (!empty($data['end_time'])) {
                        $d7_data['beginTime'] = time();
                        $d7_data['endTime'] = $data['end_time'];
                        if (isset($data['parking_car_type']) && !empty($data['parking_car_type']) && $data['parking_car_type'] < 9) {
                        } else {
                            $data['parking_car_type'] = 1;
                        }
                    }
                    if (isset($data['parking_car_type']) && !empty($data['parking_car_type'])) {
                        $d7_data['carTypeCode'] = $this->parking_D7_car_type_value[$data['parking_car_type']]['value'];
                    } else {
                        $d7_data['carTypeCode'] = 36;
                        $data['parking_car_type'] = 9;
                    }
                    $d7_result = $service_qinlin->whiteList($d7_data);
                    fdump_api([$d7_result, $d7_data], 'D7/addCar_0819', 1);
                    if (!empty($d7_result) && $d7_result['state'] == '200' && !empty($d7_result['data'])) {
                        $d7_result['data'] = json_decode($d7_result['data'], true);
                        fdump_api([$d7_result['data'], $d7_result['data']['vehicleList'][0]['id']], 'D7/addCar_0819', 1);
                        if (!empty($d7_result['data']['vehicleList'][0]['id'])) {
                            $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $data['car_id'], 'village_id' => $data['village_id']], ['parking_car_type' => $data['parking_car_type'], 'vehicleId' => $d7_result['data']['vehicleList'][0]['id']]);
                        }
                    }
                }
            }
            elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D3'){
                $carCachekey='village:'.$res['village_id'].':car:'.$res['province'].$res['car_number'];
                cache($carCachekey,null);
                //同步白名单到设备上
                if (!empty($data['end_time']) && ($res['examine_status']==1||$data['examine_status']==1)){
                    $white_record=[
                        'village_id'=>$data['village_id'],
                        'car_number'=>$res['province'].$res['car_number']
                    ];
                    (new HouseVillageParkingService())->addWhitelist($white_record);
                }
            }

            if (!empty($village_user_bind)) {
                $bing_info=$db_house_village_bind_car->getFind(['village_id' => $data['village_id'],  'car_id' => $data['car_id']]);
                if (!empty($bing_info)){
                    $db_house_village_bind_car->saveOne(['village_id' => $data['village_id'],  'car_id' => $data['car_id']],['relationship'=>$relationship,'user_id' => $pigcms_id,'uid'=>$user_bind['uid']]);
                }else{
                    $db_house_village_bind_car->addOne(['village_id' => $data['village_id'], 'car_id' => $data['car_id'], 'relationship'=>$relationship,'user_id' => $pigcms_id,'uid'=>$user_bind['uid']]);
                }
            }elseif($data['binding_type']>0){
                //$db_house_village_bind_car->delOne(['village_id' => $data['village_id'],  'car_id' => $data['car_id']]);
            }

            if ($park_versions && 2 == $park_versions && $parking_position) {
                $park_system=$db_park_system->getFind(['park_id' => $data['village_id']]);
                invoke_cms_model('House_village_parking_car/carToDevice', [$result, $data, $data['village_id'], $park_versions, [], $parking_position,$house_village_park_config], true);
            }
        }
        fdump_api(['data'=>$data,'house_village_park_config'=>$house_village_park_config], '00editCar', 1);
        if($house_village_park_config && ($res['examine_status']==1||$data['examine_status']==1)){
            if($house_village_park_config['park_sys_type'] == 'D6'){
                (new D6Service())->d6synVehicle($data['village_id'],$data['car_id']);
            }elseif ($house_village_park_config['park_sys_type'] == 'A11'){
                $this->assembleEditCarMethod($data);
            }elseif (!empty($car_position_ids) && $house_village_park_config['park_sys_type'] == 'HTX85'){
                $parkDevInfoHandleService=  new \app\community\model\service\Park\ParkDevInfoHandleService();
                $parkDevInfoHandleService->batchMonthCarToDev($data['village_id'],$data['car_id']);
            }
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '车辆管理-编辑',
                'table'  => 'house_village_parking_car',
                'client' => '小区后台',
                'trigger_path' => '停车管理->车辆管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => $res
        ];
        $this->laterLogInQueue($queuData);
        return $result;
    }

    public function handleSingleCarNumberPosition($car_position_id, $car_number, $params = [])
    {
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;

        $parking_position = $db_house_village_parking_position->getFind(['position_id' => $car_position_id, 'village_id' => $village_id]);
        if ($parking_position && !is_array($parking_position)) {
            $parking_position = $parking_position->toArray();
        }
        if (isset($parking_position['use_car_id']) && $parking_position['use_car_id'] && isset($parking_position['use_car_number']) && $parking_position['use_car_number']==$car_number) {
            return true;
        }
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereRaw = '(car_position_id =' . $car_position_id . ' OR FIND_IN_SET(' . $car_position_id . ',bind_position_ids))';
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $car_list_obj = $db_house_village_parking_car->getNewList($whereArr, '*', 0, 20, 'car_id DESC', $whereRaw);
        if ($car_list_obj && !is_array($car_list_obj)) {
            $car_list_obj = $car_list_obj->toArray();
        }
        if (!empty($car_list_obj) && count($car_list_obj) == 1) {
            // 确定车牌是否绑定了单个车位是有截止时间直接绑定
            $car_info = reset($car_list_obj);
            if (isset($car_info['province']) && isset($car_info['car_number']) && $car_number == $car_info['province'] . $car_info['car_number']) {
                // 车位也是只绑定了一个车辆
                if(!empty($parking_position['use_car_number']) && $parking_position['use_car_number']!=$car_number){
                    $whereArr = array('position_id' => $car_position_id);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '');
                    (new HouseVillageParkingService())->updateParkingPositionUse($whereArr, $saveArr, $car_number, 'handleSingleCarNumberPosition');  
                }

            }
        }
        return true;
    }
    
    //针对A11组装数据 触发下发
    public function assembleEditCarMethod($param){
        if(!isset($param['village_id']) || !isset($param['province']) || !isset($param['car_number'])){
            return true;
        }
        $car_number=(new D6Service())->checkCarNumber($param['province'],$param['car_number']);
        (new A11Service())->triggerWhitelist($param['village_id'],$car_number,0,0);
        return true;
    }


    public function delCar($data){
        $db_house_village_bind_car=new HouseVillageBindCar();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_bind_position=new HouseVillageBindPosition();
        $bind_info=$db_house_village_bind_car->getFind(['car_id'=>$data['car_id']]);
        $car_info=$db_house_village_parking_car->getFind(['car_id'=>$data['car_id']]);
        $res=0;
        if (empty($car_info) || $car_info->isEmpty()){
            return $res;
        }
        $car_info=$car_info->toArray();
        $village_id=0;
        if(isset($data['village_id']) && $data['village_id']){
            $village_id=$data['village_id'];
        }elseif($car_info['village_id']){
            $village_id=$car_info['village_id'];
        }
        $garage_id=isset($car_info['garage_id']) ? $car_info['garage_id']:0;
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        if (!empty($bind_info)){
           $res1= $db_house_village_bind_car->delOne(['car_id'=>$data['car_id']]);
           if ($res1>0){
              $res= $db_house_village_parking_car->delHouseVillageParkingCar(['car_id'=>$data['car_id']]);
           }
        }else{
            $res= $db_house_village_parking_car->delHouseVillageParkingCar(['car_id'=>$data['car_id']]);
        }
        if ($res){
            if($car_info['equipment_no'] && $car_info['add_cfrom']=='third_ai_park'){
                $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                $car_number_tmp=$car_info['province'] . $car_info['car_number'];
                $houseNewAiParkingService->delFailMonthCar($car_info['equipment_no'],$car_number_tmp,$village_id);
            }
            $bind_position=$db_house_village_bind_position->getOne(['position_id' => $car_info['car_position_id'],'village_id' => $car_info['village_id']]);
            if (!empty($car_info['car_position_id'])&&empty($bind_position)){
                $db_house_village_parking_position->saveOne(['position_id' => $car_info['car_position_id'],'village_id' => $car_info['village_id']],['position_status'=>1]);
            }
            if (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D7'&&!empty($house_village_park_config['d7_park_id'])&&!empty($car_info['vehicleId'])){
                $d7_data=[];
                $d7_data['park_id']=$house_village_park_config['d7_park_id'];
                $d7_data['operate']=3;
                $d7_data['vehicleId']=$car_info['vehicleId'];
                $d7_data['vehicleNo']=$car_info['province'].$car_info['car_number'];
                (new QinLinCloudService())->whiteList($d7_data);

            }
            elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D3') {
                //同步删除设备上的白名单
                $white_record = [
                    'village_id' => $data['village_id'],
                    'car_number' => $car_info['province'] . $car_info['car_number']
                ];
                (new HouseVillageParkingService())->delWhitelist($white_record);
            }elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D6') {

                //D6智慧停车下发删除命令
                (new D6Service())->d6synVehicle($data['village_id'], $data['car_id'], 1);
            }elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='A11'){
                (new A11Service())->a11DelBindVehicle($data['village_id'],$car_info);
            }else {
                $car_info['del_this_car']=1;
                $param=array('car_id'=>$data['car_id'],'car_info'=>$car_info,'village_id'=>$village_id);
                $carToDevice=invoke_cms_model('House_village_parking_car/carToDevice',$param);
            }
        }
        return $res;
    }
    public function delCarAll($data){
        $res=0;
        if(!$data['car_id']){
            return $res;
        }
        $data['car_id'] = is_array($data['car_id']) ? $data['car_id'] : [$data['car_id']];
        $db_house_village_bind_car=new HouseVillageBindCar();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_bind_position=new HouseVillageBindPosition();
        $db_house_village_park_config=new HouseVillageParkConfig();
        foreach ($data['car_id'] as $car_id){
            $bind_info=$db_house_village_bind_car->getFind(['car_id'=>$car_id]);
            $car_info=$db_house_village_parking_car->getFind(['car_id'=>$car_id]);
            $house_village_park_config=$db_house_village_park_config->getFind(['village_id' => $car_info['village_id']]);
            if (empty($car_info) || $car_info->isEmpty()){
                continue;
            }
            $car_info=$car_info->toArray();
            $village_id=0;
            if(isset($data['village_id']) && $data['village_id']){
                $village_id=$data['village_id'];
            }elseif($car_info['village_id']){
                $village_id=$car_info['village_id'];
            }
            $bind_position_ids=array();
            if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                $bind_position_ids=array_map('intval', $bind_position_ids);
            }
            if($car_info && $car_info['car_position_id']){
                $bind_position_ids[]=intval($car_info['car_position_id']);
            }
            if($bind_position_ids){
                $car_number=$car_info['province'].$car_info['car_number'];
                $bind_position_ids = array_unique($bind_position_ids);
                $whereArr=array(['position_id','in',$bind_position_ids]);
                $whereArr[]=array('use_car_number','=',$car_number);
                $parking_position_obj=$db_house_village_parking_position->getList($whereArr,'position_id',0);
                if($parking_position_obj && !$parking_position_obj->isEmpty()){
                    $parking_positions=$parking_position_obj->toArray();
                    $clear_parking_positions=array();
                    foreach ($parking_positions as $pvv){
                        $clear_parking_positions[]=$pvv['position_id'];
                    }
                    $whereArr=array(['position_id','in',$clear_parking_positions]);
                    $saveCarr=array('use_start_time'=>0,'use_car_number'=>'','use_car_id'=>0);
                    $db_house_village_parking_position->saveOne($whereArr,$saveCarr);
                }
            }
            if (!empty($bind_info)){
                $res1= $db_house_village_bind_car->delOne(['car_id'=>$car_id]);
                if ($res1>0){
                    $res= $db_house_village_parking_car->delHouseVillageParkingCar(['car_id'=>$car_id]);
                }
            }else{
                $res= $db_house_village_parking_car->delHouseVillageParkingCar(['car_id'=>$car_id]);
            }
            if ($res){
                if($car_info['equipment_no'] && $car_info['add_cfrom']=='third_ai_park'){
                    $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                    $car_number_tmp=$car_info['province'] . $car_info['car_number'];
                    $houseNewAiParkingService->delFailMonthCar($car_info['equipment_no'],$car_number_tmp,$village_id);
                }
                $bind_position=$db_house_village_bind_position->getOne(['position_id' => $car_info['car_position_id'],'village_id' => $car_info['village_id']]);
                if (!empty($car_info['car_position_id'])&&empty($bind_position)){
                    $db_house_village_parking_position->saveOne(['position_id' => $car_info['car_position_id'],'village_id' => $car_info['village_id']],['position_status'=>1]);
                }
                if (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D7'&&!empty($house_village_park_config['d7_park_id'])&&!empty($car_info['vehicleId'])){
                    $d7_data=[];
                    $d7_data['park_id']=$house_village_park_config['d7_park_id'];
                    $d7_data['operate']=3;
                    $d7_data['vehicleId']=$car_info['vehicleId'];
                    $d7_data['vehicleNo']=$car_info['province'].$car_info['car_number'];
                    (new QinLinCloudService())->whiteList($d7_data);

                }
                elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D3') {
                    //同步删除设备上的白名单
                    $white_record = [
                        'village_id' => $data['village_id'],
                        'car_number' => $car_info['province'] . $car_info['car_number']
                    ];
                    (new HouseVillageParkingService())->delWhitelist($white_record);
                }elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='D6') {

                    //D6智慧停车下发删除命令
                    (new D6Service())->d6synVehicle($data['village_id'], $car_id, 1);
                }elseif (!empty($house_village_park_config)&&$house_village_park_config['park_sys_type']=='A11'){
                    (new A11Service())->a11DelBindVehicle($data['village_id'],$car_info);
                }else {
                    $car_info['del_this_car']=1;
                    $param=array('car_id'=>$car_id,'car_info'=>$car_info,'village_id'=>$village_id);
                    $carToDevice=invoke_cms_model('House_village_parking_car/carToDevice',$param);
                }
            }
        }
        return $res;
    }
    public function getCarBindPositionList($data=array()){
        $returnArr=[];
        $returnArr['list'] = array();
        $returnArr['total_limit'] = 20;
        $returnArr['count'] = 0;
        if(!isset($data['village_id']) || empty($data['village_id'])){
            return $returnArr;
        }
        $houseVillageParkingCarBindPositionDb= new HouseVillageParkingCarBindPosition();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $garage_id_arr='';
        if(isset($data['garage_id']) && !empty($data['garage_id'])){
            $garage_id_arr=explode(',',$data['garage_id']);
        }
        $xtype='';
        if(isset($data['xtype']) && !empty($data['xtype'])){
            $xtype=$data['xtype'];
        }   
        $nowtime=time();
        if($xtype!='position'&& isset($data['position_id']) && !empty($data['position_id'])){
            $whereArr=array();
            $whereArr[]=array('village_id','=',$data['village_id']);
            $whereArr[]=array('position_id','=',$data['position_id']);
            $whereArr[]=array('del_time','=','0');
            $whereArr[]=array('car_id','>',0);
            if($garage_id_arr){
                $whereArr[]=array('garage_id','in',$garage_id_arr);
            }
            $carBindPositionObj=$houseVillageParkingCarBindPositionDb->getListData($whereArr);
            $car_ids=array();
            $bindCars=array();
            if($carBindPositionObj && !$carBindPositionObj->isEmpty()){
                $carBindPosition=$carBindPositionObj->toArray();
                foreach ($carBindPosition as $cpv){
                    $car_ids[]=$cpv['car_id'];
                    $bindCars[$cpv['car_id']]=$cpv;
                }
            }
            $whereArr=array();
            $whereArr[]=array('village_id','=',$data['village_id']);
            $whereRaw='(car_position_id ='.$data['position_id'] .' OR FIND_IN_SET('.$data['position_id'].',bind_position_ids))';
            $car_list_obj=$db_house_village_parking_car->getNewList($whereArr,'*',0,20,'car_id DESC',$whereRaw);
            if($car_list_obj && !$car_list_obj->isEmpty()){
                $car_list=$car_list_obj->toArray();
                foreach ($car_list as $kk=>$vv){
                    $car_list[$kk]['bind_position_time_str']='';
                    if(isset($bindCars[$vv['car_id']]) && $bindCars[$vv['car_id']]['bind_position_time']){
                        $car_list[$kk]['bind_position_time_str']=date('Y-m-d H:i:s',$bindCars[$vv['car_id']]['bind_position_time']);
                    }
                    if( isset($vv['car_user_phone']) && !empty($vv['car_user_phone'])){
                        $car_list[$kk]['car_user_phone']=$houseVillageDb->phone_desensitization_v($vv['car_user_phone'],$data['village_id'],$village_info);
                        //$car_list[$kk]['car_user_phone']=phone_desensitization($vv['car_user_phone']);
                    }
                }
                $returnArr['list']=$car_list;
                $returnArr['count']=count($car_list);
            }
            
            return $returnArr;
        }else if($xtype=='position'&& isset($data['car_id']) && !empty($data['car_id'])){
            $db_house_village_parking_position=new HouseVillageParkingPosition();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$data['village_id']);
            $whereArr[]=array('car_id','=',$data['car_id']);
            $car_obj=$db_house_village_parking_car->getOne($whereArr);
            if($car_obj && !$car_obj->isEmpty()){
                $car=$car_obj->toArray();
                $bind_position_ids=array();
                if(isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])){
                    $bind_position_ids=explode(',',$car['bind_position_ids']);
                    $bind_position_ids=array_map('intval', $bind_position_ids);
                }
                if($car['car_position_id']){
                    $bind_position_ids[]=intval($car['car_position_id']);
                }
                if($bind_position_ids){
                    $bind_position_ids=array_unique($bind_position_ids);
                    $whereArr=array();
                    $whereArr[]=array('village_id','=',$data['village_id']);
                    $whereArr[]=array('car_id','=',$data['car_id']);
                    $whereArr[]=array('del_time','=','0');
                    $whereArr[]=array('position_id','>',0);
                    if($garage_id_arr){
                        $whereArr[]=array('garage_id','in',$garage_id_arr);
                    }
                    $carBindPositionObj=$houseVillageParkingCarBindPositionDb->getListData($whereArr);
                    $bindPositions=array();
                    if($carBindPositionObj && !$carBindPositionObj->isEmpty()){
                        $carBindPosition=$carBindPositionObj->toArray();
                        foreach ($carBindPosition as $cpv){
                            $bindPositions[$cpv['position_id']]=$cpv;
                        }
                    }
                    $bindPositionList=array();
                    foreach ($bind_position_ids as $position_id){
                        $tmp_position=array('position_id'=>$position_id);
                        $tmp_position['bind_position_time_str']='';
                        if(isset($bindPositions[$position_id]) && $bindPositions[$position_id]['bind_position_time']){
                            $tmp_position['bind_position_time_str']=date('Y-m-d H:i:s',$bindPositions[$position_id]['bind_position_time']);
                        }
                        $tmp_position['position_num']='';
                        $tmp_position['garage_id']=0;
                        $tmp_position['garage_num']='';
                        $tmp_position['name']='';
                        $tmp_position['phone']='';
                        $tmp_position['end_time_str']='';
                        $whereArr=array('pp.position_id'=>$position_id);
                        $whereArr['pp.village_id']=$data['village_id'];
                        $parking_position=$db_house_village_parking_position->getOne($whereArr,'pp.position_num,pp.start_time,pp.end_time,pp.garage_id,pg.garage_num,ub.name,ub.phone');
                        if($parking_position && !$parking_position->isEmpty()){
                            $tmp_position['position_num']=$parking_position['position_num'];
                            $tmp_position['garage_id']=$parking_position['garage_id'];
                            $tmp_position['garage_num']=$parking_position['garage_num'] ? $parking_position['garage_num']:'';
                            $tmp_position['name']=!empty($parking_position['name']) ? $parking_position['name']:'';
                            $tmp_position['phone']=!empty($parking_position['phone']) ? $parking_position['phone']:'';
                            $tmp_position['phone']=$houseVillageDb->phone_desensitization_v($tmp_position['phone'],$data['village_id'],$village_info);
                            $end_time_str='';
                            if($parking_position['start_time']>$nowtime){
                                $end_time_str=date('Y-m-d',$parking_position['start_time']);
                            }
                            if($parking_position['end_time']>100){
                                $tmp_end_time= date('Y-m-d',$parking_position['end_time']);
                                $end_time_str=$end_time_str ? $end_time_str.' 至 '.$tmp_end_time:$tmp_end_time;
                            }
                            $tmp_position['end_time_str']=$end_time_str;
                        }
                        $bindPositionList[]=$tmp_position;
                    }
                    $returnArr['list']=$bindPositionList;
                    $returnArr['count']=count($bindPositionList);
                }
            }
        }

        return $returnArr;
    }

    public function getCarlist($data,$ishidephone=false){
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_house_village_bind_car=new HouseVillageBindCar();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $where=[];
        $is_no_query=false;
        $whereRaw='';
        if (!empty($data['search_type'])&&!empty($data['search_value'])){
            if ($data['search_type']==4){
                $where_parent=[];
                $where_parent['village_id']=$data['village_id'];
                $where_parent['parent_position_id']=$data['search_value'];
                $parent_position_arr=$db_house_village_parking_position->getColumn($where_parent,'position_id');
                $parent_position_arr[]=$data['search_value'];
            }
            switch ($data['search_type']) {
                case '1'://车牌号
                    $car_number_1 = preg_replace("/[\x{4e00}-\x{9fa5}]/iu", "", trim($data['search_value'])); //这样是去掉汉字
                    $where[] =['a.car_number','like','%' . $car_number_1 . '%'] ;
                    break;
                case '2': //使用状态
                    //todo 待修改
                    $where[] =['a.car_stop_num','like', '%' . $data['search_value'] . '%'] ;
                    break;
                case '3'://车位号
                    $where_position=[];
                    $where_position['village_id']=$data['village_id'];
                    $where_position['position_num']=$data['search_value'];
                    $parking_position_arr=$db_house_village_parking_position->getColumn($where_position,'position_id');;
                    if($parking_position_arr){
                        $parking_position_str=implode(',',$parking_position_arr);
                        $whereRaw='(a.car_position_id in ('.$parking_position_str .') OR FIND_IN_SET('.$parking_position_arr['0'].',bind_position_ids))';
                    }else{
                        $is_no_query=true;
                    }
                    break;
                case '4'://车位号
                    $parent_position_str=implode(',',$parent_position_arr);
                    $whereRaw='(a.car_position_id in ('.$parent_position_str .') OR FIND_IN_SET('.$data['search_value'].',bind_position_ids))';
                    //$where[] =['a.car_position_id','in',  $parent_position_arr] ;
                    break;
                case '5': //车主姓名
                    $where[] =['a.car_user_name','like', '%' . $data['search_value'] . '%'] ;
                    break;
                case '6'://车主手机号
                    $where[] =['a.car_user_phone','=',  $data['search_value']] ;
                    break;
            }
        }
        if(isset($data['record_date']) && !empty($data['record_date']) && is_array($data['record_date'])){
            if (!empty($data['record_date'][0])){
                $where[] = ['a.car_addtime','>=',strtotime($data['record_date'][0].' 00:00:00')];
            }
            if (!empty($data['record_date'][1])){
                $where[] = ['a.car_addtime','<=',strtotime($data['record_date'][1].' 23:59:59')];
            }
        }
        if(isset($data['pass_date']) && !empty($data['pass_date']) && is_array($data['pass_date'])){
            if (!empty($data['pass_date'][0])){
                $where[] = ['a.examine_time','>=',strtotime($data['pass_date'][0].' 00:00:00')];
            }
            if (!empty($data['pass_date'][1])){
                $where[] = ['a.examine_time','<=',strtotime($data['pass_date'][1].' 23:59:59')];
            }
        }
        $checkType = isset($data['checkType']) ? trim($data['checkType']) : '';
        if ($checkType == 'check') {
            $where[] =['a.examine_status','=',0] ;
        }
        if(isset($data['position_id']) && $data['position_id']>0){
            $whereRaw='(a.car_position_id ='.$data['position_id'] .' OR FIND_IN_SET('.$data['position_id'].',bind_position_ids))';
        }
        $where[] =['a.village_id','=',$data['village_id']] ;
        $field = 'a.*';
        $order = 'a.car_id DESC,a.car_addtime DESC ';
        $info_list=array();
        if(!$is_no_query){
            $info_list=$db_house_village_parking_car->getNewList($where,$field,$data['page'],$data['limit'],$order,$whereRaw);
            if (!empty($info_list)){
                $info_list=$info_list->toArray();
            }
        }
        $park_sys_type_stored=array('A11','a11','A1','a1','D7','d7','D3','d3');
        $is_car_stored_func=0;
        $house_village_park_config=array();
        $nowtime=time();
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        if (!empty($info_list)) {
            foreach ($info_list as $k => &$v) {
                
                $bind_position_ids=array();
                if(isset($v['bind_position_ids']) && !empty($v['bind_position_ids'])){
                    $bind_position_ids=explode(',',$v['bind_position_ids']);
                    $bind_position_ids=array_map('intval', $bind_position_ids);
                }
                if($v['car_position_id']){
                    $bind_position_ids[]=intval($v['car_position_id']);
                }
                $v['position_num']='';
                $end_time=$v['end_time'];
                $start_time=$v['start_time'];
                if($bind_position_ids){
                    $bind_position_ids=array_unique($bind_position_ids);
                    $where_position=array(['village_id','=',$data['village_id']]);
                    $where_position[]=['position_id','in',$bind_position_ids];
                    //$position_name_arr=$db_house_village_parking_position->getColumn($where_position,'position_num');
                    $position_name_arr=array();
                    $position_list=$db_house_village_parking_position->getList($where_position,'position_id,start_time,position_num,end_time');
                    if($position_list && !$position_list->isEmpty()){
                        $position_list=$position_list->toArray();
                        foreach ($position_list as $ppv){
                            $position_name_arr[]=$ppv['position_num'];
                            if($v['car_type']<1 && $end_time<$ppv['end_time']){
                                $end_time=$ppv['end_time'];
                                $start_time=$ppv['start_time'];
                                $db_house_village_parking_car->editCar(array('car_id'=>$v['car_id']),array('end_time'=>$end_time));
                            }elseif ($ppv['end_time']<1 && $end_time>100){
                                //$db_house_village_parking_position->saveOne(array('position_id'=>$ppv['position_id'],'village_id'=>$data['village_id']),array('end_time'=>$end_time));
                            }
                        }
                        
                    }
                    if($position_name_arr){
                        $v['position_num']=implode('，',$position_name_arr);
                    }
                }
                $v['car_type_str']=$v['car_type']<1 ? '汽车':'电瓶车';
                $bind_car =$db_house_village_bind_car->getFind(['car_id' => $v['car_id'], 'village_id' => $v['village_id']]);
                if (!empty($bind_car)) {
                    $v['bind_car_status'] = 1;
                    $v['bind_car_s'] = 1;
                    $v['relationship']=$bind_car['relationship']>0?$this->relationship[$bind_car['relationship']]:'--';
                } else {
                    $v['bind_car_status'] = 2;
                    $v['bind_car_s'] = 0;
                    $v['relationship']='--';
                }
                $v['bind_position_time_str']='--';
                if(isset($v['bind_position_time']) && $v['bind_position_time']>100){
                    $v['bind_position_time_str']=date('Y-m-d H:i:s',$v['bind_position_time']);
                }
                $garage_id=isset($v['garage_id']) ? $v['garage_id']:0;
                $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
                if(in_array($house_village_park_config['park_sys_type'],$park_sys_type_stored) && ($house_village_park_config['is_temporary_park_type']==1)){
                    //有储值功能，储值功能开启了
                    $v['stored_balance']= $v['stored_balance']>0 ? $v['stored_balance']:'--';
                    $is_car_stored_func=1;
                }else{
                    $v['stored_balance']='--';
                }
                if (!empty($v['parking_car_type'])&&$house_village_park_config['park_sys_type']=='A11'&&$v['parking_car_type']<=21){
                    $v['parking_car_type']=$this->parking_a11_car_type_arr[$v['parking_car_type']];
                }elseif(!empty($v['parking_car_type'])&&($house_village_park_config['park_sys_type']=='D3'||$house_village_park_config['park_sys_type']=='HXT85')){
                    $v['parking_car_type']=isset($this->parking_d3_car_type_arr[$v['parking_car_type']]) ? $this->parking_d3_car_type_arr[$v['parking_car_type']]:'--';
                }else{
                    $parking_car_type_arr =$this->parking_car_type_arr;
                    $v['parking_car_type']=isset($parking_car_type_arr[$v['parking_car_type']]) ? $parking_car_type_arr[$v['parking_car_type']]: '--';
                }
                $v['car_number']=$v['province'].$v['car_number'];
                $v['car_addtime']=date('Y-m-d H:i:s',$v['car_addtime']);
                $v['examine_time']=$v['examine_time']<=1?'--':date('Y-m-d H:i:s',$v['examine_time']);
                $v['examine_status']=$v['examine_status']==1?'已审核':($v['examine_status']==2?'拒绝':'待审核');
                
                $v['end_time'] = $end_time>1 ? date('Y-m-d', $end_time) : '--';
                if($start_time && $start_time>$nowtime && $end_time>$nowtime){
                    $v['end_time']=date('Y-m-d', $start_time).' 至 '.$v['end_time'];
                }
                if($ishidephone && isset($v['car_user_phone']) && !empty($v['car_user_phone'])){
                    $v['car_user_phone']=$houseVillageDb->phone_desensitization_v($v['car_user_phone'],$data['village_id'],$village_info);
                }
            }
        }
        $whereArr=array();
        $whereArr[]=array('village_id','=',$data['village_id']);
        $whereArr[]=array('park_versions','=',2);
        $garageConfigArr=$houseVillageParkGarageConfigService->getListData($whereArr);
        $park_sys_type_arr=array();
        if($garageConfigArr){
            foreach ($garageConfigArr as $gvv){
                if($gvv['park_sys_type']){
                    $park_sys_type_arr[]=ucfirst($gvv['park_sys_type']);
                }
            }
            $park_sys_type_arr=array_unique($park_sys_type_arr);
        }
        $configCustomizationService = new ConfigCustomizationService();
        $isAihorseCustomizedJudge = $configCustomizationService->getAihorseCustomizedJudge();
        $db_house_village_ParkIng_garage=new HouseVillageParkingGarage();
        if ($isAihorseCustomizedJudge) {
            $garage_info = $db_house_village_ParkIng_garage->getOne(array(['village_id', '=', $data['village_id']], ['status', '=', 1], ['third_park_key', '<>', '']));
            if ($garage_info && isset($garage_info['third_park_key']) && !empty($garage_info['third_park_key'])) {
                $park_sys_type_arr[] = 'third_ai_park';
            }
        }
        $count=0;
        if(!$is_no_query) {
            $count = $db_house_village_parking_car->getNewCount($where,$whereRaw);
        }
        $data1=[];
        $data1['list'] = $info_list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['village_id'] = $data['village_id'];
        $data1['park_sys_type'] = $park_sys_type_arr ? array_values($park_sys_type_arr):array();
        $data1['is_car_stored_func']=$is_car_stored_func;
        return $data1;
    }

    public function addPassage($data){
        $db_park_passage=new ParkPassage();
        $village_park_config = $this->getParkConfigInfo(['village_id'=>$data['village_id']],$data['garage_id']);
        if($village_park_config['park_sys_type']!='D5'){
            if(empty($data['channel_number'])){
                throw new \think\Exception("通道编号不能为空");
            }
        }
        if (in_array($village_park_config['park_sys_type'],array('D3','A11','HXT85'))){
            $where_http=[
                ['village_id','=',$data['village_id']],
                ['device_number','=',$data['device_number']],
                ['park_sys_type','=',$village_park_config['park_sys_type']],
            ];
            $passage_info=$db_park_passage->getFind($where_http);
            if (!empty($passage_info)){
                throw new \think\Exception("设备编号不能重复");
            }
            $data1['passage_type']=$data['passage_type'];
        }
        if ($village_park_config['park_sys_type']=='D7'){
            if(empty($data['d7_channelId'])){
                throw new \think\Exception("业务编号不能为空");
            }
            $passage_info_d7=$db_park_passage->getFind(['d7_channelId'=>$data['d7_channelId']]);
            if (!empty($passage_info_d7)){
                throw new \think\Exception("业务编号已存在");
            }
            $data1['d7_channelId'] =  $data['d7_channelId'];
        }
        $where11=[
            ['village_id','=',$data['village_id']],
            ['channel_number','=',$data['channel_number']],
        ];
        $passage_tmp=$db_park_passage->getFind($where11);
        if (!empty($passage_tmp) && !$passage_tmp->isEmpty()){
            throw new \think\Exception("通道号不能重复");
        }
        if($data['device_number']){
            $where13=[
                ['device_number','=',$data['device_number']]
            ];
            $passage_tmp=$db_park_passage->getFind($where13);
            if (!empty($passage_tmp) && !$passage_tmp->isEmpty()){
                throw new \think\Exception("设备编号有重复，此设备在其他小区已添加了！");
            }
        }
        $data1['passage_name'] =  $data['passage_name'];
        $data1['channel_number'] = $data['channel_number'];
        $data1['device_number'] = $data['device_number'];
        $data1['long']=0;
        $data1['lat']=0;
        if (!empty($data['long_lat'])){
            $long_lat = explode(',', $data['long_lat']);
            $data1['long'] = $long_lat[0];
            $data1['lat'] = $long_lat[1];
        }
        $data1['status'] = $data['status'];
        $data1['passage_direction'] = $data['passage_direction'];
        $data1['passage_label'] = $data['passage_label'];
        $data1['passage_area'] =$data['passage_area'];
        $data1['area_type'] = $data['area_type'];
        $data1['village_id'] =  $data['village_id'];
        $data1['device_type'] =  $data['device_type'];
        $data1['park_sys_type'] = $village_park_config['park_sys_type'];
        $data1['garage_id'] =  $data['garage_id'];
        if(isset($data['device_protocol']) && in_array($village_park_config['park_sys_type'],['D3','A11'])){
            $data1['device_protocol'] =$data['device_protocol'];
        }else{
            $data1['device_protocol']=0;
        }
        $data1['device_ip'] =  $data['device_ip'];
        $data1['device_pwd'] =  $data['device_pwd'];
        $data1['device_account'] =  $data['device_account'];

        if($this->hasCloudIntercom()){
            /*
                $data1['device_port'] =  $data['device_port'];
                if($data['device_port']){
                    $where13=[
                        ['device_port','=',$data['device_port']],
                    ];
                    $passage_tmp=$db_park_passage->getFind($where13);
                    if($passage_tmp && !$passage_tmp->isEmpty()){
                        throw new \think\Exception("此端口号已被设备编号:".$passage_tmp['device_number']."的车道占用了！");
                    }
                }
                */
            /*
            if(isset($data['camera_channel_id'])){
                $data1['camera_channel_id']=$data['camera_channel_id'] ? $data['camera_channel_id']:0;
            }
            */
            if(isset($data['public_v_url'])){
                $data1['public_v_url']=$data['public_v_url'] ? $data['public_v_url']:'';
            }
        }
        $deviceSetting = empty($data['deviceSetting']) ? [] : $data['deviceSetting'];
        $data1['device_setting'] = json_encode($deviceSetting,JSON_UNESCAPED_UNICODE);
        (new ParkPassageService())->checkDeviceSetting($deviceSetting);
        $res = $db_park_passage->addOne($data1);
        if(in_array($data1['park_sys_type'],['D3' , 'A11'])){
            (new ParkPassageService())->syncDeviceVolume($res);
        }
        if ($village_park_config['park_sys_type']=='A11'){
            if ($data['passage_type']==2&&!empty($data['passage_relation'])){
                $db_park_passage_type=new ParkPassageType();
                $type_info=$db_park_passage_type->getFind(['passage_id|passage_relation'=>$data['passage_relation'],'village_id'=>$data['village_id'],'park_sys_type'=>'A11']);
                if (empty($type_info)){
                    $data11=[];
                    $data11['village_id']=$data['village_id'];
                    $data11['passage_name']=$data['passage_name'];
                    $data11['passage_type']=$data['passage_type'];
                    $data11['passage_id']=$res;
                    $data11['passage_relation']=$data['passage_relation'];
                    $data11['passage_direction']=$data['passage_direction'];
                    $data11['park_sys_type']=$village_park_config['park_sys_type'];
                    $data11['add_time']=time();
                    $data11['status']=1;
                    $db_park_passage_type->addOne($data11);
                }
            }
        }
        return $res;
    }

    public function editPassage($data){
        $db_park_passage=new ParkPassage();
        $village_park_config = $this->getParkConfigInfo(['village_id'=>$data['village_id']],$data['garage_id']);
        if($village_park_config['park_sys_type']!='D5'){
            if(empty($data['channel_number'])){
                throw new \think\Exception("通道编号不能为空");
            }
        }
        if ($village_park_config['park_sys_type']=='D7'){
            if(empty($data['d7_channelId'])){
                throw new \think\Exception("业务编号不能为空");
            }
            $passage_info_d7=$db_park_passage->getFind(['d7_channelId'=>$data['d7_channelId']]);
            if (!empty($passage_info_d7)&&$passage_info_d7['id']!=$data['id']){
                throw new \think\Exception("业务编号已存在");
            }
            $data1['d7_channelId'] =  $data['d7_channelId'];
        }
        if ($village_park_config['park_sys_type']=='A11'){
            $data1['passage_type']=$data['passage_type'];
        }
        $where11=[
            ['village_id','=',$data['village_id']],
            ['channel_number','=',$data['channel_number']],
            ['id','<>',$data['id']]
        ];
        $passage_tmp=$db_park_passage->getFind($where11);
        if (!empty($passage_tmp) && !$passage_tmp->isEmpty()){
            throw new \think\Exception("通道号不能重复");
        }
        if($data['device_number']){
            $where13=[
                ['device_number','=',$data['device_number']],
                ['id','<>',$data['id']]
            ];
            $passage_tmp=$db_park_passage->getFind($where13);
            if (!empty($passage_tmp) && !$passage_tmp->isEmpty()){
                throw new \think\Exception("设备编号有重复，此设备在其他小区已添加了！");
            }
        }
        $whereArr=[
            ['village_id','=',$data['village_id']],
            ['id','=',$data['id']]
        ];
        $passage_info = $db_park_passage->getFind($whereArr,'*');
        if($passage_info && !$passage_info->isEmpty()){
            $passage_info=$passage_info->toArray();
        }

        $data1['passage_name'] =  $data['passage_name'];
        $data1['channel_number'] = $data['channel_number'];
        $data1['device_number'] = $data['device_number'];
        $data1['long']=0;
        $data1['lat']=0;
        if (!empty($data['long_lat'])){
            $long_lat = explode(',', $data['long_lat']);
            $data1['long'] = $long_lat[0];
            $data1['lat'] = $long_lat[1];
        }
        $data1['status'] = $data['status'];
        $data1['passage_direction'] = $data['passage_direction'];
        $data1['passage_label'] = $data['passage_label'];
        $data1['passage_area'] =$data['passage_area'];
        $data1['area_type'] = $data['area_type'];
        $data1['park_sys_type'] = $village_park_config['park_sys_type'];
        $data1['device_type'] =  $data['device_type'];
        $data1['mac_address'] =  $data['mac_address'];
        $deviceSetting = empty($data['deviceSetting']) ? [] : $data['deviceSetting'];
        $data1['device_setting'] = json_encode($deviceSetting,JSON_UNESCAPED_UNICODE);
        $data1['garage_id'] =$data['garage_id'];
        if(isset($data['device_protocol']) && $data['device_protocol']>0 && in_array($village_park_config['park_sys_type'],['D3','A11'])){
            $data1['device_protocol'] =$data['device_protocol'];
            $data1['opt_protocol_type'] =0;
        }else{
            $data1['device_protocol']=0;
            if($passage_info && $passage_info['device_protocol']>0){
                $data1['opt_protocol_type'] =2; //1mqtt协议已订阅2协议设置取消3mqtt订阅取消
            }
        }

        $data1['device_ip'] = $data['device_ip'];
        $data1['device_pwd'] = $data['device_pwd'];
        $data1['device_account'] = $data['device_account'];

        if($this->hasCloudIntercom()){
            /*
             $data1['device_port'] = $data['device_port'];
            if ($data['device_port']) {
                $where13 = [
                    ['device_port', '=', $data['device_port']],
                    ['id', '<>', $data['id']]
                ];
                $passage_tmp = $db_park_passage->getFind($where13);
                if ($passage_tmp && !$passage_tmp->isEmpty()) {
                    throw new \think\Exception("此端口号已被设备编号:" . $passage_tmp['device_number'] . "的车道占用了");
                }
            }
            */
            /*
            if(isset($data['camera_channel_id'])){
                $data1['camera_channel_id']=$data['camera_channel_id'] ? $data['camera_channel_id']:0;
            }
            */
            if(isset($data['public_v_url'])){
                $data1['public_v_url']=$data['public_v_url'] ? $data['public_v_url']:'';
            }
        }
        (new ParkPassageService())->checkDeviceSetting($deviceSetting);
        $res = $db_park_passage->save_one(['id'=>$data['id']],$data1);
        if ($res) {
            (new CarHandleGetInfoService())->clearPassInfo($data['village_id']);
        }
        if ($res&&$village_park_config['park_sys_type']=='D3'){
            cache('park:'.$data['device_number'].':serialno',null);
        }
        if(in_array($data1['park_sys_type'],['D3' , 'A11'])){
            (new ParkPassageService())->syncDeviceVolume($data['id']);
        }
        if ($res>0&&$village_park_config['park_sys_type']=='A11'){
            if ($data['passage_type']==2&&!empty($data['passage_relation'])){
                $db_park_passage_type=new ParkPassageType();
                $type_info=$db_park_passage_type->getFind(['passage_id|passage_relation'=>$data['id'],'village_id'=>$data['village_id'],'park_sys_type'=>'A11']);
                if (empty($type_info)){
                    $type_info1=$db_park_passage_type->getFind(['passage_id|passage_relation'=>$data['passage_relation'],'village_id'=>$data['village_id'],'park_sys_type'=>'A11']);
                    if (empty($type_info1)){
                        $data11=[];
                        $data11['village_id']=$data['village_id'];
                        $data11['passage_name']=$data['passage_name'];
                        $data11['passage_type']=$data['passage_type'];
                        $data11['passage_id']=$data['id'];
                        $data11['passage_relation']=$data['passage_relation'];
                        $data11['passage_direction']=$data['passage_direction'];
                        $data11['park_sys_type']=$village_park_config['park_sys_type'];
                        $data11['add_time']=time();
                        $data11['status']=1;
                        $db_park_passage_type->addOne($data11); 
                    }
                    
                }else{
                    $type_info1=$db_park_passage_type->getFind(['passage_id|passage_relation'=>$data['passage_relation'],'village_id'=>$data['village_id'],'park_sys_type'=>'A11']);
                    if (empty($type_info1)){
                        $data11=[];
                        $data11['passage_name']=$data['passage_name'];
                        $data11['passage_relation']=$data['passage_relation'];
                        $data11['passage_direction']=$data['passage_direction'];
                        $data11['add_time']=time();
                        $db_park_passage_type->save_one(['passage_id'=>$data['id'],'village_id'=>$data['village_id'],'park_sys_type'=>'A11'],$data11);
                    }
                 }
            }
            cache('park:'.$data['device_number'].':serialno',null);
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '车道管理-编辑',
                'table'  => 'park_passage',
                'client' => '小区后台',
                'trigger_path' => '停车管理->车道管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => [],
        ];
        $this->laterLogInQueue($queuData);
        return $res;
    }


    public function delPassage($passage_id){
        $db_park_passage=new ParkPassage();
        $passage_info=$db_park_passage->getFind(['id'=>$passage_id]);
        if (empty($passage_info)){
            throw new \think\Exception("通道信息不存在");
        }
        $res = $db_park_passage->del_one(['id'=>$passage_id]);
        if ($res&&$passage_info['park_sys_type']=='D3'){
            cache('park:'.$passage_info['device_number'].':serialno',null);
        }
        (new CarHandleGetInfoService())->clearPassInfo($passage_info['village_id']);
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '车道管理-删除',
                'table'  => 'park_passage',
                'client' => '小区后台',
                'trigger_path' => '停车管理->车道管理',
                'trigger_type' => $this->getDeleteName(),
                'addtime'      => time(),
                'village_id'   => $passage_info['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => ['id'=>$passage_id],
            'oldData' => [],
        ];
        $this->laterLogInQueue($queuData);
        return $res;
    }

    /**
     * 添加标签分组
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:28
     */
    public function addLabelCat($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $cat_info=$db_house_village_label_cat->get_one(['cat_name'=>$data['cat_name'],'village_id'=>$data['village_id'],'status'=>[1,2]]);
        if (!empty($cat_info)){
            throw new \think\Exception("标签分组名称重复，无法添加");
        }
        $data_label=[];
        $data_label['cat_function']=$data['cat_function'];
        $data_label['village_id']=$data['village_id'];
        $data_label['cat_name']=$data['cat_name'];
        $data_label['status']=1;
        $id=$db_house_village_label_cat->addOne($data_label);
        return $id;
    }

    /**
     * 编辑标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:11
     */
    public function editLabelCat($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $where=[];
        $where=[
            ['cat_name','=',$data['cat_name']],
            ['village_id','=',$data['village_id']],
            ['status','in',[1,2]],
            ['cat_id','<>',$data['cat_id']],
        ];
        $cat_info=$db_house_village_label_cat->get_one($where);
        if (!empty($cat_info)){
            throw new \think\Exception("标签分组名称重复，无法添加");
        }
        $data_label=[];
        $data_label['cat_function']=$data['cat_function'];
        $data_label['cat_name']=$data['cat_name'];
        $id=$db_house_village_label_cat->save_one(['cat_id'=>$data['cat_id']],$data_label);
        return $id;
    }

    /**
     * 编辑标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:11
     */
    public function getLabelCatInfo($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $where=[];
        $where=[
            ['village_id','=',$data['village_id']],
            ['status','in',[1,2]],
            ['cat_id','=',$data['cat_id']],
        ];
        $cat_info=$db_house_village_label_cat->get_one($where);
        return $cat_info;
    }

    /**
     * 删除标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:11
     */
    public function delLabelCat($cat_id){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $res=$db_house_village_label_cat->save_one(['cat_id'=>$cat_id],['status'=>4]);
        return $res;
    }

    /**
     * 添加标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:11
     */
    public function addLabel($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $db_house_village_label=new HouseVillageLabel();
        if ($data['cat_id']!=99999){
            $cat_info=$db_house_village_label_cat->get_one(['cat_id'=>$data['cat_id']]);
            if (empty($cat_info)){
                throw new \think\Exception("标签分组信息不存在");
            }
        }
        $label_info=$db_house_village_label->get_one(['label_name'=>$data['label_name'],'village_id'=>$data['village_id'],'is_delete'=>0,'cat_id'=>$data['cat_id']]);
        if (!empty($label_info)){
            throw new \think\Exception("标签名称重复，无法添加");
        }
        $data_label=[];
        $data_label['cat_id']=$data['cat_id'];
        $data_label['village_id']=$data['village_id'];
        $data_label['label_name']=$data['label_name'];
        $data_label['create_at']=time();
        $data_label['update_at']=time();
        $id=$db_house_village_label->addOne($data_label);
        return $id;
    }

    /**
     * 编辑标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:11
     */
    public function editLabel($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $db_house_village_label=new HouseVillageLabel();
        $label_info1=$db_house_village_label->get_one(['id'=>$data['label_id']]);
        if ($data['cat_id']==99999){
            $data['cat_id']=$label_info1['cat_id'];
        }

        $cat_info=$db_house_village_label_cat->get_one(['cat_id'=>$data['cat_id']]);
        if (empty($cat_info)){
            throw new \think\Exception("标签分组信息不存在");
        }
        $where=[
            ['cat_id','=',$data['cat_id']],
            ['label_name','=',$data['label_name']],
            ['village_id','=',$data['village_id']],
            ['is_delete','=',0],
            ['id','<>',$data['label_id']],
        ];
        $label_info=$db_house_village_label->get_one($where);
        if (!empty($label_info)){
            throw new \think\Exception("标签名称重复，无法添加");
        }
        $data_label=[];
        $data_label['cat_id']=$data['cat_id'];
        $data_label['label_name']=$data['label_name'];
        $data_label['create_at']=time();
        $data_label['update_at']=time();
        $id=$db_house_village_label->save_one(['id'=>$data['label_id']],$data_label);
        return $id;
    }

    /**
     * 移动标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:11
     */
    public function moveLabel($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $db_house_village_label=new HouseVillageLabel();
        if ($data['cat_id']!=99999){
            $cat_info=$db_house_village_label_cat->get_one(['cat_id'=>$data['cat_id']]);
            if (empty($cat_info)){
                throw new \think\Exception("标签分组信息不存在");
            }
        }
        $arr['count']=0;
        $arr['msg']='';
      //   print_r($data['label_id']);exit;
        foreach ($data['label_id'] as $vv){
            $label_info1=$db_house_village_label->get_one(['id'=>$vv]);
            $where=[];
            $where=[
                ['cat_id','=',$data['cat_id']],
                ['label_name','=',$label_info1['label_name']],
                ['village_id','=',$data['village_id']],
                ['is_delete','=',0],
                ['id','<>',$vv],
            ];
            $label_info=$db_house_village_label->get_one($where);
            if (!empty($label_info)){
                $arr['msg']=$arr['msg'].'标签名称【'.$label_info1['label_name'].'】重复，无法移动';
            }else{
                $data_label=[];
                $data_label['cat_id']=$data['cat_id'];
                $data_label['update_at']=time();
                $db_house_village_label->save_one(['id'=>$vv],$data_label);
                $arr['count']++;
            }

        }

        return $arr;
    }

    /**
     * 删除标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:10
     */
    public function delLabel($label_id){
        $db_house_village_label=new HouseVillageLabel();
        $res=$db_house_village_label->save_one(['id'=>$label_id],['is_delete'=>1]);
        return $res;
    }

    /**
     * 查询标签信息
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:10
     */
    public function getLabelInfo($label_id){
        $db_house_village_label=new HouseVillageLabel();
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $res=$db_house_village_label->get_one(['id'=>$label_id]);
        $res['cat_name']='未分组';
        if (!empty($res['cat_id'])){
            $cat_info=$db_house_village_label_cat->get_one(['cat_id'=>$res['cat_id']]);
            if (!empty($cat_info)){
                if ($cat_info['status']==4){
                    $res['cat_name']='未分组';
                    $res['cat_id']=99999;
                }else{
                    $res['cat_name']=$cat_info['cat_name'];
                }
            }
        }
        return $res;
    }


    /**
     * 查询标签列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:21
     */
    public function getLabelList($data){
        $db_house_village_label=new HouseVillageLabel();
        $where1=[];
        if (isset($data['cat_id']) &&  $data['cat_id']==99999){
//            $where[]=['l.village_id'=>$data['village_id']];
//            $where[]=['l.is_delete'=>0];
//            $where[]=['c.status'=>4];
//            $where1[]=['l.village_id'=>$data['village_id']];
//            $where1[]=['l.is_delete'=>0];
//            $where1[]=['l.cat_id'=>$data['cat_id']];
            $where=['l.village_id'=>$data['village_id'],'l.is_delete'=>0,'c.status'=>4];
            $where1=['l.cat_id'=>$data['cat_id']];
        }else{
            $where=[['l.village_id','=',$data['village_id']],['l.is_delete','=',0]];
            if(isset($data['cat_id']) && $data['cat_id']>0){
                $where[]=['l.cat_id','=',$data['cat_id']];
            }else{
                $where[]=['l.cat_id','>',0];
            }
        }
        $label_list=$db_house_village_label->getList($where,'l.label_name,l.id,c.cat_name',$data['page'],$data['limit'],'l.id desc',$where1);
       if (!empty($label_list)){
           $label_list=$label_list->toArray();
       }
        if (!empty($label_list)){
            if ($data['cat_id']==99999){
                foreach ($label_list as &$v){
                    $v['cat_name']='未分组';
                }
            }
        }
        $count=$db_house_village_label->getCounts($where,$where1);
        $data1=[];
        $data1['list'] = $label_list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    /**
     * 查询标签分类列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:21
     */
    public function getLabelCatList($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $cat_list=$db_house_village_label_cat->getList(['village_id'=>$data['village_id'],'status'=>[1,2]],'cat_id,cat_name');
        if (!empty($cat_list)){
            $cat_list=$cat_list->toArray();
        }
        return $cat_list;
    }
    /**
     * 查询标签分类列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:21
     */
    public function getLabelCatsList($data){
        $db_house_village_label_cat=new HouseVillageLabelCat();
        $cat_list=$db_house_village_label_cat->getList(['village_id'=>$data['village_id'],'status'=>[1,2]],'cat_id,cat_name');
        if (!empty($cat_list)){
            $cat_list=$cat_list->toArray();
        }
        if (!empty($cat_list)){
            $v['cat_id']=99999;
            $v['cat_name']='未分组';
            $cat_list[]=$v;
        }
        return $cat_list;
    }

    /**
     * 添加免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:00
     */
    public function addFreeCar($data){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_park_free=new HouseVillageParkFree();
        $data1['park_type'] = $data['park_type'];
        $data1['first_name'] = $data['first_name'];
        $data1['last_name'] = $data['last_name'];
        $data1['free_park'] = $data['free_park'];
        $data1['village_id'] = $data['village_id'];
        $data1['park_sys_type'] = '';
        $data1['addTime'] = time();
        $data1['updateTime'] = time();
        $res =$db_house_village_park_free->addOne($data1);
        if ($res) {
            cache('freeList_'.$data['village_id'], null);
        }
       return $res;
    }

    /**
     * 添加免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:00
     */
    public function editFreeCar($data){
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_park_free=new HouseVillageParkFree();
        $data1['park_type'] = $data['park_type'];
        $data1['first_name'] = $data['first_name'];
        $data1['last_name'] = $data['last_name'];
        $data1['free_park'] = $data['free_park'];
        $data1['park_sys_type'] = '';
        $data1['updateTime'] = time();
        $feeCar = $db_house_village_park_free->getFind(['id' => $data['id']]);
        $res =$db_house_village_park_free->save_one(['id'=>$data['id']],$data1);
        if (isset($feeCar['village_id']) && $feeCar['village_id']) {
            cache('freeList_'.$feeCar['village_id'], null);
        }
        return $res;
    }

    /**
     * 删除免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function delFreeCar($free_id){
        $db_house_village_park_free=new HouseVillageParkFree();
        $feeCar = $db_house_village_park_free->getFind(['id' => $free_id]);
        $res = $db_house_village_park_free->del_one(['id' => $free_id]);
        if (isset($feeCar['village_id']) && $feeCar['village_id']) {
            cache('freeList_'.$feeCar['village_id'], null);
        }
        return $res;
    }


    /**
     * 删除免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function getFreeCarInfo($data){
        $db_house_village_park_free=new HouseVillageParkFree();
        $res=$db_house_village_park_free->getFind(['id'=>$data['free_id']]);
        return $res;
    }

    /**
     * 查询免费车列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:28
     */
   public function getFreeCar($data){
       $db_house_village_park_free=new HouseVillageParkFree();
       $where['village_id'] = $data['village_id'];
       if (!empty($data['park_type'])){
           $where['park_type'] = $data['park_type'];
       }
       if (!empty($data['free_park'])){
           $where['free_park|first_name|last_name'] = $data['free_park'];
       }
       $count =$db_house_village_park_free->getCount($where);
       $list =$db_house_village_park_free->getList($where,'*',$data['page'],$data['limit']);
       if (!empty($list)){
           $list=$list->toArray();
       }
       if (!empty($list)) {
           foreach ($list as &$v) {
               if (!empty($v['first_name'])) {
                   $v['free_name'] = $v['first_name'];
               } elseif (!empty($v['last_name'])) {
                   $v['free_name'] = $v['last_name'];
               } else {
                   $v['free_name'] = $v['free_park'];
               }
               $v['park_type'] =$v['park_type']>0? $this->park_type[$v['park_type']]:'--';
               $v['addTime'] =$v['addTime']>0?date('Y-m-d H:i:s',$v['addTime']):'--';
           }
       }
       $data1=[];
       $data1['list'] = $list;
       $data1['total_limit'] = $data['limit'];
       $data1['count'] = $count;
       return $data1;
   }


    /**
     * 添加黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:00
     */
    public function addBlackCar($data){
        $db_house_village_park_black=new HouseVillageParkBlack();
        $data1['user_name'] = $data['user_name'];
        $data1['phone'] = $data['phone'];
        $data1['car_number'] = $data['city_arr'] .$data['car_number'];
        $data1['village_id'] = $data['village_id'];
        $data1['park_sys_type'] = '';
        $data1['addTime'] = time();
        $data1['updateTime'] = time();
        $data1['remark']= $data['remark'];//车牌类型
        $res =$db_house_village_park_black->addOne($data1);
        if ($res>0){
            $this->villageParkBlackOpt($data['village_id'],$data1['car_number'],1);
        }
        return $res;
    }
    
    /*****
    operate 操作类型（1=添加 2=修改 3=删除）
     ***/
    public function villageParkBlackOpt($village_id = 0, $car_number = '',$operate=1)
    {
        if ($village_id < 1 || empty($car_number)) {
            return true;
        }
        cache('backList_' . $village_id, null);
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $garagePark = $houseVillageParkGarageConfigService->getListData($whereArr);
        if (!empty($garagePark)) {
            $a11Service = new A11Service();
            $serice_qinlinCloud = new QinLinCloudService();
            foreach ($garagePark as $gvv) {
                if ($gvv['park_sys_type'] == 'D7') {
                    $serice_qinlinCloud->getBlackList($gvv['d7_park_id'], $car_number, $operate);
                } elseif ($gvv['park_sys_type'] == 'A11') {
                    $is_alarm=1;
                    $operate_type=0;
                    if($operate==3){
                        $operate_type=1;
                    }
                    $a11Service->triggerWhitelist($village_id, $car_number, $is_alarm, $operate_type);
                }
            }
        }
        return true;
    }
        /**
     * 添加黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:00
     */
    public function editBlackCar($data){
        $db_house_village_park_black=new HouseVillageParkBlack();
        $info=$db_house_village_park_black->getFind(['id'=>$data['id'],'village_id'=>$data['village_id']],'id,car_number');
        if(!$info){
            throw new \think\Exception("该数据不存在");
        }
        $data1['user_name'] = $data['user_name'];
        $data1['phone'] = $data['phone'];
        $data1['car_number'] = $data['city_arr'] .$data['car_number'];
        $data1['park_sys_type'] = '';
        $data1['updateTime'] = time();
        $data1['remark']= $data['remark'];//车牌类型
        $res = $db_house_village_park_black->save_one(['id' => $data['id']], $data1);
        if ($res>0){
            $this->villageParkBlackOpt($data['village_id'],$data1['car_number'],2);
        }
        return $res;
    }

    /**
     * 删除黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function delBlackCar($black_id){
        $db_house_village_park_black=new HouseVillageParkBlack();
        $car_info=$db_house_village_park_black->getFind(['id'=>$black_id]);
        $res=$db_house_village_park_black->del_one(['id'=>$black_id]);
        if ($res>0){
            $this->villageParkBlackOpt($car_info['village_id'],$car_info['car_number'],3);
        }
        return $res;
    }

    /**
     * 查询黑名单列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:28
     */
    public function getBlackCar($data,$hidephone=false){
        $db_house_village_park_black=new HouseVillageParkBlack();
        $where[] =['village_id','=',$data['village_id']];
        if (!empty($data['car_number'])){
            $where[] =['car_number','=',$data['car_number']];
        }
        if (!empty($data['user_name'])){
            $where[] =['user_name','like','%'.$data['user_name'].'%'];
        }
        $count =$db_house_village_park_black->getCount($where);
        $list =$db_house_village_park_black->getList($where,'*',$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
        }
        if (!empty($list)) {
            $houseVillageDb=new HouseVillage();
            $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
            foreach ($list as &$v) {
                $v['addTime'] = date('Y-m-d H:i:s',$v['addTime']);
                if($hidephone && isset($v['phone']) && !empty($v['phone'])){
                    $v['phone'] = $houseVillageDb->phone_desensitization_v($v['phone'],$data['village_id'],$village_info);
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    /**
     * 删除黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function getBlackCarInfo($data){
        $db_house_village_park_black=new HouseVillageParkBlack();
        $res=$db_house_village_park_black->getFind(['id'=>$data['black_id']]);
        if (!empty($res)){
            $res['province']=mb_substr($res['car_number'],0,1);
            $res['car_number']=mb_substr($res['car_number'],1);
        }
        return $res;
    }



    /**
     * 查询通道详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 10:55
     */
    public function getPassageInfo($data){
        $db_park_passage = new ParkPassage();
        $db_house_village_parking_garage = new HouseVillageParkingGarage();
        $db_house_village_public_area = new HouseVillagePublicArea();
        $db_park_passage_type = new ParkPassageType();
        $where['village_id'] = $data['village_id'];
        $where['id'] = $data['id'];
        $list = $db_park_passage->getFind($where,'*');
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
        }
        $garage_id = 0;
        if (isset($list['garage_id']) && $list['garage_id'] > 0) {
            $garage_id = $list['garage_id'];
        }
        $list['lat']=$list['lat'] ? $list['lat']:'';
        $list['long']=$list['long'] ? $list['long']:'';
        if(isset($list['device_port']) && $list['device_port']<1){
            $list['device_port']='';
        }
        if ($list['area_type'] == 2) {
            if ($garage_id < 1) {
                $garage_id = $list['passage_area'];
            }
            $list['passage_area'] = '';
        } elseif ($list['area_type'] == 1) {
            $area_name = $db_house_village_public_area->getOne(['public_area_id' => $list['passage_area'], 'status' => 1], 'public_area_name');
            if (empty($area_name)) {
                $list['passage_area'] = '';
            }
        }
        if(isset($list['device_setting']) && isset($list['device_setting'])){
            $list['device_setting']=json_decode($list['device_setting'],true);
        }
        if ($list['passage_type']==2){
            $type_info=$db_park_passage_type->getFind(['passage_id|passage_relation'=>$data['id'],'village_id'=>$data['village_id'],'park_sys_type'=>'A11']);
            if (!empty($type_info)){
                if (!empty($type_info['passage_id'])){
                    $where11=[];
                    $where11['village_id']=$data['village_id'];
                    $where11['id']=$type_info['passage_id'];
                    $info=$db_park_passage->getFind($where11);
                   
                }else{
                    $where11=[];
                    $where11['village_id']=$data['village_id'];
                    $where11['id']=$type_info['passage_relation'];
                    $info=$db_park_passage->getFind($where11);
                }
                if (!empty($info)){
                    $list['passage_relation']=$info['passage_name'];
                }
            }
        }
        $list['garage_id']=$garage_id>0 ? intval($garage_id):'';
        $list['garage_info']='';
        if($list['garage_id']>0){
            $whereArr=array(['garage_id','=',$list['garage_id']]);
            $parking_garage=$db_house_village_parking_garage->getOne($whereArr,'*');
            if($parking_garage && !$parking_garage->isEmpty()){
                $garage_info=$parking_garage->toArray();
                if(isset($garage_info['extra_data'])){
                    unset($garage_info['extra_data']);
                }
                $list['garage_info']=$garage_info;
            }
        }
        if($list && isset($list['park_sys_type']) && $list['park_sys_type']=='third_ai_park'){
            $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
            $dataTmp=array('village_id'=>$list['village_id'],'passage_id'=>$list['id'],'park_key'=>$list['park_type'],'device_number'=>$list['device_number'],'is_return_data'=>1);
            $tmpArr=$houseNewAiParkingService->getGaragePassageStatus($list['id'],$dataTmp);
            if($tmpArr && $tmpArr['is_online']){
                $list['is_online']=$tmpArr['is_online'];
            }
            $list['is_online_str']=$list['is_online']>0 ? '在线':'离线';
        }
        return $list;
    }


    /**
     * 查询通道列表
     * @author:zhubaodi
     * @date_time: 2022/3/11 10:55
     */
    public function getPassageList($data){
        $db_park_passage=new ParkPassage();
        $db_park_showscreen_log=new ParkShowscreenLog();
        $db_park_system=new ParkSystem();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_public_area=new HouseVillagePublicArea();
        $where=array();
        $where['village_id']=$data['village_id'];
        if (!empty($data['garage_id'])){
            $where['garage_id']=$data['garage_id'];
        }
        $count=$db_park_passage->getCount($where);
        $list=$db_park_passage->getLists($where,'*',$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
        }
        $last_heart_time=0;

        $heartParams = ['village_id' => $data['village_id']];
        $heartResult = invoke_cms_model('ParkService/getParkSystemHeartTime',$heartParams);
        $heartResult = $heartResult['retval'];
        $last_heart_time_info=$db_park_system->getFind(['park_id'=>$data['village_id']]);
        if ($heartResult > 100) {
            $last_heart_time = $heartResult;
        } elseif (!empty($last_heart_time_info)&&$last_heart_time_info['last_upload_time']>1){
            $last_heart_time=$last_heart_time_info['last_upload_time'];
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $village_park_config=array();
        if (!empty($list)){
            foreach ($list as $kk=>&$v){
                $v['garage_name']='';
                if ($v['passage_direction']==1){
                    $v['passage_direction_txt']='入口';
                }elseif($v['passage_direction']==2){
                    $v['passage_direction_txt']='出入口';
                }else{
                    $v['passage_direction_txt']='出口';
                }
                $v['area_name']='';
                $garage_id=0;
                if($v['garage_id']>0){
                    $area_name= $db_house_village_parking_garage->getOne(['garage_id'=>$v['garage_id']],'garage_num,status');
                    $garage_id=$v['garage_id'];
                    if($area_name['status']==1){
                        $v['garage_name']=$area_name['garage_num'];
                    }
                }else if ($v['area_type']==2){
                    $area_name= $db_house_village_parking_garage->getOne(['garage_id'=>$v['passage_area']],'garage_num,status');
                    $garage_id=$v['passage_area'];
                    if($area_name['status']==1){
                        $v['garage_name']=$area_name['garage_num'];
                    }
                } 
                if ($v['area_type']==1){
                    $area_name=$db_house_village_public_area->getOne(['public_area_id'=>$v['passage_area']],'public_area_name');
                    $v['area_name']=$area_name['public_area_name'];
                }
                if ($v['status']==1){
                    $v['status_txt']='开启';
                }else{
                    $v['status_txt']='关闭';
                }
                $village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);

                $device_number = isset($v['device_number']) && trim($v['device_number']) ? trim($v['device_number']) : '';
                if ($device_number) {
                    $heartTime = (new CarHandleGetInfoService())->getParkPassageHeart($device_number);
                } else {
                    $heartTime = 0;
                }
                if ( isset($v['last_heart_time']) && ($v['last_heart_time'] > $heartTime)) {
                    $heartTime = $v['last_heart_time'];
                }
                $park_sys_type = isset($village_park_config['park_sys_type']) ? trim($village_park_config['park_sys_type']) : '';

                if (in_array($park_sys_type,array('D3','A11','HXT85')) && $heartTime > 1) {
                    if ($heartTime + $this->last_heart_time < time()) {
                        $log_where = [
                            ['village_id', '=', $data['village_id']],
                            ['channel_id', '=', $v['device_number']],
                            ['add_time', '<', $this->last_heart_time],
                        ];
                        $log_count = $db_park_showscreen_log->getCount($log_where);
                        if ($log_count > 0) {
                            $db_park_showscreen_log->delOne($log_where);
                        }
                    }
                    $v['last_heart_time'] = date('Y-m-d H:i:s', $heartTime);
                } elseif ($village_park_config['park_sys_type'] == 'A1' && $last_heart_time > 1) {
                    $v['last_heart_time'] = date('Y-m-d H:i:s', $last_heart_time);
                } else {
                    $v['last_heart_time'] = '--';
                }

                $is_button_show=false;
                if(in_array($village_park_config['park_sys_type'],['D3','A11','HXT85']) && $v['status'] == 1){
                    $is_button_show=true;
                }
                $v['show_ewm_btn']=$is_button_show;
                $v['is_hand_open_gate']=$is_button_show;
                $v['is_edit_btn']=$is_button_show;
                $v['is_view_btn']=false;
                $v['is_add_record']=$is_button_show;
                if($v['park_sys_type']=='third_ai_park'){
                    $is_button_show=false;
                    $v['is_hand_open_gate']=true;
                    $v['show_ewm_btn']=true;
                    if ($v['passage_direction']==1){
                        $v['show_ewm_btn']=false;
                    }
                    $v['is_edit_btn']=false;
                    $v['is_view_btn']=true;
                    $v['is_add_record']=true;
                    $v['last_heart_time'] = '离线';
                    if($v['is_online']>0){
                        $v['last_heart_time'] = '在线';
                    }
                    $queueData=array('village_id'=>$v['village_id'],'passage_id'=>$v['id'],'park_key'=>$v['park_type'],'device_number'=>$v['device_number'],'opt_type'=>'get_third_ai_park_vehicle_status');
                    $tmptime=($kk+1)*2;
                    $this->parkPassageQueuePushLaterToJob($queueData,$tmptime);
                }
                $v['is_button_show']=$is_button_show;
                if(isset($v['device_setting'])){
                    unset($list[$kk]['device_setting']);
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type'] =$village_park_config ? $village_park_config['park_sys_type']:'';
        return $data1;
    }

    public function getQrcodePassage($data){
        $db_park_passage=new ParkPassage();
        $passage_info=$db_park_passage->getFind(['id'=>$data['passage_id']]);
        if (empty($passage_info)){
            throw new \think\Exception("通道信息不存在");
        }
        //二维码
        $qrcode_url = get_base_url(). 'pages/village/smartPark/parkingFeetwo';
        $qrcode_url1 = get_base_url(). 'pages/parkingLot/pages/registerCar?isRegisterCar=0&village_id='.$data['village_id'];
        if ($passage_info['passage_direction']==1){
            $spread=3;
        }else{
            $spread=2;
        }
        if (in_array($passage_info['park_sys_type'],['D3','D7','A11','HXT85'])&&$passage_info['passage_direction']==1){
            $qrcode_key=$qrcode_url1.'&channel_id='.$passage_info['channel_number'].'&passage_id='.$passage_info['id'].'&garage_id='.$passage_info['garage_id'];
        }else{
            $qrcode_key=$qrcode_url.'?channel_id='.$passage_info['channel_number'].'&spread='.$spread.'&village_id='.$data['village_id'].'&passage_id='.$passage_info['id'].'&garage_id='.$passage_info['garage_id'];
        }
        $path=urlencode(htmlspecialchars_decode($qrcode_key));
        return ['qrcode'=>cfg('site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.$path];
        $nowtime=time();
        // 创建目录
        $filename =rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/upload/parkPassage/'.date('Ymd').'/qrcode_'.$data['passage_id'].'_'.$nowtime.'.png';
        $dirName = dirname($filename);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists($filename)){
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($qrcode_key,$filename,$errorCorrectionLevel,$matrixPointSize,2);
        }
        $filename1 ='/upload/parkPassage/'.date('Ymd').'/qrcode_'.$data['passage_id'].'_'.$nowtime.'.png';
        $data =  cfg('site_url').$filename1;
        return ['qrcode'=>$data];
    }


    public function getQrcodeSpread($data){

        //二维码
        $qrcode_url = get_base_url(). 'pages/village/smartPark/parkingFeetwo';
        $qrcode_key=$qrcode_url.'?channel_id=1&spread=1&village_id='.$data['village_id'];
        $whereArr=array(['village_id','=',$data['village_id']],['passage_direction','in',[0,2]]);
        if(isset($data['garage_id']) && $data['garage_id']){
            $whereArr[]=['garage_id','=',$data['garage_id']];
        }
        $parkPassage=new ParkPassage();
        $parkPassageObj=$parkPassage->getFind($whereArr,'*','passage_direction asc');
        if($parkPassageObj && !$parkPassageObj->isEmpty()){
            $qrcode_key=$qrcode_url.'?channel_id='.$parkPassageObj['channel_number'].'&spread=1&village_id='.$data['village_id'].'&garage_id='.$parkPassageObj['garage_id'];
        }
        $nowtime=time();
        // 创建目录
        $filename =rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/upload/parkPassageSpread/'.date('Ymd').'/qrcode_'.$data['village_id'].'_'.$nowtime.'.png';
        $dirName = dirname($filename);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists($filename)){
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($qrcode_key,$filename,$errorCorrectionLevel,$matrixPointSize,2);
        }
        $filename1 ='/upload/parkPassageSpread/'.date('Ymd').'/qrcode_'.$data['village_id'].'_'.$nowtime.'.png';
        $data =  cfg('site_url').$filename1;
        return ['qrcode'=>$data];
    }


    /**
     * 优惠券二维码
     * @author:zhubaodi
     * @date_time: 2022/3/21 9:47
     */
    public function getQrcodeCoupons($data){
        $db_park_passage=new ParkShopCoupons();
        $passage_info=$db_park_passage->getFind(['id'=>$data['coupons_id']]);
        if (empty($passage_info)){
            throw new \think\Exception("优惠券信息不存在");
        }
        //二维码
        $qrcode_url = get_base_url(). 'pages/village/smartPark/parkingFee';
        $qrcode_key=$qrcode_url.'?coupons_id='.$data['coupons_id'].'&status=1&village_id='.$data['village_id'];

        $nowtime=time();
        // 创建目录
        $filename =rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/upload/parkShopCoupons/'.date('Ymd').'/qrcode_'.$data['coupons_id'].'_'.$nowtime.'.png';
        $dirName = dirname($filename);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists($filename)){
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($qrcode_key,$filename,$errorCorrectionLevel,$matrixPointSize,2);
        }
        $filename1 ='/upload/parkShopCoupons/'.date('Ymd').'/qrcode_'.$data['coupons_id'].'_'.$nowtime.'.png';
        $data =  cfg('site_url').$filename1;
        return ['qrcode'=>$data];
    }

    /**
     * 店铺管理二维码
     * @author:zhubaodi
     * @date_time: 2022/3/21 9:46
     */
    public function getQrcodeShop($data){
        $db_park_passage=new ParkCouponsShop();
        $passage_info=$db_park_passage->getFind(['m_id'=>$data['m_id']]);
        if (empty($passage_info)){
            throw new \think\Exception("店铺信息不存在");
        }
        //二维码
        $qrcode_url = get_base_url(). 'pages/village/smartPark/shopCoupon';
        $qrcode_key=$qrcode_url.'?m_id='.$data['m_id'].'&village_id='.$data['village_id'];

        $path=urlencode(htmlspecialchars_decode($qrcode_key));
        return ['qrcode'=>cfg('site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.$path];

        $nowtime=time();
        // 创建目录
        $filename =rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/upload/parkCouponsShop/'.date('Ymd').'/qrcode_'.$data['m_id'].'_'.$nowtime.'.png';
        $dirName = dirname($filename);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists($filename)){
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($qrcode_key,$filename,$errorCorrectionLevel,$matrixPointSize,2);
        }
        $filename1 ='/upload/parkCouponsShop/'.date('Ymd').'/qrcode_'.$data['m_id'].'_'.$nowtime.'.png';
        $data =  cfg('site_url').$filename1;
        return ['qrcode'=>$data];
    }

    public function shop_search($data){
        $db_park_coupons_shop=new ParkCouponsShop();
        $db_merchant_store=new MerchantStore();
        $where1=[
           /* ['village_id','=',$data['village_id']],*/
            ['status','<>',-1],
            ['type','=',1],
           /* ['m_name','=',$data['m_name']],*/
        ];
        $bind_m_id =$db_park_coupons_shop->getColumn($where1,'bind_m_id');
        if($bind_m_id){
            $where[] =['store_id','not in',$bind_m_id] ;
        }
        $where[]=['status','=',1];
        $list =$db_merchant_store->getStoreList($where,0,20,'store_id,mer_id,name,adress');
        return $list;
    }

    public function add_park_shop($data){
        $db_park_coupons_shop=new ParkCouponsShop();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $data['village_id']);
        $village_park_configs=$houseVillageParkGarageConfigService->getListData($whereArr,'*','id DESC',1,2);
        $village_park_config=array();
        if($village_park_configs){
            $village_park_config=$village_park_configs['0'];
        }
        if(empty($village_park_config) || empty($village_park_config['park_sys_type'])){
            throw new \think\Exception("请先开启智慧停车");
        }
        $where = [
            ['m_name','=', $data['m_name']],
            ['village_id','=', $data['village_id']],
            ['status','<>', -1],
            ];
        $info =$db_park_coupons_shop->getFind($where);
        if (!empty($info)) {
            throw new \think\Exception("店铺已存在");
        }
        $data1['village_id'] = $data['village_id'];
        $data1['m_name'] = $data['m_name'];
        $data1['bind_m_id'] = $data['bind_m_id'];
        $data1['type'] = $data['type'];
        $data1['remark'] = $data['remark'];
        $data1['add_time'] = time();
       // $data1['park_sys_type'] = $village_park_config['park_sys_type'];
        $data1['park_sys_type'] = '';
        $res = $db_park_coupons_shop->addOne($data1);
        $url = get_base_url().'pages/village/smartPark/shopCoupon?village_id='.$data['village_id'].'&m_id='.$res;
        $db_park_coupons_shop->save_one(['m_id'=>$res],['turn_url'=>$url]);
        return $res;
    }

    public function edit_park_shop($data){
        //$db_house_village_park_config=new HouseVillageParkConfig();
        $db_park_coupons_shop=new ParkCouponsShop();
        //$village_park_config=$db_house_village_park_config->getFind(['village_id'=>$data['village_id']]);
        $info =$db_park_coupons_shop->getFind(['m_id'=>$data['m_id']]);
        if (empty($info)) {
            throw new \think\Exception("店铺信息不存在");
        }
        $where = [
            ['m_name','=',$data['m_name']],
            ['village_id','=',$data['village_id']],
            ['m_id','<>',$data['m_id']],
            ['status','<>', -1],
            ];
        $info =$db_park_coupons_shop->getFind($where);
        if (!empty($info)) {
            throw new \think\Exception("店铺名称已存在");
        }
        $data1['village_id'] = $data['village_id'];
        $data1['m_name'] = $data['m_name'];
        $data1['bind_m_id'] = $data['bind_m_id'];
        $data1['type'] = $data['type'];
        $data1['remark'] = $data['remark'];
        //$data1['park_sys_type'] = $village_park_config['park_sys_type'];
        $res = $db_park_coupons_shop->save_one(['m_id'=>$data['m_id']],$data1);

        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '店铺列表-编辑',
                'table'  => 'park_coupons_shop',
                'client' => '小区后台',
                'trigger_path' => '停车管理->优惠券管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => []
        ];
        $this->laterLogInQueue($queuData);
        
        return $res;
    }

    public function del_park_shop($data){
        $db_park_coupons_shop=new ParkCouponsShop();
        $info =$db_park_coupons_shop->getFind(['m_id'=>$data['m_id']]);
        if (empty($info)) {
            throw new \think\Exception("店铺信息不存在");
        }
        $res = $db_park_coupons_shop->save_one(['m_id'=>$data['m_id']],['status'=> -1,'edit_time'=>time()]);

        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '店铺列表-删除',
                'table'  => 'park_coupons_shop',
                'client' => '小区后台',
                'trigger_path' => '停车管理->优惠券管理',
                'trigger_type' => $this->getDeleteName(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => []
        ];
        $this->laterLogInQueue($queuData);
        return $res;
    }

    public function getParkShopList($data){
        $db_park_coupons_shop=new ParkCouponsShop();
        if (!empty($data['m_name'])){
            $where[]=['m_name','like','%'.$data['m_name'].'%'];
        }
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['add_time','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['add_time','<=',strtotime($data['date'][1].' 23:59:59')];
            }

        }
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['status','=',0];
        $list=$db_park_coupons_shop->getLists($where,'*',$data['page'],$data['limit']);
        $count=$db_park_coupons_shop->getCount($where);
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    public function getParkShopInfo($data){
        $db_park_coupons_shop=new ParkCouponsShop();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['m_id','=',$data['m_id']];
        $info=$db_park_coupons_shop->getFind($where);
        return $info;
    }

    /**
     * 添加优惠券
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function add_park_coupons($data){
        //$db_house_village_park_config=new HouseVillageParkConfig();
        $db_park_coupons=new ParkCoupons();
        //$village_park_config=$db_house_village_park_config->getFind(['village_id'=>$data['village_id']]);
        $where1=[
            ['c_title','=',$data['c_title']],
            ['village_id','=',$data['village_id']],
            ['status','<>',-1],
        ];
        $info =$db_park_coupons->getFind($where1);
        if ($info) {
            throw new \think\Exception("优惠券已存在");
        }
        $data1['village_id'] = $data['village_id'];
        //$data1['park_sys_type'] = $village_park_config['park_sys_type'];
        $data1['c_title'] = $data['c_title'];
        $data1['c_price'] = $data['c_price'];
        $data1['c_free_price'] = $data['c_free_price'];
        $data1['status'] = $data['status'];
        $data1['remark'] = $data['remark'];
        $data1['garage_id'] = isset($data['garage_id']) ? intval($data['garage_id']) : 0;
        $data1['add_time']=time();
        $res=$db_park_coupons->addOne($data1);
        return $res;
    }

    /**
     * 编辑优惠券
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function edit_park_coupons($data){
        //$db_house_village_park_config=new HouseVillageParkConfig();
        $db_park_coupons=new ParkCoupons();
        //$village_park_config=$db_house_village_park_config->getFind(['village_id'=>$data['village_id']]);
        $where1=[
            ['c_title','=',$data['c_title']],
            ['village_id','=',$data['village_id']],
            ['status','<>',-1],
            ['c_id','<>',$data['c_id']],
        ];
        $info =$db_park_coupons->getFind($where1);
        if ($info) {
            throw new \think\Exception("优惠券已存在");
        }
        $data1['village_id'] = $data['village_id'];
       // $data1['park_sys_type'] = $village_park_config['park_sys_type'];
        $data1['c_title'] = $data['c_title'];
        $data1['c_price'] = $data['c_price'];
        $data1['c_free_price'] = $data['c_free_price'];
        $data1['status'] = $data['status'];
        $data1['remark'] = $data['remark'];
        $data1['garage_id'] = isset($data['garage_id']) ? intval($data['garage_id']) : 0;
        $data1['add_time']=time();
        $res=$db_park_coupons->save_one(['c_id'=>$data['c_id']],$data1);

        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '优惠券列表-编辑',
                'table'  => 'park_coupons',
                'client' => '小区后台',
                'trigger_path' => '停车管理->优惠券管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => []
        ];
        $this->laterLogInQueue($queuData);
        return $res;
    }

    /**
     * 删除优惠券
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function del_park_coupons($data){
        $db_park_coupons=new ParkCoupons();
        $info =$db_park_coupons->getFind(['c_id'=>$data['c_id']]);
        if (empty($info)) {
            throw new \think\Exception("优惠券不存在");
        }
        $res=$db_park_coupons->save_one(['c_id'=>$data['c_id']],['status'=> -1,'edit_time'=>time()]);
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '优惠券列表-删除',
                'table'  => 'park_coupons',
                'client' => '小区后台',
                'trigger_path' => '停车管理->优惠券管理',
                'trigger_type' => $this->getDeleteName(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => []
        ];
        $this->laterLogInQueue($queuData);
        return $res;
    }

    /**
     * 查询优惠券详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function get_park_coupons_info($data){
        $db_park_coupons=new ParkCoupons();
        $info =$db_park_coupons->getFind(['c_id'=>$data['c_id']]);
        return $info;
    }

    public function getParkCouponsList($data){
        $db_park_coupons=new ParkCoupons();
        if (!empty($data['c_title'])){
            $where[]=['c_title','=',$data['c_title']];
        }
        if (!empty($data['date'])&&count($data['date'])>0){
            $date=$data['date'];
            if (!empty($date[0])){
                $where[] = ['add_time','>=',strtotime($date[0].' 00:00:00')];
            }
            if (!empty($date[1])){
                $where[] = ['add_time','<=',strtotime($date[1].' 23:59:59')];
            }

        }
        $where[]=['status','<>',-1];
        $where[]=['village_id','=',$data['village_id']];
        $list=$db_park_coupons->getLists($where,'*',$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
        }
        if (!empty($list)){

            $garageIds = array_unique(array_column($list, 'garage_id'));
            $db_house_village_parking_garage = new HouseVillageParkingGarage();
            $whereGarage = [];
            $whereGarage[] = ['village_id', '=', $data['village_id']];
            $whereGarage[] = ['garage_id', 'in', $garageIds];
            $garageColumn = $db_house_village_parking_garage->getColumn($whereGarage, 'garage_id, village_id, garage_num', 'garage_id');

            $db_house_village_park_config = new HouseVillageParkConfig();
            $whereConfig = [];
            $whereConfig[] = ['village_id', '=', $data['village_id']];
            $parkConfig = $db_house_village_park_config->getFind($whereConfig, 'id, village_id, park_sys_type');
            $defaultParkSysType = isset($parkConfig['park_sys_type']) && trim($parkConfig['park_sys_type']) ? trim($parkConfig['park_sys_type']) : 'A1';
            $db_house_village_park_garage_config = new HouseVillageParkGarageConfig();
            $whereGarageConfig = [];
            $whereGarageConfig[] = ['village_id', '=', $data['village_id']];
            $whereGarageConfig[] = ['garage_id', 'in', $garageIds];
            $garageConfigColumn = $db_house_village_park_garage_config->getColumn($whereGarageConfig, 'garage_id, village_id, park_sys_type', 'garage_id');
            
            
            foreach ($list as &$v){
                if ($v['status']==1){
                    $v['status_txt']='禁用';
                }elseif($v['status']==0){
                    $v['status_txt']='开启';
                }
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                $garage_id = isset($v['garage_id']) ? intval($v['garage_id']) : 0;
                $garage = isset($garageColumn[$garage_id]) ? $garageColumn[$garage_id] : [];
                $garageConfig = isset($garageConfigColumn[$garage_id]) ? $garageConfigColumn[$garage_id] : [];
                $park_sys_type = isset($garageConfig['park_sys_type']) ? trim($garageConfig['park_sys_type']) : $defaultParkSysType;
                $garage_num = isset($garage['garage_num']) ? trim($garage['garage_num']) : '';
                if ($garage_num) {
                    $v['garage_num'] = $garage_num . " [{$park_sys_type}]";
                } else {
                    $v['garage_num'] = '-';
                }
            }
        }
        $count=$db_park_coupons->getCount($where);
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    public function getParkCouponsLists($data){
        $db_park_coupons=new ParkCoupons();
        if (!empty($data['c_title'])){
            $where[]=['c_title','=',$data['c_title']];
        }
        if (!empty($data['date'])&&count($data['date'])>0){
            $date=$data['date'];
            if (!empty($date[0])){
                $where[] = ['add_time','>=',strtotime($date[0].' 00:00:00')];
            }
            if (!empty($date[1])){
                $where[] = ['add_time','<=',strtotime($date[1].' 23:59:59')];
            }

        }
        $where[]=['status','=',0];
        $where[]=['village_id','=',$data['village_id']];
        $list=$db_park_coupons->getLists($where,'*');
        if (!empty($list)){
            $list=$list->toArray();
        }
        if (!empty($list)){
           foreach ($list as &$v){
               if ($v['status']==1){
                   $v['status_txt']='禁用';
               }elseif($v['status']==0){
                   $v['status_txt']='开启';
               }
               $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
           }
        }
        $data1=[];
        $data1['list'] = $list;
        return $data1;
    }

    public function add_park_shop_coupons($data){
        //$db_house_village_park_config=new HouseVillageParkConfig();
        $db_park_shop_coupons=new ParkShopCoupons();
        //$village_park_config=$db_house_village_park_config->getFind(['village_id'=>$data['village_id']]);
        $where['village_id']= $data['village_id'];
        $where['cid']= $data['cid'];
        $where['mid']= $data['mid'];
        $where['status']= $data['status'];
        $info =$db_park_shop_coupons->getFind($where);
        //$data['park_sys_type']=$village_park_config['park_sys_type'];
        if($info){
            $data['residue_num'] = $info['residue_num']+$data['num'];
            $data['num'] = $info['num']+$data['num'];
            if(!$info['random_code']){
                $data['random_code'] = md5(time().rand(11111,99999));
            }
            if($data['status'] == 1){//静态码
                $url = get_base_url().'pages/village/smartPark/parkingFee?coupons_id='.$info['id'].'&status='.$data['status'];
                $this->create_code($info['id'],$url);
            }
            $res = $db_park_shop_coupons->save_one(['id'=>$info['id']],$data);
            return $res;
        }else{
            $data['residue_num'] = $data['num'];
            $data['add_time'] = time();
            $data['random_code'] = md5(time().rand(11111,99999));
            $res = $db_park_shop_coupons->addOne($data);
            if($data['status'] == 1){//静态码
                $url = get_base_url().'pages/village/smartPark/parkingFee?coupons_id='.$res.'&status='.$data['status'].'&village_id='.$data['village_id'];
                $this->create_code($res,$url);
            }
            return $res;
        }
    }

    public function getShopCouponsList($data){
       $db_park_shop_coupons=new ParkShopCoupons();
        $where['mc.village_id']= $data['village_id'];
        $where['mc.mid']= $data['m_id'];
        $count =$db_park_shop_coupons->getCounts($where);
        $list = $db_park_shop_coupons->getListss($where,'mc.*,c.c_title,c.garage_id',$data['page'],$data['limit']);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            $garageIds = array_unique(array_column($list, 'garage_id'));
            $db_house_village_parking_garage = new HouseVillageParkingGarage();
            $whereGarage = [];
            $whereGarage[] = ['village_id', '=', $data['village_id']];
            $whereGarage[] = ['garage_id', 'in', $garageIds];
            $garageColumn = $db_house_village_parking_garage->getColumn($whereGarage, 'garage_id, village_id, garage_num', 'garage_id');
            
            $db_house_village_park_config = new HouseVillageParkConfig();
            $whereConfig = [];
            $whereConfig[] = ['village_id', '=', $data['village_id']];
            $parkConfig = $db_house_village_park_config->getFind($whereConfig, 'id, village_id, park_sys_type');
            $defaultParkSysType = isset($parkConfig['park_sys_type']) && trim($parkConfig['park_sys_type']) ? trim($parkConfig['park_sys_type']) : 'A1';
            $db_house_village_park_garage_config = new HouseVillageParkGarageConfig();
            $whereGarageConfig = [];
            $whereGarageConfig[] = ['village_id', '=', $data['village_id']];
            $whereGarageConfig[] = ['garage_id', 'in', $garageIds];
            $garageConfigColumn = $db_house_village_park_garage_config->getColumn($whereGarageConfig, 'garage_id, village_id, park_sys_type', 'garage_id');
            foreach ($list as &$item) {
                $garage_id = isset($item['garage_id']) ? intval($item['garage_id']) : 0;
                $garage = isset($garageColumn[$garage_id]) ? $garageColumn[$garage_id] : [];
                $garageConfig = isset($garageConfigColumn[$garage_id]) ? $garageConfigColumn[$garage_id] : [];
                $park_sys_type = isset($garageConfig['park_sys_type']) ? trim($garageConfig['park_sys_type']) : $defaultParkSysType;
                $garage_num = isset($garage['garage_num']) ? trim($garage['garage_num']) : '';
                if ($garage_num) {
                    $item['garage_num'] = $garage_num . " [{$park_sys_type}]";
                } else {
                    $item['garage_num'] = '-';
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['garageIds'] = $garageIds;
        return $data1;
    }

    public function sendCouponToCarUse($data = array())
    {
        if (empty($data) || !isset($data['village_id']) || !isset($data['car_number']) || empty($data['car_number'])) {
            throw new \think\Exception("提交数据错误！");
        }
        $idd = isset($data['idd']) ? $data['idd'] : 0;
        $parkShopCouponsDb = new ParkShopCoupons();
        $whereArr = array(['id', '=', $idd], ['village_id', '=', $data['village_id']]);
        $coupons_info = $parkShopCouponsDb->getFind($whereArr, '*');
        if (empty($coupons_info) || $coupons_info->isEmpty()) {
            throw new \think\Exception("优惠券信息不存在！");
        }
        $send_num = isset($data['send_num']) ? $data['send_num'] : 1;
        $send_num = $send_num > 0 ? $send_num : 1;
        if($send_num>$coupons_info['residue_num']){
            throw new \think\Exception("优惠券剩余数量不足，现在还剩".$coupons_info['residue_num']);
        }
        $nowTime = time();
        $houseVillageParkCouponDb = new HouseVillageParkCoupon();
        $uid = 0;
        $houseVillageCarAccessRecord= new HouseVillageCarAccessRecord();
        $whereTmpArr=array(['park_id','=',$data['village_id']],['car_number','=',$data['car_number']],['uid','>',0]);
        $oneTmpData= $houseVillageCarAccessRecord->getOne($whereTmpArr,'uid','record_id DESC');
        if($oneTmpData && !$oneTmpData->isEmpty()){
            $uid=$oneTmpData['uid'];
        }
        if($uid<1){
            $whereTmpArr=array(['village_id','=',$data['village_id']],['car_number','=',$data['car_number']],['user_id','>',0]);
            $oneUserCoupon=$houseVillageParkCouponDb->getFind($whereTmpArr,'*','id desc');
            if($oneUserCoupon && !$oneUserCoupon->isEmpty()){
                $uid=$oneUserCoupon['user_id'];
            }
        }
        for ($ii = 0; $ii < $send_num; $ii++) {
            $dataCoupon = array();
            $dataCoupon['car_number'] = $data['car_number'];
            $dataCoupon['user_id'] = $uid;
            $dataCoupon['add_time'] = $nowTime;
            $dataCoupon['is_download'] = 0;//未下发
            $dataCoupon['is_use'] = 0;//未使用
            $dataCoupon['village_id'] = $data['village_id'];
            $dataCoupon['ticket_id'] = createRandomStr(15);
            $dataCoupon['park_sys_type'] = $coupons_info['park_sys_type'];
            $dataCoupon['garage_id'] = $data['garage_id'];
            $dataCoupon['money'] = $coupons_info['free_money'];
            $dataCoupon['coupons_id'] = $coupons_info['id'];
            $tmp_id = $houseVillageParkCouponDb->addOneData($dataCoupon);
            if ($tmp_id) {
                $houseVillageCarFlowCouponDb = new HouseVillageCarFlowCoupon();
                $flowCoupon = [
                    'park_coupon_id' => $tmp_id, 'uid' => $uid, 'village_id' => $dataCoupon['village_id'],
                    'car_number' => $dataCoupon['car_number'], 'park_sys_type' => $coupons_info['park_sys_type'], 'is_use' => 0,
                    'ticket_id' => $dataCoupon['ticket_id'], 'money' => $dataCoupon['money'], 'coupons_id' => $coupons_info['id'],
                    'receive_time' => $nowTime, 'is_download' => 0, 'add_time' => $nowTime,
                ];
                $uniqueArr = [
                    'car_number' => $dataCoupon['car_number'], 'uid' => $uid, 'village_id' => $dataCoupon['village_id'],
                    'park_sys_type' => $coupons_info['park_sys_type'], 'is_use' => 0,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $flowCoupon['unique_id'] = $unique_id;
                $flowCoupon['add_time'] = $nowTime;
                $houseVillageCarFlowCouponDb->addOneData($flowCoupon);
            }
        }
        $residue_num=$coupons_info['residue_num']-$send_num;  //剩余数减
        $residue_num=$residue_num>0 ?$residue_num:0;
        $buy_num=$coupons_info['buy_num']+$send_num;  //售出数加
        $whereArr = array(['id', '=', $idd], ['village_id', '=', $data['village_id']]);
        $saveArr=array('residue_num'=>$residue_num,'buy_num'=>$buy_num);
        $ret = $parkShopCouponsDb->save_one($whereArr,$saveArr);
        return $send_num;
    }
    //生成二维码
    public function create_code($code_no,$url)
    {
        // 创建目录
        $filename =rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/upload/qrcode/park_qrcode/'.$code_no.'.png';
        $filename1 ='/upload/qrcode/park_qrcode/'.$code_no.'.png';
        $dirName = dirname($filename);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists($filename)){
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($url,$filename,$errorCorrectionLevel,$matrixPointSize,2);
        }
        $data =  cfg('site_url').$filename1;
        return $data;
    }

    /**
     * 优惠券购买记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getPayCouponsList($data){
        $db_park_shop_coupons=new ParkShopCoupons();
        $where = [];
        if (!empty($data['title'])) {
            if ($data['param']=='m_name'){
                $where[] = ['m.'.$data['param'],'like', '%' . trim($data['title']) . '%'];
            }else{
                $where[] = ['c.'.$data['param'],'like', '%' . trim($data['title']) . '%'];
            }

        }
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['mc.add_time','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['mc.add_time','<=',strtotime($data['date'][1].' 23:59:59')];
            }

        }
        $where[] =['mc.village_id','=',$data['village_id']];
        $where[] = ['mc.status','<>',-1];
        $count =$db_park_shop_coupons->getCounts($where);
        $list =$db_park_shop_coupons->getListss($where,'mc.*,c.c_title,m.m_name',$data['page'],$data['limit']);
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
        }
        $map['village_id'] = $data['village_id'];
        $total_pay_num = $db_park_shop_coupons->sum($map,'num');
        $total_receivable_money = $db_park_shop_coupons->sum($map,'receivable_money');
        $total_paid_money = $db_park_shop_coupons->sum($map,'paid_money');
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['record'] = [
            'total_pay_num'=>$total_pay_num,
            'total_receivable_money'=>$total_receivable_money,
            'total_paid_money'=>$total_paid_money,
        ];
        return $data1;
    }

    /**
     * 优惠券领取记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getReceiveCouponsList($data){

        $db_house_village_park_coupon=new HouseVillageParkCoupon();
        $houseVillageCarAccessRecord= new HouseVillageCarAccessRecord();
        $db_user=new User();

        $where = [];
        $where[] =['mc.village_id','=',$data['village_id']];
        $where[] = ['mc.coupons_id','=',$data['coupons_id']];
        $where[] =['mc.car_number','<>',''];
       //  $where[] = ['c.accessType','=',2];
        $count =$db_house_village_park_coupon->getCouponCounts($where);
        //$list =$db_house_village_park_coupon->getCouponListss($where,'mc.*,cr.accessTime as in_accessTime,c.user_name,c.pay_time,c.trade_no,c.channel_name,c.accessTime',$data['page'],$data['limit']);
        $list =$db_house_village_park_coupon->getCouponListss($where,'mc.*',$data['page'],$data['limit']);

        foreach ($list as &$val){
            $val['user_name']='';
            $val['in_accessTime'] = '--';
            $val['accessTime'] = '--';
            $val['pay_time'] = '--';
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
            $whereArr=array();
            $whereArr[]=array('coupon_id','=',$val['id']);
            $whereArr[]=array('order_id','<>','');
            $whereArr[]=array('park_id','=',$data['village_id']);
            $whereArr[]=array('car_number','=',$val['car_number']);
            $whereArr[]=array('accessType','=',2);
            $outCarAccessRecordObj=$houseVillageCarAccessRecord->getOne($whereArr,'accessTime,user_name,pay_time,trade_no,park_id,channel_name,order_id,accessType');
            if($outCarAccessRecordObj && !$outCarAccessRecordObj->isEmpty()){
                $outCarAccessRecord=$outCarAccessRecordObj->toArray();
                if($outCarAccessRecord['user_name']){
                    $val['user_name']=$outCarAccessRecord['user_name'];
                }
                $val['accessTime'] = $outCarAccessRecord['accessTime'] ? date('Y-m-d H:i:s',$outCarAccessRecord['accessTime']) : '--';
                $val['pay_time'] = $outCarAccessRecord['pay_time']>100 ? date('Y-m-d H:i:s',$outCarAccessRecord['pay_time']) : $val['accessTime'];
                $whereArr=array();
                $whereArr[]=array('order_id','=',$outCarAccessRecord['order_id']);
                $whereArr[]=array('park_id','=',$data['village_id']);
                $whereArr[]=array('car_number','=',$val['car_number']);
                $whereArr[]=array('accessType','=',1);
                $inCarAccessRecordObj=$houseVillageCarAccessRecord->getOne($whereArr,'accessTime,user_name,pay_time,trade_no,park_id,channel_name,order_id,accessType');
                if($inCarAccessRecordObj && !$inCarAccessRecordObj->isEmpty()) {
                    $inCarAccessRecord = $inCarAccessRecordObj->toArray();
                    $val['in_accessTime'] = date('Y-m-d H:i:s',$inCarAccessRecord['accessTime']);
                }
            }
            if (!empty($val['user_id'])){
                $user_info=$db_user->getOne(['uid'=>$val['user_id']],'nickname');
                if (!empty($user_info)){
                    $val['user_name']= $user_info['nickname'];
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    /**
     * 在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getInParkList($data,$hidephoe=false){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessType','=',1];
        $where[] = ['is_out','=',0];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];

            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $count =$db_house_village_car_access_record->getCount($where);
        $list =$db_house_village_car_access_record->get_list($where,'park_car_type,record_id,user_name,user_phone,car_number,accessTime,park_name,channel_id,channel_number',$data['page'],$data['limit']);
        foreach ($list as &$val){
            $val['car_type']='汽车';
            if($val['accessTime']){
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['accessTime'] = '--';
            }
            if($hidephoe && isset($val['user_phone']) && !empty($val['user_phone'])){
                $val['user_phone']=$houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }
            $whereArr=array();
            if(!empty($val['channel_id'])){
                $whereArr[]=array('id','=',$val['channel_id']);
            }else if(!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            if($whereArr){
                $whereArr[]=array('village_id','=',$data['village_id']);
                $garage_num=$this->getGarageByParkPassage($whereArr);
                if($garage_num){
                    $val['park_name']=$garage_num;
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type']='';
        return $data1;
    }

    public function getCarInParkInfo($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['record_id','=',$data['record_id']];
        $where[] = ['park_id','=',$data['village_id']];
        $info =$db_house_village_car_access_record->getOne($where);
        if (!empty($info)){
            $info['accessTime']=date('Y-m-d',$info['accessTime']);
            if (!empty($info['accessImage'])){
                if (stripos($info['accessImage'], 'http://') !== false||stripos($info['accessImage'], 'https://') !== false){
                    $info['in_accessImage'] =$info['accessImage'];
                }else{
                    $accessImageArr=explode('/upload/',$info['accessImage']);
                    $info['accessImage']='/upload/'.$accessImageArr['1'];
                    
                    $path=explode('/',$info['accessImage']);
                    if (count($path)>2&&$path[2]=='meeting'){
                        $info['in_accessImage']=replace_file_domain($info['accessImage']);
                    }else{
                        $info['in_accessImage']=replace_file_domain($info['accessImage']);
//                        $info['in_accessImage'] = cfg('site_url').$info['accessImage'];
                    }
                }
                
            }elseif(!empty($info['accessBigImage'])){
                if (stripos($info['accessBigImage'], 'http://') !== false||stripos($info['accessBigImage'], 'https://') !== false){
                    $info['in_accessImage'] =$info['accessBigImage'];
                }else {
                    $accessImageArr=explode('/upload/',$info['accessBigImage']);
                    $info['accessBigImage']='/upload/'.$accessImageArr['1'];
                    $path = explode('/', $info['accessBigImage']);
                    if (count($path) > 2 && $path[2] == 'meeting') {
                        $info['in_accessImage'] = replace_file_domain($info['accessBigImage']);
                    } else {
                        $info['in_accessImage'] = replace_file_domain($info['accessBigImage']);
//                        $info['in_accessImage'] = cfg('site_url') . $info['accessBigImage'];
                    }
                }

            }
            if (empty($info['in_accessImage'])){
                $info['in_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';

            }

        }
        return $info;
    }

    public function getOutParkInfo($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['record_id','=',$data['record_id']];
        $where[] = ['park_id','=',$data['village_id']];
        $info =$db_house_village_car_access_record->getOne($where);
        if (!empty($info)){
            $info['accessTime']=date('Y-m-d H:i:s',$info['accessTime']);
        }
        return $info;
    }

    public function editOutParkInfo($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['record_id','=',$data['record_id']];
        $where[] = ['park_id','=',$data['village_id']];
        $info =$db_house_village_car_access_record->getOne($where);
        if (empty($info)){
            throw new Exception('出场纪录不存在');
        }
        $db_house_village_car_access_record->saveOne($where,['label_id'=>$data['label_id']]);
        return $info;
    }

    public function editInParkInfo($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['record_id','=',$data['record_id']];
        $where[] = ['park_id','=',$data['village_id']];
        $info =$db_house_village_car_access_record->getOne($where);
        if (empty($info)){
            throw new \think\Exception("入场信息不存在");
        }
        if (!empty($data['accessTime'])){
            $data['accessTime']=strtotime($data['accessTime']);
        }else{
            $data['accessTime']=$info['accessTime'];
        }
        $data_park=[
            'car_number'=>$data['car_number'],
            'user_phone'=>$data['user_phone'],
            'accessTime'=>$data['accessTime'],
        ];
       $res= $db_house_village_car_access_record->saveOne($where,$data_park);
        return $res;
    }
    /**
     * 月租车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getMonthParkList($data,$hidephone=false){
        if (isset($data['date_type']) && $data['date_type']==2){
            $dataTmp=$this->getMonthOutParkList($data);
            return $dataTmp;
            exit();
        }
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessMode','=',5];
        $where[]=['accessType','=',1];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];

            }
        }
        $count =$db_house_village_car_access_record->getCount($where);
        $list =$db_house_village_car_access_record->get_list($where,'park_car_type,park_time,order_id,park_name,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_number,channel_name',$data['page'],$data['limit']);

        $db_house_village_parking_car=new HouseVillageParkingCar();
        $carParkCarTypeArr = [];
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        foreach ($list as &$val){
            $out_info=[];
            if (!empty($val['order_id'])){
                $out_info_obj=$db_house_village_car_access_record->getOne(['order_id'=>$val['order_id'],'accessType'=>2],'park_time,order_id,accessType,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_number,channel_name');
                if($out_info_obj && !$out_info_obj->isEmpty()){
                    $out_info=$out_info_obj->toArray();
                }
            }
            if($out_info){
                $val['park_time']=$out_info['park_time']>0?$out_info['park_time']:$val['park_time'];
            }
            $val['accessType'] =$val['accessType']==1?'进场':'出场';
            if($val['accessTime']>1){
                $val['in_accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['in_accessTime'] = '--';
            }
            if($out_info && $out_info['accessTime']>1){
                $val['out_accessTime'] = date('Y-m-d H:i:s',$out_info['accessTime']);
            }else{
                $val['out_accessTime'] = '--';
            }
            if(!empty($out_info && $out_info['channel_name'])){
                $val['out_channel_name'] = $out_info['channel_name'];
            }else{
                $val['out_channel_name'] = ' ';
            }
            if(!empty($val['channel_name'])){
                $val['in_channel_name'] = $val['channel_name'];
            }else{
                $val['in_channel_name'] = ' ';
            }
            if($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])){
                $val['user_phone'] = $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }

            if (!empty($val['park_time'])){
                $hours=intval($val['park_time']/3600);
                $minter=ceil(($val['park_time']-$hours*3600)/60);
                if ($val['park_time']>=3600){
                    $val['park_time']=$hours.'小时'.$minter.'分钟';
                }else{
                    $val['park_time']=$minter.'分钟';
                }

            }
            $whereArr=array();
            if(!empty($val['channel_id'])){
                $whereArr[]=array('id','=',$val['channel_id']);
            }else if(!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            if($whereArr){
                $whereArr[]=array('village_id','=',$data['village_id']);
                $garage_num=$this->getGarageByParkPassage($whereArr);
                if($garage_num){
                    $val['park_name']=$garage_num;
                }
            }
            $car_number = isset($val['car_number']) ? trim($val['car_number']) : '';
            if (isset($val['park_car_type']) || !$val['park_car_type']) {
                if (isset($carParkCarTypeArr[$car_number]) && $carParkCarTypeArr[$car_number]) {
                    $val['park_car_type'] = $carParkCarTypeArr[$car_number];
                } else {
                    $province = mb_substr($car_number, 0, 1);
                    $car_no = mb_substr($car_number, 1);
                    $whereCar = ['province' => $province, 'car_number' => $car_no,'village_id'=>$data['village_id']];
                    $car_info = $db_house_village_parking_car->getFind($whereCar, 'car_id, parking_car_type');
                    $parking_car_type = isset($car_info['parking_car_type']) ? $car_info['parking_car_type'] : '';
                    $val['park_car_type'] = $this->parking_a11_car_type_arr[$parking_car_type] ?? '';
                    $val['park_car_type'] && $carParkCarTypeArr[$car_number] = $val['park_car_type'];
                }
            } else {
                $carParkCarTypeArr[$car_number] = $val['park_car_type'];
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type']='';
        return $data1;
    }

    public function getMonthOutParkList($data, $hidephone = false)
    {

        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();

        $where = [];
        $where[] = ['park_id', '=', $data['village_id']];
        $where[] = ['accessMode', '=', 5];
       // $where[] = ['b.accessType', '=', 1];
       // $where[] = ['b.del_time', '<', 1];
        $where[] = ['accessType', '=', 2];
        //$where[] = ['a.order_id', '<>', ''];
        $where[] = ['del_time', '<', 1];
        //出场时间
        if (!empty($data['outdate']) && count($data['outdate']) > 0) {
            if (!empty($data['outdate'][0])) {
                $where[] = ['accessTime', '>=', strtotime($data['outdate'][0] . ' 00:00:00')];
            }
            if (!empty($data['outdate'][1])) {
                $where[] = ['accessTime', '<=', strtotime($data['outdate'][1] . ' 23:59:59')];
            }
        }
        //进场时间
        /*
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        */
        if (!empty($data['param']) && !empty($data['value'])) {
            if ($data['param'] == 1) {
                $where[] = ['user_name', 'like', '%' . $data['value'] . '%'];
            } elseif ($data['param'] == 2) {
                $where[] = ['user_phone', 'like', '%' . $data['value'] . '%'];

            } elseif ($data['param'] == 3) {
                $where[] = ['car_number', 'like', '%' . $data['value'] . '%'];

            }
        }
        //$count = $db_house_village_car_access_record->getCounts($where);
        
        $count =$db_house_village_car_access_record->getCount($where);
        $listObj =$db_house_village_car_access_record->get_list($where,'*', $data['page'], $data['limit']);
            
        //$fieldStr = 'a.*,b.exception_type as i_exception_type,b.park_time as i_park_time,b.accessType as i_accessType,b.record_id as i_record_id,b.accessMode as i_accessMode,b.user_name as i_user_name, b.user_phone as i_user_phone, b.accessTime as i_accessTime, b.channel_id as i_channel_id, b.channel_name as i_channel_name,b.channel_number as i_channel_number';
        //$listObj = $db_house_village_car_access_record->getOutList($where, $fieldStr, $data['page'], $data['limit'], 'a.accessTime DESC,a.record_id DESC');
        $list = '';
        if ($listObj && !$listObj->isEmpty()) {
            $list = $listObj->toArray();
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $carParkCarTypeArr = [];
        if ($list) {
            $fieldInStr = 'record_id,business_id,car_number,accessType,accessTime,exception_type,park_time,accessMode,user_name,user_phone,channel_id,channel_name,channel_number';
            foreach ($list as &$val) {
                $val['o_record_id'] = $val['record_id'];
                $in_info = [];
                $in_where = array();
                $in_where[] = ['accessTime', '<', $val['accessTime']];
                $in_where[] = ['park_id', '=', $data['village_id']];
                $in_where[] = ['car_number', '=', $val['car_number']];
                $in_where[] = ['accessType', '=', 1];
                $in_where[] = ['del_time', '<', 1];
                if (!empty($val['order_id'])) {
                    $in_where[] = ['order_id', '=', $val['order_id']];
                }
                $in_info_obj = $db_house_village_car_access_record->getOne($in_where, $fieldInStr, 'record_id DESC');
                if ($in_info_obj && !$in_info_obj->isEmpty()) {
                    $in_info = $in_info_obj->toArray();
                }
                $val['park_time'] = $val['park_time'] ? $val['park_time'] : 0;
                $val['in_accessTime'] = '--';
                $val['in_channel_name'] = ' ';
                $in_exception_type=0;
                if ($in_info && isset($in_info['record_id'])) {
                    $park_time = $val['accessTime'] - $in_info['accessTime'];
                    $val['park_time'] = $park_time > 0 ? $park_time : 0;
                    $val['record_id'] = $in_info['record_id'];
                    $in_exception_type=$in_info['exception_type'];
                    if ($in_info['accessTime'] > 100) {
                        $val['in_accessTime'] = date('Y-m-d H:i:s', $in_info['accessTime']);
                    }
                    if (!empty($in_info['channel_name'])) {
                        $val['in_channel_name'] = $in_info['channel_name'];
                    }
                    if ($hidephone && !empty($in_info['user_phone'])) {
                        $val['user_phone'] = $in_info['user_phone'];
                        
                    }
                    if (empty($val['user_name']) && !empty($in_info['user_name'])) {
                        $val['user_name'] = $in_info['user_name'];
                    }
                    $whereArr = array();
                    if (!empty($in_info['channel_id'])) {
                        $whereArr[] = array('id', '=', $in_info['channel_id']);
                    } else if (!empty($in_info['channel_number'])) {
                        $whereArr[] = array('channel_number', '=', $in_info['channel_number']);
                    }
                    if ($whereArr) {
                        $whereArr[] = array('village_id', '=', $data['village_id']);
                        $garage_num = $this->getGarageByParkPassage($whereArr);
                        if ($garage_num) {
                            $val['park_name'] = $garage_num;
                        }
                    }
                }
                if ($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])) {
                    $val['user_phone'] =  $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
                }
                $val['record_id_index'] = $val['record_id'] . '_' . $val['o_record_id'];
                $val['accessType'] = '出场';
                if ($val['accessTime'] > 100) {
                    $val['out_accessTime'] = date('Y-m-d H:i:s', $val['accessTime']);
                } else {
                    $val['out_accessTime'] = '--';
                }
                if (!empty($val['channel_name'])) {
                    $val['out_channel_name'] = $val['channel_name'];
                } else {
                    $val['out_channel_name'] = ' ';
                }
                if (!empty($val['park_time'])) {
                    $hours = intval($val['park_time'] / 3600);
                    $minter = ceil(($val['park_time'] - $hours * 3600) / 60);
                    if ($val['park_time'] >= 3600) {
                        $val['park_time'] = $hours . '小时' . $minter . '分钟';
                    } else {
                        $val['park_time'] = $minter . '分钟';
                    }
                } else {
                    $val['park_time'] = '--';
                    if (!empty($val['exception_type'])) {
                        $val['park_time'] .= ($val['exception_type'] == 2 ? '(出场异常)' : '');
                    } elseif (!empty($in_exception_type)) {
                        $val['park_time'] .= ($in_exception_type == 1 ? '(入场异常)' : '');
                    }

                }
                $car_number = isset($val['car_number']) ? trim($val['car_number']) : '';
                if (isset($val['park_car_type']) || !$val['park_car_type']) {
                    if (isset($carParkCarTypeArr[$car_number]) && $carParkCarTypeArr[$car_number]) {
                        $val['park_car_type'] = $carParkCarTypeArr[$car_number];
                    } else {
                        $province = mb_substr($car_number, 0, 1);
                        $car_no = mb_substr($car_number, 1);
                        $whereCar = ['province' => $province, 'car_number' => $car_no, 'village_id' => $data['village_id']];
                        $car_info = $db_house_village_parking_car->getFind($whereCar, 'car_id, parking_car_type');
                        $parking_car_type = isset($car_info['parking_car_type']) ? $car_info['parking_car_type'] : '';
                        $val['park_car_type'] = $this->parking_a11_car_type_arr[$parking_car_type] ?? '';
                        $val['park_car_type'] && $carParkCarTypeArr[$car_number] = $val['park_car_type'];
                    }
                } else {
                    $carParkCarTypeArr[$car_number] = $val['park_car_type'];
                }
            }
        }
        $data1 = [];
        $data1['list'] = $list ? $list:array();
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type'] = '';
        return $data1;
    }
    
    public function getMonthParkInfo($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['record_id','=',$data['record_id']];
        $where[] = ['park_id','=',$data['village_id']];
        $info =$db_house_village_car_access_record->getOne($where);
        if (!empty($info)){
            $where_data=[];
            $where_data['order_id']=$info['order_id'];
            if ($info['accessType']==1){
                $where_data['accessType']=2;
                $info1 =$db_house_village_car_access_record->getOne($where_data);
                if (!empty($info1)){
                    $info['out_accessTime']=date('Y-m-d H:i:s',$info1['accessTime']);
                    $info['out_channel_name']=$info1['channel_name'];
                    $info['out_accessImage']=$info1['accessImage'];
                    $info['out_accessBigImage']=$info1['accessBigImage'];
                    if (!empty($info['out_accessBigImage'])){
                        if (stripos($info['out_accessBigImage'], 'http://') !== false||stripos($info['out_accessBigImage'], 'https://') !== false){
                            $info['out_accessImage'] =$info['out_accessBigImage'];
                        }else {
                            $accessImageArr=explode('/upload/',$info['out_accessBigImage']);
                            $info['out_accessBigImage']='/upload/'.$accessImageArr['1'];
                            $path = explode('/', $info['out_accessBigImage']);
                            if (count($path) > 2 && $path[2] == 'meeting') {
                                $info['out_accessImage'] = replace_file_domain($info['out_accessBigImage']);
                            } else {
                                $info['out_accessImage'] = replace_file_domain($info['out_accessBigImage']);
//                                $info['out_accessImage'] = cfg('site_url') . $info['out_accessBigImage'];
                            }
                        }

                    }
                    if (!empty($info['out_accessImage'])){
                        if (stripos($info['out_accessImage'], 'http://') !== false||stripos($info['out_accessImage'], 'https://') !== false){

                        }else {
                            $accessImageArr=explode('/upload/',$info['out_accessImage']);
                            $info['out_accessImage']='/upload/'.$accessImageArr['1'];
                            $path = explode('/', $info['out_accessImage']);
                            if (count($path) > 2 && $path[2] == 'meeting') {
                                $info['out_accessImage'] = replace_file_domain($info['out_accessImage']);
                            } else {
//                                $info['out_accessImage'] = cfg('site_url') . $info['out_accessImage'];
                                $info['out_accessImage'] = replace_file_domain($info['out_accessImage']);
                            }
                        }

                    }

                 }else{
                    $info['out_accessTime']='--';
                    $info['out_channel_name']='--';
                    $info['out_accessImage']=cfg('site_url').'/static/images/house/park/park.png';
                    $info['out_accessBigImage']=cfg('site_url').'/static/images/house/park/park.png';
                }

                if($info['accessTime']>1){
                    $info['in_accessTime'] = date('Y-m-d H:i:s',$info['accessTime']);
                }else{
                    $info['in_accessTime'] = '--';
                }
                if(!empty($info['channel_name'])){
                    $info['in_channel_name'] = $info['channel_name'];
                }else{
                    $info['in_channel_name'] = ' ';
                }
                if(!empty($info['accessImage'])){
                    if (stripos($info['accessImage'], 'http://') !== false||stripos($info['accessImage'], 'https://') !== false){
                        $info['in_accessImage'] =$info['accessImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessImage']);
                        $info['accessImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['in_accessImage'] = replace_file_domain($info['accessImage']);
                        } else {
//                            $info['in_accessImage'] = cfg('site_url')  . $info['accessImage'];
                            $info['in_accessImage'] = replace_file_domain($info['accessImage']);
                        }
                    }
                }
                if(!empty($info['accessBigImage'])){
                    if (stripos($info['accessBigImage'], 'http://') !== false||stripos($info['accessBigImage'], 'https://') !== false){
                        $info['in_accessImage'] =$info['accessBigImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessBigImage']);
                        $info['accessBigImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessBigImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['in_accessImage'] = replace_file_domain($info['accessBigImage']);
                        } else {
                            $info['in_accessImage'] = replace_file_domain($info['accessBigImage']);
//                            $info['in_accessImage'] = cfg('site_url') . $info['accessBigImage'];
                        }
                    }

                }
                if(empty($info['in_accessImage'])){
                    $info['in_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';
                }
            }else{
                $where_data['accessType']=1;
                $info1 =$db_house_village_car_access_record->getOne($where_data);
                if (!empty($info1)){
                    $info['in_accessTime']=date('Y-m-d H:i:s',$info1['accessTime']);
                    $info['in_channel_name']=$info1['channel_name'];
                    $info['in_accessImage']=cfg('site_url').$info1['accessImage'];
                    $info['in_accessBigImage']=cfg('site_url').$info1['accessBigImage'];
                    $info['in_accessImage']=replace_file_domain($info1['accessImage']);
                    $info['in_accessBigImage']=replace_file_domain($info1['accessBigImage']);
                 }else{
                    $info['in_accessTime']='--';
                    $info['in_channel_name']='--';
                    $info['in_accessImage']=cfg('site_url').'/static/images/house/park/park.png';
                    $info['in_accessBigImage']=cfg('site_url').'/static/images/house/park/park.png';
                }
                if($info['accessTime']>1){
                    $info['out_accessTime'] = date('Y-m-d H:i:s',$info['accessTime']);
                }else{
                    $info['out_accessTime'] = '--';
                }
                if(!empty($info['channel_name'])){
                    $info['out_channel_name'] = $info['channel_name'];
                }else{
                    $info['out_channel_name'] = ' ';
                }
                if(!empty($info['accessImage'])){
                    if (stripos($info['accessImage'], 'http://') !== false||stripos($info['accessImage'], 'https://') !== false){
                        $info['out_accessImage'] =$info['accessImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessImage']);
                        $info['accessImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['out_accessImage'] = replace_file_domain($info['accessImage']);
                        } else {
//                            $info['out_accessImage'] = cfg('site_url') . $info['accessImage'];
                            $info['out_accessImage'] = replace_file_domain($info['accessImage']);
                        }
                    }
                }
                if(!empty($info['accessBigImage'])){
                    if (stripos($info['accessBigImage'], 'http://') !== false||stripos($info['accessBigImage'], 'https://') !== false){
                        $info['out_accessImage'] =$info['accessBigImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessBigImage']);
                        $info['accessBigImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessBigImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['out_accessImage'] = replace_file_domain($info['accessBigImage']);
                        } else {
//                            $info['out_accessImage'] = cfg('site_url')  . $info['accessBigImage'];
                            $info['out_accessImage'] = replace_file_domain($info['accessBigImage']);
                        }
                    }
                }
                if(empty($info['out_accessImage'])){
                    $info['out_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';
                }
            }

        }
        return $info;
    }

    /**
     * 临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTempParkList($data,$hidephone=false){
        
        if (isset($data['date_type']) && $data['date_type']==2){
            $dataTmp=$this->getTempOutParkList($data,$hidephone);
            return $dataTmp;
            exit();
        }
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $whereOut = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessMode','in',[3,4,6,9,7]];
        $where[] = ['accessType','=',1];
        $where[] = ['del_time','<',1];
        $whereOut[] = ['park_id','=',$data['village_id']];
        $whereOut[] = ['accessType','=',2];
        $whereOut[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];
                $whereOut[] = ['car_number','like','%'.$data['value'].'%'];
            }
        }
        if(isset($data['place_value'])&&!empty($data['place_value'])){
            if($data['place_type']==2){
                $where[] = ['channel_name','like','%'.$data['place_value'].'%'];
            }else{
                $where[] = ['park_name','like','%'.$data['place_value'].'%'];
            }
        }
        $count = $db_house_village_car_access_record->getCount($where);
        $whereArr=$where;
        foreach ($whereArr as $kk=>$wvv){
            $whereArr[$kk]['0']='a.'.$wvv['0'];
        }
        $whereArr[] = ['a.order_id', '<>', ''];
        $whereArr[] = ['b.accessType', '=', 2];
        //$allTotalMoney  = $db_house_village_car_access_record->getSum($whereArr, 'total');
        $allTotalMoney  = $db_house_village_car_access_record->getSums($whereArr,'b.total');
        //$allTotalMoney1 = $db_house_village_car_access_record->getSum($whereArr, 'prepayTotal');
        $allTotalMoney1 = $db_house_village_car_access_record->getSums($whereArr,'b.prepayTotal');
        $allTotalMoney += $allTotalMoney1;
        fdump_api([$allTotalMoney, $allTotalMoney1, $whereArr], '$allTotalMoney');
        if ($allTotalMoney > 0) {
            $allTotalMoney = round($allTotalMoney, 2);
        } else {
            $allTotalMoney = 0.00;
        }
        $list = $db_house_village_car_access_record->getList($where,true,$data['page'],$data['limit'],'accessTime desc');
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        fdump_api([$where, $list], '$list');
    
        $inParkOrderIdWhereArr = $db_house_village_car_access_record->getColumn($where, 'order_id');
        fdump_api($inParkOrderIdWhereArr, '$inParkOrderIdWhereArr');
        if (!empty($inParkOrderIdWhereArr)) {
            $whereOut[] = ['order_id', 'in', $inParkOrderIdWhereArr];
        }
        $fieldOut = 'order_id, record_id, exception_type, total, prepayTotal, park_time, accessType, accessMode, user_name, user_phone, accessTime, channel_id, channel_name,channel_number';
        $outOrderIdList = $db_house_village_car_access_record->getList($whereOut, $fieldOut, 0, 0, 'total DESC,prepayTotal DESC, record_id DESC');
        if ($outOrderIdList && !is_array($outOrderIdList)) {
            $outOrderIdList = $outOrderIdList->toArray();
        }
        fdump_api($outOrderIdList, '$outOrderIdList');
        $outOrderArr = [];
        foreach ($outOrderIdList as $item) {
            if (!isset($outOrderArr[$item['order_id']])) {
                $outOrderArr[$item['order_id']] = $item;
            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        
       // $field='record_id,park_name,order_id,accessType,accessImage,accessBigImage,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name';
//        $field='a.*,b.exception_type as out_exception_type,b.total,b.park_time,b.accessType as out_accessType,b.record_id as out_record_id,b.accessMode as out_accessMode,b.user_name as out_user_name,b.user_phone as out_user_phone,b.accessTime as out_accessTime,b.channel_id as out_channel_id,b.channel_name as out_channel_name';
//        $list =$db_house_village_car_access_record->getLists($where,$field,$data['page'],$data['limit']);
        foreach ($list as &$val){
            
            $order_id = $val['order_id'];
            $outPark = isset($outOrderArr[$order_id]) && $outOrderArr[$order_id] ? $outOrderArr[$order_id] : [];
            $val['out_exception_type'] = isset($outPark['exception_type']) ? $outPark['exception_type'] : 0;
            $total       = isset($outPark['total'])       ? $outPark['total']        : 0;
            $prepayTotal = isset($outPark['prepayTotal']) ? $outPark['prepayTotal']  : 0;
            $val['total'] = $total + $prepayTotal;
            $val['park_time']        = isset($outPark['park_time'])    ? $outPark['park_time']    : 0;
            $val['out_accessType']   = isset($outPark['accessType'])   ? $outPark['accessType']   : 2;
            $val['out_record_id']    = isset($outPark['record_id'])    ? $outPark['record_id']    : 0;
            $val['out_accessMode']   = isset($outPark['accessMode'])   ? $outPark['accessMode']   : 0;
            $val['out_user_name']    = isset($outPark['user_name'])    ? $outPark['user_name']    : '';
            $val['out_user_phone']   = isset($outPark['user_phone'])   ? $outPark['user_phone']   : '';
            $val['out_accessTime']   = isset($outPark['accessTime'])   ? $outPark['accessTime']   : '';
            $val['out_channel_id']   = isset($outPark['channel_id'])   ? $outPark['channel_id']   : '';
            $val['out_channel_name'] = isset($outPark['channel_name']) ? $outPark['channel_name'] : '';
            if (isset($val['accessTime']) && $val['accessTime'] && isset($outPark['accessTime']) && $outPark['accessTime']) {
                $park_time = $outPark['accessTime'] - $val['accessTime'];
                $val['park_time']    = $park_time > 0 ? $park_time : 0;
            }
            $val['record_id_index']=$val['record_id'];
            $val['accessType'] =$val['accessType']==1?'进场':'出场';

            if($val['accessTime']>1){
                $val['in_accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['in_accessTime'] = '--';
            }
            if (!empty($val['channel_name'])){
                $val['in_channel_name']=$val['channel_name'];
            }else{
                $val['in_channel_name']='';
            }
            if( $val['out_accessTime']>1){
                $val['out_accessTime'] = date('Y-m-d H:i:s', $val['out_accessTime']);
            }else{
                $val['out_accessTime'] = '--';
            }
            if (!empty($val['out_user_name'])){
                $val['user_name']=$val['out_user_name'];
            }
            if (!empty($val['out_user_phone'])){
                $val['user_phone']=$val['out_user_phone'];
            }
            if (!empty($val['park_time'])){
                $hours=intval($val['park_time']/3600);
                $minter=ceil(($val['park_time']-$hours*3600)/60);
                if ($val['park_time']>=3600){
                    $val['park_time']=$hours.'小时'.$minter.'分钟';
                }else{
                    $val['park_time']=$minter.'分钟';
                }

            }else{
                $val['park_time'] = '--';
                if (!empty($val['out_exception_type'])){
                    $val['park_time'].= ($val['out_exception_type']==2?'(出场异常)':'');
                }elseif (!empty($val['exception_type'])){
                    $val['park_time'].= ($val['exception_type']==1?'(入场异常)':'');
                }
                
            }
            if($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])){
                $val['user_phone'] = $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }
            $whereArr=array();
            if(!empty($val['channel_id'])){
                $whereArr[]=array('id','=',$val['channel_id']);
            }else if(!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            if($whereArr){
                $whereArr[]=array('village_id','=',$data['village_id']);
                $garage_num=$this->getGarageByParkPassage($whereArr);
                if($garage_num){
                    $val['park_name']=$garage_num;
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['all_total_money'] = $allTotalMoney;
        $data1['park_sys_type']='';
        return $data1;
    }
    /**
     * 临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */

    public function getTempOutParkList($data, $hidephone = false)
    {
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();

        $where = [];
        $where[] = ['a.park_id', '=', $data['village_id']];

        $where[] = ['b.accessMode', 'in', [3, 4, 6, 9, 7]];
        $where[] = ['a.accessType', '=', 1];
        $where[] = ['a.del_time', '<', 1];
        $where[] = ['b.accessType', '=', 2];
        $where[] = ['b.park_id', '=', $data['village_id']];
        $where[] = ['b.order_id', '<>', ''];
        $where[] = ['b.del_time', '<', 1];
        if (isset($data['pay_status']) && $data['pay_status'] == 'free') {
            $where[] = ['b.total', '=', 0];
        } else if (isset($data['pay_status']) && $data['pay_status'] == 'paied') {
            $where[] = ['b.total', '>', 0];
        }
        if (isset($data['stop_time']) && $data['stop_time'] > 0) {
            //小时转化成秒
            $stop_time = $data['stop_time'] * 3600;
            $where['_string'] = '(b.accessTime-a.accessTime)<=' . $stop_time;
        }
        //出场时间
        if (!empty($data['outdate']) && count($data['outdate']) > 0) {
            if (!empty($data['outdate'][0])) {
                $where[] = ['b.accessTime', '>=', strtotime($data['outdate'][0] . ' 00:00:00')];
            }
            if (!empty($data['outdate'][1])) {
                $where[] = ['b.accessTime', '<=', strtotime($data['outdate'][1] . ' 23:59:59')];
            }
        }
        //进场时间
        /*
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['a.accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['a.accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        */
        if (!empty($data['param']) && !empty($data['value'])) {
            if ($data['param'] == 1) {
                $where[] = ['a.user_name', 'like', '%' . $data['value'] . '%'];
            } elseif ($data['param'] == 2) {
                $where[] = ['a.user_phone', 'like', '%' . $data['value'] . '%'];

            } elseif ($data['param'] == 3) {
                $where[] = ['b.car_number', 'like', '%' . $data['value'] . '%'];
            }
        }
        if (isset($data['place_value']) && !empty($data['place_value'])) {
            if ($data['place_type'] == 2) {
                $where[] = ['a.channel_name', 'like', '%' . $data['place_value'] . '%'];
            } else {
                $where[] = ['a.park_name', 'like', '%' . $data['place_value'] . '%'];
            }
        }
        $count = $db_house_village_car_access_record->getCounts($where);
        $allTotalMoney = $db_house_village_car_access_record->getSums($where, 'b.total');
        $allTotalMoney1 = $db_house_village_car_access_record->getSums($where, 'b.prepayTotal');
        $allTotalMoney += $allTotalMoney1;
        if ($allTotalMoney > 0) {
            $allTotalMoney = round($allTotalMoney, 2);
        } else {
            $allTotalMoney = 0.00;
        }
        $fieldStr = 'a.*,b.total as o_total,b.exception_type as o_exception_type,b.prepayTotal as o_prepayTotal,b.park_time as o_park_time,b.accessType as o_accessType,b.record_id as o_record_id,b.accessMode as o_accessMode,b.user_name as o_user_name, b.user_phone as o_user_phone, b.accessTime as o_accessTime, b.channel_id as o_channel_id, b.channel_name as o_channel_name';
        $listObj = $db_house_village_car_access_record->getOutList($where, $fieldStr, $data['page'], $data['limit'], 'b.accessTime DESC, b.total DESC,b.record_id DESC');
        $list = '';
        if ($listObj && !$listObj->isEmpty()) {
            $list = $listObj->toArray();
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        if ($list) {
            foreach ($list as &$val) {
                $val['out_exception_type'] = $val['o_exception_type'] ? $val['o_exception_type'] : 0;
                $total = $val['o_total'] ? $val['o_total'] : 0;
                $prepayTotal = $val['o_prepayTotal'] ? $val['o_prepayTotal'] : 0;
                $val['total'] = $total + $prepayTotal;
                $val['park_time'] = $val['o_park_time'] ? $val['o_park_time'] : 0;
                $val['out_accessType'] = $val['o_accessType'] ? $val['o_accessType'] : 2;
                $val['out_record_id'] = $val['o_record_id'] ? $val['o_record_id'] : 0;
                $val['out_accessMode'] = $val['o_accessMode'] ? $val['o_accessMode'] : 0;
                $val['out_user_name'] = $val['o_user_name'] ? $val['o_user_name'] : '';
                $val['out_user_phone'] = $val['o_user_phone'] ? $val['o_user_phone'] : '';
                $val['out_accessTime'] = $val['o_accessTime'] ? $val['o_accessTime'] : '';
                $val['out_channel_id'] = $val['o_channel_id'] ? $val['o_channel_id'] : '';
                $val['out_channel_name'] = $val['o_channel_name'] ? $val['o_channel_name'] : '';
                if (isset($val['accessTime']) && $val['accessTime'] && isset($val['o_accessTime']) && $val['o_accessTime']) {
                    $park_time = $val['o_accessTime'] - $val['accessTime'];
                    $val['park_time'] = $park_time > 0 ? $park_time : 0;
                }
                $val['record_id_index'] = $val['record_id'] . '_' . $val['out_record_id'];
                $val['accessType'] = '出场';

                if ($val['accessTime'] > 1) {
                    $val['in_accessTime'] = date('Y-m-d H:i:s', $val['accessTime']);
                } else {
                    $val['in_accessTime'] = '--';
                }
                if (!empty($val['channel_name'])) {
                    $val['in_channel_name'] = $val['channel_name'];
                } else {
                    $val['in_channel_name'] = '';
                }
                if ($val['out_accessTime'] > 1) {
                    $val['out_accessTime'] = date('Y-m-d H:i:s', $val['out_accessTime']);
                } else {
                    $val['out_accessTime'] = '--';
                }
                if (!empty($val['out_user_name'])) {
                    $val['user_name'] = $val['out_user_name'];
                }
                if (!empty($val['out_user_phone'])) {
                    $val['user_phone'] = $val['out_user_phone'];
                }
                if (!empty($val['user_phone']) && $hidephone) {
                    $val['user_phone'] =$houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
                }
                if (!empty($val['park_time'])) {
                    $hours = intval($val['park_time'] / 3600);
                    $minter = ceil(($val['park_time'] - $hours * 3600) / 60);
                    if ($val['park_time'] >= 3600) {
                        $val['park_time'] = $hours . '小时' . $minter . '分钟';
                    } else {
                        $val['park_time'] = $minter . '分钟';
                    }

                } else {
                    $val['park_time'] = '--';
                    if (!empty($val['out_exception_type'])) {
                        $val['park_time'] .= ($val['out_exception_type'] == 2 ? '(出场异常)' : '');
                    } elseif (!empty($val['exception_type'])) {
                        $val['park_time'] .= ($val['exception_type'] == 1 ? '(入场异常)' : '');
                    }

                }
                $whereArr = array();
                if (!empty($val['channel_id'])) {
                    $whereArr[] = array('id', '=', $val['channel_id']);
                } else if (!empty($val['channel_number'])) {
                    $whereArr[] = array('channel_number', '=', $val['channel_number']);
                }
                if ($whereArr) {
                    $whereArr[] = array('village_id', '=', $data['village_id']);
                    $garage_num = $this->getGarageByParkPassage($whereArr);
                    if ($garage_num) {
                        $val['park_name'] = $garage_num;
                    }
                }
            }
        }
        $data1 = [];
        $data1['list'] = $list ? $list:array();
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['all_total_money'] = $allTotalMoney;
        $data1['park_sys_type'] = '';
        return $data1;
    }
    /**
     * 临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTempParkList1($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessMode','in',[3,4,6,9,7]];
        $where[]=['accessType','=',1];
        $where[]=['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];
            }
        }
        if(isset($data['place_value'])&&!empty($data['place_value'])){
            if($data['place_type']==2){
                $where[] = ['channel_name','like','%'.$data['place_value'].'%'];
            }else{
                $where[] = ['park_name','like','%'.$data['place_value'].'%'];
            }
        }
        $count =$db_house_village_car_access_record->getCount($where);
        $whereArr=$where;
        foreach ($whereArr as $kk=>$wvv){
            if($wvv['0']=='accessType'){
                $whereArr[$kk]['2']=2;
            }
        }
        $whereArr[]=['order_id','<>',''];
        $allTotalMoney=$db_house_village_car_access_record->getSum($whereArr,'total');
        if($allTotalMoney>0){
            $allTotalMoney=round($allTotalMoney,2);
        }else{
            $allTotalMoney=0.00;
        }
        $list =$db_house_village_car_access_record->get_list($where,'record_id,park_name,park_time,order_id,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name',$data['page'],$data['limit']);
        foreach ($list as &$val){
            $val['accessType'] =$val['accessType']==1?'进场':'出场';
            $out_info=array();
            if (!empty($val['order_id'])){
                $out_info_obj=$db_house_village_car_access_record->getOne(['order_id'=>$val['order_id'],'accessType'=>2],'total,park_time,order_id,accessType,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name');
                if($out_info_obj && !$out_info_obj->isEmpty()){
                    $out_info=$out_info_obj->toArray();
                }
            }
            if($out_info){
                $val['park_time']=$out_info['park_time'];
                $val['total']=$out_info['total'];
            }
            if($val['accessTime']>1){
                $val['in_accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['in_accessTime'] = '--';
            }
            if($out_info && $out_info['accessTime']>1){
                $val['out_accessTime'] = date('Y-m-d H:i:s',$out_info['accessTime']);
            }else{
                $val['out_accessTime'] = '--';
            }
            if($out_info && !empty($out_info['channel_name'])){
                $val['out_channel_name'] = $out_info['channel_name'];
            }else{
                $val['out_channel_name'] = ' ';
            }
            if(!empty($val['channel_name'])){
                $val['in_channel_name'] = $val['channel_name'];
            }else{
                $val['in_channel_name'] = ' ';
            }
            if (!empty($val['park_time'])){
                $hours=intval($val['park_time']/3600);
                $minter=ceil(($val['park_time']-$hours*3600)/60);
                if ($val['park_time']>=3600){
                    $val['park_time']=$hours.'小时'.$minter.'分钟';
                }else{
                    $val['park_time']=$minter.'分钟';
                }

            }

        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['all_total_money'] = $allTotalMoney;

        return $data1;
    }


    public function getTempParkInfo($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        if(isset($data['out_record_id']) && $data['out_record_id']>0){
            $where[] = ['record_id','=',$data['out_record_id']];
        }else{
            $where[] = ['record_id','=',$data['record_id']];
        }
        $where[] = ['park_id','=',$data['village_id']];
        $info =$db_house_village_car_access_record->getOne($where);
        if (!empty($info)){
            $where_data=[];
            $where_data['order_id']=$info['order_id'];
            if ($info['accessType']==1){
                $where_data['accessType']=2;
                $info1 =$db_house_village_car_access_record->getOne($where_data, true, 'total DESC, prepayTotal DESC, record_id DESC');
                if (!empty($info1)){
                    if (isset($info1['total']) && $info1['total']) {
                        $info['total'] = $info1['total'];
                    }
                    if (isset($info1['prepayTotal']) && $info1['prepayTotal']) {
                        $info['total'] += $info1['prepayTotal'];
                    }
                    $info['out_accessTime']=date('Y-m-d H:i:s',$info1['accessTime']);
                    $info['out_channel_name']=$info1['channel_name'];
                    if(!empty($info1['accessImage'])){
                        if (stripos($info1['accessImage'], 'http://') !== false||stripos($info1['accessImage'], 'https://') !== false){
                            $info['out_accessImage'] =$info1['accessImage'];
                        }else {
                            $accessImageArr=explode('/upload/',$info1['accessImage']);
                            $info1['accessImage']='/upload/'.$accessImageArr['1'];
                            $path = explode('/', $info1['accessImage']);
                            if (count($path) > 2 && $path[2] == 'meeting') {
                                $info['out_accessImage'] = replace_file_domain($info1['accessImage']);
                            } else {
                                $info['out_accessImage'] = replace_file_domain($info1['accessImage']);
//                                $info['out_accessImage'] = cfg('site_url') . $info1['accessImage'];
                            }
                        }
                    }
                    if(!empty($info1['accessBigImage'])){
                        if (stripos($info1['accessBigImage'], 'http://') !== false||stripos($info1['accessBigImage'], 'https://') !== false){
                            $info['out_accessImage'] =$info1['accessBigImage'];
                        }else {
                            $accessImageArr=explode('/upload/',$info1['accessBigImage']);
                            $info1['accessBigImage']='/upload/'.$accessImageArr['1'];
                            $path = explode('/', $info1['accessBigImage']);
                            if (count($path) > 2 && $path[2] == 'meeting') {
                                $info['out_accessImage'] = replace_file_domain($info1['accessBigImage']);
                            } else {
                                $info['out_accessImage'] = replace_file_domain($info1['accessBigImage']);
//                                $info['out_accessImage'] = cfg('site_url') . $info1['accessBigImage'];
                            }
                        }

                    } 
                    if(empty($info['out_accessImage'])){
                        $info['out_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';
                    }
                }else{
                    $info['out_accessTime']='--';
                    $info['out_channel_name']='--';
                    $info['out_accessImage']=cfg('site_url').'/static/images/house/park/park.png';
                    $info['out_accessBigImage']=cfg('site_url').'/static/images/house/park/park.png';
                }
                $info['park_time_str']='';
                if($info['accessTime']>1){
                    $info['in_accessTime'] = date('Y-m-d H:i:s',$info['accessTime']);
                    if($info1['accessTime']>$info['accessTime']){
                        $park_time=$info1['accessTime']-$info['accessTime'];
                        $hours=intval($park_time/3600);
                        $minter=ceil(($park_time-$hours*3600)/60);
                        if ($park_time>=3600){
                            $info['park_time_str']=$hours.'小时'.$minter.'分钟';
                        }else{
                            $info['park_time_str']=$minter.'分钟';
                        }
                    }
                }else{
                    $info['in_accessTime'] = '--';
                }
                if(!empty($info['channel_name'])){
                    $info['in_channel_name'] = $info['channel_name'];
                }else{
                    $info['in_channel_name'] = ' ';
                }
                if(!empty($info['accessImage'])){
                    if (stripos($info['accessImage'], 'http://') !== false||stripos($info['accessImage'], 'https://') !== false){
                        $info['in_accessImage'] =$info['accessImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessImage']);
                        $info['accessImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['in_accessImage'] = replace_file_domain($info['accessImage']);
                        } else {
                            $info['in_accessImage'] = replace_file_domain($info['accessImage']);
//                            $info['in_accessImage'] = cfg('site_url')  . $info['accessImage'];
                        }
                    }
                }
                if(!empty($info['accessBigImage'])){
                    if (stripos($info['accessBigImage'], 'http://') !== false||stripos($info['accessBigImage'], 'https://') !== false){
                        $info['in_accessImage'] =$info['accessBigImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessBigImage']);
                        $info['accessBigImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessBigImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['in_accessImage'] = replace_file_domain($info['accessBigImage']);
                        } else {
                            $info['in_accessImage'] = replace_file_domain($info['accessBigImage']);
//                            $info['in_accessImage'] = cfg('site_url')  . $info['accessBigImage'];
                        }
                    }
                }
                if(empty($info['in_accessImage'])){
                    $info['in_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';
                }
            }else{
                $where_data['accessType']=1;
                $info1 =$db_house_village_car_access_record->getOne($where_data);
                if (!empty($info1)){
                    $info['in_accessTime']=date('Y-m-d H:i:s',$info1['accessTime']);
                    $info['in_channel_name']=$info1['channel_name'];
                    if(!empty($info1['accessImage'])){
                        if (stripos($info1['accessImage'], 'http://') !== false||stripos($info1['accessImage'], 'https://') !== false){
                            $info['in_accessImage'] =$info1['accessImage'];
                        }else {
                            $accessImageArr=explode('/upload/',$info1['accessImage']);
                            $info1['accessImage']='/upload/'.$accessImageArr['1'];
                            $path = explode('/', $info1['accessImage']);
                            if (count($path) > 2 && $path[2] == 'meeting') {
                                $info['in_accessImage'] = replace_file_domain($info1['accessImage']);
                            } else {
                                $info['in_accessImage'] = replace_file_domain($info1['accessImage']);
//                                $info['in_accessImage'] = cfg('site_url')  . $info1['accessImage'];
                            }
                        }
                    }else{
                        $info['in_accessImage'] =cfg('site_url').'/static/images/house/park/park.png';
                    }
                    if(!empty($info1['accessBigImage'])){
                        if (stripos($info1['accessBigImage'], 'http://') !== false||stripos($info1['accessBigImage'], 'https://') !== false){
                            $info['in_accessImage'] =$info1['accessBigImage'];
                        }else {
                            $accessImageArr=explode('/upload/',$info1['accessBigImage']);
                            $info1['accessBigImage']='/upload/'.$accessImageArr['1'];
                            $path = explode('/', $info1['accessBigImage']);
                            if (count($path) > 2 && $path[2] == 'meeting') {
                                $info['in_accessImage'] = replace_file_domain($info1['accessBigImage']);
                            } else {
                                $info['in_accessImage'] = replace_file_domain($info1['accessBigImage']);
//                                $info['in_accessImage'] = cfg('site_url')  . $info1['accessBigImage'];
                            }
                        }

                    }
                    if(empty($info['in_accessImage'])){
                        $info['in_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';
                    }

                }else{
                    $info['in_accessTime']='--';
                    $info['in_channel_name']='--';
                    $info['in_accessImage']=cfg('site_url').'/static/images/house/park/park.png';
                    $info['in_accessBigImage']=cfg('site_url').'/static/images/house/park/park.png';
                }
                $info['park_time_str']='';
                if($info['accessTime']>1){
                    $info['out_accessTime'] = date('Y-m-d H:i:s',$info['accessTime']);
                    if($info['accessTime']>$info1['accessTime']){
                        $park_time=$info['accessTime']-$info1['accessTime'];
                        $hours=intval($park_time/3600);
                        $minter=ceil(($park_time-$hours*3600)/60);
                        if ($park_time>=3600){
                            $info['park_time_str']=$hours.'小时'.$minter.'分钟';
                        }else{
                            $info['park_time_str']=$minter.'分钟';
                        }
                    }
                }else{
                    $info['out_accessTime'] = '--';
                }
                if(!empty($info['channel_name'])){
                    $info['out_channel_name'] = $info['channel_name'];
                }else{
                    $info['out_channel_name'] = ' ';
                }
                if(!empty($info['accessImage'])){
                    if (stripos($info['accessImage'], 'http://') !== false||stripos($info['accessImage'], 'https://') !== false){
                        $info['out_accessImage'] =$info['accessImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessImage']);
                        $info['accessImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['out_accessImage'] = replace_file_domain($info['accessImage']);
                        } else {
                            $info['out_accessImage'] = replace_file_domain($info['accessImage']);
//                            $info['out_accessImage'] = cfg('site_url') . $info['accessImage'];
                        }
                    }
                }
                if(!empty($info['accessBigImage'])){
                    if (stripos($info['accessBigImage'], 'http://') !== false||stripos($info['accessBigImage'], 'https://') !== false){
                        $info['out_accessImage'] =$info['accessBigImage'];
                    }else {
                        $accessImageArr=explode('/upload/',$info['accessBigImage']);
                        $info['accessBigImage']='/upload/'.$accessImageArr['1'];
                        $path = explode('/', $info['accessBigImage']);
                        if (count($path) > 2 && $path[2] == 'meeting') {
                            $info['out_accessImage'] = replace_file_domain($info['accessBigImage']);
                        } else {
                            $info['out_accessImage'] = replace_file_domain($info['accessBigImage']);
//                            $info['out_accessImage'] = cfg('site_url') . $info['accessBigImage'];
                        }
                    }

                } 
                if(empty($info['out_accessImage'])){
                    $info['out_accessImage'] = cfg('site_url').'/static/images/house/park/park.png';
                }
            }
            $info['accessTime']=date('Y-m-d H:i:s',$info['accessTime']);
            $info['totalMoney']= $info['total'];
        }
        
        return $info;
    }

    /**
     * 手动开闸记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getOpenGateList($data,$hidephone=false){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessMode','=',7];
        $where[] = ['del_time','<',1];
       //  $where[]=['accessType','=',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];

            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $count =$db_house_village_car_access_record->getCount($where);
        $list =$db_house_village_car_access_record->get_list($where,'park_car_type,park_name,park_time,order_id,optname,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_number,channel_name as in_channel_name',$data['page'],$data['limit']);
        foreach ($list as &$val){
            $val['accessType'] =$val['accessType']==1?'进场':'出场';
            if($val['accessTime']){
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['accessTime'] = '--';
            }
            if($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])){
                $val['user_phone'] = $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }
            $whereArr=array();
            if(!empty($val['channel_id'])){
                $whereArr[]=array('id','=',$val['channel_id']);
            }else if(!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            if($whereArr){
                $whereArr[]=array('village_id','=',$data['village_id']);
                $garage_num=$this->getGarageByParkPassage($whereArr);
                if($garage_num){
                    $val['park_name']=$garage_num;
                }
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type']='';
        return $data1;
    }

    /**
     * 不在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getOutParkList($data,$hidephone=false){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['exception_type','=',2];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];

            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $count =$db_house_village_car_access_record->getCount($where);
        $list =$db_house_village_car_access_record->get_list($where,'park_car_type,label_id,park_name,park_time,order_id,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_number,channel_name',$data['page'],$data['limit']);
        foreach ($list as &$val){
            if($val['accessTime']){
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['accessTime'] = '--';
            }
            if($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])){
                $val['user_phone'] = $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }
            $whereArr=array();
            if(!empty($val['channel_id'])){
                $whereArr[]=array('id','=',$val['channel_id']);
            }else if(!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            if($whereArr){
                $whereArr[]=array('village_id','=',$data['village_id']);
                $garage_num=$this->getGarageByParkPassage($whereArr);
                if($garage_num){
                    $val['park_name']=$garage_num;
                }
            }

        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type']='';
        return $data1;
    }
    public function  getGarageInfo($passageWhere=array(),$village_id=0,$garage_id,$field='garage_num'){
        if($garage_id>0){
            $houseVillageParkingGarage=new HouseVillageParkingGarage();
            $whereArr=array('garage_id'=>$garage_id);
            $parkingGarage=$houseVillageParkingGarage->getOne($whereArr,$field);
            if($parkingGarage && !$parkingGarage->isEmpty()){
                $garageArr=$parkingGarage->toArray();
                if($field=='garage_num' && $garageArr && isset($garageArr['garage_num'])){
                    return $garageArr['garage_num'];
                }
                return $garageArr;
            }
            return array();
        }else{
            $passageWhere[]=array('village_id','=',$village_id);
            return $this->getGarageByParkPassage($passageWhere,$field);
        }
    }
    public function getGarageByParkPassage($passageWhere=array(),$field='garage_num'){
        $garageArr=array();
        $parkPassageParkPassage=new ParkPassage();
        $parkPassage=$parkPassageParkPassage->getFind($passageWhere);
        $garage_id=0;
        if($parkPassage && !$parkPassage->isEmpty()){
            if($parkPassage && isset($parkPassage['garage_id']) && $parkPassage['garage_id']>0){
                $garage_id=$parkPassage['garage_id'];
            }else if($parkPassage && isset($parkPassage['passage_area']) && !empty($parkPassage['passage_area']) && $parkPassage['area_type']==2){
                $garage_id=intval($parkPassage['passage_area']);
            }
        }
        if($garage_id>0){
            $houseVillageParkingGarage=new HouseVillageParkingGarage();
            $whereArr=array('garage_id'=>$garage_id);
            $parkingGarage=$houseVillageParkingGarage->getOne($whereArr,$field);
            if($parkingGarage && !$parkingGarage->isEmpty()){
                $garageArr=$parkingGarage->toArray();
            }
        }
        if($field=='garage_num' && $garageArr && isset($garageArr['garage_num'])){
            return $garageArr['garage_num'];
        }
        return $garageArr;
    }
    /**
     * 电瓶车出入记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getElectricParkList($data,$hidephone=false){

        $db_in_park_electriccar=new InParkElectriccar();
        $where = [];
        $where[] = ['i.village_id','=',$data['village_id']];
        $count =$db_in_park_electriccar->getCounts($where);
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['i.add_time','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['i.add_time','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['c.car_user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['c.car_user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['i.car_number','like','%'.$data['value'].'%'];

            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $list =$db_in_park_electriccar->getList($where,'i.*,c.car_user_name,c.car_user_phone',$data['page'],$data['limit']);
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
            if($hidephone && isset($val['car_user_phone']) && !empty($val['car_user_phone'])){
                $val['car_user_phone']=$houseVillageDb->phone_desensitization_v($val['car_user_phone'],$data['village_id'],$village_info);
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    /**
     * 储值车出入记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTemporaryParkList($data,$hidephone=false){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['car_type','=','storedCar'];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];

            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $count =$db_house_village_car_access_record->getCount($where);
        $list =$db_house_village_car_access_record->get_list($where,'park_car_type,order_id,stored_balance,total,park_time,park_name,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name',$data['page'],$data['limit']);
        foreach ($list as &$val){
            $val['accessType'] =$val['accessType']==1?'进场':'出场';
            $out_info=[];
            if (!empty($val['order_id'])){
                if ($val['accessType']==1){
                    $accessType=2;
                }else{
                    $accessType=1;
                }
                $out_info=$db_house_village_car_access_record->getOne(['order_id'=>$val['order_id'],'accessType'=>$accessType],'stored_balance,total,park_time,order_id,accessType,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name');
            }
            $val['surplus_balance']=$val['stored_balance'];
            $val['stored_balance']=$val['stored_balance']+$val['total'];
            if ($val['accessType']==1){
                if($val['accessTime']>1){
                    $val['in_accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);//
                }else{
                    $val['in_accessTime'] = '--';
                }
                if(!empty($out_info['accessTime'])&&$out_info['accessTime']>1){
                    $val['out_accessTime'] = date('Y-m-d H:i:s',$out_info['accessTime']);
                }else{
                    $val['out_accessTime'] = '--';
                }
            }else{
                if($val['accessTime']>1){
                    $val['out_accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);//
                }else{
                    $val['out_accessTime'] = '--';
                }
                if(!empty($out_info['accessTime'])&&$out_info['accessTime']>1){
                    $val['in_accessTime'] = date('Y-m-d H:i:s',$out_info['accessTime']);
                }else{
                    $val['in_accessTime'] = '--';
                }
            }
            if (!empty($val['park_time'])){
                $hours=intval($val['park_time']/3600);
                $minter=ceil(($val['park_time']-$hours*3600)/60);
                if ($val['park_time']>=3600){
                    $val['park_time']=$hours.'小时'.$minter.'分钟';
                }else{
                    $val['park_time']=$minter.'分钟';
                }

            }
            if(!empty($out_info['channel_name'])){
                $val['out_channel_name'] = $out_info['channel_name'];
            }else{
                $val['out_channel_name'] = ' ';
            }
            if(!empty($val['channel_name'])){
                $val['in_channel_name'] = $val['channel_name'];
            }else{
                $val['in_channel_name'] = ' ';
            }
            if($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])){
                $val['user_phone'] = $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['park_sys_type']='';
        return $data1;
    }

    /**
     * 储值车充值记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTemporaryPayList($data){
        $db_house_village_car_access_record=new HouseNewPayOrder();
        $db_house_village=new HouseVillage();
        $village_info=$db_house_village->getOne($data['village_id'],'village_name');
        $where = [];
        $where[] =['village_id','=',$data['village_id']];
        $where[] = ['car_type','=','stored_type'];
        $where[] = ['order_type','=','park_new'];
        $where[] = ['is_paid','=',1];
        $count =$db_house_village_car_access_record->getPayCount($where);
        $list =$db_house_village_car_access_record->getPayLists($where,'pay_time,pay_money,car_number,stored_balance',$data['page'],$data['limit']);
        foreach ($list as &$val){
            $val['park_name']=$village_info['village_name'];

            if($val['pay_time']){
                $val['pay_time'] = date('Y-m-d H:i:s',$val['pay_time']);
            }else{
                $val['pay_time'] = '--';
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    /**
     * 储值车记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTemporaryList($data){
        $db_house_village_car_access_record=new HouseVillageParkingCar();
        $where = [];
        $where[] =['village_id','=',$data['village_id']];
        $where[] = ['stored_card','<>',''];
        $count =$db_house_village_car_access_record->get_village_car_num($where);
        $list =$db_house_village_car_access_record->getHouseVillageParkingCarLists($where,'*',$data['page'],$data['limit']);
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }else{
                $val['add_time'] = '--';
            }
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }

    public function getInParkInfo($car_number,$village_id){
        $inParkInfo=[];
        if (!empty($car_number)&&!empty($village_id)){
            $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
            $where['car_number']=$car_number;
            $where['park_id']=$village_id;
            $where['accessType']=1;
            $where['is_out']=0;
            $inParkInfo=$db_house_village_car_access_record->getOne($where);
            if (!empty($inParkInfo)){
                $inParkInfo=$inParkInfo->toArray();
            }
        }

        return $inParkInfo;
    }

    public function getParkType(){
        return $this->park_type;
    }

    public function getParkProvice(){
        $city_arr = array('京', '津', '冀', '晋', '蒙', '辽', '吉', '黑', '沪', '苏', '浙', '皖', '闽', '赣', '鲁', '豫', '鄂', '湘', '粤', '桂', '琼', '渝', '川', '贵', '云', '藏', '陕', '甘', '青', '宁', '新');
        return $city_arr;
    }


    /**
     * 读取文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 13:21
     */
    public function readFile($file, $filed, $readerType)
    {
        $uploadfile = $file;
        $reader = IOFactory::createReader($readerType); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($uploadfile); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                if (isset($filed[$column])){
                    $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getFormattedValue();
                }
            }

        }
        return $data;
    }


    /**
     * 读取文件(带时间字段的文件读取)
     * @author:zhubaodi
     * @date_time: 2021/5/21 13:21
     */
    public function readFile_date($file, $filed, $readerType)
    {
        $uploadfile = $file;
        $reader = IOFactory::createReader($readerType); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($uploadfile); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $formattedValue = [];
        $value = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                if (isset($filed[$column])){
                    $formattedValue[$row][$filed[$column]] = $sheet->getCell($column . $row)->getFormattedValue();
                    $value[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
                }
            }

        }
        $res=[];
        $res['formattedValue']=$formattedValue;
        $res['value']=$value;
        return $res;
    }

    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExcel($title, $data, $fileName, $setWidthValue = 25)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth($setWidthValue);
        //  $sheet->getStyle('A')->getNumberFormat()->setFormatCode('@');
        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '1', $value);
            $titCol++;
        }
        $row = 2;
        foreach ($data as $item) {
            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $value=$value ? strval($value):'';
                $sheet->setCellValueExplicit($dataCol . $row, $value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                //$sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row++;
        }
        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }

    public function getHouseVillageUserBindInfo($village_id=0,$user_house_arr=array(),$is_owner=false){
        $houseVillageSingleDb= new HouseVillageSingle();
        $houseVillageFloorDb= new HouseVillageFloor();
        $houseVillageLayerDb= new HouseVillageLayer();
        $houseVillageUserVacancyDb= new HouseVillageUserVacancy();
        $houseVillageUserBindDb= new HouseVillageUserBind();
        $single_id=0;
        $floor_id=0;
        $layer_id=0;
        $vacancy_id=0;
        if($village_id>0 && !empty($user_house_arr)){
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('id','>',0);
            $whereArr[]=array('status','<',4);
            if(isset($user_house_arr['single_name']) && !empty($user_house_arr['single_name'])){
                $whereArr[]=array('single_name','=',$user_house_arr['single_name']);
            }elseif(isset($user_house_arr['single_number']) && !empty($user_house_arr['single_number'])){
                $whereArr[]=array('single_number','=',$user_house_arr['single_number']);
            }else{
                return array();
            }
            $houseVillageSingleInfo=$houseVillageSingleDb->getOne($whereArr,'id');
            if($houseVillageSingleInfo && !$houseVillageSingleInfo->isEmpty()){
                $single_id=$houseVillageSingleInfo['id'];
            }
            if($single_id<1){
                return array();
            }
            
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('floor_id','>',0);
            $whereArr[]=array('status','<',4);
            $whereArr[]=array('single_id','=',$single_id);
            if(isset($user_house_arr['floor_name']) && !empty($user_house_arr['floor_name'])){
                $whereArr[]=array('floor_name','=',$user_house_arr['floor_name']);
            }elseif(isset($user_house_arr['floor_number']) && !empty($user_house_arr['floor_number'])){
                $whereArr[]=array('floor_number','=',$user_house_arr['floor_number']);
            }else{
                return array();
            }
            $houseVillageFloorleInfo=$houseVillageFloorDb->getOne($whereArr,'floor_id');
            if($houseVillageFloorleInfo && !$houseVillageFloorleInfo->isEmpty()){
                $floor_id=$houseVillageFloorleInfo['floor_id'];
            }
            if($floor_id<1){
                return array();
            }

            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('id','>',0);
            $whereArr[]=array('status','<',4);
            $whereArr[]=array('single_id','=',$single_id);
            $whereArr[]=array('floor_id','=',$floor_id);
            if(isset($user_house_arr['layer_name']) && !empty($user_house_arr['layer_name'])){
                $whereArr[]=array('layer_name','=',$user_house_arr['layer_name']);
            }elseif(isset($user_house_arr['layer_number']) && !empty($user_house_arr['layer_number'])){
                $whereArr[]=array('layer_number','=',$user_house_arr['layer_number']);
            }else{
                return array();
            }
            $houseVillageLayerInfo=$houseVillageLayerDb->getOne($whereArr,'id');
            if($houseVillageLayerInfo && !$houseVillageLayerInfo->isEmpty()){
                $layer_id=$houseVillageLayerInfo['id'];
            }
            if($layer_id<1){
                return array();
            }

            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('pigcms_id','>',0);
            $whereArr[]=array('is_del','<',1);
            $whereArr[]=array('single_id','=',$single_id);
            $whereArr[]=array('floor_id','=',$floor_id);
            $whereArr[]=array('layer_id','=',$layer_id);
            if(isset($user_house_arr['room_name']) && !empty($user_house_arr['room_name'])){
                $whereArr[]=array('room','=',$user_house_arr['room_name']);
            }elseif(isset($user_house_arr['room_number']) && !empty($user_house_arr['room_number'])){
                $whereArr[]=array('room_number','=',$user_house_arr['room_number']);
            }else{
                return array();
            }
            $houseVillageVacancyInfo=$houseVillageUserVacancyDb->getOne($whereArr,'pigcms_id');
            if($houseVillageVacancyInfo && !$houseVillageVacancyInfo->isEmpty()){
                $vacancy_id=$houseVillageVacancyInfo['pigcms_id'];
            }
            if($vacancy_id<1){
                return array();
            }

            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('pigcms_id','>',0);
            $whereArr[]=array('status','in',array(0,1,2));
            if($is_owner){
                $whereArr[]=array('type','in',array(0,3));
            }else{
                $whereArr[]=array('type','in',array(0,1,2,3));
            }
            $whereArr[]=array('single_id','=',$single_id);
            $whereArr[]=array('floor_id','=',$floor_id);
            $whereArr[]=array('layer_id','=',$layer_id);
            $whereArr[]=array('vacancy_id','=',$vacancy_id);
            $is_have_user_info=false;
            if(isset($user_house_arr['user_name']) && !empty($user_house_arr['user_name'])){
                $is_have_user_info=true;
                $whereArr[]=array('name','=',$user_house_arr['user_name']);
            }
            if(isset($user_house_arr['user_phone']) && !empty($user_house_arr['user_phone'])){
                $is_have_user_info=true;
                $whereArr[]=array('phone','=',$user_house_arr['user_phone']);
            }
            if(!$is_have_user_info && !$is_owner){
                return array();
            }
            fdump_api(['whereArr'=>$whereArr,'is_owner'=>$is_owner],'00uplodeCar',1);
            $houseVillageUserBind=$houseVillageUserBindDb->getOne($whereArr);
            if($houseVillageUserBind && !$houseVillageUserBind->isEmpty()){
                $houseVillageUserBind=$houseVillageUserBind->toArray();
                return $houseVillageUserBind;
            }
            return array();
        }
        return array();
    }
    public function uplodeDateConvertDateToInt($dateStr, $sfm='00:00:00')
    {
        if (is_numeric($dateStr)){
            $value = date("Y-m-d", ($dateStr - 25569) * 24 * 3600);
            $dateStr =strtotime($value.' '.$sfm);
        }else if (preg_match('/[\x7f-\xff]/',$dateStr)){
            $arr = date_parse_from_format('Y年m月d日', $dateStr);
            if ($sfm) {
                $dateStr = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
            }else {
                $dateStr = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
            }
        } else{
            $dateStr =strtotime($dateStr.' '.$sfm);
        }
        return $dateStr;
    }
    /**
     * 导入车位
     * @author:zhubaodi
     * @date_time: 2021/5/21 11:55
     */
    public function uplodePosition($village_id,$file, $upload_dir)
    {
        set_time_limit(0);
        ini_set('memory_limit','-1');
        $service = new UploadFileService();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $savepath = $service->uploadFile($file, $upload_dir);
        $filed = [
            'A' => 'position_id',
            'B' => 'garage_num',
            'C' => 'position_num',
            'D'=>'position_pattern',
            'E'=>'position_status',
            'F' => 'position_area',
            'G' => 'position_note',
            'H' => 'single_name',
            'I' => 'floor_name',
            'J' => 'layer_name',
            'K' => 'room_name',
            'L' => 'user_name',
            'M' => 'user_phone',
            'N'=>'start_time',
            'O'=>'end_time',
        ];
        $data = $this->readFile($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, 'Xlsx');
        $data_print = [];
        $res=[];
        $res['url'] ='';
        $res['error'] = 0;
        if (!empty($data)) {
            $data_print = [];
            fdump_api(['data' => $data], '111111uplodePosition');
            foreach ($data as $value) {
                $position_num_string = isset($value['position_num']) && $value['position_num'] ? trim($value['position_num']) : '';
                $position_id = trim($value['position_id']);
                $garage_num = trim($value['garage_num']);
                $position_pattern = trim($value['position_pattern']);
                $position_status = trim($value['position_status']);
                $position_area = trim($value['position_area']);
                $position_note = trim($value['position_note']);
                if (empty($garage_num) && empty($position_num_string) && empty($position_pattern) && empty($position_status) && empty($position_area) && empty($position_note)) {
                    unset($value);
                    continue;
                }
                if (empty($position_num_string)) {
                    $value['failReason'] = '车位号必填，请按照模板完整填写参数';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                if (empty($garage_num)) {
                    $value['failReason'] = '车库名称必填，请按照模板完整填写参数';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                $position_num_arr = explode(',', $position_num_string);
                fdump_api(['position_num_arr' => $position_num_arr], '111111uplodePosition', 1);
                if (count($position_num_arr) <= 0) {
                    $value['failReason'] = '参数不全，请按照模板完整填写参数';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                $garage_info = $db_house_village_parking_garage->getOne(['garage_num' => $garage_num, 'village_id' => $village_id, 'status' => 1]);
                fdump_api(['value' => $value, 'garage_num' => $garage_num, 'garage_info' => $garage_info && !is_array($garage_info) ? $garage_info->toArray() : $garage_info], '111111uplodePosition', 1);
                if (empty($garage_info)) {
                    $value['failReason'] = '所属车库不存在，请正确填写参数';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                if (!empty($position_area) && !is_numeric($position_area)) {
                    $value['failReason'] = '请正确上传车位面积';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                if (!empty($position_id) && !is_numeric($position_id)) {
                    $value['failReason'] = '请正确上传车位ID，导入新车位时请不要填写！';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                $user_house_arr = array();
                $bind_user_id = 0;
                $is_yezhu_info = false;
                $single_name = isset($value['single_name']) && $value['single_name'] ? trim($value['single_name']) : '';
                if ($single_name) {
                    $user_house_arr['single_name'] = $single_name;
                    $is_yezhu_info = true;
                }
                $floor_name = isset($value['floor_name']) && $value['floor_name'] ? trim($value['floor_name']) : '';
                if ($floor_name) {
                    $user_house_arr['floor_name'] = $floor_name;
                    $is_yezhu_info = true;
                }
                $layer_name = isset($value['layer_name']) && $value['layer_name'] ? trim($value['layer_name']) : '';
                if ($layer_name) {
                    $user_house_arr['layer_name'] = $layer_name;
                    $is_yezhu_info = true;
                }
                $room_name = isset($value['room_name']) && $value['room_name'] ? trim($value['room_name']) : '';
                if ($room_name) {
                    $user_house_arr['room_name'] = $room_name;
                    $is_yezhu_info = true;
                }
                $user_name = isset($value['user_name']) && $value['user_name'] ? trim($value['user_name']) : '';
                if ($user_name) {
                    $user_house_arr['user_name'] = $user_name;
                    $is_yezhu_info = true;
                }
                $user_phone = isset($value['user_phone']) && $value['user_phone'] ? trim($value['user_phone']) : '';
                if ($user_phone) {
                    $user_house_arr['user_phone'] = $user_phone;
                    $is_yezhu_info = true;
                }
                if (!empty($user_house_arr)) {
                    $user_bind_info = $this->getHouseVillageUserBindInfo($village_id, $user_house_arr);
                    if ($user_bind_info && isset($user_bind_info['pigcms_id'])) {
                        $bind_user_id = $user_bind_info['pigcms_id'];
                    }
                }
                fdump_api(['user_house_arr' => $user_house_arr, 'village_id' => $village_id, '$bind_user_id' => isset($bind_user_id) ? $bind_user_id : 0], '111111uplodePosition', 1);
                /*
                $position_count = $db_house_village_parking_position->getCounts(['garage_id' => $garage_info['garage_id'], 'village_id' => $village_id]);
                fdump_api(['position_count' => $position_count, 'garage_id' => $garage_info['garage_id'], 'village_id' => $village_id], '111111uplodePosition', 1);
                */
                $start_time=0;
                if($value['start_time']){
                    $value['start_time']=strval($value['start_time']);
                    $value['start_time']=trim($value['start_time']);
                    $start_time=$this->uplodeDateConvertDateToInt($value['start_time']);
                    $start_time=$start_time>0 ? $start_time:0;
                }
                $end_time=0;
                if($value['end_time']){
                    $value['end_time']=strval($value['end_time']);
                    $value['end_time']=trim($value['end_time']);
                    $end_time=$this->uplodeDateConvertDateToInt($value['end_time']);
                    $end_time=$end_time>0 ? $end_time:0;
                }
                if($end_time>0 && $end_time<$start_time){
                    $value['failReason'] = '到期时间不能小于开始时间';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
                // 添加车位
                // 表中的车位id集合
                $tmpPositionIds = [];
                if (empty($position_id)){
                    $errFailReason = "";
                    foreach ($position_num_arr as $position_num) {
                        $position_num = trim($position_num);
                        if ($position_num === '') {
                            continue;
                        }
                        if(strlen($position_num) > 30){
                            $errFailReason = '车位号[' . $position_num . ']不能超过30位';
                            break;
                        }
                        $position_info = $db_house_village_parking_position->getFind(['position_num' => $position_num,'village_id'=>$village_id],'*');
                        if ($position_info) {
                            if ($position_info['position_num'] == $position_num && $position_info['garage_id'] == $garage_info['garage_id']) {
                                $errFailReason = '车位号[' . $position_num . ']已存在，请正确填写参数';
                                $errFailReason = "";
                                continue;
                            }
                        }
                        /*
                        if ($position_count >= $garage_info['position_count']) {
                            $errFailReason = '当前车位数量已到达车库[' . $garage_num . ']设置的车位总数，请正确填写参数';
                            break;
                        }
                        */
                        $position_data = [
                            'garage_id' => $garage_info['garage_id'], 'position_num' => $position_num, 'position_area' => $position_area,
                            'position_note' => $position_note, 'village_id' => $village_id, 'position_pattern' => 1,
                        ];
                        $position_data['start_time']=$start_time;
                        $position_data['end_time']=$end_time;
                        $tmp_id = $db_house_village_parking_position->addOne($position_data);
                        if (!$tmp_id) {
                            $errFailReason = '车位号[' . $position_num . ']添加失败，请正确填写参数';
                            break;
                        }
                        $tmpPositionIds[] = $tmp_id;
                    }
                    fdump_api(['errFailReason' => $errFailReason, 'tmpPositionIds' => $tmpPositionIds], '111111uplodePosition', 1);
                    if ($errFailReason) {
                        $value['failReason'] = $errFailReason;
                        $data_print[] = $value;
                        unset($value);
                        continue;
                    }
                } else {
                    $position_info1 = $db_house_village_parking_position->getFind(['position_id' => $position_id,'village_id'=>$village_id],'*');
                    $position_num1 = isset($position_num_arr[0]) && $position_num_arr[0] ? trim($position_num_arr[0]) : '';
                    fdump_api(['position_num1' => $position_num1, 'position_info1' => $position_info1 && !is_array($position_info1) ? $position_info1->toArray() : $position_info1], '111111uplodePosition', 1);
                    if (empty($position_info1) || $position_info1->isEmpty()) {
                        $errFailReason = "";
                        foreach ($position_num_arr as $position_num) {
                            $position_num = trim($position_num);
                            if ($position_num === '') {
                                continue;
                            }
                            if(strlen($position_num) > 30){
                                $errFailReason = '车位号[' . $position_num . ']不能超过30位';
                                break;
                            }
                            $position_info = $db_house_village_parking_position->getFind(['position_num' => $position_num,'village_id'=>$village_id]);
                            if ($position_info) {
                                if ($position_info['garage_id'] == $garage_info['garage_id']) {
                                    $errFailReason = '车位号[' . $position_num . ']已存在，请正确填写参数';
                                    $errFailReason = "";
                                    continue;
                                }
                            }
                            /*
                            if ($position_count >= $garage_info['position_count']) {
                                $errFailReason = '当前车位数量已到达车库[' . $garage_num . ']设置的车位总数，请正确填写参数';
                                break;
                            }
                            */
                            $position_data = [
                                'garage_id' => $garage_info['garage_id'], 'position_num' => $position_num, 'position_area' => $position_area,
                                'position_note' => $position_note, 'village_id' => $village_id, 'position_pattern' => 1,
                            ];
                            $position_data['start_time']=$start_time;
                            $position_data['end_time']=$end_time;
                            $tmp_id = $db_house_village_parking_position->addOne($position_data);
                            if (!$tmp_id) {
                                $errFailReason = '车位号[' . $position_num . ']添加失败，请正确填写参数';
                                break;
                            }
                            $tmpPositionIds[] = $tmp_id;
                        }
                        fdump_api(['errFailReason' => $errFailReason, 'tmpPositionIds' => $tmpPositionIds], '111111uplodePosition', 1);
                        if ($errFailReason) {
                            $value['failReason'] = $errFailReason;
                            $data_print[] = $value;
                            unset($value);
                            continue;
                        }
                    } else {
                        /*
                        if ($position_count >= $garage_info['position_count']) {
                            $value['failReason'] = '当前车位数量已到达车库设置的车位总数，请正确填写参数';
                            $data_print[] = $value;
                            unset($value);
                            continue;
                        }
                        */
                            $position_info = $db_house_village_parking_position->getFind([
                                'position_num' => $position_num1,
                                'garage_id' => $garage_info['garage_id'],
                                'village_id' => $village_id,
                            ], 'position_id');
                            if ($position_info && $position_info['position_id'] != $position_id) {
                            } else {
                                $updatePositionData = [
                                    'garage_id' => $garage_info['garage_id'], 'position_num' => $position_num1, 'position_area' => $position_area,
                                    'position_note' => $position_note, 'village_id' => $village_id, 'position_pattern' => 1,
                                ];
                                $updatePositionData['start_time']=$start_time;
                                $updatePositionData['end_time']=$end_time;
                                $db_house_village_parking_position->saveOne(['position_id' => $position_id,'village_id'=>$village_id], $updatePositionData);
                                $tmpPositionIds[] = $position_id;
                                fdump_api(['position_id' => $position_id, 'updatePositionData' => $updatePositionData, 'tmpPositionIds' => $tmpPositionIds], '111111uplodePosition', 1);
                            }

                    }
                }
                if (!empty($tmpPositionIds) && $bind_user_id > 0) {
                    $db_house_village_bind_position = new HouseVillageBindPosition();
                    foreach ($tmpPositionIds as $tmp_position_id) {
                        $bind_where = [];
                        $bind_where['position_id'] = $tmp_position_id;
                        $bind_where['village_id'] = $village_id;
                        $bind_info = $db_house_village_bind_position->getOne($bind_where);
                        fdump_api(['bind_where' => $bind_where, 'bind_info' => $bind_info && !is_array($bind_info) ? $bind_info->toArray() : $bind_info], '111111uplodePosition', 1);
                        if (!empty($bind_info) && !$bind_info->isEmpty()) {
                            $data_bind = [];
                            $data_bind['user_id'] = $bind_user_id;
                            $db_house_village_bind_position->saveOne($bind_where, $data_bind);
                        } else {
                            $data_bind = [];
                            $data_bind['position_id'] = $tmp_position_id;
                            $data_bind['user_id'] = $bind_user_id;
                            $data_bind['village_id'] = $village_id;
                            $db_house_village_bind_position->addOne($data_bind);
                            fdump_api(['data_bind' => $data_bind], '111111uplodePosition', 1);
                        }
                        $db_house_village_parking_position->saveOne(['position_id' => $tmp_position_id], ['position_status' => 2]);
                    }
                } elseif ($is_yezhu_info && !empty($tmpPositionIds)) {
                    $value['failReason'] = '车位已导入成功，绑定业主失败，请仔细检查所填业主信息是否正确！';
                    $data_print[] = $value;
                    unset($value);
                    continue;
                }
            }
            fdump_api(['data_print' => $data_print], '111111uplodePosition', 1);
            if (!empty($data_print)) {
                $title = ['车位ID(不要填写)', '车库名称(必填)', '车位号(必填)[支持英文逗号分隔填写多个]', '车位模式(选填)','车位状态(选填)','车位面积(选填)', '备注(选填)','楼栋名称(选填)','单元名称(选填)','楼层名称(选填)','房间号(选填)','业主姓名(选填)','业主手机号(选填)','开始时间(选填)','到期时间(选填)','失败原因'];
                $res = $this->saveExcel($title, $data_print, '批量导入车位失败数据列表' . time(), 40);
            }

        }
        return $res;
    }


    /**
     * 导入车辆
     * @author:zhubaodi
     * @date_time: 2021/5/21 11:55
     */
    public function uplodeCar($village_id,$file, $upload_dir)
    {
        $service = new UploadFileService();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $houseVillageParkingService=new HouseVillageParkingService();
        $houseVillageUserBindDb= new HouseVillageUserBind();
        $savepath = $service->uploadFile($file, $upload_dir);
        $filed = [
            'A' => 'car_id',
            'B' => 'province',
            'C' => 'car_number',
            'D' => 'garage_num',
            'E' => 'position_num',
            'F' => 'car_stop_num',
            'G' => 'car_user_name',
            'H' => 'car_user_phone',
            'I' => 'parking_car_type',
            'J' => 'single_name',
            'K' => 'floor_name',
            'L' => 'layer_name',
            'M' => 'room_name',
        ];
        $data = $this->readFile_date($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, 'Xlsx');
        $data_print = [];
        $res=[];
        $res['url'] ='';
        $res['error'] = 0;
        $nowtime=time();
        if (!empty($data)) {
            $data_print = [];
            $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
            foreach ($data['formattedValue'] as $k=>$value) {
                if (empty($value['garage_num']) && empty($value['position_num'])&&empty($value['province']) && empty($value['car_number'])) {
                    continue;
                }

                if (empty($value['garage_num']) || empty($value['position_num'])||empty($value['province']) || empty($value['car_number'])) {
                    $value['failReason'] = '参数不全，请按照模板完整填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $value['garage_num']=trim($value['garage_num']);
                $value['position_num']=trim($value['position_num']);
                $value['province']=trim($value['province']);
                $value['car_number']=trim($value['car_number']);
                $garage_info = $db_house_village_parking_garage->getOne(['garage_num' => $value['garage_num'],'status'=>1, 'village_id' => $village_id]);
                if (empty($garage_info)) {
                    $value['failReason'] = '所属车库不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $parking_position = $db_house_village_parking_position->getFind(['position_num' => $value['position_num'],'garage_id'=>$garage_info['garage_id'],'village_id' => $village_id]);
                if (empty($parking_position) || $parking_position->isEmpty()) {
                    $value['failReason'] = '所属车位不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $car_info = $db_house_village_parking_car->getFind(['province' => $value['province'],'car_number'=>$value['car_number'],'village_id' => $village_id]);

                if (empty($value['car_stop_num'])){
                    $value['car_stop_num']=' ';
                }else{
                    $value['car_stop_num']=trim($value['car_stop_num']);
                }
                if (empty($value['car_user_name'])){
                    $value['car_user_name']=' ';
                }else{
                    $value['car_user_name']=trim($value['car_user_name']);
                }
                if (empty($value['car_user_phone'])){
                    $value['car_user_phone']='';
                }else{
                    $value['car_user_phone']=trim($value['car_user_phone']);
                    if(!cfg('international_phone') && !preg_match('/^[0-9]{11}$/',$value['car_user_phone'])){
                        $value['failReason'] = '手机号格式不正确，请正确填写参数';
                        $data_print[] = $value;
                        continue;
                    }
                }
                $end_time=$parking_position['end_time'] ? $parking_position['end_time']:0;
                /*
                if ((!empty($value['end_time'])&&!empty(trim($value['end_time'])))||(!empty($data['value'][$k]['end_time'])&&!empty(trim($data['value'][$k]['end_time'])))){
                    $value['end_time']=trim($value['end_time']);
                    $data['value'][$k]['end_time']=trim($data['value'][$k]['end_time']);
                    if (is_numeric($data['value'][$k]['end_time'])){
                        $valueF = date("Y-m-d", ($data['value'][$k]['end_time'] - 25569) * 24 * 3600);
                        $value['end_time']=strtotime($valueF.' 23:59:59');
                    }else if (preg_match('/[\x7f-\xff]/',$value['end_time'])){
                        $arr = date_parse_from_format('Y年m月d日', $value['end_time']);
                        $value['end_time']= mktime(23, 59, 59, $arr['month'], $arr['day'], $arr['year']);
                    } else{
                        $value['end_time']=strtotime($value['end_time']. '23:59:59');
                    }
					if (!empty($car_info)&&!empty($car_info['end_time'])){
					    if ($value['end_time']<$car_info['end_time']){
                            $value['failReason'] = '到期时间不能小于'.date('Y-m-d H:i:s',$car_info['end_time']);
                            $value['end_time'] = date('Y-m-d',$value['end_time']);
                            $data_print[] = $value;
                            continue;
                        }
                       if ($parking_position && 2 != $parking_position['position_pattern'] && !$value['end_time'] && $parking_position['end_time']) {
                           $value['end_time'] = $parking_position['end_time'];
                        } elseif ($parking_position && 2 != $parking_position['position_pattern'] && $value['end_time'] && $parking_position['end_time'] && intval($value['end_time']) < $parking_position['end_time']) {
                            $value['failReason'] ='到期时间不得小于当前停车位到期时间，停车位到期时间：' . date('Y-m-d', $parking_position['end_time']);
                            $value['end_time'] = date('Y-m-d',$value['end_time']);
                            $data_print[] = $value;
                            continue;
                        }
                    }

                }
                */
                $user_house_arr=array();
                $bind_user_id=0;
                if(isset($value['single_name']) && !empty($value['single_name'])){
                    $user_house_arr['single_name']=trim($value['single_name']);
                }
                if(isset($value['floor_name']) && !empty($value['floor_name'])){
                    $user_house_arr['floor_name']=trim($value['floor_name']);
                }
                if(isset($value['layer_name']) && !empty($value['layer_name'])){
                    $user_house_arr['layer_name']=trim($value['layer_name']);
                }
                if(isset($value['room_name']) && !empty($value['room_name'])){
                    $user_house_arr['room_name']=trim($value['room_name']);
                }
                if(!empty($user_house_arr)){
                    $user_bind_info=$this->getHouseVillageUserBindInfo($village_id,$user_house_arr,true);
                    if($user_bind_info && isset($user_bind_info['pigcms_id'])){
                        $bind_user_id=$user_bind_info['pigcms_id'];
                    }elseif($value['car_user_phone']){
                        $whereBindArr=array();
                        $whereBindArr[]=array('village_id','=',$village_id);
                        $whereBindArr[]=array('pigcms_id','>',0);
                        $whereBindArr[]=array('status','in',array(0,1,2));
                        $whereBindArr[]=array('type','in',array(0,3));
                        $whereBindArr[]=array('phone','=',$value['car_user_phone']);
                        $houseVillageUserBinds=$houseVillageUserBindDb->getLimitUserList($whereBindArr,1,'pigcms_id,village_id,uid,name,phone,type,status','pigcms_id desc',3);
                        if($houseVillageUserBinds && !$houseVillageUserBinds->isEmpty()){
                            $houseVillageUserBinds=$houseVillageUserBinds->toArray();
                            if($houseVillageUserBinds && count($houseVillageUserBinds)==1){
                                $bind_user_id=$houseVillageUserBinds['0']['pigcms_id'];
                            }
                        }
                    }
                }
                $car_id=$value['car_id'];
                //添加车辆
                if (empty($value['car_id'])){
                    if ($car_info) {
                        $value['failReason'] = '车辆已存在，请正确填写参数';
                        $data_print[] = $value;
                        continue;
                    }

                    $electric_data = [
                        'car_position_id' => $parking_position['position_id'],
                        'garage_id' => $garage_info['garage_id'],
                        'province'      => $value['province'],
                        'car_number'    => $value['car_number'],
                        'car_stop_num'  => $value['car_stop_num'],
                        'car_user_name' => $value['car_user_name'],
                        'car_user_phone'=> $value['car_user_phone'],
                        'village_id'    => $village_id,
	                    'end_time'      => $end_time,
                        'examine_status'=> 1,
                        'examine_time'  => time(),
                        'car_addtime'   => time(),
                    ];
                    if ($bind_user_id > 0) {
                        $electric_data['binding_type'] = 2;
                    }
                    
                    $village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_info['garage_id'], $village_id);
                    if (isset($value['parking_car_type']) && !empty($value['parking_car_type']) && !empty($village_park_config)) {
                        $parking_car_type_str=trim($value['parking_car_type']);
                        $parking_car_type_arr=$this->parking_car_type_arr;
                        if($village_park_config['park_sys_type']=='A11'){
                            $parking_car_type_arr=$this->parking_a11_car_type_arr;
                        }elseif ($village_park_config['park_sys_type']=='D3' || $village_park_config['park_sys_type']=='HXT85' ){
                            $parking_car_type_arr=$this->parking_d3_car_type_arr;

                        }else if($village_park_config['park_sys_type']=='D7'){
                            $parking_car_type_arr=$this->parking_d7_car_type_arr;
                        }
                        foreach ($parking_car_type_arr as $kk=>$vv){
                            if($vv==$parking_car_type_str){
                                $electric_data['parking_car_type']=$kk;
                                break;
                            }
                        }
                    }
                    
                    $electric_id = $db_house_village_parking_car->addHouseVillageParkingCar($electric_data);
                    if (!$electric_id) {
                        $value['failReason'] = '添加失败，请正确填写参数';
                        $data_print[] = $value;
                        continue;
                    }else{
                        $car_id=$electric_id;
                        $car_number=$value['car_number'];
                        if($value['province'] && strpos($value['car_number'],$value['province'])===false){
                            $car_number=$value['province'].$car_number;
                        }
                        if (!empty($village_park_config)&&$village_park_config['park_sys_type']=='D3'){
                            $carCachekey='village:'.$village_id.':car:'.$car_number;
                            cache($carCachekey,null);
                            //同步白名单到设备上
                            if ($end_time && $end_time>$nowtime){
                                $white_record=[
                                    'village_id'=>$village_id,
                                    'car_number'=>$car_number
                                ];
                                $houseVillageParkingService->addWhitelist($white_record);
                            }
                        }
                    }
                }else{
                    $car_info1 = $db_house_village_parking_car->getFind(['car_id' => $value['car_id']]);
                    if (empty($car_info1)){
                        if ($car_info) {
                              $value['failReason'] = '车辆已存在，请正确填写参数';
                                $data_print[] = $value;
                                continue;
                        }
                        $electric_data = [
                            'car_position_id' => $parking_position['position_id'],
                            'garage_id' => $garage_info['garage_id'],
                            'province'      => $value['province'],
                            'car_number'    => $value['car_number'],
                            'car_stop_num'  => $value['car_stop_num'],
                            'car_user_name' => $value['car_user_name'],
                            'car_user_phone'=> $value['car_user_phone'],
                            'village_id'    => $village_id,
                            'end_time'      => $end_time,
                            'examine_status'=> 1,
                            'examine_time'  => time(),
                            'car_addtime'   => time(),
                        ];
                        if ($bind_user_id > 0) {
                            $electric_data['binding_type'] = 2;
                        }
                        if (isset($value['parking_car_type']) && !empty($value['parking_car_type']) && !empty($village_park_config)) {
                            $parking_car_type_str=trim($value['parking_car_type']);
                            $parking_car_type_arr=$this->parking_car_type_arr;
                            if($village_park_config['park_sys_type']=='A11'){
                                $parking_car_type_arr=$this->parking_a11_car_type_arr;
                            }elseif ($village_park_config['park_sys_type']=='D3' || $village_park_config['park_sys_type']=='HXT85' ){
                                $parking_car_type_arr=$this->parking_d3_car_type_arr;

                            }else if($village_park_config['park_sys_type']=='D7'){
                                $parking_car_type_arr=$this->parking_d7_car_type_arr;
                            }
                            foreach ($parking_car_type_arr as $kk=>$vv){
                                if($vv==$parking_car_type_str){
                                    $electric_data['parking_car_type']=$kk;
                                    break;
                                }
                            }
                        }
                        $electric_id = $db_house_village_parking_car->addHouseVillageParkingCar($electric_data);
                        if (!$electric_id) {
                            $value['failReason'] = '添加失败，请正确填写参数';
                            $data_print[] = $value;
                            continue;
                        }else{
                            $car_id=$electric_id;
                            $car_number=$value['car_number'];
                            if($value['province'] && strpos($value['car_number'],$value['province'])===false){
                                $car_number=$value['province'].$car_number;
                            }
                            if (!empty($village_park_config)&&$village_park_config['park_sys_type']=='D3'){
                                $carCachekey='village:'.$village_id.':car:'.$car_number;
                                cache($carCachekey,null);
                                //同步白名单到设备上
                                if ($end_time && $end_time>$nowtime){
                                    $white_record=[
                                        'village_id'=>$village_id,
                                        'car_number'=>$car_number
                                    ];
                                    $houseVillageParkingService->addWhitelist($white_record);
                                }
                            }
                        }
                    }else{
                        if ($car_info) {
                            if ($value['car_id']!=$car_info['car_id']&&$car_info['province'] == $value['province'] && $car_info['car_number'] == $value['car_number'] ) {
                                $value['failReason'] = '车辆已存在，请正确填写参数';
                                $data_print[] = $value;
                                continue;
                            }
                        }
                        if ($car_info1['province'] != $value['province'] || $car_info1['car_number'] != $value['car_number'] ) {
                            $value['failReason'] = '车辆编号对应车牌号为['.$car_info1['province'].$car_info1['car_number'].']有误，请正确填写参数';
                            $data_print[] = $value;
                            continue;
                        }
                        $electric_data = [
                            'car_position_id' => $parking_position['position_id'],
                            'garage_id' => $garage_info['garage_id'],
                            'province' => $value['province'],
                            'car_number' => $value['car_number'],
                            'car_stop_num' => $value['car_stop_num'],
                            'car_user_name' => $value['car_user_name'],
                            'car_user_phone' => $value['car_user_phone'],
                            'village_id' => $village_id,
                            'end_time'      => $end_time,
                            'examine_status'=>1,
                            'examine_time'=>time(),
                            'car_addtime' => time(),
                        ];
                        if ($bind_user_id > 0) {
                            $electric_data['binding_type'] = 2;
                        }
                        if (isset($value['parking_car_type']) && !empty($value['parking_car_type']) && !empty($village_park_config)) {
                            $parking_car_type_str=trim($value['parking_car_type']);
                            $parking_car_type_arr=$this->parking_car_type_arr;
                            if($village_park_config['park_sys_type']=='A11'){
                                $parking_car_type_arr=$this->parking_a11_car_type_arr;
                            }elseif ($village_park_config['park_sys_type']=='D3' || $village_park_config['park_sys_type']=='HXT85' ){
                                $parking_car_type_arr=$this->parking_d3_car_type_arr;

                            }else if($village_park_config['park_sys_type']=='D7'){
                                $parking_car_type_arr=$this->parking_d7_car_type_arr;
                            }
                            foreach ($parking_car_type_arr as $kk=>$vv){
                                if($vv==$parking_car_type_str){
                                    $electric_data['parking_car_type']=$kk;
                                    break;
                                }
                            }
                        }
                        $electric_id = $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$value['car_id']],$electric_data);
                        if (!$electric_id) {
                            $value['failReason'] = '编辑失败，请正确填写参数';
                            $data_print[] = $value;
                            continue;
                        }else{
                            /*
                            if (!empty($end_time)&&isset($parking_position['position_id'])&&!empty($parking_position['position_id'])){
                                $park_position_set = [
                                    'end_time' => $end_time
                                ];
                                $db_house_village_parking_position->saveOne(['position_id' => $parking_position['position_id'],'village_id' => $parking_position['village_id']],$park_position_set);
                            }
                            */
                        }
                    }

                }
                fdump_api(['car_id'=>$car_id,'bind_user_id'=>$bind_user_id],'00uplodeCar',1);
                if($car_id && $bind_user_id>0){
                    $pdata=array('pigcms_id'=>$bind_user_id,'room_id'=>0,'relationship'=>1,'car_id'=>$car_id);
                    $this->saveCarOtherInfo($village_id,2,$pdata);
                }
            }
            if (!empty($data_print)) {
                $title = ['车辆编号(选填)','省份(必填)','车牌号码(必填)', '车库名称(必填)', '车位号(必填)', '停车卡号(选填)', '车主姓名(选填)','车主手机号(选填)','停车卡类(选填)','楼栋名称(选填)','单元名称(选填)','楼层名称(选填)','房间号(选填)','失败原因'];
                $res = $this->saveExcel($title, $data_print, '批量导入车辆失败数据列表' . time());
            }

        }
        return $res;

    }


    /**
     * 导出车位模板
     * @author:zhubaodi
     * @date_time: 2021/5/21 11:55
     */
    public function downPositionModel()
    {
        $data_print[]=['','测试车库','101,202','真实车位','已使用','10','','','','','','','','','2024-12-31'];
        $title = ['车位ID(请不要填写)', '车库名称(必填)', '车位号(必填)[支持英文逗号分隔填写多个]', '车位模式(选填)','车位状态(选填)','车位面积(选填)', '备注(选填)','楼栋名称(选填)','单元名称(选填)','楼层名称(选填)','房间号(选填)','业主姓名(选填)','业主手机号(选填)','开始时间(选填)','到期时间(选填)'];
        $res = $this->saveExcel($title, $data_print, '导入车位模板' . time(), 40);
        return $res;
    }


    /**
     * 导出车辆模板
     * @author:zhubaodi
     * @date_time: 2021/5/21 11:55
     */
    public function downCarModel()
    {
        $data_print[]=['','皖','A12345','测试车库','ceshi','11111111','测试车主','13812345678','临时车A','','','',''];
        $title = ['车辆编号(选填)','省份(必填)','车牌号码(必填)', '车库名称(必填)', '车位号(必填)', '停车卡号(选填)', '车主姓名(选填)','车主手机号(选填)','停车卡类(选填)','楼栋名称(选填)','单元名称(选填)','楼层名称(选填)','房间号(选填)'];
        $res = $this->saveExcel($title, $data_print, '导入车辆模板' . time());
        return $res;

    }


    /**
     *导出车位
     * @author: zhubaodi
     * @date : 2021/6/26
     */
    public function downPosition($data)
    {
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $houseVillageSingleDb= new HouseVillageSingle();
        $houseVillageFloorDb= new HouseVillageFloor();
        $houseVillageLayerDb= new HouseVillageLayer();
        $houseVillageUserVacancyDb= new HouseVillageUserVacancy();
        $houseVillageUserBindDb= new HouseVillageUserBind();
        $where[] = [ 'pp.position_pattern','=', $data['position_pattern']];
        $where[] = [ 'pp.village_id','=', $data['village_id']];
        $where[] = [ 'pp.position_type','<', 3];
        $village_id=$data['village_id'];
        if (!empty($data['garage_id'])){
            $where[] = [ 'pp.garage_id','=', $data['garage_id']];
        }
        if (!empty($data['position_num'])){
            $where[] = [ 'pp.position_num','=', $data['position_num']];
        }
        if (!empty($data['position_car_status'])){
            $where[] = [ 'pp.position_status','=', $data['position_car_status']];
        }
        if (!empty($data['position_status'])){
            if($data['position_status'] == 1){
                $where[] = ['c.car_id', 'exp', Db::raw('IS NULL')];
            }elseif ($data['position_status'] == 2){
                $where[] = ['c.car_id', 'exp', Db::raw('IS NOT NULL')];
            }
        }
        $list=$db_house_village_parking_position->getListss($where,'pp.*,pg.garage_num,c.car_id as is_bind_car',$page=0,15,'pp.position_id DESC','pp.position_id');
        $data_list=[];
        if (!empty($list)){
            $list=$list->toArray();
            $db_house_village_bind_position=new HouseVillageBindPosition();
            if (!empty($list)){
                foreach ($list as $k=>$vv){
                    $data_list[$k]['position_id']=$vv['position_id'];
                    $data_list[$k]['garage_num']=$vv['garage_num'];
                    $data_list[$k]['position_num']=$vv['position_num'];
                    if ($vv['position_pattern']==1){
                        $data_list[$k]['position_pattern_txt']='真实车位';
                    }else{
                        $data_list[$k]['position_pattern_txt']='虚拟车位';
                    }
                    if ($vv['is_bind_car']){
                        $data_list[$k]['position_status_txt']='已使用';
                    }else{
                        $data_list[$k]['position_status_txt']='空置';
                    }
                    $data_list[$k]['position_area']=$vv['position_area'];
                    $data_list[$k]['position_note']=$vv['position_note'];
                    $data_list[$k]['single_name']='';
                    $data_list[$k]['floor_name']='';
                    $data_list[$k]['layer_name']='';
                    $data_list[$k]['room_name']='';
                    $data_list[$k]['user_name']='';
                    $data_list[$k]['user_phone']='';
                    $bind_where=[];
                    $bind_where['position_id'] =$vv['position_id'];
                    $bind_where['village_id'] = $village_id;
                    $bind_info=$db_house_village_bind_position->getOne($bind_where);
                    if($bind_info && !$bind_info->isEmpty()){
                        $user_id=$bind_info['user_id'];
                        $whereTempArr=array();
                        $whereTempArr[]=array('pigcms_id','=',$user_id);
                        $whereTempArr[]=array('village_id','=',$village_id);
                        $user_bind_info=$houseVillageUserBindDb->getOne($whereTempArr);
                        if($user_bind_info && !$user_bind_info->isEmpty()){
                            $data_list[$k]['user_name']=$user_bind_info['name'];
                            $data_list[$k]['user_phone']=$user_bind_info['phone'];
                            $whereTempArr=array();
                            $whereTempArr[]=array('pigcms_id','=',$user_bind_info['vacancy_id']);
                            $whereTempArr[]=array('village_id','=',$village_id);
                            $userVacancy= $houseVillageUserVacancyDb->getOne($whereTempArr);
                            if($userVacancy && !$userVacancy->isEmpty()){
                                $data_list[$k]['room_name']=$userVacancy['room'];
                                $whereTempArr=array();
                                $whereTempArr[]=array('id','=',$userVacancy['single_id']);
                                $whereTempArr[]=array('village_id','=',$village_id);
                                $singleInfo= $houseVillageSingleDb->getOne($whereTempArr,'id,single_name,single_number');
                                if($singleInfo && !$singleInfo->isEmpty()){
                                    $data_list[$k]['single_name']=$singleInfo['single_name'];
                                }
                                $whereTempArr=array();
                                $whereTempArr[]=array('floor_id','=',$userVacancy['floor_id']);
                                $whereTempArr[]=array('village_id','=',$village_id);
                                $floorInfo= $houseVillageFloorDb->getOne($whereTempArr,'floor_id,floor_name,floor_number');
                                if($floorInfo && !$floorInfo->isEmpty()){
                                    $data_list[$k]['floor_name']=$floorInfo['floor_name'];
                                }

                                $whereTempArr=array();
                                $whereTempArr[]=array('id','=',$userVacancy['layer_id']);
                                $whereTempArr[]=array('floor_id','=',$userVacancy['floor_id']);
                                $whereTempArr[]=array('village_id','=',$village_id);
                                $layerInfo= $houseVillageLayerDb->getOne($whereTempArr,'id,layer_name,layer_number');
                                if($layerInfo && !$layerInfo->isEmpty()){
                                    $data_list[$k]['layer_name']=$layerInfo['layer_name'];
                                }
                                
                            }
                        }
                    }
                    $data_list[$k]['start_time']='';
                    if($vv['start_time']>100){
                        $data_list[$k]['start_time']=date('Y-m-d',$vv['start_time']);
                    }
                    $data_list[$k]['end_time']='';
                    if($vv['end_time']>100){
                        $data_list[$k]['end_time']=date('Y-m-d',$vv['end_time']);
                    }
                    $data_list[$k]['car_status_txt']='未售出';
                    if ($vv['position_status']==2){
                        $data_list[$k]['car_status_txt']='已售出';
                    }
                }
            }
        }
        if (!empty($data_list)){
            $filename='车位列表';
            $data1['list'] = $data_list;
            $title =['车位ID(请不要改动)', '车库名称', '车位号', '车位模式','车位状态','车位面积', '备注','楼栋名称','单元名称','楼层名称','房间号','业主姓名','业主手机号','开始时间','结束时间','租售状态'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }

    }

    /**
     *导出车辆
     * @author: zhubaodi
     * @date : 2021/6/26
     */
    public function downCar($data)
    {
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $houseVillageDb=new HouseVillage();
        
        $where=[];
        if (!empty($data['search_type'])&&!empty($data['search_value'])){
            switch ($data['search_type']) {
                case '1'://车牌号
                    $car_number_1 = preg_replace("/[\x{4e00}-\x{9fa5}]/iu", "", trim($data['search_value'])); //这样是去掉汉字
                    $where[] = ['a.car_number','like', '%' . $car_number_1 . '%'];
                    break;
                case '2': //使用状态
                    //todo 待修改
                    $where[] = ['a.car_stop_num','like', '%' . $data['search_value'] . '%'];
                    break;
                case '3'://车位号
                    $where[] = ['b.position_num','like', '%' . $data['search_value'] . '%'];
                    break;
            }
        }
        if(isset($data['record_date']) && !empty($data['record_date']) && is_array($data['record_date'])){
            if (!empty($data['record_date'][0])){
                $where[] = ['a.car_addtime','>=',strtotime($data['record_date'][0].' 00:00:00')];
            }
            if (!empty($data['record_date'][1])){
                $where[] = ['a.car_addtime','<=',strtotime($data['record_date'][1].' 23:59:59')];
            }
        }
        if(isset($data['pass_date']) && !empty($data['pass_date']) && is_array($data['pass_date'])){
            if (!empty($data['pass_date'][0])){
                $where[] = ['a.examine_time','>=',strtotime($data['pass_date'][0].' 00:00:00')];
            }
            if (!empty($data['pass_date'][1])){
                $where[] = ['a.examine_time','<=',strtotime($data['pass_date'][1].' 23:59:59')];
            }
        }
 
        $where[] = ['a.village_id','=',$data['village_id']];
        $field = 'a.*,b.position_num,b.garage_id,g.garage_num';
        $data_list=[];
        $info_list=$db_house_village_parking_car->getList($where,$field,$page=0);
        if (!empty($info_list)){
            $info_list=$info_list->toArray();
        }
        $park_sys_type_stored=array('A11','a11','A1','a1','D7','d7','D3','d3');
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $nowtime=time();
        $is_car_stored_func=0;
        $whereArr=array();
        $whereArr[]=array('village_id','=',$data['village_id']);
        $whereArr[]=array('park_versions','=',2);
        $garageConfigArr=$houseVillageParkGarageConfigService->getListData($whereArr,'park_sys_type,is_temporary_park_type');
        if($garageConfigArr){
            foreach ($garageConfigArr as $gvv){
                if(in_array($gvv['park_sys_type'],$park_sys_type_stored) && ($gvv['is_temporary_park_type']==1)){
                    //有储值功能，储值功能开启了
                    $is_car_stored_func=1;
                }
            }
        }
        if (!empty($info_list)) {
            foreach ($info_list as $k => $v) {
                $data_list[$k]['car_id']        = $v['car_id'];
                $data_list[$k]['province']      = $v['province'];
                $data_list[$k]['car_number']    = $v['car_number'];
                $data_list[$k]['garage_num']    = $v['garage_num'];
                $bind_position_ids=array();
                if(isset($v['bind_position_ids']) && !empty($v['bind_position_ids'])){
                    $bind_position_ids=explode(',',$v['bind_position_ids']);
                    $bind_position_ids=array_map('intval', $bind_position_ids);
                }
                if($v['car_position_id']){
                    $bind_position_ids[]=intval($v['car_position_id']);
                }
                $end_time=$v['end_time'];
                $start_time=$v['start_time'];
                if($bind_position_ids){
                    $bind_position_ids=array_unique($bind_position_ids);
                    $where_position=array(['village_id','=',$data['village_id']]);
                    $where_position[]=['position_id','in',$bind_position_ids];
                    $position_name_arr=array();
                    $position_list=$db_house_village_parking_position->getList($where_position,'start_time,position_num,end_time');
                    if($position_list && !$position_list->isEmpty()){
                        $position_list=$position_list->toArray();
                        foreach ($position_list as $ppv){
                            $position_name_arr[]=$ppv['position_num'];
                            if($v['car_type']<1 && $end_time<$ppv['end_time']){
                                $end_time=$ppv['end_time'];
                                $start_time=$ppv['start_time'];
                            }
                        }
                    }
                    if($position_name_arr){
                        $v['position_num']=implode('，',$position_name_arr);
                    }
                }
                $data_list[$k]['position_num']  = $v['position_num'];
                $data_list[$k]['car_stop_num']  = $v['car_stop_num'];
                $data_list[$k]['car_user_name'] = $v['car_user_name'];
                //$data_list[$k]['car_user_phone']= $v['car_user_phone'];
                $data_list[$k]['car_user_phone']=$houseVillageDb->phone_desensitization_v($v['car_user_phone'],$data['village_id'],$village_info);
                $end_time_str = $end_time>1 ? date('Y-m-d', $end_time) : '';
                if($start_time && $start_time>$nowtime && $end_time>$nowtime){
                    $end_time_str=date('Y-m-d', $start_time).' 至 '.$v['end_time'];
                }
                $data_list[$k]['end_time']      = $end_time_str;
                $data_list[$k]['parking_car_type']= '';
                $village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($v['garage_id'],$data['village_id']);
                if(isset($v['parking_car_type']) && !empty($v['parking_car_type']) && !empty($village_park_config)){
                    $parking_car_type_arr=$this->parking_car_type_arr;
                    if($village_park_config['park_sys_type']=='A11'){
                        $parking_car_type_arr=$this->parking_a11_car_type_arr;
                    }elseif ($village_park_config['park_sys_type']=='D3' || $village_park_config['park_sys_type']=='HXT85' ){
                        $parking_car_type_arr=$this->parking_d3_car_type_arr;

                    }else if($village_park_config['park_sys_type']=='D7'){
                        $parking_car_type_arr=$this->parking_d7_car_type_arr;
                    }
                    if(isset($parking_car_type_arr[$v['parking_car_type']]) && !empty($parking_car_type_arr[$v['parking_car_type']])){
                        $data_list[$k]['parking_car_type']= $parking_car_type_arr[$v['parking_car_type']];
                    }
                }
                if($is_car_stored_func){
                    $data_list[$k]['stored_balance']= $v['stored_balance']>0 ? $v['stored_balance']:'--'; 
                }
            }
        }
        if (!empty($data_list)){
            $filename='车辆列表';
            $data1 = $data_list;
            $title = ['车辆编号(请不要改动)','省份','车牌号码', '车库名称', '车位号', '停车卡号', '车主姓名','车主手机号','停车到期时间','停车卡类'];
            if($is_car_stored_func==1){
                $title[]='储值卡余额';
            }
            $res = $this->saveExcel($title, $data1, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }

    }

	public function parkingCarTypeArr($village_id=0,$garage_id=0)
	{
		$parking_car_type_arr = [];
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
		if ($house_village_park_config['park_sys_type'] == 'D3'||$house_village_park_config['park_sys_type'] == 'HXT85') {
			$parking_car_type_arr =$this->parking_d3_car_type_arr;
		} elseif ($house_village_park_config['park_sys_type'] == 'D7') {
			$parking_car_type_arr =$this->parking_d7_car_type_arr;
		} else {
			$parking_car_type_arr =$this->parking_car_type_arr;
			if ($house_village_park_config['is_park_month_type'] != 1) {
				unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
			}
			if ($house_village_park_config['is_temporary_park_type'] != 1) {
				unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
			}
		}
		
		return $parking_car_type_arr;
	}

    public function getAreaList($data)
    {
        $db_house_village_public_area = new HouseVillagePublicArea();
        $db_house_village_parking_garage = new HouseVillageParkingGarage();
        $area_list = $db_house_village_public_area->getList(['village_id' => $data['village_id'], 'status' => 1], 'public_area_id,public_area_name');
        $garage_list = $db_house_village_parking_garage->getLists(['village_id' => $data['village_id'], 'status' => 1], 'garage_id,garage_num');
        
        $db_house_village_park_config = new HouseVillageParkConfig();
        $whereConfig = [];
        $whereConfig[] = ['village_id', '=', $data['village_id']];
        $parkConfig = $db_house_village_park_config->getFind($whereConfig, 'id, village_id, park_sys_type');
        $defaultParkSysType = isset($parkConfig['park_sys_type']) && trim($parkConfig['park_sys_type']) ? trim($parkConfig['park_sys_type']) : 'A1';

        if (!empty($area_list)) {
            $area_list = $area_list->toArray();
        }
        $isHandle = isset($data['isHandle']) ? intval($data['isHandle']) : 0;
        if (!empty($garage_list) && !$garage_list->isEmpty()) {
            $garage_list = $garage_list->toArray();
            $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
            foreach ($garage_list as $kk => $gvv) {
                $garage_list[$kk]['park_sys_type'] = $defaultParkSysType;
                $garageConfig = $houseVillageParkGarageConfigService->getVillageParkConfig($gvv['garage_id'], $data['village_id']);
                if ($garageConfig && $garageConfig['park_sys_type']) {
                    $garage_list[$kk]['park_sys_type'] = $garageConfig['park_sys_type'];
                }
                if ($isHandle == 1 && isset($garage_list[$kk]['garage_num']) && isset($garage_list[$kk]['park_sys_type'])) {
                    $isHandle == 1 && $garage_list[$kk]['garage_num'] .= " [{$garage_list[$kk]['park_sys_type']}]";
                }
            }
        } else {
            $garage_list = array();
        }
        $data_list = [];
        
        if (!empty($area_list)) {
            foreach ($area_list as $v11) {
                $list = [];
                $list['id'] = $v11['public_area_id'];
                $list['garage_num'] = $v11['public_area_name'];
                $list['area_type'] = 1;
                $data_list[] = $list;
            }
        }

        return ['list' => $data_list, 'garage_list' => $garage_list];
    }
    
    public function getParkingGarageSelectList($data)
    {
        $db_house_village_parking_garage = new HouseVillageParkingGarage();
        $db_house_village_park_garage_config = new HouseVillageParkGarageConfig();
        $db_house_village_park_config = new HouseVillageParkConfig();
        
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        $isHandle = isset($data['isHandle']) ? intval($data['isHandle']) : 0;
        $whereConfig = [];
        $whereConfig[] = ['village_id', '=', $village_id];
        $parkConfig = $db_house_village_park_config->getFind($whereConfig, 'id, village_id, park_sys_type');
        $defaultParkSysType = isset($parkConfig['park_sys_type']) && trim($parkConfig['park_sys_type']) ? trim($parkConfig['park_sys_type']) : 'A1';
        
        $whereGarage = ['village_id' => $village_id, 'status' => 1];
        $garage_list = $db_house_village_parking_garage->getLists($whereGarage, 'garage_id,garage_num');
        if ($garage_list && !is_array($garage_list)) {
            $garage_list = $garage_list->toArray();
        }
        if (!empty($garage_list)) {
            $garageIds = array_unique(array_column($garage_list, 'garage_id'));
            $whereGarageConfig = [];
            $whereGarageConfig[] = ['village_id', '=', $village_id];
            $whereGarageConfig[] = ['garage_id', 'in', $garageIds];
            $garageConfigColumn = $db_house_village_park_garage_config->getColumn($whereGarageConfig, 'garage_id, village_id, park_sys_type', 'garage_id');
            foreach ($garage_list as &$garage) {
                $garage_id = isset($garage['garage_id']) ? intval($garage['garage_id']) : 0;
                $garage['park_sys_type'] = $defaultParkSysType;
                $garageConfig = isset($garageConfigColumn[$garage_id]) ? $garageConfigColumn[$garage_id] : [];
                $park_sys_type = isset($garageConfig['park_sys_type']) ? trim($garageConfig['park_sys_type']) : '';
                $park_sys_type && $garage['park_sys_type'] = $park_sys_type;
                $isHandle == 1 && $garage['garage_num'] .= " [{$garage['park_sys_type']}]";
            }
        }
        return $garage_list;
    }

    public function getParkCouponGarage($data)
    {
        $parkCouponInfo = $this->get_park_coupons_info($data);
        $garage_id = isset($parkCouponInfo['garage_id']) ? intval($parkCouponInfo['garage_id']) : 0;
        if ($garage_id <= 0) {
            return [];
        }
        $village_id = isset($parkCouponInfo['village_id']) ? intval($parkCouponInfo['village_id']) : 0;
        !$village_id && $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        $isHandle = isset($data['isHandle']) ? intval($data['isHandle']) : 0;
        $db_house_village_parking_garage = new HouseVillageParkingGarage();
        $whereGarage = [];
        $whereGarage[] = ['garage_id', '=', $garage_id];
        $garageInfo = $db_house_village_parking_garage->getOne($whereGarage, 'garage_id,village_id,garage_num');
        if ($garageInfo && !is_array($garageInfo)) {
            $garageInfo = $garageInfo->toArray();
        }
        if (empty($garageInfo)) {
            return  [];
        }
        !$village_id && $village_id = isset($garageInfo['village_id']) ? intval($garageInfo['village_id']) : 0;
        $db_house_village_park_garage_config = new HouseVillageParkGarageConfig();
        $whereGarageConfig = [];
        $whereGarageConfig[] = ['garage_id', '=', $garage_id];
        $garageConfig = $db_house_village_park_garage_config->getOneData($whereGarageConfig, 'garage_id, village_id, park_sys_type');
        if ($garageConfig && !is_array($garageConfig)) {
            $garageConfig = $garageConfig->toArray();
        }
        $park_sys_type = isset($garageConfig['park_sys_type']) ? trim($garageConfig['park_sys_type']) : '';
        if (!$park_sys_type) {
            $db_house_village_park_config = new HouseVillageParkConfig();
            $whereConfig = [];
            $whereConfig[] = ['village_id', '=', $village_id];
            $parkConfig = $db_house_village_park_config->getFind($whereConfig, 'id, village_id, park_sys_type');
            $park_sys_type = isset($parkConfig['park_sys_type']) && trim($parkConfig['park_sys_type']) ? trim($parkConfig['park_sys_type']) : 'A1';
        }
        $garageInfo['park_sys_type'] = $park_sys_type;
        $isHandle == 1 && $garageInfo['garage_num'] .= " [{$garageInfo['park_sys_type']}]";
        return $garageInfo;
    }
    
    /**
     * 查询不在场标签
     * @author:zhubaodi
     * @date_time: 2022/4/21 19:04
     */
    public function getParkLabelList($data){
        $db_house_village_label= new HouseVillageLabel();
        $label_list=$db_house_village_label->getList(['c.cat_function'=>$data['cat_function'],'c.status'=>1,'l.is_delete'=>0,'l.status'=>0],'l.*');
        return $label_list;
    }


    /**
     * 不在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function downOutPark($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['exception_type','=',2];
        $where[]=['accessType','=',2];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        $data_list=[];
        $list =$db_house_village_car_access_record->get_list($where,'park_name,label_id,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name,car_type');
        foreach ($list as $k=>&$val){
            if($val['accessTime']){
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['accessTime'] = '--';
            }
            $data_list[$k]['order_id']=strval($val['order_id']);
            $data_list[$k]['park_name']=$val['park_name'];
            $data_list[$k]['car_number']=$val['car_number'];
            $data_list[$k]['car_type']=$val['car_type'];
            $data_list[$k]['user_name']=$val['user_name'];
            $data_list[$k]['user_phone']=$val['user_phone'];
            $data_list[$k]['channel_name']=$val['channel_name'];
            $data_list[$k]['accessTime']=$val['accessTime'];
        }

        if (!empty($data_list)){
            $filename='不在场车辆记录';
            $title =['订单编号','车场','车牌号','车辆类型','车主姓名','车主手机号','出场通道','出场时间','标签'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }

    }


    /**
     * 手动开闸记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function downOpenGate($data,$hidephone = false){
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $village_park_config = $db_house_village_park_config->getFind(['village_id' => $data['village_id']]);
        $where = [];
        $where[] = ['park_id', '=', $data['village_id']];
        $where[] = ['park_sys_type', '=', $village_park_config['park_sys_type']];
        $where[] = ['accessMode', '=', 7];
        $where[] = ['del_time', '<', 1];
        if (!empty($data['date']) && count($data['date']) > 0) {
            if (!empty($data['date'][0])) {
                $where[] = ['accessTime', '>=', strtotime($data['date'][0] . ' 00:00:00')];
            }
            if (!empty($data['date'][1])) {
                $where[] = ['accessTime', '<=', strtotime($data['date'][1] . ' 23:59:59')];
            }
        }
        if (!empty($data['param']) && !empty($data['value'])) {
            if ($data['param'] == 1) {
                $where[] = ['user_name', 'like', '%' . $data['value'] . '%'];
            } elseif ($data['param'] == 2) {
                $where[] = ['user_phone', 'like', '%' . $data['value'] . '%'];

            } elseif ($data['param'] == 3) {
                $where[] = ['car_number', 'like','%'.$data['value'].'%'];

            }
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        $field = 'park_car_type,park_name,park_time,order_id,optname,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_number,channel_name as in_channel_name';
        $list = $db_house_village_car_access_record->get_list($where, $field);
        $data_list = [];
        foreach ($list as $k=>&$val){
            $val['accessType'] = $val['accessType'] == 1 ? '进场' : '出场';
            if ($val['accessTime']) {
                $val['accessTime'] = date('Y-m-d H:i:s', $val['accessTime']);
            } else {
                $val['accessTime'] = '--';
            }
            if ($hidephone && isset($val['user_phone']) && !empty($val['user_phone'])) {
                $val['user_phone'] = $houseVillageDb->phone_desensitization_v($val['user_phone'],$data['village_id'],$village_info);
            }
            $whereArr = array();
            if (!empty($val['channel_id'])) {
                $whereArr[] = array('id', '=', $val['channel_id']);
            } else if (!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            if ($whereArr) {
                $whereArr[] = array('village_id', '=', $data['village_id']);
                $garage_num = $this->getGarageByParkPassage($whereArr);
                if ($garage_num) {
                    $val['park_name'] = $garage_num;
                }
            }

            $dataItem = [];
            $dataItem['order_id'] = strval($val['order_id']);
            $dataItem['park_name'] = strval($val['park_name']);
            $dataItem['car_number'] = strval($val['car_number']);
            $dataItem['park_car_type'] = strval($val['park_car_type']);
            $dataItem['user_name'] = strval($val['user_name']);
            $dataItem['user_phone'] = strval($val['user_phone']);
            $dataItem['accessType'] = strval($val['accessType']);
            $dataItem['in_channel_name'] = strval($val['in_channel_name']);
            $dataItem['accessTime'] = strval($val['accessTime']);
            $dataItem['total'] = floatval($val['total']) > 0 ? getFormatNumber($val['total']) : '0.00';
            $dataItem['park_time'] = strval($val['park_time']);
            $dataItem['optname'] = strval($val['optname']);
            
            $data_list[] = $dataItem;
        }
        if (!empty($data_list)){
            $filename='手动开闸记录';
            $title =['订单编号','车场','车牌号','车辆卡类','用户姓名','用户手机号','通行方式','通行通道','通行时间','停车费用','停车时长','遥控器编号'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }
    }


    /**
     * 临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function downTempPark($data){
        if (isset($data['date_type']) && $data['date_type']==2){
            $retTmp=$this->downTempOutPark($data);
            return $retTmp;
            exit();
        }
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessMode','in',[3,4,6,9,7]];
        $where[] = ['accessType','=',1];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['car_number','like','%'.$data['value'].'%'];
            }
        }
        if(isset($data['place_value'])&&!empty($data['place_value'])){
            if($data['place_type']==2){
                $where[] = ['channel_name','like','%'.$data['place_value'].'%'];
            }else{
                $where[] = ['park_name','like','%'.$data['place_value'].'%'];
            }
        }
        $data_list=[];
        $list =$db_house_village_car_access_record->get_list($where,'park_name,order_id,total,accessType,accessImage,accessBigImage,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name,exception_type,car_type',0,100,'accessTime desc');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }else{
            $list=array();
        }
        if(empty($list)){
            throw new \think\Exception("暂无数据可以导出");
        }
        foreach ($list as $k=>&$val){
            $val['accessType'] =$val['accessType']==1?'进场':'出场';
            if($val['accessTime']){
                $accessTime = $val['accessTime'];
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $accessTime = 0;
                $val['accessTime'] = '--';
            }
            $out_info = [];
            $park_time_txt = '--';
            if (!empty($val['order_id'])){
                $whereOut = [];
                $whereOut[] = ['order_id', '=', $val['order_id']];
                $whereOut[] = ['accessType', '=', 2];
                $whereOut[] = ['accessTime', '>', $accessTime];
                $outField = 'park_time,totalMoney,prepayTotal,deductionTotal,total,pay_type,pay_time,order_id,accessType,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name,exception_type';
                $out_info = $db_house_village_car_access_record->getOne($whereOut,$outField, 'accessTime ASC');
                if ($out_info && !is_array($out_info)) {
                    $out_info = $out_info->toArray();
                } else {
                    $out_info = [];
                }
                if (isset($out_info['prepayTotal']) && $out_info['prepayTotal']) {
                    $out_info['total'] += $out_info['prepayTotal'];
                }
                if (isset($out_info['totalMoney']) && !$out_info['totalMoney']) {
                    $out_info['totalMoney'] = $out_info['total'];
                }
                if (isset($out_info['park_time']) && !$out_info['park_time'] && isset($out_info['accessTime'])) {
                    $park_time = $out_info['accessTime'] - $accessTime;
                    $out_info['park_time']    = $park_time > 0 ? $park_time : 0;
                }
            }
            $data_list[$k]['order_id']         = strval($val['order_id']);
            $data_list[$k]['park_name']        = $val['park_name'];
            $data_list[$k]['car_number']       = $val['car_number'];
            $data_list[$k]['car_type']         = $val['car_type'];
            $data_list[$k]['user_name']        = $val['user_name'];
            $data_list[$k]['user_phone']       = $val['user_phone'];
            $data_list[$k]['channel_name']     = $val['channel_name'];
            $data_list[$k]['accessTime']       = $val['accessTime'];
            $data_list[$k]['out_channel_name'] = isset($out_info['channel_name']) && !empty($out_info['channel_name']) ? $out_info['channel_name'] : '--';
            $data_list[$k]['out_accessTime']   = isset($out_info['accessTime']) && !empty($out_info['accessTime']) ? date('Y-m-d H:i:s',$out_info['accessTime']) : '--';

            if (isset($out_info['park_time']) && !empty($out_info['park_time'])) {
                $hours = intval($out_info['park_time'] / 3600);
                $minter = ceil(($out_info['park_time'] - $hours * 3600) / 60);
                if ($out_info['park_time'] >= 3600) {
                    $park_time_txt = $hours . '小时' . $minter . '分钟';
                } else {
                    $park_time_txt = $minter . '分钟';
                }

            } else {
                if (isset($out_info['exception_type']) &&  !empty($out_info['exception_type'])) {
                    $park_time_txt .= ($out_info['exception_type'] == 2 ? '(出场异常)' : '');
                } elseif (!empty($val['exception_type'])) {
                    $park_time_txt .= ($val['exception_type'] == 1 ? '(入场异常)' : '');
                }

            }
            fdump_api($out_info, '$out_info');

            $data_list[$k]['park_time']      = $park_time_txt;
            $data_list[$k]['totalMoney']     = isset($out_info['totalMoney'])     && !empty($out_info['totalMoney']) ? $out_info['totalMoney'] : '--';
            $data_list[$k]['deductionTotal'] = isset($out_info['deductionTotal']) && !empty($out_info['deductionTotal']) ? $out_info['deductionTotal'] : '--';
            $data_list[$k]['total']          = isset($out_info['total'])          && !empty($out_info['total']) ? $out_info['total'] : '--';
            $data_list[$k]['pay_type']       = isset($out_info['pay_type'])       && !empty($out_info['pay_type']) ? $out_info['pay_type'] : '--';
            $data_list[$k]['pay_time']       = isset($out_info['pay_time'])       && !empty($out_info['pay_time']) ? $out_info['pay_time'] : '--';
        }
        if (!empty($data_list)){
            $filename='临时车进出记录';
            $title =['订单编号','车场','车牌号','车辆类型','车主姓名','车主任手机号','入场通道','入场时间','出场通道','出场时间','停车时间','应付金额（元）','优惠券','实付金额（元）','支付类型','支付时间'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }
    }
    /**
     * 临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function downTempOutPark($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['a.park_id','=',$data['village_id']];
        $where[] = ['b.accessMode','in',[3,4,6,9,7]];
        $where[] = ['a.accessType','=',1];
        $where[] = ['a.del_time','<',1];
        $where[] = ['b.accessType','=',2];
        $where[] = ['b.park_id','=',$data['village_id']];
        $where[] = ['a.order_id', '<>', ''];
        $where[] = ['b.del_time','<',1];
        if(isset($data['pay_status']) && $data['pay_status']=='free'){
            $where[] = ['b.total','=',0];
        }else if(isset($data['pay_status']) && $data['pay_status']=='paied'){
            $where[] = ['b.total','>',0];
        }
        if(isset($data['stop_time']) && $data['stop_time']>0){
            //小时转化成秒
            $stop_time=$data['stop_time']*3600;
            $where['_string']='(b.accessTime-a.accessTime)<='.$stop_time;
        }
        //出场时间
        if (!empty($data['outdate'])&&count($data['outdate'])>0){
            if (!empty($data['outdate'][0])){
                $where[] = ['b.accessTime','>=',strtotime($data['outdate'][0].' 00:00:00')];
            }
            if (!empty($data['outdate'][1])){
                $where[] = ['b.accessTime','<=',strtotime($data['outdate'][1].' 23:59:59')];
            }
        }
        /*
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['a.accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['a.accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        */
        if (!empty($data['param'])&&!empty($data['value'])){
            if ($data['param']==1){
                $where[] = ['a.user_name','like','%'.$data['value'].'%'];
            }elseif($data['param']==2){
                $where[] = ['a.user_phone','like','%'.$data['value'].'%'];

            }elseif($data['param']==3){
                $where[] = ['a.car_number','like','%'.$data['value'].'%'];
            }
        }
        if(isset($data['place_value'])&&!empty($data['place_value'])){
            if($data['place_type']==2){
                $where[] = ['a.channel_name','like','%'.$data['place_value'].'%'];
            }else{
                $where[] = ['a.park_name','like','%'.$data['place_value'].'%'];
            }
        }
        $fieldStr='a.park_name,a.car_type,a.order_id,a.total,a.accessType,a.accessImage,a.accessBigImage,a.record_id,a.accessMode,a.user_name,a.user_phone,a.car_number,a.accessTime,a.channel_id,a.channel_name,a.exception_type';
        $fieldStr .=',b.total as o_total,b.totalMoney as o_totalMoney,b.deductionTotal as o_deductionTotal,b.pay_type as o_pay_type,b.pay_time as o_pay_time,b.exception_type as o_exception_type,b.prepayTotal as o_prepayTotal,b.park_time as o_park_time,b.accessType as o_accessType,b.record_id as o_record_id,b.accessMode as o_accessMode,b.user_name as o_user_name, b.user_phone as o_user_phone, b.accessTime as o_accessTime, b.channel_id as o_channel_id, b.channel_name as o_channel_name';
        $list = $db_house_village_car_access_record->getOutList($where,$fieldStr,0,100,'a.accessTime DESC, b.total DESC,b.record_id DESC');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }else{
            $list=array();
        }
        if(empty($list)){
            throw new \think\Exception("暂无数据可以导出");
        }
        $data_list=[];
        foreach ($list as $k=>&$val){
            $val['accessType'] =$val['accessType']==1?'进场':'出场';
            if($val['accessTime']){
                $accessTime = $val['accessTime'];
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $accessTime = 0;
                $val['accessTime'] = '--';
            }
            $park_time_txt = '--';
            if (!empty($val['order_id'])){
                if (isset($val['o_prepayTotal']) && $val['o_prepayTotal']) {
                    $val['o_total'] += $val['o_prepayTotal'];
                }
                if (isset($val['o_accessTime']) && $val['o_accessTime']>$accessTime) {
                    $park_time = $val['o_accessTime'] - $accessTime;
                    $val['o_park_time']    = $park_time > 0 ? $park_time : 0;
                }
            }
            if (isset($val['o_totalMoney']) && $val['o_totalMoney']<=0) {
                $val['o_totalMoney'] = $val['o_total'];
            }
            $data_list[$k]['order_id']         = strval($val['order_id']);
            $data_list[$k]['park_name']        = $val['park_name'];
            $data_list[$k]['car_number']       = $val['car_number'];
            $data_list[$k]['car_type']         = $val['car_type']=='temporaryCarNoPosition' ? '':$val['car_type'];
            $data_list[$k]['user_name']        = $val['user_name'];
            $data_list[$k]['user_phone']       = $val['user_phone'];
            $data_list[$k]['channel_name']     = $val['channel_name'];
            $data_list[$k]['accessTime']       = $val['accessTime'];
            $data_list[$k]['out_channel_name'] = isset($val['o_channel_name']) && !empty($val['o_channel_name']) ? $val['o_channel_name'] : '--';
            $data_list[$k]['out_accessTime']   = isset($val['o_accessTime']) && !empty($val['o_accessTime']) ? date('Y-m-d H:i:s',$val['o_accessTime']) : '--';

            if (isset($val['o_park_time']) && !empty($val['o_park_time'])) {
                $hours = intval($val['o_park_time'] / 3600);
                $minter = ceil(($val['o_park_time'] - $hours * 3600) / 60);
                if ($val['o_park_time'] >= 3600) {
                    $park_time_txt = $hours . '小时' . $minter . '分钟';
                } else {
                    $park_time_txt = $minter . '分钟';
                }

            } else {
                if (isset($val['o_exception_type']) &&  !empty($val['o_exception_type'])) {
                    $park_time_txt .= ($val['o_exception_type'] == 2 ? '(出场异常)' : '');
                } elseif (!empty($val['exception_type'])) {
                    $park_time_txt .= ($val['exception_type'] == 1 ? '(入场异常)' : '');
                }

            }

            $data_list[$k]['park_time']      = $park_time_txt;
            $data_list[$k]['totalMoney']     = isset($val['o_totalMoney'])     && !empty($val['o_totalMoney']) ? $val['o_totalMoney'] : '--';
            $data_list[$k]['deductionTotal'] = isset($val['o_deductionTotal']) && !empty($val['o_deductionTotal']) ? $val['o_deductionTotal'] : '--';
            $data_list[$k]['total']          = isset($val['o_total'])          && !empty($val['o_total']) ? $val['o_total'] : '--';
            $data_list[$k]['pay_type']       = isset($val['o_pay_type'])       && !empty($val['o_pay_type']) ? $val['o_pay_type'] : '--';
            $data_list[$k]['pay_time']       = isset($val['o_pay_time'])       && !empty($val['o_pay_time']) ? $val['o_pay_time'] : '--';
        }
        unset($list);
        if (!empty($data_list)){
            $filename='临时车进出记录';
            $title =['订单编号','车场','车牌号','车辆类型','车主姓名','车主任手机号','入场通道','入场时间','出场通道','出场时间','停车时间','应付金额（元）','优惠券','实付金额（元）','支付类型','支付时间'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }
    }

    /**
     * 月租车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function downMonthPark($data){
        if (isset($data['date_type']) && $data['date_type']==2){
            $retTmp=$this->downMonthOutPark($data);
            return $retTmp;
            exit();
        }
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessMode','=',5];
        $where[]=['accessType','=',1];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        $list =$db_house_village_car_access_record->get_list($where,'order_id,accessType,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name,car_type');
        $data_list=[];
        foreach ($list as $k=>&$val){
            if($val['accessTime']){
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['accessTime'] = '--';
            }
            $out_info=[];
            if (!empty($val['order_id'])){
                $out_info=$db_house_village_car_access_record->getOne(['order_id'=>$val['order_id'],'accessType'=>2],'order_id,accessType,record_id,accessMode,user_name,user_phone,car_number,accessTime,channel_id,channel_name');
                if($out_info && isset($out_info['accessTime'])){
                    $park_time = $out_info['accessTime'] - $val['accessTime'];
                    $val['park_time'] = $park_time > 0 ? $park_time : 0;
                }

            }
            $whereArr = array();
            if (!empty($val['channel_id'])) {
                $whereArr[] = array('id', '=', $val['channel_id']);
            } else if (!empty($val['channel_number'])) {
                $whereArr[] = array('channel_number', '=', $val['channel_number']);
            }
            $val['park_name']='';
            if ($whereArr) {
                $whereArr[] = array('village_id', '=', $data['village_id']);
                $garage_num = $this->getGarageByParkPassage($whereArr);
                if ($garage_num) {
                    $val['park_name'] = $garage_num;
                }
            }

            if (!empty($val['park_time'])) {
                $hours = intval($val['park_time'] / 3600);
                $minter = ceil(($val['park_time'] - $hours * 3600) / 60);
                if ($val['park_time'] >= 3600) {
                    $val['park_time'] = $hours . '小时' . $minter . '分钟';
                } else {
                    $val['park_time'] = $minter . '分钟';
                }
            } else {
                $val['park_time'] = '--';
                if (!empty($val['o_exception_type'])) {
                    $val['park_time'] .= ($val['o_exception_type'] == 2 ? '(出场异常)' : '');
                } elseif (!empty($val['exception_type'])) {
                    $val['park_time'] .= ($val['exception_type'] == 1 ? '(入场异常)' : '');
                }

            }
            
            $data_list[$k]['user_name']=$val['user_name'];
            $data_list[$k]['user_phone']=$val['user_phone'];
            $data_list[$k]['park_name']=$val['park_name'];
            $data_list[$k]['car_number']=$val['car_number'];
            $data_list[$k]['channel_name']=$val['channel_name'];
            $data_list[$k]['accessTime']=$val['accessTime'];
            $data_list[$k]['out_channel_name']=empty($out_info['channel_name'])?'--':$out_info['channel_name'];
            $data_list[$k]['out_accessTime']=empty($out_info['accessTime'])?'--':$out_info['accessTime'];
            $data_list[$k]['park_time'] = $val['park_time'];
            $data_list[$k]['order_id']=strval($val['order_id']);
        }
        if (!empty($data_list)){
            $filename='月租车进出记录';
            $title =['用户姓名','用户手机号','车场名称','车牌号','入场通道','入场时间','出场通道','出场时间','停车时间','订单编号'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }
    }
    public function  downMonthOutPark($data=array()){
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();

        $where = [];
        $where[] = ['park_id', '=', $data['village_id']];
        $where[] = ['accessMode', '=', 5];
        // $where[] = ['b.accessType', '=', 1];
        // $where[] = ['b.del_time', '<', 1];
        $where[] = ['accessType', '=', 2];
        //$where[] = ['a.order_id', '<>', ''];
        $where[] = ['del_time', '<', 1];
        //出场时间
        if (!empty($data['outdate']) && count($data['outdate']) > 0) {
            if (!empty($data['outdate'][0])) {
                $where[] = ['accessTime', '>=', strtotime($data['outdate'][0] . ' 00:00:00')];
            }
            if (!empty($data['outdate'][1])) {
                $where[] = ['accessTime', '<=', strtotime($data['outdate'][1] . ' 23:59:59')];
            }
        }
        //进场时间
        /*
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        */
        if (!empty($data['param']) && !empty($data['value'])) {
            if ($data['param'] == 1) {
                $where[] = ['user_name', 'like', '%' . $data['value'] . '%'];
            } elseif ($data['param'] == 2) {
                $where[] = ['user_phone', 'like', '%' . $data['value'] . '%'];

            } elseif ($data['param'] == 3) {
                $where[] = ['car_number', 'like', '%' . $data['value'] . '%'];

            }
        }
        $listObj =$db_house_village_car_access_record->get_list($where,'*', $data['page'], $data['limit']);

        //$fieldStr = 'a.*,b.exception_type as i_exception_type,b.park_time as i_park_time,b.accessType as i_accessType,b.record_id as i_record_id,b.accessMode as i_accessMode,b.user_name as i_user_name, b.user_phone as i_user_phone, b.accessTime as i_accessTime, b.channel_id as i_channel_id, b.channel_name as i_channel_name,b.channel_number as i_channel_number';
        //$listObj = $db_house_village_car_access_record->getOutList($where, $fieldStr, $data['page'], $data['limit'], 'a.accessTime DESC,a.record_id DESC');
        $list = '';
        if ($listObj && !$listObj->isEmpty()) {
            $list = $listObj->toArray();
        }
        if(empty($list)){
            throw new \think\Exception("暂无数据可以导出");
        }
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $carParkCarTypeArr = [];
        $data_list=[];
        if ($list) {
            $fieldInStr = 'record_id,business_id,car_number,accessType,order_id,accessTime,exception_type,park_time,accessMode,user_name,user_phone,channel_id,channel_name,channel_number';
            foreach ($list as $k=>$val) {
                $val['o_record_id'] = $val['record_id'];
                $in_info = [];
                $in_where = array();
                $in_where[] = ['accessTime', '<', $val['accessTime']];
                $in_where[] = ['park_id', '=', $data['village_id']];
                $in_where[] = ['car_number', '=', $val['car_number']];
                $in_where[] = ['accessType', '=', 1];
                $in_where[] = ['del_time', '<', 1];
                if (!empty($val['order_id'])) {
                    $in_where[] = ['order_id', '=', $val['order_id']];
                }
                $in_info_obj = $db_house_village_car_access_record->getOne($in_where, $fieldInStr, 'record_id DESC');
                if ($in_info_obj && !$in_info_obj->isEmpty()) {
                    $in_info = $in_info_obj->toArray();
                }
                $val['park_time'] = $val['park_time'] ? $val['park_time'] : 0;
                $val['in_accessTime'] = '--';
                $val['in_channel_name'] = ' ';
                $in_exception_type=0;
                if ($in_info && isset($in_info['record_id'])) {
                    $park_time = $val['accessTime'] - $in_info['accessTime'];
                    $val['park_time'] = $park_time > 0 ? $park_time : 0;
                    $val['record_id'] = $in_info['record_id'];
                    $in_exception_type=$in_info['exception_type'];
                    if ($in_info['accessTime'] > 100) {
                        $val['in_accessTime'] = date('Y-m-d H:i:s', $in_info['accessTime']);
                    }
                    if (!empty($in_info['channel_name'])) {
                        $val['in_channel_name'] = $in_info['channel_name'];
                    }
                    if ( !empty($in_info['user_phone'])) {
                        $val['user_phone'] =$in_info['user_phone'];
                    }
                    if (empty($val['user_name']) && !empty($in_info['user_name'])) {
                        $val['user_name'] = $in_info['user_name'];
                    }
                    $whereArr = array();
                    if (!empty($in_info['channel_id'])) {
                        $whereArr[] = array('id', '=', $in_info['channel_id']);
                    } else if (!empty($in_info['channel_number'])) {
                        $whereArr[] = array('channel_number', '=', $in_info['channel_number']);
                    }
                    if ($whereArr) {
                        $whereArr[] = array('village_id', '=', $data['village_id']);
                        $garage_num = $this->getGarageByParkPassage($whereArr);
                        if ($garage_num) {
                            $val['park_name'] = $garage_num;
                        }
                    }
                }
                if ( isset($val['user_phone']) && !empty($val['user_phone'])) {
                    $val['user_phone'] = $val['user_phone'];
                }
                $val['record_id_index'] = $val['record_id'] . '_' . $val['o_record_id'];
                $val['accessType'] = '出场';
                if ($val['accessTime'] > 100) {
                    $val['out_accessTime'] = date('Y-m-d H:i:s', $val['accessTime']);
                } else {
                    $val['out_accessTime'] = '--';
                }
                if (!empty($val['channel_name'])) {
                    $val['out_channel_name'] = $val['channel_name'];
                } else {
                    $val['out_channel_name'] = ' ';
                }
                if (!empty($val['park_time'])) {
                    $hours = intval($val['park_time'] / 3600);
                    $minter = ceil(($val['park_time'] - $hours * 3600) / 60);
                    if ($val['park_time'] >= 3600) {
                        $val['park_time'] = $hours . '小时' . $minter . '分钟';
                    } else {
                        $val['park_time'] = $minter . '分钟';
                    }
                } else {
                    $val['park_time'] = '--';
                    if (!empty($val['exception_type'])) {
                        $val['park_time'] .= ($val['exception_type'] == 2 ? '(出场异常)' : '');
                    } elseif (!empty($in_exception_type)) {
                        $val['park_time'] .= ($in_exception_type == 1 ? '(入场异常)' : '');
                    }

                }
                $car_number = isset($val['car_number']) ? trim($val['car_number']) : '';
                if (isset($val['park_car_type']) || !$val['park_car_type']) {
                    if (isset($carParkCarTypeArr[$car_number]) && $carParkCarTypeArr[$car_number]) {
                        $val['park_car_type'] = $carParkCarTypeArr[$car_number];
                    } else {
                        $province = mb_substr($car_number, 0, 1);
                        $car_no = mb_substr($car_number, 1);
                        $whereCar = ['province' => $province, 'car_number' => $car_no, 'village_id' => $data['village_id']];
                        $car_info = $db_house_village_parking_car->getFind($whereCar, 'car_id, parking_car_type');
                        $parking_car_type = isset($car_info['parking_car_type']) ? $car_info['parking_car_type'] : '';
                        $val['park_car_type'] = $this->parking_a11_car_type_arr[$parking_car_type] ?? '';
                        $val['park_car_type'] && $carParkCarTypeArr[$car_number] = $val['park_car_type'];
                    }
                } else {
                    $carParkCarTypeArr[$car_number] = $val['park_car_type'];
                }
                $data_list[$k]['user_name']=strval($val['user_name']);
                $data_list[$k]['user_phone']=strval($val['user_phone']);
                $data_list[$k]['park_name']=$val['park_name'];
                $data_list[$k]['car_number']=$val['car_number'];
                $data_list[$k]['channel_name']=$val['in_channel_name'];
                $data_list[$k]['accessTime']=$val['in_accessTime'];
                $data_list[$k]['out_channel_name']=$val['channel_name'];
                $data_list[$k]['out_accessTime']=$val['out_accessTime'];
                $data_list[$k]['park_time']=$val['park_time'];
                $data_list[$k]['order_id']=strval($val['order_id']);
            }
        }
        unset($list);
        if (!empty($data_list)){
            $filename='月租车进出记录';
            $title =['用户姓名','用户手机号','车场名称','车牌号','入场通道','入场时间','出场通道','出场时间','停车时间','订单编号'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }

    }
    /**
     * 在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function downInPark($data){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $where = [];
        $where[] = ['park_id','=',$data['village_id']];
        $where[] = ['accessType','=',1];
        $where[] = ['is_out','=',0];
        $where[] = ['del_time','<',1];
        if (!empty($data['date'])&&count($data['date'])>0){
            if (!empty($data['date'][0])){
                $where[] = ['accessTime','>=',strtotime($data['date'][0].' 00:00:00')];
            }
            if (!empty($data['date'][1])){
                $where[] = ['accessTime','<=',strtotime($data['date'][1].' 23:59:59')];
            }
        }
        $list =$db_house_village_car_access_record->get_list($where,'order_id,record_id,user_name,user_phone,car_number,accessTime,park_name,channel_id,channel_name');
        $data_list=[];
        foreach ($list as $k=>&$val){
            $val['car_type']='汽车';
            if($val['accessTime']){
                $val['accessTime'] = date('Y-m-d H:i:s',$val['accessTime']);
            }else{
                $val['accessTime'] = '--';
            }
            $data_list[$k]['order_id']=strval($val['order_id']);
            $data_list[$k]['park_name']=$val['park_name'];
            $data_list[$k]['car_number']=$val['car_number'];
            $data_list[$k]['car_type']=$val['car_type'];
            $data_list[$k]['user_name']=$val['user_name'];
            $data_list[$k]['user_phone']=$val['user_phone'];
            $data_list[$k]['channel_name']=$val['channel_name'];
            $data_list[$k]['accessTime']=$val['accessTime'];

        }
        if (!empty($data_list)){
            $filename='在场车辆记录';
            $title =['订单编号','车场','车牌号','车辆类型','车主姓名','车主任手机号','入场通道','入场时间'];
            $res = $this->saveExcel($title, $data_list, $filename . time());
            return $res;
        }else{
            throw new \think\Exception("暂无数据");
        }
    }

    /**
     * 智慧停车场批量同步
     * @author:zhubaodi
     * @date_time: 2022/4/28 13:43
     */
    public function sysParkCarDevice($data,$xtype=''){
        if($xtype=='third_ai_park'){
            $configCustomizationService = new ConfigCustomizationService();
            $isAihorseCustomizedJudge = $configCustomizationService->getAihorseCustomizedJudge();
            $db_house_village_ParkIng_garage=new HouseVillageParkingGarage();
            $job_id='';
            $cacheKey = 'village:cache:'.$data['village_id'].':syn_third_ai_park';
            $syn_flag     = Cache::get($cacheKey);
            if(!$syn_flag){
                Cache::set($cacheKey,300,300);
            }
            if (!$syn_flag && $isAihorseCustomizedJudge) {
                $garageObj = $db_house_village_ParkIng_garage->getLists(array(['village_id', '=', $data['village_id']], ['status', '=', 1], ['third_park_key', '<>', '']),'garage_id,village_id,third_park_key');
                if ($garageObj && !$garageObj->isEmpty()) {
                    $garages=$garageObj->toArray();
                    $queueData = array('village_id' => $data['village_id'], 'garages' => $garages, 'opt_type' => 'update_garage_third_ai_park_car');
                    $job_id= $this->parkPassageQueuePushLaterToJob($queueData);
                }
            }
            return array('is_job_exe'=>1,'job_id'=>$job_id,'syn_flag'=>$syn_flag);
        }else{
            $res=invoke_cms_model('House_village_parking_car/sysParkCarDevice',['village_id'=>$data['village_id']], true);
            //一键同步D6智慧停车
            (new D6Service())->d6synVehicle($data['village_id']);
            return $res;
        }

    }

    public function open_gate($data)
    {
        $db_park_passage = new ParkPassage();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_park_open_log = new ParkOpenLog();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village = new HouseVillage();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $res = '0';
        $now_time = time();
        $garage_id = 0;
        $village_id = $data['village_id'];
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $passage_info = $carHandleGetInfoService->getParkPassageInfoById($data['id'], $village_id);
        if (isset($passage_info['garage_id']) && intval($passage_info['garage_id']) > 0) {
            $garage_id = intval($passage_info['garage_id']);
        } elseif (isset($passage_info['area_type']) && isset($passage_info['passage_area']) && intval($passage_info['area_type']) == 2 && intval($passage_info['passage_area']) > 0) {
            $garage_id = intval($passage_info['passage_area']);
        } else {
            $garage_id = 0;
        }
        $park_sys_type = (new HouseVillageParkingService())->checkVillageParkConfig($village_id, $garage_id);
        $houseVillageCarFlowService = new HouseVillageCarFlowService();
        $park_config_info = $carHandleGetInfoService->getParkConfig($village_id, $garage_id);
        !$park_sys_type && $park_sys_type = isset($park_config_info['park_sys_type']) ? trim($park_config_info['park_sys_type']) : '';
        $res='0';
        $now_time = time();
        $serialno = isset($passage_info['device_number']) ? trim($passage_info['device_number']) : '';
        $toNew = $houseVillageCarFlowService->judgeToNew($serialno);
        $isA11 = false;
        $park_sys_type == 'A11' && $isA11 = true;
        $village_info = $db_house_village->getOne($village_id, 'village_name');
        if($passage_info['park_sys_type']=='third_ai_park'){
            $toNew=false;
            $park_sys_type='third_ai_park';
            $houseNewAiParkingService=new HouseNewAiParkingService();
            $retGate=$houseNewAiParkingService->openHandlePassageGate($village_id,$data['id'],'',$garage_id);
            if($retGate && empty($retGate['data']) && $retGate['err_code']==1){
                throw new \think\Exception($retGate['err_msg']);
            }
            $car_access_record = [];
            $car_access_record['channel_id'] = $passage_info['id'];
            $car_access_record['channel_number'] = $passage_info['channel_number'];
            $car_access_record['channel_name'] = $passage_info['passage_name'];
            $park_where = [
                ['car_number', '=', $data['car_number']],
                ['park_id', '=', $passage_info['village_id']],
                ['accessType', '=', 1],
                ['is_out', '=', 0],
            ];
            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
            if (empty($park_info_car111)) {
                $car_access_record['exception_type'] = 2;
            } else {
                $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
            }
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $passage_info['village_id'];
            $car_access_record['car_number'] = $data['car_number'];
            $car_access_record['accessType'] = 2;
            $car_access_record['coupon_id'] =0;
            $car_access_record['park_time'] = $data['park_time'] * 60;
            $car_access_record['total'] = $data['price'];
            $car_access_record['accessTime'] = $now_time;
            if (empty($park_info_car111)) {
                $car_access_record['exception_type'] = 2;
            } else {
                $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
            }
            $car_access_record['accessMode'] = 7;
            $car_access_record['park_sys_type'] = $park_sys_type;
            $car_access_record['is_out'] = 1;
            $car_access_record['park_id'] = $passage_info['village_id'];
            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
            $car_access_record['order_id'] = $park_info_car111 && $park_info_car111['order_id'] ? $park_info_car111['order_id']:'';
            $car_access_record['pay_type'] = 'cash';
            $car_access_record['garage_id']=$garage_id;
            $car_access_record['update_time'] = $now_time;
            $car_access_record['service_name']=json_encode($retGate['data'],JSON_UNESCAPED_UNICODE);
            $car_access_record['token']=$retGate['data']['parkKey'];
            $car_access_record['park_car_type'] =  $retGate['data']['parkKey'];
            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            if($record_id>0){
                $access_record_where_near = [
                    ['car_number', '=', $data['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=',1],
                    ['is_out', '=', 0],
                    ['del_time', '<', 1],
                    ['record_id', '<', $record_id],
                ];
                $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
            }
            return $retGate;
        }
        
        if (in_array($park_sys_type,['D3','A11','HXT85'])){
            if (!empty($passage_info)){
                $where='';
                if ($passage_info['passage_direction'] == 1) {
                    if (!empty($data['car_number'])) {
                        $park_log_data = [];
                        $park_log_data['open_type'] = 3;
                        $park_log_data['car_number'] = $data['car_number'];
                        $park_log_data['channel_id'] = $passage_info['device_number'];
                        $park_log_data['park_type'] = $passage_info['passage_direction'] == 1 ? 1 : 2;
                        $park_log_data['add_time'] = $now_time;
                        $res = $db_park_open_log->add($park_log_data);
                        return $res;
                    }
                    $start_time = $now_time - 300;
                    $end_time = $now_time + 150;
                    $where = [
                        ['park_type', '=', 1],
                        ['channel_id', '=', $passage_info['device_number']],
                        ['add_time', '>=', $start_time],
                        ['add_time', '<=', $end_time]
                    ];
                }
                $car_number = isset($data['car_number']) && trim($data['car_number']) ? trim($data['car_number']) : '';
                if ($toNew && $passage_info['passage_direction'] == 0 && $car_number) {
                    $commonData = $data;
                    $commonData['open_type'] = 3;
                    $commonData['nowTime'] = $now_time;
                    (new CarHandleGetInfoService())->commonExceptionOutHandle($commonData, $passage_info);
                    $park_log_data = [];
                    $park_log_data['open_type'] = 3;
                    $park_log_data['car_number'] = $data['car_number'];  //有车牌心跳时会产生进出 记录
                    $park_log_data['channel_id'] = $passage_info['device_number'];
                    $park_log_data['park_type'] = $passage_info['passage_direction'] == 1 ? 1 : 2;
                    $park_log_data['add_time'] = time();
                    $res = $db_park_open_log->add($park_log_data);
                    return $res;
                }
                if ($toNew && $passage_info['passage_direction'] == 0) {
                    $start_time = $now_time - 300;
                    $end_time = $now_time + 150;
                    $where = [
                        ['park_type', '=', 2],
                        ['channel_id', '=', $passage_info['device_number']],
                        ['add_time', '>=', $start_time],
                        ['add_time', '<=', $end_time],
                    ];
                }
                if (!$toNew && $passage_info['passage_direction'] == 0) {
                    // 出场 上面已经给处理过 先屏蔽
                    if ($car_number) {
                        $car_access_record = [];
                        $car_access_record['channel_id'] = $passage_info['id'];
                        $car_access_record['channel_number'] = $passage_info['channel_number'];
                        $car_access_record['channel_name'] = $passage_info['passage_name'];
                        
                        $whereInRecord = ['car_number' => $data['car_number'], 'park_id' => $village_id, 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
                        $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
                        if (!empty($in_park_pay_info)) {
                            $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $village_id, 'park_sys_type' => $park_sys_type, 'is_out' => 1, 'del_time' => 0];
                            $out_park_info = $db_in_park->getOne1($whereInOut);
                            if (empty($out_park_info)){
                                $park_data = [];
                                $park_data['out_time'] = $now_time;
                                $park_data['is_out'] = 1;
                                // todo 同步更新下中间表
                                $whereInPark = ['car_number' => $car_number, 'park_id' => $village_id, 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                                $res_in = $db_in_park->saveOne($whereInPark, $park_data);
                                fdump_api([$res_in, $res_in, $data['car_number'], $passage_info['village_id']], 'open_gate_0519', 1);
                                $insert_id=0;
                                /*
                                $out_data = [];
                                $out_data['car_number'] = $data['car_number'];
                                if (!empty($in_park_pay_info)) {
                                    $out_data['in_time'] = $in_park_pay_info['in_time'];
                                    $out_data['order_id'] = $in_park_pay_info['order_id'];
                                }
                                $out_data['out_time'] = $now_time;
                                $out_data['park_id'] = $passage_info['village_id'];
                                $out_data['pay_type'] = 'cash';
                                $out_data['total'] = $data['price'];
                                $starttime = time() - 60;
                                $endtime = time() + 10;
                                $park_where = [
                                    ['car_number', '=', $data['car_number']],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['out_time', '>=', $starttime],
                                    ['out_time', '<=', $endtime],
                                ];
                                fdump_api([$park_where, $out_data], 'open_gate_0519', 1);
                                $park_info_car = $db_out_park->getOne($park_where);
                                //写入车辆入场表
                                if (empty($park_info_car)) {
                                    $insert_id = $db_out_park->insertOne($out_data);
                                }
                                */
                                $park_where = [
                                    ['car_number', '=', $data['car_number']],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['accessType', '=', 1],
                                    ['is_out', '=', 0],
                                ];
                                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                fdump_api([$park_info_car111, $park_where], 'open_gate_0519', 1);
                                $car_access_record['business_type'] = 0;
                                $car_access_record['business_id'] = $passage_info['village_id'];
                                $car_access_record['car_number'] = $data['car_number'];
                                $car_access_record['accessType'] = 2;
                                $car_access_record['coupon_id'] =  0;
                                $car_access_record['park_time'] = $data['park_time'] * 60;
                                $car_access_record['total'] = $data['price'];
                                $car_access_record['accessTime'] = $now_time;
                                if (empty($park_info_car111)) {
                                    $car_access_record['exception_type'] = 2;
                                } else {
                                    $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                                }
                                $car_access_record['accessMode'] = 7;
                                $car_access_record['park_sys_type'] = $park_sys_type;
                                $car_access_record['is_out'] = 1;
                                $car_access_record['park_id'] = $passage_info['village_id'];
                                $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                                $car_access_record['order_id'] = $park_info_car111['order_id'];
                                $car_access_record['garage_id']=$garage_id;
                                //  $car_access_record['total'] = 0;
                                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                $car_access_record['pay_type'] = 'cash';
                                $car_access_record['update_time'] = $now_time;
                                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                if (isset($insert_id)) {
                                    $car_access_record['from_id'] = $insert_id;
                                }
                                fdump_api([$car_access_record], 'open_gate_0519', 1);
                                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                                if($record_id>0){
                                    $access_record_where_near = [
                                        ['car_number', '=', $data['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['accessType', '=',1],
                                        ['is_out', '=', 0],
                                        ['del_time', '<', 1],
                                        ['record_id', '<', $record_id],
                                    ];
                                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                                }
                                fdump_api([$record_id, $car_access_record], 'open_gate_0519', 1);
                            } elseif ($out_park_info['out_time'] > $in_park_pay_info['in_time']) {
                                $insert_id=0;
                                /*
                                $out_data = [];
                                $out_data['car_number'] = $data['car_number'];
                                $out_data['out_time'] = $now_time;
                                $out_data['park_id'] = $passage_info['village_id'];
                                $out_data['pay_type'] = 'cash';
                                $out_data['total'] = $data['price'];
                                $starttime = time() - 30;
                                $endtime = time() + 30;
                                $park_where = [
                                    ['car_number', '=', $data['car_number']],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['out_time', '>=', $starttime],
                                    ['out_time', '<=', $endtime],
                                ];
                                fdump_api([$park_where, $out_data], 'open_gate_0519', 1);
                                $park_info_car = $db_out_park->getOne($park_where);
                                //写入车辆入场表
                                if (empty($park_info_car)) {
                                    $insert_id = $db_out_park->insertOne($out_data);
                                }
                                */
                                $car_access_record['business_type'] = 0;
                                $car_access_record['business_id'] = $passage_info['village_id'];
                                $car_access_record['car_number'] = $data['car_number'];
                                $car_access_record['accessType'] = 2;
                                $car_access_record['coupon_id'] = 0;
                                $car_access_record['park_time'] = $data['park_time'] * 60;
                                $car_access_record['total'] = $data['price'];
                                $car_access_record['accessTime'] = $now_time;
                                $car_access_record['exception_type'] = 2;
                                $car_access_record['accessMode'] = 7;
                                $car_access_record['park_sys_type'] = $park_sys_type;
                                $car_access_record['is_out'] = 1;
                                $car_access_record['park_id'] = $passage_info['village_id'];
                                $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                                $car_access_record['pay_type'] = 'cash';
                                $car_access_record['update_time'] = $now_time;
                                $car_access_record['garage_id']=$garage_id;
                                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                if (isset($insert_id)) {
                                    $car_access_record['from_id'] = $insert_id;
                                }
                                fdump_api([$car_access_record], 'open_gate_0519', 1);
                                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                                if($record_id>0){
                                    $access_record_where_near = [
                                        ['car_number', '=', $data['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['accessType', '=',1],
                                        ['is_out', '=', 0],
                                        ['del_time', '<', 1],
                                        ['record_id', '<', $record_id],
                                    ];
                                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                                }
                            }
                            elseif ($out_park_info['out_time']>$in_park_pay_info['in_time']){
                                $insert_id=0;
                                    /*
                                    $out_data = [];
                                    $out_data['car_number'] = $data['car_number'];
                                    $out_data['out_time'] = $now_time;
                                    $out_data['park_id'] = $passage_info['village_id'];
                                    $out_data['pay_type'] = 'cash';
                                    $out_data['total'] = $data['price'];
                                    $starttime = time() - 60;
                                    $endtime = time() + 10;
                                    $park_where = [
                                        ['car_number', '=', $data['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['out_time', '>=', $starttime],
                                        ['out_time', '<=', $endtime],
                                    ];
                                    fdump_api([$park_where,$out_data],'open_gate_0519',1);
                                    $park_info_car = $db_out_park->getOne($park_where);
                                    //写入车辆入场表
                                    if (empty($park_info_car)) {
                                        $insert_id= $db_out_park->insertOne($out_data);
                                    }
                                    */
                                    $car_access_record['business_type'] = 0;
                                    $car_access_record['business_id'] = $passage_info['village_id'];
                                    $car_access_record['car_number'] = $data['car_number'];
                                    $car_access_record['accessType'] = 2;
                                    $car_access_record['coupon_id'] = 0;
                                    $car_access_record['park_time'] =$data['park_time']*60 ;
                                    $car_access_record['total'] =$data['price'];
                                    $car_access_record['accessTime'] = $now_time;
                                    $car_access_record['exception_type'] = 2;
                                    $car_access_record['accessMode'] = 7;
                                    $car_access_record['park_sys_type'] = $park_sys_type;
                                    $car_access_record['is_out'] = 1;
                                    $car_access_record['park_id'] = $passage_info['village_id'];
                                    $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                                    $car_access_record['pay_type'] ='cash';
                                    $car_access_record['update_time'] = $now_time;
                                    $car_access_record['garage_id']=$garage_id;
                                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                    if (isset($insert_id)) {
                                        $car_access_record['from_id'] = $insert_id;
                                    }
                                    fdump_api([$car_access_record],'open_gate_0519',1);
                                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                                    if($record_id>0){
                                        $access_record_where_near = [
                                            ['car_number', '=', $data['car_number']],
                                            ['park_id', '=', $passage_info['village_id']],
                                            ['accessType', '=',1],
                                            ['is_out', '=', 0],
                                            ['del_time', '<', 1],
                                            ['record_id', '<', $record_id],
                                        ];
                                        $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                                    }

                            }
                        } else {
                            $insert_id=0;
                            /*
                            $out_data = [];
                            $out_data['car_number'] = $data['car_number'];
                            $out_data['out_time'] = $now_time;
                            $out_data['park_id'] = $passage_info['village_id'];
                            $out_data['pay_type'] = 'cash';
                            $out_data['total'] = $data['price'];
                            $starttime = time() - 60;
                            $endtime = time() + 10;
                            $park_where = [
                                ['car_number', '=', $data['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            fdump_api([$park_where, $out_data], 'open_gate_0519', 1);
                            $park_info_car = $db_out_park->getOne($park_where);
                            //写入车辆入场表
                            if (empty($park_info_car)) {
                                $insert_id = $db_out_park->insertOne($out_data);
                            }
                            */
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $data['car_number'];
                            $car_access_record['accessType'] = 2;
                            $car_access_record['coupon_id'] = 0;
                            $car_access_record['park_time'] = $data['park_time'] * 60;
                            $car_access_record['total'] = $data['price'];
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['exception_type'] = 2;
                            $car_access_record['accessMode'] = 7;
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                            $car_access_record['pay_type'] = 'cash';
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            fdump_api([$car_access_record], 'open_gate_0519', 1);
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            if($record_id>0){
                                $access_record_where_near = [
                                    ['car_number', '=', $data['car_number']],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['accessType', '=',1],
                                    ['is_out', '=', 0],
                                    ['del_time', '<', 1],
                                    ['record_id', '<', $record_id],
                                ];
                                $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                            }
                        }
                        $park_log_data = [];
                        $park_log_data['car_number'] = '';
                        $park_log_data['open_type'] = 3;
                        $park_log_data['channel_id'] = $passage_info['device_number'];
                        $park_log_data['park_type'] = $passage_info['passage_direction'] == 1 ? 1 : 2;
                        $park_log_data['add_time'] = time();
                        $res = $db_park_open_log->add($park_log_data);
                        return $res;
                    }
                    $start_time=time() - 360;
                    $end_time=time() + 50;
                    $where=[
                        ['park_type','=',2],
                        ['channel_id','=',$passage_info['device_number']],
                        ['add_time','>=',$start_time],
                        ['add_time','<=',$end_time],
                    ];
                }
                $log_info = $db_park_plateresult_log->get_one($where);
                $park_log_data = [];
                $park_log_data['open_type'] = 3;
                if (!empty($log_info)) {
                    $park_log_data['car_number'] = $log_info['car_number'];
                    $park_log_data['channel_id'] = $log_info['channel_id'];
                    $park_log_data['park_type'] = $log_info['park_type'];
                    $park_log_data['add_time'] = time();
                } else {
                    $park_log_data['car_number'] = '';
                    $park_log_data['channel_id'] = $passage_info['device_number'];
                    $park_log_data['park_type'] = $passage_info['passage_direction'] == 1 ? 1 : 2;
                    $park_log_data['add_time'] = time();
                }
                $res = $db_park_open_log->add($park_log_data);
            }
        }
        return $res;
    }


    public function getPositionLists($data,$page=0){
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $whereArr=array(['pp.garage_id','=',$data['garage_id']],['pp.position_pattern','=',1]);
        if(is_array($data['garage_id'])){
            $whereArr=array(['pp.garage_id','in',$data['garage_id']],['pp.position_pattern','=',1]);
        }
        if(isset($data['position_ids']) && !empty($data['position_ids'])){
            $position_ids=$data['position_ids'];
            if(is_array($data['position_ids'])){
                $position_ids=implode(',',$data['position_ids']);
            }
            $whereArr['_string']='pp.position_pattern=1 OR pp.position_id in ('.$position_ids.')';
        }
        $list=$db_house_village_parking_position->getLists($whereArr,'pp.position_id,pp.position_num,pg.garage_num', $page);
        
        return $list;
    }
    public function getPositionBindCarList($data,$page=0){
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $whereArr=array(['village_id','=',$data['village_id']]);
        if(isset($data['car_ids']) && !empty($data['car_ids'])){
            $car_ids=$data['car_ids'];
            if(is_array($data['car_ids'])){
                $car_ids=implode(',',$data['car_ids']);
            }
            $whereArr=array(['village_id','=',$data['village_id']]);
        }
        $list=$db_house_village_parking_car->getHouseVillageParkingCarLists($whereArr,'car_id,CONCAT(province,car_number) as car_number', $page);
        return $list;
    }

    /**
     * 查询车辆缴费列表
     * @author:zhubaodi
     * @date_time: 2022/5/17 10:55
     */
    public function getOrderList($data){
        $db_house_new_pay_order=new HouseNewPayOrder();
        $where=[
            'uid'=>$data['uid'],
            'village_id'=>$data['village_id'],
            'order_type'=>'park_new',
            'is_paid'=>1,
        ];
        $list=$db_house_new_pay_order->getPayLists($where,'order_id,uid,village_id,order_type,pay_money,pay_time,car_type,car_number',$data['page'],$data['limit']);
        $count=$db_house_new_pay_order->getPayCount($where);
        if (!empty($list)){
            $list=$list->toArray();
        }else{
            $list=[];
        }
        if (!empty($list)){
            foreach ($list as &$v){
                $v['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
                if ($v['car_type']=='stored_type'){
                    $v['pay_car_type']='停车卡储值';
                }elseif ($v['car_type']=='temporary_type'){
                    $v['pay_car_type']='临时车缴费';
                }elseif ($v['car_type']=='month_type'){
                    $v['pay_car_type']='月租车充值';
                }
            }
        }
        $res['list']=$list;
        $res['count']=$count;
        return $res;
    }

    /**
     * 查询车辆缴费列表
     * @author:zhubaodi
     * @date_time: 2022/5/17 10:55
     */
    public function getOrderDetail($data){
        $db_house_new_pay_order=new HouseNewPayOrder();
        $db_house_village=new HouseVillage();
        $db_in_park=new InPark();
        $where=[
            'uid'=>$data['uid'],
            'village_id'=>$data['village_id'],
            'order_id'=>$data['order_id'],
            'order_type'=>'park_new',
            'is_paid'=>1,
        ];
        $info=$db_house_new_pay_order->get_one($where,'order_id,summary_id,uid,village_id,order_type,total_money,pay_money,pay_time,car_type,car_number');
        $village_name=$db_house_village->getOne($data['village_id'],'village_name');
       if (empty($info)){
           throw new \think\Exception("订单数据不存在");
       }
        if ($info['car_type']=='stored_type'){
            $info['pay_car_type']='停车卡储值';
        }elseif ($info['car_type']=='temporary_type'){
            $info['pay_car_type']='临时车缴费';
        }elseif ($info['car_type']=='month_type'){
            $info['pay_car_type']='月租车充值';
        }
        $info['pay_time']=date('Y-m-d H:i:s',$info['pay_time']);
        $list = [];
        $list[]=array(
            'title'=>'订单编号',
            'val'=>$info['order_id']
        );
        $list[]=array(
            'title'=>'小区名称',
            'val'=>$village_name['village_name']
        );
        $list[]=array(
            'title'=>'车牌号',
            'val'=>$info['car_number']
        );
        $list[]=array(
            'title'=>'缴费类型',
            'val'=>$info['pay_car_type']
        );

        $list[]=array(
            'title'=>'支付时间',
            'val'=>$info['pay_time']
        );
        $summary_id = isset($info['summary_id']) ? intval($info['summary_id']) : 0;
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        !$village_id && $village_id = isset($info['village_id']) ? intval($info['village_id']) : 0;
        $isHas = false;
        if ($summary_id > 0 && $info['car_type'] == 'temporary_type') {
            $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
            $whereCarAccess = [];
            $whereCarAccess[] = ['business_type', '=', 0];
            $whereCarAccess[] = ['business_id', '=', $village_id];
            $whereCarAccess[] = ['car_number', '=', $info['car_number']];
            $whereCarAccess[] = ['pay_order_id', '=', $summary_id];
            $field = "record_id, business_type, business_id, car_number, pay_order_id, accessType, accessTime, coupon_id, order_id, park_time";
            $carAccessRecord = $db_house_village_car_access_record->getOne($whereCarAccess, $field);
            if ($carAccessRecord && !is_array($carAccessRecord)) {
                $carAccessRecord = $carAccessRecord->toArray();
            }
            $record_id = isset($carAccessRecord['record_id']) ? intval($carAccessRecord['record_id']) : 0;
            $accessType = isset($carAccessRecord['accessType']) ? intval($carAccessRecord['accessType']) : 0;
            $accessTime = isset($carAccessRecord['accessTime']) ? intval($carAccessRecord['accessTime']) : 0;
            $coupon_id = isset($carAccessRecord['coupon_id']) ? intval($carAccessRecord['coupon_id']) : 0;
            $park_time = isset($carAccessRecord['park_time']) ? intval($carAccessRecord['park_time']) : 0;
            $record_order_id = isset($carAccessRecord['order_id']) ? trim($carAccessRecord['order_id']) : '';
            $use_coupon_id = 0;
            $out_park_time = 0;
            $in_time_text = '';
            $out_time_text = '';
            $in_time = 0;
            $out_time = 0;
            if ($accessType == 2) {
                $use_coupon_id = $coupon_id;
                $out_time = $accessTime;
                $out_time_text = $accessTime > 0 ? date('Y-m-d H:i:s', $accessTime) : '';
                $out_park_time = $park_time;
            } elseif ($accessType == 1) {
                $in_time = $accessTime;
                $in_time_text = $accessTime > 0 ? date('Y-m-d H:i:s', $accessTime) : '';
            }
            if ($record_order_id) {
                $whereCarAccessOther = [];
                $whereCarAccessOther[] = ['business_id', '=', $village_id];
                $whereCarAccessOther[] = ['car_number', '=', $info['car_number']];
                $whereCarAccessOther[] = ['order_id', '=', $record_order_id];
                $whereCarAccessOther[] = ['record_id', '<>', $record_id];
                $fieldOther = "record_id, business_type, business_id, car_number, pay_order_id, accessType, accessTime, coupon_id, order_id, park_time";
                $carAccessRecordOther = $db_house_village_car_access_record->getOne($whereCarAccessOther, $fieldOther);
                if ($carAccessRecordOther && !is_array($carAccessRecordOther)) {
                    $carAccessRecordOther = $carAccessRecordOther->toArray();
                }
                $accessTypeOther = isset($carAccessRecordOther['accessType']) ? intval($carAccessRecordOther['accessType']) : 0;
                $accessTimeOther = isset($carAccessRecordOther['accessTime']) ? intval($carAccessRecordOther['accessTime']) : 0;
                $coupon_id_other = isset($carAccessRecordOther['coupon_id']) ? intval($carAccessRecordOther['coupon_id']) : 0;
                $park_time_other = isset($carAccessRecordOther['park_time']) ? intval($carAccessRecordOther['park_time']) : 0;

                if ($accessTypeOther == 2) {
                    $use_coupon_id = $coupon_id_other;
                    $out_time = $accessTimeOther;
                    $out_time_text = $accessTimeOther > 0 ? date('Y-m-d H:i:s', $accessTimeOther) : '';
                    $out_park_time = $park_time_other;
                } elseif ($accessTypeOther == 1) {
                    $in_time = $accessTimeOther;
                    $in_time_text = $accessTimeOther > 0 ? date('Y-m-d H:i:s', $accessTimeOther) : '';
                }
            }
            if ($out_park_time <= 0 && $out_time && $in_time && $out_time > $in_time) {
                $out_park_time = $out_time - $in_time;
            }
            if ($use_coupon_id > 0) {
                $isHas = true;
                $db_house_village_park_coupon = new HouseVillageParkCoupon();
                $whereCoupon = [];
                $whereCoupon[] = ['id', '=', $use_coupon_id];
                $couponInfo = $db_house_village_park_coupon->getFind($whereCoupon, 'id, money');
                $couponMoney = isset($couponInfo['money']) && floatval($couponInfo['money']) > 0 ? $couponInfo['money'] : 0;
                $list[] = array(
                    'title' => '停车优惠券',
                    'val' => '￥'.$couponMoney
                );
            }
            if ($in_time_text) {
                $isHas = true;
                $list[] = array(
                    'title' => '入场时间',
                    'val' => $in_time_text
                );
            }
            if ($out_time_text) {
                $isHas = true;
                $list[] = array(
                    'title' => '出场时间',
                    'val' => $out_time_text
                );
            }
            if ($out_park_time > 1) {
                $h = intval($out_park_time / 3600);
                $i = intval(($out_park_time - 3600 * $h) / 60);
                $s = $out_park_time - 3600 * $h - 60 * $i;
                $parkTimeText = $h . '小时' . $i . '分钟' . $s . '秒';
            } else {
                $parkTimeText = '--';
            }
            if ($isHas) {
                $list[] = array(
                    'title' => '停车时长',
                    'val' => $parkTimeText
                );
            }
        }
        if (!$isHas && $info['car_type']=='temporary_type'){
            $in_park_info=$db_in_park->getOne1(['pay_order_id'=>$info['summary_id'],'park_id'=>$village_id,'car_number'=>$info['car_number']]);
            $parkTime=0;
            $in_time='--';
            if (!empty($in_park_info)){
                $in_time=$in_park_info['in_time'];
                $park_time=$in_park_info['out_time']-$in_park_info['in_time'];
                if ($park_time>1){
                    $h=intval($park_time/3600);
                    $i=intval(($park_time-3600*$h)-60);
                    $s=$park_time-3600*$h-60*$i;
                    $parkTime=$h.'小时'.$i.'分钟'.$s.'秒';
                }
            }
            $list[]=array(
                'title'=>'入场时间',
                'val'=>$in_time
            );
            $list[]=array(
                'title'=>'停车时长',
                'val'=>$parkTime
            );
            $list[]=array(
                'title'=>'应付金额',
                'val'=>'￥'.$info['total_money']
            );
            $list[]=array(
                'title'=>'停车优惠券',
                'val'=>'￥0.00'
            );
        }else{
            $list[]=array(
                'title'=>'应付金额',
                'val'=>'￥'.$info['total_money']
            );
        }
        $list[]=array(
            'title'=>'实付金额',
            'val'=>'￥'.$info['pay_money']
        );

        return ['list'=>$list];
    }

    //一键同步白名单集中处理
    public function synAddParkWhite($village_id)
    {
        $data = [
            'status' => true,
            'msg' => '同步成功'
        ];
        $result = $this->allAddParkWhite($village_id);
        if (!$result) {
            $data['status'] = false;
            $data['msg'] = '同步失败';
        }

        $result = (new A11Service())->triggerWhitelist($village_id);
        $data = [
            'status' => true,
            'status_err'=>$result['error'],
            'msg_err'=>$result['msg'],
            'msg' => '同步成功'
        ];
        return $data;
    }
    /**
     * D3一键同步白名单
     * @author:zhubaodi
     * @date_time: 2022/6/25 14:21
     */
    public function allAddParkWhite($village_id){
        $db_house_village_parking_car=new HouseVillageParkingCar();
        if (!empty($village_id)){
            $where=[
                ['village_id','=',$village_id],
                ['end_time','>',time()],
            ];
            $car_list=$db_house_village_parking_car->getHouseVillageParkingCarLists($where,'car_id,village_id,province,car_number,end_time,start_time,garage_id',0);
            if (!empty($car_list) && !$car_list->isEmpty()){
                $car_list=$car_list->toArray();
            }
            $parkGarageConfig=array();
            if (!empty($car_list)){
                $houseVillageParkingService= new HouseVillageParkingService();
                $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
                foreach ($car_list as $value){
                    if($value['garage_id']>0){
                        $is_d3=false;
                        if(isset($parkGarageConfig[$value['garage_id']]) && !empty($parkGarageConfig[$value['garage_id']])){
                            if($parkGarageConfig[$value['garage_id']]=='D3'||$parkGarageConfig[$value['garage_id']]=='d3'){
                                $is_d3=true;
                            }
                        }else{
                            $park_config_info = $houseVillageParkGarageConfigService->getVillageParkConfig($value['garage_id'],$village_id);
                            if($park_config_info && !empty($park_config_info['park_sys_type'])){
                                $parkGarageConfig[$value['garage_id']]=$park_config_info['park_sys_type'];
                                if($park_config_info['park_sys_type']=='D3'||$park_config_info['park_sys_type']=='d3'){
                                    $is_d3=true;
                                }
                            }
                        }
                        if($is_d3){
                            $arr=[];
                            $arr['village_id']=$village_id;
                            $arr['car_number']=$value['province'].$value['car_number'];
                            $houseVillageParkingService->addWhitelist($arr); 
                        }

                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * D7一键同步车辆信息
     * @author:zhubaodi
     * @date_time: 2022/6/25 14:21
     */
    public function allAddCarD7($village_id)
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        if (!empty($village_id)) {
            $where = [
                ['village_id', '=', $village_id],
            ];
            $car_list = $db_house_village_parking_car->getHouseVillageParkingCarLists($where, 'car_id,village_id,province,car_number,end_time,start_time,garage_id', 0);
            if (!empty($car_list)) {
                $car_list = $car_list->toArray();
            }
            $parkGarageConfig = array();
            if (!empty($car_list)) {
                foreach ($car_list as $value) {
                    $d7_park_id = '';
                    $is_d7 = false;
                    if ($value['garage_id'] > 0) {
                        if (isset($parkGarageConfig[$value['garage_id']]) && !empty($parkGarageConfig[$value['garage_id']])) {
                            if ($parkGarageConfig[$value['garage_id']]['park_sys_type'] == 'D7' || $parkGarageConfig[$value['garage_id']]['park_sys_type'] == 'd7') {
                                $is_d7 = true;
                                $d7_park_id = $parkGarageConfig[$value['garage_id']]['d7_park_id'];
                            }
                        } else {
                            $park_config_info = $houseVillageParkGarageConfigService->getVillageParkConfig($value['garage_id'], $village_id);
                            if ($park_config_info && !empty($park_config_info['park_sys_type'])) {
                                $parkGarageConfig[$value['garage_id']] = $park_config_info['park_sys_type'];
                                if ($park_config_info['park_sys_type'] == 'D7' || $park_config_info['park_sys_type'] == 'd7') {
                                    $is_d7 = true;
                                    $d7_park_id = $park_config_info['d7_park_id'];
                                }
                            }
                        }
                    }
                    if ($d7_park_id) {
                        $d7_data = [];
                        $d7_data['park_id'] = $d7_park_id;
                        if (!empty($value['vehicleId'])) {
                            $d7_data['operate'] = 2;
                            $d7_data['vehicleId'] = $value['vehicleId'];
                        } else {
                            $d7_data['operate'] = 1;
                        }
                        $d7_data['vehicleNo'] = $value['province'] . $value['car_number'];
                        $d7_data['carTypeCode'] = $this->parking_D7_car_type_value[$value['parking_car_type']]['value'];
                        if (!empty($value['car_user_name'])) {
                            $d7_data['userName'] = $value['car_user_name'];
                        }
                        if (!empty($value['car_user_phone'])) {
                            $d7_data['tels'] = $value['car_user_phone'];
                        }
                        if (!empty($value['end_time'])) {
                            $d7_data['beginTime'] = time();
                            $d7_data['endTime'] = $value['end_time'];
                            if (empty($value['parking_car_type']) || $value['parking_car_type'] >= 9) {
                                $value['parking_car_type'] = 1;
                            }
                        }
                        if (isset($value['parking_car_type']) && !empty($value['parking_car_type'])) {
                            $d7_data['carTypeCode'] = $this->parking_D7_car_type_value[$value['parking_car_type']]['value'];
                        } else {
                            $d7_data['carTypeCode'] = 36;
                            $value['parking_car_type'] = 9;
                        }
                        $d7_result = (new QinLinCloudService())->whiteList($d7_data);
                        if (!empty($d7_result) && $d7_result['state'] == '200' && !empty($d7_result['data'])) {
                            $d7_result['data'] = json_decode($d7_result['data'], true);
                            if (!empty($d7_result['data']['vehicleList'][0]['id']) && empty($value['vehicleId'])) {
                                $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $value['car_id'], 'village_id' => $value['village_id']], ['parking_car_type' => $value['parking_car_type'], 'vehicleId' => $d7_result['data']['vehicleList'][0]['id']]);
                            }
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    //删除记录
    public function delRecordCar($whereArr=array()){
        if(empty($whereArr)){
            return ['err_code'=>1,'msg'=>'where data empty'];
        }
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $accessRecordObj=$db_house_village_car_access_record->getOne($whereArr);
        if(!empty($accessRecordObj) && !$accessRecordObj->isEmpty()){
            $tmpAccessRecord=$accessRecordObj->toArray();
            if(empty($tmpAccessRecord) || ($tmpAccessRecord['del_time']>0)){
                return ['err_code'=>0,'msg'=>'is del time of '.$tmpAccessRecord['del_time']];
            }else{
                $del_time=time();
                $db_house_village_car_access_record->saveOne($whereArr,['del_time'=>$del_time]);
                if(!empty($tmpAccessRecord['order_id'])){
                    $tmpWhereArr=array();
                    $tmpWhereArr['order_id']=$tmpAccessRecord['order_id'];
                    $tmpWhereArr['park_id']=$tmpAccessRecord['park_id'];
                    $tmpWhereArr['car_number']=$tmpAccessRecord['car_number'];
                    if($tmpAccessRecord['accessType']==1){
                        $tmpWhereArr['accessType']=2;
                        $db_house_village_car_access_record->saveOne($tmpWhereArr,['del_time'=>$del_time]);
                    }elseif($tmpAccessRecord['accessType']==2){
                        $outWhereArr=$tmpWhereArr;
                        $outWhereArr['accessType']=2;
                        $outWhereArr['del_time']=0;
                        //查一下是否出场的 都删除完了
                        $outAccessRecord=$db_house_village_car_access_record->getOne($outWhereArr);
                        if(empty($outAccessRecord) || $outAccessRecord->isEmpty()){
                            //出场的全部删完了，删掉进场记录
                            $tmpWhereArr['accessType']=1;
                            $db_house_village_car_access_record->saveOne($tmpWhereArr,['del_time'=>$del_time]);
                        }
                    }
                }
                if($tmpAccessRecord['from_id']>0 && $tmpAccessRecord['accessType']==1){
                    $db_in_park=new InPark();
                    $db_in_park->saveOne(['id'=>$tmpAccessRecord['from_id']],['del_time'=>$del_time]);
                }elseif($tmpAccessRecord['from_id']>0 && $tmpAccessRecord['accessType']==2){
                    $db_out_park=new OutPark();
                    $db_out_park->saveOne(['id'=>$tmpAccessRecord['from_id']],['del_time'=>$del_time]);
                }
            }
            return ['err_code'=>0,'msg'=>'del time of '.$del_time];
        }
        return ['err_code'=>1,'msg'=>'no data option'];
    }
    

    /**
     *
     * @author:zhubaodi
     * @date_time: 2022/8/15 17:43
     */
    public function getD7ChannelList($village_id,$garage_id=0){
        $service_qinLinCloud=new QinLinCloudService();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $info = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $village_id);
        if (empty($info)||empty($info['d7_park_id'])||$info['park_sys_type']!='D7'){
            $list=[];
        }else{
            $channel_list=$service_qinLinCloud->getChannels($info['d7_park_id']);
            if ($channel_list['state']!=200||empty($channel_list['data'])){
                throw new \think\Exception("请先到第三方平台添加车道信息");
            }
            $list=json_decode($channel_list['data'],true);
        }


        $res=[];
        $res['list']=$list;
        $res['garage_config']=$info;
        $res['park_sys_type']= $info && isset($info['park_sys_type']) ? $info['park_sys_type']:'';
        return $res;
    }

    /**
     * 解除子母车位的绑定关系
     * @author:zhubaodi
     * @date_time: 2022/8/1 8:33
     */
    public function unBindChildrenPosition($data){
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $where=[];
        $where['village_id']=$data['village_id'];
        $where['position_id']=$data['position_id'];
        $position_info=$db_house_village_parking_position->getFind($where);
        if (empty($position_info)||$position_info['children_type']!=2||empty($position_info['parent_position_id'])){
            throw new \think\Exception("当前车位无法解除子母车位绑定关系");
        }
        $garage_id=$position_info['garage_id'] ? $position_info['garage_id']:0;
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $data['village_id']);
        if ($house_village_park_config['children_position_type']!=1){
            throw new \think\Exception("当前车库未开启字母车位功能配置");
        }
        $id=$db_house_village_parking_position->saveOne($where,['parent_position_id'=>0]);
        return $id;
    }


    /**
     * 添加子母车位的绑定关系
     * @author:zhubaodi
     * @date_time: 2022/8/1 8:33
     */
    public function bindChildrenPosition($data){
        $db_house_village_parking_position=new HouseVillageParkingPosition();

        $where=[];
        $where['village_id']=$data['village_id'];
        $where['position_id']=$data['position_id'];
        $position_info=$db_house_village_parking_position->getFind($where);
        if (empty($position_info)||$position_info['children_type']!=2){
            throw new \think\Exception("当前车位无法绑定母车位");
        }
        $garage_id=$position_info['garage_id'] ? $position_info['garage_id']:0;
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $data['village_id']);
        if ($house_village_park_config['children_position_type']!=1){
            throw new \think\Exception("当前车库未开启字母车位功能配置");
        }
        $where1=[];
        $where1['village_id']=$data['village_id'];
        $where1['position_id']=$data['parent_position_id'];
        $where1['garage_id']=$position_info['garage_id'];
        $parent_position_info=$db_house_village_parking_position->getFind($where1);
        if (empty($parent_position_info)||$parent_position_info['children_type']!=1){
            throw new \think\Exception("当前母车位无法绑定子车位");
        }

        if (!empty($position_info['end_time'])&&$position_info['end_time']>100){
            if (empty($parent_position_info['end_time'])||$parent_position_info['end_time']<$position_info['end_time']){
                throw new \think\Exception("子车位的到期时间大于母车位的到期时间,无法绑定");
            }
        }

        $id=$db_house_village_parking_position->saveOne($where,['parent_position_id'=>$data['parent_position_id']]);
        return $id;
    }

    public function getParentPositionList($data){
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $where=[];
        $where['village_id']=$data['village_id'];
        $where['position_id']=$data['position_id'];
        $position_info=$db_house_village_parking_position->getFind($where);
        if (empty($position_info)||$position_info['children_type']!=2){
            throw new \think\Exception("当前车位无法绑定母车位");
        }
        $garage_id=$position_info['garage_id'] ? $position_info['garage_id']:0;
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $data['village_id']);
        if ($house_village_park_config['children_position_type']!=1){
            throw new \think\Exception("当前车库未开启字母车位功能配置");
        }
        $where1=[];
        $where1[]=['village_id','=',$data['village_id']];
        $where1[]=['garage_id','=',$position_info['garage_id']];
        $where1[]=['children_type','=',1];
        $where1[]=['position_pattern','=',1];
        if (!empty($data['position_num'])){
            $where1[]=['position_num','like','%'.$data['position_num'].'%'];
        }
        $parent_position_List=$db_house_village_parking_position->getList($where1,'position_id,position_num');
        return $parent_position_List;
    }
    
    /**
     * 查询数据统计页面显示页签
     * @author:zhubaodi
     * @date_time: 2022/8/30 14:16
     */
    public function getRecordShow($village_id)
    {
        $data['onlineCars'] = true;
        $data['monthCars'] = true;
        $data['temporaryCars'] = true;
        $data['manualOpen'] = true;
        $data['notPresent'] = true;
        $data['storageBattery'] = true;
        $data['storedValue'] = true;
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('park_sys_type', '=', 'D7');
        $d7Config = $houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr);
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('park_sys_type', 'in', array('D3', 'A11','HXT85'));
        $d3Config = $houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr);
        if (!empty($d7Config) ) {
            $data['manualOpen'] = false;
            $data['storageBattery'] = false;
        } 
        if (!empty($d3Config)) {
            $data['storageBattery'] = false;
        }
        return $data;
    }

    //todo 触发小区月租车到期提醒
    public function triggerParkMonthExpiresNotice(){
        //$HouseVillageParkConfig=new HouseVillageParkConfig();
        $HouseVillageParkingCar=new HouseVillageParkingCar();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $time=time();
        $data=[];
        //$where[] = ['park_month_day','>',0];
        for ($x=1; $x<=50; $x++) {
            $field='id,village_id,park_month_day';
            $whereArr=array();
            $whereArr[]=array('village_id','>',0);
            $whereArr[]=array('park_month_day','>',0);
            $list =$houseVillageParkGarageConfigService->getListData($whereArr,$field,'id DESC',$x,50,'village_id',true);
            //$list = $HouseVillageParkConfig->get_list($where,$field,$x,50);
            if (empty($list)){
                break;
            }
            //$list=$list->toArray();
            fdump_api(['列表数据==line:'.__LINE__,$list],'park_month_day/park_month_day_notice',1);
            foreach ($list as $v){
                $whereCar=[];
                $whereCar[] = ['village_id','=',$v['village_id']];
                $whereCar[] = ['end_time','>',$time];
                $whereCar[] = ['end_time','<=',(strtotime("+".(intval($v['park_month_day']))." day"))];
                $carNum=$HouseVillageParkingCar->get_village_car_num($whereCar);
                if(empty($carNum)){
                    continue;
                }
                if(!isset($data[$v['village_id']])){
                    $data[$v['village_id']]=[
                        'param'=>serialize([
                            'village_id'=>  $v['village_id'],
                            'type'      =>  'park_month_notice',
                        ]),
                        'plan_time'     =>  -110,
                        'space_time'    =>  0,
                        'add_time'      =>  $time,
                        'file'          =>  'sub_d5_park',
                        'time_type'     =>  1,
                        'unique_id'     =>  $v['village_id'].'_parkMonth_'.date('mdHi'),
                        'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
                    ];
                }

            }
        }
        $data=!empty($data) ? array_values($data):array();
        fdump_api(['data数据==line:'.__LINE__,$data],'park_month_day/park_month_day_notice',1);
        if($data){
            (new ProcessSubPlan())->addAll($data);
        }
        return true;
    }
    public function getHouseVillageInfoExtend($where,$field='*')
    {
        $houseVillageInfoExtend = new HouseVillageInfo();
        $res = $houseVillageInfoExtend->getOne($where,$field);
        if($res && !$res->isEmpty()){
            $res=$res->toArray();
        }else{
            $res=array();
        }
        return $res;
    }
    //todo 月租车到期给业主发送短信/模板
    public function sendParkMonthExpiresNotice($village_id){
        $houseVillageBindCar=new HouseVillageBindCar();
        $houseVillageParkingCar=new HouseVillageParkingCar();
        $HouseVillageMonthParkNoticeRecord=new HouseVillageMonthParkNoticeRecord();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $D5Service = new D5Service();
        $Tempmsg=new Tempmsg();
        $db_user = new User();
        $time=time();
        $village_info_extend = $this->getHouseVillageInfoExtend(['village_id'=>$village_id],'urge_notice_type');
        $urge_notice_type=0;
        if($village_info_extend && isset($village_info_extend['urge_notice_type'])){
            //1短信通知2微信模板通知3短信和微信模板通知
            $urge_notice_type=$village_info_extend['urge_notice_type'];
        }
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('park_month_day','>',0);
        $garageConfig =$houseVillageParkGarageConfigService->getListData($whereArr,'id,village_id,garage_id,park_month_day','park_month_day asc');
        fdump_api([__LINE__,'village_id'=>$village_id,'urge_notice_type'=>$urge_notice_type,'garageConfig'=>$garageConfig],'park_month_day/sendParkMonthExpiresNotice',1);
        
        if(!empty($garageConfig)){
            foreach ($garageConfig as $gvv){
                $day=intval($gvv['park_month_day']);
                $park_month_time=strtotime("+".$day." day");
                /*
                $whereCar=[];
                $whereCar[] = ['bc.village_id','=',$village_id];
                $whereCar[] = ['c.village_id','=',$village_id];
                $whereCar[] = ['b.village_id','=',$village_id];
                $whereCar[] = ['c.end_time','>',$time];
                $whereCar[] = ['c.end_time','<=',$park_month_time];
                if(isset($gvv['garage_id']) && $gvv['garage_id']>0){
                    $whereCar[] = ['c.garage_id','=',$gvv['garage_id']];
                }
                $whereCar[] = ['b.status','<>',4];
                $field='bc.car_id,bc.village_id,b.pigcms_id,b.uid,b.name,b.phone,c.end_time,c.province,c.car_number,c.parking_car_type';
                $list=$houseVillageBindCar->getUserBindCar($whereCar,$field);
                */
                $whereCar=[];
                $whereCar[] = ['village_id','=',$village_id];
                $whereCar[] = ['end_time','>',$time];
                $whereCar[] = ['end_time','<=',$park_month_time];
                if(isset($gvv['garage_id']) && $gvv['garage_id']>0){
                    $whereCar[] = ['garage_id','=',$gvv['garage_id']];
                }
                $field='car_id,village_id,car_user_name,car_user_phone,end_time,province,car_number,parking_car_type,uid,garage_id';
                $list=$houseVillageParkingCar->getNewList($whereCar,$field,0,1000,'end_time ASC');
                fdump_api([__LINE__,'village_id'=>$village_id,'whereCar'=>$whereCar],'park_month_day/sendParkMonthExpiresNotice',1);
                if (!$list || $list->isEmpty()){
                    continue;
                }
                $list=$list->toArray();
                fdump_api([__LINE__,'village_id'=>$village_id,'list'=>$list],'park_month_day/sendParkMonthExpiresNotice',1);
                $tempKey='OPENTM411328351';
                $temp_info=$Tempmsg->getOne([
                    ['tempkey','=',$tempKey]
                ],'property_id','property_id asc');
                if ($temp_info && !$temp_info->isEmpty()) {
                    $property_id=$temp_info['property_id'];
                }
                else{
                    $property_id=0;
                }
                foreach ($list as $v){
                    $record=$HouseVillageMonthParkNoticeRecord->getFind([
                        ['pigcms_id','=',$v['garage_id']],
                        ['car_id','=',$v['car_id']]
                    ]);
                    if($record && !$record->isEmpty()){
                        //最新记录的到期时间和当前用户到期时间相同 且 配置项到期时间未改动
                        if($v['end_time'] == $record['car_end_time']){
                            continue;
                        }
                    }
                    $car_number=$D5Service->checkCarNumber($v['province'],$v['car_number']);
                    $record_arr=array(
                        'pigcms_id'=>$v['garage_id'],
                        'car_id'=>$v['car_id'],
                        'car_end_time'=>$v['end_time'],
                        'config_end_time'=>$park_month_time,
                        'park_month_day'=>$day,
                        'add_time'=>date('Y-m-d H:i:s',$time)
                    );
                    $HouseVillageMonthParkNoticeRecord->addOne($record_arr);
                    $car_user_phone=$v['car_user_phone'];
                    $name=$v['car_user_name'];
                    $uid = $v['uid'];

                    $whereCar = [];
                    $whereCar[] = ['bc.village_id', '=', $village_id];
                    $whereCar[] = ['bc.car_id', '=', $v['car_id']];
                    $field = 'bc.uid as bc_uid,b.pigcms_id,b.uid,b.name,b.phone';
                    $oneCarBindUser = $houseVillageBindCar->getOneCarBindUserBind($whereCar, $field);
                    if ($oneCarBindUser && !$oneCarBindUser->isEmpty()) {
                        $oneCarBindUser = $oneCarBindUser->toArray();
                        $uid = $oneCarBindUser['uid'] ? $oneCarBindUser['uid'] : $oneCarBindUser['bc_uid'];
                        if (empty($car_user_phone) && !empty($oneCarBindUser['phone'])) {
                            $car_user_phone = $oneCarBindUser['phone'];
                        }
                        if (empty($name) && !empty($oneCarBindUser['name'])) {
                            $name = $oneCarBindUser['name'];
                        }
                    }
                    if($urge_notice_type!=1) {
                        $user_info = $db_user->getOne(['uid' => $uid], 'openid');
                        fdump_api([__LINE__, 'car_user_phone' => $car_user_phone, 'user_info' => $user_info, 'v' => $v], 'park_month_day/sendParkMonthExpiresNotice', 1);
                        if ($user_info && !$user_info->isEmpty() && !empty($user_info['openid'])) {
                            $param = [
                                'tempKey' => $tempKey,
                                'car_id' => $v['car_id'],
                                'openid' => $user_info['openid'],
                                'car_number' => $car_number,
                                'end_time' => $v['end_time'],
                                'parking_car_type' => $v['parking_car_type'],
                            ];
                            //发送模板消息
                            $this->sendParkMonthWx($param, 0);
                        }
                    }
                    if($car_user_phone && $urge_notice_type!=2){
                        fdump_api([__LINE__, 'car_user_phone' => $car_user_phone, 'v' => $v], 'park_month_day/sendParkMonthExpiresNotice', 1);
                        $param = [
                            'uid' => $uid,
                            'village_id' => $village_id,
                            'name' => $name,
                            'phone' => $car_user_phone,
                            'car_number' => $car_number,
                            'end_time' => date('Y年m月d日', $v['end_time']),
                            'parking_car_type' => $v['parking_car_type'],
                        ];
                        //发送短信
                        $this->sendParkMonthSms($param);
                    }
                }
            }
        }
        return true;
    }

    //todo 发送模板消息
    public function sendParkMonthWx($param,$property_id=0){
        $time=time();
        $str='';
        if($param['end_time'] > $time){
            $str='（'.intval(($param['end_time'] - $time) / 86400).'天后到期）';
        }
        $A11_Stored = (new  A11Service())->A11_Stored;
        $first = '您的月租车有效时间即将到期，请尽快处理！';
        if (isset($param['parking_car_type']) && $param['parking_car_type'] && !in_array($param['parking_car_type'], $A11_Stored)) {
            // 储值车 月租车到期
            $first = '您的月租车有效时间即将到期，将转为储值车计费！';
        }

        $carInfo=(new HouseVillageParkingCar())->where(['car_id'=>$param['car_id']])->field('garage_id,stored_balance')->find();
        $garage_id = (isset($carInfo['garage_id']) && $carInfo['garage_id']) ? $carInfo['garage_id'] : 0;
        $thing3 = (new HouseVillageParkingGarage())->where(['garage_id'=>$garage_id])->value('garage_num');
        $thing3 = $thing3 ?: '无';
        $amount20 = (isset($carInfo['stored_balance']) && $carInfo['stored_balance']) ? $carInfo['stored_balance'] : '0';
        $data=[
            'href' => get_base_url('pages/village/smartPark/parkpayment?car_id='.$param['car_id'].'&car_type=month_type'),
            'wecha_id' => $param['openid'],
            'first' => $first,
            'keyword1' => $param['car_number'],
            'keyword2' => '月租车缴费',
            'keyword3' => date('Y年m月d日',$param['end_time']).$str,
            'keyword4' => date('Y年m月d日 H:i'),
            'remark' => '请点击查看详细信息！',
            'new_info' => [//新版本发送需要的信息
                'tempKey'=>'43837',//新模板号
                'car_number2'=>$param['car_number'],//车牌号
                'thing3'=>$thing3.$str,//停车场
                'time9'=>date('Y年m月d日',$param['end_time']),//限停时间
                'thing4'=>$first,//停车时长
                'amount5'=>'0元',//缴费金额
                'amount20'=>$amount20.'元',//储值卡余额
            ],
        ];
        $result=(new TemplateNewsService())->sendTempMsg($param['tempKey'], $data,0,$property_id,($property_id ? 1 : 0));
        fdump_api(['发送模板消息==line:'.__LINE__,$data,$result],'park_month_day/send_wx',1);
        return true;
    }

    //todo 发送短信
    public function sendParkMonthSms($param){
        $data = array('type' => 'fee_notice');
        $data['uid'] = $param['uid'];
        $data['village_id'] = $param['village_id'];
        $data['mobile'] = $param['phone'];
        $data['sendto'] = 'user';
        $data['mer_id'] = 0;
        $data['store_id'] = 0;
        $A11_Stored = (new  A11Service())->A11_Stored;
        if (isset($param['parking_car_type']) && $param['parking_car_type'] && !in_array($param['parking_car_type'], $A11_Stored)) {
            // 储值车 月租车到期
            $data['content'] = L_('尊敬的业主x1，您的车牌号码：x2于x3，将转为储值车计费。', array(
                'x1' => $param['name'],
                'x2' => $param['car_number'],
                'x3' => $param['end_time'].'到期',
            ));
        } else {
            $data['content'] = L_('尊敬的业主x1，您的车牌号码：x2于x3，请您尽快续费避免影响您的正常出行。', array(
                'x1' => $param['name'],
                'x2' => $param['car_number'],
                'x3' => $param['end_time'].'到期',
            ));
        }
        $result = (new SmsService())->sendSms($data);
        fdump_api(['发送短信==line:'.__LINE__,$data,$result],'park_month_day/send_sms',1);
        return true;
    }
    
    public function getGarageStatusList($data,$garage_id=0,$isoptional=false){
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $result['status']=false;
        $result['list']=[];
        if($isoptional){
            $result['list'][]=array('garage_id'=>0, 'garage_num'=>"请选择父级车库");
        }
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$data['village_id']);
        if($garage_id>0){
            $whereArr[]=array('garage_id','=',$garage_id);
        }
        $whereArr[]=array('park_sys_type','=','A11');
        $house_village_park_config =$houseVillageParkGarageConfigService->getListData($whereArr);
        if($house_village_park_config){
            $isHaveA11=false;
            foreach ($house_village_park_config as $vc){
                if($vc['park_sys_type'] == 'A11'){
                    $isHaveA11=true;
                }
            }
            if($isHaveA11){
                $where['village_id']=$data['village_id'];
                $where['status']=1;
                $list=$db_house_village_parking_garage->getLists($where,'garage_id,garage_num');
                $result['status']=true;
                if($list && !$list->isEmpty()){
                    $list=$list->toArray();
                    foreach ($list as $lvv){
                        $result['list'][]=$lvv;
                    }
                }
            }
        }
        return $result;
        
    }
    
    public function getA11CarType(){
        $list=$this->parking_a11_car_type_arr;
        $data_list=[];
        foreach ($list as $k=>$v){
            if ($k==self::Free_A){
                continue;
            }else{
                $arr=[];
                $arr['car_type']=$v;
                $arr['car_type_id']=$k;
            }
            $data_list[]=$arr;
        }
        
        return $data_list;
    }
    
    public function getChargeCarType($data){
       $db_house_village_charge_cartype=new HouseVillageParkChargeCartype();
       $db_house_new_charge_rule=new HouseNewChargeRule();
       
       $where=[
           'village_id'=>$data['village_id'],
           'car_type'=>$data['type_id'],
           'status'=>1
       ];
       $id_arr=$db_house_village_charge_cartype->getColumn($where,'rule_id');
       $field='id,village_id,charge_name,charge_valid_time,fees_type';
       $where_rule=['village_id'=>$data['village_id'],'status'=>1];
        if (in_array($data['type_id'],$this->A11_Month)){
            $where_rule['fees_type']=4;
        }elseif(in_array($data['type_id'],$this->A11_Temp)||in_array($data['type_id'],$this->A11_Stored)){
            $where_rule['fees_type']=3;
        }
       $list=$db_house_new_charge_rule->getList($where_rule,$field,'id DESC',$data['page'],$data['limit']);
       if (!empty($list)){
           $list=$list->toArray();
       }
      
       if (!empty($list)){
           foreach ($list as &$v){
               if ($v['fees_type']==3){
                   $v['fees_type_txt']='临时车收费标准';
               }elseif($v['fees_type']==4){
                   $v['fees_type_txt']='月租车收费标准'; 
               }
               if (in_array($v['id'],$id_arr)){
                   $v['status_txt']=1;
               }else{
                   $v['status_txt']=0;
               }
               $v['charge_valid_time']=date('Y-m-d',$v['charge_valid_time']);
           }
       }
       $count=$db_house_new_charge_rule->getCount($where_rule);
       $data1=[];
       $data1['list']=$list;
       $data1['count']=$count;
       $data1['total']=$data['limit'];
        
       return $data1;
    }

    /**
     * 添加收费标准绑定停车卡类
     * @author:zhubaodi
     * @date_time: 2022/11/22 9:11
     */
    public function addChargeCarType($data,$garage_id=0){
       $db_house_new_charge_rule=new HouseNewChargeRule();
       $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
       $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
       $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
       if (empty($park_config)){
           throw new \think\Exception("请先设置停车相关配置");
       }
       $park_sys_type='A11';
       $where=[
           'village_id'=>$data['village_id'],
           'car_type'=>$data['type_id'],
           'rule_id'=>$data['rule_id'],
           'status'=>1,
           'park_sys_type'=>$park_sys_type
       ];
       $car_type_info=$db_house_village_park_charge_cartype->getFind($where);
       if (!empty($car_type_info)){
           throw new \think\Exception("当前卡类已绑定该收费标准");
       }
       
       $where_rule=[
           'village_id'=>$data['village_id'],
           'id'=>$data['rule_id'],
           'status'=>1,   
       ];
       $rule_info=$db_house_new_charge_rule->getOne($where_rule);
       if (empty($rule_info)){
           throw new \think\Exception("当前收费标准信息不存在");
       }
       $arr=[];
       $arr['rule_id']=$data['rule_id'];
       $arr['village_id']=$data['village_id'];
       $arr['car_type']=$data['type_id'];
       $arr['park_sys_type']=$park_sys_type;
       $arr['status']=1;
       $arr['addTime']=time();
       $arr['updateTime']=time();
       $res=$db_house_village_park_charge_cartype->addFind($arr);
       return $res;
    }

    /**
     * 解除收费标准绑定的停车卡类
     * @author:zhubaodi
     * @date_time: 2022/11/22 11:02
     */
    public function delChargeCarType($data,$garage_id=0){
        $db_house_new_charge_rule=new HouseNewChargeRule();
        $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        if (empty($park_config)){
            throw new \think\Exception("请先设置停车相关配置");
        }
        $park_sys_type='A11';
        $where=[
            'village_id'=>$data['village_id'],
            'car_type'=>$data['type_id'],
            'rule_id'=>$data['rule_id'],
            'status'=>1,
            'park_sys_type'=>$park_sys_type
        ];
        $car_type_info=$db_house_village_park_charge_cartype->getFind($where);
        if (empty($car_type_info)){
            throw new \think\Exception("绑定信息不存在");
        }

        $where_rule=[
            'village_id'=>$data['village_id'],
            'id'=>$data['rule_id'],
            'status'=>1,
        ];
        $rule_info=$db_house_new_charge_rule->getOne($where_rule);
        if (empty($rule_info)){
            throw new \think\Exception("当前收费标准信息不存在");
        }
        $res=$db_house_village_park_charge_cartype->save_one($where,['status'=>2,'updateTime'=>time()]);
        return $res;
    }

    /**
     * 修改车道状态
     * @author:zhubaodi
     * @date_time: 2022/12/1 9:29
     */
    public function editPassageStatus($data){
        $db_park_passage=new ParkPassage();
        $passage_info=$db_park_passage->getFind(['id'=>$data['id'],'village_id'=>$data['village_id']]);
        $res=0;
        if (!empty($passage_info)){
            $res=$db_park_passage->save_one(['id'=>$data['id'],'village_id'=>$data['village_id']],['status'=>$data['status']]);
            (new CarHandleGetInfoService())->clearPassInfo($data['village_id']);
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '车道管理-车道状态',
                'table'  => 'park_passage',
                'client' => '小区后台',
                'trigger_path' => '停车管理->车道管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => [],
        ];
        $this->laterLogInQueue($queuData);
        return $res;
    }
    
    public function editCouponStatus($data){
        $db_park_coupons=new ParkCoupons();
        $coupons_info=$db_park_coupons->getFind(['c_id'=>$data['id'],'village_id'=>$data['village_id']]);
        $res=0;
        if (!empty($coupons_info)){
            $res=$db_park_coupons->save_one(['c_id'=>$data['id'],'village_id'=>$data['village_id']],['status'=>$data['status']]);
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '优惠券列表-状态',
                'table'  => 'park_coupons',
                'client' => '小区后台',
                'trigger_path' => '停车管理->优惠券管理',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $data['village_id'],
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' => []
        ];
        $this->laterLogInQueue($queuData);
        
        return $res;
    }
    public function getCarType($car_type) {
        $car_type_txt = '';
        switch ($car_type) {
            case 'stored_type':
                $car_type_txt = '停车卡储值';
                break;
            case 'temporary_type':
                $car_type_txt = '临时车缴费';
                break;
            case 'month_type':
                $car_type_txt = '月租车充值';
                break;
        }
        return $car_type_txt;
    }
    /**
     * 查询A11停车车牌类型
     * @author:zhubaodi
     * @date_time: 2022/12/13 9:46
     */
    public function getCarTypeA11($data){
        $db_house_village_charge_cartype=new HouseVillageParkChargeCartype();
        $where=[
            ['village_id','=',$data['village_id']],
            ['car_type','=',$data['type_id']],
            ['status','=',1],
            ['car_number_type', 'exp', Db::raw('IS NOT NULL')],
        ];
        $id_arr=$db_house_village_charge_cartype->getColumn($where,'car_number_type');

        $where=[
            ['village_id','=',$data['village_id']],
            ['car_type','<>',$data['type_id']],
            ['status','=',1],
            ['car_number_type', 'exp', Db::raw('IS NOT NULL')],
        ];
        $id_arr1=$db_house_village_charge_cartype->getColumn($where,'car_number_type');
        $list=$this->A11_type;
        $dataList=[];
        if (!empty($list)){
            foreach ($list as $k=>$v){
                if (!empty($id_arr1)&&in_array($k,$id_arr1)){
                    unset($list[$k]);
                    continue;
                }
                $vv=[];
                if (!empty($id_arr)&&in_array($k,$id_arr)){
                    $vv['status_txt']=1;
                }else{
                    $vv['status_txt']=0;
                }
                $vv['car_number_type']=$k;
                $vv['car_type']=$v;
                $dataList[]=$vv;
            }
        }
        $count=count($list);
        $data1=[];
        $data1['list']=$dataList;
        $data1['count']=$count;
        $data1['total']=$count;

        return $data1;
    }
    
    /**
     * 添加绑定A11车牌类型
     * @author:zhubaodi
     * @date_time: 2022/12/13 10:24
     */
    public function addCarTypeA11($data,$garage_id=0){
        $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        if (empty($park_config)){
            throw new \think\Exception("请先设置停车相关配置");
        }
        $park_sys_type='A11';
        $where=[
            'village_id'=>$data['village_id'],
            'car_type'=>$data['type_id'],
            'car_number_type'=>$data['car_number_type'],
            'status'=>1,
            'park_sys_type'=>$park_sys_type
        ];
        $car_type_info=$db_house_village_park_charge_cartype->getFind($where);
        if (!empty($car_type_info)){
            throw new \think\Exception("当前卡类已绑定该车牌类型");
        }
        if (!isset($this->A11_type[$data['car_number_type']])){
            throw new \think\Exception("当前车牌类型不存在");
        }
        $arr=[];
        $arr['car_number_type']=$data['car_number_type'];
        $arr['village_id']=$data['village_id'];
        $arr['car_type']=$data['type_id'];
        $arr['park_sys_type']=$park_sys_type;
        $arr['status']=1;
        $arr['addTime']=time();
        $arr['updateTime']=time();
        $res=$db_house_village_park_charge_cartype->addFind($arr);
        return $res;
    }


    /**
     * 解除绑定A11车牌类型
     * @author:zhubaodi
     * @date_time: 2022/12/13 10:25
     */
    public function delCarTypeA11($data,$garage_id=0){
        $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        if (empty($park_config)){
            throw new \think\Exception("请先设置停车相关配置");
        }
        $park_sys_type='A11';
        $where=[
            'village_id'=>$data['village_id'],
            'car_type'=>$data['type_id'],
            'car_number_type'=>$data['car_number_type'],
            'status'=>1,
            'park_sys_type'=>$park_sys_type
        ];
        $car_type_info=$db_house_village_park_charge_cartype->getFind($where);
        if (empty($car_type_info)){
            throw new \think\Exception("绑定信息不存在");
        }
        if (!isset($this->A11_type[$data['car_number_type']])){
            throw new \think\Exception("当前车牌类型不存在");
        }
        $res=$db_house_village_park_charge_cartype->save_one($where,['status'=>2,'updateTime'=>time()]);
        return $res;
    }

    /**
     * 查询关联车道列表
     * @author:zhubaodi
     * @date_time: 2022/12/13 15:29
     */
    public function getPassageTypeList($data){
        $db_park_passage=new ParkPassage();
        $db_park_passage_type=new ParkPassageType();
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $garage_id=0;
        if(isset($data['garage_id']) && $data['garage_id']){
            $garage_id=$data['garage_id'];
        }
        $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        if (empty($park_config)){
            throw new \think\Exception("请先设置停车相关配置");
        }
        //查询当前小区所有已关联的车道
        $passage_ids1=$db_park_passage_type->getColumn(['village_id'=>$data['village_id']],'passage_id');
        $passage_ids2=$db_park_passage_type->getColumn(['village_id'=>$data['village_id']],'passage_relation');
        if (!empty($passage_ids1)&&!empty($passage_ids2)){
            $ids=array_merge($passage_ids1,$passage_ids2);
        }elseif(!empty($passage_ids1)&&empty($passage_ids2)){
            $ids=$passage_ids1;
        }elseif(empty($passage_ids1)&&!empty($passage_ids2)){
            $ids=$passage_ids2;
        }else{
            $ids=[];
        }
        
        $where=[];
        if($data['passage_direction']==1){
            $passage_direction =0;
        }else{
            $passage_direction =1;
        }
        $where[]= ['passage_direction','=',$passage_direction];
        if(!empty($ids)){
            $where[]=  ['id','not in',$ids];  
        }
        $where[]= ['village_id','=',$data['village_id']];
       /* $where[]=  ['passage_type','=',2];*/
        $where[]=['park_sys_type','=',$park_config['park_sys_type']];   
       
        $passage_list=$db_park_passage->getList($where,'id,passage_name');
        return  $passage_list;
    }
    
    /***
    ** 获取抢购 租赁的车位
     **/
    public function getRobOrLeaseCarPositionList($village_id=0,$garage_id=0,$opt_type=0,$page=1,$page_size=10){
        $returnArr=array('list'=>array(),'count'=>0,'page_size'=>$page_size);
        $whereArr=array();
        $whereArr[]=['village_id','=',$village_id];
        if($garage_id>0){
            $whereArr[]=['garage_id','=',$garage_id];
        }
        $whereArr[]=['position_status','=',1];
        if($opt_type==1){
            $whereArr[]=['opt_type','=',$opt_type];
        }else if($opt_type==2){
            $whereArr[]=['opt_type','=',$opt_type];
        }
        $whereArr[]=['used_count','<',1];
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $fieldStr='position_id,garage_id,position_num,position_area,position_type,position_note,village_id,opt_type';
        $parking_position_obj=$db_house_village_parking_position->getList($whereArr,$fieldStr,$page,$page_size);
        if($parking_position_obj && !$parking_position_obj->isEmpty()){
            $parking_positions=$parking_position_obj->toArray();
            $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
            $garageArr=array();
            foreach ($parking_positions as $kk=>$pvv){
                if(isset($garageArr[$pvv['garage_id']]) && !empty($garageArr[$pvv['garage_id']])){
                    $parking_positions[$kk]['garage_num']=$garageArr[$pvv['garage_id']]['garage_num'];
                }else{
                    $whereGarageArr=array(['village_id','=',$pvv['village_id']],['garage_id','=',$pvv['garage_id']]);
                    $parkingGarage=$houseVillageParkingGarageDb->getOne($whereGarageArr,'garage_id,garage_num,village_id');
                    if($parkingGarage && !$parkingGarage->isEmpty()){
                        $parkingGarage= $parkingGarage->toArray();
                        $parking_positions[$kk]['garage_num']=$parkingGarage['garage_num'];
                        $garageArr[$pvv['garage_id']]=$parkingGarage;
                    }
                }
            }
            $returnArr['list']=$parking_positions;
            $count_position=$db_house_village_parking_position->getCounts($whereArr);
            $returnArr['count']=$count_position>0 ? $count_position:0;
        }
        return $returnArr;
    }


    public function saveCarOtherInfo($village_id = 0, $binding_type = 0, $pdata = array())
    {

        if ($village_id < 1 || $binding_type < 1 || empty($pdata)) {
            return false;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $village_user_bind = array();
        $pigcms_id = 0;
        $uid = 0;
        if (!isset($pdata['car_id']) || !isset($pdata['relationship'])) {
            return false;
        }
        if ($binding_type == 2 && isset($pdata['pigcms_id']) && $pdata['pigcms_id'] > 0) {
            $user_bind = $db_house_village_user_bind->getSumBills(['village_id' => $village_id, 'pigcms_id' => $pdata['pigcms_id'], 'type' => [0, 1, 2, 3], 'status' => 1], 'pigcms_id,vacancy_id,uid');
            if (empty($user_bind) || $user_bind->isEmpty()) {
                return false;
            }
            if ($user_bind && isset($user_bind['pigcms_id'])) {
                $pigcms_id = $user_bind['pigcms_id'];
                $village_user_bind[] = $user_bind['pigcms_id'];
                $uid = $user_bind['uid'];
            }
        } elseif ($binding_type == 1 && isset($pdata['room_id']) && $pdata['room_id'] > 0) {

            $user_bind = $db_house_village_user_bind->getSumBills(['village_id' => $village_id, 'vacancy_id' => $pdata['room_id'], 'type' => [0, 1, 2, 3], 'status' => 1], 'pigcms_id,vacancy_id,uid');
            if (empty($user_bind) || $user_bind->isEmpty()) {
                return false;
            }
            $pigcms_id = $user_bind['pigcms_id'];
            $village_user_bind[] = $user_bind['pigcms_id'];
            $uid = $user_bind['uid'];
        }
        $db_house_village_bind_car = new HouseVillageBindCar();
        if (!empty($village_user_bind)) {
            $bing_info = $db_house_village_bind_car->getFind(['village_id' => $village_id, 'car_id' => $pdata['car_id']]);
            if (!empty($bing_info) && !$bing_info->isEmpty()) {
                $db_house_village_bind_car->saveOne(['village_id' => $village_id, 'car_id' => $pdata['car_id']], ['relationship' => $pdata['relationship'], 'user_id' => $pigcms_id, 'uid' => $uid]);
            } else {
                $db_house_village_bind_car->addOne(['village_id' => $village_id, 'car_id' => $pdata['car_id'], 'relationship' => $pdata['relationship'], 'user_id' => $pigcms_id, 'uid' => $uid]);
            }
        }
        return true;
    }

    public function update_position_id_test($village_id=0,$car_number='',$position_num='',$garage_id=0){
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $whereArr=array();
        $whereArr[]=['village_id','=',$village_id];
        $whereArr[]=['car_position_id','=',0];
        $whereArr[]=['car_number','=',$car_number];
        if($garage_id>0){
            $whereArr[]=['garage_id','=',$garage_id];
        }
        $carObj=$db_house_village_parking_car->getFind($whereArr);
        if($carObj && !$carObj->isEmpty()){
            $car=$carObj->toArray();
            if($car){
                $whereTmpArr=array();
                $whereTmpArr[]=['village_id','=',$village_id];
                $whereTmpArr[]=['position_num','=',$position_num];
                if($garage_id>0){
                    $whereTmpArr[]=['garage_id','=',$garage_id];
                }
                $position=$db_house_village_parking_position->getFind($whereTmpArr);
                if($position && !$position->isEmpty()){
                    $db_house_village_parking_car->editHouseVillageParkingCar(['car_id'=>$car['id']],['car_position_id'=>$position['position_id']]);
                }
            }
        }
    }
    
    /**
     * 查询车位号绑定车辆数量
     */
    public function getPositionBindNum($param)
    {
        if(!$param['village_id'] || !$param['car_position_id']){
            return ['num'=>0];
        }
        $positionArr=array();
        $position_bind_car=array('text1'=>'','text2'=>'','text3'=>'','text4'=>'','text5'=>'');
        $position_bind_info=array();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $count=0;
        if(is_array($param['car_position_id'])){
            foreach ($param['car_position_id'] as $kk=>$position_id){
                $whereArr=array('village_id'=>$param['village_id']);
                $whereRaw='(a.car_position_id ='.$position_id .' OR FIND_IN_SET('.$position_id.',bind_position_ids))';
                $bindCount=$db_house_village_parking_car->getNewCount($whereArr,$whereRaw);
                $bindCount=$bindCount>0 ? $bindCount:0;
                $count=$count+$bindCount;
                $parking_position_obj=$db_house_village_parking_position->getFind(array('position_id'=>$position_id),'position_id,garage_id,position_num');
                $position_num='';
                if($parking_position_obj && !$parking_position_obj->isEmpty()){
                    $position_num= $parking_position_obj['position_num'];
                }
                $textStr=$position_num.' 已绑定车辆数：'.$bindCount;
                //$pitem=array('position_id'=>$position_id,'bindCarCount'=>$bindCount,'position_num'=>$position_num,'position_bind_str'=>$textStr);
                $kkadd=$kk+1;
                $position_bind_car['text'.$kkadd]=$textStr;
                $position_bind_info[]=$textStr;
                //$positionArr[]=$pitem;
            }
        }else{
            $count=$db_house_village_parking_car->get_village_car_num([
                'village_id'=>$param['village_id'],
                'car_position_id'=>$param['car_position_id']
            ]);
        }

        return ['num'=>$count,'position_bind_info'=>$position_bind_info,'position_bind_car'=>$position_bind_car];
    }

    public function getParkCarAccessImg($record = array())
    {
        $imgArr = array('small_image' => '', 'big_image' => '', '_small_image' => '', '_big_image' => '');
        if (!empty($record['accessImage'])) {
            if (stripos($record['accessImage'], 'http://') !== false || stripos($record['accessImage'], 'https://') !== false) {
                $imgArr['small_image'] = $record['accessImage'];
            } else {
                $accessImageArr = explode('/upload/', $record['accessImage']);
                $imgArr['small_image'] = '/upload/' . $accessImageArr['1'];
                $imgArr['small_image'] = replace_file_domain($imgArr['small_image']);
            }
        }
        if (!empty($record['accessBigImage'])) {
            if (stripos($record['accessBigImage'], 'http://') !== false || stripos($record['accessBigImage'], 'https://') !== false) {
                $imgArr['big_image'] = $record['accessBigImage'];
            } else {
                $accessImageArr = explode('/upload/', $record['accessBigImage']);
                $imgArr['big_image'] = '/upload/' . $accessImageArr['1'];
                $imgArr['big_image'] = replace_file_domain($imgArr['big_image']);
            }
        }
        if (!empty($record['_accessImage'])) {
            if (stripos($record['_accessImage'], 'http://') !== false || stripos($record['_accessImage'], 'https://') !== false) {
                $imgArr['_small_image'] = $record['_accessImage'];
            } else {
                $accessImageArr = explode('/upload/', $record['_accessImage']);
                $imgArr['_small_image'] = '/upload/' . $accessImageArr['1'];
                $imgArr['_small_image'] = replace_file_domain($imgArr['_small_image']);
            }
        }
        if (!empty($record['_accessBigImage'])) {
            if (stripos($record['accessBigImage'], 'http://') !== false || stripos($record['_accessBigImage'], 'https://') !== false) {
                $imgArr['_big_image'] = $record['_accessBigImage'];
            } else {
                $accessImageArr = explode('/upload/', $record['_accessBigImage']);
                $imgArr['_big_image'] = '/upload/' . $accessImageArr['1'];
                $imgArr['_big_image'] = replace_file_domain($imgArr['_big_image']);
            }
        }
        return $imgArr;
    }
    
    public function getParkCarAccessRecords($where=array(),$data=array())
    {
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $count = $db_house_village_car_access_record->getCounts($where);
        $sum_field_total='b.total';
        $sum_field_prepayTotal='b.prepayTotal';
        $date_type=$data['date_type'];  // 1进场 2出场
        $orderby='b.accessTime DESC, b.total DESC,b.record_id DESC';
        if($date_type==2){
            $sum_field_total='a.total';
            $sum_field_prepayTotal='a.prepayTotal';
            $orderby='a.accessTime DESC, a.total DESC,a.record_id DESC';
        }
        $allTotalMoney = $db_house_village_car_access_record->getSums($where, $sum_field_total);
        $allTotalMoney1 = $db_house_village_car_access_record->getSums($where, $sum_field_prepayTotal);
        $allTotalMoney += $allTotalMoney1;
        if ($allTotalMoney > 0) {
            $allTotalMoney = round($allTotalMoney, 2);
        } else {
            $allTotalMoney = 0;
        }
        $fieldStr = 'a.record_id,a.business_id,a.car_number,a.accessType,a.accessTime,a.accessImage,a.accessBigImage,a.channel_id,a.channel_number,a.channel_name,a.accessMode,a.park_sys_type,a.park_id,a.uid,a.user_name,a.user_phone,a.total,a.prepayTotal,a.order_id,a.exception_type,a.position_id,a.position_num,a.garage_id';
        $fieldStr .=',b.record_id as _record_id,b.business_id as _business_id,b.car_number as _car_number,b.accessType as _accessType,b.accessTime as _accessTime,b.accessImage as _accessImage,b.accessBigImage as _accessBigImage,b.channel_id as _channel_id,b.channel_number as _channel_number,b.channel_name as _channel_name,b.accessMode as _accessMode';
        $fieldStr .=',b.park_sys_type as _park_sys_type,b.park_id as _park_id,b.uid as _uid,b.user_name as _user_name,b.user_phone as _user_phone,b.total as _total,b.prepayTotal as _prepayTotal,b.order_id as _order_id,b.exception_type as _exception_type,b.position_id as _position_id,b.position_num as _position_num,b.garage_id as _garage_id';
        
        $listObj = $db_house_village_car_access_record->getOutList($where, $fieldStr, $data['page'], $data['limit'],$orderby);
        $list = '';
        if ($listObj && !$listObj->isEmpty()) {
            $list = $listObj->toArray();
        }
        $newList=array();
        if ($list) {
            $villageArr=array();
            $garageArr=array();
            $passageArr=array();
            $houseVillageDb = new HouseVillage();
            $db_park_passage = new ParkPassage();
            foreach ($list as $val) {
                $tmpArr=array();
                $tmpArr['car_number']=$val['car_number'];
                $tmpArr['garage_id']=0;
                $tmpArr['garage_name']='';
                $tmpArr['position_id']=0;
                $tmpArr['position_num']='';
                $tmpArr['position_num']='';
                $tmpArr['device_number']='';
                $tmpArr['channel_number']='';	 
                $tmpArr['channel_name']='';
                $tmpArr['out_device_number']='';
                $tmpArr['out_channel_number']='';
                $tmpArr['out_channel_name']='';
                $tmpArr['park_sys_type']=$val['park_sys_type'];
                $tmpArr['exception_type'] = 0;
                $tmpArr['out_exception_type'] = 0;
                $tmpArr['out_pay_money']=0;
                $tmpArr['park_time']=0;
                $tmpArr['park_time_str']='';
                $tmpArr['small_image']='';
                $tmpArr['big_image']='';
                $tmpArr['out_small_image']='';
                $tmpArr['out_big_image']='';
                $tmpArr['access_time']=0;
                $tmpArr['out_access_time']=0;
                $tmpArr['order_id']='';
                $tmpArr['out_order_id']='';
                $tmpArr['user_name']='';
                $tmpArr['user_phone']='';
                if($date_type==2){
                    //出场记录
                    $tmpArr['record_id']=$val['_record_id'] ? $val['_record_id']:0;
                    $tmpArr['position_id']=$val['_position_id'] ? $val['_position_id']:0;
                    $tmpArr['position_num']=$val['_position_num'] ? $val['_position_num']:'';
                    $tmpArr['out_record_id']=$val['record_id'];
                    $tmpArr['device_number']='';
                    $tmpArr['channel_number']=$val['_channel_number'] ? $val['_channel_number']:0;
                    $tmpArr['channel_name']=$val['_channel_name'] ? $val['_channel_name']:'';
                    $tmpArr['exception_type'] = $val['_exception_type']>0 ? 1 : 0;
                    $tmpArr['out_exception_type'] = $val['exception_type']>0 ? 1 : 0;
                    $tmpArr['access_time']=0;
                    $tmpArr['out_access_time']=$val['accessTime'];
                    $tmpArr['access_time']=$val['_accessTime'] >0 ? $val['_accessTime']:0;
                    $tmpArr['order_id']=$val['_order_id'];
                    $tmpArr['out_order_id']=$val['order_id'];
                    if($val['_channel_id']>0){
                        //进场通道
                        if(isset($passageArr[$val['_channel_id']])){
                            $tmpArr['device_number']=$passageArr[$val['_channel_id']]['device_number'] ? $passageArr[$val['_channel_id']]['device_number'] :'';
                            $tmpArr['channel_number']=$passageArr[$val['_channel_id']]['channel_number'] ? $passageArr[$val['_channel_id']]['channel_number']:0;
                            $tmpArr['channel_name']=$passageArr[$val['_channel_id']]['passage_name'] ? $passageArr[$val['_channel_id']]['passage_name']:'';
                            $tmpArr['park_sys_type']=$tmpArr['park_sys_type'] ? $tmpArr['park_sys_type']:$passageArr[$val['_channel_id']]['park_sys_type'];
                        }else{
                            $wherePassage=array(['id','=',$val['_channel_id']]);
                            $park_passage_obj = $db_park_passage->getFind($wherePassage,'park_sys_type,passage_name,device_number,channel_number');
                            if($park_passage_obj && !$park_passage_obj->isEmpty()){
                                $tmpArr['device_number']=$park_passage_obj['device_number'] ? $park_passage_obj['device_number'] :'';
                                $tmpArr['channel_number']=$park_passage_obj['channel_number'] ? $park_passage_obj['channel_number']:0;
                                $tmpArr['channel_name']=$park_passage_obj['passage_name'] ? $park_passage_obj['passage_name']:'';
                                $tmpArr['park_sys_type']=$tmpArr['park_sys_type'] ? $tmpArr['park_sys_type']:$park_passage_obj['park_sys_type'];
                                $passageArr[$val['_channel_id']]=$park_passage_obj->toArray();
                            }
                        }
                    }
                    
                    $tmpArr['out_device_number']='';
                    $tmpArr['out_channel_number']=$val['channel_number'];
                    $tmpArr['out_channel_name']=$val['channel_name'];
                    if($val['channel_id']>0) {
                        //出场通道
                        if (isset($passageArr[$val['channel_id']])) {
                            $tmpArr['out_device_number'] = $passageArr[$val['channel_id']]['device_number'] ? $passageArr[$val['channel_id']]['device_number'] : '';
                            $tmpArr['out_channel_number'] = $passageArr[$val['channel_id']]['channel_number'] ? $passageArr[$val['channel_id']]['channel_number'] : 0;
                            $tmpArr['out_channel_name'] = $passageArr[$val['channel_id']]['passage_name'] ? $passageArr[$val['channel_id']]['passage_name'] : '';
                            $tmpArr['park_sys_type'] = $tmpArr['park_sys_type'] ? $tmpArr['park_sys_type'] : $passageArr[$val['channel_id']]['park_sys_type'];
                        } else {
                            $wherePassage = array(['id', '=', $val['channel_id']]);
                            $park_passage_obj = $db_park_passage->getFind($wherePassage, 'park_sys_type,passage_name,device_number,channel_number');
                            if ($park_passage_obj && !$park_passage_obj->isEmpty()) {
                                $tmpArr['out_device_number'] = $park_passage_obj['device_number'] ? $park_passage_obj['device_number'] : '';
                                $tmpArr['out_channel_number'] = $park_passage_obj['channel_number'] ? $park_passage_obj['channel_number'] : 0;
                                $tmpArr['out_channel_name'] = $park_passage_obj['passage_name'] ? $park_passage_obj['passage_name'] : '';
                                $tmpArr['park_sys_type'] = $tmpArr['park_sys_type'] ? $tmpArr['park_sys_type'] : $park_passage_obj['park_sys_type'];
                                $passageArr[$val['channel_id']] = $park_passage_obj->toArray();
                            }
                        }
                    }
                    $total = $val['total'] ? $val['total'] : 0;
                    $prepayTotal = $val['prepayTotal'] ? $val['prepayTotal'] : 0;
                    $tmpArr['out_pay_money'] = $total + $prepayTotal;
                    $tmpArr['out_pay_money']=round($tmpArr['out_pay_money'],2);
                    if ( $val['accessTime']>0 &&  $val['_accessTime']>0) {
                        $park_time = $val['accessTime'] - $val['_accessTime'];
                        $tmpArr['park_time'] = $park_time > 0 ? $park_time : 0;
                    }
                    $imgTmp=$this->getParkCarAccessImg($val);
                    $tmpArr['small_image']=$imgTmp['_small_image'];
                    $tmpArr['big_image']=$imgTmp['_big_image'];
                    $tmpArr['out_small_image']=$imgTmp['small_image'];
                    $tmpArr['out_big_image']=$imgTmp['big_image'];

                    $tmpArr['user_name']=$val['_user_name'];
                    $tmpArr['user_phone']=$val['_user_phone'];
                    if($val['user_name']){
                        $tmpArr['user_name']=$val['user_name'];
                    }
                    if($val['user_phone']){
                        $tmpArr['user_phone']=$val['user_phone'];
                    }
                }else{
                    //进场记录
                    $tmpArr['record_id']=$val['record_id'];
                    $tmpArr['position_id']=$val['position_id'];
                    $tmpArr['position_num']=$val['position_num'];
                    $tmpArr['out_record_id']=$val['_record_id'] ? $val['_record_id']:0;
                    $tmpArr['device_number']='';
                    $tmpArr['channel_number']=$val['channel_number']; 
                    $tmpArr['channel_name']=$val['channel_name'];
                    $tmpArr['exception_type'] = $val['exception_type']>0 ? 1 : 0;
                    $tmpArr['out_exception_type'] = $val['_exception_type']>0 ? 1 : 0;
                    $tmpArr['access_time']=$val['accessTime'];
                    $tmpArr['out_access_time']=$val['_accessTime'] >0 ? $val['_accessTime']:0;
                    $tmpArr['order_id']=$val['order_id'];
                    $tmpArr['out_order_id']=$val['_order_id'];
                    if($val['channel_id']>0) {
                        //进场通道
                        if (isset($passageArr[$val['channel_id']])) {
                            $tmpArr['device_number'] = $passageArr[$val['channel_id']]['device_number'] ? $passageArr[$val['channel_id']]['device_number'] : '';
                            $tmpArr['channel_number'] = $passageArr[$val['channel_id']]['channel_number'] ? $passageArr[$val['channel_id']]['channel_number'] : 0;
                            $tmpArr['channel_name'] = $passageArr[$val['channel_id']]['passage_name'] ? $passageArr[$val['channel_id']]['passage_name'] : '';
                            $tmpArr['park_sys_type'] = $tmpArr['park_sys_type'] ? $tmpArr['park_sys_type'] : $passageArr[$val['channel_id']]['park_sys_type'];
                        } else {
                            $wherePassage = array(['id', '=', $val['channel_id']]);
                            $park_passage_obj = $db_park_passage->getFind($wherePassage, 'park_sys_type,passage_name,device_number,channel_number');
                            if ($park_passage_obj && !$park_passage_obj->isEmpty()) {
                                $tmpArr['device_number'] = $park_passage_obj['device_number'] ? $park_passage_obj['device_number'] : '';
                                $tmpArr['channel_number'] = $park_passage_obj['channel_number'] ? $park_passage_obj['channel_number'] : 0;
                                $tmpArr['channel_name'] = $park_passage_obj['passage_name'] ? $park_passage_obj['passage_name'] : '';
                                $tmpArr['park_sys_type'] = $tmpArr['park_sys_type'] ? $tmpArr['park_sys_type'] : $park_passage_obj['park_sys_type'];
                                $passageArr[$val['channel_id']] = $park_passage_obj->toArray();
                            }
                        }
                    }
                    
                    $tmpArr['out_device_number']='';
                    $tmpArr['out_channel_number']=$val['_channel_number'] ? $val['_channel_number']:0;
                    $tmpArr['out_channel_name']=$val['_channel_name'] ? $val['_channel_name']:'';
                    if($val['_channel_id']>0) {
                        //出场通道
                        if (isset($passageArr[$val['_channel_id']])) {
                            $tmpArr['out_device_number'] = $passageArr[$val['_channel_id']]['device_number'] ? $passageArr[$val['_channel_id']]['device_number'] : '';
                            $tmpArr['out_channel_number'] = $passageArr[$val['_channel_id']]['channel_number'] ? $passageArr[$val['_channel_id']]['channel_number'] : 0;
                            $tmpArr['out_channel_name'] = $passageArr[$val['_channel_id']]['passage_name'] ? $passageArr[$val['_channel_id']]['passage_name'] : '';
                            $tmpArr['park_sys_type'] = $tmpArr['park_sys_type'] ? $tmpArr['park_sys_type'] : $passageArr[$val['_channel_id']]['park_sys_type'];
                        } else {
                            $wherePassage = array(['id', '=', $val['_channel_id']]);
                            $park_passage_obj = $db_park_passage->getFind($wherePassage, 'park_sys_type,passage_name,device_number,channel_number');
                            if ($park_passage_obj && !$park_passage_obj->isEmpty()) {
                                $tmpArr['out_device_number'] = $park_passage_obj['device_number'] ? $park_passage_obj['device_number'] : '';
                                $tmpArr['out_channel_number'] = $park_passage_obj['channel_number'] ? $park_passage_obj['channel_number'] : 0;
                                $tmpArr['out_channel_name'] = $park_passage_obj['passage_name'] ? $park_passage_obj['passage_name'] : '';
                                $tmpArr['park_sys_type'] = $tmpArr['park_sys_type'] ? $tmpArr['park_sys_type'] : $park_passage_obj['park_sys_type'];
                                $passageArr[$val['_channel_id']] = $park_passage_obj->toArray();
                            }
                        }
                    }
                    $total = $val['_total'] ? $val['_total'] : 0;
                    $prepayTotal = $val['_prepayTotal'] ? $val['_prepayTotal'] : 0;
                    $tmpArr['out_pay_money'] = $total + $prepayTotal;
                    $tmpArr['out_pay_money']=round($tmpArr['out_pay_money'],2);
                    if ($val['accessTime']>0 &&  $val['_accessTime']>0) {
                        $park_time = $val['_accessTime'] - $val['accessTime'];
                        $tmpArr['park_time'] = $park_time > 0 ? $park_time : 0;
                    }

                    $imgTmp=$this->getParkCarAccessImg($val);
                    $tmpArr['small_image']=$imgTmp['small_image'];
                    $tmpArr['big_image']=$imgTmp['big_image'];
                    $tmpArr['out_small_image']=$imgTmp['_small_image'];
                    $tmpArr['out_big_image']=$imgTmp['_big_image'];

                    $tmpArr['user_name']=$val['user_name'];
                    $tmpArr['user_phone']=$val['user_phone'];
                    if($val['_user_name']){
                        $tmpArr['user_name']=$val['_user_name'];
                    }
                    if($val['_user_phone']){
                        $tmpArr['user_phone']=$val['_user_phone'];
                    }
                }
                
                if($val['garage_id']>0){
                    $tmpArr['garage_id']=$val['garage_id'];
                }else if($val['_garage_id']>0){
                    $tmpArr['garage_id']=$val['_garage_id'];
                }
                $village_id= intval($val['park_id']);
                $whereArr = array();
                if (!empty($val['channel_id'])) {
                    $whereArr[] = array('id', '=', $val['channel_id']);
                } else if (!empty($val['channel_number'])) {
                    $whereArr[] = array('channel_number', '=', $val['channel_number']);
                }
                if($tmpArr['garage_id']>0 && isset($garageArr[$tmpArr['garage_id']])){
                    $tmpArr['garage_name'] = $garageArr[$tmpArr['garage_id']];
                }else{
                    $garage_num = $this->getGarageInfo($whereArr,$village_id,$tmpArr['garage_id']);
                    if ($garage_num) {
                        $tmpArr['garage_name'] = $garage_num;
                        if($tmpArr['garage_id']){
                            $garageArr[$tmpArr['garage_id']]=$garage_num;
                        }
                    }
                }

                $tmpArr['village_id']=$village_id;
                $tmpArr['village_name']='';
                $tmpArr['village_address']='';
                $tmpArr['property_id']='';
                
                if ($village_id > 0) {
                    if (isset($villageArr[$village_id])) {
                        $tmpArr['village_name'] = $villageArr[$village_id]['village_name'];
                        $tmpArr['village_address'] = $villageArr[$village_id]['village_address'];
                        $tmpArr['property_id'] = $villageArr[$village_id]['property_id'];
                    } else {
                        $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,village_address,property_id');
                        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                            $tmpArr['village_name'] = $houseVillageObj['village_name'];
                            $tmpArr['village_address'] = $houseVillageObj['village_address'];
                            $tmpArr['property_id'] = $houseVillageObj['property_id'];
                            $villageArr[$village_id] = $houseVillageObj->toArray();
                        }
                    }
                }
                
                if ($tmpArr['park_time']>0) {
                    $hours = intval($tmpArr['park_time'] / 3600);
                    $minter = ceil(($tmpArr['park_time'] - $hours * 3600) / 60);
                    if ($tmpArr['park_time'] >= 3600) {
                        $tmpArr['park_time_str'] = $hours . '小时' . $minter . '分钟';
                    } else {
                        $tmpArr['park_time_str'] = $minter . '分钟';
                    }
                }
                ksort($tmpArr);
                $newList[]=$tmpArr;
            }
        }
        $data1 = [];
        $data1['list'] = $newList ? $newList:array();
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['all_pay_money'] = $allTotalMoney;
        return $data1;
    }
    public function delCarAccessRecordTask(){
        fdump_api(['xtime'=>time()], '00delCarAccessRecordTask',true);
        $houseVillageCarAccessRecord= new HouseVillageCarAccessRecord();
        $whereArr=array(['accessTime','<=',1734624000]); //2024-12-20 之前的数据
        $carAccessRecordObj=$houseVillageCarAccessRecord->get_list($whereArr,'record_id',1,1000,'record_id ASC');
        $carAccessRecords=array();
        if($carAccessRecordObj && !$carAccessRecordObj->isEmpty()){
            $carAccessRecords= $carAccessRecordObj->toArray();
        }
        $dcount=0;
        $record_id=0;
        if($carAccessRecords){
            foreach ($carAccessRecords as $rvv){
                $dcount++;
                $record_id=$rvv['record_id'];
                $whereDelArr=array(['record_id','=',$rvv['record_id']]);
                $houseVillageCarAccessRecord->deleteData($whereDelArr);
            }
        }
        fdump_api(['xtime'=>time(),'dcount'=>$dcount,'record_id'=>$record_id], '00delCarAccessRecordTask',true);
        return true;
    }
}
