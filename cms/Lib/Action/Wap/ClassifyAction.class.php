<?php
/*
 * 分类信息管理
 *
 */
class ClassifyAction extends BaseAction {

    private $uid = 0;

    public function __construct() {
        parent::__construct();
        $areaid = C('config.now_city');
        $Nowarea = $_SESSION['myArea' . $areaid];
        if (empty($Nowarea)) {
            $Nowarea = D('Area')->get_area_by_areaId($areaid);
            $_SESSION['myArea' . $areaid] = serialize($Nowarea);
        } else {
            $Nowarea = unserialize($Nowarea);
        }
        //导航条
        $wap_classifyslider = D('Slider')->get_slider_by_key('wap_classify', 4);
        $this->assign('classifyslider', $wap_classifyslider);
        //print_r($wap_classifyslider);
        $uid = !empty($this->user_session) ? $this->user_session['uid'] : 0;
        $this->uid = $uid > 0 ? $uid : 0;
        $this->assign('uid', $uid);
        $this->assign('Nowarea', $Nowarea);
    }

    public function index() {
		$cid = intval($_GET['cid']);
		if($cid){
			redirect(U('Classifynew/category',array('cat_id'=>$cid)));
		}else{
			redirect(U('Classifynew/index',$_GET));
		}
		
        $database_Classify_category = D('Classify_category');
        $Zcategorys = $database_Classify_category->field(true)->where(array('subdir' => 1, 'cat_status' => 1))->order('`cat_sort` DESC,`cid` ASC')->select();
        if (!empty($Zcategorys)) {
            $newtmp = array();
            foreach ($Zcategorys as $vv) {
                unset($vv['cat_field']);
                $subdir_info = $this->get_Subdirectory($vv['cid'], 1);
                if (!empty($subdir_info)) {
                    $vv['subdir'] = $subdir_info;
                    $newtmp[$vv['cid']] = $vv;
                }
            }
            $Zcategorys = $newtmp;
        }
        if( $this->user_session['uid'] && $this->config['open_rand_send']){
             $coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid']));
            $coupon_html && $this->assign('coupon_html',$coupon_html);
        }
        //分类信息首页
        $classify_index_ad = D('Adver')->get_adver_by_key('classify_index_ad', 3);

        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_classify_slider', 0);

        $wap_index_slider = array();
        foreach ($tmp_wap_index_slider as $key => $value) {
            $tmp_i = floor($key / 8);
            $wap_index_slider[$tmp_i][] = $value;
        }


        $this->assign('wap_classify_slider',$wap_index_slider);

        $this->assign('classify_index_ad', $classify_index_ad);
        $this->assign('Zcategorys', $Zcategorys);
        $this->display();
    }

