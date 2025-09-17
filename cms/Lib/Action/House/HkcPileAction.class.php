<?php


class HkcPileAction extends BaseAction
{
    public $status = [0 => '离线', 1 => '在线'];
    public $device_status = [0 => '注册', 1 => '上线', 2 => '禁用', 3 => '设备故障', 4 => '已拆机', 5 => '测试设备'];
    //0：电动两轮车设备   1：汽车桩设备
    public $device_family = [0 => '电动两轮车设备', 1 => '汽车桩设备'];

    public function charg_area_list()
    {
        $village_id = $this->house_session['village_id'];
        $area_name = isset($_GET['area_name']) ? trim($_GET['area_name']) : '';
        $whereArr = array('village_id' => $village_id, 'is_del' => '0');
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $is_set_ok = 0;
        if ($this->config['hao_kuai_user_no'] && $this->config['hao_kuai_password'] && $this->config['hao_kuai_apply_phone'] && $this->config['hao_kuai_apply_name']) {
            $is_set_ok = 1;
        }
        $haokuaichong_rate = array();
        if ($village_haokuaichong && $village_haokuaichong['project_id']) {
            $whereArr = array('village_id' => $village_id, 'project_id' => $village_haokuaichong['project_id'], 'del_time' => '0', 'rate_id' => array('neq', ''));
            $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereArr)->field('*')->find();
        }
        $whereArr = array('del_time' => array('lt', 10), 'village_id' => $village_id);
        if ($area_name) {
            $whereArr['area_name'] = $area_name;
        }
        $haokuaichong_region = M('House_village_haokuaichong_region')->where($whereArr)->field('*')->select();
        if ($haokuaichong_region) {
            foreach ($haokuaichong_region as $kk => $vv) {
                $haokuaichong_region[$kk]['add_time_str'] = date($vv['add_time']);
            }
        }
        $this->assign('haokuaichong_region', $haokuaichong_region);
        $this->assign('is_set_ok', $is_set_ok);
        $this->assign('village_haokuaichong', $village_haokuaichong);
        $this->assign('haokuaichong_rate', $haokuaichong_rate);
        $this->display();
    }

    public function add_project_area_page()
    {
        $project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : '';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'project_id' => $project_id);
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $hkcregion = array();
        if ($id > 0) {
            $whereArr = array('village_id' => $village_id, 'id' => $id);
            $hkcregion = M('House_village_haokuaichong_region')->where($whereArr)->find();
        }
        $whereArr = array('del_time' => array('lt', 10), 'village_id' => $village_id, 'rate_id' => array('neq', ''));
        $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereArr)->field('rate_id,rate_name,rate_type')->select();
        if ($haokuaichong_rate) {
            foreach ($haokuaichong_rate as $kk => $rvv) {
                $haokuaichong_rate[$kk]['rate_type'] = $rvv['rate_type'] == 'B1' ? '计时' : ($rvv['rate_type'] == 'B2' ? '计量' : '');
            }
        }
        $this->assign('hkcrate', $haokuaichong_rate);
        $this->assign('hkcregion', $hkcregion);
        $this->assign('idd', $id);
        $this->assign('project_id', $project_id);
        $this->assign('village_haokuaichong', $village_haokuaichong);
        $this->display();
    }

    public function add_project_region()
    {
        $project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : 0;
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'project_id' => $project_id);
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $retInfoArr = array();
        $retInfoArr['err_code'] = 0;
        $retInfoArr['err_msg'] = '';
        if (empty($village_haokuaichong)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '对接小区不存在！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $area_name = isset($_POST['area_name']) ? trim($_POST['area_name']) : '';
        if (empty($area_name)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请填写充电区名称！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $common_id = isset($_POST['common_id']) ? trim($_POST['common_id']) : '';
        if (empty($common_id)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请选择一个费率套餐！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $area_type = isset($_POST['area_type']) ? trim($_POST['area_type']) : '';  //0充电桩 1充电柜
        $area_type = 0;  //目前只支持充电桩
        $area_marks = isset($_POST['area_marks']) && $_POST['area_marks'] ? trim($_POST['area_marks']) : '';
        $id = isset($_POST['idd']) ? intval($_POST['idd']) : 0;

        $saveData = array('village_id' => $village_id, 'area_name' => $area_name, 'area_type' => $area_type, 'area_marks' => $area_marks);
        $saveData['project_id'] = $village_haokuaichong['project_id'];
        $saveData['common_id'] = $common_id;
        $haokuaichong_region = array();
        if ($id > 0) {
            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $haokuaichong_region = M('House_village_haokuaichong_region')->where($whereArr)->find();
        }

        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $apiRet = array();
        if (!empty($haokuaichong_region) && !empty($haokuaichong_region['area_id'])) {
            $postData = array('name' => $area_name);
            $postData['marks'] = $area_marks;
            $saveData['update_time'] = time();
            $rateArr = array();
            if ($common_id) {
                $rateArr = array('commonId' => $common_id);
            }
            M('House_village_haokuaichong_region')->where(array('id' => $haokuaichong_region['id']))->data($saveData)->save();
            //echo  M('House_village_haokuaichong_region')->getLastSql();
            $apiRet = $pileHaoKuaiChongApi->editProjectArea($postData, $haokuaichong_region['area_id'], $rateArr);
        } elseif (!empty($haokuaichong_region) && empty($haokuaichong_region['area_id'])) {
            $postData = array('areaName' => $area_name, 'commonId' => $common_id);
            $postData['marks'] = $area_marks;
            $postData['areaType'] = $area_type;
            $saveData['update_time'] = time();
            M('House_village_haokuaichong_region')->where(array('id' => $haokuaichong_region['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->addProjectArea($postData, $village_haokuaichong['project_id'], $haokuaichong_region['id']);
        } else {
            $postData = array('areaName' => $area_name, 'commonId' => $common_id);
            $postData['marks'] = $area_marks;
            $postData['areaType'] = $area_type;
            $saveData['village_id'] = $village_id;
            $saveData['add_time'] = time();
            $idd = M('House_village_haokuaichong_region')->data($saveData)->add();
            $apiRet = $pileHaoKuaiChongApi->addProjectArea($postData, $village_haokuaichong['project_id'], $idd);
        }
        echo json_encode($apiRet, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function get_project_area_list()
    {
        $village_id = $this->house_session['village_id'];
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $whereArr = array('village_id' => $village_id);
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $pileHaoKuaiChongApi->getProjectAreaList($village_haokuaichong['project_id']);

    }

    public function charg_rate_list()
    {
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'is_del' => '0');
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $is_set_ok = 0;
        if ($this->config['hao_kuai_user_no'] && $this->config['hao_kuai_password'] && $this->config['hao_kuai_apply_phone'] && $this->config['hao_kuai_apply_name']) {
            $is_set_ok = 1;
        }
        $rate_name = isset($_GET['rate_name']) ? trim($_GET['rate_name']) : '';
        $whereArr = array('del_time' => array('lt', 10), 'village_id' => $village_id);
        if ($rate_name) {
            $whereArr['rate_name'] = $rate_name;
        }
        $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereArr)->field('*')->select();
        if ($haokuaichong_rate) {
            foreach ($haokuaichong_rate as $kk => $vv) {
                $rate_detail = $vv['rate_detail'] ? json_decode($vv['rate_detail'], 1) : '';
                unset($vv['rate_detail']);
                $rate_detail_str = '';
                if ($rate_detail) {
                    if ($vv['rate_type'] == 'B2') {
                        $rate_detail_str = '每度电' . $rate_detail['0']['standard'] . '元';
                    } elseif ($vv['rate_type'] == 'B1') {
                        foreach ($rate_detail as $dvv) {
                            $rate_detail_str .= '功率在' . $dvv['minPower'] . '瓦到' . $dvv['maxPower'] . '瓦间每小时' . $dvv['standard'] . '元<br>';
                        }
                    }
                }
                $haokuaichong_rate[$kk]['rate_type_str'] = '计时';
                if ($vv['rate_type'] == 'B2') {
                    $haokuaichong_rate[$kk]['rate_type_str'] = '计量';
                }
                $haokuaichong_rate[$kk]['rate_detail_str'] = $rate_detail_str;
                $haokuaichong_rate[$kk]['is_fund_str'] = $vv['is_fund'] > 0 ? '退费' : '不退费';
                $haokuaichong_rate[$kk]['add_time_str'] = $vv['add_time'] > 0 ? date('Y-m-d H:i:s') : '';
            }
        }
        $this->assign('haokuaichong_rate', $haokuaichong_rate);
        $this->assign('is_set_ok', $is_set_ok);
        $this->assign('village_haokuaichong', $village_haokuaichong);
        $this->display();
    }

    public function create_project_village()
    {
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'is_del' => '0');
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        if (empty($village_haokuaichong)) {
            $d_house_village = D('House_village');
            $village_info = $d_house_village->where(array('village_id' => $village_id))->find();
            $village_haokuaichong = array('id' => 0);
            $village_haokuaichong['project_name'] = $village_info['village_name'];
            $streetaddress = '';
            if ($village_info['street_id']) {
                $area_street = M('Area_street')->where(array('area_id' => $village_info['street_id']))->field('area_name')->find();
                if ($area_street) {
                    $streetaddress = $area_street['area_name'];
                }
            }
            if ($village_info['community_id']) {
                $area_street = M('Area_street')->where(array('area_id' => $village_info['community_id']))->field('area_name')->find();
                if ($area_street) {
                    $streetaddress .= $area_street['area_name'];
                }
            }
            $village_haokuaichong['address'] = $streetaddress . $village_info['village_address'];
        }
        $province_data = array();
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $workUnit = $pileHaoKuaiChongApi->getWorkUnit();
        if (isset($workUnit['data']) && $workUnit['data']) {
            $province_data = $workUnit['data'];
        }
        $this->assign('province_data', $province_data);
        $this->assign('vinfo', $village_haokuaichong);
        $this->display();
    }

    public function opt_create_project_village()
    {
        $village_id = $this->house_session['village_id'];
        $project_name = isset($_POST['project_name']) ? trim($_POST['project_name']) : '';
        $phone_name = isset($_POST['phone_name']) ? trim($_POST['phone_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $province_code = isset($_POST['province_code']) ? trim($_POST['province_code']) : '';
        $province_name = isset($_POST['province_name']) ? trim($_POST['province_name']) : '';
        $city_code = isset($_POST['city_code']) ? trim($_POST['city_code']) : '';
        $city_name = isset($_POST['city_name']) ? trim($_POST['city_name']) : '';
        $area_code = isset($_POST['area_code']) ? trim($_POST['area_code']) : '';
        $area_name = isset($_POST['area_name']) ? trim($_POST['area_name']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $saveData = array();
        $postData = array();
        if (empty($project_name)) {
            $this->error('小区名称必须正确填写！');
        }
        $saveData['project_name'] = $project_name;
        $postData['projectName'] = $project_name;

        if (empty($phone_name)) {
            $this->error('小区联系人名称必须正确填写！');
        }
        $saveData['phone_name'] = $phone_name;
        $postData['phoneName'] = $phone_name;

        if (empty($phone) || strlen($phone) != 11) {
            $this->error('小区联系人电话必须正确填写！');
        }
        $saveData['phone'] = $phone;
        $postData['phone'] = $phone;

        if (empty($address)) {
            $this->error('请填写小区具体地址！');
        }
        $saveData['address'] = $address;
        $postData['address'] = $address;

        if (empty($area_code)) {
            $this->error('请选择省市区！');
        }
        $city_info = array('province_code' => $province_code, 'province_name' => $province_name, 'city_code' => $city_code, 'city_name' => $city_name, 'area_code' => $area_code, 'area_name' => $area_name);
        $saveData['abcode'] = $area_code;
        $saveData['city_info'] = json_encode($city_info, JSON_UNESCAPED_UNICODE);
        $postData['code'] = $area_code;
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $userId = $pileHaoKuaiChongApi->getkUserId();
        $saveData['user_id'] = $userId;
        $whereArr = array('village_id' => $village_id, 'is_del' => '0');
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $apiRet = array();
        if (!empty($village_haokuaichong) && $village_haokuaichong['project_id']) {
            unset($postData['code']);
            M('House_village_haokuaichong')->where(array('id' => $village_haokuaichong['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->editProjectVillage($postData, $village_haokuaichong['project_id']);
        } elseif (!empty($village_haokuaichong) && empty($village_haokuaichong['project_id'])) {
            $saveData['update_time'] = time();
            M('House_village_haokuaichong')->where(array('id' => $village_haokuaichong['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->createProjectVillage($postData, $village_haokuaichong['id']);
        } else {
            $saveData['village_id'] = $village_id;
            $saveData['update_time'] = time();
            $idd = M('House_village_haokuaichong')->data($saveData)->add();
            $apiRet = $pileHaoKuaiChongApi->createProjectVillage($postData, $idd);
        }
        if ($apiRet && $apiRet['err_code'] == 0) {
            $this->success($apiRet['err_msg'], U('charg_area_list'));
        } else {
            $err_msg = $apiRet && $apiRet['err_msg'] ? $apiRet['err_msg'] : '操作失败了！';
            $this->success($err_msg, U('charg_area_list'));
        }
    }

    public function get_work_unit_info()
    {
        $region_code = isset($_POST['region_code']) ? trim($_POST['region_code']) : '';
        $region_type = isset($_POST['region_type']) ? trim($_POST['region_type']) : 0;  //0省 1市 2区县
        import('@.ORG.PileHaoKuaiChong');
        $village_id = $this->house_session['village_id'];
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $workUnit = $pileHaoKuaiChongApi->getWorkUnit($region_code);
        $region_data = array();
        if (isset($workUnit['data']) && $workUnit['data']) {
            $region_data = $workUnit['data'];
        }
        $retrunArr = array('region_data' => $region_data, 'region_type' => $region_type);
        echo json_encode($retrunArr, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function get_project_village()
    {
        $project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'project_id' => $project_id);
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $projectInfo = $pileHaoKuaiChongApi->getProjectInfo($project_id, $village_haokuaichong['id']);
        echo json_encode($projectInfo, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function add_project_rate_page()
    {
        $project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : '';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'project_id' => $project_id);
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $haokuaichong_rate = array();
        $rate_detail = array(["minPower" => "0", "maxPower" => "220", "standard" => "0.25"], ["minPower" => "221", "maxPower" => "500", "standard" => "0.5"], ["minPower" => "500", "maxPower" => "1000", "standard" => "1"], ["minPower" => "1001", "maxPower" => "1500", "standard" => "1.5"], ["minPower" => "1501", "maxPower" => "2000", "standard" => "2"], ["minPower" => "2001", "maxPower" => "2001", "standard" => "2"]);
        if ($id > 0) {
            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereArr)->field('*')->find();
            if ($haokuaichong_rate && $haokuaichong_rate['rate_detail']) {
                $haokuaichong_rate['standard'] = '';
                $rate_detail = json_decode($haokuaichong_rate['rate_detail'], true);
                if ($haokuaichong_rate['rate_type'] == 'B2') {
                    $haokuaichong_rate['standard'] = $rate_detail ? $rate_detail[0]['standard'] : '';
                }
            }
        }
        $this->assign('rate_detail', $rate_detail);
        $this->assign('hkcrate', $haokuaichong_rate);
        $this->assign('idd', $id);
        $this->assign('project_id', $project_id);
        $this->assign('village_haokuaichong', $village_haokuaichong);
        $this->display();
    }

    public function add_project_rate()
    {
        $project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : 0;
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'project_id' => $project_id);
        $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
        $retInfoArr = array();
        $retInfoArr['err_code'] = 0;
        $retInfoArr['err_msg'] = '';
        if (empty($village_haokuaichong)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '对接小区不存在！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $rate_name = isset($_POST['rate_name']) ? trim($_POST['rate_name']) : '';
        if (empty($rate_name)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请填写费率套餐名称！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $rate_type = isset($_POST['rate_type']) ? trim($_POST['rate_type']) : '';  //B1计时  B2计量
        $standard = isset($_POST['standard']) ? trim($_POST['standard']) : '';
        if (empty($rate_type)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请选择费率模式！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        if ($rate_type == 'B2' && $standard <= 0) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请填写计量费用！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $is_fund = isset($_POST['is_fund']) ? intval($_POST['is_fund']) : 0;
        $itemset = isset($_POST['itemset']) ? $_POST['itemset'] : '';
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        $rate_detail = array();
        if ($rate_type == 'B1') {
            if (empty($itemset) || !is_array($itemset)) {
                $retInfoArr['err_code'] = 1;
                $retInfoArr['err_msg'] = '请填写计时档位数据！';
                echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
                exit();
            }
            $steps_err_msg = '';
            $last_item = '';
            foreach ($itemset as $kk => $vv) {
                if ($kk == 0 && $vv['maxPower'] <= 0) {
                    $steps_err_msg = '第1项设置的最大功率填写错误！';
                    break;
                }
                if ($kk == 0 && $vv['standard'] <= 0) {
                    $steps_err_msg = '第1项设置的计费金额填写错误！';
                    break;
                }

                if ($kk > 0) {
                    $mkc = $kk - 1;
                    $pre_maxPower = $itemset[$mkc]['maxPower'] * 1 + 1;
                    if ($vv['minPower'] > 0 && $vv['minPower'] != $pre_maxPower) {
                        $steps_err_msg = '第' . ($kk + 1) . '项的最小功率和第' . $mkc . '项的最大功率设置的不连贯！';
                        break;
                    }
                    if ($vv['minPower'] > 0 && $vv['minPower'] > $vv['maxPower']) {
                        $steps_err_msg = '第' . ($kk + 1) . '项最大功率不能小于最小功率！';
                        break;
                    }
                    if ($vv['minPower'] > 0 && $vv['standard'] <= 0) {
                        $steps_err_msg = '第' . ($kk + 1) . '设置的计费金额填写错误！';
                        break;
                    }
                    if ($vv['minPower'] <= 0 && $vv['maxPower'] > 0) {
                        $steps_err_msg = '第' . ($kk + 1) . '项最小功率填写错误！';
                        break;
                    }
                    if ($vv['minPower'] <= 0 && $vv['maxPower'] <= 0) {
                        continue;
                    }
                }
                $last_item = $vv;
                $rate_detail[] = $vv;
            }
            if ($last_item['minPower'] != $last_item['maxPower']) {
                $steps_err_msg = '最后一档最小功率和最大功率必须相等';
            }
            if ($steps_err_msg) {
                $retInfoArr['err_code'] = 1;
                $retInfoArr['err_msg'] = $steps_err_msg;
                echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else if ($rate_type == 'B2') {
            $rate_detail[] = array('standard' => $standard);
        }
        $id = isset($_POST['idd']) ? intval($_POST['idd']) : 0;
        $rate_detail_str = json_encode($rate_detail, JSON_UNESCAPED_UNICODE);
        $saveData = array('village_id' => $village_id, 'rate_name' => $rate_name, 'rate_type' => $rate_type, 'rate_detail' => $rate_detail_str);
        $saveData['is_fund'] = $is_fund;
        $saveData['project_id'] = $village_haokuaichong['project_id'];
        $saveData['remarks'] = $remarks;
        $haokuaichong_rate = array();
        if ($id > 0) {
            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereArr)->find();
        }
        $postData = array('packageName' => $rate_name, 'type' => $rate_type);
        $postData['remarks'] = $remarks;
        $postData['steps'] = $rate_detail;
        $postData['isFund'] = $is_fund;
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $apiRet = array();
        if (!empty($haokuaichong_rate) && !empty($haokuaichong_rate['rate_id'])) {
            $saveData['update_time'] = time();
            M('House_village_haokuaichong_rate')->where(array('id' => $haokuaichong_rate['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->editProjectRate($postData, $haokuaichong_rate['rate_id']);
        } elseif (!empty($haokuaichong_rate) && empty($haokuaichong_rate['rate_id'])) {
            $saveData['update_time'] = time();
            M('House_village_haokuaichong_rate')->where(array('id' => $haokuaichong_rate['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->addProjectRate($postData, $village_haokuaichong['project_id'], $haokuaichong_rate['id']);
        } else {
            $saveData['village_id'] = $village_id;
            $saveData['add_time'] = time();
            $idd = M('House_village_haokuaichong_rate')->data($saveData)->add();
            $apiRet = $pileHaoKuaiChongApi->addProjectRate($postData, $village_haokuaichong['project_id'], $idd);
        }
        echo json_encode($apiRet, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function del_project_rate()
    {
        $id = isset($_POST['idd']) ? intval($_POST['idd']) : 0;
        $village_id = $this->house_session['village_id'];
        $whereArr = array('id' => $id, 'village_id' => $village_id);
        $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereArr)->field('*')->find();
        if (empty($haokuaichong_rate)) {
            $this->error('费率数据不存在！');
        }
        $nowtime = time();
        if (!empty($haokuaichong_rate) && empty($haokuaichong_rate['rate_id'])) {
            M('House_village_haokuaichong_rate')->where($whereArr)->data(array('del_time' => $nowtime))->save();
            $this->success('删除成功！', U('charg_rate_list'));
            exit();
        }
        if (!empty($haokuaichong_rate) && !empty($haokuaichong_rate['rate_id'])) {
            $whereArr = array('village_id' => $village_id, 'project_id' => $haokuaichong_rate['project_id']);
            $village_haokuaichong = M('House_village_haokuaichong')->where($whereArr)->find();
            $user_id = '';
            if ($village_haokuaichong && $village_haokuaichong['user_id']) {
                $user_id = $village_haokuaichong['user_id'];
            }
            import('@.ORG.PileHaoKuaiChong');
            $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
            $apiRet = $pileHaoKuaiChongApi->delProjectRate($haokuaichong_rate['rate_id'], $user_id, $haokuaichong_rate['id']);
            $err_msg = $apiRet && $apiRet['err_msg'] ? $apiRet['err_msg'] : '操作失败了！';
            $this->success($err_msg, U('charg_rate_list'));
        }
    }

    /**
     * 智能充电桩列表
     * @author lijie
     * @date_time 2021/02/05
     */
    public function charg_equipment_list()
    {
        $village_id = $this->house_session['village_id'];

        $whereArr = array('village_id' => $village_id, 'area_id' => array('neq', ''), 'del_time' => array('lt', 10));
        $house_village_haokuaichong_regionDb = M('House_village_haokuaichong_region');
        $haokuaichong_region = $house_village_haokuaichong_regionDb->where($whereArr)->find();
        $is_have_region = 0;
        if ($haokuaichong_region && isset($haokuaichong_region['area_id'])) {
            $is_have_region = 1;
        }
        $find_value = $_GET['find_value'] ? trim($_GET['find_value']) : '';
        $where = ['village_id' => $village_id, 'is_del' => 1, 'type' => 3];
        if ($find_value) {
            if ($_GET['find_type'] == 1) {
                $where['equipment_name'] = ['LIKE', '%' . $find_value . '%'];
            } else if ($_GET['find_type'] == 2) {
                $where['equipment_num'] = $find_value;
            } else if ($_GET['find_type'] == 3) {
                $where['equipment_sn'] = $find_value;
            } else if ($_GET['find_type'] == 4) {
                $haokuaichong_regions = $house_village_haokuaichong_regionDb->where(array('area_name' => array('LIKE', '%' . $find_value . '%'), 'village_id' => $village_id))->field('area_id')->select();
                if ($haokuaichong_regions) {
                    $area_id_arr = array();
                    foreach ($haokuaichong_regions as $rvv) {
                        $area_id_arr[] = $rvv['area_id'];
                    }
                    $where['area_id'] = ['in', $area_id_arr];
                } else {
                    $where['area_id'] = '';
                }
            }
        }
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $res = D('House_village_pile_equipment')->getList($where,10);
        $nowtime=time();
        $tmptime=$nowtime-300;
        if ($res['list']) {
            $base_url = '/packapp/village/';
            $site_url = C('config.site_url');
            foreach ($res['list'] as $k => $v) {
                if($v['dev_update_time']<$tmptime){
                    $pileHaoKuaiChongApi->getEquipmentDetail($v['equipment_num'],$v['id']);
                }
                $url = $site_url . $base_url . 'pages/village/smartCharge/smartCharge?type=3&qcodeNum=' . $v['equipment_num'];
                $img_url = $site_url . U('get_own_qrcode', array('qrCon' => urlencode(htmlspecialchars_decode($url))));
                $res['list'][$k]['qrcode'] = $img_url;
                
                $res['list'][$k]['area_name'] = '';
                $haokuaichong_region = $house_village_haokuaichong_regionDb->where(array('area_id' => $v['area_id'], 'village_id' => $village_id))->field('common_id,area_name')->find();
                if ($haokuaichong_region && $haokuaichong_region['area_name']) {
                    $res['list'][$k]['area_name'] = $haokuaichong_region['area_name'];
                }
                $res['list'][$k]['dev_update_time_str'] = (isset($v['dev_update_time']) && $v['dev_update_time'] > 1) ? date('Y-m-d H:i:s', $v['dev_update_time']) : '--';
            }
        }
        $this->assign('is_have_region', $is_have_region);
        $this->assign('pagebar', $res['pagebar']);
        $this->assign('list', $res['list']);
        $this->display();
    }

    public function add_project_equipment_page()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $village_id = $this->house_session['village_id'];
        $whereArr = array('village_id' => $village_id, 'area_id' => array('neq', ''), 'del_time' => array('lt', 10));
        $haokuaichong_region = M('House_village_haokuaichong_region')->where($whereArr)->field('id,village_id,project_id,area_id,common_id,area_name,area_type')->select();
        $equipment = '';
        if ($id > 0) {
            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $equipment = M('House_village_pile_equipment')->where($whereArr)->field('*')->find();
        }
        $this->assign('haokuaichong_region', $haokuaichong_region);
        $this->assign('equipment', $equipment);
        $this->assign('idd', $id);
        $this->display();
    }

    public function add_project_equipment()
    {
        $id = isset($_POST['idd']) ? intval($_POST['idd']) : 0;
        $equipment_name = isset($_POST['equipment_name']) ? trim($_POST['equipment_name']) : '';
        $area_id = isset($_POST['area_id']) ? trim($_POST['area_id']) : '';
        $equipment_num = isset($_POST['equipment_num']) ? trim($_POST['equipment_num']) : '';
        $equipment_sn = isset($_POST['equipment_sn']) ? trim($_POST['equipment_sn']) : '';
        $village_id = $this->house_session['village_id'];
        $lng_lat = isset($_POST['lng_lat']) ? trim($_POST['lng_lat']) : '';
        $retInfoArr = array();
        $retInfoArr['err_code'] = 0;
        $retInfoArr['err_msg'] = '';
        if (empty($equipment_name)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '设备名称不能为空！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $haokuaichong_equipment = array();
        if ($id > 0) {
            $whereArr = array('id' => $id, 'village_id' => $village_id);
            $haokuaichong_equipment = M('House_village_pile_equipment')->where($whereArr)->find();
        } else {
            $whereArr = array('equipment_num' => $equipment_num, 'is_del' => '1');
            $equipment = M('House_village_pile_equipment')->where($whereArr)->find();
            if ($equipment) {
                $retInfoArr['err_code'] = 1;
                $retInfoArr['err_msg'] = '此设备已经被添加过了！';
                echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
        if (empty($area_id) && empty($haokuaichong_equipment)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请选择一个充电区！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $whereArr = array('village_id' => $village_id, 'area_id' => $area_id);
        $haokuaichong_region = M('House_village_haokuaichong_region')->where($whereArr)->find();
        if (empty($haokuaichong_region) && empty($haokuaichong_equipment)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '充电区不存在！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }

        if (empty($equipment_num) && empty($haokuaichong_equipment)) {
            $retInfoArr['err_code'] = 1;
            $retInfoArr['err_msg'] = '请填写16进制设备号！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $equipment_num = strtoupper($equipment_num);
        if (!empty($haokuaichong_equipment)) {
            $saveData = array('equipment_name' => $equipment_name);
            $equipment_num = $haokuaichong_equipment['equipment_num'];
            $area_id = $haokuaichong_equipment['area_id'];
        } else {
            $saveData = array('village_id' => $village_id, 'equipment_name' => $equipment_name, 'equipment_num' => $equipment_num, 'area_id' => $area_id);
            $saveData['type'] = 3;
            $saveData['work_type'] = 0;
            $saveData['device_type'] = 0;
            $saveData['status'] = 0;
        }
        if($lng_lat && strpos($lng_lat,',')){
            $lng_lat_arr=explode(',',$lng_lat);
            $saveData['lng']=$lng_lat_arr['0'];
            $saveData['lat']=$lng_lat_arr['1'];
        }
        if($equipment_sn){
            $saveData['equipment_sn']=$equipment_sn;
        }
        $postData = array('deviceId' => $equipment_num, 'areaId' => $area_id);
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $apiRet = array();
        if (!empty($haokuaichong_equipment) && !empty($haokuaichong_equipment['equipment_serial'])) {
            M('House_village_pile_equipment')->where(array('id' => $haokuaichong_equipment['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->getEquipmentDetail($equipment_num, $haokuaichong_equipment['id']);
            $retInfoArr['err_code'] = 0;
            $retInfoArr['err_msg'] = '操作成功！';
            echo json_encode($retInfoArr, JSON_UNESCAPED_UNICODE);
            exit();
        } elseif (!empty($haokuaichong_equipment) && empty($haokuaichong_equipment['equipment_serial'])) {
            M('House_village_pile_equipment')->where(array('id' => $haokuaichong_equipment['id']))->data($saveData)->save();
            $apiRet = $pileHaoKuaiChongApi->addProjectEquipment($postData, $equipment_num, $haokuaichong_equipment['id']);
        } else {
            $saveData['add_time'] = time();
            $idd = M('House_village_pile_equipment')->data($saveData)->add();
            $apiRet = $pileHaoKuaiChongApi->addProjectEquipment($postData, $equipment_num, $idd);
        }
        echo json_encode($apiRet, JSON_UNESCAPED_UNICODE);
        exit();

    }

    /**
     * 删除智能充电桩
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_del()
    {
        $id = isset($_POST['idd']) ? intval($_POST['idd']) : 0;
        if (!$id) {
            $this->error('删除失败', U('HkcPile/charg_equipment_list'));
        }
        $village_id = $this->house_session['village_id'];
        $whereArr = array('id' => $id, 'village_id' => $village_id);
        $res = M('House_village_pile_equipment')->where($whereArr)->find();
        if (empty($res)) {
            $this->error('数据不存在，删除失败', U('HkcPile/charg_equipment_list'));
        }
        if (empty($res['equipment_serial'])) {
            $deltime = date('YmdHis');
            M('House_village_pile_equipment')->where(['id' => $id])->save(['is_del' => 2, 'equipment_num' => $res['equipment_num'] . '_del-' . $deltime]);
            $this->success('删除成功', U('HkcPile/charg_equipment_list'));
        } else if (!empty($res['equipment_serial'])) {
            import('@.ORG.PileHaoKuaiChong');
            $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
            $apiRet = $pileHaoKuaiChongApi->delProjectEquipment($res['equipment_num'], $res['id'], $res['equipment_serial']);
            if ($apiRet['err_code'] > 0) {
                $this->error('删除失败！' . $apiRet['err_msg'], U('HkcPile/charg_equipment_list'));
            } else {
                $this->error('删除成功！', U('HkcPile/charg_equipment_list'));
            }
        }
    }

    /**
     * 充电桩详情
     * @author lijie
     * @date_time 2021/04/26
     */
    public function equipment_detail()
    {
        $village_id = $this->house_session['village_id'];
        $equipment_id = $_GET['equipment_id'] ? intval($_GET['equipment_id']) : 0;
        $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id,'village_id'=>$village_id])->find();
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $apiRet = $pileHaoKuaiChongApi->getEquipmentDetail($equipment_info['equipment_num'], $equipment_info['id']);
        $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id])->find();
        $equipment_info['add_time_txt'] = date('Y-m-d H:i:s', $equipment_info['add_time']);

        $port_list = $equipment_info['ports_info'] ? json_decode($equipment_info['ports_info'], true) : array();
        $port_status = $equipment_info['port_status'] ? json_decode($equipment_info['port_status'], true) : array();
        $a_count = 0; //空闲
        $b_count = 0;  //工作
        $c_count = 0;  //故障
        $d_count = 0; //充满占用
        $e_count = 0; //未充占用
        $port_arr = array();
        $house_village_pile_equipmentDb = D('House_village_pile_equipment');
        if ($port_status) {
            foreach ($port_status as $kk => $dks) {
                $pname = $kk + 1;
                if ($pname < 10) {
                    $pname = '0' . $pname;
                }
                $port_info = array('pname' => $pname, 'status' => $dks, 'status_des' => $house_village_pile_equipmentDb->get_type3_port_state($dks));
                if($dks == 0){
                    $a_count++;
                }else if ($dks == 2) {
                    //2 有充电器但未充电（用户未启动充电）
                    $e_count++;
                }else if ($dks == 3) {
                    //3 有充电器但未充电（已充满电）
                    $d_count++;
                }  else if (in_array($dks, [1, 5, 170])) {
                    $b_count++;
                } else {
                    $c_count++;
                }
                $port_arr[] = $port_info;
            }
        }
        $base_url = '/packapp/village/';
        $site_url = C('config.site_url');
        $url = $site_url . $base_url . 'pages/village/smartCharge/smartCharge?type=3&qcodeNum=' . $equipment_info['equipment_num'];
        $img_url = $site_url . U('get_own_qrcode', array('qrCon' => urlencode(htmlspecialchars_decode($url))));
        $equipment_info['qrcode'] = $img_url;
        $regionWhere = array('village_id' => $village_id, 'area_id' => $equipment_info['area_id']);
        $haokuaichong_region = M('House_village_haokuaichong_region')->where($regionWhere)->find();
        $equipment_info['area_name'] = $haokuaichong_region['area_name'];
        $extra_data = $equipment_info['extra_data'] ? json_decode($equipment_info['extra_data'], true) : array();
        $this->assign('a_count', $a_count);
        $this->assign('b_count', $b_count);
        $this->assign('c_count', $c_count);
        $this->assign('d_count', $d_count);
        $this->assign('e_count', $e_count);
        $this->assign('status', $port_list[0]);
        $this->assign('ports', $port_arr);
        $this->assign('info', $equipment_info);
        $device_info = $extra_data;
        $device_info['village_id'] = $equipment_info['village_id'];
        $device_info['deviceFamily'] = $this->device_family['0'];
        $device_info['time_str'] = $device_info['time'] ? $device_info['time'] : '';
        $device_info['signals'] = $device_info['signals'] ? $device_info['signals'] : '';
        $device_info['sidetemp'] = $device_info['sidetemp'] ? $device_info['sidetemp'] : '';
        $device_info['version'] = $device_info['version'] ? $device_info['version'] : '';
        $device_info['simCode'] = $device_info['simCode'] ? $device_info['simCode'] : '';
        $device_info['IMEI'] = $device_info['IMEI'] ? $device_info['IMEI'] : '';
        $this->assign('device_info', $device_info);
        $priceStandard = array();
        $regionWhere = array('village_id' => $village_id, 'rate_id' => $haokuaichong_region['common_id']);
        $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($regionWhere)->find();
        $priceStandard['name'] = $haokuaichong_rate['rate_name'];
        $rate_type_arr = array('B1' => '计时', 'B2' => '计量');
        $priceStandard['rate_type'] = isset($rate_type_arr[$haokuaichong_rate['rate_type']]) ? $rate_type_arr[$haokuaichong_rate['rate_type']] : '未知';
        $rate_detail = $haokuaichong_rate['rate_detail'] ? json_decode($haokuaichong_rate['rate_detail'], 1) : '';
        $rate_detail_str = '';
        if ($rate_detail) {
            if ($haokuaichong_rate['rate_type'] == 'B2') {
                $rate_detail_str = '每度电' . $rate_detail['0']['standard'] . '元';
            } elseif ($haokuaichong_rate['rate_type'] == 'B1') {
                foreach ($rate_detail as $dvv) {
                    $rate_detail_str .= '功率在' . $dvv['minPower'] . '瓦到' . $dvv['maxPower'] . '瓦间每小时' . $dvv['standard'] . '元<br>';
                }
            }
        }
        $priceStandard['rate_detail_str'] = $rate_detail_str;
        $this->assign('priceStandard', $priceStandard);
        $this->display();
    }

    public function get_own_qrcode($qrCon, $size = 8, $is_down = true, $outfile = false)
    {
        header('Content-Type: image/png');
        ob_clean();
//        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
//        $size = $_GET['size'] ? $_GET['size']: 10;
//        $is_down = $_GET['down'] ? true: false;
        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)), $outfile, 0, $size, 1, $is_down);
    }

    /**
     * 获取小区智能充电桩月卡列表
     * @author lijie
     * @date_time 2021/02/05
     */
    public function month_card_list()
    {
        $village_id = $this->house_session['village_id'];
        $count = M('House_village_pile_month_card')->where(['village_id' => $village_id, 'is_del' => 1, 'type' => 3])->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $list = M('House_village_pile_month_card')->where(['village_id' => $village_id, 'is_del' => 1, 'type' => 3])->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('pagebar', $p->show());
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加、编辑充电桩月卡
     * @author lijie
     * @date_time 2021/02/05
     */
    public function month_card_add()
    {
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $data['village_id'] = $village_id;
            $data['card_name'] = $_POST['card_name'];
            $data['card_des'] = $_POST['card_des'];
            $data['price'] = $_POST['price'];
            $data['use_month'] = $_POST['use_month'];
            $data['all_num'] = $_POST['all_num'];
            $data['max_num'] = $_POST['max_num'];
            $data['per_price'] = $_POST['per_price'];
            $data['status'] = $_POST['status'];
            $data['add_time'] = time();
            $data['type'] = 3;
            if ($_POST['id'] && $_POST['id'] > 0) {
                $whereArr = ['id' => $_POST['id'], 'village_id' => $village_id];
                $res = M('House_village_pile_month_card')->where($whereArr)->save($data);
            } else {
                $res = M('House_village_pile_month_card')->add($data);
            }
            if ($res) {
                $this->success('保存成功', U('HkcPile/month_card_list'));
            }
        } else {
            $id = $_GET['id'] ? intval($_GET['id']) : 0;
            if ($id) {
                $whereArr = ['id' => $id, 'village_id' => $village_id];
                $info = M('House_village_pile_month_card')->where($whereArr)->find();
                $this->assign('info', $info);
            }
            $this->assign('id', $id);
            $this->display();
        }
    }

    /**
     * 删除充电桩月卡
     * @author lijie
     * @date_time 2021/02/05
     */
    public function month_card_del()
    {
        $id = $_GET['id'] ? intval($_GET['id']) : 0;
        $village_id = $this->house_session['village_id'];
        if (!$id) {
            $this->error('删除失败', U('HkcPile/month_card_list'));
        }
        $whereArr = ['id' => $id, 'village_id' => $village_id];
        if (M('House_village_pile_month_card')->where($whereArr)->save(['is_del' => 2])) {
            $this->success('删除成功', U('HkcPile/month_card_list'));
        }
    }


    /**
     * 充电桩插座二维码
     */
    public function getNumCode()
    {
        $equipment_id = $_POST['equipment_id'];
        $socket = $_POST['socket'];
        $village_id = $this->house_session['village_id'];
        $site_url = $this->config['site_url'];
        $base_url = D('House_village')->base_url;
        $url = $site_url . $base_url . 'pages/village/smartCharge/chargeNum?id=' . $equipment_id . '&socket=' . $socket.'&village_id='.$village_id;
        $img_url = $site_url . U('get_own_qrcode', array('qrCon' => urlencode(htmlspecialchars_decode($url)), 'down' => true));
        echo json_encode(['url' => $img_url, 'status' => 1]);
    }

    /**
     * 批量下载充电桩插座二维码
     */
    public function downloadCode()
    {

        $village_id = $this->house_session['village_id'];
        $equipment_id = $_POST['equipment_id'] ? intval($_POST['equipment_id']) : 0;
        $equipment_num = $_POST['equipment_num'] ? intval($_POST['equipment_num']) : '';
        $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id,'village_id'=>$village_id])->find();
        $port_status = $equipment_info['port_status'] ? json_decode($equipment_info['port_status'], true) : array();
        
        $site_url = $this->config['site_url'];
        $base_url = D('House_village')->base_url;
        foreach ($port_status as $k => $v) {
            $socket = $k + 1;
            $url = $site_url . $base_url . 'pages/village/smartCharge/chargeNum?id=' . $equipment_id . '&socket=' . $socket.'&village_id='.$village_id;;
            $dir_path = './upload/temp/pileHkcQrcode/' . $_POST['equipment_num'] . '/' . $_POST['equipment_num'];
            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0777, true);
            }
            $filePath = $dir_path . '/插座' . $socket . '.png';
            $this->get_own_qrcode($url, 7, true, $filePath);
        }
        if ($port_status) {
            $this->get_zip_originalsize('./upload/temp/pileHkcQrcode/' . $_POST['equipment_num'] . '/', './upload/temp/pileHkcQrcode/' . $_POST['equipment_num'] . '/');
        }
        echo json_encode(['msg' => '下载成功', 'status' => 1, 'url' => $site_url . '/upload/temp/pileHkcQrcode/' . $_POST['equipment_num'] . '/' . $_POST['equipment_num'] . '.zip']);
    }

    /**
     * 将文件夹压缩
     * @param $path
     * @param $zipPath
     * @return bool
     */
    public function get_zip_originalsize($path, $zipPath)
    {

        !is_dir($zipPath) ? mkdir($zipPath, 0755, true) : '';


        $zip = new ZipArchive();

        $dir_list = scandir($path);

        if (empty($dir_list)) {
            return false;
        }

        foreach ($dir_list as $dir_name) {
            if ($dir_name == '.' || $dir_name == '..') {
                continue;
            }
            $dir_path = $path . $dir_name;    //待压缩目录
            if (!is_dir($dir_path)) {
                continue;
            }
            $zipFile = $zipPath . $dir_name . '.zip';   //压缩包名称
            $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $file_list = scandir($dir_path);    //文件名
            foreach ($file_list as $file_name) {
                if ($file_name == '.' || $file_name == '..') {
                    continue;
                }

                $zip->addFile($dir_path . '/' . $file_name, $file_name);    //第二个参数使压缩文件名为待压缩文件原名（否则压缩包里会有多级目录）

            }
            $zip->close();

        }
    }

    /**
     * 订单列表
     * @author lijie
     * @date_time 2021/04/28
     */
    public function order_list()
    {
        $village_id = $this->house_session['village_id'];
        $pay_type = trim($_GET['pay_type']);
        $order_type = trim($_GET['order_type']);
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        $where['o.village_id'] = $village_id;
        $where['o.pay_time'] = array('gt', 100);
        $where['o.type'] = 3;
        if ($pay_type) {
            $where['o.pay_type'] = $pay_type;
        }
        if ($order_type) {
            $where['o.order_type'] = $order_type;
        }
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        if ($find_type == 1 && !empty($find_value)) {
            $where['u.nickname'] = $find_value;
        }
        if ($find_type == 2 && !empty($find_value)) {
            $where['u.phone'] = $find_value;
        }
        if ($find_type == 3 && !empty($find_value)) {
            $where['o.order_no'] = $find_value;
        }
        if ($begin_time && empty($end_time)) {
            $where['o.pay_time'] = array('egt', strtotime($begin_time));
        } elseif ($end_time && empty($begin_time)) {
            $where['o.pay_time'] = array('elt', strtotime($end_time . ' 23:59:59'));
        } elseif ($end_time && $begin_time) {
            $where['o.pay_time'] = array('between', [strtotime($begin_time), strtotime($end_time . ' 23:59:59')]);
        }
        $count = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->field('o.*,u.nickname,u.phone')
            ->order('o.id DESC')
            ->limit($p->firstRow, $p->listRows)
            ->select();
        $this->assign('pagebar', $p->show());
        $nowtime=time();
        $need_start_time=$nowtime-300; //5分鐘
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
        $tmptime=time()-100;
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach ($order_list as $k => &$v) {
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id' => $v['equipment_id']])->getField('equipment_name');
            $order_list[$k]['pay_time_txt'] = date('Y-m-d H:i:s', $v['pay_time']);
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time'] / 60);
            if ($v['order_type'] == 1) {
                $v['order_type_str'] = '消费订单';
            } else {
                $v['order_type_str'] = '月卡购买';
            }
            switch ($v['pay_type']) {
                case 1:
                    $v['pay_type'] = '余额支付';
                    break;
                case 2:
                    $v['pay_type'] = '微信支付';
                    break;
                case 3:
                    $v['pay_type'] = '月卡支付';
                    break;
                case 4:
                    $v['pay_type'] = '支付宝支付';
                    break;
                case 10:
                    $v['pay_type'] = '本地余额卡支付';
                    break;
                default:
                    $v['pay_type'] = '余额支付';
            }
            if (isset($v['phone']) && $order_list[$k]['phone']) {
                $order_list[$k]['phone'] = $d_house_village->phone_desensitization_v($order_list[$k]['phone'],$village_id,$village_info);
            }
            if($v['status']<1 && $v['pay_time']>100 && $v['last_query_time']<$tmptime && $v['pay_time']<$need_start_time && $v['start_time']<1){
                //支付后 5分钟没收到开电通知的 查一下
                $pileHaoKuaiChongApi->queryChargeOrderInfo($v['id']);
            }
        }
        $this->assign('order_list', $order_list);
        $this->display();
    }

    public function order_export()
    {
        $village_id = $this->house_session['village_id'];
        $where = array();
        $where['o.village_id'] = $village_id;
        $where['o.type'] = 3;
        $where['o.pay_time'] = array('gt', 100);
        $find_type = trim($_GET['find_type']);
        $find_value = trim($_GET['find_value']);
        $order_type = trim($_GET['order_type']);
        $pay_type = trim($_GET['pay_type']);
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        if ($find_value) {
            if ($find_type == 1) {
                $where['u.nickname'] = $find_value;
            } else if ($find_type == 2) {
                $where['u.phone'] = $find_value;
            }
        }
        if ($pay_type) {
            $where['o.pay_type'] = $pay_type;
        }
        if ($order_type) {
            $where['o.order_type'] = $order_type;
        }
        if ($begin_time && empty($end_time)) {
            $where['o.pay_time'] = array('egt', strtotime($begin_time . ' 00:00:00'));
        } elseif ($end_time && empty($begin_time)) {
            $where['o.pay_time'] = array('elt', strtotime($end_time . ' 23:59:59'));
        } elseif ($end_time && $begin_time) {
            $where['o.pay_time'] = array('between', [strtotime($begin_time . ' 00:00:00'), strtotime($end_time . ' 23:59:59')]);
        }
        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->field('o.*,u.nickname,u.phone')
            ->order('o.id DESC')
            ->select();
        $order_count = count($order_list);
        if ($order_count <= 0) {
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = '好快充充电桩订单导出';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $per = 3000;
        $length = ceil($order_count / $per);

        $pCoordinate_arr = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'I', 'J', 'K', 'L', 'M', 'N',
            'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z',
        ];
        $pValue_arr = [
            '消费订单编号',
            '设备名称',
            '订单类型',
            '消费时长',
            '状态（结束原因）',
            '消费金额',
            '退款金额',
            '消费电量',
            '姓名',
            '联系方式',
            '支付方式',
            '支付时间',
            '支付订单编号',
            '第三方支付流水号',
        ];
        $house_village_pile_equipmentDb = M('House_village_pile_equipment');
        $key_length = $order_count - 1;
        $oder_key = 0;
        for ($i = 0; $i < $length; $i++) {
            if (!isset($order_list[$oder_key])) {
                break;
            }
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('每页' . $per . '条数据，共' . $order_count . '条');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1 => $v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1] . '1', $v1);
            }
            if (!empty($order_list)) {
                $index = 2;

                $cell_list = range($pCoordinate_arr[0], $pCoordinate_arr[17]);
                foreach ($cell_list as $cell) {
                    if (in_array($cell, ['C', 'D', 'F', 'G', 'H'])) {
                        $objActSheet->getColumnDimension($cell)->setWidth(15);
                    } else if ($cell == 'E') {
                        $objActSheet->getColumnDimension($cell)->setWidth(40);
                    } else if ($cell == 'N') {
                        $objActSheet->getColumnDimension($cell)->setWidth(40);
                    } else {
                        $objActSheet->getColumnDimension($cell)->setWidth(25);
                    }
                }
                for ($ii = 0; $ii < $per; $ii++) {
                    if (!isset($order_list[$oder_key])) {
                        break;
                    }
                    $value = $order_list[$oder_key];
                    $equipment_name = $house_village_pile_equipmentDb->where(['id' => $value['equipment_id']])->getField('equipment_name');
                    $pay_time = date('Y-m-d H:i:s', $value['pay_time']);
                    if ($value['order_type'] == 1)
                        $order_type = '消费订单';
                    else
                        $order_type = '月卡购买';
                    switch ($value['pay_type']) {
                        case 1:
                            $pay_type = '余额支付';
                            break;
                        case 2:
                            $pay_type = '微信支付';
                            break;
                        case 3:
                            $pay_type = '月卡支付';
                            break;
                        case 4:
                            $pay_type = '支付宝支付';
                            break;
                        case 10:
                            $pay_type = '本地余额卡支付';
                            break;
                        default:
                            $pay_type = '余额支付';
                    }
                    $statusStr = $value['status'] == 1 ? '已结束' . ($value['end_result'] ? '（' . $value['end_result'] . '）' : '') : '未结束';
                    $continued_time = getFormatNumber($value['continued_time'] / 60);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['order_no']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $equipment_name);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $order_type);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $continued_time);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $statusStr);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['use_money']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['refund_money']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $value['use_ele']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, $value['nickname']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[9] . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[10] . $index, $pay_type);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[11] . $index, $pay_time);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[12] . $index, $value['pay_orderid']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[13] . $index, $value['third_paid_orderid']);
                    $index++;
                    $oder_key++;
                }
            }
        }
        $objExcel->setActiveSheetIndex(0);
        sleep(1);
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

    /**
     * 订单详情
     */
    public function order_info()
    {
        $village_id = $this->house_session['village_id'];
        $order_id = $_GET['order_id'] ? intval($_GET['order_id']) : 0;
        $order_info = M('House_village_pile_pay_order')->where(['id' => $order_id,'village_id'=>$village_id])->find();
        if($order_info['status']==0){
            //查询订单状况
            import('@.ORG.PileHaoKuaiChong');
            $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
            $apiRet=$pileHaoKuaiChongApi->queryChargeOrderInfo($order_info['id']);
            if($apiRet['err_code']==0){
                $order_info = M('House_village_pile_pay_order')->where(['id' => $order_id,'village_id'=>$village_id])->find();
            }
        }
        $refund_order_info = M('House_village_pile_refund_order')->where(['order_id' => $order_id])->find();
        $order_info['equipment_name'] = M('House_village_pile_equipment')->where(['id' => $order_info['equipment_id']])->getField('equipment_name');
        $order_info['continued_time'] = getFormatNumber($order_info['continued_time'] / 60);
        switch ($order_info['pay_type']) {
            case 1:
                $order_info['pay_type'] = '余额支付';
                break;
            case 2:
                $order_info['pay_type'] = '微信支付';
                break;
            case 3:
                $order_info['pay_type'] = '月卡支付';
                break;
            case 4:
                $order_info['pay_type'] = '支付宝支付';
                break;
            case 10:
                $order_info['pay_type'] = '本地余额卡支付';
                break;
            default:
                $order_info['pay_type'] = '余额支付';
        }
        if ($order_info['order_type'] == 1) {
            $order_info['order_name'] = '消费订单';
        } else {
            $order_info['order_name'] = '月卡购买';
        }
        $order_info['start_time'] = $order_info['start_time'] > 0 ? $order_info['start_time'] : $order_info['pay_time'];
        if ($order_info['start_time'] > 0) {
            $order_info['start_time'] = date('Y-m-d H:i:s', $order_info['start_time']);
        }
        $order_info['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
        if ($order_info['status'] == 0) {
            $order_info['end_time'] = '----';
            $order_info['status'] = '未结束';
        } else {
            $order_info['end_time'] = date('Y-m-d H:i:s', $order_info['end_time']);
            $order_info['status'] = '已结束';
        }
        
        // 1：已取消 2：发起退款（审核中）3：退款成功 4：退款失败
        if ($refund_order_info && $refund_order_info['refund_money']) {
            $arr = [
                1 => '已取消',
                2 => '发起退款（审核中）',
                3 => '退款成功',
                4 => '退款失败',
            ];
            $order_info['refund_money'] = $refund_order_info['refund_money'];
            $actualMoney = $order_info['use_money'] - $refund_order_info['refund_money'];
            $order_info['refund_money_txt'] = isset($arr[$refund_order_info['status']]) ? $arr[$refund_order_info['status']] : '';
        } else {
            $actualMoney = $order_info['use_money'];
        }
        $order_info['actualMoney'] = $actualMoney;
        $this->assign('order_info', $order_info);
        $this->display();
    }
    /**
     * 退款订单列表
     * @author lijie
     * @date_time 2021/07/20
     */
    public function refund_list()
    {
        $village_id = $this->house_session['village_id'];
        $where = array();
        $where['o.village_id'] = $village_id;
        $where['o.type'] = 3;
        $where['o.is_refund'] = array('gt', 1);
        $find_type = trim($_GET['find_type']);
        $find_value = trim($_GET['find_value']);
        $is_refund = trim($_GET['is_refund']);
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        if ($find_type == 1 && !empty($find_value)) {
            $where['u.nickname'] = $find_value;
        }
        if ($find_type == 2 && !empty($find_value)) {
            $where['u.phone'] = $find_value;
        }
        if ($is_refund) {
            $where['o.is_refund'] = $is_refund;
        }
        if ($begin_time && empty($end_time)) {
            $where['r.add_time'] = array('egt', strtotime($begin_time));
        } elseif ($end_time && empty($begin_time)) {
            $where['r.add_time'] = array('elt', strtotime($end_time . ' 23:59:59'));
        } elseif ($end_time && $begin_time) {
            $where['r.add_time'] = array('between', [strtotime($begin_time), strtotime($end_time . ' 23:59:59')]);
        }
        $count = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->join('left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            ->where($where)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->join('left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            ->where($where)
            ->field('o.*,u.nickname,u.phone,r.refund_money,r.refund_time,r.refund_reason')
            ->order('o.id DESC')
            ->limit($p->firstRow, $p->listRows)
            ->select();
        $this->assign('pagebar', $p->show());
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach ($order_list as $k => &$v) {
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id' => $v['equipment_id']])->getField('equipment_name');
            $order_list[$k]['refund_time_txt'] = $v['refund_time'] > 100 ? date('Y-m-d H:i:s', $v['refund_time']) : date('Y-m-d H:i:s', $v['add_time']);
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time'] / 60);
            if ($v['is_refund'] == 1)
                $order_list[$k]['refund_status'] = '退款申请通过';
            if ($v['is_refund'] == 2)
                $order_list[$k]['refund_status'] = '<span style="color: red">待审核</span>';
            if ($v['is_refund'] == 3)
                $order_list[$k]['refund_status'] = '<span style="color: blue">已退款</span>';
            if ($v['is_refund'] == 4)
                $order_list[$k]['refund_status'] = '<span style="color: green">未通过</span>';
            if (isset($v['phone']) && $order_list[$k]['phone']) {
                $order_list[$k]['phone'] = $d_house_village->phone_desensitization_v($order_list[$k]['phone'],$village_id,$village_info);
            }
        }
        $this->assign('order_list', $order_list);
        $this->display();
    }

    /**
     * 退款订单详情
     * @author lijie
     * @date_time 2021/07/20
     */
    public function refund_info()
    {
        $village_id = $this->house_session['village_id'];
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order_info = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            ->field('o.*,r.refund_money,r.img,r.refund_reason,r.refund_time,r.refuse_reason,r.refundType,o.refund_money as r_money')
            ->where(['o.id' => $order_id,'o.village_id'=>$village_id])
            ->find();
        $order_info['equipment_name'] = M('House_village_pile_equipment')->where(['id' => $order_info['equipment_id']])->getField('equipment_name');
        $order_info['continued_time'] = getFormatNumber($order_info['continued_time'] / 60);
        $order_info['actual_money'] = $order_info['use_money'] - $order_info['r_money'];
        $order_info['actual_money'] = $order_info['actual_money'] > 0 ? $order_info['actual_money'] : 0;
        if (!empty($order_info['img'])) {
            $order_info['img'] = explode(',', $order_info['img']);
            $order_info['img_count'] = count($order_info['img']);
        } else {
            $order_info['img_count'] = 0;
        }
        switch ($order_info['pay_type']) {
            case 1:
                $order_info['pay_type'] = '余额支付';
                break;
            case 2:
                $order_info['pay_type'] = '微信支付';
                break;
            case 3:
                $order_info['pay_type'] = '月卡支付';
                break;
            case 4:
                $order_info['pay_type'] = '支付宝支付';
                break;
            case 10:
                $order_info['pay_type'] = '本地余额卡支付';
                break;
            default:
                $order_info['pay_type'] = '余额支付';
        }
        if ($order_info['order_type'] == 1) {
            $order_info['order_name'] = '消费订单';
        } else {
            $order_info['order_name'] = '月卡购买';
        }
        $order_info['start_time'] = $order_info['start_time'] > 0 ? $order_info['start_time'] : $order_info['pay_time'];
        if ($order_info['start_time'] > 0) {
            $order_info['start_time'] = date('Y-m-d H:i:s', $order_info['start_time']);
        }
        $order_info['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
        $order_info['refund_time'] = $order_info['refund_time'] > 100 ? date('Y-m-d H:i:s', $order_info['refund_time']) : '';
        if ($order_info['status'] == 0) {
            $order_info['end_time'] = '----';
            $order_info['status'] = '未结束';
        } else {
            $order_info['end_time'] = date('Y-m-d H:i:s', $order_info['end_time']);
            $order_info['status'] = '已结束';
        }

        if ($order_info['order_id']) {
            $wherePayOrderInfo = [
                'business' => 'pile',
                'business_order_id' => $order_info['order_id'],
            ];
            $pay_order_info = M('Pay_order_info')->where($wherePayOrderInfo)->find();
            if ($pay_order_info && $pay_order_info['paid'] == 1 && !$pay_order_info['paid_record_time'] && $pay_order_info['orderid']) {
                invoke_v20_service('\app\pay\model\service\PayService/getQueryDetail', ['order_no' => $pay_order_info['orderid']]);
                $pay_order_info = M('Pay_order_info')->where($wherePayOrderInfo)->find();
            }
            if ($pay_order_info['paid_discount_amount'] > 0) {
                $order_info['paid_discount_amount'] = $pay_order_info['paid_discount_amount'];
                $order_info['buyer_pay_amount'] = $pay_order_info['buyer_pay_amount'];
            }
        }

        $this->assign('order_info', $order_info);
        $this->display();
    }

    public function refund()
    {
        $village_id=$this->house_session['village_id'];
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0; //0 ,3 通过退款款 -1变更退款金额  4拒绝退款 1取消退款
        $refund_reason = isset($_POST['refund_reason']) ? trim($_POST['refund_reason']) : '';  //拒绝原因
        $refund_from = isset($_POST['refund_from']) ? trim($_POST['refund_from']) : '';  //来自哪个页面的退款
        $order_info = M('House_village_pile_pay_order')->where(['id' => $order_id,'village_id'=>$village_id])->find();
        if (empty($order_info) || $order_info['type'] != 3) {
            echo json_encode(['status' => 1, 'msg' => '操作失败']);
            exit;

        }
        if ($status == 4) {
            $res = M('House_village_pile_pay_order')->where(['id' => $order_id])->save(['is_refund' => $status]);
            if ($res) {
                M('House_village_pile_refund_order')->where(['order_id' => $order_id])->save(['status' => $status, 'refund_time' => time(), 'refuse_reason' => $refund_reason]);
                echo json_encode(['status' => 1, 'msg' => '退款审核未通过，操作成功！']);
                exit;
            }
            echo json_encode(['status' => 1, 'msg' => '操作失败']);
            exit;

        }
        if ($status==3 || $status==0) {
            // 0：未申请退款 1：已取消  2：发起退款（审核中） 3：退款成功 4：退款失败
            $is_refund = isset($order_info['is_refund']) ? intval($order_info['is_refund']) : 0;
            if ($is_refund == 3) {
                echo json_encode(['status' => 1, 'msg' => '当前订单已经退款成功，如需再退款请线下操作']);
                exit();
            }
            import('@.ORG.PileHaoKuaiChong');
            $pileHaoKuaiChongApi = new PileHaoKuaiChong($village_id);
            if ($order_info['status'] == 0) {
                //查询订单状况
                $apiRet = $pileHaoKuaiChongApi->queryChargeOrderInfo($order_info['id']);
                if ($apiRet['err_code'] == 0) {
                    $order_info = M('House_village_pile_pay_order')->where(['id' => $order_id, 'village_id' => $village_id])->find();
                }
                if ($order_info['is_refund'] == 3) {
                    echo json_encode(['status' => 0, 'msg' => '退款成功，订单已更新！']);
                    exit();
                }
            }
            //全部退
            $refund_money=$order_info['use_money']-$order_info['refund_money'];
            $refund_money=round($refund_money,2);
            $data=array('comefrom'=>'opt_refund','money'=>0,'refund_money'=>$refund_money);
            $data['stopReason']=0;
            $data['powerConsumption']=0;
            $refundRet=$pileHaoKuaiChongApi->outSettlementRefund($order_info['id'],$data);
            if($refundRet && isset($refundRet['error'])){
                echo json_encode(['status' => $refundRet['error'], 'msg' => $refundRet['msg']]);
                exit();
            }else{
                echo json_encode(['status' => 1, 'msg' => '退款操作失败了!']);
                exit();
            }
        }
    }
}