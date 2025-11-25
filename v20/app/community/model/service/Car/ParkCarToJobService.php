<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      停车队列执行业务
 */

namespace app\community\model\service\Car;

use app\common\model\service\image\ImageService;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\InPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkPassage;
use app\traits\Car\CarFlowHandleTraits;
use app\traits\CommonLogTraits;
use file_handle\FileHandle;
use customization\customization;
use redis\Redis;

class ParkCarToJobService
{
    use CarFlowHandleTraits;
    use CommonLogTraits;
    use customization;
    public function parkCarImageToJob($imageToJob)
    {
        try {
            $ymdh = date('Ymd');
            $imageToJob['jobType'] = 'handleCarPassImageRecord';
            $imageToJob['ymdh'] = $ymdh;
            $nowTime = time();
            $serialno = isset($imageToJob['serialno']) ? trim($imageToJob['serialno']) : '';
            $car_number = isset($imageToJob['car_number']) ? trim($imageToJob['car_number']) : '';
            $rand_num = date('Ymd', $nowTime);
            $rootPath = app()->getRootPath() . '..';
            $imageSavePath = $rootPath . '/upload/parkCar/' . $rand_num . '/' . $serialno . '/';
            if (!is_dir($imageSavePath)) {
                mkdir($imageSavePath, 0777, true);
            }
            $park_type = isset($imageToJob['park_type']) ? trim($imageToJob['park_type']) : '';
            $car_number_no = mb_substr($car_number, 1);
            if(isset($imageToJob['car_number_no']) && $imageToJob['car_number_no']){
                $car_number_no=$imageToJob['car_number_no'];
            }
            if(empty($car_number_no)){
                $car_number_no=$car_number;
            }
            $rediskey='carimg_'.$park_type . '_' . $car_number_no . '_' . date('YmdHis'); 
            $isDirectProcessing=true;
            if($this->hasCloudIntercom() || (isset($imageToJob['from_xtype']) && $imageToJob['from_xtype']=='hz_mqttRequest')){
                if(!isset($imageToJob['from_xtype']) || $imageToJob['from_xtype']!='hz_mqttRequest'){
                    //return true;
                }
                $bast_root='';
                if($_SERVER && isset($_SERVER['DOCUMENT_ROOT'])){
                    $bast_root=$_SERVER['DOCUMENT_ROOT'];
                }else{
                    $bast_root = app()->getRootPath() . '..';
                }
                $imageToJob['imageSavePath']= $bast_root.'/upload/newImageUpload/park_car_img/' . $rand_num . '/' . $serialno . '/';
                $isDirectProcessing=false;
                $redisConfig=$this->getCarOtherRedisConfig();
                $redisDb = new Redis($redisConfig);
                $redisDb->set($rediskey,$imageToJob,86400);
                $keysCount=$redisDb->getDbSize();
                $keysCount=$keysCount>0 ? $keysCount:0;
                $imageToJobParam = [
                    'img_redis_key' => $rediskey,
                    'car_number'=>$car_number,
                    'park_type'=>$park_type,
                    'jobType' => 'handleCarPassImageRecord',
                ];
                $time=3;
                if($keysCount>0){
                    $time=$keysCount*2+$time;
                }
                /*
                if($keysCount>0 && $keysCount<200){
                    $time=$keysCount*3;
                }elseif ($keysCount>=200 && $keysCount<500){
                    $time=$keysCount*5;
                }else if($keysCount>=500){
                    $time=$keysCount*10;
                }
                $dateH=date('H');
                $dateH=intval($dateH);
                if(in_array($dateH,[17,18,19,20])){
                    if($keysCount>0 && $keysCount<300){
                        $time=$keysCount*20;
                    }elseif ($keysCount>=300){
                        $time=$keysCount*30;
                    }else{
                        //$time=3600*5;
                    }
                    //$time=$time+7200;
                }else if(in_array($dateH,[6,7,8])){
                    if($keysCount>0 && $keysCount<300){
                        $time=$keysCount*20;
                    }elseif ($keysCount>=300){
                        $time=$keysCount*30;
                    }else{
                       // $time=3600*3;
                    }
                    //$time=$time+7200;
                }else if(in_array($dateH,[11,12,13])){
                    if($keysCount>0 && $keysCount<300){
                        $time=$keysCount*10;
                    }elseif ($keysCount>=300){
                        $time=$keysCount*20;
                    }else{
                        // $time=3600*3;
                    }
                    //$time=$time+7200;
                }
                if($time>36000){
                    //大于10个小时 就按10个小时延迟处理
                    $time=36000;
                }
                */
                $job_id = $this->traitCommonLvCC($imageToJobParam, $time);
                fdump_api([ 'imageToJobParam' => $imageToJobParam,'keysCount'=>$keysCount,'time'=>$time ,'job_id' => $job_id], 'ParkCarToJob/handleCarPassImageRecordJob'.$ymdh);
            }else{
                $isDirectProcessing=false;
                $jsonName = $park_type . '_' . $car_number . '_' . $nowTime . '.json';
                $jsonPath = $imageSavePath . $jsonName;
                $imageToJobJson = json_encode($imageToJob, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $j = @file_put_contents($jsonPath, $imageToJobJson);
                if ($j) {
                    $imageToJobParam = [
                        'jsonPath' => $jsonPath,
                        'nowTime' => $nowTime,
                        'jobType' => 'handleCarPassImageRecord',
                    ];
                    $job_id = $this->traitCommonLvCC($imageToJobParam, 6);
                }
            }
            if($isDirectProcessing){
                $this->handleCarPassImageRecord($imageToJob,$isDirectProcessing);
            }
//            $job_id = $this->traitCommonCarFlowHandle($imageToJob, $this->parkCarImageSpaceTimes);
//            fdump_api(['msg' => '图片走队列', 'j' => $j, 'imageToJobParam' => $imageToJobParam, 'jsonPath' => $jsonPath, 'job_id' => $job_id], 'ParkCarToJob/parkCarImageToJob'.$ymdh);

        } catch (\Exception $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'imageToJob' => $imageToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errParkCarImageToJobLog', true);
        } catch (\Throwable $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'imageToJob' => $imageToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errParkCarImageToJobLog', true);
        } catch (\RuntimeException $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'imageToJob' => $imageToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errParkCarImageToJobLog', true);
        } catch (\InvalidArgumentException $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'imageToJob' => $imageToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errParkCarImageToJobLog', true);
        }
        
        /*
        $imageToJob = unsetParam($imageToJob, ['imageSavePath', 'imageFile', 'imageFragmentFile', 'imageFileSaveFileName', 'imageFragmentFileSaveFileName', 'jobType']);
        $this->outToInParkToJob($imageToJob);
        */
        return true;
    }
    
    /**
     * 处理图片记录.
     */
    public function handleCarPassImageRecord($imageToJob,$isDirectProcessing=false)
    {
        $jsonPath = '';
        $param=array();
        $ymdh = isset($imageToJob['ymdh']) && trim($imageToJob['ymdh']) ? trim($imageToJob['ymdh']) : date('YmdH');
        if (isset($imageToJob['jsonPath']) && $imageToJob['jsonPath']) {
            $jsonPath = trim($imageToJob['jsonPath']);
            if (! $jsonPath) {
                return true;
            }
            $dataJson = file_get_contents($jsonPath);
            $param = json_decode($dataJson, true);
//            fdump_api(['msg' => '图片执行队列', 'jsonPath' => $jsonPath, 'dataJson' => $dataJson, 'param' => $param], 'ParkCarToJob/1111handleCarPassImageRecord');
            if (empty($param)) {
                return true;
            }
        }else if(isset($imageToJob['img_redis_key']) && $imageToJob['img_redis_key']){
            $redisConfig=$this->getCarOtherRedisConfig();
            $redisDb = new Redis($redisConfig);
            $param=$redisDb->get($imageToJob['img_redis_key']);
            $param['isCompressImage']=1;
            $redisDb->delete($imageToJob['img_redis_key']);
            fdump_api([ 'imageToJob' => $imageToJob, 'param' => $param], 'ParkCarToJob/handleCarPassImageRecord'.$ymdh);
            if (empty($param)) {
                return true;
            }
        }else if($isDirectProcessing){
            $param=$imageToJob;
        }
        if(empty($param) || !isset($param['car_number'])){
            return true;
        }
      //  $imageToJobParam = unsetParam($param, ['imageFile', 'imageFragmentFile']);
//        fdump_api(['msg' => '图片执行队列', 'param' => $imageToJobParam, 'jsonPath' => $jsonPath], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh);
        $park_type = isset($param['park_type']) ? trim($param['park_type']) : '';
        $serialno = isset($param['serialno']) ? trim($param['serialno']) : '';
        $car_number = isset($param['car_number']) ? trim($param['car_number']) : '';
        $now_time = isset($param['now_time']) && intval($param['now_time']) > 0 ? intval($param['now_time']) : time();
        $order_id = isset($param['order_id']) ? trim($param['order_id']) : '';
        $car_access_order_id = isset($param['car_access_order_id']) ? trim($param['car_access_order_id']) : '';
        $imageSavePath = isset($param['imageSavePath']) ? trim($param['imageSavePath']) : '';
        $isCompressImage = isset($param['isCompressImage']) ? intval($param['isCompressImage']) : 0;
        $village_id = isset($param['village_id']) && intval($param['village_id']) > 0 ? intval($param['village_id']) : 0;
        $in_park_id = isset($param['in_park_id']) && intval($param['in_park_id']) > 0 ? intval($param['in_park_id']) : 0;
        $in_record_id = isset($param['in_record_id']) && intval($param['in_record_id']) > 0 ? intval($param['in_record_id']) : 0;
        $out_park_id = isset($param['out_park_id']) && intval($param['out_park_id']) > 0 ? intval($param['out_park_id']) : 0;
        $out_record_id = isset($param['out_record_id']) && intval($param['out_record_id']) > 0 ? intval($param['out_record_id']) : 0;

        $imageFragmentFile = isset($param['imageFragmentFile']) ? trim($param['imageFragmentFile']) : '';
        $imageFragmentFileSaveFileName = isset($param['imageFragmentFileSaveFileName']) ? trim($param['imageFragmentFileSaveFileName']) : '';

        $imageFile = isset($param['imageFile']) ? trim($param['imageFile']) : '';
        $imageFileSaveFileName = isset($param['imageFileSaveFileName']) ? trim($param['imageFileSaveFileName']) : '';

        $ymdhis = date('Ymdhis', $now_time);
        $rand_num = date('Ymd', $now_time);
        $rootPath = app()->getRootPath() . '..';
        !$imageSavePath && $imageSavePath = $rootPath . '/upload/park_log/' . $rand_num . '/' . $serialno . '/';
        fdump_api([ 'rootPath' => $rootPath, 'imageSavePath' => $imageSavePath], 'ParkCarToJob/handleCarPassImageRecord'.$ymdh,true);
        $image_big = '';
        $content_path = '';
        $service_image = new ImageService();
        $fileHandle   = new FileHandle();
        if ($imageFile) {
            if ($isCompressImage == 1) {
                $image_data = [];
                $image_data['imgPath'] = $imageFile;
                $image_data['with'] = 1000;
                $image_data['heiht'] = 600;
                $imageFileCompress = $service_image->encodeImgToDataUrl($image_data);
                if (!empty($imageFileCompress)) {
                    $imageFile = str_replace('data:image/jpeg;base64,', '', $imageFileCompress);
                }
            }
            !$imageFileSaveFileName && $imageFileSaveFileName = $ymdhis . '_image_big_' . $car_number;
            $image_big_root = base64_to_img($imageSavePath, $imageFileSaveFileName, $imageFile, 'jpg');
            $rootImageBigArr = explode('/upload/', $image_big_root);
            $searchPath = isset($rootImageBigArr[0]) ? $rootImageBigArr[0] : '';
            $image_big = str_replace($searchPath, '', $image_big_root);
            $image_big && $content_path = $image_big;
            if ($image_big_root && $fileHandle->check_open_oss()) {
                try {
                    $info = $fileHandle->upload($image_big_root);
                    if(isset($info['error']) && !$info['error']){
                        @unlink($image_big_root);
                    }
                } catch (\Exception $e) {
                    fdump_api(['msg' => 'oss上传大图执行错误', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root, 'image_big' => $image_big, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                } catch (\Throwable $e) {
                    fdump_api(['msg' => 'oss上传大图执行错误', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root, 'image_big' => $image_big, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                } catch (\RuntimeException $e) {
                    fdump_api(['msg' => 'oss上传大图执行错误', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root, 'image_big' => $image_big, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['msg' => 'oss上传大图执行错误', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root, 'image_big' => $image_big, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                }
            }
//            fdump_api(['msg' => '大图片存储下来', 'image_big' => $image_big, 'imageSavePath' => $imageSavePath, 'imageFileSaveFileName' => $imageFileSaveFileName], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh, 1);
        }

        $image_small = '';
        if ($imageFragmentFile) {
            if ($isCompressImage == 1) {
                $image_data = [];
                $image_data['imgPath'] = $imageFragmentFile;
                $image_data['with'] = 1000;
                $image_data['heiht'] = 600;
                $imageFragmentFileCompress = $service_image->encodeImgToDataUrl($image_data);
                if (!empty($imageFragmentFileCompress)) {
                    $imageFragmentFile = str_replace('data:image/jpeg;base64,', '', $imageFragmentFileCompress);
                }
            }
            !$imageFragmentFileSaveFileName && $imageFragmentFileSaveFileName = $ymdhis . '_image_small_' . $car_number;
            $image_small_root = base64_to_img($imageSavePath, $imageFragmentFileSaveFileName, $imageFragmentFile, 'jpg');
            $rootImageSmallArr = explode('/upload/', $image_small_root);
            $searchPath = isset($rootImageSmallArr[0]) ? $rootImageSmallArr[0] : '';
            $image_small = str_replace($searchPath, '', $image_small_root);
            $image_small && !$content_path && $content_path = $image_small;
            if ($image_small_root && $fileHandle->check_open_oss()) {
                try {
                    $fileHandle->upload($image_small_root);
                } catch (\Exception $e) {
                    fdump_api(['msg' => 'oss上传小图执行错误', 'message' => $e->getMessage(), 'image_small_root' => $image_small_root, 'image_small' => $image_small, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                } catch (\Throwable $e) {
                    fdump_api(['msg' => 'oss上传小图执行错误', 'message' => $e->getMessage(), 'image_small_root' => $image_small_root, 'image_small' => $image_small, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                } catch (\RuntimeException $e) {
                    fdump_api(['msg' => 'oss上传小图执行错误', 'message' => $e->getMessage(), 'image_small_root' => $image_small_root, 'image_small' => $image_small, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['msg' => 'oss上传小图执行错误', 'message' => $e->getMessage(), 'image_small_root' => $image_small_root, 'image_small' => $image_small, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOssUploadLog', true);
                }
            }
//            fdump_api(['msg' => '小图片存储下来', 'image_small' => $image_small, 'imageSavePath' => $imageSavePath, 'imageFragmentFileSaveFileName' => $imageFragmentFileSaveFileName], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh, 1);
        }

        $db_in_park = new InPark();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();

        if ($park_type == 'in_park') {
            // todo 入场
            $in_time = isset($param['in_time']) && intval($param['in_time']) > 0 ? intval($param['in_time']) : $now_time;
            $in_park_data = [];
            $car_access_record = [];
            $image_big && $in_park_data['in_image_big'] = $image_big;
            $image_big && $car_access_record['accessBigImage'] = $image_big;

            $image_small && $in_park_data['in_image_small'] = $image_small;
            $image_small && $car_access_record['accessImage'] = $image_small;

//            fdump_api(['msg' => '入场记录', 'in_park_data' => $in_park_data, 'car_access_record' => $car_access_record], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh, 1);
            $where_in_park = [];
            if (!empty($in_park_data) && $in_park_id) {
                $where_in_park[] = ['id', '=', $in_park_id];
            } elseif (!empty($in_park_data)) {
                $where_in_park[] = ['order_id', '=', $order_id];
                $village_id && $where_in_park[] = ['park_id', '=', $village_id];
                $where_in_park[] = ['car_number', '=', $car_number];
            }
            if (!empty($where_in_park)) {
//                fdump_api(['msg' => '入场记录-存储', 'in_park_data' => $in_park_data, 'where_in_park' => $where_in_park], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh, 1);
                $db_in_park->saveOne($where_in_park, $in_park_data);
            }
            $where_in_access = [];
            if (!empty($car_access_record) && $in_record_id) {
                $where_in_access[] = ['record_id', '=', $in_record_id];
            } elseif (!empty($car_access_record) && $car_access_order_id) {
                $where_in_access[] = ['order_id', '=', $car_access_order_id];
                $village_id && $where_in_access[] = ['park_id', '=', $village_id];
                $where_in_access[] = ['car_number', '=', $car_number];
                $where_in_access[] = ['accessType', '=', 1];
            }
            if (!empty($where_in_access)) {
//                fdump_api(['msg' => '新版入场记录-存储', 'car_access_record' => $car_access_record, 'where_in_access' => $where_in_access], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh, 1);
                $db_house_village_car_access_record->saveOne($where_in_access, $car_access_record);
            }
        }

        if ($park_type == 'out_park') {
            $in_time = isset($param['in_time']) && intval($param['in_time']) > 0 ? intval($param['in_time']) : 0;
            $out_time = isset($param['out_time']) && intval($param['out_time']) > 0 ? intval($param['out_time']) : $now_time;
            $in_park_data = [];
            $car_access_record = [];
            $image_big && $in_park_data['out_image_big'] = $image_big;
            $image_big && $car_access_record['accessBigImage'] = $image_big;

            $image_small && $in_park_data['out_image_small'] = $image_small;
            $image_small && $car_access_record['accessImage'] = $image_small;

//            fdump_api(['timeStr' => date('Y-m-d H:i:s'), 'in_park_data' => $in_park_data, 'car_access_record' => $car_access_record], 'ParkCarToJob/handleCarPassImageRecordLog'.$ymdh, 1);
            $where_in_park = [];
            if (!empty($in_park_data) && $in_park_id) {
                $where_in_park[] = ['id', '=', $in_park_id];
            } elseif (!empty($in_park_data)) {
                $where_in_park[] = ['order_id', '=', $order_id];
                $village_id && $where_in_park[] = ['park_id', '=', $village_id];
                $where_in_park[] = ['car_number', '=', $car_number];
            }
            if (!empty($where_in_park)) {
                 //fdump_api(['timeStr' => date('Y-m-d H:i:s'),'car_number'=>$car_number, 'in_park_data' => $in_park_data, 'where_in_park' => $where_in_park], 'ParkCarToJob/handleCarPassImageOut_park'.$ymdh, 1);
                $db_in_park->saveOne($where_in_park, $in_park_data);
            }
            $where_out_access = [];
            if (!empty($car_access_record) && $out_record_id) {
                $where_out_access[] = ['record_id', '=', $out_record_id];
            } elseif (!empty($car_access_record) && $car_access_order_id) {
                $where_out_access[] = ['order_id', '=', $car_access_order_id];
                $village_id && $where_out_access[] = ['park_id', '=', $village_id];
                $where_out_access[] = ['car_number', '=', $car_number];
                $where_out_access[] = ['accessType', '=', 2];
            }
            if (!empty($where_out_access)) {
               //fdump_api(['timeStr' => date('Y-m-d H:i:s'),'car_number'=>$car_number, 'where_out_access' => $where_out_access,'car_access_record'=>$car_access_record], 'ParkCarToJob/handleCarPassImageOut_park'.$ymdh, 1);
                $db_house_village_car_access_record->saveOne($where_out_access, $car_access_record);
            }
            if ($content_path && $out_park_id) {
                $db_out_park = new OutPark();
                $whereOutPark = [];
                $whereOutPark[] = ['id', '=', $out_park_id];
                $outUpdateParam = ['content_path' => $content_path];
                $db_out_park->saveOne($whereOutPark, $outUpdateParam);
            }
        }
        if($jsonPath){
            @unlink($jsonPath);
        }
        return true;
    }

    public function outToInParkToJob($outToJob) {
        try {
            $ymdh = date('Ymd');
            $outToJob['jobType'] = 'handleOutToInPark';
            $outToJob['ymdh'] = $ymdh;
            $job_id = $this->traitCommonLvCC($outToJob, 10);
        } catch (\Exception $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'outToJob' => $outToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOutToInParkToJobLog', true);
        } catch (\Throwable $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'outToJob' => $outToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOutToInParkToJobLog', true);
        } catch (\RuntimeException $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'outToJob' => $outToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOutToInParkToJobLog', true);
        } catch (\InvalidArgumentException $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'outToJob' => $outToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errOutToInParkToJobLog', true);
        }
        return true;
    }

    public function handleOutToInPark($param) {
        $ymdh = isset($param['ymdh']) && trim($param['ymdh']) ? trim($param['ymdh']) : date('Ymd');
        $car_number = isset($param['car_number']) ? trim($param['car_number']) : '';
        $village_id = isset($param['village_id']) && $param['village_id'] ? $param['village_id'] : 0;
        $in_park_id = isset($param['in_park_id']) && $param['in_park_id'] ? $param['in_park_id'] : 0;
        $record_id = isset($param['record_id']) && $param['record_id'] ? $param['record_id'] : 0;
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $access_time = isset($param['access_time']) && $param['access_time'] ? $param['access_time'] : 0;
        $access_type = isset($param['access_type']) && $param['access_type'] ? $param['access_type'] : 0;  // 1进  2出
        $access_order_id = isset($param['access_order_id']) && $param['access_order_id'] ? $param['access_order_id'] : '';
        if($access_time>0){
            $access_time=$access_time-120;
        }else{
            $access_time=time()-120;
        }
        $a_week_time=time()-8*24*3600;  //一周前的 一出数据删掉
        fdump_api(['param' => $param], 'ParkCarToJob/handleOutToInPark'.$ymdh);
        $db_in_park = new InPark();
        if($in_park_id>0 && $access_type==2){
            if(empty($access_order_id) && $record_id>0){
                $access_record_where_near = [
                        ['car_number', '=', $car_number],
                        ['park_id', '=', $village_id],
                        ['accessType', '=',1],
                        ['is_out', '=', 0],
                        ['accessTime', '<=', $access_time],
                    ];
                    // 通行时间小于出场时间的最近的一条入场时间的记录
                    $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                    if ($park_info_car111 && !$park_info_car111->isEmpty()){
                        fdump_api(['access_record_where_near' => $access_record_where_near], 'ParkCarToJob/handleOutToInPark'.$ymdh,1);
                        $park_info_car111=$park_info_car111->toArray();
                        $db_house_village_car_access_record->saveOne(array('record_id'=>$record_id),['is_out'=>1,'order_id'=>$park_info_car111['order_id']]);
                        fdump_api(['time'=>time()], 'ParkCarToJob/handleOutToInPark'.$ymdh,1);
                        
                        $access_record_where_near = [
                            ['record_id', '=', $park_info_car111['record_id']],
                        ];
                        $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                        
                    }
                    
            }else{
                $park_where=array('id'=>$in_park_id);
                $in_park_info_obj = $db_in_park->getOne($park_where,'*');
                $in_park_info=array();
                if($in_park_info_obj && !$in_park_info_obj->isEmpty()){
                    $in_park_info= $in_park_info_obj->toArray();
                }
                if($in_park_info && isset($in_park_info['record_id']) && $in_park_info['record_id']){
                    $access_record_where_near = [
                        ['record_id', '=', $in_park_info['record_id']],
                    ];
                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                }
            } 
        }
        if($in_park_id>0 && $access_type==1){
            $park_where=array('id'=>$in_park_id);
            $in_park_info_obj = $db_in_park->getOne($park_where,'*');
            $in_park_info=array();
            if($in_park_info_obj && !$in_park_info_obj->isEmpty()){
                $in_park_info= $in_park_info_obj->toArray();
            }
            if($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])){
                $saveArr=array('access_record_id'=>$record_id,'access_order_id'=>$access_order_id);
                $db_in_park->saveOne($park_where,$saveArr);
            }
        }
        if($record_id>0 || $access_time>0){
            $access_record_where_near = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $village_id],
                ['is_out', '=', 0],
            ];
            if($record_id>0){
                $access_record_where_near[]=['record_id', '<', $record_id];
            }else{
                $access_record_where_near[]=['accessTime', '<', $access_time];
            }
            fdump_api(['time'=>time(),'access_record_where_near' => $access_record_where_near], 'ParkCarToJob/handleOutToInPark'.$ymdh,1);
            //最近一条未出场的 记录
            $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
            if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                $access_record_tmp=$access_record_tmp->toArray();
                fdump_api(['time'=>time()], 'ParkCarToJob/handleOutToInPark'.$ymdh, 1);
                $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                
            }
        }
        fdump_api(['time'=>time(),'access_record_tmp' => $access_record_tmp], 'ParkCarToJob/handleOutToInPark'.$ymdh, 1);
        if($in_park_id>0 && $car_number){
            //删掉一周前进场 已出场的 记录数据
            $del_where=array(['id','<',$in_park_id],['park_id','=',$village_id],['car_number','=',$car_number]);
            $del_where[]=array('is_out','=',1);
            $del_where[]=array('in_time','<=',$a_week_time);
            $db_in_park->delDatas($del_where);
            fdump_api(['time'=>time(),'del_where' => $del_where], 'ParkCarToJob/handleOutToInPark'.$ymdh,1);
        }
        return true;
    }

    public function HandleUseParkingPositionToJob($useToJob)
    {
        try {
            $ymdh = date('Ymd');
            if (!isset($useToJob['jobType']) || !$useToJob['jobType']) {
                $useToJob['jobType'] = 'handleUseParkingPosition';
            }
            $useToJob['ymdh'] = $ymdh;
            $job_id = $this->traitCommonCarFlowHandle($useToJob, $this->parkCarFlowHandleSpaceTimes);
            fdump_api(['msg' => '车位占用处理队列', 'job_id' => $job_id, 'useToJob' => $useToJob], 'ParkCarToJob/handleUseParkingPositionToJobLog'.$ymdh);
        } catch (\Exception $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'useToJob' => $useToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errHandleUseParkingPositionToJobLog', true);
        } catch (\Throwable $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'useToJob' => $useToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errHandleUseParkingPositionToJobLog', true);
        } catch (\RuntimeException $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'useToJob' => $useToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errHandleUseParkingPositionToJobLog', true);
        } catch (\InvalidArgumentException $e) {
            fdump_api(['msg' => '执行错误', 'message' => $e->getMessage(), 'useToJob' => $useToJob, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'ParkCarToJob/errHandleUseParkingPositionToJobLog', true);
        }
        return true;
    }

    // 检查一下异常的占用 重置成未占用
    public function resettingUseParkingPosition($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $car_id = isset($params['car_id']) ? intval($params['car_id']) : 0;
        $nowTime = isset($params['nowTime']) ? intval($params['nowTime']) : time();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('use_car_id', '=', $car_id);
        $whereArr[] = array('use_start_time', '<', $nowTime);
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $fieldStr = 'position_id,garage_id,end_time,access_record_id,use_start_time,use_car_number,use_car_id';
        $use_position_obj = $db_house_village_parking_position->getList($whereArr, $fieldStr, 1, 1);
        if ($use_position_obj && !is_array($use_position_obj)) {
            $use_position_obj = $use_position_obj->toArray();
        }
        if (!empty($use_position_obj)) {
            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
            $params['where'] = $whereArr;
            $params['updateData'] = $saveArr;
            $this->handleUseParkingPosition($params);
        }
        return true;
    }

    public function handleUseParkingPosition($params)
    {
        $where = isset($params['where']) ? $params['where'] : [];
        $updateData = isset($params['updateData']) ? $params['updateData'] : [];
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        if (!empty($where) && !empty($updateData)) {
            return $db_house_village_parking_position->saveOne($where, $updateData);
        }
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $car_id = isset($params['car_id']) ? intval($params['car_id']) : 0;
        $position_id = isset($params['position_id']) ? intval($params['position_id']) : 0;
        $nowTime = isset($params['nowTime']) ? intval($params['nowTime']) : time();
        $car_number = isset($params['car_number']) ? trim($params['car_number']) : '';
        $access_record_id = isset($params['access_record_id']) ? intval($params['access_record_id']) : 0;
        $wherePosition = [];
        if ($position_id > 0) {
            $wherePosition[] = ['position_id' => $position_id];
            $updateData = array('use_car_id' => $car_id, 'use_start_time' => $nowTime, 'use_car_number' => $car_number, 'access_record_id' => $access_record_id);
        } else {
            $wherePosition[] = ['village_id', '=', $village_id];
            $wherePosition[] = ['use_car_id', '=', $car_id];
            $wherePosition[] = ['use_start_time', '<', $nowTime];
            $updateData = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
        }
        return $db_house_village_parking_position->saveOne($wherePosition, $updateData);
    }

    /**
     * 更新车辆通道心跳.
     */
    public function updateParkPassage($params)
    {
        $device_number = isset($params['device_number']) ? trim($params['device_number']) : '';
        $nowTime = isset($params['nowTime']) ? intval($params['nowTime']) : time();
        $db_park_passage = new ParkPassage();
        $wherePassage = [];
        $wherePassage[] = ['device_number', '=', $device_number];
        return $db_park_passage->save_one($wherePassage, ['last_heart_time' => $nowTime]);
    }
}