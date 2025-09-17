<?php
/**
 *文档  http://doc.cpyht.com/web/#/304
 * 2024-09-27
 */

namespace app\community\model\service\Park;

use think\facade\Cache;

class AiHorseSzymzhService
{

    public $village_id = 0;
    public $app_id = '';
    public $app_secret = '';
    public $version = 'v1.0';
    //public $requestApiUri = 'http://openapi.szymzh.com/Api';
    public $requestApiUri ='http://openapi.ymiot.net/Api';
    public function __construct($village_id = 0, $app_id = '', $app_secret = '')
    {
        $this->village_id = $village_id;
        if (empty($app_id)) {
            $this->app_id = cfg('park_aicar_app_id');
            $this->app_id = $this->app_id ? trim($this->app_id) : '';
        }
        if (empty($app_secret)) {
            $this->app_secret = cfg('park_aicar_app_secret');
            $this->app_secret = $this->app_secret ? trim($this->app_secret) : '';
        }
    }

    public function getApiBaseInfo()
    {
        return array('village_id' => $this->village_id, 'app_id' => $this->app_id, 'app_secret' => $this->app_secret, 'version' => $this->version);
    }

    /*
    *接口已废弃
    */
    public function getVillageAllParkGarages()
    {
        $request_url = $this->requestApiUri . '/Inquire/GetAllParkInfo';
        $this->commonApiRequest($request_url);
    }

