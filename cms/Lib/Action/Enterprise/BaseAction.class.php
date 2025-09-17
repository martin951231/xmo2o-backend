<?php

/*
 * 企业后台基类
 *
 */

class BaseAction extends Action
{
    protected $enterprise_session;

    protected $qiyeId;

    protected $config;

    protected $static_path;

    protected $static_public;

    protected function _initialize()
    {

        if (empty($_SERVER['REQUEST_SCHEME'])) {
            if ($_SERVER['SERVER_PORT'] == '443') {
                $_SERVER['REQUEST_SCHEME'] = 'https';
            } else {
                $_SERVER['REQUEST_SCHEME'] = 'http';
            }
        }
        $this->getQiyeMenu();
        $this->config = D('Config')->get_config(cookie('system_lang'));
        /* 判断强制跳转https开始 */
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {    //其他cdn
            $_SERVER['REQUEST_SCHEME'] = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } else if (isset($_SERVER['HTTP_X_CLIENT_SCHEME'])) {    //阿里cdn
            $_SERVER['REQUEST_SCHEME'] = $_SERVER['HTTP_X_CLIENT_SCHEME'];
        }
        if (empty($_SERVER['REQUEST_SCHEME'])) {
            if ($_SERVER['SERVER_PORT'] == '443') {
                $_SERVER['REQUEST_SCHEME'] = 'https';
            } else {
                $_SERVER['REQUEST_SCHEME'] = 'http';
            }
        }
        $config_parse_arr = parse_url($this->config['site_url']);
        if ($config_parse_arr['scheme'] == 'https' && ($_SERVER['REQUEST_SCHEME'] == 'http' || $_SERVER['SERVER_PORT'] == '80') && $_SERVER['REQUEST_METHOD'] == 'GET' && empty($_GET['echostr'])) {
            redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        }
        //是否是演示站
        $this->config['is_demo_domain'] = $this->config['is_demo_domain'] ? true : false;
        $this->config['is_network_demo_domain'] = $this->config['is_network_demo_domain'] ? true : false;
        //语言列表
        $lang = D('Lang');
        $this->assign('lang_list', $lang->service_lang);
        $this->config['system_lang_alias'] = $this->config['system_lang'] ? array_column($lang->service_lang, 'alias', 'val')[$this->config['system_lang']] : 'zh_CN';
        $this->assign('config', $this->config);
        C('config', $this->config);
        C('customization', D('Config_customization')->get_config());

        if (!empty($this->config['session_save_type']) && $this->config['session_save_type'] != 'file') {
            $class = 'Session' . ucwords(strtolower($this->config['session_save_type']));
            // 检查驱动类
            if (require_cache(EXTEND_PATH . 'Driver/Session/' . $class . '.class.php')) {
                $hander = new $class();
                $hander->execute();
            } else {
                // 类没有定义
                throw_exception(L('_CLASS_NOT_EXIST_') . ': ' . $class);
            }
        }
        session_start();
        $this->static_path = './tpl/Enterprise/static/';
        $this->static_public = './static/';
        $this->assign('static_path', $this->static_path);
        $this->assign('static_public', $this->static_public);
        if (!defined('IS_RUN_PLAN')) {
            include($_SERVER['DOCUMENT_ROOT'] . '/source/plan/index.php');
        }
        $this->enterprise_session = session('qiye');
        if ($this->enterprise_session) {
            $this->qiyeId = $this->enterprise_session['qiye_id'];
        }
        if (MODULE_NAME != 'Login') {
            if (empty($this->enterprise_session)) {
                redirect(U('Login/index'));
                exit;
            }
        }
    }

    public function _empty()
    {
        $this->error(L_('您访问错了！该页面不存在。'));
    }

    protected function error_tips($tips, $time = 2, $href = '')
    {
        if ($href == '') {
            $tips = '<font color=\"red\">' . $tips . '</font>';
            $href = 'javascript:history.back(-1);';
            $tips .= '<br/><br/>系统正在跳转到上一个页面。';
        }
        if ($time != 2) {
            $tips .= L_('X1秒后会提示将自动关闭，可手动关闭！', array('X1' => $time));
        }
        exit('<html><head><script>window.top.msg(0,"' . $tips . '",true,' . $time . ');location.href="' . $href . '";</script></head></html>');
    }


