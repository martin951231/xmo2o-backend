<?php
/**
 * 滚动消息
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 18:13
 */

namespace app\common\model\service;

use app\common\model\db\ScrollMsg;

class ScrollMsgService
{
    public $scrollMsgObj = null;
    public function __construct()
    {
        $this->scrollMsgObj = new ScrollMsg();
    }

    /**
     * 添加消息
     * User: chenxiang
     * Date: 2020/5/29 14:30
     * @param $type
     * @param $fid
     * @param $content
     */
    public function addMsg($type, $fid, $content) {
        if(cfg('show_scroll_msg')) {
            $data['type'] = $type;
            $data['fid'] = $fid;
            $data['content'] = $content;
            $data['add_time'] = $_SERVER['REQUEST_TIME'];
            $this->scrollMsgObj->addMsg($data);
        }
    }


}

