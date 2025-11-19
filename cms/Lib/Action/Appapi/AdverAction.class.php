<?php

/*
 * 关于我们
 *
 */

class AdverAction extends BaseAction
{

    //数据收集
    public function app_fullscreen_data_collect()
    {
        $adver_id = $_POST['adver_id'];
        $type = $_POST['type'];
        $num_name = $type . '_num';
        M('App_fullscreen_adver')->where(array('id' => $adver_id))->setInc($num_name, 1);
        $this->returnCode(0);
    }

    public function app_fullscreen_get()
    {
        $where['status'] = 1;
        $where['city_id'] = array('in', array(0, C("config.now_city")));
        $today_zero_time = mktime(0, 0, 0, date('m', $_SERVER['REQUEST_TIME']), date('d', $_SERVER['REQUEST_TIME']), date('Y', $_SERVER['REQUEST_TIME']));
        $where['_string'] = 'end_time+86400 > ' . $_SERVER['REQUEST_TIME'];
        $adver_list = M('App_fullscreen_adver')->field('id,name,ios_pic_s,ios_pic_b,ios_pic_bb,android_pic,url,begin_time,end_time,play_time')->where($where)->select();

        shuffle($adver_list);
        if (empty($adver_list)) {
            $this->returnCode('10080004');
        }
        foreach ($adver_list as &$v) {
            $v['ios_pic_s'] = replace_file_domain($v['ios_pic_s']);
            $v['ios_pic_b'] = replace_file_domain($v['ios_pic_b']);
            $v['ios_pic_bb'] = $v['ios_pic_bb'] ? replace_file_domain($v['ios_pic_bb']) : replace_file_domain($v['ios_pic_b']);
            $v['android_pic'] = replace_file_domain($v['android_pic']);
            $v['url'] = html_entity_decode($v['url']);
        }
        $this->returnCode(0, $adver_list[0]);
    }

}