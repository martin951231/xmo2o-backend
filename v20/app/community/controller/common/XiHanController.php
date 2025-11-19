<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      西函门禁相关
 */

namespace app\community\controller\common;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\XiHanEventService;

class XiHanController  extends CommunityBaseController
{
    public function eventNotification()
    {
        $input = file_get_contents("php://input");
        if ($input && strpos($input, '<?xml') !== false) {
            $event_log = json_decode(json_encode(simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        } else {
            $event_log = json_decode($input, true);
        }
        $serialNo = isset($_GET['SerialNo']) && $_GET['SerialNo'] ? $_GET['SerialNo'] : '';
        $event_log['SerialNo'] = $serialNo;
        fdump_api(['msg' => '监控消息通知', 'line' => __LINE__, '_file' => $_FILES, 'event_log' => $event_log], 'xiHan/eventNotificationLog', 1);
        try {
            (new XiHanEventService())->handleAccessControllerEvent($event_log);
        } catch (\Exception $e) {
            fdump_api(['门禁事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'xiHan/errEventNotificationLog');
        } catch (\Throwable $e) {
            fdump_api(['门禁事件详情处理-执行错误' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST], 'xiHan/errEventNotificationLog');
        }
        $result = [
            'result' => true, 'message' => 'ok', 'code' => 200,
        ];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        die;
    }
}