<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date: 2019/10/29
 * Time: 11:48
 */
//物业后台微信
class NewWeixinAction extends BaseAction{

    protected $village_id;
    protected $property_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        $property = session('property');
        $this->property_id = $property['id'];
    }
    // 微信公众号

    public function index()
    {

        $weixin_bind = array();
        if ($weixin_bind = D('Weixin_bind')->where(array('mer_id' => $this->property_id, 'type' => 2))->find()) {
            if ($weixin_bind['service_type_info'] == 0 || $weixin_bind['service_type_info'] == 1) {
                if ($weixin_bind['verify_type_info'] == -1) {
                    $weixin_bind['type_info'] = '未认证的订阅号';
                } else {
                    $weixin_bind['type_info'] = '认证的订阅号';
                }
            } else {
                if ($weixin_bind['verify_type_info'] == -1) {
                    $weixin_bind['type_info'] = '未认证服务号';
                } else {
                    $weixin_bind['type_info'] = '认证服务号';
                }
            }
        } else {
            import('ORG.Net.Http');
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $component_verify_ticket= D('Config')->where("`name`='wx_house_componentverifyticket'")->getField('value');
            $data = array('component_appid' => $this->config['wx_house_appid'], 'component_appsecret' => $this->config['wx_house_appsecret'], 'component_verify_ticket' => $component_verify_ticket);
            $result = Http::curlPost($url, json_encode($data));

            if (empty($result['errcode'])) {
                $_SESSION['component_access_token'] = array($result['expires_in'] + time(), $result['component_access_token']);

                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' . $result['component_access_token'];//
                $data = array('component_appid' => $this->config['wx_house_appid']);
                $auth_code = Http::curlPost($url, json_encode($data));
                if (empty($auth_code['errcode'])) {
                    $redirect_uri = urlencode($this->config['site_url'] . '/shequ.php?g=House&c=NewWeixin&a=auth_back&property='.$this->property_id);
                    $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->config['wx_house_appid'].'&pre_auth_code='.$auth_code['pre_auth_code'].'&redirect_uri=' . $redirect_uri ;
                    $this->assign('url', $url);
                } else {
                    $this->assign('url', '');
                }
            } else {
                // 报错了 且参数存在调取下消息获取
                if($this->config['wx_house_appid'] && $this->config['wx_house_token'] && $this->config['wx_house_appsecret'] && $this->config['wx_house_encodingaeskey']) {
                    $component_appid = $this->config['wx_house_appid'];
                    $component_secret = $this->config['wx_house_appsecret'];
                    D('Access_token_common_expires')->api_start_push_ticket($component_appid, $component_secret);
                }
                $this->assign('url', '');
                $this->assign('api_component_error_result',$result);
            }
        }

        if($weixin_bind){
            $weixin_bind['qrcode_url'] = $this->config['site_url'].'/index.php?c=Image&a=wx_image_download&url='.urlencode($weixin_bind['qrcode_url']).'&path='.$this->property_id;
        }

        $this->assign('bind', $weixin_bind);
        $this->display();
    }



    public function menu()
    {

        $weixin = D('Weixin_bind')->get_account_type($this->property_id, 2);
        if (isset($weixin['code']) && $weixin['code'] > 0) {
            $diymenus = D('House_diymenu_class')->where(array('mer_id' => $this->property_id, 'type' => 1))->order('sort ASC')->select();
            $lists = array();
            foreach ($diymenus as $diy) {
                $diy['other_wxapp_pagepath']=htmlspecialchars_decode($diy['other_wxapp_pagepath'],ENT_QUOTES);
                if ($diy['pid']) {
                    if (!isset($lists[$diy['pid']]['list'])) {
                        $lists[$diy['pid']]['list'] = array(1 => $diy);
                    } else {
                        $lists[$diy['pid']]['list'][] = $diy;
                    }
                } else {
                    if (isset($lists[$diy['id']])) {
                        $lists[$diy['id']] = array_merge($lists[$diy['id']], $diy);
                    } else {
                        $lists[$diy['id']] = $diy;
                    }
                }
            }
            $dlists = array();
            $i = 0;
            foreach ($lists as $l) {
                $dlists[++$i] = $l;
            }
            $this->assign('dlists', $dlists);
        }
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->assign('weixin', $weixin);
        $this->display();
    }

    public function savemenu()
    {

        $data = isset($_POST['custommenu']) ? $_POST['custommenu'] : array();
        $HouseDiyDB = D('House_diymenu_class');
        $diymenus = $HouseDiyDB->where(array('mer_id' => $this->property_id, 'type' => 1))->select();
        $ids = array();
        foreach ($diymenus as $diy) {
            $ids[$diy['id']] = $diy['id'];
        }

        foreach ($data as $index => $val) {
            // $val['url'] = htmlspecialchars_decode($val['url']);
            // if ($val['url'] && !strstr($val['url'], $this->config['site_url'])) exit(json_encode(array('errcode' => 1, 'errmsg' => 'URL地址不合法')));
            unset($val['type']);
            $val['is_show'] = 1;
            $val['title']=trim($val['title']);
            if ($index > 10) {//二级菜单
                $pindex = $index / 10;
                if ($val['title'] && isset($data[$pindex]['id']) && $data[$pindex]['id']) {
                    if ($val['id'] && ($diymenu = $HouseDiyDB->where(array('mer_id' => $this->property_id, 'id' => $val['id'], 'type' => 1))->find())) {
                        $id = $val['id'];
                        unset($ids[$val['id']]);
                        unset($val['id']);
                        $val['type'] = 1;
                        $val['pid'] = $data[$pindex]['id'];
                        $val['url'] = htmlspecialchars_decode($val['url']);
                        if(!empty($val['wxsys']) && ($val['wxsys']=='miniprogram')){
                            //跳转小程序
                            $val['url']='';
                            $val['keyword']='';
                            $val['wxapp_appid']=htmlspecialchars($val['wxapp_appid'],ENT_QUOTES);
                            $val['wxapp_pagepath']=htmlspecialchars($val['wxapp_pagepath'],ENT_QUOTES);
                        }
                        $HouseDiyDB->where(array('mer_id' => $this->property_id, 'id' => $id, 'type' => 1))->save($val);
                    } else {
                        unset($val['id']);
                        $val['mer_id'] = $this->property_id;
                        $val['pid'] = $data[$pindex]['id'];
                        $val['type'] = 1;
                        $val['url'] = htmlspecialchars_decode($val['url']);
                        if(!empty($val['wxsys']) && ($val['wxsys']=='miniprogram')){
                            //跳转小程序
                            $val['url']='';
                            $val['keyword']='';
                            $val['wxapp_appid']=htmlspecialchars($val['wxapp_appid'],ENT_QUOTES);
                            $val['wxapp_pagepath']=htmlspecialchars($val['wxapp_pagepath'],ENT_QUOTES);
                        }
                        $HouseDiyDB->add($val);
                    }
                } elseif (empty($val['title']) && isset($data[$pindex]['id']) && $data[$pindex]['id']) {
                    unset($ids[$val['id']]);
                    $HouseDiyDB->where(array('mer_id' => $this->property_id, 'id' => $val['id'], 'type' => 1))->delete();
                }
            } else {//一级菜单
                if ($val['title']) {
                    if ($val['id'] && ($diymenu = D('House_diymenu_class')->where(array('mer_id' => $this->property_id, 'id' => $val['id'], 'type' => 1))->find())) {
                        $id = $val['id'];
                        unset($ids[$val['id']]);
                        unset($val['id']);
                        $val['type'] = 1;
                        $val['url'] = htmlspecialchars_decode($val['url']);
                        if(!empty($val['wxsys']) && ($val['wxsys']=='miniprogram')){
                            //跳转小程序
                            $val['url']='';
                            $val['keyword']='';
                            $val['wxapp_appid']=htmlspecialchars($val['wxapp_appid'],ENT_QUOTES);
                            $val['wxapp_pagepath']=htmlspecialchars($val['wxapp_pagepath'],ENT_QUOTES);
                        }
                        $HouseDiyDB->where(array('mer_id' => $this->property_id, 'id' => $id, 'type' => 1))->save($val);
                    } else {
                        unset($val['id']);
                        $val['mer_id'] = $this->property_id;
                        $val['type'] = 1;
                        $val['url'] = htmlspecialchars_decode($val['url']);
                        if(!empty($val['wxsys']) && ($val['wxsys']=='miniprogram')){
                            //跳转小程序
                            $val['url']='';
                            $val['keyword']='';
                            $val['wxapp_appid']=htmlspecialchars($val['wxapp_appid'],ENT_QUOTES);
                            $val['wxapp_pagepath']=htmlspecialchars($val['wxapp_pagepath'],ENT_QUOTES);
                        }
                        $data[$index]['id'] = $HouseDiyDB->add($val);
                    }
                } else {
                    unset($ids[$val['id']]);
                    $HouseDiyDB->where(array('mer_id' => $this->property_id, 'id' => $val['id'], 'type' => 1))->delete();
                }
            }
        }
        if ($ids) $HouseDiyDB->where(array('mer_id' => $this->property_id, 'id' => array('in', $ids), 'type' => 1))->delete();
        $result = $this->class_send();
        exit($result);
    }

    public function testmenu()
    {
        $token_data = D('Weixin_bind')->get_access_token($this->property_id, 2);
        'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=ACCESS_TOKEN';
        $data = file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$token_data['access_token']);

        $data = json_decode($data, true);
        echo "<pre/>";
        print_r($data);
    }
    public function class_send()
    {
        $token_data = D('Weixin_bind')->get_access_token($this->property_id, 2);
        if ($token_data['errcode']) exit(json_encode($token_data));
        $HouseDiyDB = D('House_diymenu_class');
        $class = $HouseDiyDB->where(array('pid' => 0, 'mer_id' => $this->property_id, 'type' => 1))->limit(3)->order('sort asc')->select();
        $kcount = $HouseDiyDB->where(array('pid' => 0, 'mer_id' => $this->property_id, 'type' => 1))->count('id');
        $k = 1;
        $data = '{"button":[';
        foreach ($class as $key => $vo) {
            //主菜单
            $data .= '{"name":"'.$vo['title'].'",';
            $c = $HouseDiyDB->where(array('pid'=>$vo['id'], 'mer_id' => $this->property_id, 'type' => 1))->limit(5)->order('sort asc')->select();
            $count = $HouseDiyDB->where(array('pid'=>$vo['id'], 'mer_id' => $this->property_id, 'type' => 1))->count('id');
            //子菜单
            if ($c != false) {
                $data .= '"sub_button":[';
            } else {
                if($vo['wxsys']=='miniprogram'){
                    $vo['wxapp_pagepath']=htmlspecialchars_decode($vo['wxapp_pagepath'],ENT_QUOTES);
                    $data .= '"type":"miniprogram", "url":"' . $this->config['site_url'] . '/wap.php", "pagepath":"' . $vo['wxapp_pagepath'] . '", "appid":"' . $vo['wxapp_appid'] . '"';
                }else if ($vo['url']) {
                    $vo['url'] = str_replace('&amp;', '&', $vo['url']);
                    $data .= '"type":"view","url":"'.$vo['url'].'"';
                } elseif($vo['keyword']) {
                    $data .='"type":"click","key":"'.$vo['keyword'].'"';
                } elseif($vo['wxsys'] != 0 && $vo['wxsys'] != 1) {
                    $data .='"type":"'.$vo['wxsys'].'","key":"'.$vo['wxsys'].'"';
                }
            }
            $i=1;
            foreach ($c as $voo) {
                if ($i == $count) {
                    if($voo['wxsys']=='miniprogram'){
                        $voo['wxapp_pagepath']=htmlspecialchars_decode($voo['wxapp_pagepath'],ENT_QUOTES);
                        $data .= '{"type":"miniprogram","name":"'.$voo['title'].'","url":"' . $this->config['site_url'] . '/wap.php", "pagepath":"' . $voo['wxapp_pagepath'] . '", "appid":"' . $voo['wxapp_appid'] . '"}';
                    }else if ($voo['url']) {
                        $voo['url'] = str_replace('&amp;', '&', $voo['url']);
                        $data .= '{"type":"view","name":"'.$voo['title'].'","url":"'.$voo['url'].'"}';
                    } elseif($voo['keyword']) {
                        $data .= '{"type":"click","name":"'.$voo['title'].'","key":"'.$voo['keyword'].'"}';
                    } elseif($voo['wxsys'] != 0 && $voo['wxsys'] != 1) {
                        $data .= '{"type":"'.$voo['wxsys'].'","name":"'.$voo['title'].'","key":"'.$voo['wxsys'].'"}';
                    }
                } else {
                    if($voo['wxsys']=='miniprogram'){
                        $voo['wxapp_pagepath']=htmlspecialchars_decode($voo['wxapp_pagepath'],ENT_QUOTES);
                        $data .= '{"type":"miniprogram","name":"'.$voo['title'].'","url":"' . $this->config['site_url'] . '/wap.php", "pagepath":"' . $voo['wxapp_pagepath'] . '", "appid":"' . $voo['wxapp_appid'] . '"},';
                    }else if ($voo['url']) {
                        $data .= '{"type":"view","name":"'.$voo['title'].'","url":"'.$voo['url'].'"},';
                    } elseif($voo['keyword']) {
                        $data .= '{"type":"click","name":"'.$voo['title'].'","key":"'.$voo['keyword'].'"},';
                    } elseif($voo['wxsys'] != 0 && $voo['wxsys'] != 1) {
                        $data .= '{"type":"'.$voo['wxsys'].'","name":"'.$voo['title'].'","key":"'.$voo['wxsys'].'"},';
                    }
                }
                $i++;
            }
            if ($c != false) {
                $data .= ']';
            }

            if ($k == $kcount) {
                $data .= '}';
            } else {
                $data .= '},';
            }
            $k++;
        }
        $data .= ']}';


        file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$token_data['access_token']);
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$token_data['access_token'];
        import('ORG.Net.Http');
        $rt = Http::curlPost($url, $data);
        if ($rt['errcode']) {
            return json_encode($rt);
        } else {
            return json_encode(array('errcode' => 0, 'errmsg' => '自定义菜单生产成功'));
        }
    }


    public function get_url()
    {
        import('ORG.Net.Http');
        $result = $_SESSION['component_access_token'];
        if ($result && $result[0] > time()) {
            $result['component_access_token'] = $result[1];
        } else {
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $data = array('component_appid' => $this->config['wx_house_appid'], 'component_appsecret' => $this->config['wx_house_appsecret'], 'component_verify_ticket' => $this->config['wx_house_componentverifyticket']);
            $result = Http::curlPost($url, json_encode($data));
            if (empty($result['errcode'])) {
                $_SESSION['component_access_token'] = array($result['expires_in'] + time(), $result['component_access_token']);
            } else {
                exit(json_encode(array('err_code' => 1, 'err_msg' => '获取授权地址失败')));
            }
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' . $result['component_access_token'];
        $data = array('component_appid' => $this->config['wx_house_appid']);
        $auth_code = Http::curlPost($url, json_encode($data));
        if (empty($auth_code['errcode'])) {
            $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->config['wx_house_appid'].'&pre_auth_code='.$auth_code['pre_auth_code'].'&redirect_uri=' . urlencode($this->config['site_url'] . '/shequ.php?g=House&c=NewWeixin&a=auth_back');
            exit(json_encode(array('err_code' => 0, 'err_msg' => $url)));
        }
        exit(json_encode(array('err_code' => 1, 'err_msg' => '获取授权地址失败')));
    }

    public function auth_back()
    {
        fdump_api(['微信公众号授权反馈'.__LINE__,$_REQUEST,$_POST],'NewWeixin/authBackLog',1);
        if (isset($_GET['auth_code']) && isset($_GET['expires_in'])){

            //获取 component_access_token
            import('ORG.Net.Http');

            $result = $_SESSION['component_access_token'];
            if ($result && $result[0] > time()) {
                $result['component_access_token'] = $result[1];
            } else {
                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
                $data = array('component_appid' => $this->config['wx_house_appid'], 'component_appsecret' => $this->config['wx_house_appsecret'], 'component_verify_ticket' => $this->config['wx_house_componentverifyticket']);
                $result = Http::curlPost($url, json_encode($data));
                if ($result['errcode']) {
                    $this->assign('errmsg', $result['errmsg']);
                    $this->display('fail');
                    exit();
                }
            }

            //获取 authorizer_appid
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . $result['component_access_token'];
            $data = array('component_appid' => $this->config['wx_house_appid'], 'authorization_code' => $_GET['auth_code']);
            $result1 = Http::curlPost($url, json_encode($data));
            if ($result1['errcode']) {
                $this->assign('errmsg', $result1['errmsg']);
                $this->display('fail');
                exit();
            }
            $_SESSION['authorizer_access_token'] = array($result1['authorization_info']['expires_in'] + time(), $result1['authorization_info']['authorizer_access_token']);
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=' . $result['component_access_token'];
            $data = array('component_appid' => $this->config['wx_house_appid'], 'authorizer_appid' => $result1['authorization_info']['authorizer_appid']);
            $result2 = Http::curlPost($url, json_encode($data));
            if ($result2['errcode']) {
                $this->assign('errmsg', $result2['errmsg']);
                $this->display('fail');
                exit();
            } else {
                $data = array();
                $data = $result2['authorizer_info'];
                $data['service_type_info'] = $data['service_type_info']['id'];
                $data['verify_type_info'] = $data['verify_type_info']['id'];
                $pre = '';
                $func_info = '';
                foreach ($result2['authorization_info']['func_info'] as $val) {
                    $func_info .= $pre . $val['funcscope_category']['id'];
                    $pre = ',';
                }
                if ($_GET['property']) {
                    $mer_id = $_GET['property'];
                } else {
                    $mer_id = $this->property_id;
                }
                $data['func_info'] = $func_info;
                $data['authorizer_appid'] = $result1['authorization_info']['authorizer_appid'];
                $data['authorizer_refresh_token'] = $result1['authorization_info']['authorizer_refresh_token'];
                $data['mer_id'] = $mer_id;
                $data['type'] = 2;

                if ($is_bind = D('Weixin_bind')->where(array('user_name' => $data['user_name'], 'type' => 2))->find()) {
                    if ($is_bind['mer_id'] != $mer_id) {
                        $this->assign('errmsg', '该微信公众号已在其他物业完成绑定，无法绑定到当前物业！');
                        $this->display('fail');
                        exit();
                    }
                }
                if ($weixin_bind = D('Weixin_bind')->where(array('mer_id' => $mer_id, 'type' => 2))->find()) {
                    D('Weixin_bind')->where(array('mer_id' => $mer_id))->data($data)->save();
                } else {
                    D('Weixin_bind')->data($data)->add();
                }
                // 绑定了物业自有公众号 清除原有小区推广二维码
                $save_data = ['qrcode_id' => 0];
                M('House_village')->where(['property_id' => $mer_id])->save($save_data);
                $this->display('success');
            }
        } else {
            $this->assign('errmsg', '不合法的请求授权');
            $this->display('fail');
        }
    }

    private function _get_sys($type='',$key='')
    {
        $wxsys 	= array(
            '扫码带提示',
            '扫码推事件',
            '系统拍照发图',
            '拍照或者相册发图',
            '微信相册发图',
            '发送位置',
        );

        if($type == 'send'){
            $wxsys 	= array(
                '扫码带提示'=>'scancode_waitmsg',
                '扫码推事件'=>'scancode_push',
                '系统拍照发图'=>'pic_sysphoto',
                '拍照或者相册发图'=>'pic_photo_or_album',
                '微信相册发图'=>'pic_weixin',
                '发送位置'=>'location_select',
            );
            return $wxsys[$key];
            exit;
        }
        return $wxsys;
    }

    /**
     * 创建文本回复
     */
    public function reply_txt()
    {
        $keywordDataBase = D('House_keyword');
        if (IS_POST) {
            $pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
            $keyword = isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '';
            $content = isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
            $this->assign('keyword', array('pigcms_id' => $pigcms_id, 'content' => $content, 'keyword' => $keyword));
            if (empty($keyword)) {
                $this->assign('error', '关键词 不可为空白.');
                $this->display();
                exit();
            }
            if (empty($content)) {
                $this->assign('error', '回复内容 不可为空白.');
                $this->display();
                exit();
            }

            if ($keyobj = $keywordDataBase->where(array('mer_id' => $this->property_id, 'content' => $keyword, 'type' => 1))->find()) {
                if ($keyobj['pigcms_id'] != $pigcms_id) {
                    $this->assign('error', '关键词 "'.$keyword.'" 已被取用.');
                    $this->display();
                    exit();
                }
            }

            if ($pigcms_id && ($keywordArr = $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->find())) {

                $txt = D($keywordArr['table'])->where(array('pigcms_id' => $keywordArr['from_id'], 'mer_id' => $this->property_id, 'info_type' => 1))->find();
                if ($txt) {
                    D($keywordArr['table'])->where(array('pigcms_id' => $keywordArr['from_id'], 'mer_id' => $this->property_id, 'info_type' => 1))->save(array('content' => $content));
                    $txtid = $keywordArr['from_id'];
                } else {
                    $txtid = D($keywordArr['table'])->add(array('mer_id' => $this->property_id, 'content' => $content, 'info_type' => 1));
                }
                if (empty($txtid)) {
                    $this->assign('error', '创建失败');
                    $this->display();
                    exit();
                }
                $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->save(array('content' => $keyword, 'from_id' => $txtid, 'table' => 'House_text'));

            } else {

                $txtid = D('House_text')->add(array('mer_id' => $this->property_id, 'content' => $content, 'info_type' =>1));
                $keywordDataBase->add(array('mer_id' => $this->property_id, 'content' => $keyword, 'from_id' => $txtid, 'table' => 'House_text', 'type' => 1)) ;
            }
            $this->redirect(U('NewWeixin/txt',array('iframe'=>$_GET['iframe'])));
        } else {
            $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
            if ($keyword = $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->find()) {
                $keyword['keyword'] = $keyword['content'];
                $content = D($keyword['table'])->where(array('pigcms_id' => $keyword['from_id'], 'mer_id' => $this->property_id, 'info_type' => 1))->find();
                $keyword['content'] = $content ? $content['content'] : '';
                $this->assign('keyword', $keyword);
            }
        }
        $this->display();
    }

    public function del_txt()
    {
        $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
        $keywordDataBase = D('House_keyword');
        if ($keyword = $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->find()) {
            $content = D($keyword['table'])->where(array('pigcms_id' => $keyword['from_id'], 'mer_id' => $this->property_id, 'type' => 1))->delete();
            $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->delete();
            $this->redirect(U('NewWeixin/txt',array('iframe'=>$_GET['iframe'])));
        } else {
            $this->error('不合法的请求');
        }
    }

    /**
     * 文本回复列表
     */
    public function txt()
    {
        $keywordDataBase = D('House_keyword');
        $count = $keywordDataBase->where(array('mer_id' => $this->property_id, 'table' => 'House_text', 'type' => 1))->count('pigcms_id');
        import('@.ORG.merchant_page');
        $p = new Page($count, 10);
        $list = $keywordDataBase->where(array('mer_id' => $this->property_id, 'table' => 'House_text', 'type' => 1))->order('pigcms_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        $temp = $result = array();
        foreach ($list as $l) {
            $temp[] = $l['from_id'];
        }
        $tmp = array();
        if ($temp) {
            $texts = D('House_text')->where(array('mer_id' => $this->property_id, 'pigcms_id' => array('in', $temp), 'info_type' => 1))->select();
            foreach ($texts as $t) {
                $tmp[$t['pigcms_id']] = $t;
            }
        }
        foreach ($list as &$v) {
            $v['keyword'] = $v['content'];
            $v['content'] = isset($tmp[$v['from_id']]) ? $tmp[$v['from_id']]['content'] : '';
        }
        $this->assign('lists', $list);
        $this->assign('page', $p->show());
        $this->display();
    }

    /**
     * 创建图文回复
     */
    public function reply_img()
    {
        $list = D('House_source_material')->where(array('mer_id' => $this->property_id, 'info_type' => 1))->order('pigcms_id DESC')->select();
        $it_ids = array();
        $temp = array();
        foreach ($list as $l) {
            foreach (unserialize($l['it_ids']) as $id) {
                if (!in_array($id, $it_ids)) $it_ids[] = $id;
            }
        }
        $result = array();
        $image_text = D('House_image_text')->field('pigcms_id, title')->where(array('pigcms_id' => array('in', $it_ids), 'info_type' => 1))->order('pigcms_id asc')->select();
        foreach ($image_text as $txt) {
            $result[$txt['pigcms_id']] = $txt;
        }
        foreach ($list as &$l) {
            $l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
            foreach (unserialize($l['it_ids']) as $id) {
                $l['list'][] = isset($result[$id]) ? $result[$id] : array();
            }
        }
        $this->assign('list', $list);
        $keywordDataBase = D('House_keyword');
        if (IS_POST) {
            $pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
            $keyword = isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '';
            $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
            $this->assign('keyword', array('pigcms_id' => $pigcms_id, 'from_id' => $source_id, 'keyword' => $keyword));
            if (empty($keyword)) {
                $this->assign('error', '关键词 不可为空白.');
                $this->display();
                exit();
            }
            if ($keyobj = $keywordDataBase->where(array('mer_id' => $this->property_id, 'content' => $keyword, 'type' => 1))->find()) {
                if ($keyobj['pigcms_id'] != $pigcms_id) {
                    $this->assign('error', '关键词 "'.$keyword.'" 已被取用.');
                    $this->display();
                    exit();
                }
            }
            if (!($obj = D('House_source_material')->where(array('pigcms_id' => $source_id, 'mer_id' => $this->property_id, 'info_type' => 1))->find())) {
                $this->assign('error', '选择了不存在的文图.');
                $this->display();
                exit();
            }
            if ($pigcms_id && ($keyword = $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->find())) {
                //图文回复-编辑 权限
//                if (!in_array(188, $this->house_session['menus'])) {
//                    $this->assign('error', '对不起，您没有权限执行此操作');
//                    $this->display();
//                    exit();
//                }

                $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->save(array('content' => $keyword, 'from_id' => $source_id, 'table' => 'House_source_material'));
            } else {
                //图文回复-添加 权限
//                if (!in_array(187, $this->house_session['menus'])) {
//                    $this->assign('error', '对不起，您没有权限执行此操作');
//                    $this->display();
//                    exit();
//                }

                $keywordDataBase->add(array('mer_id' => $this->property_id, 'content' => $keyword, 'from_id' => $source_id, 'table' => 'House_source_material', 'type' => 1));
            }
            $this->redirect(U('NewWeixin/img',array('iframe'=>$_GET['iframe'])));
        } else {
            $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
            if ($keyword = $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->find()) {
                $keyword['keyword'] = $keyword['content'];
                $this->assign('keyword', $keyword);
            }
        }
        $this->display();
    }


    public function del_img()
    {
        $keywordDataBase = D('House_keyword');
        $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
        if ($keyword = $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->find()) {
            $keywordDataBase->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'type' => 1))->delete();
            $this->redirect(U('NewWeixin/img',array('iframe'=>$_GET['iframe'])));
        } else {
            $this->error('不合法的请求');
        }
    }

    public function img()
    {
        $keywordDataBase = D('House_keyword');
        $count = $keywordDataBase->where(array('mer_id' => $this->property_id, 'table' => 'House_source_material', 'type' => 1))->count('pigcms_id');
        import('@.ORG.merchant_page');
        $p = new Page($count, 10);
        $list = $keywordDataBase->where(array('mer_id' => $this->property_id, 'table' => 'House_source_material', 'type' => 1))->order('pigcms_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        $ids = array();
        foreach ($list as $l) {
            $ids[] = $l['from_id'];
        }

        $sources = D('House_source_material')->where(array('pigcms_id' => array('in', $ids), 'info_type' => 1))->order('pigcms_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        $it_ids = array();
        $temp = array();

        foreach ($sources as $so) {
            foreach (unserialize($so['it_ids']) as $id) {
                if (!in_array($id, $it_ids)) $it_ids[] = $id;
            }
        }
        $result = array();
        $image_text = D('House_image_text')->field('pigcms_id, title')->where(array('pigcms_id' => array('in', $it_ids), 'info_type' => 1))->order('pigcms_id asc')->select();
        foreach ($image_text as $txt) {
            $result[$txt['pigcms_id']] = $txt;
        }

        $tuwen_list = array();
        foreach ($sources as $s) {
            $s['dateline'] = date('Y-m-d H:i:s', $s['dateline']);
            foreach (unserialize($s['it_ids']) as $id) {
                $s['list'][] = isset($result[$id]) ? $result[$id] : array();
            }
            $tuwen_list[$s['pigcms_id']] = $s;
        }

        foreach ($list as &$li) {
            $li['list'] = isset($tuwen_list[$li['from_id']]['list']) ? $tuwen_list[$li['from_id']]['list'] : array();
        }


        $this->assign('lists', $list);
        $this->assign('page', $p->show());
        $this->display();
    }

    public function auto()
    {

        $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
        if ($type == 0) {
            $this->assign('tips', '关注时');
        } elseif ($type == 1) {
            $this->assign('tips', '无效词');
        } else {
            $this->assign('tips', '图片');
        }
        $this->assign('type', $type);

        $otherDataBase = D("House_reply_other");
        $materialDataBase = D('House_source_material');
        $imageDataBase = D('House_image_text');
        if (IS_POST) {

            $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
            $reply_type = isset($_POST['reply_type']) ? intval($_POST['reply_type']) : 0;
            $is_open = isset($_POST['is_open']) ? intval($_POST['is_open']) : 0;
            $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
            $content = isset($_POST['content']) ? $_POST['content'] : '';
            if (empty($reply_type) && empty($content)) {
                $this->assign('error', '回复内容 不可为空白.');
                $this->display();
                exit();
            }
            if ($reply_type == 1) {
                $data = $materialDataBase->where(array('mer_id' => $this->property_id, 'pigcms_id' => $source_id, 'info_type' => 1))->find();
                if (empty($data)) {
                    $this->assign('error', '选择了不存在的文图.');
                    $this->display();
                    exit();
                }
            }
            if ($other = $otherDataBase->where(array('mer_id' => $this->property_id, 'type' => $type, 'info_type' => 1))->find()) {
                $otherDataBase->where(array('mer_id' => $this->property_id, 'type' => $type, 'info_type' => 1))->data(array('content' => $content, 'from_id' => $source_id, 'reply_type' => $reply_type, 'is_open' => $is_open))->save();
                $this->success('更新成功');
            } else {
                $otherDataBase->data(array('mer_id' => $this->property_id, 'type' => $type, 'content' => $content, 'from_id' => $source_id, 'reply_type' => $reply_type, 'is_open' => $is_open, 'info_type' => 1))->add();
                $this->success('创建成功');
            }

        } else {

            $list = $materialDataBase->where(array('mer_id' => $this->property_id, 'info_type' => 1))->order('pigcms_id DESC')->select();
            $it_ids = array();
            $temp = array();
            foreach ($list as $l) {
                foreach (unserialize($l['it_ids']) as $id) {
                    if (!in_array($id, $it_ids)) $it_ids[] = $id;
                }
            }
            $result = array();
            $image_text = $imageDataBase->field('pigcms_id, title')->where(array('pigcms_id' => array('in', $it_ids), 'info_type' => 1))->order('pigcms_id asc')->select();
            foreach ($image_text as $txt) {
                $result[$txt['pigcms_id']] = $txt;
            }
            foreach ($list as &$l) {
                $l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
                foreach (unserialize($l['it_ids']) as $id) {
                    $l['list'][] = isset($result[$id]) ? $result[$id] : array();
                }
            }
            $this->assign('list', $list);
            $other = $otherDataBase->where(array('mer_id' => $this->property_id, 'type' => $type, 'info_type' => 1))->find();
            $this->assign('other', $other);
            $this->display();
        }
    }

    // 图文素材
    public function article()
    {
        $material_database = D('House_source_material');
        $count = $material_database->where(array('mer_id' => $this->property_id, 'info_type' => 1))->count('pigcms_id');
        import('@.ORG.merchant_page');
        $p = new Page($count, 20);
        $list = $material_database->where(array('mer_id' => $this->property_id, 'info_type' => 1))->order('pigcms_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        $it_ids = array();
        $temp = array();
        foreach ($list as $l) {
            foreach (unserialize($l['it_ids']) as $id) {
                if (!in_array($id, $it_ids)) $it_ids[] = $id;
            }
        }
        $result = array();
        $image_text = D('House_image_text')->field('pigcms_id, title ,read_quantity')->where(array('pigcms_id' => array('in', $it_ids), 'info_type' => 1))->order('pigcms_id asc')->select();
        foreach ($image_text as $txt) {
            $result[$txt['pigcms_id']] = $txt;
        }
        foreach ($list as &$l) {
            $l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
            foreach (unserialize($l['it_ids']) as $id) {
                $l['list'][] = isset($result[$id]) ? $result[$id] : array();
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $p->show());
        $this->display();
    }

    public function select_img()
    {
        $count = D('House_source_material')->where(array('mer_id' => $this->property_id, 'info_type' => 1))->count('pigcms_id');
        $p = new Page($count, 10);
        $image_text = D('House_image_text')->field(true)->where(array('mer_id' => $this->property_id, 'info_type' => 1))->order('pigcms_id asc')->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign('list', $image_text);
        $this->assign('page', $p->show());
        $this->display();
    }

    public function del_image()
    {
        $material_database = D('House_source_material');
        $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
        if ($data = $material_database->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->find()) {
            if ($data['type'] == 0) {
                $it_ids = unserialize($data['it_ids']);
                $id = isset($it_ids[0]) ? intval($it_ids[0]) : 0;
                D('House_image_text')->where(array('pigcms_id' => $id, 'mer_id' => $this->property_id, 'info_type' => 1))->delete();
            }
            $material_database->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->delete();
            $this->error('删除成功', U('NewWeixin/article',array('iframe'=>$_GET['iframe'])));
        } else {
            $this->error('不合法的操作');
        }
    }

    public function one()
    {
        $image_database = D('House_image_text');
        $material_database = D('House_source_material');
        if (IS_POST) {
            $pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
            $thisid = isset($_POST['thisid']) ? intval($_POST['thisid']) : 0;
            $data['content'] = isset($_POST['content']) ? fulltext_filter($_POST['content']) : '';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['author'] = isset($_POST['author']) ? htmlspecialchars($_POST['author']) : '';
            $data['url'] = isset($_POST['url']) ? ($_POST['url']) : '';
            $data['url_title'] = isset($_POST['url_title']) ? htmlspecialchars($_POST['url_title']) : '';
            $data['cover_pic'] = isset($_POST['cover_pic']) ? htmlspecialchars($_POST['cover_pic']) : '';
            $data['digest'] = isset($_POST['digest']) ? htmlspecialchars($_POST['digest']) : '';
            $data['is_show'] = isset($_POST['is_show']) ? intval($_POST['is_show']) : 0;
            $data['classid'] = isset($_POST['classid']) ? intval($_POST['classid']) : 0;
            $data['classname'] = isset($_POST['classname']) ? htmlspecialchars($_POST['classname']) : '';
            if (empty($data['classname'])) {
                $data['classid'] = 0;
            }
            if (empty($data['title'])) {
                $this->error('标题不能为空！');
            }
            if (empty($data['cover_pic'])) {
                $this->error('必须得有封面图！');
            }
            if (empty($data['content'])) {
                $this->error('内容不能为空！');
            }
            $data['dateline'] = time();
            $data['mer_id'] = $this->property_id;
            $data['info_type'] = 1;
            if ($pigcms_id && $thisid) {
                //图文素材-编辑 权限
//                if (!in_array(192, $this->house_session['menus'])) {
//                    $this->error('对不起，您没有权限执行此操作');
//                }

                if ($image_database->where(array('pigcms_id' => $thisid, 'mer_id' => $this->property_id, 'info_type' => 1))->data($data)->save()) {
                    $material_database->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->data(array('it_ids' => serialize(array($thisid)), 'mer_id' => $this->property_id, 'dateline' => time()))->save();
                    $this->success('编辑成功！');
                } else {
                    $this->error('操作失败稍后重试！');
                }
            } else {
                //图文素材-添加 权限
//                if (!in_array(191, $this->house_session['menus'])) {
//                    $this->error('对不起，您没有权限执行此操作');
//                }

                if ($id = $image_database->data($data)->add()) {
                    $material_database->data(array('it_ids' => serialize(array($id)), 'mer_id' => $this->property_id, 'dateline' => time(), 'info_type' => 1))->add();
                    $this->success('新增成功！');
                } else {
                    $this->error('操作失败稍后重试！');
                }
            }

        } else {
            $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
            $image_text = array('title' => '', 'cover_pic' => '', 'author' => '', 'content' => '', 'digest' => '', 'url' => '', 'dateline' => time(), 'pigcms_id' => 0);
            if ($data = $material_database->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->find()) {
                $it_ids = unserialize($data['it_ids']);
                $id = isset($it_ids[0]) ? intval($it_ids[0]) : 0;
                $image_text = $image_database->where(array('pigcms_id' => $id, 'mer_id' => $this->property_id, 'info_type' => 1))->find();
            }
            $this->assign('pigcms_id', $pigcms_id);
            $this->assign('image_text', $image_text);
            $this->display();
        }
    }


    public function multi()
    {
        if (IS_POST) {
            $ids = isset($_POST['imgids']) ? htmlspecialchars($_POST['imgids']) : '';
            $ids = explode(",", $ids);
            if (count($ids) > 10) {
                $this->error('最多十条图文');
            }
            $material_database = D('House_source_material');
            $pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
            if ($pigcms_id && ($data = $material_database->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->find())) {

                $material_database->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->property_id, 'info_type' => 1))->data(array('it_ids' => serialize($ids), 'mer_id' => $this->property_id, 'dateline' => time(), 'type' => 2))->save();
                $this->success('编辑成功！');
            } else {
                $material_database->data(array('it_ids' => serialize($ids), 'mer_id' => $this->property_id, 'dateline' => time(), 'type' => 2, 'info_type' => 1))->add();
                $this->success('创建成功！');
            }
        } else {
            $this->display();
        }
    }

    public function diytool()
    {
        $this->display();
    }
    public function ajax_upload_pic(){
        if($_FILES['imgFile']['error'] != 4){
            $image = D('Image')->handle($this->property_id, 'house', 1);
            if ($image['error']) {
                exit(json_encode($image));
            } else {
                $title = $image['title']['imgFile'];
                $house_image_class = new house_image();
                $url = $house_image_class->get_image_by_path($title);
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            }
        } else {
            exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
        }
    }
    public function ajax_del_pic(){
        $house_image_class = new house_image();
        $house_image_class->del_image_by_path($_POST['path']);
    }

    /**
     * 微信模板消息
     */
    public function template()
    {
        $where = array('property_id' => $this->property_id,'status'=>1);
        $list = M('Tempmsg')->where($where)->field(true)->select();
        $this->assign('list', $list);
        $weixin = D('Weixin_bind')->get_account_type($this->property_id,2);
        $this->assign('weixin', $weixin);
        $this->display();
    }

    /**
     * 添加模板消息
     */
    public function tpladd()
    {
        if (IS_POST) {
            $data = array();
            $data['tempkey'] = trim($_POST['tempkey']);
            $tmpKeyArr = explode(L_('（'),$data['tempkey']);
            $data['tempkey'] = trim($tmpKeyArr[0]);
            $data['name'] = trim($_POST['name']);
            $data['content'] = trim($_POST['content']);
            $data['topcolor'] = trim($_POST['topcolor']);
            $data['textcolor'] = trim($_POST['textcolor']);
            $data['status'] = intval($_POST['status']);
            $data['tempid'] = trim($_POST['tempid']);
            $data['property_id'] = $this->property_id?$this->property_id:0;
            $data['status'] = $data['tempid'] == '' ? 0 : $data['status'];
            $where = array(
                'tempkey' => $data['tempkey'],
                'mer_id' => $this->property_id
            );
            if($_POST['is_category']==1){
                $data['is_category'] = $_POST['is_category'];
                $data['key_word'] = trim($_POST['key_word']);
            }
            if($_POST['is_category']==1 && $data['tempid']){
                //查询我的模板列表
                $onlineTempAry = $this->getOnlineTempAry();
                $data['new_content'] = $onlineTempAry[$data['tempid']] ?? '';
            }
            if (M('Tempmsg')->where($where)->getField('id')) {
                M('Tempmsg')->where($where)->save($data);
            } else {
                M('Tempmsg')->add($data);
            }
            $this->success(L_('操作成功'), U('template',array('iframe'=>$_GET['iframe'])));
        } else {
            $this->assign('tplList', $this->tplArray());
            $this->display();
        }
    }
    public function getOnlineTempAry()
    {
        $access_token_array = D('Weixin_bind')->get_access_token($this->property_id, 2);
        if ($access_token_array['errcode']) {
            $this->error('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
        }
        $access_token = $access_token_array['access_token'];
        $url = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token='.$access_token;
        import('ORG.Net.Http');
        $http = new Http();
        $result = $http->curlGet($url);
        $result = json_decode($result,true);
        $result = $result['template_list']??[];
        $result = array_column($result,'content','template_id');
        return $result;
    }

    /**
     * 编辑模板消息
     */
    public function tpledit()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $tempmsg = M('Tempmsg')->where(array('id' => $id))->find();
        if (empty($tempmsg)) $this->error(L_('错误的数据信息'));
        if (IS_POST) {
            $data = array();
            $data['tempkey'] = trim($_POST['tempkey']);
            $tmpKeyArr = explode(L_('（'),$data['tempkey']);
            $data['tempkey'] = $tmpKeyArr[0];
            $data['name'] = trim($_POST['name']);
            $data['content'] = trim($_POST['content']);
            $data['topcolor'] = trim($_POST['topcolor']);
            $data['textcolor'] = trim($_POST['textcolor']);
            $data['status'] = intval($_POST['status']);
            $data['tempid'] = trim($_POST['tempid']);
            $data['status'] = $data['tempid'] == '' ? 0 : $data['status'];
            $where = array(
                'id' => $id,
//                'mer_id' => $this->property_id
            );
            if($_POST['is_category']==1){
                $data['is_category'] = $_POST['is_category'];
                $data['key_word'] = trim($_POST['key_word']);
            }
            if($data['tempid']){
                //查询我的模板列表
                $onlineTempAry = $this->getOnlineTempAry();
                $data['new_content'] = $onlineTempAry[$data['tempid']] ?? '';
            }
            M('Tempmsg')->where($where)->save($data);
            $this->success(L_('操作成功'), U('template',array('iframe'=>$_GET['iframe'])));
        } else {
            $this->assign('tplList', $this->tplArray());
            $this->assign('tempmsg', $tempmsg);
            $this->display();
        }
    }

    /**
     * 删除模板消息
     */
    public function tpldel()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $tempmsg = M('Tempmsg')->where(array( 'id' => $id))->delete();
        if ($tempmsg) {
            $this->success(L_('删除成功'));
        } else {
            $this->error(L_('错误的数据信息'));
        }
    }

    private function tplArray()
    {
        $tplList[L_('请选择模板编号')] = array('key' => L_('请选择模板编号'), 'name' => '', 'content' => '');
        $tplList['OPENTM201752540（订单支付成功通知）'] = array('key' => 'OPENTM201752540', 'name' => L_('订单支付成功通知'), 'content' => '
{{first.DATA}}
订单商品：{{keyword1.DATA}}
订单编号：{{keyword2.DATA}}
支付金额：{{keyword3.DATA}}
支付时间：{{keyword4.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM207498902（订单支付成功通知）'] = array('key' => 'OPENTM207498902', 'name' => L_('订单支付成功通知'), 'content' => '
{{first.DATA}}
用户名：{{keyword1.DATA}}
订单号：{{keyword2.DATA}}
订单金额：{{keyword3.DATA}}
商品信息：{{keyword4.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM201682460（订单生成通知）'] = array('key' => 'OPENTM201682460', 'name' => L_('订单生成通知'), 'content' => '
{{first.DATA}}
时间：{{keyword1.DATA}}
商品名称：{{keyword2.DATA}}
订单号：{{keyword3.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM202521011（订单完成通知）'] = array('key' => 'OPENTM202521011', 'name' => L_('订单完成通知'), 'content' => '
{{first.DATA}}
订单号：{{keyword1.DATA}}
完成时间：{{keyword2.DATA}}
{{remark.DATA}}');
        $tplList['TM00017（订单状态更新）'] = array('key' => 'TM00017', 'name' => L_('订单状态更新'), 'content' => '
{{first.DATA}}
订单编号：{{OrderSn.DATA}}
订单状态：{{OrderStatus.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM400166399	（项目状态提醒）'] = array('key' => 'OPENTM400166399', 'name' => L_('项目状态提醒'), 'content' => '
{{first.DATA}}
项目名称：{{keyword1.DATA}}
最新状态：{{keyword2.DATA}}
时间：{{keyword3.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM407216824	（服务状态提醒）'] = array('key' => 'OPENTM407216824', 'name' => L_('服务状态提醒'), 'content' => '
{{first.DATA}}
服务类型：{{keyword1.DATA}}
订单编号：{{keyword2.DATA}}
订单状态：{{keyword3.DATA}}
更新时间：{{keyword4.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM408101810	（来访通知）'] = array('key' => 'OPENTM408101810', 'name' => L_('来访通知'), 'content' => '
{{first.DATA}}
访客名称：{{keynote1.DATA}}
访客电话：{{keynote2.DATA}}
来访时间：{{keynote3.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM416545460（门禁提醒）'] = array('key' => 'OPENTM416545460', 'name' => L_('门禁提醒'), 'content' => '
{{first.DATA}}
姓名：{{keyword1.DATA}}
工号：{{keyword2.DATA}}
时间：{{keyword3.DATA}}
地点名称：{{keyword4.DATA}}
出入类型：{{keyword5.DATA}}
{{remark.DATA}}');
        $tplList['TM01008（缴费提醒通知）'] = array('key' => 'TM01008', 'name' => L_('缴费提醒通知'), 'content' => '
{{first.DATA}}
收费单位：{{keynote1.DATA}}
缴费账号：{{keynote2.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM417740354（审批抄送提醒）'] = array('key' => 'OPENTM417740354', 'name' => L_('审批抄送提醒'), 'content' => '
{{first.DATA}}
流程类型：{{keyword1.DATA}}
申请人：{{keyword2.DATA}}
申请时间：{{keyword3.DATA}}
{{remark.DATA}}');
        $tplList['OPENTM411328351（车辆缴费提醒）'] = array('key' => 'OPENTM411328351', 'name' => L_('车辆缴费提醒'), 'content' => '
{{first.DATA}}
车牌号：{{keyword1.DATA}}
缴费类型：{{keyword2.DATA}}
到期日：{{keyword3.DATA}}
提醒时间：{{keyword4.DATA}}
{{remark.DATA}}');
        $tplList['42822（缴费成功通知）'] = array('key' => '42822', 
            'name' => L_('缴费成功通知'), 
            'is_category' => 1, 
            'key_word' => '户名,地址,账单类型,缴费方式,商家名称', 
            'content' => '
户名：{{thing8.DATA}}           
地址：{{thing17.DATA}}               
账单类型：{{thing9.DATA}}               
缴费金额：{{amount3.DATA}}               
商家名称：{{thing10.DATA}}');
        $tplList['42804（物业费用账单缴纳提醒）'] = array('key' => '42804', 
            'name' => L_('物业费用账单缴纳提醒'), 
            'is_category' => 1, 
            'key_word' => '业主姓名,地址,缴费类型,账单名称,账单金额', 
            'content' => '
业主姓名:{{thing2.DATA}}
地址:{{thing3.DATA}}
缴费类型:{{thing9.DATA}}
账单名称:{{thing10.DATA}}
账单金额:{{amount4.DATA}}');
        $tplList['48508（开门成功通知）'] = array('key' => '48508', 
            'name' => L_('开门成功通知'), 
            'is_category' => 1, 
            'key_word' => '门禁名称,姓名,身份,开门方式,开门时间', 
            'content' => '
门禁名称:{{thing1.DATA}}
姓名:{{thing3.DATA}}
身份:{{phrase2.DATA}}
开门方式:{{thing4.DATA}}
开门时间:{{thing5.DATA}}');
        $tplList['43837（停车缴费通知）'] = array('key' => '43837', 
            'name' => L_('停车缴费通知'), 
            'is_category' => 1, 
            'key_word' => '车牌号码,停车场,限停时间', 
            'content' => '
车牌号码:{{car_number2.DATA}}
停车场:{{thing3.DATA}}
限停时间:{{time9.DATA}}
储值卡余额:{{amount20.DATA}}');
        $tplList['43021（车辆进出通知）'] = array('key' => '43021', 
            'name' => L_('车辆进出通知'), 
            'is_category' => 1, 
            'key_word' => '车牌号,停车场名称,进出类型,进出时间', 
            'content' => '
车牌号:{{car_number2.DATA}}
停车场名称:{{thing3.DATA}}
进出类型:{{phrase6.DATA}}
进出时间:{{time4.DATA}}');
        $tplList['43728（访客来访通知）'] = array('key' => '43728', 
            'name' => L_('访客来访通知'), 
            'is_category' => 1, 
            'key_word' => '来访人员,联系电话,拜访时间', 
            'content' => '
来访人员:{{thing2.DATA}}
联系电话:{{phone_number10.DATA}}
拜访时间:{{time9.DATA}}');
        $tplList['43258（办公系统流程审批通知）'] = array('key' => '43258', 
            'name' => L_('办公系统流程审批通知'), 
            'is_category' => 1, 
            'key_word' => '流程名称,发起人,发起时间,房源名称,流程状态', 
            'content' => '
流程名称:{{thing2.DATA}}
发起人:{{thing9.DATA}}
发起时间:{{time10.DATA}}
房源名称:{{thing7.DATA}}');
        $tplList['44716（收到服务订单通知）'] = array('key' => '44716',
            'name' => L_('收到服务订单通知'),
            'is_category' => 1,
            'key_word' => '服务项目,订单类型,服务时间,服务地址',
            'content' => '
服务项目:{{thing12.DATA}}
订单类型:{{phrase2.DATA}}
服务时间:{{time13.DATA}}
服务地址:{{thing11.DATA}}');
        $tplList['43030（工单处理通知）'] = array('key' => '43030',
            'name' => L_('工单处理通知'),
            'is_category' => 1,
            'key_word' => '项目名称,工单类型,工单状态,处理时间,工单地址,处理原因',
            'content' => '
项目名称:{{thing22.DATA}}
工单类型:{{thing3.DATA}}
工单状态:{{thing10.DATA}}
处理时间:{{time11.DATA}}
工单地址:{{thing20.DATA}}
处理原因:{{thing14.DATA}}');
        return $tplList;
    }
}