    public function json($data)
    {
        header('Content-type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function getQiyeMenu()
    {
        $menu = [
            [
                "id" => "1",
                "fid" => "0",
                "name" => "首页",
                "module" => "Index",
                "action" => "index",
                "select_module" => "Index",
                "select_action" => "index",
                "icon" => "fa-home",
                "sort" => "7",
                "show" => "1",
                "status" => "1",
                "price" => "0.00",
                "url" => "/enterprise.php?g=Enterprise&c=Index&a=index"
            ],
            [
                "id" => "2",
                "fid" => "0",
                "name" => "企业设置",
                "module" => "Config",
                "action" => "index",
                "select_module" => "Config",
                "select_action" => "index",
                "icon" => "fa-align-center",
                "sort" => "7",
                "show" => "1",
                "status" => "1",
                "price" => "0.00",
                "url" => "/enterprise.php?g=Enterprise&c=Config&a=index"
            ],
            [
                "id" => "3",
                "fid" => "0",
                "name" => "员工管理",
                "module" => "Staff",
                "action" => "index",
                "select_module" => "Staff",
                "select_action" => "index",
                "icon" => "fa-align-center",
                "sort" => "7",
                "show" => "1",
                "status" => "1",
                "price" => "0.00",
                "url" => "/enterprise.php?g=Enterprise&c=Staff&a=index"
            ],
            [
                "id" => "4",
                "fid" => "0",
                "name" => "商家关联",
                "module" => "Merchant",
                "action" => "index",
                "select_module" => "Merchant",
                "select_action" => "index",
                "icon" => "fa-align-center",
                "sort" => "7",
                "show" => "1",
                "status" => "1",
                "price" => "0.00",
                "url" => "/enterprise.php?g=Enterprise&c=Merchant&a=index"
            ],
            [
                "id" => "5",
                "fid" => "0",
                "name" => "财务结算",
                "module" => "",
                "action" => "",
                "select_module" => "Analysis",
                "select_action" => "index",
                "icon" => "fa-align-center",
                "sort" => "7",
                "show" => "1",
                "status" => "1",
                "price" => "0.00",
                "menu_list" => [
                    [
                        "id" => "6",
                        "fid" => "3",
                        "name" => "财务结算",
                        "module" => "Analysis",
                        "action" => "index",
                        "select_module" => "Analysis",
                        "select_action" => "index",
                        "icon" => "",
                        "sort" => "7",
                        "show" => "0",
                        "status" => "1",
                        "price" => "0.00",
                        "url" => "/enterprise.php?g=Enterprise&c=Analysis&a=index"],
                    [
                        "id" => "6",
                        "fid" => "3",
                        "name" => "消费数据报表",
                        "module" => "Analysis",
                        "action" => "trade",
                        "select_module" => "Analysis",
                        "select_action" => "trade",
                        "icon" => "",
                        "sort" => "7",
                        "show" => "1",
                        "status" => "1",
                        "price" => "0.00",
                        "url" => "/enterprise.php?g=Enterprise&c=Analysis&a=trade"],
                    [
                        "id" => "6",
                        "fid" => "3",
                        "name" => "结算记录",
                        "module" => "Analysis",
                        "action" => "settlement",
                        "select_module" => "Analysis",
                        "select_action" => "settlement",
                        "icon" => "",
                        "sort" => "7",
                        "show" => "1",
                        "status" => "1",
                        "price" => "0.00",
                        "url" => "/enterprise.php?g=Enterprise&c=Analysis&a=settlement"],
                    [
                        "id" => "6",
                        "fid" => "3",
                        "name" => "消费记录",
                        "module" => "Analysis",
                        "action" => "orders",
                        "select_module" => "Analysis",
                        "select_action" => "orders",
                        "icon" => "",
                        "sort" => "7",
                        "show" => "1",
                        "status" => "1",
                        "price" => "0.00",
                        "url" => "/enterprise.php?g=Enterprise&c=Analysis&a=orders"],
                    [
                        "id" => "6",
                        "fid" => "3",
                        "name" => "余额操作记录",
                        "module" => "Analysis",
                        "action" => "balance",
                        "select_module" => "Analysis",
                        "select_action" => "balance",
                        "icon" => "",
                        "sort" => "7",
                        "show" => "1",
                        "status" => "1",
                        "price" => "0.00",
                        "url" => "/enterprise.php?g=Enterprise&c=Analysis&a=balance"]
                ]
            ]
        ];
        $moduleName = MODULE_NAME;
        $actionName = ACTION_NAME;
        foreach ($menu AS $k => $v) {
            if ($moduleName == $v['select_module']) {
                $menu[$k]['is_active'] = true;
                //判断二级菜单
                foreach ($v['menu_list'] as $subKey => $subMenu) {
                    if ($actionName == $subMenu['select_action']) {
                        $menu[$k]['menu_list'][$subKey]['is_active'] = true;
                        break;
                    }
                }
                break;
            }
        }
        $this->assign('merchant_menu', $menu);
    }

}

?>