    public function getOneParkGarageDetail($park_key = '')
    {
        $request_url = $this->requestApiUri . '/Inquire/GetParkInfo';
        $params = array('parkKey' => $park_key);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '未获取到数据';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    public function getParkGaragePassageList($park_key = '', $indexId = '0')
    {
        $request_url = $this->requestApiUri . '/Inquire/GetVehicleInfo';
        //$params=array('parkKey'=>$park_key,'indexId'=>$indexId,'size'=>'20');
        $params = array('parkKey' => $park_key);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '未获取到数据';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    public function getParkGaragePassageStatus($park_key = '', $vlCtrNo = '0')
    {
        $request_url = $this->requestApiUri . '/Inquire/GetVehicleOnline';
        $params = array('parkKey' => $park_key, 'ctrlNo' => $vlCtrNo);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '未获取到数据';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /***
     * 远程手动开闸
     */
    public function openPassageGate($park_key = '', $vlCtrNo = '')
    {
        $request_url = $this->requestApiUri . '/Inform/OpenGate';
        $params = array('parkKey' => $park_key, 'vlCtrNo' => $vlCtrNo);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '开闸失败了！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /***
     * 远程手动关闸
     */
    public function closePassageGate($park_key = '', $vlCtrNo = '')
    {
        $request_url = $this->requestApiUri . '/Inform/CloseGate';
        $params = array('parkKey' => $park_key, 'vlCtrNo' => $vlCtrNo);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '开闸失败了！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 车牌号查询停车订单费用
     **/
    public function getCarNoParkingFee($park_key = '', $carNo = '', $freeMinutes = '0')
    {
        $request_url = $this->requestApiUri . '/Inquire/GetCarNoOrderFee';
        $freeMinutes = strval($freeMinutes);
        $params = array('parkKey' => $park_key, 'carNo' => $carNo, 'freeMinutes' => $freeMinutes);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '未查询到费用数据！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 获取车道车辆订单详情
     * ctrlNo 车道控制机号
     **/
    public function getCarParkingPassageFee($park_key = '', $ctrlNo = '')
    {
        $request_url = $this->requestApiUri . '/Inquire/GetVehicleCarInfo';
        $ctrlNo = strval($ctrlNo);
        $params = array('parkKey' => $park_key, 'ctrlNo' => $ctrlNo);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '未查询到费用数据！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 车牌号查询停车订单费用
     * orderNo 停车订单号
     **/
    public function getCarParkingFeeByOrder($park_key = '', $orderNo = '', $carTypeNo = '', $freeMinutes = '0')
    {
        $request_url = $this->requestApiUri . '/Inquire/GetOrderFee';
        $freeMinutes = strval($freeMinutes);
        $carTypeNo = strval($carTypeNo);
        $params = array('parkKey' => $park_key, 'orderNo' => $orderNo, 'carTypeNo' => $carTypeNo, 'freeMinutes' => $freeMinutes);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '未查询到费用数据！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 出口缴费开闸
     *
     **/
    public function payToUnVehicleOut($park_key = '', $orderInfo = array())
    {
        $request_url = $this->requestApiUri . '/Inform/UnVehicleOut';
        $ctrlNo = strval($orderInfo['ctrlNo']);
        $payedMoney = strval($orderInfo['payedMoney']);
        $payedSN = $orderInfo['payed_sn'];
        $params = array('parkKey' => $park_key, 'ctrlNo' => $ctrlNo, 'payOrderNo' => $orderInfo['payOrderNo'], 'payedMoney' => $payedMoney, 'payedSN' => $payedSN);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '出口缴费开闸失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 新增月租车用户
     *
     **/
    public function addMonthCarPerson($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/AddPerson';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '新增月租车用户失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求新增月租车用户接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    public function editMonthCarPerson($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/EditPerson';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '修改月租车用户失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求修改月租车用户接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 新增月租车用户
     *
     **/
    public function addMonthCar($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/AddMthCar';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '新增月租车失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求新增月租车接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 月租车充值（按月 或者 按时间段）
     *
     **/
    public function chargeMonthCarTime($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/ChargeMonthCar';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '月租车充值失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求月租车充值接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 注销月租车
     *该接口来注销禁用车牌
     **/
    public function failMonthCar($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/FailMonthCar';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '注销月租车失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求注销月租车接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }
    /**
     * 访客车辆新增
     *访客车辆登记，登记时间必须在1小时之内，停车场必须支持且开启访客车功能
     **/
    public function addReservationCar($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/Reservation';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '访客车辆新增失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求访客车辆新增接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }

    /**
     * 取消访客车预约
     *访客车辆删除
     **/
    public function delReservationCar($park_key = '', $params = array())
    {
        $request_url = $this->requestApiUri . '/Inform/CancelReservation';
        $params['parkKey'] = $park_key;
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        try {
            $responseData = $this->commonApiRequest($request_url, $params);
            if ($responseData && isset($responseData['code'])) {
                if (isset($responseData['data']) && !empty($responseData['data'])) {
                    $returnArr['data'] = $responseData['data'];
                } else {
                    $err_msg = '取消访客车预约失败！';
                    if ($responseData['code'] != 1 && isset($responseData['msg']) && !empty($responseData['msg'])) {
                        $err_msg = $responseData['msg'];
                    }
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = $err_msg;
                }
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '请求取消访客车预约接口失败！';
            }
        } catch (\Exception $e) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $e->getMessage();
        }
        return $returnArr;
    }
    
    /**
     * 数据签名.
     */
    public function getThirdDataSign($datas = array())
    {
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $buffStr = "";
        foreach ($datas as $kk => $vv) {
            if($vv!==''){
                $buffStr .= $kk . '=' . $vv . '&';
            }
        }
        $pString = '';
        if (strlen($buffStr) > 0) {
            //签名步骤二：在string后加入KEY
            $buffStr = $buffStr . $this->app_secret;
            $pString = md5($buffStr);
            $pString = strtoupper($pString);
        }
        return $pString;
    }

    /**
     * 公用的组装请求.
     */
    public function commonApiRequest($requestApiUrl = '', $params = array(), $method = 'post')
    {
        $params['appid'] = $this->app_id;
        $params['version'] = $this->version;
        $params['rand'] = rand(1, 9) . '.' . date('His') . rand(100, 999);
        ksort($params);
        $method = trim($method);
        $signStr = $this->getThirdDataSign($params);
        $requestData = $params;
        $requestData['sign'] = $signStr;
        $headers = array();
        $headers[] = "Accept-Charset: utf-8";
        $headers[] = "Content-type: application/json;";
        $headers[] = 'Content-Encoding: utf-8';
        $requestJsonStr = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = http_request($requestApiUrl, $method, $requestJsonStr, $headers);
        $responseData = array();
        fdump_api(['_Line_' . __LINE__, 'params' => $params, 'requestJsonStr' => $requestJsonStr, 'requestApiUrl' => $requestApiUrl, 'result' => $result], 'aiHorseSzymzh/commonApiRequest' . date('H') . 'Log', 1);
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], 1);
        }
        return $responseData;
    }

    //todo 校验组装车牌
    public function checkCarNumber($province = '', $car_number = '')
    {
        $str = $car_number;
        if (!empty($province) && !empty($car_number)) {
            if (strpos($car_number, $province) !== false) {
                $str = $car_number;
            } else {
                $str = $province . $car_number;
            }
        }
        return $str;
    }

    //todo 解析车牌
    public function getCarNumber($car_number)
    {
        if (preg_match('/[\x7f-\xff]/', $car_number)) {
            return [
                'province' => mb_substr($car_number, 0, 1),
                'car_number' => mb_substr($car_number, 1)
            ];
        } else {
            return [
                'province' => '',
                'car_number' => $car_number
            ];
        }
    }
}