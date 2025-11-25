<?php

use app\community\model\db\ParkPlateresultLog;

/**
 * Created 停车费管理.
 * User: guoxiansen
 * Date: 2020/1/7
 * Time: 10:45
 */
class ParkAction extends BaseAction
{
    protected $village_id;
    public $http;

    //D3定制停车参数
    public $AppID = '平台测试';
    public $AppSecret = '12345678';
    public $url = 'http://admin.scxhwtkj.com/parking/api';
    public $url_v2 = 'http://admin.scxhwtkj.com/parking/api/v2';

    public $park_type = array('1' => '军车', '2' => '警车', '3' => '消防车');

    public function _initialize()
    {
        parent::_initialize();
//        if (empty($this->house_session['is_open_estate'])) {
//            redirect($this->config['site_url'] . '/shequ.php?g=House&c=Index&a=index');
//            exit;
//        }
        $this->village_id = $this->house_session['village_id'];
        $this->village = D('House_village')->where(array('village_id' => $this->village_id))->find();
        $this->role_id = $this->house_session['role_id']; //角色id
    }

    public function getParkList()
    {
        $data = [
            'method' => 'ParkingList',
            'page' => 1,
            'size' => 20
        ];
        $parkList = $this->getParkingApi($data, $this->url);

        $this->ajaxReturn(array('status' => 1, 'info' => '上传成功', 'data' => $parkList));
    }

    public function park_config()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        if (empty($village_park_config)) {
            $park_config['village_id'] = $_SESSION['house']['village_id'];
            $park_config['park_versions'] = 1;
            $park_config['park_show'] = 1;
            $park_config['is_temporary_park_type'] = 0;
            $park_config['month_park_charge_type'] = 0;
            $park_config['is_park_month_type'] = 0;
            $park_config['is_user_receive_coupon'] = 0;
            M('House_village_park_config')->data($park_config)->add();
        }
        if (2 == $village_park_config['park_versions'] && $village_park_config['park_sys_type'] && in_array($village_park_config['park_sys_type'], ['D1'])) {
            $village_park_config['park_sys_type_box'] = 1;
        } else if (2 == $village_park_config['park_versions'] && $village_park_config['park_sys_type'] && in_array($village_park_config['park_sys_type'], ['D3'])) {
            $village_park_config['park_sys_type_box'] = 3;
        }else {
            $village_park_config['park_sys_type_box'] = 0;
        }
        $parkList = [];
        if ($village_park_config['park_sys_type'] == 'D3') {
            $data = [
                'method' => 'ParkingList',
                'page' => 1,
                'size' => 20
            ];
            $parkList = $this->getParkingApi($data, $this->url);
            //print_r($parkList);exit;
        }
        $this->assign('parkList', $parkList);
        $this->assign('village_park_config', $village_park_config);

        $park_sys_type = D('ParkService')->getParkSysType();
        $this->assign('park_sys_type', $park_sys_type);

        //二维码
        $base_url = D('House_village')->base_url;
        $qrcode_url = $this->config['site_url'] . $base_url . 'pages/village/smartPark/parkingFeetwo';
        $this->assign('qrcode_url', $qrcode_url);

        //停车主机最后上报时间
        $park_system = M('Park_system')->where(['park_id' => $_SESSION['house']['village_id']])->find();
        $this->assign('park_system', $park_system);

