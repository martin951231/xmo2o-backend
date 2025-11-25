<?php

declare(strict_types=1);

namespace app\wechatLive\model\db;


use think\Model;

class WechatLiveRoom extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;



    public function getLiveStatusAttr($value)
    {
        $liveStatus   = [
            '101' => '直播中',
            '102' => '未开始',
            '103' => '已结束',
            '104' => '禁播',
            '105' => '暂停',
            '106' => '异常',
            '107' => '已过期',
        ];
        return $liveStatus[$value] ?? '';
    }
}
