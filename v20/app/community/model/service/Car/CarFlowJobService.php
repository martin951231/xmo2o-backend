<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      门禁权限下发状态订阅消息(目前仅用于海康云眸内部)
 */


namespace app\community\model\service\Car;

use app\community\model\db\Car\HouseVillageCarFlowIdentify;
use app\community\model\db\Car\HouseVillageCarFlowRecord;
use app\community\model\db\Car\HouseVillageCarFlowTemp;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkChargeCartype;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\InPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkScrcuRecord;
use app\community\model\db\ParkSerialLog;
use app\community\model\db\User;
use app\community\model\service\Park\A11Service;
use app\consts\Car\CarFlowConst;
use app\traits\Car\CarFlowInTraits;
use app\traits\Car\CarFlowOutTraits;
use app\traits\Car\CarFlowPlateResultTraits;
use app\traits\Car\CarFlowCouponTraits;
use app\traits\Car\CarFlowTempTraits;

class CarFlowJobService
{
    use CarFlowInTraits, CarFlowPlateResultTraits, CarFlowOutTraits, CarFlowCouponTraits, CarFlowTempTraits;

    /**
     * 入场队列重启 延时3秒
     */
    public function againToInTraits($data) {
        $data['again'] = 1;
        return $this->traitCommonCarFlowIn($data, 3);
    }

    /**
     * 识别记录队列重启 延时3秒
     */
    public function againToPlateResultTraits($data) {
        $data['again'] = 1;
        return $this->traitCommonCarFlowPlateResult($data, 3);
    }
    /**
     * 出场队列重启 延时3秒
     */
    public function againToOutTraits($data) {
        $data['again'] = 1;
        return $this->traitCommonCarFlowOut($data, 3);
    }

    /**
     * 优惠券队列重启 延时3秒
     */
    public function againToCouponTraits($data) {
        $data['again'] = 1;
        return $this->traitCommonCarFlowCoupon($data, 3);
    }

    /**
     * 临时中间表队列重启 延时3秒
     */
    public function againToTempTraits($data) {
        $data['again'] = 1;
        return $this->traitCommonCarFlowTemp($data, 3);
    }