        $this->display();
    }

    public function park_config_edit()
    {
        $village_id = $this->village_id;
        $condition_village['village_id'] = $village_id;
        if (IS_POST) {
            $data['park_appearance_type'] = $_POST['park_appearance_type'] ? $_POST['park_appearance_type'] : '';
            $data['park_appearance_time'] = $_POST['park_appearance_time'] ? $_POST['park_appearance_time'] : '';
            $data['is_park_month_type'] = $_POST['is_park_month_type'];
            $data['month_park_charge_type'] = $_POST['month_park_charge_type'] ? $_POST['month_park_charge_type'] : '';
            $data['is_temporary_park_type'] = $_POST['is_temporary_park_type'];
            $data['temporary_park_charge_type'] = $_POST['temporary_park_charge_type'] ? $_POST['temporary_park_charge_type'] : '';
            $data['is_friend_type'] = $_POST['is_friend_type'];
            $data['park_versions'] = $_POST['park_versions'];
            $data['park_show'] = $_POST['park_show'];
            $data['visitor_money'] = $_POST['visitor_money'];

            $park_sys_type = $_POST['park_sys_type'] ? $_POST['park_sys_type'] : 'A1';
            $data['park_sys_type'] = $park_sys_type;
            if ($data['park_sys_type'] == "D3") {
                /*$data['comid'] = $_POST['park_id'] ? $_POST['park_id'] : '';
                if (empty($data['comid'])) {
                    $this->error('请选择需要绑定的停车场！');
                }*/
                if (!empty($_POST['register_day'])){
                    $data['register_day'] = (int)$_POST['register_day'];
                    if ($data['register_day']<0){
                        $this->error('临时车免登记时长请输入正整数！');
                    }
                }
            }elseif ($data['park_sys_type'] == 'D5'){
                $data['d5_url'] = trim($_POST['d5_url']);
                $data['d5_name'] = trim($_POST['d5_name']);
                $data['d5_pass'] = trim($_POST['d5_pass']);
                if(empty($data['d5_url'])){
                    $this->error('请输入设备请求url');
                }
                if(empty($data['d5_name'])){
                    $this->error('请输入设备账号名');
                }
                if(empty($data['d5_pass'])){
                    $this->error('请输入设备账号密码');
                }
            }else {
                $data['comid'] = $_POST['comid'] ? $_POST['comid'] : '';
            }
            $data['union_id'] = $_POST['union_id'] ? $_POST['union_id'] : '';
            $data['ckey'] = $_POST['ckey'] ? $_POST['ckey'] : '';
            $result = M('House_village_park_config')->where($condition_village)->data($data)->save();
            if ($result !== false) {
                if($data['park_sys_type'] == 'D6'){
                    //todo D6智慧停车 生成下发数据
                    invoke_v20_service('\app\community\model\service\Park\D6Service/d6synVehicle',[
                        'village_id'=>$this->village_id
                    ]);
                }
                S('house_park_' . $village_id, null);
                $this->success('修改成功！', U('park_config'));
            } else {
                $this->error('修改失败！请重试。');
            }
        }
    }

    //缴费优惠列表
    public function preferential_list()
    {
        //缴费优惠列表-查看 权限

        if (empty($_GET['park_month_id'])) {
            $this->error('传递参数有误！');
        }

        $database_house_village_park = D('House_village_park');
        $where['village_id'] = $_SESSION['house']['village_id'];
        $where['park_month_id'] = $_GET['park_month_id'];
        $list = $database_house_village_park->house_village_park_page_list($where, true, 'id desc', 20);

        if (!$list) {
            $this->error('数据处理有误！');
        } else {
            $this->assign('list', $list['list']);
        }

        $this->display();
    }

    public function preferential_add()
    {
        //缴费优惠列表-添加 权限


        if (IS_POST) {
            $database_house_village_park = D('House_village_park');

            $_POST['village_id'] = $_SESSION['house']['village_id'];
            $result = $database_house_village_park->house_village_park_add($_POST);

            if (!$result) {
                $this->error('数据处理有误！');
            } else {
                if ($result['status']) {
                    $this->success($result['msg'], U('preferential_list', array('park_month_id' => $_POST['park_month_id'])));//U('cate_index')
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {
            $this->display();
        }
    }

    public function preferential_del()
    {
        //缴费优惠列表-删除 权限


        $id = $_GET['id'] + 0;
        $park_month_id = $_GET['park_month_id'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_park = D('House_village_park');
        $where['id'] = $id;
        $result = $database_house_village_park->house_village_park_del($where);

        if (!$result) {
            $this->error('数据处理有误！');
        } else {
            if ($result['status']) {
                $house_village_park = D('House_village_park')->where(array('id' => $id))->find();
                $park_system = M('park_system')->where(array('park_id' => $_SESSION['house']['village_id']))->find();
                $house_village_park_month = M('house_village_park_month')->where(array('id' => $park_month_id, 'village_id' => $_SESSION['house']['village_id']))->find();
                if ($park_system && $house_village_park_month && $house_village_park) {

                    $record['service_name'] = 'month_card_sync';
                    $record['village_id'] = $_SESSION['house']['village_id'];
                    $record['token'] = $park_system['token'];
                    $record['park_id'] = $park_system['park_id'];
                    $record['status'] = 2;
                    $record['third_id'] = $id;
                    //序列化存储
                    $desc_arr['operate_type'] = 3;
                    $desc_arr['price'] = $house_village_park['park_month_price'];
                    $desc_arr['park_id'] = $park_system['park_id'];
                    $desc_arr['create_time'] = time();
                    $desc_arr['update_time'] = time();
                    $desc_arr['name'] = $house_village_park_month['month_name'];
                    $desc_arr['describe'] = '';//描述
                    $desc_arr['package_id'] = $id;
                    $desc_arr['period'] = '月';
                    $desc_arr['car_type'] = '';//车辆类型car_type_tb 中的car_type_id  否
                    $arr['data'] = $desc_arr;
                    $arr['service_name'] = 'month_card_sync';
                    $record['unserialize_desc'] = serialize($arr);

                    //M('park_total_record')->data($record)->add();
                }
                $this->success($result['msg']);
            } else {
                $this->error($result['msg']);
            }
        }
    }

    public function preferential_edit()
    {
        //缴费优惠列表-查看 权限

        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_park = D('House_village_park');
        $where['id'] = $id;

        if (IS_POST) {


            $result = $database_house_village_park->house_village_park_edit($where, $_POST);

            if (!$result) {
                $this->error('数据处理有误！');
            } else {
                if ($result['status']) {
                    $this->success($result['msg'], U('preferential_list', array('park_month_id' => $_GET['park_month_id'])));
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {
            $detail = $database_house_village_park->house_village_park_detail($where, true);

            if (!$detail) {
                $this->error('数据处理有误！');
            } else {
                $this->assign('detail', $detail['detail']);
            }
            $this->display();
        }
    }


    public function park_month_list()
    {
        $dataList = M('House_village_park_month')->where(array('village_id' => $this->village_id, 'park_cycle_type' => 1))->select();
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (empty($village_park_config)) {
            $park_config['village_id'] = $_SESSION['house']['village_id'];
            $park_config['park_versions'] = 1;
            $park_config['is_temporary_park_type'] = 0;
            $park_config['month_park_charge_type'] = 0;
            $park_config['is_park_month_type'] = 0;
            $park_config['is_user_receive_coupon'] = 0;
            M('House_village_park_config')->data($park_config)->add();
        }
        $this->assign('dataList', $dataList);
        $this->display();
    }

    public function park_month_add()
    {
        if (IS_POST) {
            $res = M('House_village_park_month')->where(array('type' => $_POST['type'], 'village_id' => $this->village_id, 'park_cycle_type' => 1))->find();
            if ($res) {
                $this->error('此类型已存在,请重新选择!');
            }
            $data['month_name'] = $_POST['month_name'];
            $data['park_cycle_type'] = 1;//月租车
            $data['village_id'] = $this->village_id;
            $village_park_month = M('House_village_park_month')->data($data)->add();
            if ($village_park_month) {
                $this->success('添加成功！', U('park_month_list'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }

    }

    public function park_month_edit()
    {
        if (IS_POST) {
            $info = D('House_village_park_month')->where(array('id' => array('neq', $_POST['id']), 'type' => $_POST['type'], 'village_id' => $this->village_id, 'park_cycle_type' => 1))->find();
            if ($info) {
                $this->error('此类型已存在,请重新选择!');
            }
            $data['month_name'] = $_POST['month_name'];
            $inset_id = M('House_village_park_month')->where(array('id' => $_POST['id']))->data($data)->save();
            if ($inset_id) {
                $this->success('修改成功！', U('park_month_list'));
            } else {
                $this->error('未做更改');
            }
        } else {
            $village_park_month = M('House_village_park_month')->where(array('id' => $_GET['id']))->find();
            $this->assign('village_park_month', $village_park_month);
            $this->display();
        }
    }

    public function park_month_del()
    {

        $result = D('House_village_park')->where(array('park_month_id' => $_GET['id']))->find();
        if ($result) {
            $this->error('请先删除此类型下面的费用才可以删除此项！');
        }
        $res = M('House_village_park_month')->where(array('id' => $_GET['id']))->delete();
        if ($res) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }

    }


    public function park_temporary_list()
    {
        $dataList = M('House_village_park_month')->where(array('village_id' => $this->village_id, 'park_cycle_type' => 2))->select();
        $this->assign('dataList', $dataList);
        $this->display();

    }

    public function park_temporary_add()
    {
        if (IS_POST) {
            $res = M('House_village_park_month')->where(array('type' => $_POST['type'], 'village_id' => $this->village_id, 'park_cycle_type' => 2))->find();
            if ($res) {
                $this->error('此类型已存在,请重新选择!');
            }
            $data['month_name'] = $_POST['month_name'];
            $data['type'] = $_POST['type'];
            //$data['expire_time_type'] = $_POST['expire_time_type'];
            $data['park_cycle_type'] = 2;//临时车
            $data['village_id'] = $this->village_id;
            $village_park_month = M('House_village_park_month')->data($data)->add();
            if ($village_park_month) {
                $this->success('添加成功！', U('park_month_list'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }

    }

    public function park_temporary_edit()
    {
        if (IS_POST) {
            $info = D('House_village_park_month')->where(array('id' => array('neq', $_POST['id']), 'type' => $_POST['type'], 'village_id' => $this->village_id, 'park_cycle_type' => 2))->find();
            if ($info) {
                $this->error('此类型已存在,请重新选择!');
            }
            $data['month_name'] = $_POST['month_name'];
            $data['type'] = $_POST['type'];
            //$data['expire_time_type'] = $_POST['expire_time_type'];

            $inset_id = M('House_village_park_month')->where(array('id' => $_POST['id']))->data($data)->save();
            if ($inset_id) {
                $this->success('修改成功！', U('park_month_list'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $village_park_month = M('House_village_park_month')->where(array('id' => $_GET['id']))->find();
            $this->assign('village_park_month', $village_park_month);
            $this->display();
        }
    }

    public function park_temporary_del()
    {
        $id = M('House_village_park_month')->where(array('id' => $_GET['id']))->delete();
        if ($id) {
            D('House_village_park')->where(array('park_month_id' => $_GET['id']))->delete();
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }


    public function temporary_charge_list()
    {
        $where['park_month_id'] = $_GET['park_month_id'];
        $count_user = M('House_village_park_change')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('House_village_park_change')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function temporary_charge_add()
    {
        if (IS_POST) {
            $data['park_month_id'] = $_POST['park_month_id'];
            $data['change_name'] = $_POST['change_name'];

            $data['change_money'] = $_POST['change_money'];
            $data['change_type'] = $_POST['change_type'];
            if ($_POST['change_type'] == 1) {
                $data['change_time'] = $_POST['change_time'];
            } elseif ($_POST['change_type'] == 2) {
                $data['change_time'] = $_POST['change_time'] * 60;
            }
            $data['village_id'] = $this->village_id;
            $insert_id = M('House_village_park_change')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('temporary_charge_list', array('park_month_id' => $_POST['park_month_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {

            $this->display();
        }

    }


    public function temporary_charge_edit()
    {
        if (IS_POST) {
            //$data['park_month_id'] = $_POST['park_month_id'];
            $data['change_name'] = $_POST['change_name'];
            if ($_POST['change_type'] == 1) {
                $data['change_time'] = $_POST['change_time'];
            } elseif ($_POST['change_type'] == 2) {
                $data['change_time'] = $_POST['change_time'] * 60;
            }
            $data['change_money'] = $_POST['change_money'];
            $data['change_type'] = $_POST['change_type'];
            $data['village_id'] = $this->village_id;
            $insert_id = M('House_village_park_change')->where(array('id' => $_POST['id']))->data($data)->save();
            if ($insert_id) {
                $this->success('修改成功！', U('temporary_charge_list', array('park_month_id' => $_POST['park_month_id'])));
            } else {
                $this->error('修改失败');
            }
        } else {
            $park_change = M('House_village_park_change')->where(array('id' => $_GET['id']))->find();
            if ($park_change['change_type'] == 2) {
                $park_change['change_time'] = $park_change['change_time'] / 60;
            }
            $this->assign('park_change', $park_change);
            $this->display();
        }

    }


    public function temporary_charge_del()
    {
        $id = M('House_village_park_change')->where(array('id' => $_GET['id']))->delete();
        if ($id) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }

    }

    public function mass_add()
    {
        if (IS_POST) {
            if ($_POST['end_type'] != $_POST['each_type']) {
                $this->error('选择类型必须统一');
            }
            $park_change = M('House_village_park_change')->where(array('park_month_id' => $_POST['park_month_id']))->order('id desc')->find();
            if ($park_change) {
                if ($park_change['change_time'] > 0) {
                    $start_time = $park_change['change_time'];
                    $start_money = $park_change['change_money'];
                } else {
                    $start_time = 0;
                    $start_money = 0;
                }
            } else {
                $start_time = 0;
                $start_money = 0;
            }
            if ($_POST['end_type'] == 1) {
                $end_time = $_POST['end_time'];
            } elseif ($_POST['end_type'] == 2) {
                $end_time = $_POST['end_time'] * 60;
            }
            if ($_POST['each_type'] == 1) {
                $each_time = $_POST['each_time'];
            } elseif ($_POST['each_type'] == 2) {
                $each_time = $_POST['each_time'] * 60;
            }
            $totle_time = floor($end_time / $each_time);
            for ($i = 1; $i <= $totle_time; $i++) {
                $data['change_name'] = '批量生成' . $i;
                $data['village_id'] = $this->village_id;
                $data['park_month_id'] = $_POST['park_month_id'];
                $data['change_type'] = $_POST['each_type'];
                if ($_POST['each_type'] == 1) {
                    $data['change_time'] = $_POST['each_time'] * $i + $start_time;//分钟
                    $data['change_money'] = $_POST['change_money'] * $i + $start_money;//钱
                } elseif ($_POST['each_type'] == 2) {
                    $data['change_time'] = $_POST['each_time'] * 60 * $i + $start_time;//分钟
                    $data['change_money'] = $_POST['change_money'] * $i + $start_money;//钱
                }

                M('House_village_park_change')->data($data)->add();

            }
//            $data['village_id'] = $this->village_id;
//            $insert_id = M('House_village_park_change')->data($data)->add();
//            if($insert_id){
//                $this->success('添加成功！',U('mass_add',array('park_month_id'=>$_POST['park_month_id'])));
//            }else{
//                $this->error('添加失败');
//            }
            $this->success('添加成功！', U('temporary_charge_list', array('park_month_id' => $_POST['park_month_id'])));
        } else {
            $park_change = M('House_village_park_change')->where(array('park_month_id' => $_GET['park_month_id']))->order('id desc')->find();
            if ($park_change) {
                if ($park_change['change_time'] > 0) {
                    $start_time = $park_change['change_time'];
                    $start_money = $park_change['change_money'];
                } else {
                    $start_time = 0;
                    $start_money = 0;
                }
            } else {
                $start_time = 0;
                $start_money = 0;
            }

            $this->assign('start_time', $start_time);
            $this->display();
        }
    }

    //2021/1/9 改版（废弃）
    public function set_user_receive_coupons()
    {
        if (IS_POST) {

            $data['is_user_receive_coupon'] = $_POST['is_user_receive_coupon'];
            $data['receive_coupon_fee'] = $_POST['receive_coupon_fee'];
            $result = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->data($data)->save();
            if ($result) {
                $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
                if ($village_park_config['is_user_receive_coupon'] == 1) {
                    echo json_encode(['is_user_receive_coupon' => 1, 'status' => 1]);
                    die;
                } else {
                    echo json_encode(['is_user_receive_coupon' => 0, 'status' => 1]);
                    die;
                }

            } else {
                echo json_encode(['is_user_receive_coupon' => 0, 'status' => 0]);
                die;
            }
        } else {
            $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
            if (2 == $village_park_config['park_versions'] && 'D1' == $village_park_config['park_sys_type']) {
                $this->error('D1智慧停车暂不支持优惠券');
            }
            $this->assign('village_park_config', $village_park_config);

            $base_url = D('House_village')->base_url;

            //二维码
            $qrcode_url = $this->config['site_url'] . $base_url . 'pages/village/smartPark/parkingFee?village_id=' . $_SESSION['house']['village_id'];
            $this->assign('qrcode_url', $qrcode_url);

            $this->display();
        }


    }

    //停车优惠券
    public function set_user_receive_coupon()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        fdump_api(['获取优惠券列表'.__LINE__,$village_park_config,M('House_village_park_config')->_sql()],'list_coupons',1);
        if (2 == $village_park_config['park_versions'] && 'D1' == $village_park_config['park_sys_type']) {
            $this->error('D1智慧停车暂不支持优惠券');
        }
        //商家列表
        $name = I('name');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = [];
        if ($name) {
            $where['m_name'] = ['like', '%' . trim($name) . '%'];
        }
        if ($start_time) {
            $where['add_time'] = ['gt', strtotime($start_time)];
        } elseif ($end_time) {
            $where['add_time'] = ['lt', strtotime($end_time)];
        }
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = $village_park_config['park_sys_type'];
        $list = D('House_village_park_coupons')->couponsShopList($where);
        $this->assign('list', $list);
        //优惠券列表
        $c_title = I('c_title');
        $c_start_time = I('c_start_time');
        $c_end_time = I('c_end_time');
        $where = [];
        if ($c_title) {
            $where['c_title'] = ['like', '%' . trim($c_title) . '%'];
        }
        if ($c_start_time && $c_end_time) {
            $where['add_time'][] = ['gt', strtotime($c_start_time)];
            $where['add_time'][] = ['lt', strtotime($c_end_time . ' 23:59:59')];
        } else {
            if ($c_start_time) {
                $where['add_time'] = ['gt', strtotime($c_start_time)];
            }
            if ($c_end_time) {
                $where['add_time'] = ['lt', strtotime($c_end_time . ' 23:59:59')];
            }
        }
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = $village_park_config['park_sys_type'];
        $list_coupons = D('House_village_park_coupons')->getPackCouponsList($where);
        fdump_api(['获取优惠券列表'.__LINE__,$list_coupons,$where],'list_coupons',1);
        $this->assign('list_coupons', $list_coupons);
        //购买记录
        $title = I('title');
        $param = I('param');
        $pay_start_time = I('pay_start_time');
        $pay_end_time = I('pay_end_time');
        $where = [];
        if ($title) {
            $where[$param] = ['like', '%' . trim($title) . '%'];
        }
        if ($pay_start_time && $pay_end_time) {
            $where['mc.add_time'][] = ['gt', strtotime($pay_start_time)];
            $where['mc.add_time'][] = ['lt', strtotime($pay_end_time . ' 23:59:59')];
        } else {
            if ($pay_start_time) {
                $where['mc.add_time'] = ['gt', strtotime($pay_start_time)];
            }
            if ($pay_end_time) {
                $where['mc.add_time'] = ['lt', strtotime($pay_end_time . ' 23:59:59')];
            }
        }
        $where['mc.village_id'] = $this->village_id;
        $where['mc.park_sys_type'] = $village_park_config['park_sys_type'];
        $pay_record = D('House_village_park_coupons')->payRecordList($where);
        $this->assign('pay_record', $pay_record);
        $this->display();
    }

    //d5查看停车详情
    public function park_details(){
        $details=D('House_village_car_access_record')->getCarSnapInfo($this->village_id,intval($_GET['id']));
        $this->assign('details', $details);
        $this->display();
    }

    //获取品牌下类型数据
    public function getHardwareBrandType(){
        if (IS_POST) {
            if (empty($_POST['brand_id'])) {
                $this->error('缺少必要参数！');
            }
            $data=D('HardwareBrand')->getType(intval($_POST['brand_id']),2);
            $this->ajaxReturn(array('status' => 1, 'msg' => '', 'data' => $data));
        }
    }

    public function passage_list()
    {
        $where['village_id'] = $_SESSION['house']['village_id'];
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        $where['park_sys_type'] = $village_park_config['park_sys_type'];
        $is_d5_park=false;
        $getBrand=D('HardwareBrand')->getBrand();
        $getType=D('HardwareBrand')->getType(0,2);
        if (2 == $village_park_config['park_versions'] && 'D5' == $village_park_config['park_sys_type']){
            $is_d5_park=true;
            if(isset($_GET['type']) && !empty($_GET['type'])){
                $where['park_type'] = trim($_GET['type']);
            }
            if(isset($_GET['passage_name']) && !empty($_GET['passage_name'])){
                $where['passage_name'] = ['like', '%' . trim($_GET['passage_name']) . '%'];
            }
            if(isset($_GET['device_number']) && !empty($_GET['device_number'])){
                $where['device_number'] = ['like', '%' . trim($_GET['device_number']) . '%'];
            }
            if(isset($_GET['status']) && !empty($_GET['status'])){
                $where['status'] = intval($_GET['status']) - 1;
            }
        }
        if (2 == $village_park_config['park_versions'] && $village_park_config['park_sys_type'] == 'D3') {
            $count_user = M('Park_passage')->where($where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, 20, 'page');
            $passage_direction = array('出口', '入口', '出入口');
            $status_desc = array('关闭', '开启');
            $dataList = M('Park_passage')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
            foreach ($dataList as &$v) {
                $v['passage_direction_desc'] = $passage_direction[$v['passage_direction']];
                $v['status_desc'] = $status_desc[$v['status']];
            }
        } else {
            if (2 == $village_park_config['park_versions'] && 'D1' == $village_park_config['park_sys_type']) {
                // d1智慧停车 只支持出场 没有入场功能
                $where['passage_direction'] = 0;
            }
            $count_user = M('Park_passage')->where($where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, 20, 'page');
            $passage_direction = array('出口', '入口', '出入口');
            $status_desc = array('关闭', '开启');
            $dataList = M('Park_passage')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
            $channel=D('ParkService')->getChannel($village_park_config);//获取D5停车数据
            foreach ($dataList as &$v) {
                if($is_d5_park){
                    $park_brand='';
                    foreach ($getBrand as $b1){
                        if($v['park_brand'] == $b1['key']){
                            $park_brand=$b1['title'];
                            break;
                        }
                    }
                    $park_type='';
                    foreach ($getType as $b2){
                        if($v['park_type'] == $b2['key']){
                            $park_type=$b2['title'];
                            break;
                        }
                    }
                    $v['park_brand']=$park_brand;
                    $v['park_type']=$park_type;
                }
                if($village_park_config['park_sys_type'] == 'D5'){
                    if(!empty($channel) && in_array($v['device_number'],$channel)){
                        if($v['status'] == 3){
                            M('Park_passage')->where(['id'=>$v['id']])->save(['status'=>1]);
                        }
                        $status='<span>正常</span>';
                    }else{
                        if($v['status'] == 1){
                            M('Park_passage')->where(['id'=>$v['id']])->save(['status'=>3]);
                        }
                        $status='<span style="color: red">离线</span>';
                    }
                }
                else{
                if($v['status'] == 0){
                    $status='关闭';
                }elseif ($v['status'] == 1){
                    $status='正常';
                }elseif ($v['status'] == 3){
                    $status='离线';
                }else{
                    $status='';
                }
                }
                $v['status']=$status;
                $v['passage_direction_desc'] = $passage_direction[$v['passage_direction']];
                $v['status_desc'] = $status_desc[$v['status']];
            }
        }
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->assign('village_park_config', $village_park_config);
        $base_url = D('House_village')->base_url;

        //二维码
        $qrcode_url = $this->config['site_url'] . $base_url . 'pages/village/smartPark/parkingFeetwo';
        $qrcode_url1 = $this->config['site_url'] . $base_url . 'pages/parkingLot/pages/registerCar?isRegisterCar=0&village_id='.$_SESSION['house']['village_id'];

        $this->assign('qrcode_url', $qrcode_url);
        $this->assign('qrcode_url1', $qrcode_url1);
        $this->assign('village_id', $_SESSION['house']['village_id']);
        if ($is_d5_park){
            $this->assign('d5_park_type', $getType);
            $this->display('passage_d5_list');
        }else{
            $this->display();
        }

    }

    public function passage_add()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            if (empty($_POST['passage_name'])) {
                $this->error('通道名称不能为空！');
            }
            $data['passage_name'] = $_POST['passage_name'];
            $data['channel_number'] = $_POST['channel_number'];
            $data['device_number'] = $_POST['device_number'];
            $long_lat = explode(',', $_POST['long_lat']);
            $data['long'] = $long_lat[0];
            $data['lat'] = $long_lat[1];
            $data['status'] = $_POST['status'];
            if (2 == $village_park_config['park_versions'] && 'D1' == $village_park_config['park_sys_type'] && intval($_POST['passage_direction']) > 0) {
                // d1智慧停车 只支持出场 没有入场功能
                $this->error('d1智慧停车 当前只支持出场！');
            }elseif(2 == $village_park_config['park_versions'] && 'D3' == $village_park_config['park_sys_type']){
                $data['device_type'] = $_POST['device_type'];
            }
            elseif (2 == $village_park_config['park_versions'] && 'D5' == $village_park_config['park_sys_type']){
                //d5智慧停车
                if (empty($_POST['park_brand'])) {
                    $this->error('请选择通道品牌！');
                }
                if (empty($_POST['park_type'])) {
                    $this->error('请选择通道类型！');
                }
                if (empty($_POST['passage_name'])) {
                    $this->error('请输入通道名称！');
                }
                if (empty($_POST['channel_number'])) {
                    $this->error('请输入通道号！');
                }
                if($_POST['start_time']){
                    $data['start_time'] =strtotime($_POST['start_time']);
                }else{
                    $data['start_time']=time();
                }
                if($_POST['park_param']){
                    $data['park_param'] =$_POST['park_param'];
                }
                if($_POST['park_remarks']){
                    $data['park_remarks'] =$_POST['park_remarks'];
                }
                $data['device_number'] =$_POST['channel_number'];
                $data['park_brand'] =$_POST['park_brand'];
                $data['park_type'] =$_POST['park_type'];
                $data['add_time'] =time();
                unset($data['status']);
            }
            $data['passage_direction'] = $_POST['passage_direction'] ? $_POST['passage_direction'] : 0;
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $res = M('Park_passage')->data($data)->add();
            if ($res) {
//                if($_POST['status'] == 1){
//                    $park_system = M('Park_system')->where(array('park_id'=>$_SESSION['house']['village_id']))->find();
//                    $park_data['operate_type'] = 1;
//                    $park_data['passtype'] = $_POST['passage_direction'];
//                    $park_data['park_id'] = $_SESSION['house']['village_id'];
//                    $park_data['passname'] = $_POST['passage_name'];
//                    $park_data['channel_id'] = createRandomStr(16,true,true);
//                    $json_data['service_name'] = 'gate_sync';
//                    $json_data['data'] = $park_data;
//                    $post_data['unserialize_desc'] = serialize($json_data);
//                    $post_data['park_id'] = $_SESSION['house']['village_id'];
//                    $post_data['token'] = $park_system['token'];
//                    $post_data['service_name'] = 'gate_sync';
//                    $post_data['village_id'] = $this->village_id;
//                    M('park_total_record')->data($post_data)->add();//储值卡充值下发
//                }
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $this->assign('d5_park_brand', D('HardwareBrand')->getBrand());
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }

    }

    public function passage_edit()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            if (empty($_POST['passage_name'])) {
                $this->error('通道名称不能为空！');
            }
            $data['passage_name'] = $_POST['passage_name'];
            $data['channel_number'] = $_POST['channel_number'];
            $data['device_number'] = $_POST['device_number'];
            $long_lat = explode(',', $_POST['long_lat']);
            $data['long'] = $long_lat[0];
            $data['lat'] = $long_lat[1];
            $data['status'] = $_POST['status'];
            $data['passage_direction'] = $_POST['passage_direction'];

            if (2 == $village_park_config['park_versions'] && 'D5' == $village_park_config['park_sys_type']){
                //d5智慧停车
                if (empty($_POST['park_brand'])) {
                    $this->error('请选择通道品牌！');
                }
                if (empty($_POST['park_type'])) {
                    $this->error('请选择通道类型！');
                }
                if (empty($_POST['passage_name'])) {
                    $this->error('请输入通道名称！');
                }
                if (empty($_POST['channel_number'])) {
                    $this->error('请输入通道号！');
                }
                $data['device_number'] =$_POST['channel_number'];
                if($_POST['park_param']){
                    $data['park_param'] =$_POST['park_param'];
                }
                if($_POST['park_remarks']){
                    $data['park_remarks'] =$_POST['park_remarks'];
                }
                $data['park_brand'] =$_POST['park_brand'];
                $data['park_type'] =$_POST['park_type'];
                $data['add_time'] =time();
                unset($data['status']);
            }
            elseif(2 == $village_park_config['park_versions'] && 'D3' == $village_park_config['park_sys_type']){
                $data['device_type'] = $_POST['device_type'];
            }
            $res = M('Park_passage')->where(array('id' => $_POST['id']))->data($data)->save();
            if ($res) {
//                if($_POST['status'] == 1){
//                    $park_system = M('Park_system')->where(array('park_id'=>$_SESSION['house']['village_id']))->find();
//                    $park_data['operate_type'] = 2;
//                    $park_data['passtype'] = $_POST['passage_direction'];
//                    $park_data['park_id'] = $_SESSION['house']['village_id'];
//                    $park_data['passname'] = $_POST['passage_name'];
//                    $park_data['channel_id'] = createRandomStr(16,true,true);
//                    $json_data['service_name'] = 'gate_sync';
//                    $json_data['data'] = $park_data;
//                    $post_data['unserialize_desc'] = serialize($json_data);
//                    $post_data['park_id'] = $_SESSION['house']['village_id'];
//                    $post_data['token'] = $park_system['token'];
//                    $post_data['service_name'] = 'gate_sync';
//                    $post_data['village_id'] = $this->village_id;
//                    M('park_total_record')->data($post_data)->add();//储值卡充值下发
//                }
                $this->success('修改成功！');
            } else {
                $this->error('未做更改！');
            }
        } else {

            $res = M('Park_passage')->where(array('id' => $_GET['id']))->find();
            if (2 == $village_park_config['park_versions'] && 'D5' == $village_park_config['park_sys_type']){
                $this->assign('d5_park_brand', D('HardwareBrand')->getBrand());
                $res['channel_number']=$res['device_number'];
            }
            $this->assign('passage', $res);
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function passage_del()
    {
        $res = M('Park_passage')->where(array('id' => $_GET['id']))->find();
        if ($res) {
            $id = M('Park_passage')->where(array('id' => $_GET['id']))->delete();
            if ($id) {
//                $park_system = M('Park_system')->where(array('park_id'=>$_SESSION['house']['village_id']))->find();
//                $park_data['operate_type'] = 3;
//                $park_data['passtype'] = $res['passage_direction'];
//                $park_data['park_id'] = $_SESSION['house']['village_id'];
//                $park_data['passname'] = $res['passage_name'];
//                $park_data['channel_id'] = createRandomStr(16,true,true);
//                $json_data['service_name'] = 'gate_sync';
//                $json_data['data'] = $park_data;
//                $post_data['unserialize_desc'] = serialize($json_data);
//                $post_data['park_id'] = $_SESSION['house']['village_id'];
//                $post_data['token'] = $park_system['token'];
//                $post_data['service_name'] = 'gate_sync';
//                $post_data['village_id'] = $this->village_id;
//                M('park_total_record')->data($post_data)->add();//储值卡充值下发
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        }

    }


    /**
     * D3获取token
     * @author:zhubaodi
     * @date_time: 2021/9/8 13:45
     */
    public function getToken()
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $url = $this->url;
        $data = [
            'method' => 'auth',
            'AppID' => $this->AppID,
            'AppSecret' => $this->AppSecret
        ];
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), []);
        $res = json_decode($res, true);
        $token = '';
        if ($res['code'] == 200) {
            $token = $res['data']['token'];
        }
        return $token;
    }

    /**
     * D3远程抬竿
     * @author:zhubaodi
     * @date_time: 2021/9/8 13:45
     */
    public function open_gate()
    {
        if (empty($_POST['id'])){
            $this->error('设备id不能为空！');
        }
        $passage_info=M('Park_passage')->where(['id'=>$_POST['id']])->field('*')->find();
        if (empty($passage_info)){
            $this->error('设备信息不能为空！');
        }
        $where='';
        if ($passage_info['passage_direction']==1){
            $start_time=time() - 150;
            $end_time=time() + 150;
            $where='park_type=1 and add_time >='.$start_time.' and add_time <='.$end_time;

        }
        if ($passage_info['passage_direction']==0){
            $start_time=time() - 150;
            $end_time=time() + 150;
            $where='park_type=2 and add_time >='.$start_time.' and add_time <='.$end_time;

        }
        $type = I('type');
        if (!empty($where) && (!$type || $type!='admin')){
            $log_info=M('Park_plateresult_log')->where($where)->field('*')->order('id DESC')->find();
        }else{
            $log_info=[];
        }
        $park_log_data = [];
        if (!empty($log_info)) {
            $park_log_data['car_number'] = $log_info['car_number'];
            $park_log_data['channel_id'] = $log_info['channel_id'];
            $park_log_data['park_type'] = $log_info['park_type'];
            $park_log_data['add_time'] = time();
        }else{
            $park_log_data['channel_id'] = $passage_info['device_number'];
            $park_log_data['park_type'] = $passage_info['passage_direction']==1?1:2;
            $park_log_data['add_time'] = time();
        }

        $res=M('park_open_log')->add($park_log_data);
        if ($res>0) {
            $this->ajaxReturn(array('status' => 1, 'info' => '开闸指令下发成功', 'data' => $res));
        } else {
            $this->ajaxReturn(array('status' => 0, 'info' => '开闸指令下发失败', 'data' => $res));
        }
    }

    public function getParkingApi($data, $url)
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $token = $this->getToken();
        $headersArr = [];
        $headersArr[] = "token:" . $token;
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), $headersArr);
        $res = json_decode($res, true);
        $dataList = [];
        if ($res['code'] == 200) {
            $dataList = $res['data'];
        }
        // print_r($dataList);exit;
        return $dataList;
    }

    public function editParkingApi($data, $url)
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $token = $this->getToken();
        $headersArr = [];
        $headersArr[] = "token:" . $token;
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), $headersArr);
        $res = json_decode($res, true);

        return $res;
    }

    //添加店铺 ------------------------------店铺相关------------------------------------
    public function add_pack_shop()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        if ($_POST) {
            $post = $_POST;
            if ($post['type'] == 1) {
                $data['m_name'] = $post['m_name'];
            } else {
                $data['m_name'] = $post['m_names'];
            }
            $where = ['m_name' => $data['m_name']];
            $where['village_id'] = $this->village_id;
            $where['status'] = array('neq',-1);
            $where['park_sys_type'] = $village_park_config['park_sys_type'];
            $info = D('House_village_park_coupons')->getShop($where);
            if ($info) {
                $this->error('店铺已存在');
            }
            $data['village_id'] = $this->village_id;
            $data['bind_m_id'] = $post['bind_m_id'];
            if (intval($data['bind_m_id'])<=0 && intval($post['type']) == 1) {
                $this->error('请重新选择下店铺');
            }
            $data['type'] = $post['type'];
            $data['remark'] = $post['remark'];
            $data['add_time'] = time();
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            try {
                $res = D('House_village_park_coupons')->subShop($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    // 二维码
    public function get_own_qrcode()
    {
        header('Content-Type: image/png');
        ob_clean();
        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
        $size = $_GET['size'] ? $_GET['size'] : 10;
        $is_down = $_GET['down'] ? true : false;

        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)), false, 0, $size, 1, $is_down);
    }

    //编辑店铺
    public function edit_pack_shop()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        $m_id = I('m_id');
        if (!$m_id) {
            $this->error('参数异常');
        }
        if ($_POST) {
            $post = $_POST;
            if ($post['type'] == 1) {
                $data['m_name'] = $post['m_name'];
            } else {
                $data['m_name'] = $post['m_names'];
            }
            if (intval($post['bind_m_id'])>0) {
                // 必须更换店铺才可以
                $data['bind_m_id'] = $post['bind_m_id'];
            }
            $data['type'] = $post['type'];
            $data['remark'] = $post['remark'];
            $data['edit_time'] = time();
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            try {
                $res = D('House_village_park_coupons')->subShop($data, $m_id);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $where = ['m_id' => $m_id];
            $info = D('House_village_park_coupons')->getShop($where);
            $this->assign('info', $info);
            $this->display();
        }
    }

    //搜索店铺
    public function shop_search()
    {
        if (IS_AJAX) {
            $post = $_POST;
            if ($post['m_name']) {
                $village_id = $this->village_id;
                $where['name'] = ['like', '%' . $post['m_name'] . '%'];
                $list = D('House_village_park_coupons')->searchShop($where, $village_id);

                if ($list && count($list) > 0) {
                    $res['info'] = '成功';
                    $res['status'] = 1;
                    $res['list'] = $list;
                    $this->ajaxReturn($res);
                } else {
                    $this->error('未搜索到店铺。（已添加的无法再次搜索到）');
                }
            } else {
                $this->error('店铺不存在');
            }
        } else {
            $this->display();
        }
    }

    //删除店铺
    public function shop_del()
    {
        $m_id = I('m_id');
        if (!$m_id) {
            $this->error('参数异常');
        }
        $where['m_id'] = $m_id;
        $res = D('House_village_park_coupons')->delShop($where);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //添加优惠券 ------------------------------------优惠券---------------------------------------------
    public function add_pack_coupons()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        if ($_POST) {
            $data = $_POST;
            if (!$data['c_title']) {
                $this->error('请填写优惠券名称');
            }
            if (!$data['c_price']) {
                $this->error('请填写优惠券价格');
            }
            if (!$data['c_free_price']) {
                $this->error('请填写每一次停车免费金额');
            }
            $where['c_title'] = $data['c_title'];
            $where['village_id'] = $this->village_id;
            $where['park_sys_type'] = $village_park_config['park_sys_type'];
            $where['status'] = array('neq','-1');

            $info = D('House_village_park_coupons')->getCouponsFind($where);
            if ($info) {
                $this->error('优惠券已存在');
            }
            $data['village_id'] = $this->village_id;
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $res = D('House_village_park_coupons')->subPackCoupons($data);
            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    //编辑优惠券
    public function edit_pack_coupons()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        $id = I('c_id');
        if ($_POST) {
            $data = $_POST;
            if (!$data['c_title']) {
                $this->error('请填写优惠券名称');
            }
            if (!$data['c_price']) {
                $this->error('请填写优惠券价格');
            }
            if (!$data['c_free_price']) {
                $this->error('请填写每一次停车免费金额');
            }
            $where['c_id'] = $id;
            $info = D('House_village_park_coupons')->getCouponsFind($where);
            if (!$info) {
                $this->error('优惠券不存在');
            }
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $res = D('House_village_park_coupons')->subPackCoupons($data, $id);
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修复失败');
            }
        } else {
            $where['c_id'] = $id;
            $info = D('House_village_park_coupons')->getCouponsFind($where);
            $this->assign('info', $info);
            $this->display();
        }
    }

    //删除优惠券
    public function del_pack_coupons()
    {
        $id = I('c_id');
        if (!$id) {
            $this->error('参数异常');
        }
        $data['status'] = -1;
        $res = D('House_village_park_coupons')->subPackCoupons($data, $id);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //店铺优惠券列表 ----------------------------店铺优惠券----------------------------------
    public function pack_shop_coupons()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        $mid = I('m_id');
        if (!$mid) {
            $this->error('参数异常');
        }
        $where['mc.village_id'] = $this->village_id;
        $where['mc.mid'] = $mid;
        $where['mc.park_sys_type'] = $village_park_config['park_sys_type'];
        $list_coupons = D('House_village_park_coupons')->getPackShopCouponsList($where);
        $this->assign('list_coupons', $list_coupons);
        $this->assign('mid', $mid);
        $this->display();
    }

    //添加店铺优惠券
    public function add_pack_shop_coupons()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $_SESSION['house']['village_id']))->find();
        $mid = I('mid');
        if (!$mid) {
            $this->error('参数异常');
        }
        if ($_POST) {
            $data = $_POST;
            if (!$data['cid']) {
                $this->error('请选择优惠券');
            }
            if (!$data['free_money']) {
                $this->error('每次停车免费金额异常');
            }
            if (!$data['num']) {
                $this->error('请输入添加数量');
            }
            if (!$data['receivable_money']) {
                $this->error('应收金额异常');
            }
            if (!$data['receivable_money']) {
                $this->error('请输入实付金额');
            }
            $data['village_id'] = $this->village_id;
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $res = D('House_village_park_coupons')->subShopCoupons($data);
            if ($res) {
                $this->error('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->assign('mid', $mid);
            $where['status'] = 0;
            $where['village_id'] = $this->village_id;
            $coupons_list = D('House_village_park_coupons')->getCouponsList($where);
            $this->assign('coupons_list', $coupons_list);
            $this->display();
        }
    }

    //获取优惠券参数 已计算对应的价格
    public function getCoupons()
    {
        $cid = I('cid');
        if (!$cid) {
            $this->error('请选择优惠券');
        }
        $where['village_id'] = $this->village_id;
        $where['c_id'] = $cid;
        $field = 'c_id,c_price,c_free_price';
        $coupons = D('House_village_park_coupons')->getCouponsFind($where, $field);
        if ($coupons) {
            $res['info'] = '成功';
            $res['status'] = 1;
            $res['coupons'] = $coupons;
        } else {
            $res['info'] = '没有找到对应的优惠券';
            $res['status'] = 0;
            $res['coupons'] = [];
        }
        $this->ajaxReturn($res);
    }

    public function park_statistics()
    {
        $village_id = $this->village_id;
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
		## 得到小区是否支持电动车
		$electriccar_has = M('House_village_parking_car')->where(['car_type' => '1', 'village_id' => $village_id])->find();
		$this->assign('electriccar_has', $electriccar_has ? true : false);
		
		## 电动车出入记录
		if($_GET['type'] == 'electriccar_statistics'){
			$param = I('param');
			$name = I('name');
			$start_time = I('start_time');
			$end_time = I('end_time');
			
			$where = [
				'i.village_id' => $village_id,
			];
			$where_count = [
			    'village_id' => $village_id,
            ];
			if($param && $name){
				switch ($param){
					case 'car_number':
						$where['i.car_number'] = ['like','%'.trim($name).'%'];
                        $where_count['car_number'] = ['like','%'.trim($name).'%'];
						break;
					case 'car_stop_num':
						$where['i.car_stop_num'] = ['like','%'.trim($name).'%'];
                        $where_count['car_stop_num'] = ['like','%'.trim($name).'%'];
						break;
					case 'car_user_name':
						$where['c.car_user_name'] = ['like','%'.trim($name).'%'];
						$car_stop_num = D('House_village_parking_car')->where(['car_user_name'=>['like','%'.trim($name).'%']])->getField('car_stop_num',true);
                        $where_count['car_stop_num'] = ['in',$car_stop_num];
						break;
					case 'car_user_phone':
						$where['c.car_user_phone'] = ['like','%'.trim($name).'%'];
                        $car_stop_num = D('House_village_parking_car')->where(['car_user_phone'=>['like','%'.trim($name).'%']])->getField('car_stop_num',true);
                        $where_count['car_stop_num'] = ['in',$car_stop_num];
						break;
				}
			}
			
			## 时间筛选
			$start_time = I('start_time');
			$end_time = I('end_time');
			if($start_time && $end_time){
				$where['i.add_time'][] = ['gt',strtotime($start_time)];
				$where['i.add_time'][] = ['lt',strtotime($end_time.' 23:59:59')];
                $where_count['add_time'][] = ['gt',strtotime($start_time)];
                $where_count['add_time'][] = ['lt',strtotime($end_time.' 23:59:59')];
			}else {
				if ($start_time) {
					$where['i.add_time'] = ['gt', strtotime($start_time)];
                    $where_count['add_time'] = ['gt',strtotime($start_time)];
				}
				if ($end_time) {
					$where['i.add_time'] = ['lt', strtotime($end_time . ' 23:59:59')];
                    $where_count['add_time'] = ['lt',strtotime($end_time.' 23:59:59')];
				}
			}
			
			import('@.ORG.merchant_page');
			$left_join = "as i left join ".C('DB_PREFIX').'house_village_parking_car as c on (i.village_id=c.village_id AND i.car_stop_num=c.car_stop_num)';
			$count = D('In_park_electriccar')->where($where_count)->count();
			$p = new Page($count, 20, 'page');
			
			$in_park_data = D('In_park_electriccar')->join($left_join)->where($where)->order("i.id DESC")->limit($p->firstRow.','.$p->listRows)->select();
			$this->assign('in_park_data', $in_park_data);
            $this->assign('pagebar', $p->show());
			
			$this->display();
			die;
		}

        //车辆进出统计导出
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        $this->assign('village_park_config', $village_park_config);
        $param = I('param');
        $name = I('name');
        $is_out = I('is_out');
        $start_time = I('start_time');
        $end_time = I('end_time');
        if(($_GET['type'] == 'basicinfo' || !$_GET['type']) && in_array($village_park_config['park_sys_type'],['D5','D6'])){
            $where=[
                'r.business_type'=>0,
                'r.business_id'=>$this->village_id,
                'r.park_sys_type'=>$village_park_config['park_sys_type']
            ];
            if ($start_time && $end_time) {
                if ($is_out == 1) {
                    $where['r.accessType']='1';
                    $where['r.accessTime'] = array('between',array(strtotime($start_time),strtotime($end_time . ' 23:59:59')));
                } else {
                    $where['r.accessType']='2';
                    $where['r.accessTime'] = array('between',array(strtotime($start_time),strtotime($end_time . ' 23:59:59')));
                }
            }
            else {
                if ($start_time) {
                    if ($is_out == 1) {
                        $where['r.accessType']='1';
                        $where['r.accessTime'] = ['gt', strtotime($start_time)];
                    } else {
                        $where['r.accessType']='2';
                        $where['r.accessTime'] = ['gt', strtotime($start_time)];
                    }
                }
                if ($end_time) {
                    if ($is_out == 1) {
                        $where['r.accessType']='1';
                        $where['r.accessTime'] = ['lt', strtotime($end_time . ' 23:59:59')];
                    } else {
                        $where['r.accessType']='2';
                        $where['r.accessTime'] = ['lt', strtotime($end_time . ' 23:59:59')];
                    }
                }
            }
            if ($param && $name) {
                switch ($param) {
                    case 'car_number':
                        $where['r.car_number'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'position_num':
                        $where['c.car_number'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'car_user_name':
                        $where['r.user_name'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'car_user_phone':
                        $where['r.user_phone'] = ['like', '%' . trim($name) . '%'];
                        break;
                }
            }
            if ($is_out) {
                if ($is_out == 1) {
                    $where['r.accessType'] = 1;
                } else {
                    $where['r.accessType'] = 2;
                }
            }
            $list=D('House_village_car_access_record')->getlist($this->village_id,$where,$village_park_config['park_sys_type']);
        }
        elseif(($_GET['type'] == 'basicinfo' || !$_GET['type'])){
            $paramList = [
                'park_id'       => $village_id,
                'village_id'    => $village_id,
                'park_sys_type' => $village_park_config['park_sys_type'],
            ];
            if ($is_out == 2) {
                $paramList['isOutPark'] = 1;
            } else {
                $paramList['isInPark']      = 1;
            }
            if ($start_time && $end_time) {
                if ($is_out == 1) {
                    $paramList['in_time_start'] = strtotime($start_time);
                    $paramList['in_time_end']   = strtotime($end_time . ' 23:59:59');
                } elseif ($is_out == 2) {
                    $paramList['out_time_start'] = strtotime($start_time);
                    $paramList['out_time_end']   = strtotime($end_time . ' 23:59:59');
                }
            } else {
                if ($start_time) {
                    if ($is_out == 1) {
                        $paramList['in_time_start'] = strtotime($start_time);
                    } elseif ($is_out == 2) {
                        $paramList['out_time_start'] = strtotime($start_time);
                    }
                }
                if ($end_time) {
                    if ($is_out == 1) {
                        $paramList['in_time_end']   = strtotime($end_time . ' 23:59:59');
                    } elseif ($is_out == 2) {
                        $paramList['out_time_end']   = strtotime($end_time . ' 23:59:59');
                    }
                }
            }
            if ($param && $name) {
                switch ($param) {
                    case 'car_number':
                        $paramList['car_number']      = $name;
                        break;
                    case 'position_num':
                        $paramList['position_num']      = $name;
                        break;
                    case 'car_user_name':
                        $paramList['car_user_name']      = $name;
                        break;
                    case 'car_user_phone':
                        $paramList['car_user_phone']      = $name;
                        break;
                }
            }
            fdump_api($paramList, '$paramList');
            $list = D('House_village_parking_car')->parkPassList($paramList);
        }

        //停车费统计
        if (isset($_GET['type']) && $_GET['type'] == 'txtstore') {
            $params = I('param');
            $name = I('title');
            $car_type = I('car_type');
            $pay_type = I('pay_type');
            $o_start_time = I('o_start_time');
            $o_end_time = I('o_end_time');
            $str_where='';
            if ($params && $name) {
                switch ($param) {
                    case 'car_number':
                        //$map['c.car_number'] = ['like', '%' . trim($name) . '%'];
                        $str_where='c.car_number LIKE "%' .trim($name) .'%" OR o.car_number LIKE "%' .trim($name) .'%"';
                        break;
                    case 'car_user_name':
                        $map['c.car_user_name'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'car_user_phone':
                        $map['c.car_user_phone'] = ['like', '%' . trim($name) . '%'];
                        break;
                }
            }
            if ($car_type) {
                $map['o.car_type'] = $car_type;
            }
            if ($pay_type) {
                if ($pay_type == 2) {
                    $map['o.pay_type'] = 0;
                } else {
                    $map['o.pay_type'] = $pay_type;
                }
            }
            if ($o_start_time && $o_end_time) {
                $map['o.pay_time'][] = ['gt', strtotime($o_start_time)];
                $map['o.pay_time'][] = ['lt', strtotime($o_end_time . ' 23:59:59')];
            } else {
                if ($o_start_time) {
                    $map['o.pay_time'] = ['gt', strtotime($o_start_time)];
                }
                if ($o_end_time) {
                    $map['o.pay_time'] = ['lt', strtotime($o_end_time . ' 23:59:59')];
                }
            }
            $map['o.village_id'] = $village_id;
            $map['o.order_type'] = 'park';
            $map['o.paid'] = 1;
            if($str_where){
                $map['_string']=$str_where;
            }
            $park_cost = D('House_village_park_coupons')->getParkCostList1($map);
            $this->assign('park_cost', $park_cost);
        }
        $this->assign('list', $list);
        $this->display();
    }

    //车辆进出统计导出
    public function in_out_car_export()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        $param = I('param');
        $name = I('name');
        $is_out = I('is_out');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = [];
        $village_name = $this->village['village_name'];
        if($village_park_config['park_sys_type'] == 'D5'){
            $where=[
                'r.business_type'=>0,
                'r.business_id'=>$this->village_id,
                'r.park_sys_type'=>'D5'
            ];
            if ($start_time && $end_time) {
                if ($is_out == 1) {
                    $where['r.accessType']='1';
                    $where['r.accessTime'] = array('between',array(strtotime($start_time),strtotime($end_time . ' 23:59:59')));
                } else {
                    $where['r.accessType']='2';
                    $where['r.accessTime'] = array('between',array(strtotime($start_time),strtotime($end_time . ' 23:59:59')));
                }
            }
            else {
                if ($start_time) {
                    if ($is_out == 1) {
                        $where['r.accessType']='1';
                        $where['r.accessTime'] = ['gt', strtotime($start_time)];
                    } else {
                        $where['r.accessType']='2';
                        $where['r.accessTime'] = ['gt', strtotime($start_time)];
                    }
                }
                if ($end_time) {
                    if ($is_out == 1) {
                        $where['r.accessType']='1';
                        $where['r.accessTime'] = ['lt', strtotime($end_time . ' 23:59:59')];
                    } else {
                        $where['r.accessType']='2';
                        $where['r.accessTime'] = ['lt', strtotime($end_time . ' 23:59:59')];
                    }
                }
            }
            if ($param && $name) {
                switch ($param) {
                    case 'car_number':
                        $where['r.car_number'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'position_num':
                        $where['c.car_number'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'car_user_name':
                        $where['r.user_name'] = ['like', '%' . trim($name) . '%'];
                        break;
                    case 'car_user_phone':
                        $where['r.user_phone'] = ['like', '%' . trim($name) . '%'];
                        break;
                }
            }
            if ($is_out) {
                if ($is_out == 1) {
                    $where['r.accessType'] = 1;
                } else {
                    $where['r.accessType'] = 2;
                }
            }
        }
        else{
            $paramList = [
                'park_id'       => $this->village_id,
                'village_id'    => $this->village_id,
                'park_sys_type' => $village_park_config['park_sys_type'],
            ];
            if ($is_out == 2) {
                $paramList['isOutPark'] = 1;
            } else {
                $paramList['isInPark']  = 1;
            }
            if ($start_time && $end_time) {
                if ($is_out == 1) {
                    $paramList['in_time_start'] = strtotime($start_time);
                    $paramList['in_time_end']   = strtotime($end_time . ' 23:59:59');
                } elseif ($is_out == 2) {
                    $paramList['out_time_start'] = strtotime($start_time);
                    $paramList['out_time_end']   = strtotime($end_time . ' 23:59:59');
                }
            } else {
                if ($start_time) {
                    if ($is_out == 1) {
                        $paramList['in_time_start'] = strtotime($start_time);
                    } elseif ($is_out == 2) {
                        $paramList['out_time_start'] = strtotime($start_time);
                    }
                }
                if ($end_time) {
                    if ($is_out == 1) {
                        $paramList['in_time_end']   = strtotime($end_time . ' 23:59:59');
                    } elseif ($is_out == 2) {
                        $paramList['out_time_end']   = strtotime($end_time . ' 23:59:59');
                    }
                }
            }
            if ($param && $name) {
                switch ($param) {
                    case 'car_number':
                        $paramList['car_number']      = $name;
                        break;
                    case 'position_num':
                        $paramList['position_num']      = $name;
                        break;
                    case 'car_user_name':
                        $paramList['car_user_name']      = $name;
                        break;
                    case 'car_user_phone':
                        $paramList['car_user_phone']      = $name;
                        break;
                }
            }
        }
        try {
            if($village_park_config['park_sys_type'] == 'D5'){
               D('House_village_car_access_record')->inOutCarExport($where,$village_name);
            }else{
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                D('House_village_parking_car')->passCarExport($paramList);
//                D('House_village_park_coupons')->inOutCarExport($where, $village_name);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        die();
    }

    //停车统计导出
    public function park_cost_export()
    {
        $param = I('param');
        $name = I('title');
        $car_type = I('car_type');
        $pay_type = I('pay_type');
        $o_start_time = I('o_start_time');
        $o_end_time = I('o_end_time');
        if ($param && $name) {
            switch ($param) {
                case 'car_number':
                    $map['c.car_number'] = ['like', '%' . trim($name) . '%'];
                    break;
                case 'car_user_name':
                    $map['c.car_user_name'] = ['like', '%' . trim($name) . '%'];
                    break;
                case 'car_user_phone':
                    $map['c.car_user_phone'] = ['like', '%' . trim($name) . '%'];
                    break;
            }
        }
        if ($car_type) {
            $map['o.car_type'] = $car_type;
        }
        if ($pay_type) {
            if ($pay_type == 2) {
                $map['o.pay_type'] = 0;
            } else {
                $map['o.pay_type'] = $pay_type;
            }
        }
        if ($o_start_time && $o_end_time) {
            $map['o.pay_time'][] = ['gt', strtotime($o_start_time)];
            $map['o.pay_time'][] = ['lt', strtotime($o_end_time . ' 23:59:59')];
        } else {
            if ($o_start_time) {
                $map['o.pay_time'] = ['gt', strtotime($o_start_time)];
            }
            if ($o_end_time) {
                $map['o.pay_time'] = ['lt', strtotime($o_end_time . ' 23:59:59')];
            }
        }

        $village_name = $this->village['village_name'];
        $map['o.village_id'] = $this->village_id;
        $map['o.order_type'] = 'park';
        $map['o.paid'] = 1;
        try {
            D('House_village_park_coupons')->parkCostExport($map, $village_name);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        die();
    }

    //黑名单列表
    public function pack_black_list()
    {
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = 'D3';
        $list = D('House_village_park_black')->where($where)->select();
        $this->assign('list', $list);
        $this->display();
    }


    //添加黑名单
    public function add_pack_black()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if ($_POST) {
            $data['user_name'] = $_POST['user_name'];
            $data['phone'] = $_POST['phone'];
            $data['car_number'] = $_POST['city_arr'] . $_POST['car_number'];
            $data['village_id'] = $this->village_id;
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['addTime'] = time();

            $data['updateTime'] = time();
            $res = M('House_village_park_black')->data($data)->add();
            if ($res) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $city_arr = array('京', '津', '冀', '晋', '蒙', '辽', '吉', '黑', '沪', '苏', '浙', '皖', '闽', '赣', '鲁', '豫', '鄂', '湘', '粤', '桂', '琼', '渝', '川', '贵', '云', '藏', '陕', '甘', '青', '宁', '新');
            $this->assign('city_arr', $city_arr);
            $this->display();
        }
    }


    //删除黑名单
    public function del_pack_black()
    {
        $id = I('id');
        if (!$id) {
            $this->error('参数异常');
        }
        //print_r($car_no);exit;
        $where['id'] = $id;
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = 'D3';
        $res = M('House_village_park_black')->where($where)->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }

    }


    public function park_rule_list()
    {
        $type = $_GET['type'];
        if (empty($type) || $type == 'temp') {
            $where['village_id'] = $this->village_id;
            $where['park_sys_type'] = 'D3';
            $where['status'] = array('IN', [1, 2]);
            $list = D('House_village_park_charge')->where($where)->select();
            // print_r(D('House_village_park_charge')->_sql());exit;
            if (!empty($list)) {
                foreach ($list as &$v) {
                    if (!empty($v['updateTime'])) {
                        $v['updateTime'] = date('Y-m-d H:i:s', $v['updateTime']);
                    }
                    if ($v['charge_type'] == 1) {
                        $v['charge_type'] = '二十四小时';
                    }
                }
            }
            //  print_r($list);exit;
            $this->assign('dataList', $list);
        }
        if ($type == 'mouth') {
            $where['village_id'] = $this->village_id;
            $where['park_sys_type'] = 'D3';
            $list = D('House_village_park')->where($where)->select();
            // print_r($list);exit;
            $this->assign('dataList', $list);
        }

        if ($type == 'free') {
            $where['village_id'] = $this->village_id;
            $where['park_sys_type'] = 'D3';
            $list = D('House_village_park_free')->where($where)->select();
            if (!empty($list)) {
                foreach ($list as &$v) {
                    if (!empty($v['first_name'])) {
                        $v['free_name'] = $v['first_name'];
                    } elseif (!empty($v['last_name'])) {
                        $v['free_name'] = $v['last_name'];
                    } else {
                        $v['free_name'] = $v['free_park'];
                    }
                    $v['park_type'] = $this->park_type[$v['park_type']];
                }
            }
            // print_r($list);exit;
            $this->assign('dataList', $list);
        }

        $this->display();
    }

    public function rule_add()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            $charge_set=[];
            foreach ($_POST['charge_set'][1] as $k=>$v){
                $charge_set[$k]['time']=$v;
            }
            foreach ($_POST['charge_set'][2] as $k=>$v){
                $charge_set[$k]['money']=$v;
            }
            $charge_set1=[];
            foreach ($charge_set as $vv){
                $charge_set1[]=$vv;
            }

            if (empty($_POST['charge_name'])) {
                $this->error('收费标准名称不能为空！');
            }
            if (empty($_POST['charge_type'])) {
                $this->error('收费类型不能为空！');
            }
            if (empty($_POST['charge_set'])) {
                $this->error('费用设置不能为空！');
            }

            $data['charge_name'] = $_POST['charge_name'];
            $data['charge_type'] = $_POST['charge_type'];
            $data['charge_set'] = serialize($charge_set1);
            $data['free_time'] = $_POST['free_time'];
            $data['max_charge_money'] = $_POST['max_charge_money'];
            $data['first_free_time'] = $_POST['first_free_time'];
            $data['first_charge_money'] = $_POST['first_charge_money'];
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['status'] = 1;
            $data['updateTime'] = time();
            $data['addTime'] = time();
            $res = M('House_village_park_charge')->data($data)->add();
            if ($res) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function rule_edit()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            if (empty($_POST['charge_name'])) {
                $this->error('收费标准名称不能为空！');
            }
            if (empty($_POST['charge_type'])) {
                $this->error('收费类型不能为空！');
            }
            if (empty($_POST['charge_set'])) {
                $this->error('费用设置不能为空！');
            }
            $charge_set=[];
            foreach ($_POST['charge_set'][1] as $k=>$v){
                $charge_set[$k]['time']=$v;
            }
            foreach ($_POST['charge_set'][2] as $k=>$v){
                $charge_set[$k]['money']=$v;
            }
            $charge_set1=[];
            foreach ($charge_set as $vv){
                $charge_set1[]=$vv;
            }
            $data['charge_name'] = $_POST['charge_name'];
            $data['charge_type'] = $_POST['charge_type'];
            $data['charge_set'] = serialize($charge_set1);
            $data['free_time'] = $_POST['free_time'];
            $data['max_charge_money'] = $_POST['max_charge_money'];
            $data['first_free_time'] = $_POST['first_free_time'];
            $data['first_charge_money'] = $_POST['first_charge_money'];
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['updateTime'] = time();
            $id = $_POST['id'];
            $res = M('House_village_park_charge')->where(array('id' => $id))->data($data)->save();
            if ($res) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        } else {
            $id = $_GET['id'];
            $charge_info = M('House_village_park_charge')->where(array('id' => $id))->find();
            if (!empty($charge_info)) {
                $charge_info['charge_set'] = unserialize($charge_info['charge_set']);
                if (count($charge_info['charge_set'])>1){
                    $charge_info['charge_set1']=$charge_info['charge_set'];
                    unset($charge_info['charge_set1'][0]);
                }else{
                    $charge_info['charge_set1']=[];
                }
                $charge_set1=[];
                if (!empty($charge_info['charge_set1'])){
                    foreach ($charge_info['charge_set1'] as $vv){
                        $charge_set1[]=$vv;
                    }
                }
                $charge_info['charge_set1']=$charge_set1;
                $charge_info['updateTime'] = date('Y-m-d H:i:s', $charge_info['updateTime']);
            }
           //  print_r($charge_info['charge_set1']);exit;
            $this->assign('charge_info', $charge_info);
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function del_rule()
    {
        $id = I('id');
        if (!$id) {
            $this->error('参数异常');
        }
        //print_r($car_no);exit;
        $where['id'] = $id;
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = 'D3';
        $res = M('House_village_park_charge')->where($where)->save(['status' => 4]);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }

    }

    public function rule_mouth_add()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            //  print_r($_POST);exit;
            if (empty($_POST['park_month_name'])) {
                $this->error('收费标准名称不能为空！');
            }
            if (empty($_POST['park_month_num']) && empty($_POST['diy_park_month_num'])) {
                $this->error('停车收费周期不能为空！');
            }
            if (empty($_POST['park_month_price'])) {
                $this->error('停车缴费费用不能为空！');
            }
            if (empty($_POST['status'])) {
                $this->error('状态不能为空！');
            }
            $data['park_month_name'] = $_POST['park_month_name'];
            $data['park_month_price'] = $_POST['park_month_price'];
            if (empty($_POST['park_month_num'])) {
                $data['park_month_num'] = $_POST['diy_park_month_num'];
            } else {
                $data['park_month_num'] = $_POST['park_month_num'];
            }
            if (empty($_POST['presented_park_month_num'])) {
                $data['presented_park_month_num'] = $_POST['diy_presented_park_month_num'];
            } else {
                $data['presented_park_month_num'] = $_POST['presented_park_month_num'];
            }
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['status'] = $_POST['status'];
            $data['park_month_id'] = $_POST['park_month_id'];
            $data['add_time'] = time();
            $res = M('House_village_park')->data($data)->add();
            if ($res) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $park_month_type = M('House_village_park_month')->where(array('village_id' => $this->village_id,'park_cycle_type'=>1))->select();
            $this->assign('park_month_type', $park_month_type);
            // print_r($park_month_type);die;
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function rule_mouth_edit()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            if (empty($_POST['park_month_name'])) {
                $this->error('收费标准名称不能为空！');
            }
            if (empty($_POST['park_month_num']) && empty($_POST['diy_park_month_num'])) {
                $this->error('停车收费周期不能为空！');
            }
            if (empty($_POST['park_month_price'])) {
                $this->error('停车缴费费用不能为空！');
            }
            if (empty($_POST['status'])) {
                $this->error('状态不能为空！');
            }
            $data['park_month_name'] = $_POST['park_month_name'];
            $data['park_month_price'] = $_POST['park_month_price'];
            if (empty($_POST['park_month_num'])) {
                $data['park_month_num'] = $_POST['diy_park_month_num'];
            } else {
                $data['park_month_num'] = $_POST['park_month_num'];
            }
            if (empty($_POST['presented_park_month_num'])) {
                $data['presented_park_month_num'] = $_POST['diy_presented_park_month_num'];
            } else {
                $data['presented_park_month_num'] = $_POST['presented_park_month_num'];
            }
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['status'] = $_POST['status'];
            $data['park_month_id'] = $_POST['park_month_id'];
            $data['add_time'] = time();
            $id = $_POST['id'];
            $res = M('House_village_park')->where(array('id' => $id))->data($data)->save();
            if ($res) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        } else {
            $id = $_GET['id'];
            $charge_info = M('House_village_park')->where(array('id' => $id))->find();
            $this->assign('detail', $charge_info);
            $park_month_type = M('House_village_park_month')->where(array('village_id' => $this->village_id,'park_cycle_type'=>1))->select();
            $this->assign('park_month_type', $park_month_type);
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function del_mouth_rule()
    {
        $id = I('id');
        if (!$id) {
            $this->error('参数异常');
        }
        //print_r($car_no);exit;
        $where['id'] = $id;
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = 'D3';
        $res = M('House_village_park')->where($where)->save(['status' => 4]);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }

    }

    public function free_add()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {

            $data['park_type'] = $_POST['park_type'];
            $data['first_name'] = $_POST['first_name'];
            $data['last_name'] = $_POST['last_name'];
            $data['free_park'] = $_POST['free_park'];
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['addTime'] = time();
            $data['updateTime'] = time();
            $res = M('House_village_park_free')->data($data)->add();
            if ($res) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function free_edit()
    {
        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if (IS_POST) {
            $data['park_type'] = $_POST['park_type'];
            $data['first_name'] = $_POST['first_name'];
            $data['last_name'] = $_POST['last_name'];
            $data['free_park'] = $_POST['free_park'];
            $data['village_id'] = $_SESSION['house']['village_id'];
            $data['park_sys_type'] = $village_park_config['park_sys_type'];
            $data['updateTime'] = time();
            $id = $_POST['id'];
            $res = M('House_village_park_free')->where(array('id' => $id))->data($data)->save();
            //   print_r(M('House_village_park_free')->_sql());exit;
            if ($res) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        } else {
            $id = $_GET['id'];
            $charge_info = M('House_village_park_free')->where(array('id' => $id))->find();
            $this->assign('detail', $charge_info);
            $this->assign('village_park_config', $village_park_config);
            $this->display();
        }
    }

    public function del_free()
    {
        $id = I('id');
        if (!$id) {
            $this->error('参数异常');
        }
        //print_r($car_no);exit;
        $where['id'] = $id;
        $where['village_id'] = $this->village_id;
        $where['park_sys_type'] = 'D3';
        $res = M('House_village_park_free')->where($where)->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }

    }

    //临时停车记录
    public function village_visitor_tmp_parking()
    {
        /*
        if (!in_array(216, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        */
        $begin_time = !empty($_GET['begin_time']) ? trim($_GET['begin_time']):'';
        $begin_time= !empty($begin_time) ? strtotime($begin_time):0;
        $end_time = !empty($_GET['end_time']) ? trim($_GET['end_time']):'';
        $end_time= !empty($end_time) ? strtotime($end_time.' 23:59:59'):0;
        $database_house_village_visitor = D('House_village_visitor');
        $village_id =  $this->house_session['village_id'];
        $_string='car_id!="" AND car_id IS NOT NULL ';
        if($begin_time>0 && ($end_time<=0)){
            $_string .=' AND add_time>='.$begin_time;
        }elseif ($end_time>0 && ($begin_time<=0)){
            $_string .=' AND add_time<='.$end_time;
        }elseif ($end_time>0 && $begin_time>0){
            $_string .=' AND add_time>='.$begin_time.' AND add_time<='.$end_time;
        }
        $whereArr=array('owner_uid'=>0,'is_car'=>1,'owner_bind_id'=>0,'village_id'=>$village_id,'_string'=>$_string);

        $list = $database_house_village_visitor->house_village_visitor_page_list($whereArr);
        //print_r($list);
        if (!$list) {
            $this->error('数据处理有误！');
        } else {
            $this->assign('list', $list['list']);
            $this->assign('visitor_type', $database_house_village_visitor->visitor_type);
        }

        $this->display();
    }

    //d5 生成唯一车场id
    public function get_d6_comid(){
        $house_village_park_config=M('house_village_park_config');
        $config=$house_village_park_config->where(['village_id'=>$this->village_id])->field('id,village_id,d6_comid')->find();
        if(empty($config['d6_comid'])){
            $d6_comid=createRandomStr(8,false,true);
            $house_village_park_config->where(['id' => $config['id']])->data(['d6_comid' => $d6_comid])->save();
        }else{
            $d6_comid=$config['d6_comid'];
        }
        $this->success('操作成功', '',['status'=>1,'data'=>$d6_comid]);
    }
    
    public function parkPlateResultLogList()
    {
        $find_type = isset($_GET['find_type']) ? intval($_GET['find_type']) : 0;
        $find_value = isset($_GET['find_value']) ? trim($_GET['find_value']) : '';
        $channel_id = '';
        $car_number = '';
        if ($find_type == 1 && $find_value) {
            $channel_id = $find_value;
        }
        if ($find_type == 2 && $find_value) {
            $car_number = $find_value;
        }
        
        $village_id = $this->house_session['village_id'];
        $db_park_plateresult_log = M('Park_plateresult_log');
        $db_park_passage = M('Park_passage');
        $wherePark = [];
        $wherePassage = [];
        $wherePassage['village_id'] = $village_id;
        $deviceNumbers = $db_park_passage->where($wherePassage)->getField('device_number', true);
        if ($channel_id) {
            $whereChannel = [];
            $whereChannel['village_id'] = $village_id;
            $whereChannel['device_number'] = $channel_id;
            $passageInfo = $db_park_passage->where($whereChannel)->find();
            if (empty($passageInfo)) {
                $channel_id = '';
            }
            $wherePark['channel_id'] = $channel_id;
        } else {
            $wherePark['channel_id'] = ['in', $deviceNumbers];
        }
        if ($car_number) {
            $wherePark['car_number'] = ['LIKE', '%' . $car_number . '%'];
        }
        
        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        if ($begin_time && !$end_time) {
            $wherePark['add_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $wherePark['add_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $wherePark['add_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }
        $pageSize = 100;
        import('@.ORG.merchant_page');
        $count = $db_park_plateresult_log->where($wherePark)->count();
        $p = new Page($count, $pageSize, 'page');
        $list = $db_park_plateresult_log->where($wherePark)->order('add_time DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        foreach ($list as &$item) {
            $add_time = $item['add_time'];
            $ymdhis = date('Ymdhis', $add_time);
            $rand_num = date('Ymd', $add_time);
            $serialno = $item['channel_id'];
            $car_number = $item['car_number'];
            $bigImage = "/upload/park_log/{$rand_num}/{$serialno}/{$ymdhis}_image_big_{$car_number}.jpg";
            $smallImage = "/upload/park_log/{$rand_num}/{$serialno}/{$ymdhis}_image_small_{$car_number}.jpg";
            $item['bigImage'] = replace_file_domain($bigImage);
            $item['smallImage'] = replace_file_domain($smallImage);
            $item['add_time_text'] = date('Y-m-d H:i:s', $add_time);
        }
        fdump_api($list, '$list');
        $data = [];
        $data['totalPage'] = ceil($count / $pageSize);
        $data['count'] = $count;
        $data['pagebar'] = $p->show();
        $data['list'] = $list;
        $this->assign('data', $data);
        $this->display();
    }

    public function repair_position_parking_car(){
        $house_village_parking_carDb= M('House_village_parking_car');
        $house_village_parking_positionDb= M('House_village_parking_position');
        //	SELECT count(sud.car_position_id) as uuu,sud.village_id,sud.car_position_id FROM pigcms_house_village_parking_car as sud where sud.car_id>0 AND sud.car_position_id group by sud.car_position_id having uuu>1
        $mode = new Model();
        $sql = "SELECT count(sud.car_position_id) as uuu,sud.village_id,sud.car_position_id FROM ".C('DB_PREFIX')."house_village_parking_car as sud where sud.car_id>0 AND sud.car_position_id>0 group by sud.car_position_id having uuu>1";
        echo $sql;
        exit();
        $res = $mode->query($sql);
        if($res){
            foreach ($res as $cvv){
                $parking_position=$house_village_parking_positionDb->where(array('position_id'=>$cvv['car_position_id']))->find();
                if($parking_position){
                    $house_village_parking_carDb->where(array('car_position_id'=>$cvv['car_position_id'],'village_id'=>array('neq',$parking_position['village_id'])))->data(array('car_position_id'=>0))->save();
                    $whereArr=array('village_id'=>$parking_position['village_id'],'car_position_id'=>$cvv['car_position_id']);
                    $parking_car=$house_village_parking_carDb->where($whereArr)->order('end_time desc')->find();
                    if($parking_car && $parking_car['end_time']>0){
                        $house_village_parking_positionDb->where(array('position_id'=>$cvv['car_position_id']))->data(array('end_time'=>$parking_car['end_time']))->save();
                        $house_village_parking_carDb->where($whereArr)->data(array('end_time'=>$parking_car['end_time']))->save();
                        echo $cvv['car_position_id'].'<br/>';
                    }
                }else{
                    //车位已被删除了
                    $house_village_parking_carDb->where(array('car_position_id'=>$cvv['car_position_id']))->data(array('car_position_id'=>0))->save();
                }

            }
        }

    }
}
