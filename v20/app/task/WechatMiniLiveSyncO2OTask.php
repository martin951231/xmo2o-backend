<?php

namespace app\task;

use app\wechatLive\model\db\WechatLiveGoods;
use app\wechatLive\model\db\WechatLiveRoom;
use app\wechatLive\model\service\LiveGoodsService;
use Exception;
use file_handle\FileHandle;
use wechatLive\MiniLive;
use yunwuxin\cron\Task;

/**
 * 微信小程序直播同步任务
 *
 * @author: zt
 * @date: 2023/12/16
 */
class WechatMiniLiveSyncO2OTask extends Task
{
    public function configure()
    {
        $this->everyFiveMinutes();
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        return true;
        try {
            //同步直播间,调用额度：100000次/一天（与获取回放接口共用次数）
            $limit = 50;
            $miniLive = new MiniLive();
            $liveRoomMod = new WechatLiveRoom();
            for ($i = 1; $i <= 30; $i++) {
                $rooms = $miniLive->getShowList($i, $limit);
                if (empty($rooms) || empty($rooms['room_info'])) {
                    break;
                }
                $rooms = $rooms['room_info'];
                foreach ($rooms as $room) {
                    $room['goods'] = isset($room['goods']) ? json_encode($room['goods'], JSON_UNESCAPED_UNICODE) : '';

                    //分享图下载保存，微信防盗链无法直接前端显示
                    $sharePic = root_path() . '/../upload/wechat_live/' . md5($room['share_img']) . '.jpg';
                    $dir = dirname($sharePic);
                    if (!file_exists($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    file_put_contents($sharePic, file_get_contents($room['share_img']));
                    $room['share_img'] = '/upload/wechat_live/' . md5($room['share_img']) . '.jpg';

                    $file_handle = new FileHandle();
                    if($file_handle->check_open_oss()) {
                        invoke_cms_model('Image/oss_upload_image', ['savepath' => $room['share_img']]);
                    }


                    $thisRoom = $liveRoomMod->where('roomid', $room['roomid'])
                        ->find();
                    if ($thisRoom) {
                        $liveRoomMod->updateThis(['roomid' => $room['roomid']], $room);
                    } else {
                        $liveRoomMod->add($room);
                    }
                }
                if (count($rooms) < $limit) {
                    break;
                }
            }
        } catch (Exception $e) {
        }

        try {
            //同步商品审核状态,调用额度：1000次/一天，每次读取20个商品，读取20次，最多400
            $liveGoodsMod = new WechatLiveGoods();
            $liveGoodsService = new LiveGoodsService();
            for ($i = 1; $i <= 20; $i++) {
                $wechatGoodsIds = $liveGoodsMod
                    ->whereIn('check_status', [0, 1])
                    ->limit(($i - 1) * 20, 20)
                    ->column('wechat_goods_id');

                if (empty($wechatGoodsIds)) {
                    break;
                }
                $liveGoodsService->wechatLiveGoodsSync($wechatGoodsIds);
            }
        } catch (Exception $e) {
        }
    }
}
