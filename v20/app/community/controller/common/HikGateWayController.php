<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康网关-监控+门禁
 */

namespace app\community\controller\common;

use app\common\model\service\UploadFileService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HikGateWayEventService;
use app\consts\DeviceConst;
use customization\customization;
use app\common\model\service\image\ImageService;
class HikGateWayController  extends CommunityBaseController
{
    use customization;
    public function eventNotification()
    {
        $input = file_get_contents("php://input");
        $event_log = isset($_POST['event_log']) && $_POST['event_log'] ? json_decode($_POST['event_log'], 1) : [];
        if(empty($event_log) && isset($_POST['AccessControllerEvent']) && !empty($_POST['AccessControllerEvent'])){
            //4以上的新版本
            $event_log = isset($_POST['AccessControllerEvent']) && $_POST['AccessControllerEvent'] ? json_decode($_POST['AccessControllerEvent'], 1) : [];
        }
        if ($input && strpos($input, '<?xml') !== false) {
            $event_log = json_decode(json_encode(simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
       fdump_api(['msg' => '监控消息通知', 'line' => __LINE__, '_file' => $_FILES, '_GET' => $_GET, '_POST' => $_POST, '_REQUEST' => $_REQUEST, 'event_log' => $event_log, 'input' => $input, '_SERVER' => $_SERVER], 'event/eventNotificationLog');
        // 抓拍图片
        $picture = $this->request->file('Picture');
        // 抓拍热成像图片
        $thermal = $this->request->file('Thermal');
        fdump_api(['msg' => '监控消息通知', 'line' => __LINE__, 'picture' => $picture, 'thermal' => $thermal], 'event/eventNotificationLog',1);
        $upload_dir='face_log';
        if($this->hasCloudIntercom() || (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] && strpos($_SERVER['HTTP_HOST'],'hz.huizhisq.com')!== false)){
            $upload_dir='newImageUpload/face_img';
        }
        if ($picture) {
            $_FILES['file']=$_FILES['Picture'];
            unset($_FILES['Picture']);
            try {
                $eventPicture = (new UploadFileService())->uploadPublicRental($picture, $upload_dir);
                fdump_api(['msg' => '抓拍图片', 'line' => __LINE__, 'eventPicture' => $eventPicture,'upload_dir'=>$upload_dir], 'event/eventNotificationLog', 1);
            } catch (\Exception $e) {
                    fdump_api(['err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'event/eventNotificationLog', 1);
                } catch (\Throwable $e) {
                    fdump_api(['err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'event/eventNotificationLog', 1);
                } catch (\RuntimeException $e) {
                    fdump_api(['err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'event/eventNotificationLog', 1);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'event/eventNotificationLog', 1);
                }
            $event_log['eventPicture'] = $eventPicture;
        } elseif ($thermal) {
          $_FILES['file']=$_FILES['Thermal'];
          unset($_FILES['Thermal']);
          $eventThermal = (new UploadFileService())->uploadPublicRental($thermal, $upload_dir);
           fdump_api(['msg' => '热成像-抓拍图片', 'line' => __LINE__, 'eventThermal' => $eventThermal], 'event/eventNotificationLog', 1);
            $event_log['eventPicture'] = $eventThermal;
        }
       
        $eventType = isset($event_log['eventType']) ? trim($event_log['eventType']) : '';
        if (empty($event_log)) {
            $eventArray = [
                'errorCode' => 1, 'errorMsg' => 'success', 'statusCode' => 1, 'statusString' => 'OK', 'subStatusCode' => 'ok',
            ];
            echo json_encode($eventArray, JSON_UNESCAPED_UNICODE);
            die;
        }
        switch ($eventType) {
            case 'AccessControllerEvent':
                try {
                    /*
                    $record_log = [];
                    if ($picture) {
                        $record_log = $picture;
                        if ($thermal && isset($thermal['tmp_name']) && $thermal['tmp_name'] && !file_exists($thermal['tmp_name'])) {
                           // unlink($thermal['tmp_name']);
                        }
                    } elseif ($thermal) {
                        $record_log = $thermal;
                    }
                    $event_log['record_log'] = $record_log;
                    */
                    if(isset($event_log['eventPicture']) && $event_log['eventPicture'] && isset($event_log['eventPicture']['url']) && $event_log['eventPicture']['url']){
                        //质量压缩 50%
                        $imageService=new ImageService();
                        $savePath = root_path() . ltrim($event_log['eventPicture']['url'],'.');
                        $savePath=str_replace('v20//','',$savePath);
                        $imageInfo = $imageService->getImageInfo($savePath);
                        if($imageInfo && $imageInfo['width']){
                            if($imageInfo['type']=='jpeg'){
                                $imageInfo['type']='jpg';
                            }
                            $param = [
                                'imgPath'      => $savePath,
                                'savePath'     => $savePath,
                                'format'       => $imageInfo['type'],
                                'heiht'        => $imageInfo['width'],
                                'with'         => $imageInfo['height'],
                                'quality'=>80
                            ];
                            $imgret=$imageService->compressImage($param);
                            fdump_api(['line' => __LINE__, 'imageInfo' => $imageInfo,'param'=>$param,'imgret'=>$imgret], 'event/eventNotificationLog', 1);
                        }
                    }
                    (new HikGateWayEventService())->handleAccessControllerEvent($event_log);
                } catch (\Exception $e) {
                    fdump_api(['门禁事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleAccessControllerEventLog', 1);
                } catch (\Throwable $e) {
                    fdump_api(['门禁事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleAccessControllerEventLog', 1);
                } catch (\RuntimeException $e) {
                    fdump_api(['门禁事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleAccessControllerEventLog', 1);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['门禁事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleAccessControllerEventLog', 1);
                }
                break;
            case 'VMD':
                try {
                    (new HikGateWayEventService())->handleCommonEvent($event_log);
                } catch (\Exception $e) {
                    fdump_api(['移动侦测事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                } catch (\Throwable $e) {
                    fdump_api(['移动侦测事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                } catch (\RuntimeException $e) {
                    fdump_api(['移动侦测事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['移动侦测事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                }
                if ($picture && isset($picture['tmp_name']) && $picture['tmp_name'] && !file_exists($picture['tmp_name'])) {
                    unlink($picture['tmp_name']);
                }
                if ($thermal && isset($thermal['tmp_name']) && $thermal['tmp_name'] && !file_exists($thermal['tmp_name'])) {
                    unlink($thermal['tmp_name']);
                }
                break;
            default:
                try {
                    (new HikGateWayEventService())->handleCommonEvent($event_log);
                } catch (\Exception $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                } catch (\Throwable $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                } catch (\RuntimeException $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'event/handleCommonEventLog', 1);
                }
                if ($picture && isset($picture['tmp_name']) && $picture['tmp_name'] && !file_exists($picture['tmp_name'])) {
                    unlink($picture['tmp_name']);
                }
                if ($thermal && isset($thermal['tmp_name']) && $thermal['tmp_name'] && !file_exists($thermal['tmp_name'])) {
                    unlink($thermal['tmp_name']);
                }
                break;
        }
        $eventArray = [
            'errorCode' => 1, 'errorMsg' => 'success', 'statusCode' => 1, 'statusString' => 'OK', 'subStatusCode' => 'ok',
        ];
        echo json_encode($eventArray, JSON_UNESCAPED_UNICODE);
        die;
    }
    
    public function eventISUP()
    {
        $input = file_get_contents("php://input");
        fdump_api(['msg' => 'ISUP消息通知', 'line' => __LINE__, '_file' => $_FILES, '_GET' => $_GET, '_POST' => $_POST, '_REQUEST' => $_REQUEST, 'input' => $input, '_SERVER' => $_SERVER], 'event/eventISUPLog', 1);
    }
}