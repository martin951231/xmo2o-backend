<?php

declare(strict_types=1);

namespace app\wechatLive\model\service;

use app\wechatLive\model\db\WechatLiveAnchor;
use app\wechatLive\model\db\WechatLiveRoom;
use Exception;
use think\facade\Db;
use wechatLive\MiniLive;

class LiveRoomService
{
    public $roomMod = null;

    public function __construct()
    {
        $this->roomMod = new WechatLiveRoom();
    }

    public function getRooms($params)
    {
        $where = [];
        $merId = $params['mer_id'] ?? 0;
        $pageSize = $params['page_size'] ?? 10;
        $keyword = $params['keyword']??'';
        if ($merId) {
            $openids = (new WechatLiveAnchor())->where('mer_id', $merId)->column('openid');
            if (empty($openids)) {
                return [];
            }
            $where[] = ['creater_openid', 'IN', $openids];
        }
        if ($keyword) {
            $where[] = Db::raw("anchor_name like '%" . $keyword . "%' OR name like '%" . $keyword . "%'");
        }

        $result = $this->roomMod->withoutField('creater_openid')->order('roomid', 'DESC')->where($where)->paginate($pageSize)->each(
            function ($item) {
                $item['start_date'] = date('Y-m-d H:i:s', $item['start_time']);
                $item['end_date'] = date('Y-m-d H:i:s', $item['end_time']);
                $item['share_img'] = replace_file_domain($item['share_img']);
            }
        )->toArray();
        return $result;
    }

    public function getRoomShare($roomid)
    {
        $thisRoom = $this->roomMod->where('roomid', $roomid)->find();
        if (empty($thisRoom)) {
            throw new Exception('直播间不存在');
        }

        if ($thisRoom['cdnUrl']) {
            $res = [
                'cdnUrl' => $thisRoom['cdnUrl'],
                'pagePath' => $thisRoom['pagePath'],
                'posterUrl' => $thisRoom['posterUrl'],
            ];
        } else {
            $minLive = new MiniLive;
            $res = $minLive->doAction(false, 'getSharedCode', ['roomId' => $roomid]);
            $thisRoom->cdnUrl = $res['cdnUrl'];
            $thisRoom->pagePath = $res['pagePath'];
            $thisRoom->posterUrl = $res['posterUrl'];
            $thisRoom->save();
        }

        $imgBase64 = function ($url) {
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($url));
        };
        $res['cdnUrlOrigin'] = $res['cdnUrl'];
        $res['posterUrlOrigin'] = $res['posterUrl'];
        $res['cdnUrl'] = $imgBase64($res['cdnUrl']);
        $res['posterUrl'] = $imgBase64($res['posterUrl']);
        return $res;
    }

    public function delRoom($roomid){
        $thisRoom = $this->roomMod->where('roomid', $roomid)->find();
        if (empty($thisRoom)) {
            throw new Exception('直播间不存在');
        }
        (new MiniLive)->doAction(true,'deleteRoom',['id'=>$roomid]);
        $this->roomMod->where('roomid', $roomid)->delete();
        return true;
    }
}