    /*     * *个人中心** */
    public function myCenter() {
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录',U('Login/index'));
        }
        $now_user = D('User')->get_user($this->uid);
        if (!empty($now_user)) {
            $now_user['now_money'] = floatval($now_user['now_money']);
        }
        $this->assign('now_user', $now_user);
        $this->display();
    }

    /*     * *我的发布** */

    public function myfabu() {
        if (!($this->uid > 0)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
            exit();
        }
		redirect(U('Classifynew/my_fabu'));
        $wherestr = 'uid=' . $this->uid . ' AND status = 1';
        $userinputDb = M('Classify_userinput');
        $tmpdatas = $userinputDb->field('id,uid,cid,fcid,sub3dir,title,imgs,input1,input2,input3,input4,expand,updatetime,status,is_assure')->where($wherestr)->order('updatetime DESC')->select();

        $pass_data = $this->_get_fabu_format($tmpdatas);

        $wherestr = 'uid=' . $this->uid . ' AND status = 0';
        $tmpdatas = $userinputDb->field('id,uid,cid,fcid,sub3dir,title,imgs,input1,input2,input3,input4,expand,updatetime,status,is_assure')->where($wherestr)->order('updatetime DESC')->select();
        $unpass_data = $this->_get_fabu_format($tmpdatas);

        $now_user = D('User')->get_user($this->uid);
        if (!empty($now_user)) {
            $now_user['now_money'] = floatval($now_user['now_money']);
        }
        $this->assign('now_user', $now_user);
        $this->assign('pass_data', $pass_data);
        $this->assign('unpass_data', $unpass_data);
        $this->display();
    }

    private function _get_fabu_format($tmpdatas){
        $today = strtotime(date('Y-m-d') . " 00:00:00"); //今天
        $yesterday = $today - 86400; //昨天
        foreach ($tmpdatas as $kk => $vv) {
            if (!empty($vv['expand'])) {
                $expand = unserialize($vv['expand']);
                foreach ($expand as $ek => $ev) {
                    if (empty($vv[$ek]) && isset($ev['inarr']) && ($ev['inarr'] == 1)) {
                        $vv[$ek] = '面议';
                    } else {
                        $vv[$ek] = $vv[$ek] . '&nbsp;' . $ev['unit'];
                    }
                }
            }
            if ($vv['updatetime'] > $today) {
                $vv['timestr'] = "今天 " . date('H:i', $vv['updatetime']);
            } elseif ($vv['updatetime'] > $yesterday) {
                $vv['timestr'] = "昨天 " . date('H:i', $vv['updatetime']);
            } else {
                $vv['timestr'] = date('Y-m-d H:i', $vv['updatetime']);
            }
            if (!empty($vv['imgs'])) {
                $vv['imgs'] = unserialize($vv['imgs']);
                $vv['imgthumbnail'] = !strpos($vv['imgs']['0'],'ttp:') ? $this->config['site_url'].$vv['imgs']['0'] :$vv['imgs']['0'];
            }
            $tmpdatas[$kk] = $vv;
        }

        return $tmpdatas;
    }

    public function delItem() {
        $vid = intval($_POST['vid']);
        if (($vid > 0) && ($this->uid > 0)) {
            $flag = M('Classify_userinput')->where(array('id' => $vid, 'uid' => $this->uid))->delete();
            if ($flag){
                M('Classify_scrollmsg')->where(array('classify_id'=>$vid))->delete();
                $this->dexit(array('error' => 0, 'msg' => 'OK'));
            }
        }
        $this->dexit(array('error' => 1, 'msg' => ''));
    }

    public function myCollect() {
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录',U('Login/index'));
        }
        $o2oFavorite = $_COOKIE['o2oFavoriteThis'];
        $o2oFavoriteArr = !empty($o2oFavorite) ? explode('-', urldecode($o2oFavorite)) : array();
        $usercollectDb = M('Classify_usercollect');
        if ($this->uid > 0) {
            $tmp = $usercollectDb->where(array('uid' => $this->uid))->select();
            if ($tmp) {
                foreach ($tmp as $vv) {
                    $o2oFavoriteArr[] = $vv['vid'];
                }
            }
        }
        $o2oFavoriteArr = array_unique($o2oFavoriteArr);
        if (!empty($o2oFavoriteArr)) {
            $wherestr = 'id in(' . implode(',', $o2oFavoriteArr) . ')';
            $userinputDb = M('Classify_userinput');
            $count_userinputDb = $userinputDb->where($wherestr)->count();
            import('@.ORG.system_page');
            $p = new Page($count_userinputDb, 50);
            $pagebar = $p->show();
            $this->assign('pagebar', $pagebar);
            $tmpdatas = $userinputDb->where($wherestr)->order('updatetime DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            //echo $userinputDb->getLastSql();
            $today = strtotime(date('Y-m-d') . " 00:00:00"); //今天
            $yesterday = $today - 86400; //昨天
            if (!empty($tmpdatas)) {
                foreach ($tmpdatas as $kk => $vv) {
                    if (!empty($vv['expand'])) {
                        $expand = unserialize($vv['expand']);
                        foreach ($expand as $ek => $ev) {
                            if (empty($vv[$ek]) && isset($ev['inarr']) && ($ev['inarr'] == 1)) {
                                $vv[$ek] = '面议';
                            } else {
                                $vv[$ek] = $vv[$ek] . '&nbsp;' . $ev['unit'];
                            }
                        }
                    }
                    if ($vv['updatetime'] > $today) {
                        $vv['timestr'] = "今天 " . date('H:i', $vv['updatetime']);
                    } elseif ($vv['updatetime'] > $yesterday) {
                        $vv['timestr'] = "昨天 " . date('H:i', $vv['updatetime']);
                    } else {
                        $vv['timestr'] = date('Y-m-d H:i', $vv['updatetime']);
                    }
                    if (!empty($vv['imgs'])) {
                        $vv['imgs'] = unserialize($vv['imgs']);
                        $vv['imgthumbnail'] = !strpos($vv['imgs']['0'],'ttp:') ? $this->config['site_url'].$vv['imgs']['0'] :$vv['imgs']['0'];
                    }
                    $tmpdatas[$kk] = $vv;
                }
                $this->assign('listsdatas', $tmpdatas);
            }
        }
        $now_user = D('User')->get_user($this->uid);
        if (!empty($now_user)) {
            $now_user['now_money'] = floatval($now_user['now_money']);
        }
        $this->assign('now_user', $now_user);
        $this->display();
    }
    public function emptyC() {
        $id_arr = $_POST['id_arr'];

        if(empty($id_arr)){
            $this->dexit(array('error' => 1, 'msg' => 0));
        }

        if ($this->uid > 0) {
            $collect_condition['uid'] = $this->uid;
            $collect_condition['vid'] = array('in',$id_arr);
            $flag = M('Classify_usercollect')->where($collect_condition)->delete();
            $this->dexit(array('error' => 0, 'msg' => $flag));
        }
        $this->dexit(array('error' => 1, 'msg' => 0));
    }
    public function collectOpt() {
        $vid = intval($_POST['vid']);
        if (($this->uid > 0) && ($vid > 0)) {
            $usercollectDb = M('Classify_usercollect');
            $tmp = $usercollectDb->where(array('uid' => $this->uid, 'vid' => $vid))->find();
            if (empty($tmp)) {
                $flag = $usercollectDb->add(array('uid' => $this->uid, 'vid' => $vid, 'addtime' => time()));
                if ($flag)
                    $this->dexit(array('error' => 0, 'msg' => $flag));
            }
            unset($tmp);
        }
        $this->dexit(array('error' => 1, 'msg' => 0));
    }

    private function get_Subdirectory($cid, $subdir, $m = 2) {
        $Classify_categoryDb = M('Classify_category');
        $Subdirectory = array();
        $where = false;
        if ($m == 2) {
            $where = array('fcid' => $cid, 'subdir' => 2, 'cat_status' => 1);
        } elseif ($m == 3) {
            if ($subdir == 1) {
                $where = array('pfcid' => $cid, 'subdir' => 3, 'cat_status' => 1);
            } else {
                $where = array('fcid' => $cid, 'subdir' => 3, 'cat_status' => 1);
            }
        }
        if ($where) {
            $Subdirectory = $Classify_categoryDb->field(true)->where($where)->order('`cat_sort` DESC,`cid` ASC')->select();
        }
        return $Subdirectory;
    }

    /*     * **子目录列表*** */

    public function Subdirectory() {
        $cid = intval($_GET['cid']);
        $ctname = trim($_GET['ctname']);
        if ($cid > 0) {
            $Subdirectory2 = $this->get_Subdirectory($cid, 1);
            $newtmp = array();
            foreach ($Subdirectory2 as $vv) {
                unset($vv['cat_field']);
                $vv['subdir'] = $this->get_Subdirectory($vv['cid'], 2, 3);
                $newtmp[$vv['cid']] = $vv;
            }
            $Subdirectory2 = $newtmp;
            $this->assign('Subdirectory2', $Subdirectory2);
            $fcategory = $this->getTishFcid($cid);
            $ctname = !empty($fcategory) ? $fcategory['cat_name'] : $ctname;
            $this->assign('ctname', $ctname);
        } else {
            $this->redirect(U('Classify/index'));
            exit();
        }


        $database_Classify_category = D('Classify_category');
        $Zcategorys = $database_Classify_category->field(true)->where(array('subdir' => 1))->order('`cat_sort` DESC,`cid` ASC')->select();
        if (!empty($Zcategorys)) {
            $newtmp = array();
            foreach ($Zcategorys as $vv) {
                unset($vv['cat_field']);
                $subdir_info = $this->get_Subdirectory($vv['cid'], $vv['subdir']);
                if (!empty($subdir_info)) {
                    $vv['subdir'] = $subdir_info;
                    $newtmp[$vv['cid']] = $vv;
                }
            }
            $Zcategorys = $newtmp;
        }

        $this->assign('Zcategorys', $Zcategorys);
        $this->display();
    }

    /**     * */
    private function getTishFcid($cid, $cache = true) {
        $tmpdata = $_SESSION["session_FcidInfo{$cid}"];
        $tmpdata = !empty($tmpdata) ? unserialize($tmpdata) : false;
        if ($cache && !empty($tmpdata)) {
            return $tmpdata;
        } else {
            $tmpdata = M('classify_category')->field('cid,fcid,pfcid,subdir,cat_name')->where(array('cid' => $cid))->find();
            if ($cache) {
                $_SESSION["session_FcidInfo{$cid}"] = !empty($tmpdata) ? serialize($tmpdata) : '';
            } else {
                $_SESSION["session_FcidInfo{$cid}"] = '';
            }
            return $tmpdata;
        }
    }

    private function getTishFcategory($cid, $cache = true) {
        $tmpdata = $_SESSION["session_Fcategory{$cid}"];
        $tmpdata = !empty($tmpdata) ? unserialize($tmpdata) : false;
        if ($cache && !empty($tmpdata)) {
            return $tmpdata;
        } else {
            $classify_categoryDb = M('classify_category');
            $tmp = $classify_categoryDb->field('cid,fcid,subdir')->where(array('cid' => $cid, 'subdir' => 2))->find();
            if (!empty($tmp)) {
                $tmpdata = $classify_categoryDb->field('cid,fcid,pfcid,subdir,cat_name')->where(array('fcid' => $tmp['fcid'], 'subdir' => 2))->order('`cat_sort` DESC,`cid` ASC')->select();
                foreach ($tmpdata as $kk => $vv) {
                    if ($cid == $vv['cid']) {
                        $tmpdata[$kk]['subdir3'] = $this->get_Subdirectory($cid, 2, 3);
                    }
                }
            }
            return $tmpdata;
        }
    }

    public function hasDistance($limit,$wherestr=array())
    {   
        $long_lat = array('lat' => 0, 'long' => 0);
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if (!empty($long_lat['long']) && !empty($long_lat['lat'])) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $location2 = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);//转换腾讯坐标到百度坐标
            $lat = $location2['lat'];
            $long = $location2['lng'];
            $list = M('Classify_userinput')
                    ->field('*,ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-`latitude`*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(`latitude`*PI()/180)*POW(SIN(('.$long.'*PI()/180-`longitude`*PI()/180)/2),2)))*1000) AS juli')
                    ->where($wherestr)
                    ->order('juli asc')
                    ->limit($limit)
                    ->select();
     
            foreach ($list as $key => $value) {
                $list[$key]['position'] = array('lng' => $value['longitude'], 'lat' => $row['latitude']);
                $list[$key]['distjuli'] = isset($value['juli']) ? intval($value['juli']) : 0;
                $list[$key]['distance'] = ($value['longitude'] !=0 && $value['latitude']!=0) ? getRange($value['juli']) : '';
            }
        }else{
            $list = M('Classify_userinput')->field(true)->where($wherestr)->order('topsort DESC,toptime DESC,updatetime DESC,id DESC')->limit($limit)->select();
        }
        return $list;
    }
    /*     * **列表页*** */

    public function Lists() {
        $cid = intval($_GET['cid']);
		redirect(U('Classifynew/category',array('cat_id'=>$cid)));
        $sub3dir = isset($_GET['sub3dir']) ? intval($_GET['sub3dir']) : 0;
        $opt = isset($_GET['opt']) ? trim($_GET['opt']) : '';
        if ($cid > 0) {
            $userinputDb = M('Classify_userinput');
            $where = $this->analyse_param($opt);
            $original = !empty($where) ? $where['original'] : '';
            $c_input = !empty($where) ? $where['fd'] : '';
            $wherestr = $sub3dir > 0 ? 'cid=' . $cid . ' AND sub3dir=' . $sub3dir . " AND status=1  AND `city_id`='".$this->config['now_city']."'" : 'cid=' . $cid . " AND status=1  AND `city_id`='".$this->config['now_city']."'";
            if (!empty($where)) {
                if ($where['ty'] == 1) {
                    $tmp = explode('-', $where['vv']);
                    if ($tmp['0'] == 0) {
                        $wherestr.=' AND ' . $where['fd'] . '>=0 AND ' . $where['fd'] . '<=' . $tmp['1'];
                    } elseif ($tmp['1'] == 0) {
                        $wherestr.=' AND ' . $where['fd'] . '>=' . $tmp['0'];
                    } else {
                        $wherestr.=' AND ' . $where['fd'] . '<=' . $tmp['1'] . " AND " . $where['fd'] . '>=' . $tmp['0'];
                    }
                } else {
                    $wherestr.=' AND ' . $where['fd'] . ' LIKE "%' . $where['vv'] . '%"';
                }
            }

            $count_userinputDb = $userinputDb->where($wherestr)->count();
            import('@.ORG.common_page');
            $p = new Page($count_userinputDb, 20);
            $pagebar = $p->show(2);
            $this->assign('pagebar', $pagebar);
            /*             * *置顶更新处理** */
            $toparr = $userinputDb->field('id,cid,updatetime,toptime,endtoptime,topsort')->where('cid=' . $cid . ' AND status=1 AND toptime >0')->select();
            if (!empty($toparr)) {
                $currenttime = time();
                foreach ($toparr as $tvv) {
                    if ($tvv['endtoptime'] < $currenttime) {
                        $userinputDb->where(array('id' => $tvv['id']))->save(array('toptime' => 0, 'topsort' => 0));
                    }
                }
            }
            $limit = $p->firstRow . ',' . $p->listRows;
            /*             * *置顶更新处理结束** */
            // $tmpdatas = $userinputDb->field(true)->where($wherestr)->order('topsort DESC,toptime DESC,updatetime DESC,id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            $tmpdatas = $this->hasDistance($limit,$wherestr);
            $today = strtotime(date('Y-m-d') . " 00:00:00"); //今天
            $yesterday = $today - 86400; //昨天
            if (!empty($tmpdatas)) {
                foreach ($tmpdatas as $kk => $vv) {
                    if (!empty($vv['expand'])) {
                        $expand = unserialize($vv['expand']);
                        foreach ($expand as $ek => $ev) {
                            if (empty($vv[$ek]) && isset($ev['inarr']) && ($ev['inarr'] == 1)) {
                                $vv[$ek] = '面议';
                            } else {
                                $vv[$ek] = $vv[$ek] . '&nbsp;' . $ev['unit'];
                            }
                        }
                    }
                    if ($vv['updatetime'] > $today) {
                        $vv['timestr'] = "今天 " . date('H:i', $vv['updatetime']);
                    } elseif ($vv['updatetime'] > $yesterday) {
                        $vv['timestr'] = "昨天 " . date('H:i', $vv['updatetime']);
                    } else {
                        $vv['timestr'] = date('Y-m-d H:i', $vv['updatetime']);
                    }
                    if (!empty($vv['imgs'])) {
                        $vv['imgs'] = unserialize($vv['imgs']);
                        $vv['imgthumbnail'] = !strpos($vv['imgs']['0'],'ttp:') ? $this->config['site_url'].$vv['imgs']['0'] :$vv['imgs']['0'];
                    }
                    unset($vv['content'],$vv['description']);
                    $tmpdatas[$kk] = $vv;
                }
            }

            $tmpcid = $this->getTishFcid($cid);
            $where_arr = $tmpcid['fcid'] > 0 ? array('cid' => $cid, 'fcid' => $tmpcid['fcid']) : array('cid' => $cid);
            $category = D('Classify_category');
            $cat_field = $category->field('cid,fcid,pfcid,subdir,cat_field')->where($where_arr)->find();
            $conarr = array();
            if ($cat_field['cat_field']) {
                $cat_field = unserialize($cat_field['cat_field']);
                foreach ($cat_field as $cv) {
                    if (($cv['isfilter'] == 1) && ($cv['input'] > 0)) {
                        if (($cv['type'] == 1) && ($cv['inarr'] == 1)) {
                            $conarr[] = array('opt' => 1, 'name' => $cv['name'], 'input' => 'input' . $cv['input'], 'data' => $cv['filtercon']);
                        } else {
                            if (isset($cv['use_field']) && ($cv['use_field'] == 'area')) {
                                $get_area_list = D('Area')->get_area_list();
                                $new_areas = array();
                                if ($get_area_list) {
                                    foreach ($get_area_list as $vv) {
                                        $new_areas[$vv['area_id']] = $vv['area_name'];
                                    }
                                }
                                $cv['filtercon'] = $new_areas;
                            }
                            $conarr[] = array('opt' => 0, 'name' => $cv['name'], 'input' => 'input' . $cv['input'], 'data' => $cv['filtercon']);
                        }
                    }
                }
            }

            if (!empty($conarr) && count($conarr) < 4) {
                $categorys = $this->getTishFcategory($cid);
                $this->assign('categorys', $categorys);
            }
            $fcid = $tmpcid['fcid'] > 0 ? $tmpcid['fcid'] : $cat_field['fcid'];
            $url = '/wap.php?g=Wap&c=Classify&a=Lists&cid=' . $cid;
            $this->assign('conarr', $conarr);
            $this->assign('qsearch', $opt);
            $this->assign('thisurl', $url);
            $this->assign('cid', $cid);
            $this->assign('cat_name', $tmpcid['cat_name']);
            $this->assign('fcid', $fcid);
            $this->assign('original', $original);
            $this->assign('c_input', $c_input);
            $this->assign('listsdatas', $tmpdatas);
            $this->display();
        } else {
            $this->redirect(U('Classify/index'));
            exit();
        }
    }


    public function ajax_Lists() {
        $cid = intval($_GET['cid']);
        $sub3dir = isset($_GET['sub3dir']) ? intval($_GET['sub3dir']) : 0;
        $opt = isset($_GET['opt']) ? trim($_GET['opt']) : '';
        if ($cid > 0) {
            $userinputDb = M('Classify_userinput');
            $where = $this->analyse_param($opt);
            $wherestr = $sub3dir > 0 ? 'cid=' . $cid . ' AND sub3dir=' . $sub3dir . " AND status=1  AND `city_id`='".$this->config['now_city']."'" : 'cid=' . $cid . " AND status=1  AND `city_id`='".$this->config['now_city']."'";
            if (!empty($where)) {
                if ($where['ty'] == 1) {
                    $tmp = explode('-', $where['vv']);
                    if ($tmp['0'] == 0) {
                        $wherestr.=' AND ' . $where['fd'] . '>=0 AND ' . $where['fd'] . '<=' . $tmp['1'];
                    } elseif ($tmp['1'] == 0) {
                        $wherestr.=' AND ' . $where['fd'] . '>=' . $tmp['0'];
                    } else {
                        $wherestr.=' AND ' . $where['fd'] . '<=' . $tmp['1'] . " AND " . $where['fd'] . '>=' . $tmp['0'];
                    }
                } else {
                    $wherestr.=' AND ' . $where['fd'] . ' LIKE "%' . $where['vv'] . '%"';
                }
            }

            $count_userinputDb = $userinputDb->where($wherestr)->count();
            import('@.ORG.common_page');
            $p = new Page($count_userinputDb, 20);

            $pagebar = $p->show(2);
            $this->assign('pagebar', $pagebar);
            /*             * *置顶更新处理** */
            $toparr = $userinputDb->field('id,cid,updatetime,toptime,endtoptime,topsort')->where('cid=' . $cid . ' AND status=1 AND toptime >0')->select();
            if (!empty($toparr)) {
                $currenttime = time();
                foreach ($toparr as $tvv) {
                    if ($tvv['endtoptime'] < $currenttime) {
                        $userinputDb->where(array('id' => $tvv['id']))->save(array('toptime' => 0, 'topsort' => 0));
                    }
                }
            }

            /*             * *置顶更新处理结束** */

            if($_GET['page'] <= ($p->totalPage)){
                $tmpdatas = $userinputDb->field(true)->where($wherestr)->order('topsort DESC,toptime DESC,updatetime DESC,id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            }else{
                $tmpdatas = array();
            }


            $today = strtotime(date('Y-m-d') . " 00:00:00"); //今天
            $yesterday = $today - 86400; //昨天
            if (!empty($tmpdatas)) {
                foreach ($tmpdatas as $kk => $vv) {
                    if (!empty($vv['expand'])) {
                        $expand = unserialize($vv['expand']);
                        foreach ($expand as $ek => $ev) {
                            if (empty($vv[$ek]) && isset($ev['inarr']) && ($ev['inarr'] == 1)) {
                                $vv[$ek] = '面议';
                            } else {
                                $vv[$ek] = $vv[$ek] . '&nbsp;' . $ev['unit'];
                            }
                        }
                    }
                    if ($vv['updatetime'] > $today) {
                        $vv['timestr'] = "今天 " . date('H:i', $vv['updatetime']);
                    } elseif ($vv['updatetime'] > $yesterday) {
                        $vv['timestr'] = "昨天 " . date('H:i', $vv['updatetime']);
                    } else {
                        $vv['timestr'] = date('Y-m-d H:i', $vv['updatetime']);
                    }
                    if (!empty($vv['imgs'])) {
                        $vv['imgs'] = unserialize($vv['imgs']);
                        $vv['imgthumbnail'] = !strpos($vv['imgs']['0'],'ttp:') ? $this->config['site_url'].$vv['imgs']['0'] :$vv['imgs']['0'];
                    }
                    unset($vv['content'],$vv['description']);
                    $tmpdatas[$kk] = $vv;
                }
            }


            if($tmpdatas){
                exit(json_encode(array('status'=>1,'listsdatas'=>$tmpdatas)));
            }else{
                exit(json_encode(array('status'=>0,'listsdatas'=>$tmpdatas)));
            }

        } else {
            $this->redirect(U('Classify/index'));
            exit();
        }
    }

    /*     * **展示页面*** */

    public function ShowDetail() {
        $vid = intval($_GET['vid']);
        $vid = $vid > 0 ? $vid : 0;
        $content = false;
        if ($vid > 0) {
            $database_classify_usercollect = D('Classify_usercollect');
            $classify_usercollect_info = $database_classify_usercollect->where(array('vid'=>$vid,'uid'=>$this->user_session['uid']))->find();
            if(!empty($classify_usercollect_info)){
                $this->assign('classify_usercollect_info' , $classify_usercollect_info);
            }
            M('Classify_userinput')->where(array('id' => $vid))->setInc('views');
            $tmpdata = M('Classify_userinput')->field(true)->where(array('id' => $vid))->find();
            if (!empty($tmpdata)) {
                $status = $tmpdata['status'];
                $content = !empty($tmpdata['content']) ? unserialize($tmpdata['content']) : false;
                if($content){
                    foreach($content as $key=>$value){
                        if(!$value['vv']){
                            unset($content[$key]);
                        }
                    }
                }
                $imgarr = !empty($tmpdata['imgs']) ? unserialize($tmpdata['imgs']) : false;
                $tmpdata['updatetime'] = date('Y-m-d H:i', $tmpdata['updatetime']);
                $category = D('Classify_category');
                $mycategory = $category->field('cid,fcid,pfcid,subdir,cat_name')->where(array('cid' => $tmpdata['cid']))->find();
                // 查询一下 主分类信息
                $cid = intval($mycategory['cid']);
                $fcid = intval($mycategory['fcid']);
                $pfcid = intval($mycategory['$pfcid']);
                // 获取主分类信息
                $classify_id = $cid;
                if ($fcid && !$pfcid) {
                    $classify_id = $fcid;
                } elseif ($fcid && $pfcid) {
                    $classify_id = $pfcid;
                }
                $classify_category_info = M('Classify_category')->where(array('cid'=>$classify_id))->field('reward_type, reward_look')->find();
                $tmpdata['reward_type'] = $classify_category_info['reward_type'];
                $tmpdata['reward_look'] =  round($classify_category_info['reward_look'], 2);
                if (!empty($classify_category_info) && ($classify_category_info['reward_type'] == 3 || $classify_category_info['reward_type'] == 4)) {
                    $uid = (int)$_SESSION['user']['uid'];
                    if ($uid) {
                        $info_pay = M('Reward_order')->where(array('uid'=>$uid,'reward_id'=>$vid, 'status' => 1, 'type' => 2))->find();
                        if ($info_pay) {
                            $tmpdata['reward_type'] = 1;
                        }
                    }
                    if ($tmpdata['uid'] == $uid) {
                        $tmpdata['reward_type'] = 1;
                    }
                }
                $tmpdata['cat_name'] = $mycategory['cat_name'];
                unset($f_category, $mycategory);
                $tmpdata['s_c'] = array();
                if ($tmpdata['sub3dir'] > 0) {
                    $tmpdata['s_c'] = $category->field('cid,fcid,pfcid,subdir,cat_name')->where(array('cid' => $tmpdata['sub3dir']))->find();
                }
                foreach($imgarr as &$v){
                    $v=$this->config['site_url'].$v;
                }
                $tmpdata['description'] = str_replace(PHP_EOL,'<br>',$tmpdata['description']);
                $tmpdata['description'] = htmlspecialchars_decode($tmpdata['description'], ENT_QUOTES);
                $tmpdata['otherdesc'] = !empty($tmpdata['otherdesc']) ? htmlspecialchars_decode($tmpdata['otherdesc'], ENT_QUOTES) :'';
                $this->assign('detail', $tmpdata);
                $this->assign('content', $content);
                $this->assign('status', $status);
                $this->assign('imglist', $imgarr);
                $this->assign('vid', $vid);
                $this->assign('client_ip', get_client_ip());


                $database_classify_order = D('Classify_order');
                $classify_order_where['paid'] = 1;
                $classify_order_where['classify_userinput_id'] = $_GET['vid'] + 0;
                $classify_order_info = $database_classify_order->where($classify_order_where)->find();

				$user = D('User')->field(true)->where(array('uid' => $tmpdata['uid']))->find();
				if($_SESSION['openid'] && $user['openid']){
					$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
					$im_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key;
					$this->assign('im_url', $im_url . '#group_' . $user['openid']);
				}

                $this->assign('classify_order_info', $classify_order_info);
                $this->display();
                exit();
            }
        }
        $this->redirect(U('Classify/index'));
        exit();
    }

    /*     * **更新浏览量*** */

    public function addviews() {
        $vid = intval($_POST['vid']);
        if ($vid > 0) {
            M('Classify_userinput')->where(array('id' => $vid))->setInc('views', 1);
            echo 0;
            exit;
        }
        echo 1;
        exit;
    }

    /**
     * **统一参数解析
     * **
     * ***** */
    private function analyse_param($str) {
        if (empty($str))
            return false;
        $s_str = base64_decode(str_replace(" ", "+", $str));

        if (!$s_str || (strpos($s_str, ',ty=') === false) || (strpos($s_str, ',fd=') === false) || (strpos($s_str, ',vv=') === false))
            return false;
        $s_arr = explode(',', $s_str);
        if (count($s_arr) != 4)
            return false;
        $tmpdata = array('ty' => '', 'fd' => '', 'vv' => '', 'original' => '');
        $tmp = explode('=', $s_arr['1']);
        $tmpdata['ty'] = intval($tmp['1']); //是否需要解析vv字符串
        $tmp = explode('=', $s_arr['2']); //是否需要解析vv字符串
        $tmpdata['fd'] = trim($tmp['1']); //字段名字 input1、input2....
        $tmp = explode('=', $s_arr['3']); //是否需要解析vv字符串
        $tmpdata['original'] = $tmpdata['vv'] = trim($tmp['1']); //条件值
        if ($tmpdata['ty'] == 1) {
            $tmpdata['vv'] = preg_replace('/[^0-9\-]*/', '', $tmpdata['vv']); /*             * 过滤掉不需要的字符* */
        }
        return $tmpdata;
    }

    /*     * **发表信息目录选择页面*** */

    public function SelectSub() {
        $cid = intval($_GET['cid']);
        $database_Classify_category = D('Classify_category');
        $Zcategorys = $database_Classify_category->field(true)->where(array('subdir' => 1,'cat_status'=>1))->order('`cat_sort` DESC,`cid` ASC')->select();
        if (!empty($Zcategorys)) {
            $newtmp = array();
            foreach ($Zcategorys as $vv) {
                unset($vv['cat_field']);
                $subdir_info = $this->get_Subdirectory($vv['cid'], $vv['subdir']);
                if (!empty($subdir_info)) {
                    $vv['subdir'] = $subdir_info;
                    $newtmp[$vv['cid']] = $vv;
                }
            }
            $Zcategorys = $newtmp;
        }

        $this->assign('Zcategorys', $Zcategorys);
        $this->assign('cid', $cid);
        $this->display();
    }



    /*     * *发布信息页* */

    public function fabu() {
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
            exit();
        }
        $cid = intval($_GET['cid']);
        $fcid = intval($_GET['fcid']);
        if (($cid > 0) && ($fcid > 0)) {
            $cat_field = false;
            $database_Classify_category = D('Classify_category');
            $tmp = $database_Classify_category->where(array('cid' => $cid, 'fcid' => $fcid))->find();
            $subdir = $this->get_Subdirectory($tmp['cid'], 2, 3);
            $ftmp = $database_Classify_category->where(array('cid' => $fcid))->find();
            if (empty($tmp)) {
                $tmp = $ftmp;
            }else{
                $tmp_cat_field = !empty($tmp['cat_field']) ? unserialize($tmp['cat_field']) : false;
                if (!$tmp_cat_field) {
                    $tmp['cat_field']=$ftmp['cat_field'];
                }
            }
            if ($tmp) {
                $cat_field = !empty($tmp['cat_field']) ? unserialize($tmp['cat_field']) : false;
                if ($cat_field) {
                    foreach ($cat_field as $kk => $vv) {
                        if (isset($vv['use_field']) && ($vv['use_field'] == 'area')) {
                            $get_area_list = D('Area')->get_area_list();
                            $new_areas = array();
                            if ($get_area_list) {
                                foreach ($get_area_list as $vv) {
                                    $new_areas[$vv['area_id']] = $vv['area_name'];
                                }
                            }
                            $cat_field[$kk]['opt'] = $new_areas;
                        }
                    }
                }

            $database_area = D('Area');
            $id = $_GET['adress_id'];
            if(cookie('user_address') === '0' || cookie("user_address") == "") {
                $now_adress = D('User_adress')->get_adress($this->user_session['uid'], $id);
                if ($now_adress) {
                    $this->assign('now_adress', $now_adress);

                    $province_list = $database_area->get_arealist_by_areaPid(0);
                    $this->assign('province_list',$province_list);

                    $city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
                    $this->assign('city_list', $city_list);

                    $area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
                    $this->assign('area_list', $area_list);
                } else {
                    $now_city_area = $database_area->where(array('area_id'=>$this->config['now_city']))->find();
                    $this->assign('now_city_area',$now_city_area);

                    $province_list = $database_area->get_arealist_by_areaPid(0);
                    $this->assign('province_list',$province_list);

                    $city_list = $database_area->get_arealist_by_areaPid($now_city_area['area_pid']);
                    $this->assign('city_list',$city_list);

                    $area_list = $database_area->get_arealist_by_areaPid($now_city_area['area_id']);
                    $this->assign('area_list',$area_list);
                }
            } else {
                $cookie = json_decode($_COOKIE['user_address'], true);
                $now_adress = $cookie;
                $now_adress['default'] = $now_adress['defaul'];
                $now_adress['adress_id'] = $now_adress['id'];
                $this->assign('now_adress', $now_adress);
                $province_list = $database_area->get_arealist_by_areaPid(0);
                $this->assign('province_list',$province_list);

                $city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
                $this->assign('city_list', $city_list);

                $area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
                $this->assign('area_list', $area_list);
            }

            $params = $_GET;
            unset($params['adress_id']);
            $this->assign('params',$params);

            } else {
                $this->error_tips('分类不存在！', U('Classify/SelectSub', array('cid' => $cid)));
            }
            //print_r($cat_field);
            $this->assign('subdir', $subdir);
            $this->assign('cid', $cid);
            $this->assign('fcid', $fcid);
            $this->assign('fabuset', $tmp);
            $this->assign('fabuTmp', $ftmp);
            $this->assign('catfield', $cat_field);
            $this->display();
        } else {
            $this->redirect(U('Classify/SelectSub', array('cid' => $cid)));
            exit();
        }
    }


    public function classify_edit(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
            exit();
        }

        $id = $_GET['id'] + 0;
        if(empty($id)){
            $this->error_tips('传递参数有误！');
        }

        $database_classify_userinput = D('Classify_userinput');
        $classify_condition['id'] = $id;
        $classify_condition['uid'] = intval($this->user_session['uid']);
        $classify_userinput_info = $database_classify_userinput->where($classify_condition)->find();
        if (empty($classify_userinput_info)) {
            $this->error_tips('发布信息不存在');
        }

        $cid = $classify_userinput_info['cid'];
        $fcid = $classify_userinput_info['fcid'];
        if (($cid > 0) && ($fcid > 0)) {
            $cat_field = false;
            $database_Classify_category = D('Classify_category');
            $tmp = $database_Classify_category->where(array('cid' => $cid, 'fcid' => $fcid))->find();
            $subdir = $this->get_Subdirectory($tmp['cid'], 2, 3);
            if (empty($tmp)) {
                $tmp = $database_Classify_category->where(array('cid' => $fcid))->find();
            }elseif(empty($tmp['cat_field'])){
                $tmp1 = $database_Classify_category->where(array('cid' => $fcid))->find();
                $tmp['cat_field']=$tmp1['cat_field'];
            }
            if ($tmp) {
                $cat_field = !empty($tmp['cat_field']) ? unserialize($tmp['cat_field']) : false;
                if ($cat_field) {
                    foreach ($cat_field as $kk => $vv) {
                        if (isset($vv['use_field']) && ($vv['use_field'] == 'area')) {
                            $get_area_list = D('Area')->get_area_list();
                            $new_areas = array();
                            if ($get_area_list) {
                                foreach ($get_area_list as $vv) {
                                    $new_areas[$vv['area_id']] = $vv['area_name'];
                                }
                            }
                            $cat_field[$kk]['opt'] = $new_areas;
                        }
                    }
                }
            } else {
                $this->error_tips('分类不存在！', U('Classify/SelectSub', array('cid' => $cid)));
            }}
        $classify_userinput_info['imgs'] = unserialize($classify_userinput_info['imgs']);
        $classify_userinput_info['content'] = unserialize($classify_userinput_info['content']);
                    $database_area = D('Area');
            $id = $_GET['adress_id'];
            if(cookie('user_address') === '0' || cookie("user_address") == "") {
                $now_adress = D('User_adress')->get_adress($this->user_session['uid'], $id);
                if ($now_adress) {
                    $this->assign('now_adress', $now_adress);

                    $province_list = $database_area->get_arealist_by_areaPid(0);
                    $this->assign('province_list',$province_list);

                    $city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
                    $this->assign('city_list', $city_list);

                    $area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
                    $this->assign('area_list', $area_list);
                } else {
                    $now_city_area = $database_area->where(array('area_id'=>$this->config['now_city']))->find();
                    $this->assign('now_city_area',$now_city_area);

                    $province_list = $database_area->get_arealist_by_areaPid(0);
                    $this->assign('province_list',$province_list);

                    $city_list = $database_area->get_arealist_by_areaPid($now_city_area['area_pid']);
                    $this->assign('city_list',$city_list);

                    $area_list = $database_area->get_arealist_by_areaPid($now_city_area['area_id']);
                    $this->assign('area_list',$area_list);
                }
            } else {
                $cookie = json_decode($_COOKIE['user_address'], true);
                $now_adress = $cookie;
                $now_adress['default'] = $now_adress['defaul'];
                $now_adress['adress_id'] = $now_adress['id'];
                $this->assign('now_adress', $now_adress);
                $province_list = $database_area->get_arealist_by_areaPid(0);
                $this->assign('province_list',$province_list);

                $city_list = $database_area->get_arealist_by_areaPid($now_adress['province']);
                $this->assign('city_list', $city_list);

                $area_list = $database_area->get_arealist_by_areaPid($now_adress['city']);
                $this->assign('area_list', $area_list);
            }
            $params = $_GET;
            unset($params['adress_id']);
            $this->assign('params',$params);
        $this->assign('subdir', $subdir);
        $this->assign('cid', $cid);
        $this->assign('fcid', $fcid);
        $this->assign('fabuset', $tmp);
        $this->assign('catfield', $cat_field);
        $this->assign('classify_userinput_info' , $classify_userinput_info);
        $this->display();
    }

    /*     * *去除单双引号以及一些非法字符*** */
    private function Removalquotes($array) {
        $regex = "/\'|\"|\\\|\<script|\<\/script/";
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->Removalquotes($value);
                } else {
                    $value = strip_tags(trim($value));
                    $value = preg_replace($regex, '', $value);
                    $array[$key] = htmlspecialchars($value, ENT_QUOTES);
                }
            }
            return $array;
        } else {
            $array = strip_tags(trim($array));
            $array = preg_replace($regex, '', $array);
            return htmlspecialchars($array, ENT_QUOTES);
        }
    }


    // 生成用户发布打赏金钱信息
    public function fabu_reward_pay_order() {
        $uid = (int)$_SESSION['user']['uid'];
        if($uid <= 0){
            exit(json_encode(array('code'=>2,'msg'=>'您还未登录，请先登录')));
        }
        $data = $this->Removalquotes($_POST);
        $cid = intval($data['cid']);
        $fcid = intval($data['fcid']);
        $pfcid = intval($data['$pfcid']);
        // 获取主分类信息
        $classify_id = $cid;
        if ($fcid && !$pfcid) {
            $classify_id = $fcid;
        } elseif ($fcid && $pfcid) {
            $classify_id = $pfcid;
        }
        $classify_category_info = M('Classify_category')->where(array('cid'=>$classify_id))->field('reward_type, reward_publish')->find();
        // 该分类已被删除
        if (empty($classify_category_info)) {
            exit(json_encode(array('error'=>2,'msg'=>'该分类已被删除')));
        }
        $userInfo = D('User')->where(array('uid'=>$uid))->field('uid, now_money')->find();
        // 如果余额不足提示去充值
        $reward_publish = floatval($classify_category_info['reward_publish']);
        $now_money = floatval($userInfo['now_money']);
        $data = array(
            'now_money' => $userInfo['now_money'],  // 当前余额
            'reward_money' => $classify_category_info['reward_publish'],  // 打赏金额
        );
        if($reward_publish > $now_money){
            $data['difference'] = $reward_publish - $now_money; // 差额
            exit(json_encode(array('error'=>3,'msg'=>'当前余额不足请充值', 'info' => $data)));
        }
        $data['difference'] = $now_money - $reward_publish; // 差额

        exit(json_encode(array('error'=>5,'info'=>$data)));
    }

    /*     * *发布信息处理* */
    public function fabuTosave() {
        if(IS_POST){
            if (empty($this->user_session)) {
                exit(json_encode(array('status'=>-1,'msg'=>'请先进行登录！','url'=> U('Login/index'))));
            }
            $address = $this->object_to_array(json_decode($_COOKIE['user_address']));

            if(isset($address['longitude'])){
                $longitude = $address['longitude'];
            }
            if(isset($address['latitude'])){
                $latitude = $address['latitude'];
            }
            if(isset($address['adress'])){
                $adress = $address['adress'];
            }
            if(isset($address['province'])){
                $province = $address['province'];
            }
            if(isset($address['city'])){
                $city = $address['city'];
            }
            if(isset($address['area'])){
                $area = $address['area'];
            }
            $uid = $this->user_session['uid'];
            $datas = $this->Removalquotes($_POST);
            $cid = intval($datas['cid']);
            $fcid = intval($datas['fcid']);
            $pfcid = intval($datas['pfcid']);
            $sub3dir = isset($datas['subdir']) ? intval($datas['subdir']) : 0;

            if(isset($datas['longitude'])){
                $longitude = $datas['longitude'];
            }
            if(isset($datas['latitude'])){
                $latitude = $datas['latitude'];
            }
            if(isset($datas['adress'])){
                $adress = $datas['adress'];
            }
            if(isset($datas['province'])){
                $province = $datas['province'];
            }
            if(isset($datas['city'])){
                $city = $datas['city'];
            }
            if(isset($datas['area'])){
                $area = $datas['area'];
            }
            
			$description = str_replace("&amp;amp;nbsp;",chr(32),$datas['description']);
			if (empty($description)){
                exit(json_encode(array('status'=>0,'msg'=>'信息内容必须填写！')));
            }
			
			$tname = $datas['tname'];
            if (empty($tname)){
                $tname = msubstr($description,0,100);
            }
			$description = str_replace(PHP_EOL,'<br/>',$description);

            $lxname = $datas['lxname'];
			if (empty($lxname)){
				exit(json_encode(array('status'=>0,'msg'=>'联系人名字必须填写！')));
			}


            $lxtel = $datas['lxtel'];
            $is_assure = $datas['is_assure'];
            $assure_money = $datas['assure_money'];
            $cost_price = $datas['cost_price'];
            $is_post_free = $datas['is_post_free'];
            $postage = $datas['postage'];

            if ($lxtel && !$this->config['international_phone'] && !preg_match('/^([0-9]{2,4})-?[0-9]{5,20}$/', $lxtel)){
                exit(json_encode(array('status'=>0,'msg'=>'联系手机号格式不正确！')));
            }

            if ($is_assure && $assure_money<=0) {
                exit(json_encode(array('status'=>0,'msg'=>'担保金额必须大于0！')));
            }

			if (isset($datas['is_assure']) && isset($datas['is_post_free']) && isset($datas['postage']) &&  $is_assure && $is_post_free==0 && $postage<=0) {
                exit(json_encode(array('status'=>0,'msg'=>'运费必须大于0！')));
            }


            $inputimg = isset($datas['inputimg']) ? $datas['inputimg'] : '';
            $inputs = $datas['input'];
            $newinputdatas = array();
            $inputfield = array();
            $expand = array();
            if (!empty($inputs)) {
                foreach ($inputs as $kk => $vv) {
                    $iswrite = intval($vv['iswrite']);
                    $input = intval($vv['input']);
                    if ($iswrite && empty($vv['vv'])) {
                        if ($vv['type'] == 6) { // 地图
                           if (!$longitude&&!$latitude) {
                               exit(json_encode(array('status'=>0,'msg'=>"有必填[{$vv['tn']}]项没有填写！")));
                           }
                        }else{
                            exit(json_encode(array('status'=>0,'msg'=>"有必填[{$vv['tn']}]项没有填写！")));
                        }
                    }
                    $newinputdatas[] = $vv;
                    $isfilter = intval($vv['isfilter']);
                    if ($isfilter && ($input > 0) && ($input < 5)) {
                        $inputfield['input' . $input] = is_array($vv['vv']) ? implode(',', $vv['vv']) : ($vv['vv']?$vv['vv']:'');
                        //$k++;
                        if (isset($vv['unit']) && !empty($vv['unit'])) {
                            $expand['input' . $input] = array('unit' => $vv['unit']);
                        }
                        if (isset($vv['inarr'])) {
                            if (isset($expand['input' . $input])) {
                                $expand['input' . $input]['inarr'] = intval($vv['inarr']);
                            } else {
                                $expand['input' . $input] = array('inarr' => intval($vv['inarr']));
                            }
                        }
                    }
                }
            }
            
            unset($datas);


            // 获取主分类信息
            $classify_id = $cid;
            if ($fcid && !$pfcid) {
                $classify_id = $fcid;
            } elseif ($fcid && $pfcid) {
                $classify_id = $pfcid;
            }
            $pay = false;
            $classify_category_info = M('Classify_category')->where(array('cid'=>$classify_id))->field('reward_type, reward_publish, reward_look,cat_name')->find();
			// 该分类已被删除
            if (empty($classify_category_info)) {
                exit(json_encode(array('status'=>2,'msg'=>'该分类已被删除')));
            }
            $reward_publish = floatval($classify_category_info['reward_publish']);
            if ($classify_category_info && ($classify_category_info['reward_type'] == 2 || $classify_category_info['reward_type'] == 4) && $reward_publish > 0) {
                $userInfo = D('User')->where(array('uid'=>$uid))->field('uid, phone, now_money')->find();
                $adminUser = [];
                if($userInfo['phone']){
                   $adminUser = D('Admin')->where(['phone' => $userInfo['phone'], 'status' => 1])->find();
                }
                if(empty($adminUser)) {
                    // 如果余额不足提示去充值
                    $now_money = floatval($userInfo['now_money']);
                    $data      = array(
                        'now_money'    => $userInfo['now_money'],  // 当前余额
                        'reward_money' => $classify_category_info['reward_publish'],  // 打赏金额
                    );
                    if ($reward_publish > $now_money) {
                        $data['difference'] = $reward_publish - $now_money; // 差额
                        exit(json_encode(array('status' => 3, 'msg' => '当前余额不足请充值', 'url' => U('My/my_money'), 'info' => $data)));
                    }
                    $dec_money = D('User')->user_money($this->user_session['uid'], $reward_publish, "发布打赏分类信息【" . $tname . "】扣除余额 " . $reward_publish . C('config.Currency_txt') . "");
                    if ($dec_money['error_code']) {
                        exit(json_encode(array('status' => 0, 'msg' => $dec_money['msg'])));
                    }
                    $pay = true;
                }
            }

            $insert_datas = array('uid' => $uid, 'cid' => $cid, 'fcid' => $fcid, 'sub3dir' => $sub3dir, 'title' => $tname, 'lxname' => $lxname, 'lxtel' => $lxtel, 'imgs' => !empty($inputimg) ? serialize($inputimg) : '', 'description' => $description);
            if (!empty($inputfield))
                $insert_datas = array_merge($insert_datas, $inputfield);
            $insert_datas['content'] = !empty($newinputdatas) ? serialize($newinputdatas) : '';
            $insert_datas['addtime'] = $insert_datas['updatetime'] = time();
            $insert_datas['expand'] = !empty($expand) ? serialize($expand) : '';
            $insert_datas['is_assure'] = !empty($is_assure) ? $is_assure : 0;
            $insert_datas['assure_money'] = !empty($assure_money) ? $assure_money : 0;
            $insert_datas['cost_price'] = !empty($cost_price) ? floatval($cost_price) : '0.00'; // 原价
            $insert_datas['is_post_free'] = !empty($is_post_free) ? $is_post_free : 0; //是否包邮
            $insert_datas['postage'] = !empty($postage) ? $postage : '0.00'; // 邮费

            $insert_datas['status'] = C('config.classify_verify') ? 0 : 1;
            $insert_datas['city_id'] = $this->config['now_city'];
            $insert_datas['longitude'] = $longitude ? $longitude : 0;
            $insert_datas['latitude'] = $latitude ? $latitude : 0;
            $insert_datas['address'] = $adress ? $adress : '';
            $insert_datas['province'] = $province ? $province : 0;
            $insert_datas['area'] = $area ? $area : 0;
			$insert_datas['ip'] = get_client_ip(0);
            $userinputDb = M('Classify_userinput');
            $insert_id = $userinputDb->add($insert_datas);
            if ($insert_id > 0) {
				if(C('config.classify_verify') == 0){
					$data_scrollmsg = array(
						'classify_id'=> $insert_id,
						'time' 		 => time(),
						'text' 		 => $this->user_session['nickname'].'发布了'.$classify_category_info['cat_name'].'信息',
					);
					M('Classify_scrollmsg')->data($data_scrollmsg)->add();
				    //定制发布成功增加积分
                    if($this->config['fabu_classify_score_add']>0){
                        D('User')->add_extra_score($uid,$this->config['fabu_classify_score_add'],'发布分类信息获得'.$this->config['fabu_classify_score_add'].'个'.$this->config['score_name']);
                        D('Scroll_msg')->add_msg('classify',$this->user_session['uid'],'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'发布分类信息获得'.$this->config['fabu_classify_score_add'].'个'.$this->config['score_name']);
                    }
                }
                if ($pay && $reward_publish > 0) {
                    $order_info = array(
                        'reward_id' => $insert_id,
                        'uid' => $uid,
                        'money' => $reward_publish,
                        'pay_type' => 3,
                        'status' => 1,
                        'type' => 2,
                        'add_time' => time()
                    );
                    M('Reward_order')->data($order_info)->add();
                }
                if($this->config['classify_verify']==0){
                    D('Classifynew')->add_spread_money($reward_publish,$this->user_session['uid']);
                    $userinputDb->where(array('id'=>$insert_id))->setField(array('is_spread_money'=>1));
                }

                if(C('config.classify_verify')){
                    $this->send_admin_template_message();
                }      
                if($this->is_weixin_browser){
                    $from_type = 0;
                }
                elseif($this->is_wxapp_browser){
                    $from_type = 3;
                }
                elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                    $from_type = 2;
                }
                elseif($this->is_app_browser){
                    $from_type = 1;
                }
                else{
                    $from_type = 0;
                }
                D('User_behavior_classify')->add_record($insert_id,$this->user_session['uid'],$tname,'用户'.str_replace_name($this->user_session['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'发布分类信息',time(),$from_type);          
                exit(json_encode(array('status'=>1,'msg'=>'发布成功！'.($this->config['classify_verify'] ? '请等待审核' : ''),'url'=>U('Classifynew/index'))));
            } else {
                if ($pay && $reward_publish > 0) {
                    $info = D('User')->add_money($this->user_session['uid'], $reward_publish, "发布打赏分类信息【".$tname."】失败, 余额添加 ". $reward_publish .C('config.Currency_txt')."");
                }
                exit(json_encode(array('status'=>0,'msg'=>'发布失败，请重试！')));
            }
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'访问页面有误！~~~')));
        }
    }

    /*推送管理员审核信息*/
    public function send_admin_template_message(){
        $admin_info = M('Admin')->where(array('openid' => array('neq', ''), 'status' => 1, 'classify_notice' => 1))->field('openid, realname, account')->select();
        if (!empty($admin_info)) {
            $user_info = M('User')->where(array('uid' => $this->user_session['uid']))->field('nickname')->find();
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            foreach ($admin_info as $val) {
                if ($val['openid']) {
                    $model->sendTempMsg('OPENTM405462911',
                        array(
                            'wecha_id' => $val['openid'],
                            'first' => $user_info['nickname'] . '用户提交同城信息发布审核，请及时登录系统后台进行处理',
                            'keyword1' => '同城信息发布审核提醒',
                            'keyword2' =>'未查看',
                            'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                            'keyword4' => $user_info['nickname'],
                            'remark' => '\n请及时登录系统后台进行处理！'
                        )
                    );
                }
            }
        }
    }

    // 生成用户发布查看金钱信息
    public function look_reward_pay_order() {
        $uid = (int)$_SESSION['user']['uid'];
        if($uid <= 0){
            exit(json_encode(array('code'=>2,'msg'=>'您还未登录，请先登录')));
        }
        $id = intval($_POST['id']);
        if(!$id){
            exit(json_encode(array('code'=>3,'msg'=>'传递参数有误')));
        }
        $info_pay = M('Reward_order')->where(array('uid'=>$uid,'reward_id'=>$id, 'status' => 1, 'type' => 2))->find();
        if ($info_pay) {
            // 如果已经打赏过了，提示打赏过了
            exit(json_encode(array('error'=>1,'msg'=>'已打赏')));
        }
        $tmp_data = M('Classify_userinput')->field(true)->where(array('id' => $id, 'status' => 1))->find();
        $category = D('Classify_category');
        $my_category = $category->field('cid,fcid,pfcid,subdir,cat_name')->where(array('cid' => $tmp_data['cid']))->find();
        // 获取主分类信息
        // 查询一下 主分类信息
        $cid = intval($my_category['cid']);
        $fcid = intval($my_category['fcid']);
        $pfcid = intval($my_category['$pfcid']);
        // 获取主分类信息
        $classify_id = $cid;
        if ($fcid && !$pfcid) {
            $classify_id = $fcid;
        } elseif ($fcid && $pfcid) {
            $classify_id = $pfcid;
        }
        $classify_category_info = M('Classify_category')->where(array('cid'=>$classify_id))->field('reward_type, reward_look')->find();
        // 该分类已被删除
        if (empty($classify_category_info)) {
            exit(json_encode(array('error'=>2,'msg'=>'该分类已被删除')));
        }
        $userInfo = D('User')->where(array('uid'=>$uid))->field('uid, now_money')->find();
        // 如果余额不足提示去充值
        $reward_look = floatval($classify_category_info['reward_look']);
        $now_money = floatval($userInfo['now_money']);
        $data = array(
            'now_money' => $userInfo['now_money'],  // 当前余额
            'reward_money' => $classify_category_info['reward_look'],  // 打赏金额
        );
        if($reward_look > $now_money){
            $data['difference'] = $reward_look - $now_money; // 差额
            exit(json_encode(array('error'=>3,'msg'=>'当前余额不足请充值', 'info' => $data)));
        }
        $data['difference'] = $now_money - $reward_look; // 差额

        exit(json_encode(array('error'=>5,'info'=>$data)));
    }

    // 用户打赏支付
    public function look_reward_pay() {
        $id = (int)$_POST['id'];
        $uid = (int)$_SESSION['user']['uid'];
        if($uid <= 0){
            exit(json_encode(array('code'=>2,'msg'=>'您还未登录，请先登录')));
        }
        if(!$id){
            exit(json_encode(array('code'=>3,'msg'=>'传递参数有误')));
        }
        // 查询一下是否已经支付过了
        $info_pay = M('Reward_order')->where(array('uid'=>$uid,'reward_id'=>$id, 'status' => 1, 'type' => 2))->find();
        if ($info_pay) {
            // 如果已经打赏过了，提示打赏过了
            exit(json_encode(array('error'=>1,'msg'=>'已打赏')));
        }
        $tmp_data = M('Classify_userinput')->field(true)->where(array('id' => $id, 'status' => 1))->find();
        $category = D('Classify_category');
        $my_category = $category->field('cid,fcid,pfcid,subdir,cat_name')->where(array('cid' => $tmp_data['cid']))->find();
        // 获取主分类信息
        // 查询一下 主分类信息
        $cid = intval($my_category['cid']);
        $fcid = intval($my_category['fcid']);
        $pfcid = intval($my_category['$pfcid']);
        // 获取主分类信息
        $classify_id = $cid;
        if ($fcid && !$pfcid) {
            $classify_id = $fcid;
        } elseif ($fcid && $pfcid) {
            $classify_id = $pfcid;
        }
        $classify_category_info = M('Classify_category')->where(array('cid'=>$classify_id))->field('reward_type, reward_look')->find();
        // 该分类已被删除
        if (empty($classify_category_info)) {
            exit(json_encode(array('error'=>2,'msg'=>'该分类已被删除')));
        }
        $userInfo = D('User')->where(array('uid'=>$uid))->field('uid, now_money')->find();
        // 如果余额不足提示去充值
        $reward_look = floatval($classify_category_info['reward_look']);
        $now_money = floatval($userInfo['now_money']);
        $data = array(
            'now_money' => $userInfo['now_money'],  // 当前余额
            'reward_money' => $classify_category_info['reward_look'],  // 打赏金额
        );
        if($reward_look > $now_money){
            $data['difference'] = $reward_look - $now_money; // 差额
            exit(json_encode(array('error'=>3,'msg'=>'当前余额不足请充值', 'info' => $data)));
        }
        $dec_money = D('User')->user_money($this->user_session['uid'], $reward_look, "查看打赏分类信息【".$tmp_data['title']."】扣除余额 ". $reward_look .C('config.Currency_txt')."");
        if(!$dec_money['error_code']){
            $order_info = array(
                'reward_id' => $id,
                'uid' => $uid,
                'money' => $reward_look,
                'pay_type' => 3,
                'status' => 1,
                'type' => 2,
                'add_time' => time()
            );
            M('Reward_order')->data($order_info)->add();
            exit(json_encode(array('error'=>1,'msg'=>'打赏成功')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=> $dec_money['msg'])));
        }
    }


    public function classify_modify(){
        if(IS_POST){
            if (empty($this->user_session)) {
                $this->error_tips('请先进行登录！', U('Login/index'));
                exit();
            }
            $uid = $this->user_session['uid'];
            $id = $_POST['id'] + 0;
            $datas = $this->Removalquotes($_POST);
            $cid = intval($datas['cid']);
            $fcid = intval($datas['fcid']);
            $sub3dir = isset($datas['subdir']) ? intval($datas['subdir']) : 0;
            if(isset($address['longitude'])){
                $longitude = $address['longitude'];
            }
            if(isset($address['latitude'])){
                $latitude = $address['latitude'];
            }
            if(isset($address['adress'])){
                $adress = $address['adress'];
            }
            if(isset($address['province'])){
                $province = $address['province'];
            }
            if(isset($address['city'])){
                $city = $address['city'];
            }
            if(isset($address['area'])){
                $area = $address['area'];
            }
			
			$description = str_replace("&amp;amp;nbsp;",chr(32),$datas['description']);
			if (empty($description)){
				$this->error_tips('信息内容必须填写！');
            }

            if(isset($datas['longitude'])){
                $longitude = $datas['longitude'];
            }
            if(isset($datas['latitude'])){
                $latitude = $datas['latitude'];
            }
            if(isset($datas['adress'])){
                $adress = $datas['adress'];
            }
            if(isset($datas['province'])){
                $province = $datas['province'];
            }
            if(isset($datas['city'])){
                $city = $datas['city'];
            }
            if(isset($datas['area'])){
                $area = $datas['area'];
            }
			
            $tname = $datas['tname'];
            if (empty($tname)){
                $tname = msubstr($description,0,100);
            }
			$description = str_replace(PHP_EOL,'<br/>',$description);
			
            $lxname = $datas['lxname'];
            $is_assure = $datas['is_assure'] + 0;
            $assure_money = $datas['assure_money'] + 0;
            if (empty($lxname))
                $this->error_tips('联系人名字必须填写！');

            $lxtel = $datas['lxtel'];
            $cost_price = $datas['cost_price'];
            $is_post_free = $datas['is_post_free'];
            $postage = $datas['postage'];
            if (empty($lxtel))
                $this->error_tips('联系手机号必须填写！');
            if (!$this->config['international_phone'] && !preg_match('/^([0-9]{2,4})-?[0-9]{5,20}$/', $lxtel))
                $this->error_tips('联系手机号格式不正确！');

			 if ($is_assure && $assure_money<=0) {
                $this->error_tips('担保金额必须大于0！');
            }

            if (isset($datas['is_assure']) && isset($datas['is_post_free']) && isset($datas['postage']) &&  $is_assure && $is_post_free==0 && $postage<=0) {
                exit(json_encode(array('status'=>0,'msg'=>'运费必须大于0！')));
            }

            $inputimg = isset($datas['inputimg']) ? $datas['inputimg'] : '';
            $inputs = $datas['input'];
            $newinputdatas = array();
            $inputfield = array();
            $expand = array();
            if (!empty($inputs)) {
                foreach ($inputs as $kk => $vv) {
                    $iswrite = intval($vv['iswrite']);
                    $input = intval($vv['input']);
                    if ($iswrite && empty($vv['vv'])) {
                        $this->error_tips('有必填项没有填写！');
                        exit();
                    }
                    $newinputdatas[] = $vv;
                    $isfilter = intval($vv['isfilter']);
                    if ($isfilter && ($input > 0) && ($input < 5)) {
                        $inputfield['input' . $input] = is_array($vv['vv']) ? implode(',', $vv['vv']) : ($vv['vv']?$vv['vv']:'');
                        //$k++;
                        if (isset($vv['unit']) && !empty($vv['unit'])) {
                            $expand['input' . $input] = array('unit' => $vv['unit']);
                        }
                        if (isset($vv['inarr'])) {
                            if (isset($expand['input' . $input])) {
                                $expand['input' . $input]['inarr'] = intval($vv['inarr']);
                            } else {
                                $expand['input' . $input] = array('inarr' => intval($vv['inarr']));
                            }
                        }
                    }
                }
            }
            unset($datas);
            $insert_datas = array('uid' => $uid, 'cid' => $cid, 'fcid' => $fcid, 'sub3dir' => $sub3dir, 'title' => $tname, 'lxname' => $lxname, 'lxtel' => $lxtel, 'imgs' => !empty($inputimg) ? serialize($inputimg) : '', 'description' => $description);
            if (!empty($inputfield))
                $insert_datas = array_merge($insert_datas, $inputfield);
            $insert_datas['content'] = !empty($newinputdatas) ? serialize($newinputdatas) : '';
            $insert_datas['addtime'] = $insert_datas['updatetime'] = time();
            $insert_datas['expand'] = !empty($expand) ? serialize($expand) : '';
            $insert_datas['status'] = $_POST['status'] + 0;
            $insert_datas['city_id'] = $this->config['now_city'];
            $insert_datas['is_assure'] = !empty($is_assure) ? $is_assure : 0;
            $insert_datas['assure_money'] = !empty($assure_money) ? $assure_money : 0;
            $insert_datas['cost_price'] = !empty($cost_price) ? floatval($cost_price) : '0.00'; // 原价
            $insert_datas['is_post_free'] = !empty($is_post_free) ? $is_post_free : 0; //是否包邮
            $insert_datas['postage'] = !empty($postage) ? $postage : '0.00'; // 邮费

			$insert_datas['status'] = C('config.classify_verify') ? 0 : 1;
            $insert_datas['longitude'] = $longitude ? $longitude : 0;
            $insert_datas['latitude'] = $latitude ? $latitude : 0;
            $insert_datas['address'] = $adress ? $adress : '';
            $insert_datas['province'] = $province ? $province : 0;
            $insert_datas['area'] = $area ? $area : 0;

            $userinputDb = M('Classify_userinput');


            $insert_id = $userinputDb->where(array('id'=>$id,'uid' => $uid))->data($insert_datas)->save();
            if ($insert_id > 0) {
				exit(json_encode(array('status'=>1,'msg'=>'修改成功！'.($this->config['classify_verify'] ? '请等待审核' : ''),'url'=>U('Classifynew/index'))));
            } else {
				exit(json_encode(array('status'=>0,'msg'=>'修改失败，请重试！')));
            }
        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }

    public function search(){
        $this->display();
    }

    /*     * *处理所搜请求* */

    public function get_Classify() {
        $kstr = $this->Removalquotes($_GET['kstr']);
        if (!empty($kstr)) {
            $Classify_userinputDb = M('Classify_userinput');
            $userinput_list = $Classify_userinputDb->where('title LIKE "%' . $kstr . '%" AND status = 1 AND city_id = '.$this->config['now_city'])->select();

            foreach($userinput_list as $Key=>$userinput){
                $userinput_list[$Key]['url'] = U('Classify/ShowDetail',array('vid'=>$userinput['id']));
            }
            $this->dexit(array('error' => 0, 'data' => $userinput_list));

        }
        $this->dexit(array('error' => 1, 'data' => array()));
    }

    public function buy(){
        if(IS_POST){
            if(empty($this->user_session)){
                // $this->assign('jumpUrl',U('Index/Login/index'));
                // $this->error_tips('请先登录！');
                exit(json_encode(array('status'=>-1,'msg'=>'请先登录！','url'=>U('Index/Login/index'))));
            }

            $classify_userinput_id = $_POST['classify_userinput_id'] + 0;
            $database_classify_userinput = D('Classify_userinput');
            $classify_userinput_where['id'] = $classify_userinput_id;
            $classify_userinput_where['status'] = 1;
            
            $classify_userinput_detail = $database_classify_userinput->where($classify_userinput_where)->find();

            if(empty($classify_userinput_detail)){
                // $this->error_tips('该信息不存在或已删除！');
                exit(json_encode(array('status'=>0,'msg'=>'该信息不存在或已删除！')));
            }

            $database_classify_order = D('Classify_order');
            $result = $database_classify_order->save_post_form($classify_userinput_detail , $this->user_session['uid']);

            if(!empty($result['flag'])){
                exit(json_encode(array('status'=>3,'msg'=>$result['msg'],'url'=>U('My/my_money'))));
                // $this->redirect('chk_buy',array('order_id'=>$result['order_id'],'num'=>$result['num']));
                exit;
            }

            if($result['error'] == 1){
                // $this->error_tips($result['msg']);
                exit(json_encode(array('status'=>0,'msg'=>$result['msg'])));
            }


            $user_info = M('Classify_order')->field(true)->where(array('classify_userinput_id'=>$classify_userinput_id))->find();
            $buyer = M('User')->field(true)->where(array('uid'=>$user_info['uid']))->find();//买方用户信息
            $seller = M('User')->field(true)->where(array('uid'=>$user_info['seller_user_id']))->find();//卖方用户信息
            if ($buyer['openid'] && $seller['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('TM00017', array(
                    'wecha_id' => $buyer['openid'],
                    'first' => '您已购买【'.$user_info['order_name'].'】',
                    'OrderSn' => $user_info['order_id'],
                    'OrderStatus' => '购买成功',
                    'remark' => date('Y-m-d H:i:s')
                ));//买方模板推送
                $model->sendTempMsg('TM00017', array(
                    'wecha_id' => $seller['openid'],
                    'first' => '您发布的【'.$user_info['order_name'].'】已被购买',
                    'OrderSn' => $user_info['order_id'],
                    'OrderStatus' => '已被购买',
                    'remark' => date('Y-m-d H:i:s')
                ));//卖方模板推送
            }
            exit(json_encode(array('status'=>1,'msg'=>'支付成功！','url'=>U('Classifynew/order_detail',array('id'=>$result['order_id'])),'backurl'=>urlencode(U('Classifynew/view',array('id'=>$classify_userinput_id))))));
            // $this->success_tips('支付成功！',U('Classifynew/view',array('id'=>$classify_userinput_id)));
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'访问页面有误！')));
            // $this->error_tips('访问页面有误！~~~');
        }
    }

    public function chk_buy(){
        $order_id = $_GET['order_id'] + 0;
        $classify_order_where['order_id'] = $order_id;
        $database_classify_order = D('Classify_order');
        $now_order = $database_classify_order->where($classify_order_where)->find();

        if(empty($now_order)){
            $this->error_tips('订单不存在 ！');
        }

        $this->assign('now_order' , $now_order);
        $this->display();
    }

    public function order(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录！', U('Login/index'));
        }

        $adress_id = $_GET['adress_id'] + 0;
        $database_user_adress = D('User_adress');
        $now_user_adress = $database_user_adress->get_one_adress($this->user_session['uid'] , $adress_id);

        $classify_userinput_id = $_GET['classify_userinput_id'] + 0;
        $database_classify_userinput = D('Classify_userinput');

        $classify_userinput_condition['id'] = $classify_userinput_id;
        $classify_userinput_condition['status'] = 1;

        $classify_userinput_detail = $database_classify_userinput->where($classify_userinput_condition)->find();
        $classify_userinput_detail['imgs'] = unserialize($classify_userinput_detail['imgs']);


        if(empty($classify_userinput_detail)){
            $this->error_tips('该信息不存在 ！');
        }

        if($classify_userinput_detail['uid'] == $this->user_session['uid']){
            $this->error_tips('当前用户不可进行购买 ！');
        }

        $database_classify_order = D('Classify_order');
        $classify_order_where['paid'] = 1;
        $classify_order_where['classify_userinput_id'] = $_GET['classify_userinput_id'] + 0;
        $classify_order_info = $database_classify_order->where($classify_order_where)->find();
        if(!empty($classify_order_info)){
            $this->error_tips('该信息已被人购买！');
        }
        if (!$classify_userinput_detail['is_post_free']) {
            $classify_userinput_detail['total_price'] = round(floatval($classify_userinput_detail['postage'] + $classify_userinput_detail['assure_money']),2);
        }else{
            $classify_userinput_detail['total_price'] = round(floatval($classify_userinput_detail['assure_money']),2);
        }

        $this->assign('classify_userinput_detail' , $classify_userinput_detail);
        $this->assign('now_user_adress' , $now_user_adress);
        $this->display();
    }

    public function submit(){
        header('Content-Type: application/json; charset=utf-8');
        $quantity = intval(I('q'));
        $order_id = $_GET['order_id'] + 0;
        $databse_classify_order = D('Classify_order');

        if($quantity < 1){
            exit(json_encode(array('status'=>0,'info'=>'最少需要参与1次')));
        }

        $classify_order_where['uid'] = $this->user_session['uid'];
        $classify_order_where['order_id'] = $order_id;
        $now_order = $databse_classify_order->where($classify_order_where)->find();

        $database_classify_userinput = D('Classify_userinput');
        $classify_userinput_id = $now_order['classify_userinput_id'];
        $classify_userinput_condition['id'] = $classify_userinput_id;
        $classify_userinput_condition['status'] = 1;

        $now_classify_userinput = $database_classify_userinput->where($classify_userinput_condition)->find();
        if(empty($now_classify_userinput)){
            exit(json_encode(array('status'=>0,'info'=>'该礼品不存在')));
        }

        $now_user = D('User')->get_user($this->user_session['uid']);
        if(empty($now_user)){
            exit(json_encode(array('status'=>0,'info'=>'未获取到您的帐号信息，请重试')));
        }

        $use_money =$now_order['total_price'];
        if($now_user['now_money'] < $use_money){
            exit(json_encode(array('status'=>-4,'info'=>'您的帐户余额为 <span>'.$now_user['now_money'].'</span> '.C('config.Currency_txt').'，请先充值帐户余额','recharge'=>$use_money-$now_user['now_money'])));
        }

        $save_result = D('User')->user_money($now_user['uid'],$use_money,'参加兑换 '.$now_order['order_name'].' * '.$now_order['num'] , 3 ,$now_order['order_id']);
        if($save_result['error_code']){
            exit(json_encode(array('status'=>0,'info'=>$save_result['error_code'])));
        }

        $order_param['order_id'] = $order_id;
        $order_param['num'] = $quantity;
        $order_param['is_source'] = 1;
        $order_param['is_mobile'] = 1;
        $result = $databse_classify_order->after_pay($order_param);

        if(!$result['error']){
            $this->success_tips('付款成功！',U('ShowDetail',array('vid'=>$classify_userinput_id)));
        }
    }


    public function ajax_chk_classify(){
        if(IS_AJAX){
            $order_id = $_GET['order_id'] + 0;
            if(empty($order_id)){
                exit(json_encode(array('status'=>0,'msg'=>'传递参数有误！')));
            }

            if(empty($this->user_session)){
                exit(json_encode(array('status'=>0,'msg'=>'未获取到您的帐号信息，请重试！~~~')));
            }

            $database_classify_order = D('Classify_order');

            $classify_order_condition['order_id'] = $order_id;
            // $classify_order_condition['uid'] = $this->user_session['uid'];
            $now_order = $database_classify_order->where($classify_order_condition)->find();
            $classify_userinput = D("Classify_userinput")->where(array('id'=>$now_order['classify_userinput_id']))->field('is_assure,uid')->find();
            if($classify_userinput['is_assure']>0){
                $now_order['total_price']  = $now_order['total_price'] - $now_order['total_price'] * (C('config.classify_proportion_full') / 100);
            }
            if ($classify_userinput['uid']!=$this->user_session['uid'] && $now_order['uid']!=$this->user_session['uid']) {
                exit(json_encode(array('status'=>0,'msg'=>'您没有权限操作~~~')));
            }
            $classify_order_data['status'] = 1;
            
            $classify_order_data['delivery_time'] = time();
            if($now_order['is_pick_in_store'] == 1){//如果为自提
                $classify_order_data['logistics_status'] = 4;
            }else{
                $classify_order_data['logistics_status'] = 3;
            }
            $update_id = $database_classify_order->where($classify_order_condition)->data($classify_order_data)->save();

            if(!empty($update_id)){
                if(empty($now_order['seller_user_id'])){
                    $database_classify_userinput = D('Classify_userinput');
                    $userinput_where['id'] = $now_order['classify_userinput_id'];
                    $seller_user_id = $database_classify_userinput->where($userinput_where)->getField('uid');
                }else{
                    $seller_user_id = $now_order['seller_user_id'];
                }

                $database_user = D('User');
                $now_user = $database_user->get_user($seller_user_id);
                $data_user['now_money'] = $now_user['now_money'] + $now_order['total_price'];
                $condition_user_where['uid'] = $seller_user_id;
                $save_result = $database_user->where($condition_user_where)->data($data_user)->save();
                if($save_result){
                    
                    D('User_money_list')->add_row($seller_user_id, 1, $now_order['total_price'], '分类信息：' . $now_order['order_name'] . ' * 1 自动打款', true);

                    $user_info = $database_classify_order->field(true)->where(array('order_id'=>$order_id))->find();
                    $buyer = M('User')->field(true)->where(array('uid'=>$user_info['uid']))->find();//买方用户信息
                    $seller = M('User')->field(true)->where(array('uid'=>$user_info['seller_user_id']))->find();//卖方用户信息
                    if ($buyer['openid'] && $seller['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('TM00017', array(
                            'wecha_id' => $buyer['openid'],
                            'first' => '您购买的【'.$user_info['order_name'].'】已收货',
                            'OrderSn' => $user_info['order_id'],
                            'OrderStatus' => '已收货',
                            'remark' => date('Y-m-d H:i:s')
                        ));//买方模板推送
                        $model->sendTempMsg('TM00017', array(
                            'wecha_id' => $seller['openid'],
                            'first' => '您发布的【'.$user_info['order_name'].'】已被买家收货',
                            'OrderSn' => $user_info['order_id'],
                            'OrderStatus' => '已收货',
                            'remark' => date('Y-m-d H:i:s')
                        ));//卖方模板推送
                    }
                    exit(json_encode(array('status'=>1,'msg'=>'收货成功！')));
                }else{
                    exit(json_encode(array('status'=>0,'msg'=>'收货失败！')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'收货失败！')));
            }
        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }

    /*
	 *图片上传
	 * 
	 */
    public function ajaxImgUpload() {
        $filename = trim($_POST['filename']);
        $img = $_POST[$filename];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $imgdata = base64_decode($img);
        $getupload_dir = "/upload/images/classify/" . date('Ymd');
        $upload_dir = "." . $getupload_dir;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $newfilename = 'classify_' . date('YmdHis') . '.jpg';
        $save = file_put_contents($upload_dir . '/' . $newfilename, $imgdata);
        if ($save) {
            $this->dexit(array('error' => 0, 'data' => array('code' => 1, 'siteurl'=>$this->config['site_url'],'imgurl' =>$getupload_dir . '/' . $newfilename, 'msg' => '')));
        } else {
            $this->dexit(array('error' => 1, 'data' => array('code' => 0, 'url' => '', 'msg' => '保存失败！')));
        }
    }

    /*     * json 格式封装函数* */
    private function dexit($data = '') {
        if (is_array($data)) {
            echo json_encode($data);
        } else {
            echo $data;
        }
        exit();
    }

    /* 地图 */
    public function adres_map(){
        $params = $_GET;
        unset($params['adress_id']);
        $this->assign('params',$params);
        //得到所有城市并以城市首拼排序
        $database_area = D('Area');
        $all_city = S('all_city_address');
        if(empty($all_city)){
            $database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`';
            $condition_all_city['area_type'] = 2;
            $all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
            foreach($all_city_old as $key=>$value){
                //首拼转成大写
                if(!empty($value['first_pinyin'])){
                    $first_pinyin = strtoupper($value['first_pinyin']);
                    $all_city[$first_pinyin][] = $value;
                }
            }
            S('all_city_address',$all_city);
        }
        $this->assign('all_city',$all_city);
        
        $this->display();
    }

    public function select_area(){
        $area_list = D('Area')->get_arealist_by_areaPid($_POST['pid']);
        if(!empty($area_list)){
            $return['error'] = 0;
            $return['list'] = $area_list;
        }else{
            $return['error'] = 1;
        }
        echo json_encode($return);
    }
    public function adress_map(){
        $this->display();
    }
    //路线导航
    public function get_route(){
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
        
        $this->assign('long_lat',$long_lat);

        $now_classify = D('Classify_userinput')->field(true)->where(array('id'=>$_GET['vid']))->find();
        if(empty($now_classify)){
            $this->error_tips('该分类信息不存在！');
        }
        $this->assign('now_classify',$now_classify);
        $this->assign('no_gotop',true);
        $this->display();
    }


    public function ajax_send_classify(){
        if(IS_AJAX){
            $order_id = $_GET['order_id'] + 0;
            if(empty($order_id)){
                exit(json_encode(array('status'=>0,'msg'=>'传递参数有误！')));
            }

            if(empty($this->user_session)){
                exit(json_encode(array('status'=>0,'msg'=>'未获取到您的帐号信息，请重试！~~~')));
            }

            $database_classify_order = D('Classify_order');

            $classify_order_condition['order_id'] = $order_id;
            $classify_order_condition['seller_user_id'] = $this->user_session['uid'];
            $now_order = $database_classify_order->where($classify_order_condition)->find();
            $classify_userinput = D("Classify_userinput")->where(array('id'=>$now_order['classify_userinput_id']))->field('is_assure')->find();
            if($classify_userinput['is_assure']>0){
                $now_order['total_price']  = $now_order['total_price'] - $now_order['total_price'] * (C('config.classify_proportion_full') / 100);
            }
            $classify_order_data['logistics_status'] = 1;
            $classify_order_data['send_time'] = time();
            $update_id = $database_classify_order->where($classify_order_condition)->data($classify_order_data)->save();

            if(!empty($update_id)){
                $user_info = M('Classify_order')->field(true)->where(array('order_id'=>$order_id))->find();
                $buyer = M('User')->field(true)->where(array('uid'=>$user_info['uid']))->find();//买方用户信息
                $seller = M('User')->field(true)->where(array('uid'=>$user_info['seller_user_id']))->find();//卖方用户信息
                if ($buyer['openid'] && $seller['openid']) {
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('TM00017', array(
                        'wecha_id' => $buyer['openid'],
                        'first' => '您购买的【'.$user_info['order_name'].'】已发货',
                        'OrderSn' => $user_info['order_id'],
                        'OrderStatus' => '已发货',
                        'remark' => date('Y-m-d H:i:s')
                    ));//买方模板推送
                    $model->sendTempMsg('TM00017', array(
                        'wecha_id' => $seller['openid'],
                        'first' => '您发布的【'.$user_info['order_name'].'】已发货',
                        'OrderSn' => $user_info['order_id'],
                        'OrderStatus' => '已发货',
                        'remark' => date('Y-m-d H:i:s')
                    ));//卖方模板推送
                }
                exit(json_encode(array('status'=>1,'msg'=>'发货成功！')));
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'发货失败！')));
            }
        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }

    public function ajax_add_express(){
        $data['express_type'] = $_POST['express_type'];
        $data['express_id'] = $_POST['express_id'];
        $data['order_id'] = $_POST['order_id'];
        if($data['express_type'] && $data['express_id'] && $data['order_id']){
            $res = M('Classify_order')->where(array('order_id'=>$data['order_id']))->save($data);
            if($res){
                exit(json_encode(array('status'=>0,'msg'=>'添加快递信息成功')));
            }else{
                exit(json_encode(array('status'=>1,'msg'=>'添加快递信息失败')));
            }
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'参数信息错误')));
        }
    }
    public function object_to_array($obj) {
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
                return;
            }

            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array)object_to_array($v);
            }
        }

        return $obj;
    }    
}
