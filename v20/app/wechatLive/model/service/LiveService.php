<?php


namespace app\wechatLive\model\service;


use app\wechatLive\model\db\LiveShow;

class LiveService
{
    public function liveList($limit)
    {
        $data = LiveShow::where([
                'is_del' => 0,
                'forbid_status' => 0,
            ])
            ->field('live_id, live_no, uid, live_nickname, thumbimg_1, thumbimg_2, title, status, create_time')
            ->order('status ASC,sort DESC,live_virtual_views DESC, zan DESC')
            ->limit($limit)
            ->select()
            ->each(function ($item){
                $item->thumbimg_1 = replace_file_domain($item->thumbimg_1);
                $item->thumbimg_2 = replace_file_domain($item->thumbimg_2);
                $item->jump_link = cfg('site_url').'/packapp/plat/pages/live_and_video/live/viewer/main_page?live_id='.$item->live_id;
            });
        
        return $data;
    }

}