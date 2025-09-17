<?php

/**
 * 帮助中心
 * @author: 张涛
 * @date: 2020/10/10
 */
class HelpAction extends BaseAction
{

    public function __construct()
    {
        parent::__construct();
        $this->static_path = $this->config['site_url'] . '/tpl/Wap/' . C('DEFAULT_THEME') . '/static/';
        $this->assign('static_path', $this->static_path);
        if($_GET['lang']){
            C('config.tmp_system_lang',$_GET['lang']);
        }
    }

    /**
     * 配送员3.0 任务地图
     * @author: 张涛
     * @date: 2020/10/10
     */
    public function taskMap()
    {
        $this->display();
    }

    /**
     * 配送员3.0 不同手机类型设置
     * @author: 张涛
     * @date: 2020/10/10
     */
    public function appAuthSet()
    {
        $brand = I('get.brand', '', 'trim');
        $tpl = 'setHuawei';
        if ($brand == 'xiaomi') {
            $tpl = 'setXiaomi';
        } else if ($brand == 'vivo') {
            $tpl = 'setVivo';
        } else if ($brand == 'oppo') {
            $tpl = 'setOppo';
        } else if ($brand == 'meizu') {
            $tpl = 'setMeizu';
        } else if ($brand == 'huawei') {
            $tpl = 'setHuawei';
        }
        $this->display($tpl);
    }

    public function showPoster()
    {
        $config = M('Config')->where(['name' => ['IN', ['site_url', 'static_oss_switch', 'static_oss_access_domain_names', 'upload_site_url', 'use_https']]])->getField('name,value');
        C('config', $config);
        $id = $_GET['id'] ?? 0;
        $activity = D('Private_activity')->find($id);
        $activity['style_tpl_pic'] = replace_file_domain($activity['style_tpl_pic']);
        $this->assign('activity', $activity);
        $this->display();
    }
}