    /**
     * 添加[in_park]入场记录.
     */
    public function addInPark($data) {
        $printParams = $data;
        $printParams = unsetParam($printParams, ['imageFile', 'AlarmInfoPlate']);
        fdump_api(['msg' => '添加[in_park]入场记录-方法入参', 'parameter' => ['data' => $printParams], 'line' => __LINE__], 'parkBetter/addInParkLog', 1);
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $flow_time = isset($data['flow_time']) && $data['flow_time'] ? intval($data['flow_time']) : time();
        $in_time = isset($data['in_time']) && $data['in_time'] ? intval($data['in_time']) : $flow_time;
        $serialno = isset($data['serialno']) && $data['serialno'] ? trim($data['serialno']) : '';
        $order_id = isset($data['order_id']) && $data['order_id'] ? trim($data['order_id']) : md5($serialno.$car_number.$flow_time);
        $in_channel_id = isset($data['in_channel_id']) && $data['in_channel_id'] ? intval($data['in_channel_id']) : 0;
        $is_paid = isset($data['is_paid']) ? intval($data['is_paid']) : 0;
        $is_out = isset($parkData['is_out']) ? intval($data['is_out']) : 0;
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_id = isset($data['park_id']) && $data['park_id'] ? intval($data['park_id']) : $village_id;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $park_name = isset($data['park_name']) && $data['park_name'] ? trim($data['park_name']) : '';
        $in_image_big = isset($data['in_image_big']) && $data['in_image_big'] ? trim($data['in_image_big']) : '';
        $in_image_small = isset($data['in_image_small']) && $data['in_image_small'] ? trim($data['in_image_small']) : '';

        $flow_record_id = isset($data['flow_record_id']) && $data['flow_record_id'] ? intval($data['flow_record_id']) : 0;
        $unique_id = isset($data['unique_id']) && $data['unique_id'] ? trim($data['unique_id']) : '';
        $garage_id = isset($data['garage_id']) && $data['garage_id'] ? $data['garage_id'] : 0;
        $flow_direction = isset($data['flow_direction']) && $data['flow_direction'] ? $data['flow_direction'] : 1;
        $park_passage_id = isset($data['park_passage_id']) && $data['park_passage_id'] ? $data['park_passage_id'] : 0;

        $park_data = [
            'car_number' => $car_number,
            'in_time' => $in_time,
            'order_id' => $order_id,
            'in_channel_id' => $in_channel_id,
            'is_paid' => $is_paid,
            'park_id' => $park_id,
            'park_sys_type' => $park_sys_type,
            'park_name' => $park_name,
        ];
        $in_image_big && $park_data['in_image_big'] = $in_image_big;
        $in_image_small && $park_data['in_image_small'] = $in_image_small;
        
        $db_in_park = new InPark();
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();

        $startTime = $in_time - 60;
        $endTime = $in_time + 30;
        $park_where = [
            ['park_sys_type', '=', $park_sys_type],
            ['car_number', '=', $car_number],
            ['park_id', '=', $park_id],
            ['in_time', '>=', $startTime],
            ['in_time', '<=', $endTime],
            ['del_time', '=', 0],
        ];
        $log = $car_number ? $car_number : 'car_numbe_log';
        $parkInfoCar = $db_in_park->getOne1($park_where);
        if (isset($parkInfoCar['id']) && $parkInfoCar['id']) {
            // 1分钟左右重复进场 不做重复记录
            $in_park_id = $parkInfoCar['id'];
        } else {
            fdump_api(['msg' => '1-记录入场信息', 'park_data' => $park_data], 'addInPark/'.$log, 1);
            $in_park_id = $db_in_park->insertOne($park_data);
        }
        fdump_api(['msg' => '2-记录入场信息', 'data' => $printParams, 'parkInfoCar' => $parkInfoCar, 'park_where' => $park_where, 'in_park_id' => $in_park_id], 'addInPark/'.$log, 1);
        if ($flow_record_id) {
            $whereUnique = [ 'id' => $flow_record_id];
        } elseif ($unique_id) {
            $whereUnique = [ 'unique_id' => $unique_id];
        } else {
            $uniqueArr = [
                'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid,
            ];
            $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
            $whereUnique = [ 'unique_id' => $unique_id];
        }
        if (isset($whereUnique) && !empty($whereUnique)) {
            $updateData = [
                'from_id' => $in_park_id, 'order_id' => $order_id, 'is_first' => 0
            ];
            $whereFlowCount = [];
            $whereFlowCount[] = ['car_number', '=', $car_number];
            $whereFlowCount[] = ['village_id', '=', $village_id];
            $flowCount = $db_house_village_car_flow_record->getCount($whereFlowCount);
            if ($flowCount <= 1) {
                $park_count = $db_in_park->getCount(['car_number' => $car_number, 'park_id' => $park_id, 'del_time' => 0]);
                if ($park_count <= 1) {
                    $updateData['is_first'] = 1;
                }
            }
            fdump_api(['msg' => '1-更改记录入场信息', 'whereUnique' => $whereUnique, 'updateData' => $updateData], 'addInPark/'.$log, 1);
            $save = $db_house_village_car_flow_record->updateThis($whereUnique, $updateData);
            fdump_api(['msg' => '2-更改记录入场信息', 'save' => $save], 'addInPark/'.$log, 1);
        }
        
        $isAddAccessRecord = isset($data['isAddAccessRecord']) && $data['isAddAccessRecord'] ? intval($data['isAddAccessRecord']) : 0;
        if ($isAddAccessRecord) {
            $data['accessType'] = 1;
            $data['from_id'] = $in_park_id;
            $this->addCarAccessRecord($data);
        } else {
            $imageFile = isset($data['imageFile']) ? trim($data['imageFile']) : '';
            $imageFragmentFile = isset($data['imageFragmentFile']) ? trim($data['imageFragmentFile']) : '';
            $imageToJob = [];
            // 处理图片 走队列
            $ymdhis = date('Ymdhis', $flow_time);
            if ($imageFile) {
                $savePathFile = $ymdhis . '_image_big_' . $car_number;
                $imageToJob['park_type'] = 'in_park';
                $in_park_id && $imageToJob['in_park_id'] = $in_park_id;
                $imageToJob['serialno'] = $in_channel_id;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['in_time'] = $flow_time;
                $imageToJob['order_id'] = $order_id;
                $imageToJob['village_id'] = $park_id;
                $imageToJob['nowTime'] = $flow_time;
                $imageToJob['imageFile'] = $imageFile;
                $imageToJob['imageFileSaveFileName'] = $savePathFile;
            }
            if ($imageFragmentFile) {
                if (empty($imageToJob)) {
                    $imageToJob['park_type'] = 'in_park';
                    $in_park_id && $imageToJob['in_park_id'] = $in_park_id;
                    $imageToJob['serialno'] = $in_channel_id;
                    $imageToJob['car_number'] = $car_number;
                    $imageToJob['in_time'] = $flow_time;
                    $imageToJob['order_id'] = $order_id;
                    $imageToJob['village_id'] = $park_id;
                    $imageToJob['nowTime'] = $flow_time;
                }
                $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
                $imageToJob['imageFragmentFile'] = $imageFragmentFile;
                $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
            }
            if (empty($imageToJob)) {
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
        }
        return true;
    }
    
    /**
     * 添加[out_park]出场+更新[in_park]记录.
     */
    public function addOutPark($data) {
        $printParams = $data;
        $printParams = unsetParam($printParams, ['imageFile', 'AlarmInfoPlate']);
        fdump_api(['msg' => '添加[out_park]出场+更新[in_park]记录-方法入参', 'parameter' => ['data' => $printParams], 'line' => __LINE__], 'parkBetter/addOutParkLog', 1);
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $flow_time = isset($data['flow_time']) && $data['flow_time'] ? intval($data['flow_time']) : time();
        $out_time = isset($data['out_time']) && $data['out_time'] ? intval($data['out_time']) : $flow_time;
        $in_time = isset($data['in_time']) && $data['in_time'] ? intval($data['in_time']) : 0;
        $order_id = isset($data['order_id']) && $data['order_id'] ? trim($data['order_id']) : md5(uniqid().$car_number.$in_time);
        $in_channel_id = isset($data['in_channel_id']) && $data['in_channel_id'] ? intval($data['in_channel_id']) : 0;
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_id = isset($data['park_id']) && $data['park_id'] ? intval($data['park_id']) : $village_id;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $park_name = isset($data['park_name']) && $data['park_name'] ? trim($data['park_name']) : '';
        $in_image_big = isset($data['in_image_big']) && $data['in_image_big'] ? trim($data['in_image_big']) : '';
        $in_image_small = isset($data['in_image_small']) && $data['in_image_small'] ? trim($data['in_image_small']) : '';
        
        $flow_record_id = isset($data['flow_record_id']) && $data['flow_record_id'] ? intval($data['flow_record_id']) : 0;
        $unique_id = isset($data['unique_id']) && $data['unique_id'] ? trim($data['unique_id']) : '';

        $pay_type = isset($data['pay_type']) && $data['pay_type'] ? trim($data['pay_type']) : '';
        $pay_order_id = isset($data['pay_order_id']) && $data['pay_order_id'] ? intval($data['pay_order_id']) : 0;
        $total = isset($data['total']) && $data['total'] ? floatval($data['total']) : 0;
        $in_park_id = isset($data['in_park_id']) && $data['in_park_id'] ? intval($data['in_park_id']) : 0;
        $garage_id = isset($data['garage_id']) && $data['garage_id'] ? $data['garage_id'] : 0;
        $flow_direction = isset($data['flow_direction']) && $data['flow_direction'] ? $data['flow_direction'] : 2;
        $park_passage_id = isset($data['park_passage_id']) && $data['park_passage_id'] ? $data['park_passage_id'] : 0;
        $is_paid = isset($data['is_paid']) ? intval($data['is_paid']) : 1;
        $is_out = isset($data['is_out']) ? intval($data['is_out']) : 1;

        $isOutSubPark = isset($data['isOutSubPark']) ? $data['isOutSubPark'] : '-1';
        $isOutParentPark = isset($data['isOutParentPark']) ? $data['isOutParentPark'] : '-1';
        $notParentORSubPark = isset($data['notParentORSubPark']) ? $data['notParentORSubPark'] : '-1';

        $out_data = [
            'car_number' => $car_number,
            'in_time' => $in_time,
            'order_id' => $order_id,
            'out_time' => $out_time,
            'park_id' => $park_id,
            'park_sys_type' => $park_sys_type,
            'total' => $total,
            'pay_type' => $pay_type,
        ];
        $pay_order_id && $out_data['pay_order_id'] = $pay_order_id;
        $in_park_id && $out_data['in_park_id'] = $in_park_id;
        $in_image_big && $out_data['content_path'] = $in_image_big;
        !$in_image_big && $in_image_small && $out_data['content_path'] = $in_image_small;
        
        $startTime = $out_time - 60;
        $endTime = $out_time + 30;
        $park_out_where = [
            ['park_sys_type', '=', $park_sys_type],
            ['car_number', '=', $car_number],
            ['park_id', '=', $park_id],
            ['out_time', '>=', $startTime],
            ['out_time', '<=', $endTime],
            ['del_time', '=', 0],
        ];
        
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();

        $updateData = ['is_first' => 0];
        if ($flow_record_id || $unique_id) {
            $in_park_info = (new CarHandleGetInfoService())->getCarFlowInt($car_number, $village_id, $park_sys_type);
            $is_first = isset($in_park_info['is_first']) && $in_park_info['is_first'] ? $in_park_info['is_first'] : 0;
            $is_first == 1 && $updateData['is_first'] = 1;
            !$out_data['order_id'] && isset($in_park_info['order_id']) && $in_park_info['order_id'] && $out_data['order_id'] = trim($in_park_info['order_id']);
            !$out_data['in_time'] && isset($in_park_info['in_time']) && $in_park_info['in_time'] && $out_data['in_time'] = intval($in_park_info['in_time']);
            !isset($out_data['pay_order_id']) && isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] && $out_data['pay_order_id'] = intval($in_park_info['pay_order_id']);
            !isset($out_data['in_park_id']) && isset($in_park_info['id']) && $in_park_info['id'] && $out_data['in_park_id'] = $in_park_info['id'];
            if (!isset($in_park_info['is_first'])) {
                $park_count = $db_in_park->getCount(['car_number' => $car_number, 'park_id' => $park_id, 'del_time' => 0]);
                if ($park_count <= 1) {
                    $updateData['is_first'] = 1;
                }
            }
        }
        $from_id=0;
        $log = $car_number ? $car_number : 'car_numbe_log';
        /*
        $outParkCar = $db_out_park->getOne($park_out_where);
        if (isset($outParkCar['id']) && $outParkCar['id']) {
            // 1分钟左右重复出场 不做重复记录
            $from_id = $outParkCar['id'];
        } else {
            fdump_api(['msg' => '1-记录出场信息', 'out_data' => $out_data], 'addOutPark/'.$log, 1);
            $from_id = $db_out_park->insertOne($out_data);
        }
        */
        fdump_api(['msg' => '2-记录出场信息', 'data' => $printParams, 'from_id' => $from_id, 'park_out_where' => $park_out_where], 'addOutPark/'.$log, 1);
        // 将对应入场更改为已经出场
        $in_park_data = [];
        $in_park_data['out_time'] = $out_time;
        $in_park_data['is_out'] = 1;
        $in_park_data['is_paid'] = $is_paid;
        $in_image_big && $in_park_data['out_image_big'] = $in_image_big;
        $in_image_small && $in_park_data['out_image_small'] = $in_image_small;
        $pay_order_id && $in_park_data['pay_order_id'] = $pay_order_id;

        $db_park_passage = new ParkPassage();
        if ($isOutSubPark && $isOutSubPark != '-1') {
            $whereChannel = [];
            $whereChannel[] = ['village_id', '=', $village_id];
            $whereChannel[] = ['area_type', '=', 2];
            $whereChannel[] = ['passage_area|garage_id', '=', $garage_id];
            $in_channel_id_arr = $db_park_passage->getColumn($whereChannel, 'id');
            $whereInPark = [];
            $whereInPark[] = ['car_number', '=', $data['car_number']];
            $whereInPark[] = ['in_channel_id', 'in', $in_channel_id_arr];
            $whereInPark[] = ['park_id', '=', $village_id];
            $whereInPark[] = ['park_sys_type', '=', $park_sys_type];
            $whereInPark[] = ['is_out', '=', 0];
        } else {
            $whereInPark = ['car_number' => $car_number, 'park_id' => $village_id, 'park_sys_type' => $park_sys_type, 'is_out' => 0];
        }
        $db_in_park->saveOne($whereInPark, $in_park_data);
        
        fdump_api(['msg' => '将对应入场更改为已经出场', 'whereInPark' => $whereInPark, 'in_park_data' => $in_park_data], 'addOutPark/'.$log, 1);
        if ($flow_record_id) {
            $whereUnique = [ 'id' => $flow_record_id];
        } elseif ($unique_id) {
            $whereUnique = [ 'unique_id' => $unique_id];
        } else {
            $uniqueArr = [
                'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid,
            ];
            $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
            $whereUnique = [ 'unique_id' => $unique_id];
        }
        if (isset($whereUnique) && !empty($whereUnique)) {
            $updateData['from_id'] = $from_id;
            $updateData['order_id'] = $order_id;
            $updateData['is_out'] = 1;
            fdump_api(['msg' => '1-更改对应出场记录信息', 'whereUnique' => $whereUnique, 'updateData' => $updateData], 'addOutPark/'.$log, 1);
            $save = $db_house_village_car_flow_record->updateThis($whereUnique, $updateData);
            fdump_api(['msg' => '2-更改对应出场记录信息', 'save' => $save], 'addOutPark/'.$log, 1);
        }

        $isAddAccessRecord = isset($data['isAddAccessRecord']) && $data['isAddAccessRecord'] ? intval($data['isAddAccessRecord']) : 0;
        if ($isAddAccessRecord) {
            $data['accessType'] = 2;
            $data['from_id'] = $from_id;
            $this->addCarAccessRecord($data);
        } else {
            $imageFile = isset($data['imageFile']) ? trim($data['imageFile']) : '';
            $imageFragmentFile = isset($data['imageFragmentFile']) ? trim($data['imageFragmentFile']) : '';
            $imageToJob = [];
            // 处理图片 走队列
            $ymdhis = date('Ymdhis', $flow_time);
            if ($imageFile) {
                $savePathFile = $ymdhis . '_image_big_' . $car_number;
                $imageToJob['park_type'] = 'out_park';
                $from_id && $imageToJob['out_park_id'] = $from_id;
                $in_park_id && $imageToJob['in_park_id'] = $in_park_id;
                $imageToJob['serialno'] = $in_channel_id;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['in_time'] = $flow_time;
                $imageToJob['order_id'] = $order_id;
                $imageToJob['village_id'] = $park_id;
                $imageToJob['nowTime'] = $flow_time;
                $imageToJob['imageFile'] = $imageFile;
                $imageToJob['imageFileSaveFileName'] = $savePathFile;
            }
            if ($imageFragmentFile) {
                if (empty($imageToJob)) {
                    $imageToJob['park_type'] = 'out_park';
                    $from_id && $imageToJob['out_park_id'] = $from_id;
                    $in_park_id && $imageToJob['in_park_id'] = $in_park_id;
                    $imageToJob['serialno'] = $in_channel_id;
                    $imageToJob['car_number'] = $car_number;
                    $imageToJob['in_time'] = $flow_time;
                    $imageToJob['order_id'] = $order_id;
                    $imageToJob['village_id'] = $park_id;
                    $imageToJob['nowTime'] = $flow_time;
                }
                $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
                $imageToJob['imageFragmentFile'] = $imageFragmentFile;
                $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
            }
            if (empty($imageToJob)) {
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
        }
        return true;
    }
    
    /**
     * 更新[out_park]出场+[in_park]入场记录+更新[house_village_car_access_record]出场记录.
     */
    public function updateOutPark($data) {
        $operateType = isset($data['operateType']) && $data['operateType'] ? $data['operateType'] : '';
        if ($operateType != 'updateRecord') {
            return true;
        }
        $is_paid = isset($data['is_paid']) ? intval($data['is_paid']) : 1;
        $pay_time = isset($data['pay_time']) ? intval($data['pay_time']) : 0;
        $total = isset($data['total']) && $data['total'] ? $data['total'] : 0;
        $pay_type = isset($data['pay_type']) && $data['pay_type'] ? $data['pay_type'] : '';
        $out_park_id = isset($data['out_park_id']) && $data['out_park_id'] ? $data['out_park_id'] : 0;
        $village_id = isset($data['village_id']) && $data['village_id'] ? $data['village_id'] : 0;
        $park_id = isset($data['park_id']) && $data['park_id'] ? $data['park_id'] : $village_id;
        $out_park_passage_id = isset($data['out_park_passage_id']) && $data['out_park_passage_id'] ? $data['out_park_passage_id'] : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $car_type = isset($data['car_type']) && $data['car_type'] ? $data['car_type'] : '';
        $stored_balance = isset($data['stored_balance']) && $data['stored_balance'] ? $data['stored_balance'] : 0;
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $last_out_time = isset($data['last_out_time']) && $data['last_out_time'] ? $data['last_out_time'] : 0;
        $last_order_id = isset($data['last_order_id']) && $data['last_order_id'] ? $data['last_order_id'] : '';
        $log = $car_number ? $car_number : 'car_number_log';

        $db_out_park = new OutPark();
        $db_in_park = new InPark();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $park_info_car = [];
        $outParkInfo = [];
        /*
        if (! $out_park_id && ($last_order_id > 0 || $last_out_time > 0)) {
            $whereOut = [];
            $whereOut[] = ['car_number', '=', $car_number];
            $whereOut[] = ['park_id', '=', $park_id];
            $whereOut[] = ['park_sys_type', '=', $park_sys_type];
            if ($last_order_id) {
                $whereOut[] = ['order_id', '=', $last_order_id];
            } else {
                $whereOut[] = ['out_time', '=', $last_out_time];
            }
            $whereOut[] = ['del_time', '=', 0];
            $outParkInfo = $db_out_park->getOne($whereOut, 'id, order_id, in_park_id');
            if ($outParkInfo && !is_array($outParkInfo)) {
                $outParkInfo = $outParkInfo->toArray();
            }
            $out_park_id = isset($outParkInfo['id']) ? intval($outParkInfo['id']) : 0;
        }
        */
        
        if ($out_park_id > 0) {
            $whereOut = ['id' => $out_park_id];
            empty($outParkInfo) && $outParkInfo = $db_out_park->getOne($whereOut, 'id, order_id, in_park_id');
            if (isset($outParkInfo['id'])) {
                $outData = [
                    'total' => $total,
                    'pay_type' => $pay_type,
                ];
                $db_out_park->saveOne($whereOut, $outData);
                $order_id = isset($outParkInfo['order_id']) && $outParkInfo['order_id'] ? trim($outParkInfo['order_id']) : '';
                $in_park_id = isset($outParkInfo['in_park_id']) && $outParkInfo['in_park_id'] ? intval($outParkInfo['in_park_id']) : '';
                $whereIn = [];
                if ($in_park_id > 0) {
                    $whereIn = ['id' => $in_park_id, 'park_id' => $park_id];
                } elseif ($order_id) {
                    $whereIn = ['order_id' => $order_id, 'park_id' => $park_id];
                }
                if (!empty($whereIn)) {
                    $inData = [
                        'pay_type' => $pay_type,
                        'is_paid' => $is_paid,
                        'pay_time' => $pay_time,
                    ];
                    $db_in_park->saveOne($whereIn, $inData);
                }
            }
            $field = 'record_id, business_id, park_id';
            $park_info_car = $db_house_village_car_access_record->getOne(['from_id' => $out_park_id, 'accessType' => 2, 'del_time' => 0], $field);
            if ($park_info_car && !is_array($park_info_car)) {
                $park_info_car = $park_info_car->toArray();
            }
        }
        $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
        if ($record_id > 0) {
            $car_access_record = [
                'car_type' => $car_type,
                'pay_type' => $pay_type,
                'update_time' => time(),
                'is_out' => 1,
            ];
            $stored_balance && $car_access_record['stored_balance'] = $stored_balance;
            $db_house_village_car_access_record->saveOne(['record_id' => $record_id], $car_access_record);
            if (isset($car_access_record['stored_balance'])) {
                $province = mb_substr($car_number, 0, 1);
                $car_no = mb_substr($car_number, 1);
                $db_house_village_parking_car = new HouseVillageParkingCar();
                $whereCar = ['province' => $province, 'car_number' => $car_no, 'village_id' => $village_id];
                fdump_api(['msg' => '更改车辆储值余额', 'whereCar' => $whereCar, 'stored_balance' => $car_access_record['stored_balance']], 'updateCarAccessRecord/'.$log, 1);
                $db_house_village_parking_car->editHouseVillageParkingCar($whereCar, ['stored_balance' => $car_access_record['stored_balance']]);
            }
            $whereOther = [];
            $whereOther[] = ['record_id', '<=', $record_id];
            $whereOther[] = ['car_number', '=', $car_number];
            $whereOther[] = ['park_sys_type', '=', $park_sys_type];
            $whereOther[] = ['is_out', '=', 0];
            $whereOther[] = ['business_id', '=', $park_info_car['business_id']];
            $whereOther[] = ['park_id', '=', $park_info_car['park_id']];
            $db_house_village_car_access_record->saveOne($whereOther, ['is_out' => 1]);
        }
        return true;
    }
    
    /**
     * 添加[house_village_car_access_record]出入场记录.
     */
    public function addCarAccessRecord($data) {
        $imageFile = isset($data['imageFile']) ? trim($data['imageFile']) : '';
        $imageFragmentFile = isset($data['imageFragmentFile']) ? trim($data['imageFragmentFile']) : '';
        $data = unsetParam($data, ['imageFile', 'AlarmInfoPlate']);
        fdump_api(['msg' => '添加[house_village_car_access_record]出入场记录-方法入参', 'parameter' => ['data' => $data], 'line' => __LINE__], 'parkBetter/addCarAccessRecordLog', 1);
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $flow_time = isset($data['flow_time']) && $data['flow_time'] ? intval($data['flow_time']) : time();
        $accessType = isset($data['accessType']) && $data['accessType'] ? intval($data['accessType']) : 1;
        if ($accessType == 2) {
            $accessTime = isset($data['out_time']) && $data['out_time'] ? intval($data['out_time']) : $flow_time;
        } else {
            $accessTime = isset($data['in_time']) && $data['in_time'] ? intval($data['in_time']) : $flow_time;
        }
        $order_id = isset($data['order_id']) && $data['order_id'] ? trim($data['order_id']) : '';
        $record_order_id = isset($data['record_order_id']) && $data['record_order_id'] ? trim($data['record_order_id']) : $order_id;
        $in_channel_id = isset($data['in_channel_id']) && $data['in_channel_id'] ? intval($data['in_channel_id']) : 0;
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_id = isset($data['park_id']) && $data['park_id'] ? intval($data['park_id']) : $village_id;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $park_name = isset($data['park_name']) && $data['park_name'] ? trim($data['park_name']) : '';
        $in_image_big = isset($data['in_image_big']) && $data['in_image_big'] ? trim($data['in_image_big']) : '';
        $in_image_small = isset($data['in_image_small']) && $data['in_image_small'] ? trim($data['in_image_small']) : '';
        $channel_number = isset($data['channel_number']) && $data['channel_number'] ? trim($data['channel_number']) : '';
        $passage_name = isset($data['passage_name']) && $data['passage_name'] ? trim($data['passage_name']) : '';
        $accessMode = isset($data['accessMode']) ? intval($data['accessMode']) : 8;
        $total = isset($data['total']) && $data['total'] ? $data['total'] : 0;
        $pay_type = isset($data['pay_type']) && $data['pay_type'] ? trim($data['pay_type']) : '';
        $trade_no = isset($data['trade_no']) && $data['trade_no'] ? trim($data['trade_no']) : '';
        $pay_order_id = isset($data['pay_order_id']) ? intval($data['pay_order_id']) : 0;
        $exception_type = isset($data['exception_type']) ? intval($data['exception_type']) : 0;
        $park_car_type = isset($data['park_car_type']) && $data['park_car_type'] ? trim($data['park_car_type']) : '';
        $optname = isset($data['optname']) && $data['optname'] ? trim($data['optname']) : '';
        
        $coupon_id = isset($data['coupon_id']) && $data['coupon_id'] ? intval($data['coupon_id']) : 0;
        $is_paid = isset($data['is_paid']) ? intval($data['is_paid']) : 0;
        $pay_time = isset($data['pay_time'])  ? intval($data['pay_time']) : 0;
        $stored_balance = isset($data['stored_balance']) && $data['stored_balance'] ? $data['stored_balance'] : 0;
        $car_type = isset($data['car_type']) && $data['car_type'] ? trim($data['car_type']) : '';
        $prepayTotal = isset($data['prepayTotal']) && $data['prepayTotal'] ? $data['prepayTotal'] : 0;
        
        $open_type = isset($data['open_type']) ? intval($data['open_type']) : 0;
        $park_time = isset($data['park_time']) ? intval($data['park_time']) : 0;
        $car_number_type = isset($data['car_number_type']) && $data['car_number_type'] ? trim($data['car_number_type']) : '';
        $flow_temp_id = isset($data['flow_temp_id']) ? intval($data['flow_temp_id']) : 0;
        
        $from_id = isset($data['from_id']) ? intval($data['from_id']) : 0;
        $in_park_id = isset($data['in_park_id']) && $data['in_park_id'] ? intval($data['in_park_id']) : 0;
        $serialno = isset($data['serialno']) && $data['serialno'] ? trim($data['serialno']) : '';
        $flow_record_id = isset($data['flow_record_id']) && $data['flow_record_id'] ? intval($data['flow_record_id']) : 0;
        $unique_id = isset($data['unique_id']) && $data['unique_id'] ? trim($data['unique_id']) : '';
        // 车辆进出方向 1-进 2-出
        if ($accessType == 2) {
            $flow_direction = 2;
        } elseif ($accessType == 1) {
            $flow_direction = 1;
        }
        $park_passage_id = isset($data['park_passage_id']) && $data['park_passage_id'] ? $data['park_passage_id'] : 0;
        if (isset($data['is_paid'])) {
            $is_paid = intval($data['is_paid']);
        } else {
            $is_paid = $accessType == 2 ? 1 : 0;
        }
        if (isset($data['is_out'])) {
            $is_out = intval($data['is_out']);
        } else {
            $is_out = $accessType == 2 ? 1 : 0;
        }
        
        $imageToJob = [];
        // 处理图片 走队列
        $ymdhis = date('Ymdhis', $flow_time);
        if ($imageFile) {
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            if ($accessType == 2) {
                $imageToJob['park_type'] = 'out_park';
                $from_id && $imageToJob['out_park_id'] = $from_id;
                $in_park_id && $imageToJob['in_park_id'] = $in_park_id;
            } else {
                $imageToJob['park_type'] = 'in_park';
                $from_id && $imageToJob['in_park_id'] = $from_id;
            }
            $imageToJob['serialno'] = $in_channel_id;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['in_time'] = $flow_time;
            $imageToJob['order_id'] = $order_id;
            $imageToJob['village_id'] = $park_id;
            $imageToJob['nowTime'] = $flow_time;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
        }
        if ($imageFragmentFile) {
            if (empty($imageToJob)) {
                if ($accessType == 2) {
                    $imageToJob['park_type'] = 'out_park';
                    $from_id && $imageToJob['out_park_id'] = $from_id;
                    $in_park_id && $imageToJob['in_park_id'] = $in_park_id;
                } else {
                    $imageToJob['park_type'] = 'in_park';
                    $from_id && $imageToJob['in_park_id'] = $from_id;
                }
                $imageToJob['serialno'] = $in_channel_id;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['in_time'] = $flow_time;
                $imageToJob['order_id'] = $order_id;
                $imageToJob['village_id'] = $park_id;
                $imageToJob['nowTime'] = $flow_time;
            }
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
        }
        
        !$village_id && $park_id && $village_id = $park_id;
        $houseVillageCarFlowService = new HouseVillageCarFlowService();
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $car_info = $carHandleGetInfoService->getCarInfo($car_number, $village_id);
        $parking_car_type = isset($car_info['parking_car_type']) && $car_info['parking_car_type'] ? intval($car_info['parking_car_type']) : 0;
        $serviceA11 = new A11Service();
        $parking_a11_car_type_arr = $serviceA11->parking_a11_car_type_arr;
        if (!$parking_car_type && $park_sys_type == 'A11') {
            $db_house_village_park_charge_cartype = new HouseVillageParkChargeCartype();
            $whereCarType = ['village_id' => $village_id, 'car_number_type' => $car_number_type, 'status' => 1, 'park_sys_type' => $park_sys_type];
            $carTypeInfo = $db_house_village_park_charge_cartype->getFind($whereCarType, 'car_type');
            if (isset($carTypeInfo['car_type']) && intval($carTypeInfo['car_type']) > 0) {
                $parking_car_type = intval($carTypeInfo['car_type']);
            } else {
                $parking_car_type = $serviceA11::Temp_A;
            }
        }
        $parking_car_type > 0 && $park_car_type = $parking_a11_car_type_arr[$parking_car_type];

        $user_name = isset($car_info['car_user_name']) && $car_info['car_user_name'] ? trim($car_info['car_user_name']) : '';
        $user_phone = isset($car_info['car_user_phone']) && $car_info['car_user_phone'] ? trim($car_info['car_user_phone']) : '';

        $uid = isset($car_info['uid']) && $car_info['uid'] ? intval($car_info['uid']) : 0;
        $bind_id = 0;
        if (!$user_name && !$user_phone) {
            $carVisitorInfo = $carHandleGetInfoService->getCarVisitorInfo($car_number, $village_id);
            $user_name = isset($carVisitorInfo['visitor_name']) && $carVisitorInfo['visitor_name'] ? trim($carVisitorInfo['visitor_name']) : '';
            $user_phone = isset($carVisitorInfo['visitor_phone']) && $carVisitorInfo['visitor_phone'] ? trim($carVisitorInfo['visitor_phone']) : '';
            $uid = isset($carVisitorInfo['uid']) && $carVisitorInfo['uid'] ? intval($carVisitorInfo['uid']) : 0;
        }
        if (isset($car_info['car_id']) && intval($car_info['car_id']) > 0) {
            $db_house_village_bind_car = new HouseVillageBindCar();
            $bind_info = $db_house_village_bind_car->getFind(['car_id' => $car_info['car_id'], 'village_id' => $village_id]);
            !$uid && $uid = $bind_info && isset($bind_info['uid']) && $bind_info['uid'] ? $bind_info['uid'] : 0;
            !$bind_id && $bind_id = $bind_info && isset($bind_info['user_id']) && $bind_info['user_id'] ? $bind_info['user_id'] : 0;
        }
        if ((!$user_name || !$user_phone) && $uid > 0) {
            $dbUser = new User();
            $whereUser = [];
            $whereUser[] = ['uid', '=', $uid];
            $userInfo = $dbUser->getOne($whereUser, 'uid, phone, nickname, real_name');
            $user_phone = isset($userInfo['phone']) ? trim($userInfo['phone']) : '';
            $nickname = isset($userInfo['nickname']) ? trim($userInfo['nickname']) : '';
            $real_name = isset($userInfo['real_name']) ? trim($userInfo['real_name']) : '';
            $user_name = $real_name ? $real_name : $nickname;
        }

        $position_id = isset($data['position_id']) && $data['position_id'] ? intval($data['position_id']) : 0;
        $garage_id = isset($data['garage_id']) && $data['garage_id'] ? intval($data['garage_id']) : 0;
        $position_num = isset($data['position_num']) && $data['position_num'] ? trim($data['position_num']) : '';
        $car_id = isset($data['car_id']) && $data['car_id'] ? intval($data['car_id']) : 0;
        $car_position_id = isset($data['car_position_id']) && $data['car_position_id'] ? intval($data['car_position_id']) : 0;
        
        $car_access_record = [];
        $car_access_record['channel_id'] = $in_channel_id;
        $car_access_record['channel_number'] = $channel_number;
        $car_access_record['channel_name'] = $passage_name;
        if ($user_name) {
            $car_access_record['user_name'] = $user_name;
        }
        if ($user_phone) {
            $car_access_record['user_phone'] = $user_phone;
        }
        if (intval($uid) > 0) {
            $car_access_record['uid'] = $uid;
        }
        if (intval($bind_id) > 0) {
            $car_access_record['bind_id'] = $bind_id;
        }
        $optname && $car_access_record['optname'] = $optname;
        if ($position_id > 0) {
            $car_access_record['position_id'] = $position_id;
        } elseif ($car_position_id > 0) {
            $car_access_record['position_id'] = $car_position_id;
        }
        $garage_id > 0 && $car_access_record['garage_id'] = $garage_id;
        $position_num && $car_access_record['position_num'] = $position_num;
        $car_id > 0 && $car_access_record['car_id'] = $car_id;
        $log = $car_number ? $car_number : 'car_number_log';
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $startTime = $accessTime - 60;
        $endTime = $accessTime + 30;
        $whereAccessRecord = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $park_id],
            ['accessType', '=', $accessType],
            ['park_sys_type', '=', $park_sys_type],
            ['accessTime', '>=', $startTime],
            ['accessTime', '<=', $endTime],
            ['del_time', '=', 0],
        ];
        $in_record_info = $db_house_village_car_access_record->getOne($whereAccessRecord);
        if ($in_record_info && !is_array($in_record_info)) {
            $in_record_info = $in_record_info->toArray();
        }
        if (empty($in_record_info) || !isset($in_record_info['record_id']) || !$in_record_info['record_id']) {
            if ($accessType == 2) {
                $access_record_where_near = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $park_id],
                    ['accessType', '=', 1],
                    ['is_out', '=', 0],
                    ['accessTime', '<', $accessTime],
                    ['del_time', '=', 0],
                ];
                $park_info_car = $db_house_village_car_access_record->getOne($access_record_where_near);
                if ($park_info_car && isset($park_info_car['record_id']) && $park_info_car['record_id']) {
                    $park_where_save = $access_record_where_near;
//                    $park_where_save[] = ['exception_type', '=', 0];
                    $db_house_village_car_access_record->saveOne($park_where_save, ['is_out' => 1]);
                    !$pay_order_id && isset($park_info_car['pay_order_id']) && $park_info_car['pay_order_id'] && $pay_order_id = intval($park_info_car['pay_order_id']);
                    !isset($car_access_record['user_name']) && $car_access_record['user_name'] = isset($park_info_car['user_name']) && $park_info_car['user_name'] ? trim($park_info_car['user_name']) : '';
                    !isset($car_access_record['user_phone']) && $car_access_record['user_phone'] = isset($park_info_car['user_phone']) && $park_info_car['user_phone'] ? trim($park_info_car['user_phone']) : '';
                    !isset($car_access_record['uid']) && $car_access_record['uid'] = isset($park_info_car['uid']) && $park_info_car['uid'] ? intval($park_info_car['uid']) : 0;
                } else {
                    !$exception_type && $exception_type = 2;
                    $park_info_car['accessTime'] = 0;
                }
                if (isset($park_info_car['order_id']) && $park_info_car['order_id']) {
                    $record_order_id = $park_info_car['order_id'];
                }
                $park_time <= 0 && $park_time = $accessTime - $park_info_car['accessTime'];
                $car_access_record['park_time'] = $park_time;
                $car_access_record['is_out'] = 1;
                $car_access_record['total'] = $total;
                $car_access_record['pay_type'] = $pay_type;
                !$trade_no && $trade_no = time() . rand(10, 99) . sprintf("%08d", $village_id);
                $car_access_record['trade_no'] = $trade_no;
                $is_paid && $car_access_record['is_paid'] = $is_paid;
                $pay_time && $car_access_record['pay_time'] = $pay_time;
                $stored_balance && $car_access_record['stored_balance'] = $stored_balance;
                $car_type && $car_access_record['car_type'] = $car_type;
                $prepayTotal && $car_access_record['prepayTotal'] = $prepayTotal;
            } else {
                //写入车辆入场表
                if (!$record_order_id && $serialno && $car_number && $flow_time) {
                    $record_order_id = md5($serialno . $car_number . $flow_time);
                } elseif (!$record_order_id) {
                    $record_order_id = date('YmdHis') . rand(100, 999);
                }
                $car_access_record['is_out'] = 0;
            }
            $park_car_type && $car_access_record['park_car_type'] = $park_car_type;
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $village_id;
            $car_access_record['car_number'] = $car_number;
            $car_access_record['accessType'] = $accessType;
            $car_access_record['accessTime'] = $accessTime;
            $car_access_record['accessMode'] = $accessMode;
            $car_access_record['park_sys_type'] = $park_sys_type;
            $car_access_record['park_id'] = $park_id;
            $car_access_record['park_name'] = $park_name;
            $car_access_record['order_id'] = $record_order_id;
            $car_access_record['update_time'] = $flow_time;
            $car_access_record['exception_type'] = $exception_type;
            $coupon_id && $car_access_record['coupon_id'] = $coupon_id ;
            if (isset($data['from_id']) && $data['from_id']) {
                $car_access_record['from_id'] = $data['from_id'];
            }
            if ($in_image_small) {
                $car_access_record['accessImage'] = $in_image_small;
            }
            if ($in_image_big) {
                $car_access_record['accessBigImage'] = $in_image_big;
            }
            if ($pay_order_id > 0) {
                $car_access_record['pay_order_id'] = $pay_order_id;
            }
            fdump_api(['msg' => '1-记录出入场信息', 'data' => $data, 'car_access_record' => $car_access_record], 'addCarAccessRecord/'.$log, 1);
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            if ($record_id > 0 && isset($car_access_record['stored_balance'])) {
                $province = mb_substr($car_number, 0, 1);
                $car_no = mb_substr($car_number, 1);
                $db_house_village_parking_car = new HouseVillageParkingCar();
                $whereCar = ['province' => $province, 'car_number' => $car_no, 'village_id' => $village_id];
                fdump_api(['msg' => '更改车辆储值余额', 'whereCar' => $whereCar, 'stored_balance' => $car_access_record['stored_balance']], 'addCarAccessRecord/'.$log, 1);
                $db_house_village_parking_car->editHouseVillageParkingCar($whereCar, ['stored_balance' => $car_access_record['stored_balance']]);
            }
            fdump_api(['msg' => '2-记录出入场信息', 'record_id' => $record_id], 'addCarAccessRecord/'.$log, 1);
            if ($record_id > 0 && $accessType == 2) {
                if ($park_sys_type == 'A11') {
                    // 判断是否为子停车场通道
                    $sHouseVillageCarFlowService = new HouseVillageCarFlowService();
                    $sCarHandleGetInfoService = new CarHandleGetInfoService();
                    $passageInfo = $sCarHandleGetInfoService->getParkPassageInfoById($in_channel_id, $village_id);
                    $garage_id = isset($passageInfo['garage_id']) ? intval($passageInfo['garage_id']) : 0;
                    $garageInfo = $sCarHandleGetInfoService->getParkingGarage($garage_id, $village_id);
                    $garage_fid = isset($garageInfo['fid']) ? intval($garageInfo['fid']) : 0;
                    if ($garage_fid > 0) {
                        // todo 当前通过的是小场 出场只能将相同小场下入场记录出场
                        $db_house_village_parking_garage = new HouseVillageParkingGarage();
                        $db_park_passage = new ParkPassage();
                        $whereColumn = [];
                        $whereColumn[] = ['village_id', '=', $village_id];
                        $whereColumn[] = ['fid', '=', $garage_fid];
                        $whereColumn[] = ['status', '=', 1];
                        $garageIds = $db_house_village_parking_garage->getColumn($whereColumn, 'garage_id');
                        if (!empty($garageIds)) {
                            $whereParkPassage = [];
                            $whereParkPassage[] = ['village_id', '=', $village_id];
                            $whereParkPassage[] = ['status', '=', 1];
                            $whereParkPassage[] = ['id', '<>', $in_channel_id];
                            $whereParkPassage[] = ['garage_id', 'in', $garageIds];
                            $parkPassageId1s = $db_park_passage->getColumn($whereParkPassage, 'id');
                            $whereParkPassage = [];
                            $whereParkPassage[] = ['village_id', '=', $village_id];
                            $whereParkPassage[] = ['status', '=', 1];
                            $whereParkPassage[] = ['id', '<>', $in_channel_id];
                            $whereParkPassage[] = ['area_type', '=', 2];
                            $whereParkPassage[] = ['passage_area', 'in', $garageIds];
                            $parkPassageId2s = $db_park_passage->getColumn($whereParkPassage, 'id');
                            $parkPassageIds = array_unique(array_merge($parkPassageId1s, $parkPassageId2s));
                        } else {
                            $parkPassageIds = [];
                        }
                        if (!empty($parkPassageIds)) {
                            $access_record_where_near = [
                                ['car_number', '=', $car_number], ['park_id', '=', $village_id], ['accessType', '=', 1],
                                ['channel_id', 'in', $parkPassageIds], ['is_out', '=', 0], ['del_time', '=', 0], ['record_id', '<', $record_id],
                            ];
                            $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                        }
                    } else {
                        // todo 当前通过的是大场或者无大小场
                        $access_record_where_near = [
                            ['car_number', '=', $car_number], ['park_id', '=', $village_id], ['accessType', '=', 1],
                            ['is_out', '=', 0], ['del_time', '=', 0], ['record_id', '<', $record_id],
                        ];
                        $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                    }
                    
                } else {
                    $access_record_where_near = [
                        ['car_number', '=', $car_number], ['park_id', '=', $village_id], ['accessType', '=', 1],
                        ['is_out', '=', 0], ['del_time', '=', 0], ['record_id', '<', $record_id],
                    ];
                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                }
            }
        } else {
            $record_id = $in_record_info['record_id'];
            fdump_api(['msg' => '1分钟内有重复出入记录', 'data' => $data, 'in_record_info' => $in_record_info], 'addCarAccessRecord/'.$log, 1);
        }
        if ($record_id > 0 &&empty($imageToJob)) {
            if (isset($record_order_id) && $record_order_id) {
                $imageToJob['car_access_order_id'] = $record_order_id;
            }
            if ($accessType == 2) {
                $imageToJob['out_record_id'] = $record_id;
            } else {
                $imageToJob['in_record_id'] = $record_id;
            }
            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            if(isset($data['isRightPosition']) && intval($data['isRightPosition']) == 1 && $car_id > 0 && $position_id > 0){
                if ($accessType == 1) {
                    // 入场占用车位
                    $whereArr = array('position_id' => $position_id);
                    $saveArr = array('use_car_id' => $car_id, 'use_start_time' => $accessTime, 'use_car_number' => $car_number, 'access_record_id' => $record_id);
                    $jobParams = [
                        'jobType' => 'handleUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car_id,
                        'nowTime' => $accessTime, 'position_id' => $position_id, 'car_number' => $car_number,
                        'where' => $whereArr, 'updateData' => $saveArr, 'access_record_id' => $record_id,
                    ];
                } else {
                    // 出场释放车位
                    $whereArr = array('position_id' => $position_id, 'use_car_id'=> $car_id);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                    $jobParams = [
                        'jobType' => 'handleUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car_id,
                        'nowTime' => $accessTime, 'position_id' => $position_id, 'car_number' => $car_number,
                        'where' => $whereArr, 'updateData' => $saveArr, 'access_record_id' => $record_id,
                    ];
                }
                (new ParkCarToJobService())->HandleUseParkingPositionToJob($jobParams);
            }
        }
        if ($flow_temp_id > 0 && $record_id > 0) {
            $data_temp = [];
            if ($accessType == 2) {
                $data_temp['out_record_id'] = $record_id;
            }
            if ($accessType == 1) {
                $data_temp['in_record_id'] = $record_id;
            }
            $data_temp['update_time'] = $record_id;
            $dbHouseVillageCarFlowTemp = new HouseVillageCarFlowTemp();
            $dbHouseVillageCarFlowTemp->updateThis(['id' => $flow_temp_id], $data_temp);
        }
        if ($record_id > 0) {
            if ($flow_record_id) {
                $whereUnique = [ 'id' => $flow_record_id];
            } elseif ($unique_id) {
                $whereUnique = [ 'unique_id' => $unique_id];
            } else {
                $uniqueArr = [
                    'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
                    'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid,
                ];
                $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
                $whereUnique = [ 'unique_id' => $unique_id];
            }
            if (isset($whereUnique) && !empty($whereUnique)) {
                $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
                $updateData = [];
                $updateData['access_record_id'] = $record_id;
                fdump_api(['msg' => '1-更改对应出场记录信息', 'whereUnique' => $whereUnique, 'updateData' => $updateData], 'addOutPark/'.$log, 1);
                $save = $db_house_village_car_flow_record->updateThis($whereUnique, $updateData);
                fdump_api(['msg' => '2-更改对应出场记录信息', 'save' => $save], 'addOutPark/'.$log, 1);
            }
        }
        return true;
    }

    /**
     * 添加[park_plateresult_log]识别记录.
     */
    public function plateResultLog($data) {
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $channel_id = isset($data['channel_id']) && $data['channel_id'] ? trim($data['channel_id']) : '';
        $park_type = isset($data['park_type']) && $data['park_type'] ? intval($data['park_type']) : 0;
        $add_time = isset($data['add_time']) && $data['add_time'] ? intval($data['add_time']) : time();
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : 'D3';
        $park_passage_id = isset($data['park_passage_id']) && $data['park_passage_id'] ? intval($data['park_passage_id']) : 0;
        $garage_id = isset($data['garage_id']) && $data['garage_id'] ? intval($data['garage_id']) : 0;
        $car_number_type = isset($data['car_number_type']) && intval($data['car_number_type']) > 0 ? intval($data['car_number_type']) : 0;
        
        // 记录一条相当于相同小区同一个车辆的进出唯一识别记录
        $dbHouseVillageCarFlowIdentify = new HouseVillageCarFlowIdentify();
        $identifyData = [
            'car_number' => $car_number, 'park_passage_id' => $park_passage_id, 'device_number' => $channel_id,
            'flow_direction' => $park_type, 'flow_time' => $add_time, 'garage_id' => $garage_id,
            'village_id' => $village_id, 'park_sys_type' => $park_sys_type,
        ];
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => $park_type, 'park_sys_type' => $park_sys_type,
        ];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereIdentifyUnique = [
            'unique_id' => $unique_id,
        ];
        $identifyData['unique_id'] = $unique_id;
        $identifyRecord = $dbHouseVillageCarFlowIdentify->getOne($whereIdentifyUnique, 'id');
        if (isset($identifyRecord['id']) && $identifyRecord['id']) {
            $identifyData['update_time'] = time();
            $dbHouseVillageCarFlowIdentify->updateThis($whereIdentifyUnique, $identifyData);
        } else {
            $identifyData['add_time'] = time();
            $dbHouseVillageCarFlowIdentify->add($identifyData);
        }
        
        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $channel_id;
        $park_log_data['park_type'] = $park_type;
        $park_log_data['add_time'] = $add_time;
        $park_log_data['village_id'] = $village_id;
        $park_log_data['park_sys_type'] = $park_sys_type;
        $park_log_data['car_number_type'] = $car_number_type;
        $whereUnique = [];
        $whereUnique[] = ['car_number', '=', $car_number];
        $whereUnique[] = ['channel_id', '=', $channel_id];
        $whereUnique[] = ['park_type', '=', $park_type];
        $whereUnique[] = ['add_time', '=', $add_time];
        $whereUnique[] = ['park_sys_type', '=', $park_sys_type];
        $db_park_plateresult_log = new ParkPlateresultLog();
        $log = $db_park_plateresult_log->get_one($whereUnique);
        if (isset($log['id']) && $log['id']) {
            // 重复的不再添加
            return $log['id'];
        }
        return $db_park_plateresult_log->add($park_log_data);
    }
    
    /**
     * 更新[house_village_park_coupon]对应记录为未使用.
     */
    public function updateCouponToNotUsed($data) {
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $id = isset($data['id']) && $data['id'] ? intval($data['id']) : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : '';

        $db_house_village_park_coupon = new HouseVillageParkCoupon();
        $where_coupon = [];
        $where_coupon['village_id'] = $village_id;
        $where_coupon['car_number'] = $car_number;
        if ($park_sys_type) {
            $where_coupon['park_sys_type'] = $park_sys_type;
        }
        $where_coupon['id'] = $id;
        $saveArr = array('is_use' => 0, 'use_time' => 0, 'use_txt' => '');
        fdump_api(['msg' => '更改优惠券', 'line' => __LINE__, 'where_coupon' => $where_coupon, 'saveArr' => $saveArr], 'CarFlowJob/updateCouponToNotUsed', true);
        $db_house_village_park_coupon->saveOne($where_coupon, $saveArr);
        return true;
    }
    
    /**
     * 更新[house_village_park_coupon]对应记录为已使用.
     */
    public function updateCouponToUsed($data) {
        $car_number = isset($data['car_number']) && $data['car_number'] ? trim($data['car_number']) : '';
        $village_id = isset($data['village_id']) && $data['village_id'] ? intval($data['village_id']) : 0;
        $id = isset($data['id']) && $data['id'] ? intval($data['id']) : 0;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? trim($data['park_sys_type']) : '';
        $use_time = isset($data['use_time']) && $data['use_time'] ? intval($data['use_time']) : time();
        $use_txt = isset($data['use_txt']) && $data['use_txt'] ? trim($data['use_txt']) : '';

        $db_house_village_park_coupon = new HouseVillageParkCoupon();
        $where_coupon = [];
        $where_coupon['village_id'] = $village_id;
        $where_coupon['car_number'] = $car_number;
        if ($park_sys_type) {
            $where_coupon['park_sys_type'] = $park_sys_type;
        }
        $where_coupon['id'] = $id;
        $saveArr = array('is_use' => 1, 'use_time' => $use_time, 'use_txt' => $use_txt);
        fdump_api(['msg' => '更改优惠券', 'line' => __LINE__, 'where_coupon' => $where_coupon, 'saveArr' => $saveArr], 'CarFlowJob/updateCouponToUsed', true);
        $db_house_village_park_coupon->saveOne($where_coupon, $saveArr);
        return true;
    }

    /**
     * 添加临时记录.
     */
    public function addDataTemp($data) {
        $car_number = isset($data['car_number']) && $data['car_number'] ? $data['car_number'] : '';
        $village_id = isset($data['village_id']) && $data['village_id'] ? $data['village_id'] : 0;
        $free_out_time = isset($data['free_out_time']) && $data['free_out_time'] ? $data['free_out_time'] : 0;
        $add_time = isset($data['add_time']) && $data['add_time'] ? $data['add_time'] : time();
        $duration = isset($data['duration']) && $data['duration'] ? $data['duration'] : 0;
        $derate_money = isset($data['derate_money']) && $data['derate_money'] ? $data['derate_money'] : 0;
        $derate_duration = isset($data['derate_duration']) && $data['derate_duration'] ? $data['derate_duration'] : 0;
        $order_id = isset($data['order_id']) && $data['order_id'] ? $data['order_id'] : 0;
        $query_order_no = isset($data['query_order_no']) && $data['query_order_no'] ? $data['query_order_no'] : 0;
        $errmsg = isset($data['errmsg']) && $data['errmsg'] ? $data['errmsg'] : '';
        $price = isset($data['price']) && $data['price'] ? $data['price'] : 0;
        $total = isset($data['total']) && $data['total'] ? $data['total'] : 0;
        $in_time = isset($data['in_time']) && $data['in_time'] ? $data['in_time'] : 0;
        $out_channel_id = isset($data['out_channel_id']) && $data['out_channel_id'] ? $data['out_channel_id'] : '';
        $is_pay_scene = isset($data['is_pay_scene']) && $data['is_pay_scene'] ? $data['is_pay_scene'] : 1;
        $park_sys_type = isset($data['park_sys_type']) && $data['park_sys_type'] ? $data['park_sys_type'] : 'D3';
        $coupon_id = isset($data['coupon_id']) && $data['coupon_id'] ? $data['coupon_id'] : 0;
        $is_paid = isset($data['is_paid']) && $data['is_paid'] ? $data['is_paid'] : 0;
        $pay_time = isset($data['pay_time']) ? intval($data['pay_time']) : 0;
        $in_record_id = isset($data['in_record_id']) && $data['in_record_id'] ? $data['in_record_id'] : 0;
        $flow_temp_id = isset($data['flow_temp_id']) && $data['flow_temp_id'] ? $data['flow_temp_id'] : 0;
        $car_type = isset($data['car_type']) && $data['car_type'] ? $data['car_type'] : '';
        $park_charge = isset($data['park_charge']) && $data['park_charge'] ? $data['park_charge'] : '';

        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $data_temp = [
            'car_number' => $car_number, 'village_id' => $village_id, 'free_out_time' => $free_out_time, 'add_time' => $add_time,
            'duration' => $duration, 'derate_money' => $derate_money, 'derate_duration' => $derate_duration, 'order_id' => $order_id,
            'query_order_no' => $query_order_no, 'errmsg' => $errmsg, 'price' => $price, 'total' => $total, 'in_time' => $in_time,
            'out_channel_id' => $out_channel_id, 'is_pay_scene' => $is_pay_scene, 'park_sys_type' => $park_sys_type, 'coupon_id' => $coupon_id,
            'is_paid' => $is_paid, 'pay_time' => $pay_time, 'in_record_id' => $in_record_id,
        ];
        $car_type && $data_temp['car_type'] = $car_type;
        $park_charge && $data_temp['park_charge'] = $park_charge;
        $parking_temp_id =  $db_house_village_park_temp->addOne($data_temp);
        $dbHouseVillageCarFlowTemp = new HouseVillageCarFlowTemp();
        if ($flow_temp_id > 0) {
            $whereUnique = [
                'id' => $flow_temp_id,
            ];
        } else {
            $hasCoupon = false;
            if ($coupon_id > 0) {
                $hasCoupon = true;
            }
            $uniqueArr = [
                'car_number' => $car_number, 'village_id' => $village_id, 'is_paid' => $is_paid, 'is_pay_scene' => $is_pay_scene,
                'out_channel_id' => $out_channel_id, 'park_sys_type' => $park_sys_type, 'hasCoupon' => $hasCoupon,
            ];
            $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
            $whereUnique = [
                'unique_id' => $unique_id,
            ];
        }
        $flowTempData = [
            'parking_temp_id' => $parking_temp_id
        ];
        $flowTempData['update_time'] = time();
        $dbHouseVillageCarFlowTemp->updateThis($whereUnique, $flowTempData);
        return true;
    }

    
    /**
     * 清理超出一定时间的开门指令.
     */
    public function clearOverParkOpen($data) {
        $openLimitTime = isset($data['openLimitTime']) && $data['openLimitTime'] ? intval($data['openLimitTime']) : 0;
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? intval($data['nowTime']) : time();
        $limitTime = isset($data['limitTime']) && $data['limitTime'] ? intval($data['limitTime']) : CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME;
        if ($openLimitTime <= 0) {
            $openLimitTime = $nowTime - $limitTime;
        }
        if ($openLimitTime <= 0) {
            return true;
        }
        $db_park_open_log = new ParkOpenLog();
        $where = [];
        $where[] = ['add_time', '<=', $openLimitTime];
        return $db_park_open_log->delOne($where);
    }
    
    /**
     * 清理超出一定时间的支付待查询记录.
     */
    public function clearOverPayRecord($data) {
        $payRecordLimitTime = isset($data['payRecordLimitTime']) && $data['payRecordLimitTime'] ? intval($data['payRecordLimitTime']) : 0;
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? intval($data['nowTime']) : time();
        $limitTime = isset($data['limitTime']) && $data['limitTime'] ? intval($data['limitTime']) : CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME;
        if ($payRecordLimitTime <= 0) {
            $payRecordLimitTime = $nowTime - $limitTime;
        }
        if ($payRecordLimitTime <= 0) {
            return true;
        }
        $db_park_scrcu_record = new ParkScrcuRecord();
        $where = [];
        $where[] = ['add_time', '<=', $payRecordLimitTime];
        return $db_park_scrcu_record->delOne($where);
    }

    public function clearOverParkSerialLog($data)
    {
        $parkSerialLogLimitTime = isset($data['parkSerialLogLimitTime']) && $data['parkSerialLogLimitTime'] ? intval($data['parkSerialLogLimitTime']) : 0;
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? intval($data['nowTime']) : time();
        $limitTime = isset($data['limitTime']) && $data['limitTime'] ? intval($data['limitTime']) : CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME;
        if ($parkSerialLogLimitTime <= 0) {
            $parkSerialLogLimitTime = $nowTime - $limitTime;
        }
        if ($parkSerialLogLimitTime <= 0) {
            return true;
        }
        $db_park_serial_log = new ParkSerialLog();
        $where = [];
        $where[] = ['add_time', '<=', $parkSerialLogLimitTime];
        return $db_park_serial_log->del_datas($where);
    }
}