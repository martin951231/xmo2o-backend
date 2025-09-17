<?php


use app\community\model\db\HouseVillagePileRefundOrder;

class ChargingAction extends BaseAction
{
    public $device_type = [
        0 => array(
            'device_type' => 2,
            'name' => 'A1智能-充电桩'
        ),
        1 => array(

            'device_type' => 1,
            'name' => '驴充充-充电桩'
        ),
        2 => array(
            'device_type' => 21,
            'name' => '艾特充-充电桩'
        ),
    ];//默认定制充电桩类型为D2

    //艾特充-充电桩
    private $userId = 237;
    private $url = 'http://ap3204.haokuaichong.cn/outeruser';
    private $projectUser = '朱宝娣';
    private $projectPhone = '15055152821';

    private $http = '';


    public function __construct()
    {
        parent::__construct();
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
    }

    /**
     * 智能充电桩列表
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_list()
    {
        $village_id = $this->house_session['village_id'];
        // $res = D('House_village_pile_equipment')->getList(['village_id' => $village_id, 'is_del' => 1, 'type' => 2]);
        $where = ['village_id' => $village_id, 'is_del' => 1,'type'=>array('in', array(2,21))];
        if($_GET['find_value'] && $_GET['find_type']==1){
            $where['equipment_name'] = ['LIKE', '%' . trim($_GET['find_value']) . '%'];
        }
        if($_GET['find_value'] && $_GET['find_type']==2){
            $where['equipment_sn'] = $_GET['find_value'];
        }
        if($_GET['find_value'] && $_GET['find_type']==3){
            $where['equipment_serial'] = $_GET['find_value'];
        }
        $res = D('House_village_pile_equipment')->getList($where);
        if ($res['list']) {
            $base_url = D('House_village')->base_url;
            $site_url = C('config.site_url');

            $time = time() - 300;
            foreach ($res['list'] as $k => $v) {
                $url = $site_url . $base_url . 'pages/village/smartCharge/smartCharge?type=' . $v['type'] . '&qcodeNum=' . $v['equipment_serial'];
                $img_url = $site_url . U('get_own_qrcode', array('qrCon' => urlencode(htmlspecialchars_decode($url))));
                $res['list'][$k]['qrcode'] = $img_url;
                if (isset($v['last_heart_time']) && $v['last_heart_time'] < $time) {
                    $res['list'][$k]['statusHeart'] = 44;
                    if ($v['last_heart_time']<=1) {
                        $res['list'][$k]['statusHeartTime'] = '暂无心跳';
                    } else {
                        $res['list'][$k]['statusHeartTime'] = date('Y-m-d H:i:s',$v['last_heart_time']);
                    }
                } elseif (isset($v['last_heart_time']) && $v['last_heart_time']>1) {
                    $res['list'][$k]['statusHeart'] = 1;
                    $res['list'][$k]['statusHeartTime'] = date('Y-m-d H:i:s',$v['last_heart_time']);
                }
                $res['list'][$k]['last_heart_time'] = (isset($v['last_heart_time']) && $v['last_heart_time']>1) ? date('Y-m-d H:i:s',$v['last_heart_time']) : '--';
            }
        }
        $this->assign('pagebar', $res['pagebar']);
        $this->assign('list', $res['list']);
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
     * 新增、编辑智能充电桩
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_add()
    {
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $data['village_id'] = $village_id;
            $data['equipment_name'] = trim($_POST['equipment_name']);
            $data['equipment_sn'] = trim($_POST['equipment_sn']);
            $data['equipment_serial'] = trim($_POST['equipment_serial']);
            $data['socket_num'] = isset($_POST['socket_num']) ? intval($_POST['socket_num']) : 0;
            $data['work_type'] = $_POST['work_type'];
            $data['add_time'] = time();
            $data['type'] = $_POST['device_type'];
            if ($_POST['lng_lat']) {
                $lng_lat = explode(',', $_POST['lng_lat']);
                $data['lng'] = $lng_lat[0];
                $data['lat'] = $lng_lat[1];
            }
            if (empty($_POST['equipment_sn']) || empty($data['socket_num']) || empty($_POST['equipment_serial']))
                $this->error('请填写必填参数', U('equipment_add'));
            if ($data['type'] == 21) {
                $data['equipment_num'] = strtoupper($data['equipment_num']);
            } else {
                $data['equipment_num'] = $this->NumToHex($data['equipment_sn'], 16);
            }
            if (M('House_village_pile_equipment')->where(['is_del' => 1, 'type' => 2, 'equipment_num' => $data['equipment_num']])->find()) {
                $this->error('设备唯一编码重复', U('equipment_add'));
            }
            if (M('House_village_pile_equipment')->where(['is_del' => 1, 'type' => 2, 'equipment_serial' => $data['equipment_serial']])->find()) {
                $this->error('	设备序列号重复', U('equipment_add'));
            }
            $port_status = [];
            for($i = 0;$i<$data['socket_num'];$i++){
                $port_status[] = 0;
            }
            $data['port_status'] = json_encode($port_status);
            $res = M('House_village_pile_equipment')->add($data);
            if ($res) {
                if ($data['type'] == 2) {
                    $set_data = [
                        'cmd' => 'setWorkType',
                        'type' => $_POST['work_type'],
                        'equipment_num' => $data['equipment_num'],
                        'time' => time(),
                    ];
                    $command_data = [
                        'command' => json_encode($set_data),
                        'type' => 1,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    $set_data2 = [
                        'cmd'=>'updateTime',
                        'equipment_num'=>$data['equipment_num'],
                        'time' => time(),
                    ];
                    $command_data2=[
                        'command'=>json_encode($set_data2),
                        'type'=>1,
                        'status'=>1,
                        'addtime'=>time(),
                    ];
                    M('House_village_pile_command')->data($command_data2)->add();
                    M('House_village_pile_command')->data($command_data)->add();
                } 
                elseif ($data['type'] == 21) {
                    $village_pile = M('house_village_pile')->where(['village_id' => $village_id])->find();
                    if (empty($village_pile)) {
                        $projectId = $this->pile_village($village_id);
                    } else {
                        $projectId = $village_pile['pile_village_id'];
                    }
                    $pointAdd_data = http_build_query([
                        'projectId' => $projectId,
                        'userId' => $this->userId,
                        'name' => $data['equipment_name'],
                        'type' => 0,
                        'word' => 0,
                    ]);
                    $add_device_url = $this->url . '/projectManager/pointAdd';
                    $res_pointAdd = $this->http->curlGet($add_device_url . '?' . $pointAdd_data);
                    if (!empty($res_pointAdd)) {
                        $res_pointAdd = json_decode($res_pointAdd, true);
                        if ($res_pointAdd['code'] == 200) {
                            $save_data = ['pointId' => $res_pointAdd['data']['id']];
                            $add_res = M('House_village_pile_equipment')->where(['id' => $res])->save($save_data);
                            $add_url = $this->url . '/api/charge/scanCode?device=' . $data['equipment_num'] . '&areaId=' . $res_pointAdd['data']['id'];
                            $res1 = $this->http->curlGet($add_url);
                        }
                    }
                }

                $this->success('保存成功', U('equipment_list'));
            } else {
                $this->error($res['error'], U('equipment_list'));
            }
        } else {
            $this->assign('device_type', $this->device_type);
            $this->display();
        }
    }

    /**
     * 将数字转换成16进制，长度不够的补0
     * @param $num
     * @param $len
     * @return string
     * @author lijie
     * @date_time 2021/05/26
     */
    public function NumToHex($num, $len)
    {
        $num_hex = base_convert($num, 10, 16);
        if (strlen($num_hex) != $len) {
            $add = '';
            for ($i = 0; $i < $len - strlen($num_hex); $i++) {
                $add .= '0';
            }
            $num_hex = $add . $num_hex;
        }
        return $num_hex;
    }

    /**
     * 编辑充电桩
     */
    public function equipment_edit()
    {
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $data['village_id'] = $village_id;
            $data['equipment_name'] = $_POST['equipment_name'];
            $data['equipment_sn'] = trim($_POST['equipment_sn']);
            if (isset($_POST['equipment_num'])&&!empty($_POST['equipment_num'])){
                $data['equipment_num'] = $_POST['equipment_num'];
            }
            $data['equipment_serial'] = trim($_POST['equipment_serial']);
            $data['add_time'] = time();
            $data['work_type'] = $_POST['work_type'];
            $data['type'] = $_POST['device_type'];
            if ($_POST['lng_lat']) {
                $lng_lat = explode(',', $_POST['lng_lat']);
                $data['lng'] = $lng_lat[0];
                $data['lat'] = $lng_lat[1];
            }
            $equipment_info = M('House_village_pile_equipment')->where(['id' => $_POST['id']])->find();
            if(empty($_POST['equipment_sn'])  || empty($_POST['equipment_serial'])) {
                $this->error('请填写必填参数',U('equipment_edit'));
            }
            $data['equipment_num'] = $this->NumToHex($data['equipment_sn'],16);
            $res = M('House_village_pile_equipment')->where(['id' => $_POST['id']])->save($data);
            $set_data2 = [
                'cmd'=>'updateTime',
                'equipment_num'=>$data['equipment_num'],
                'time' => time(),
            ];
            $command_data2=[
                'command'=>json_encode($set_data2),
                'type'=>1,
                'status'=>1,
                'addtime'=>time(),
            ];
            M('House_village_pile_command')->data($command_data2)->add();
            if ($res) {
                if ($equipment_info['type'] == 2) {
                    $set_data = [
                        'cmd' => 'setWorkType',
                        'type' => $_POST['work_type'],
                        'equipment_num' => $data['equipment_num'],
                        'time' => time(),
                    ];
                    $command_data = [
                        'command' => json_encode($set_data),
                        'type' => 1,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    M('House_village_pile_command')->data($command_data)->add();
                } 
                elseif ($equipment_info['type'] == 21 && $equipment_info['equipment_name'] != $data['equipment_name']) {
                    $this->updatePointName($data['equipment_name'], $equipment_info['pointId']);
                }

                $this->success('保存成功', U('equipment_list'));
            } else {
                $this->error($res['error'], U('equipment_list'));
            }
        } else {
            $id = $_GET['id'] ? $_GET['id'] : 0;
            if ($id) {
                $info = M('House_village_pile_equipment')->where(['id' => $id])->find();
                $this->assign('info', $info);
            }
            $this->assign('id', $id);
            $this->assign('device_type', $this->device_type);
            $this->display();
        }
    }

    /**
     * 删除智能充电桩
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_del()
    {
        $id = $_GET['id'] ? $_GET['id'] : 0;
        if (!$id) {
            $this->error('删除失败', U('equipment_list'));
        }
        $res = M('House_village_pile_equipment')->where(['id' => $id])->find();
        if (M('House_village_pile_equipment')->where(['id' => $id])->save(['is_del' => 2,'equipment_num' => $res['equipment_num'].'_'.time()])) {
            if ($res && $res['type'] == 21) {
                $this->queDelete($res['equipment_num']);
            }
            $this->success('删除成功', U('equipment_list'));
        }
    }

    /**
     * 查看智能充电桩详情
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_info()
    {
        $id = $_GET['id'] ? $_GET['id'] : 0;
        if (!$id) {
            $this->error('查看失败', U('equipment_list'));
        }
        $info = M('House_village_pile_equipment')->where(['id' => $id])->find();
        /*if($info['status'] == 1)
            $info['status_txt'] = '在线';
        else
            $info['status_txt'] = '离线';*/
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 获取小区智能充电桩月卡列表
     * @author lijie
     * @date_time 2021/02/05
     */
    public function month_card_list()
    {
        $village_id = $this->house_session['village_id'];
        $count = M('House_village_pile_month_card')->where(['village_id' => $village_id, 'is_del' => 1, 'type' => 2])->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $list = M('House_village_pile_month_card')->where(['village_id' => $village_id, 'is_del' => 1, 'type' => 2])->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
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
            $data['type'] = 2;
            if ($_POST['id']) {
                $res = M('House_village_pile_month_card')->where(['id' => $_POST['id']])->save($data);
            } else {
                $res = M('House_village_pile_month_card')->add($data);
            }
            if ($res) {
                $this->success('保存成功', U('month_card_list'));
            }
        } else {
            $id = $_GET['id'] ? $_GET['id'] : 0;
            if ($id) {
                $info = M('House_village_pile_month_card')->where(['id' => $id])->find();
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
        $id = $_GET['id'] ? $_GET['id'] : 0;
        if (!$id) {
            $this->error('删除失败', U('month_card_list'));
        }
        if (M('House_village_pile_month_card')->where(['id' => $id])->save(['is_del' => 2])) {
            $this->success('删除成功', U('month_card_list'));
        }
    }

    /**
     * 充电桩属性列表
     * @author lijie
     * @date_time 2021/02/06
     */
    public function attribute_list()
    {
        $list = M('House_village_pile_attribute')->where(['type' => 2])->select();
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 设置充电桩默认值
     * @author lijie
     * @date_time 2021/02/06
     */
    public function get_default_value()
    {
        $id = $_POST['id'];
        if (M('House_village_pile_attribute')->where(['id' => $id])->getField('default_value')) {
            echo json_encode(['status' => 1]);
            exit;
        }
        switch ($id) {
            case 1:
                $arr = [['单价', '0~100', 1, '元/CNY'], ['时间', '0~10', 4, '小时/h'], ['电量', '0~100', 1, '度/kw.h']];
                break;
            case 2:
                $arr = [['标准功率', '0~8000', 220, '瓦/W']];
                break;
            case 3:
                $arr = [['功率变化阀值', '0~100', 15, '瓦/W'], ['最小上报间隔', '0~255', 255, '秒/sec']];
                break;
            case 4:
                $arr = [['极限功率', '10~3000', 990, '瓦/W']];
                break;
            case 5:
                $arr = [['最大充电时长', '64~86400', 43200, '秒/sec']];
                break;
            case 6:
                $arr = [['时长功率', '0~36000', 1800, '秒/sec'], ['功率限制', '0~100', 15, '瓦/W']];
                break;
            case 7:
                $arr = [['时长', '0~3600', 10, '秒/sec'], ['功率', '0~100', 1, '瓦/W']];
                break;
            case 8:
                $arr = [['最低温度', '-20~120', -10, '摄氏度/℃'], ['最高温度', '-20~120', 70, '摄氏度/℃']];
                break;
            default:
                $arr = [['单价', '0~100', 1, '元/CNY'], ['时间', '0~10', 4, '小时/h'], ['电量', '0~100', 1, '度/kw.h']];
        }
        M('House_village_pile_attribute')->where(['id' => $id])->save(['default_value' => serialize($arr)]);
        echo json_encode(['status' => 1]);
    }

    /**
     * 消费标准列表页
     * @author lijie
     * @date_time 2021/02/18
     */
    public function attribute_standard_list()
    {
        $attribute_id = $_GET['attribute_id'];
        $village_id = $this->house_session['village_id'];
        $list = M('House_village_pile_attribute_set')->where(['attribute_id' => $attribute_id, 'village_id' => $village_id, 'is_del' => 1])->select();
        foreach ($list as &$val){
            $content_arr = unserialize($val['standard_value']);
            $val['standard_content'] = '';
            foreach ($content_arr as $v){
                $val['standard_content'] .= $v[0].'：'.$v[2].$v[3].';';
            }
            if($val['standard_content']){
                $val['standard_content'] = rtrim($val['standard_content'],';');
            }
        }
        $this->assign('list', $list);
        $this->assign('attribute_id', $attribute_id);
        $this->display();
    }

    public function add_standard()
    {
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $id = $_POST['id'] ? $_POST['id'] : 0;
            $data['standard_name'] = $_POST['standard_name'];
            $data['standard_des'] = $_POST['standard_des'];
            $data['village_id'] = $village_id;
            $A = trim($_POST['A']);
            $A =$A ? round($A,2):0;
            $B = trim($_POST['B']);
            $C = trim($_POST['C']);
            $F = $_POST['F'] ? trim($_POST['F']):0;  //充电服务费
            $F =$F ? round($F,2):0;
            $data['attribute_id'] = $_POST['attribute_id'];
            $data['add_time'] = time();
            if ($_POST['attribute_id'] == 1) {
                $arr = [['单价', '0~500', $A, '元/CNY'], ['时间', '0~10', $B, '小时/h'], ['电量', '0~100', $C, '度/kw.h']];
                if ($A < 0 || $A > 500 || $B < 0 || $B > 10 || $C < 0 || $C > 100) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
                if($F>0){
                    $AF=$A+$F;
                    if($AF < 0 || $AF > 500){
                        echo json_encode(['status' => 2]);
                        exit;
                    }
                    $arr['ffee']=['服务费', '0~500', $F, '元/CNY'];
                }
            } elseif ($_POST['attribute_id'] == 2) {
                $arr = [['标准功率', '0~8000', $A, '瓦/W']];
                if ($A < 0 || $A > 8000) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            } elseif ($_POST['attribute_id'] == 3) {
                $arr = [['功率变化阀值', '0~100', $A, '瓦/W'], ['最小上报间隔', '0~255', $B, '秒/sec']];
                if ($A < 0 || $A > 100 || $B < 0 || $B > 255) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            } elseif ($_POST['attribute_id'] == 4) {
                $arr = [['极限功率', '10~3000', $A, '瓦/W']];
                if ($A < 10 || $A > 3000) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            } elseif ($_POST['attribute_id'] == 5) {
                $arr = [['最大充电时长', '64~86400', $A, '秒/sec']];
                if ($A < 64 || $A > 86400) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            } elseif ($_POST['attribute_id'] == 6) {
                $arr = [['时长限制', '0~36000', $A, '秒/sec'], ['功率限制', '0~100', $B, '瓦/W']];
                if ($A < 0 || $A > 36000 || $B < 0 || $B > 100) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            } elseif ($_POST['attribute_id'] == 7) {
                $arr = [['时长', '0~3600', $A, '秒/sec'], ['功率', '0~100', $B, '瓦/W']];
                if ($A < 0 || $A > 3600 || $B < 0 || $B > 100) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            } else {
                $arr = [['最低温度', '-20~120', $A, '摄氏度/℃'], ['最高温度', '-20~120', $B, '摄氏度/℃']];
                if ($A < -20 || $A > 120 || $B < -20 || $B > 120) {
                    echo json_encode(['status' => 2]);
                    exit;
                }
            }
            $data['standard_value'] = serialize($arr);
            $e_name = '';
            if ($id) {
                $res = M('House_village_pile_attribute_set')->where(['id' => $id])->save($data);
                $list = M('House_village_pile_equipment_attribute')->join('AS a left join ' . C('DB_PREFIX') . 'house_village_pile_equipment e on e.id =a.equipment_id')->field('e.*,a.attribute_id,a.attribute_standard_id')->where(['a.attribute_id' => $_POST['attribute_id'], 'e.village_id' => $village_id, 'a.is_del' => 1])->select();
                if (!empty($list) && !empty($res)) {
                    foreach ($list as $val) {
                        if ($val['type'] == 21 && $_POST['attribute_id'] == 1) {
                            $this->pointEdit($val['id']);
                        }
                        if($val['type'] == 2){
                            $e_name .= $val['equipment_name'].'、';
                            $this->update_standard($val['id'],$val['attribute_standard_id'],$val['attribute_id']);
                        }
                    }
                }
            } else {
                $res = M('House_village_pile_attribute_set')->add($data);
            }

            if ($res) {
                if($e_name){
                    $e_name = rtrim($e_name,'、');
                }
                echo json_encode(['status' => 1,'e_name'=>$e_name]);
                exit;
            } else {
                echo json_encode(['status' => 2]);
                exit;
            }
        } else {
            $attribute_id = $_GET['attribute_id'];
            $id = $_GET['id'];
            $info = M('House_village_pile_attribute_set')->where(['id' => $id])->find();
            $info['standard_value'] = unserialize($info['standard_value']);
            $this->assign('attribute_id', $attribute_id);
            $this->assign('info', $info);
            if (!$id)
                $this->display();
            else
                $this->display('edit_standard');
        }
    }

    /**
     * 更新设备消费标准并下发给设备
     * @author lijie
     * @date_time 2022/01/20
     * @param int $equipment_id
     * @param int $attribute_standard_id
     * @param int $attribute_id
     */
    public function update_standard($equipment_id=0,$attribute_standard_id=0,$attribute_id=0)
    {
        $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id])->find();
        $attribute_info = M('House_village_pile_attribute_set')->where(['id' => $attribute_standard_id])->field('standard_value')->find();
        $value = M('House_village_pile_attribute')->where(['id' => $attribute_id])->getField('value');
        $standard_value=unserialize($attribute_info['standard_value']);
        if(isset($standard_value['ffee']) && $standard_value['ffee']){
            $dfee= $standard_value[0][2]+$standard_value['ffee'][2];  //充电费用 单价加上服务费 
            $dfee=round($dfee,2);
            $standard_value[0][2]=$dfee; 
            $attribute_info['standard_value']=serialize($standard_value);
        }

        $data = [
            'cmd' => 'bindAttribute',
            'equipment_num' => $equipment_info['equipment_num'],
            'attribute_id' => $value,
            'attribute_info' => $attribute_info['standard_value'],
            'time' => time(),
        ];

        $command_data = [
            'command' => json_encode($data,JSON_UNESCAPED_UNICODE),
            'type' => 1,
            'status' => 1,
            'addtime' => time(),
        ];
        M('House_village_pile_command')->data($command_data)->add();

        $data = [
            'cmd' => 'pileQueryRule',
            'equipment_num' => $equipment_info['equipment_num'],
            'time' => time(),
        ];
        $command_data = [
            'command' => json_encode($data,JSON_UNESCAPED_UNICODE),
            'type' => 1,
            'status' => 1,
            'addtime' => time(),
        ];
        M('House_village_pile_command')->data($command_data)->add();
    }

    /**
     * 属性标准是否开启开关
     * @author lijie
     * @date_time 2021/02/18
     */
    public function change_status()
    {
        $id = $_POST['id'];
        $type = $_POST['type'];
        if ($type == 'open') {
            $res = M('House_village_pile_attribute_set')->where(['id' => $id])->save(['status' => 1]);
        } else {
            $res = M('House_village_pile_attribute_set')->where(['id' => $id])->save(['status' => 2]);
        }
        if ($res)
            echo json_encode(['status' => 1]);
        else
            echo json_encode(['status' => 2]);
    }

    /**
     * 删除属性标准
     * @author lijie
     * @date_time 2021/02/18
     */
    public function del_standard()
    {
        $id = $_GET['id'];
        $attribute_id = $_GET['attribute_id'];
        if (M('House_village_pile_attribute_set')->where(['id' => $id])->save(['is_del' => 2])) {
            $this->success('删除成功', U('attribute_standard_list', array('attribute_id' => $attribute_id)));
        }
    }

    /**
     * 绑定设备列表
     * @author lijie
     * @date_time 2021/02/19
     */
    public function bind_standard()
    {
        $attribute_id = $_GET['attribute_id'];
        $attribute_standard_id = $_GET['id'];
        $village_id = $this->house_session['village_id'];
        $equipment_list = M('House_village_pile_equipment')->field('id,equipment_name,equipment_num,equipment_serial')->where(['village_id' => $village_id, 'is_del' => 1])->select();
        foreach ($equipment_list as $k => $v) {
            $equipment_list[$k]['attribute_standard_id'] = $attribute_standard_id;
            $equipment_list[$k]['attribute_id'] = $attribute_id;
            $res = M('House_village_pile_equipment_attribute')->where(['equipment_id' => $v['id'], 'attribute_id' => $attribute_id, 'is_del' => 1])->find();
            if (empty($res))
                $equipment_list[$k]['is_bind'] = 1;
            elseif ($res['attribute_standard_id'] == $attribute_standard_id)
                $equipment_list[$k]['is_bind'] = 2;
            else
                $equipment_list[$k]['is_bind'] = 3;
        }
        $this->assign('equipment_list', $equipment_list);
        $this->display();
    }

    /**
     * 属性绑定设备
     * @author lijie
     * @date_time 2021/02/19
     */
    public function ajax_bang_attribute()
    {
        $equipment_id = $_POST['equipment_id'];
        $attribute_standard_id = $_POST['attribute_standard_id'];
        $attribute_id = $_POST['attribute_id'];
        $arr_equipment_id = explode(',', $equipment_id);
        foreach ($arr_equipment_id as $k => $v) {
            $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id])->find();
            if (true) {
                $data['equipment_id'] = $v;
                $data['attribute_standard_id'] = $attribute_standard_id;
                $data['attribute_id'] = $attribute_id;
                $data['add_time'] = time();
                M('House_village_pile_equipment_attribute')->where(['equipment_id' => $v, 'attribute_id' => $attribute_id])->data(['is_del' => 2])->save();
                M('House_village_pile_equipment_attribute')->add($data);
                $attribute_info = M('House_village_pile_attribute_set')->where(['id' => $attribute_standard_id])->field('standard_value')->find();
                $value = M('House_village_pile_attribute')->where(['id' => $attribute_id])->getField('value');
                if ($equipment_info['type'] == 2) {
                    $data = [
                        'cmd' => 'bindAttribute',
                        'equipment_num' => $equipment_info['equipment_num'],
                        'attribute_id' => $value,
                        'attribute_info' => $attribute_info['standard_value'],
                        'time' => time(),
                    ];
                    $command_data = [
                        'command' => json_encode($data),
                        'type' => 1,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    M('House_village_pile_command')->data($command_data)->add();
                    $data = [
                        'cmd' => 'pileQueryRule',
                        'equipment_num' => $equipment_info['equipment_num'],
                        'time' => time(),
                    ];
                    $command_data = [
                        'command' => json_encode($data),
                        'type' => 1,
                        'status' => 1,
                        'addtime' => time(),
                    ];
                    M('House_village_pile_command')->data($command_data)->add();
                } elseif ($equipment_info['type'] == 21) {
                    $this->pointEdit($equipment_info['id']);
                }

            } else {
                echo json_encode(['status' => 0]);
                exit;
            }
        }
        echo json_encode(['status' => 1]);
    }

    /**
     * 属性解绑设备
     * @author lijie
     * @date_time 2021/02/19
     */
    public function ajax_attribute_relieve()
    {
        $data['equipment_id'] = $_POST['equipment_id'];
        $data['attribute_id'] = $_POST['attribute_id'];
        M('House_village_pile_equipment_attribute')->where($data)->data(['is_del' => 2])->save();
        echo json_encode(['status' => 1]);
    }

    /**
     * 设备绑定的属性列表
     * @author lijie
     * @date_time 2021/04/24
     */
    public function e_attribute_list()
    {
        $equipment_id = $_GET['equipment_id'];
        if (!$equipment_id) {
            $this->error('缺少必传参数', U('equipment_list'));
        }
        $equipment_attribute_list = D('House_village_equipment_attribute')->get_equipment_attribute_list($equipment_id);
        $this->assign('info', $equipment_attribute_list);
        $this->display('e_attribute_list');
    }

    public function e_attribute_del()
    {
        $equipment_id = $_GET['equipment_id'];
        D('House_village_equipment_attribute')->delOne($equipment_id);
        echo json_encode(['status' => 1]);
    }

    /**
     * 充电桩详情
     * @author lijie
     * @date_time 2021/04/26
     */
    public function pile_detail()
    {
        $equipment_id = $_GET['equipment_id'];
        $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id])->find();
        $equipment_info['add_time_txt'] = date('Y-m-d H:i:s', $equipment_info['add_time']);
        $this->assign('info', $equipment_info);
        if ($equipment_info['type'] == 21) {
            $port_list = $this->outGetPorts($equipment_info['equipment_num']);
        } else {
            $port_list = json_decode($equipment_info['port_status'], true);
        }
        $this->assign('ports', $port_list);
        $a = $b = $c = 0;
        foreach ($port_list as $v) {
            if ($v == 0)
                $a += 1;
            elseif ($v == 1)
                $b += 1;
            else
                $c += 1;
        }
        $this->assign('a', $a);
        $this->assign('b', $b);
        $this->assign('c', $c);
        $this->assign('status', $port_list[0]);
        $this->display();
    }

    /**
     * 充电桩插座二维码
     */
    public function getNumCode()
    {
        $equipment_id = $_POST['equipment_id'];
        $socket = $_POST['socket'];
        $site_url = $this->config['site_url'];
        $url = get_base_url('pages/village/smartCharge/chargeNum?id=' . $equipment_id . '&socket=' . $socket);
        $img_url = $site_url . U('get_own_qrcode', array('qrCon' => urlencode(htmlspecialchars_decode($url)), 'down' => true));
        echo json_encode(['url' => $img_url, 'status' => 1]);
    }

    /**
     * 批量下载充电桩插座二维码
     */
    public function downloadCode()
    {
        $equipment_id = $_POST['equipment_id'];
        $equipment_info = M('House_village_pile_equipment')->where(['id' => $equipment_id])->find();
        $port_list = json_decode($equipment_info['port_status']);
        $site_url = $this->config['site_url'];
        $base_url = D('House_village')->base_url;
        foreach ($port_list as $k => $v) {
            $socket = $k + 1;
            $url = $site_url . $base_url . 'pages/village/smartCharge/chargeNum?id=' . $equipment_id . '&socket=' . $socket;
            $dir_path = './upload/temp/pileQrcode/' . $_POST['equipment_num'] . '/' . $_POST['equipment_num'];
            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0777, true);
            }
            $filePath = $dir_path . '/插座' . $socket . '.png';

            $this->get_own_qrcode($url, 7, true, $filePath);

        }
        if ($port_list) {
            $this->get_zip_originalsize('./upload/temp/pileQrcode/' . $_POST['equipment_num'] . '/', './upload/temp/pileQrcode/' . $_POST['equipment_num'] . '/');
        }
        echo json_encode(['msg' => '下载成功', 'status' => 1, 'url' => $site_url . '/upload/temp/pileQrcode/' . $_POST['equipment_num'] . '/' . $_POST['equipment_num'] . '.zip']);
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
        $pay_type = $_GET['pay_type'];
        $order_type = $_GET['order_type'];
        $where['o.village_id'] = $village_id;

        $where['o.type'] = array('in', [2,21]);
        $where['o.pay_time'] = array('gt', 100);
        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        if($pay_type){
            $where['o.pay_type'] = $pay_type;
        }
        if ($order_type) {
            $where['o.order_type'] = $order_type;
        }
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value']? trim($_GET['find_value']):'';
        if ($find_type == 1 && !empty($find_value)) {
            $where['u.nickname'] = $find_value;
        }
        if ($find_type == 2 && !empty($find_value)) {
            $where['u.phone'] = $find_value;
        }
        if ($find_type == 3 && !empty($find_value)) {
            $where['o.order_id'] = $find_value;
        }
        if ($find_type == 4 && !empty($find_value)) {
            $where['o.pay_orderid'] = $find_value;
        }
        if ($find_type == 5 && !empty($find_value)) {
            $where['o.third_paid_orderid'] = $find_value;
        }
        if($begin_time && empty($end_time)){
            $where['o.pay_time'] = array('egt',strtotime($begin_time));
        }elseif ($end_time && empty($begin_time)){
            $where['o.pay_time'] = array('elt',strtotime($end_time.' 23:59:59'));
        }elseif ($end_time && $begin_time){
            $where['o.pay_time'] = array('between',[strtotime($begin_time),strtotime($end_time.' 23:59:59')]);
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
        $this->assign('pagebar',$p->show());
        $total_money = 0.00;
        $total_use_ele = 0.00;
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach ($order_list as $k=>&$v){
            $total_money += ($v['use_money']-$v['refund_money']);
            $total_use_ele += $v['use_ele'];
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id'=>$v['equipment_id']])->getField('equipment_name');
            if($v['pay_type'] == 10){
                $order_list[$k]['pay_time_txt'] = '--';
                $order_list[$k]['nickname'] = substr($v['card_num'],22,10);
                $order_list[$k]['phone'] = '--';
            }
            else{
                $order_list[$k]['pay_time_txt'] =  date('Y-m-d H:i:s',$v['pay_time']);
            }
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time']/60);
            if($v['order_type'] == 1)
                $v['order_type'] = '消费订单';
            else
                $v['order_type'] = '月卡购买';
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
                case 5:
                    $v['pay_type'] = '充电桩余额支付';
                    break;
                case 10:
                    $v['pay_type'] = '本地余额卡支付';
                    break;
                default:
                    $v['pay_type'] = '余额支付';
            }
            if(isset($v['phone']) && $order_list[$k]['phone']){
                $order_list[$k]['phone']= $d_house_village->phone_desensitization_v($order_list[$k]['phone'],$village_id,$village_info);
            }
        }
        $where_total_money = $where;
        $where_total_money['pay_type'] = ['neq', 10];
        $total_money = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where_total_money)
            ->sum('o.use_money');
        $where_card = $where;
        $where_card['pay_type'] = 10;
        $card_total_money = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where_card)
            ->sum('o.use_money');
        $refund_money = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->sum('o.refund_money');
        $total_use_ele = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->sum('o.use_ele');
        $pile_charge_consume_point=D('Config_customization')->getPileChargeConsumePoint();
        $this->assign('pile_charge_consume_point',$pile_charge_consume_point);
        $this->assign('order_list',$order_list);
        $this->assign('total_money',$total_money);
        $this->assign('refund_money',$refund_money);
        $real_total_money=$total_money-$refund_money;
        $real_total_money=round($real_total_money,2);
        $this->assign('real_total_money',$real_total_money);
        $this->assign('card_total_money',$card_total_money);
        $this->assign('total_use_ele',$total_use_ele);
        $this->display();
    }

    public function order_export()
    {
        //业主-导出 权限
        if (!in_array(98, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_id = $this->house_session['village_id'];
        $where['o.village_id'] = $village_id;
        $where['o.type'] =  array('in', [2,21]);
        $where['o.pay_time'] = array('gt', 100);
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $order_type = $_GET['order_type'];
        $pay_type = $_GET['pay_type'];
        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
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
        if($begin_time && empty($end_time)){
            $where['o.pay_time'] = array('egt',strtotime($begin_time.' 00:00:00'));
        }elseif ($end_time && empty($begin_time)){
            $where['o.pay_time'] = array('elt',strtotime($end_time.' 23:59:59'));
        }elseif ($end_time && $begin_time){
            $where['o.pay_time'] = array('between',[strtotime($begin_time.' 00:00:00'),strtotime($end_time.' 23:59:59')]);
        }

        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->order('o.id DESC')
            ->field('o.*,u.nickname,u.phone')
            ->select();
        
        if (count($order_list) <= 0) {
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = '充电桩订单导出';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($order_list) / 1000);

        $pCoordinate_arr = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'I', 'J', 'K', 'L', 'M', 'N',
            'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
            'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
            'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM',
            'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ'
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
            '结束时功率',
            '业主姓名',
            '联系方式',
            '支付方式',
            '支付时间',
        ];
        $key_length = count($pValue_arr) - 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($order_list) . '个订单');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1 => $v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1] . '1', $v1);
            }

            if (!empty($order_list)) {
                $index = 2;

                $cell_list = range($pCoordinate_arr[0], $pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }

                foreach ($order_list as $value) {
                    $equipment_name = M('House_village_pile_equipment')->where(['id' => $value['equipment_id']])->getField('equipment_name');
                    $pay_time = date('Y-m-d H:i:s', $value['pay_time']);
                    if ($value['status'] == 1) {
                        $value['status'] = '已结束';
                    } else {
                        $value['status'] = '未结束';
                    }
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
                        case 5:
                            $pay_type = '充电桩余额支付';
                            break;
                        case 10:
                            $pay_type = '本地余额卡支付';
                            break;
                        default:
                            $pay_type = '余额支付';
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['order_no']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $equipment_name);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $order_type);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, getFormatNumber($value['continued_time'] / 60));
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['status']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['use_money']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['refund_money']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $value['use_ele']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, $value['end_power']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[9] . $index, $value['nickname']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[10] . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[11] . $index, $pay_type);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[12] . $index, $pay_time);
                    $index++;
                }
            }
            sleep(2);
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
    }

    /**
     * 修改充电桩状态
     * @author lijie
     * @date_time 2021/04/29
     */
    public function updateStatus()
    {
        $equipment_id = $_POST['equipment_id'];
        $status = $_POST['status'];
        $equipment_num = $_POST['equipment_num'];
        if ($status == 1){
            $action = 'recovery';
        } elseif ($status == 2){
            $action = 'stop';
        } elseif ($status == 3){
            $action = 'restart';
        }
        elseif($status == 4){
            $action = 'updateTime';
        }else{
            $action = 'stop';
        }
        $data = [
            'cmd' => $action,
            'equipment_num' => $equipment_num,
            'time' => time(),
        ];
        $command_data = [
            'command' => json_encode($data),
            'type' => 1,
            'status' => 1,
            'addtime' => time(),
        ];
        $res = M('House_village_pile_command')->data($command_data)->add();
        if ($res) {
            if($status != 4){
                if ($status == 1)
                    $update_status = 3;
                elseif ($status == 2)
                    $update_status = 4;
                else
                    $update_status = 5;
                M('House_village_pile_equipment')->where(['id' => $equipment_id])->data([
                    'status' => $update_status
                ])->save();
            }
            echo json_encode(['status' => 1, 'msg' => '操作成功']);
        } else {
            echo json_encode(['status' => 0, 'msg' => '服务异常']);
        }
    }

    /**
     * 订单详情
     */
    public function order_info()
    {
        $order_id = $_GET['order_id'];
        $order_info = M('House_village_pile_pay_order')->where(['id' => $order_id])->find();
        $order_info['equipment_name'] = M('House_village_pile_equipment')->where(['id' => $order_info['equipment_id']])->getField('equipment_name');
        $order_info['continued_time'] = getFormatNumber($order_info['continued_time'] / 60);
        if($order_info['pay_type'] == 10)
            $order_info['pay_time'] = '--';
        else
            $order_info['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
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
            case 5:
                $order_info['pay_type'] = '充电桩余额支付';
                break;
            case 10:
                $order_info['pay_type'] = '本地余额卡支付';
                break;
            default:
                $order_info['pay_type'] = '余额支付';
        }
        if ($order_info['status'] == 0 || $order_info['status'] == 2) {
            $order_info['end_time'] = '----';
            $order_info['status'] = '未结束';
        } else {
            $order_info['end_time'] = date('Y-m-d H:i:s', $order_info['end_time']);
            $order_info['status'] = '已结束';
        }
		if ($order_info['order_id']){
			$orderInfo = M('House_village_pay_order')->field('order_name,pay_time,order_no')->where(['order_id' => $order_info['order_id']])->find();
			$order_info['order_pay_no'] = $orderInfo['order_no'];
			$order_info['order_name']   = $orderInfo['order_name'];
            $wherePayOrderInfo = [
                'business'          => 'pile',
                'business_order_id' => $order_info['order_id'],
            ];
            $pay_order_info = M('Pay_order_info')->where($wherePayOrderInfo)->find();
            if ($pay_order_info && $pay_order_info['paid'] == 1 && !$pay_order_info['paid_record_time'] && $pay_order_info['orderid']) {
                invoke_v20_service('\app\pay\model\service\PayService/getQueryDetail',['order_no'=>$pay_order_info['orderid']]);
                $pay_order_info = M('Pay_order_info')->where($wherePayOrderInfo)->find();
            }
            if (floatval($pay_order_info['paid_discount_amount']) > 0) {
                $order_info['paid_discount_amount'] = $pay_order_info['paid_discount_amount'];
                $order_info['buyer_pay_amount']     = $pay_order_info['buyer_pay_amount'];
            }
		}else{
			$order_info['order_pay_no'] = '';
			$order_info['order_name']   = '';
		}
        $order_info['actual_money'] = getFormatNumber($order_info['use_money'] - $order_info['refund_money']);
        $this->assign('order_info', $order_info);
        $this->display();
    }

    /**
     * 充电桩基本设置
     * @author lijie
     * @date_time 2021/07/20
     */
    public function config()
    {
        $village_id = $this->house_session['village_id'];
        if(IS_POST){
            $low_consume_time = $_POST['low_consume_time']?$_POST['low_consume_time']:0;
            $des_time_level = $_POST['des_time_level']?$_POST['des_time_level']:0;
            $low_consume_power = $_POST['low_consume_power']?$_POST['low_consume_power']:0;
            $des_power_level = $_POST['des_power_level']?$_POST['des_power_level']:0;
            $config_info = [
                'low_consume_time'=>$low_consume_time,
                'des_time_level'=>$des_time_level,
                'low_consume_power'=>$low_consume_power,
                'des_power_level'=>$des_power_level,
                'update_time'=>time(),
                'village_id'=>$village_id
            ];
            $info = M('House_village_pile_config')->where(['village_id'=>$village_id])->find();
            if($info){
                M('House_village_pile_config')->where(['id'=>$info['id']])->save($config_info);
            }else{
                M('House_village_pile_config')->add($config_info);
            }
            $this->success('提交成功！', U('config'));
        }else{
            $config_info = M('House_village_pile_config')->where(['village_id'=>$village_id])->find();
            $this->assign('config',$config_info);
            $this->display();
        }
    }

    /**
     * 退款订单列表
     * @author lijie
     * @date_time 2021/07/20
     */
    public function refund_list()
    {
        $village_id = $this->house_session['village_id'];
        $where['o.village_id'] = $village_id;
        $where['o.type'] = array('neq',1);
        $where['o.is_refund'] = array('gt',1);
        $find_type = $_GET['find_type'];
        $find_value = trim($_GET['find_value']);
        $find_value=htmlspecialchars($find_value,ENT_QUOTES );
        $is_refund = !empty($_GET['is_refund'])? trim($_GET['is_refund']):0;
        $is_refund=intval($is_refund);
        $begin_time = !empty($_GET['begin_time']) ? trim($_GET['begin_time']):'';
        $begin_time= !empty($begin_time) ? strtotime($begin_time):0;
        $end_time = !empty($_GET['end_time']) ? trim($_GET['end_time']):'';
        $end_time= !empty($end_time) ? strtotime($end_time.' 23:59:59'):0;
        if($find_type == 1 && !empty($find_value)){
            $where['u.nickname'] = $find_value;
        }
        if($find_type == 2 && !empty($find_value)){
            $where['u.phone'] = $find_value;
        }
        if ($find_type == 3 && !empty($find_value)) {
            $where['o.order_id'] = $find_value;
        }
        if($is_refund){
            $where['o.is_refund'] = $is_refund;
        }
        if($begin_time>0 && ($end_time<=0)){
            $where['o.pay_time'] = array('egt',$begin_time);
        }elseif ($end_time>0 && ($begin_time<=0)){
            $where['o.pay_time'] = array('elt',$end_time);
        }elseif ($end_time>0 && $begin_time>0){
            $where['o.pay_time'] = array('between',[$begin_time,$end_time]);
        }
        $count = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->join('left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            //->join('left join ' . C('DB_PREFIX') . 'plat_order p on p.business_id =o.id')
            ->where($where)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->join('left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            //->join('left join ' . C('DB_PREFIX') . 'plat_order p on p.business_id =o.id')
            ->where($where)
            ->field('o.*,u.nickname,u.phone,r.refund_money,r.refund_time,r.refund_reason')
            ->order('o.id DESC')
            ->limit($p->firstRow,$p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach ($order_list as $k=>&$v){
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id'=>$v['equipment_id']])->getField('equipment_name');
            $order_list[$k]['refund_time_txt'] = $v['refund_time']>100 ?  date('Y-m-d H:i:s',$v['refund_time']):'';
            $order_list[$k]['pay_time_txt'] = date('Y-m-d H:i:s',$v['pay_time']);
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time']/60);
            if($v['is_refund'] == 1){
                $order_list[$k]['refund_status'] = '退款申请通过';
            }else if($v['is_refund'] == 2){
                $order_list[$k]['refund_status'] = '<span style="color: red">待审核</span>';
            }else if($v['is_refund'] == 3){
                $order_list[$k]['refund_status'] = '<span style="color: blue">已退款</span>';
            }else if($v['is_refund'] == 4){
                $order_list[$k]['refund_status'] = '<span style="color: green">未通过</span>';
            }
            if(isset($v['phone']) && $order_list[$k]['phone']){
                $order_list[$k]['phone']= $d_house_village->phone_desensitization_v($order_list[$k]['phone'],$village_id,$village_info);
            }
        }
        $this->assign('order_list',$order_list);
        $this->display();
    }

    public function refund_order_export()
    {

        $village_id = $this->house_session['village_id'];
        $where['o.village_id'] = $village_id;
        $where['o.type'] = array('neq',1);
        $where['o.is_refund'] = array('gt',1);
        $find_type = $_GET['find_type'];
        $find_value = trim($_GET['find_value']);
        $find_value=htmlspecialchars($find_value,ENT_QUOTES );
        $is_refund = !empty($_GET['is_refund'])? trim($_GET['is_refund']):0;
        $is_refund=intval($is_refund);
        $begin_time = !empty($_GET['begin_time']) ? trim($_GET['begin_time']):'';
        $begin_time= !empty($begin_time) ? strtotime($begin_time):0;
        $end_time = !empty($_GET['end_time']) ? trim($_GET['end_time']):'';
        $end_time= !empty($end_time) ? strtotime($end_time.' 23:59:59'):0;
        if($find_type == 1 && !empty($find_value)){
            $where['u.nickname'] = $find_value;
        }
        if($find_type == 2 && !empty($find_value)){
            $where['u.phone'] = $find_value;
        }
        if($is_refund){
            $where['o.is_refund'] = $is_refund;
        }
        if($begin_time>0 && ($end_time<=0)){
            $where['o.pay_time'] = array('egt',$begin_time);
        }elseif ($end_time>0 && ($begin_time<=0)){
            $where['o.pay_time'] = array('elt',$end_time);
        }elseif ($end_time>0 && $begin_time>0){
            $where['o.pay_time'] = array('between',[$begin_time,$end_time]);
        }
        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->join('left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            ->where($where)
            ->field('o.*,u.nickname,u.phone,r.refund_money,r.refund_time,r.refund_reason,r.refuse_reason,o.refund_money as r_money')
            ->order('o.id DESC')
            ->select();

        if (count($order_list) <= 0) {
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = '充电桩退款订单导出';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($order_list) / 1000);

        $pCoordinate_arr = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'I', 'J', 'K', 'L', 'M', 'N',
            'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
            'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
            'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM',
            'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ'
        ];
        $pValue_arr = [
            '消费订单编号',
            '设备名称',
            '插头编码',
            '消费开始时间',
            '消费时长（分）',
            '消费结束时间',
            '消费金额（元）',
            '消费电量（度）',
            '消费结束时的功率',
            '状态（结束原因）',
            '业主姓名',
            '联系方式',
            '支付方式',
            '支付时间',
            '支付总金额',
            '实际消费金额',
            '退款时间',
            '退款金额（元）',
            '退款原因',
            '退款状态',
        ];
        $key_length = count($pValue_arr) - 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($order_list) . '个订单');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1 => $v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1] . '1', $v1);
            }

            if (!empty($order_list)) {
                $index = 2;
                $village_pile_equipmentDb=M('House_village_pile_equipment');
                $cell_list = range($pCoordinate_arr[0], $pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }
                foreach ($order_list as $value) {
                    $value['equipment_name'] = $village_pile_equipmentDb->where(['id'=>$value['equipment_id']])->getField('equipment_name');
                    $value['refund_time_txt'] = $value['refund_time']>100 ?  date('Y-m-d H:i:s',$value['refund_time']):'';
                    $value['continued_time'] = getFormatNumber($value['continued_time']/60);
                    if($value['is_refund'] == 1){
                        $value['refund_status'] = '退款申请通过';
                    }else if($value['is_refund'] == 2){
                        $value['refund_status'] = '待审核';
                    }else if($value['is_refund'] == 3){
                        $value['refund_status'] = '已退款';
                    }else if($value['is_refund'] == 4){
                        $value['refund_status'] = '未通过';
                    }
                    switch ($value['pay_type']){
                        case 1:
                            $value['pay_type'] = '余额支付';
                            break;
                        case 2:
                            $value['pay_type'] = '微信支付';
                            break;
                        case 3:
                            $value['pay_type'] = '月卡支付';
                            break;
                        case 4:
                            $value['pay_type'] = '支付宝支付';
                            break;
                        case 5:
                            $value['pay_type'] = '充电桩余额支付';
                            break;
                        case 10:
                            $value['pay_type'] = '本地余额卡支付';
                            break;
                        default:
                            $value['pay_type'] = '余额支付';
                    }
                    $value['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
                    $socket_no=!empty($value['socket_no']) ? $value['socket_no']:'';
                    $actual_money=$value['use_money'] - $value['r_money'];
                    $actual_money=round($actual_money,2);
                    $end_time=$value['end_time']>0 ? date('Y-m-d H:i:s',$value['end_time']):'';
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $value['order_no']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $value['equipment_name']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $socket_no); //插头编码
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $value['pay_time']); //消费开始时间
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['continued_time']); //消费时长（分）
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $end_time); //消费结束时间
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['use_money']);  //消费金额（元）
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $value['use_ele']);  //消费电量（度）
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, $value['end_power']); //消费结束时的功率
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[9] . $index, $value['end_result']); //状态（结束原因）
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[10] . $index, $value['nickname']); //业主姓名
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[11] . $index, $value['phone']); //联系方式
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[12] . $index, $value['pay_type']); //支付方式
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[13] . $index, $value['pay_time']); //支付时间
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[14] . $index, $value['use_money']); //支付总金额
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[15] . $index, $actual_money); //实际消费金额
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[16] . $index, $value['refund_time_txt']); //退款时间
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[17] . $index, $value['refund_money']); //退款金额（元）
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[18] . $index, $value['refund_reason']); //退款原因
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[19] . $index, $value['refund_status']); //退款状态
                    $index++;
                }
            }
            sleep(2);
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
    }
    /**
     * 退款订单详情
     * @author lijie
     * @date_time 2021/07/20
     */
    public function refund_info()
    {
        $order_id = $_GET['order_id'];
        $order_info = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'house_village_pile_refund_order r on r.order_id =o.id')
            ->join('left join ' . C('DB_PREFIX') . 'plat_order p on p.business_id =o.order_id')
            ->field('o.*,r.refund_money,r.img,r.refund_reason,r.refund_time,r.refuse_reason,r.refundType,o.refund_money as r_money,p.third_id as orderid')
            ->where(['o.id'=>$order_id])
            ->find();
        $order_info['equipment_name'] = M('House_village_pile_equipment')->where(['id'=>$order_info['equipment_id']])->getField('equipment_name');
        $order_info['continued_time'] = getFormatNumber($order_info['continued_time']/60);
        $order_info['actual_money'] = $order_info['use_money'] - $order_info['r_money'];
        if(!empty($order_info['img'])){
            $order_info['img'] = explode(',',$order_info['img']);
            $order_info['img_count'] = count($order_info['img']);
        }else{
            $order_info['img_count'] = 0;
        }
        switch ($order_info['pay_type']){
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
            case 5:
                $order_info['pay_type'] = '充电桩余额支付';
                break;
            case 10:
                $order_info['pay_type'] = '本地余额卡支付';
                break;
            default:
                $order_info['pay_type'] = '余额支付';
        }
        $order_info['pay_time'] = date('Y-m-d H:i:s',$order_info['pay_time']);
        $order_info['refund_time'] = $order_info['refund_time']>100 ? date('Y-m-d H:i:s',$order_info['refund_time']):'';
        if($order_info['status'] == 0){
            $order_info['end_time'] = '----';
            $order_info['status'] = '未结束';
        }else{
            $order_info['end_time'] =$order_info['end_time']>100 ?  date('Y-m-d H:i:s',$order_info['end_time']):'无';
            $order_info['status'] = '已结束';
        }
		
		if ($order_info['type'] == 2){ //TCP 充电
			if ($order_info['order_id']){
				$orderInfo = M('House_village_pay_order')->field('order_name,pay_time,order_no')->where(['order_id' => $order_info['order_id']])->find();
				$order_info['order_pay_no'] = $orderInfo['order_no'];
				$order_info['order_name']   = $orderInfo['order_name'];
			}else{
				$order_info['order_pay_no'] = '';
				$order_info['order_name']   = '';
			}
		}else{ //驴充充
			if ($order_info['order_id']){
				$orderInfo = M('House_village_pay_order')->field('order_name,pay_time,order_no')->where(['order_id' => $order_info['order_id']])->find();
				$order_info['order_pay_no'] = $order_info['order_serial'];
				$order_info['order_name']   = $orderInfo['order_name'];
			}else{
				$order_info['order_pay_no'] = '';
				$order_info['order_name']   = '';
			}
		}
		if ($order_info['order_id']) {
		    $wherePayOrderInfo = [
                'business'          => 'pile',
                'business_order_id' => $order_info['order_id'],
            ];
		    $pay_order_info = M('Pay_order_info')->where($wherePayOrderInfo)->find();
		    if ($pay_order_info && $pay_order_info['paid'] == 1 && !$pay_order_info['paid_record_time'] && $pay_order_info['orderid']) {
                invoke_v20_service('\app\pay\model\service\PayService/getQueryDetail',['order_no'=>$pay_order_info['orderid']]);
                $pay_order_info = M('Pay_order_info')->where($wherePayOrderInfo)->find();
            }
		    if (floatval($pay_order_info['paid_discount_amount']) > 0) {
                $order_info['paid_discount_amount'] = $pay_order_info['paid_discount_amount'];
                $order_info['buyer_pay_amount']     = $pay_order_info['buyer_pay_amount'];
            }
        }
		
        $this->assign('order_info',$order_info);
        $this->display();
    }

    /**
     * 艾特充充电桩同步小区信息
     * @author:zhubaodi
     * @date_time: 2021/7/8 11:29
     */
    public function pile_village($village_id)
    {

        $value = D('House_village')->join('AS v left join ' . C('DB_PREFIX') . 'area a on a.area_id =v.area_id')->field('v.village_address,v.village_name,v.village_id,v.province_id,v.city_id,v.area_id,a.area_name')->where(['v.status' => 1, 'v.village_id' => $village_id])->find();
        if (!empty($value)) {
            $area_code = 0;
            $area_code_info = M('face_area')->where(['area_id' => $value['area_id']])->find();
            if (empty($area_code_info)) {
                $area_code = $this->getProvinceCode($value['province_id'], $value['city_id'], $value['area_id']);
            } else {
                $area_code = $area_code_info['pid'];
            }

            $add_device_url = $this->url . '/project/saveProject?userId=' . $this->userId . '&code=' . $area_code . '&name=' . urlencode($value['village_name']) . '&address=' . urlencode($value['village_address']) . '&projectUser=' . urlencode($this->projectUser) . '&projectPhone=' . $this->projectPhone;
            $res = $this->http->curlGet($add_device_url);
            if (!empty($res)) {
                $res = json_decode($res, true);
                if ($res['code'] == 200) {
                    $data_village = [];
                    $data_village['village_id'] = $value['village_id'];
                    $data_village['pile_village_id'] = $res['data']['id'];
                    $data_village['add_time'] = time();
                    D('House_village_pile')->add($data_village);
                    return $res['data']['id'];
                }
            }
        }
        return 0;
    }

    /**
     * 艾特充充电桩获取省code参数
     * @author:zhubaodi
     * @date_time: 2021/7/8 16:58
     */
    public function getProvinceCode($province_id, $city_id, $area_id)
    {
        $d_face_area = M('Face_area');
        $area_info = M('area')->where(['area_id' => $province_id])->find();
        $area = $d_face_area->where(['area_id' => $province_id])->find();
        $code = 0;
        $add_device_url = $this->url . '/operatorApplication/workUnit';
        $res = $this->http->curlGet($add_device_url);
        if (!empty($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200 && !empty($res['data'])) {
                foreach ($res['data'] as $value) {
                    if (empty($area)) {
                        $face_area_data = [
                            'area_type' => 1,
                            'pid' => $value['code'],
                            'cid' => 0,
                            'aid' => 0,
                            'device_type' => 61
                        ];
                        $id = $d_face_area->where($face_area_data)->getField('id');
                        $face_area_data['area_id'] = $province_id;
                        $face_area_data['name'] = $value['name'];
                        if ($id) {
                            $face_area_data['last_time'] = time();
                            $d_face_area->where(['id' => $id])->data($face_area_data)->save();
                        } else {
                            $face_area_data['add_time'] = time();
                            $d_face_area->data($face_area_data)->add();
                        }
                    }
                    $name = str_replace('省', '', $value['name']);
                    $name = str_replace('市', '', $name);
                    $name = str_replace('自治区', '', $name);
                    $name = str_replace('壮族自治区', '', $name);
                    if ($area_info['area_name'] == trim($name)) {
                        $code = $this->getCityCode($value['code'], $city_id, $area_id);
                        break;
                    }
                }
            }
        }
        return $code;
    }

    /**
     * 艾特充充电桩获取市code参数
     * @author:zhubaodi
     * @date_time: 2021/7/8 16:58
     */
    public function getCityCode($code, $city_id, $area_id)
    {
        $d_face_area = M('Face_area');
        $area_info = M('area')->where(['area_id' => $city_id])->find();
        $area = $d_face_area->where(['area_id' => $city_id])->find();
        $add_device_url = $this->url . '/operatorApplication/workUnit?code=' . $code;
        $res = $this->http->curlGet($add_device_url);
        if (!empty($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200 && !empty($res['data'])) {
                foreach ($res['data'] as $value) {
                    if (empty($area)) {
                        $face_area_data = [
                            'area_type' => 2,
                            'pid' => $value['code'],
                            'cid' => 0,
                            'aid' => 0,
                            'device_type' => 61
                        ];
                        $id = $d_face_area->where($face_area_data)->getField('id');
                        $face_area_data['area_id'] = $city_id;
                        $face_area_data['name'] = $value['name'];
                        if ($id) {
                            $face_area_data['last_time'] = time();
                            $d_face_area->where(['id' => $id])->data($face_area_data)->save();
                        } else {
                            $face_area_data['add_time'] = time();
                            $d_face_area->data($face_area_data)->add();
                        }
                    }
                    $name = str_replace('市辖区', $area_info['area_name'], $value['name']);
                    $name = str_replace('市', '', $name);
                    $name = str_replace('特别行政区', '', $name);
                    $name = str_replace('特別行政區', '', $name);
                    $name = str_replace('蒙古自治州', '', $name);
                    $name = str_replace('回族自治州', '', $name);
                    $name = str_replace('柯尔克孜自治州', '', $name);
                    $name = str_replace('哈萨克自治州', '', $name);
                    $name = str_replace('土家族苗族自治州', '', $name);
                    $name = str_replace('藏族羌族自治州', '', $name);
                    $name = str_replace('傣族自治州', '', $name);
                    $name = str_replace('布依族苗族自治州', '', $name);
                    $name = str_replace('苗族侗族自治州', '', $name);
                    $name = str_replace('壮族苗族自治州', '', $name);
                    $name = str_replace('澳門', '澳门', $name);
                    $name = str_replace('朝鲜族自治州', '', $name);
                    $name = str_replace('哈尼族彝族自治州', '', $name);
                    $name = str_replace('傣族景颇族自治州', '', $name);
                    $name = str_replace('藏族自治州', '', $name);
                    $name = str_replace('彝族自治州', '', $name);
                    $name = str_replace('白族自治州', '', $name);
                    $name = str_replace('傈僳族自治州', '', $name);
                    if ($area_info['area_name'] == trim($name)) {
                        $code = $this->getAreaCode($value['code'], $area_id);
                        break;
                    }
                }
            }
        }
        return $code;
    }

    /**
     * 艾特充充电桩获取市code参数
     * @author:zhubaodi
     * @date_time: 2021/7/8 16:58
     */
    public function getAreaCode($code, $area_id)
    {

        $d_face_area = M('Face_area');
        $area_info = M('area')->where(['area_id' => $area_id])->find();
        $area = $d_face_area->where(['area_id' => $area_id])->find();
        $add_device_url = $this->url . '/operatorApplication/workUnit?code=' . $code;
        $res = $this->http->curlGet($add_device_url);
        if (!empty($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200 && !empty($res['data'])) {
                foreach ($res['data'] as $value) {
                    if (empty($area)) {
                        $face_area_data = [
                            'area_type' => 3,
                            'pid' => substr($value['code'], 0, 2),
                            'cid' => substr($value['code'], 0, 4),
                            'aid' => $value['code'],
                            'device_type' => 61
                        ];
                        $id = $d_face_area->where($face_area_data)->getField('id');
                        $face_area_data['area_id'] = $area_id;
                        $face_area_data['name'] = $value['name'];
                        if ($id) {
                            $face_area_data['last_time'] = time();
                            $d_face_area->where(['id' => $id])->data($face_area_data)->save();
                        } else {
                            $face_area_data['add_time'] = time();
                            $d_face_area->data($face_area_data)->add();
                        }
                    }
                    $name = str_replace('市辖区', $area_info['area_name'], $value['name']);
                    if ($area_info['area_name'] == $name) {
                        $code = $value['code'];
                        break;
                    }
                }
            }
        }
        return $code;
    }

    /**
     *艾特充充电桩修改充电区信息
     * @author:zhubaodi
     * @date_time: 2021/7/16 15:52
     */
    public function updatePointName($name, $pointId)
    {
        $add_device_url = $this->url . '/project/updatePointName?id=' . $pointId . '&name=' . urlencode($name);
        $res = $this->http->curlGet($add_device_url);
        if (!empty($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                return true;
            }
        }
        return false;
    }


    /**
     *艾特充充电桩获取插座状态信息
     * @author:zhubaodi
     * @date_time: 2021/7/16 15:52
     */
    public function outGetPorts($deviceId)
    {

        $add_device_url = $this->url . '/api/charge/outGetPorts?deviceId=' . $deviceId;
        $res = $this->http->curlGet($add_device_url);
        $socketarr = [];
        if (!empty($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                $socketList = $res['ports'];
                $socketarr = [];

                $last_names = array_column($socketList, 'port');
                array_multisort($last_names, SORT_ASC, $socketList);
                foreach ($socketList as $k => $value) {
                    $socketarr[] = $value['portType'];
                }
            }
        }
        return $socketarr;
    }

    /**
     * 艾特充修改费率信息
     * @author:zhubaodi
     * @date_time: 2021/7/16 16:45
     */
    public function pointEdit($id)
    {
        $equipment_info = M('House_village_pile_equipment')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile p on p.village_id =e.village_id')->field('e.*,p.pile_village_id')->where(['e.id' => $id])->find();
        if (!empty($equipment_info)) {
            $equipment_attribute = M('House_village_pile_equipment_attribute')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile_attribute_set p on p.id =e.attribute_standard_id')->field('e.*,p.*')->where(['e.equipment_id' => $id, 'e.is_del' => 1, 'attribute_id' => 1])->find();
            if (empty($equipment_attribute)) {
                $equipment_attribute_info = M('house_village_pile_attribute')->field('*')->where(['value' => 1])->find();
                $standard_value = unserialize($equipment_attribute_info['default_value']);
            } else {
                $standard_value = unserialize($equipment_attribute['standard_value']);
            }
            $equipment_attribute1 = M('House_village_pile_equipment_attribute')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile_attribute_set p on p.id =e.attribute_standard_id')->field('e.*,p.*')->where(['e.equipment_id' => $id, 'e.is_del' => 1, 'attribute_id' => 2])->find();
            if (empty($equipment_attribute1)) {
                $equipment_attribute_info1 = M('house_village_pile_attribute')->field('*')->where(['value' => 2])->find();
                $standard_value1 = unserialize($equipment_attribute_info1['default_value']);
            } else {
                $standard_value1 = unserialize($equipment_attribute1['standard_value']);
            }
            if ($equipment_info['work_type'] == 1) {
                $list[] = [
                    'minPower' => 0,
                    'maxPower' => $standard_value1[0][2],
                    'standard' => $standard_value[0][2] / $standard_value[1][2],
                ];
            } elseif ($equipment_info['work_type'] == 2) {
                $list[] = [
                    'standard' => $standard_value[0][2] / $standard_value[2][2],
                ];
            }

            $data = [];
            $data = [
                'projectId' => $equipment_info['pile_village_id'],
                'pointId' => $equipment_info['pointId'],
                'userId' => $this->userId,
                'type' => 0,
                'typeCharge' => $equipment_info['work_type'],
                'list' => json_encode($list),
                'money' => 0,
                'num' => 0,
                'word' => 1,
                'one' => $standard_value[0][2],
                'two' => $standard_value[0][2] * 2,
                'three' => $standard_value[0][2] * 3,
                'four' => $standard_value[0][2] * 4,
                'state' => 1,
                'rateList' => json_encode($list),

            ];
            $add_device_url = $this->url . '/projectManager/pointEdit';
            $res = $this->http->curlGet($add_device_url.'?'.http_build_query($data));
            if (!empty($res)) {
                $res = json_decode($res, true);
                if ($res['code'] == 200) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 艾特充修改费率信息
     * @author:zhubaodi
     * @date_time: 2021/7/16 16:45
     */
    public function pointEdit1($id)
    {
        $equipment_info = M('House_village_pile_equipment')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile p on p.village_id =e.village_id')->field('e.*,p.pile_village_id')->where(['e.id' => $id])->find();
        if (!empty($equipment_info)) {
            $equipment_attribute = M('House_village_pile_equipment_attribute')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile_attribute_set p on p.id =e.attribute_standard_id')->field('e.*,p.*')->where(['e.equipment_id' => $id, 'e.is_del' => 1, 'attribute_id' => 1])->find();
            if (empty($equipment_attribute)) {
                $equipment_attribute_info = M('house_village_pile_attribute')->field('*')->where(['value' => 1])->find();
                $standard_value = unserialize($equipment_attribute_info['default_value']);
            } else {
                $standard_value = unserialize($equipment_attribute['standard_value']);
            }
            $equipment_attribute1 = M('House_village_pile_equipment_attribute')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile_attribute_set p on p.id =e.attribute_standard_id')->field('e.*,p.*')->where(['e.equipment_id' => $id, 'e.is_del' => 1, 'attribute_id' => 2])->find();
            if (empty($equipment_attribute1)) {
                $equipment_attribute_info1 = M('house_village_pile_attribute')->field('*')->where(['value' => 2])->find();
                $standard_value1 = unserialize($equipment_attribute_info1['default_value']);
            } else {
                $standard_value1 = unserialize($equipment_attribute1['standard_value']);
            }
            if ($equipment_info['work_type'] == 1) {
                $list[] = [
                    'minPower' => 0,
                    'maxPower' => $standard_value1[0][2],
                    'standard' => $standard_value[0][2] / $standard_value[1][2],
                ];
            } elseif ($equipment_info['work_type'] == 2) {
                $list[] = [
                    'standard' => $standard_value[0][2] / $standard_value[2][2],
                ];
            }
            $equipment_attribute_bigPower = M('House_village_pile_equipment_attribute')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile_attribute_set p on p.id =e.attribute_standard_id')->field('e.*,p.*')->where(['e.equipment_id' => $id, 'e.is_del' => 1, 'attribute_id' => 4])->find();
            if (empty($equipment_attribute_bigPower)) {
                $equipment_attribute_bigPower = M('house_village_pile_attribute')->field('*')->where(['value' => 4])->find();
                $standard_bigPower_value = unserialize($equipment_attribute_bigPower['default_value']);
            } else {
                $standard_bigPower_value = unserialize($equipment_attribute_bigPower['standard_value']);
            }
            $equipment_attribute_bigTime = M('House_village_pile_equipment_attribute')->join('AS e left join ' . C('DB_PREFIX') . 'house_village_pile_attribute_set p on p.id =e.attribute_standard_id')->field('e.*,p.*')->where(['e.equipment_id' => $id, 'e.is_del' => 1, 'attribute_id' => 5])->find();
            if (!empty($equipment_attribute_bigTime)) {
                $standard_bigTime_value = unserialize($equipment_attribute_bigTime['standard_value']);
                $limit_data=[
                    'maxChargeTime'=>$standard_bigTime_value[0][2],
                    'maxChargePower'=>$standard_bigPower_value[0][2],
                    'pointId'=>$equipment_info['pointId'],
                ];
                $url = $this->url . '/projectManager/setLimitByPoint';
                $this->http->curlGet($url.'?'.http_build_query($limit_data));
            }


            $data = [];
            $data = [
                'projectId' => $equipment_info['pile_village_id'],
                'pointId' => $equipment_info['pointId'],
                'userId' => $this->userId,
                'type' => 0,
                'typeCharge' => $equipment_info['work_type'],
                'list' => json_encode($list),
                'money' => 0,
                'num' => 0,
                'word' => 1,
                'one' => $standard_value[0][2],
                'two' => $standard_value[0][2] * 2,
                'three' => $standard_value[0][2] * 3,
                'four' => $standard_value[0][2] * 4,
                'state' => 1,
                'rateList' => json_encode($list),

            ];
            $add_device_url = $this->url . '/projectManager/pointEdit';
            $res = $this->http->curlGet($add_device_url.'?'.http_build_query($data));
            if (!empty($res)) {
                $res = json_decode($res, true);
                if ($res['code'] == 200) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *艾特充充电桩删除设备
     * @author:zhubaodi
     * @date_time: 2021/7/16 15:52
     */
    public function queDelete($deviceId)
    {
        $add_device_url = $this->url . '/projectManager/queDelete?num=' . $deviceId;
        $res = $this->http->curlGet($add_device_url);
        if (!empty($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                return true;
            }
        }
        return false;
    }
    /*
     * 退款订单审批
     * @author lijie
     * @date_time 2021/07/20
     */
    public function refund_action()
    {
        $order_id = $_POST['order_id'];
        $refund_reason = $_POST['refund_reason'];
        $status = $_POST['status'];
        $order_info = M('House_village_pile_pay_order')->where(['id'=>$order_id])->find();
        $st1=microtime();
        fdump_api([$order_info,$order_id,$st1],'refund_action_0811',1);
        if($order_info['is_refund'] !=2){
            echo json_encode(['status'=>1,'msg'=>'该笔退款无法审核']);exit;
        }
        if ($status==-1) {
            $refund_money = isset($_POST['refund_money'])?$_POST['refund_money']:'-1';
            if ($refund_money>$order_info['use_money']) {
                echo json_encode(['status'=>1,'msg'=>'金额不能大于支付金额']);exit;
            }
            if ($refund_money!='-1') {
                $refundData = [
                    'refund_money' => $refund_money
                ];
                M('House_village_pile_refund_order')->where(['order_id'=>$order_id])->save($refundData);
            }
            echo json_encode(['status'=>1,'msg'=>'变更成功']);exit;
        } elseif($status==3) {
            $refund_info = M('House_village_pile_refund_order')->where(['order_id' => $order_id])->find();
            $st2 = microtime();
            fdump_api([$refund_info, $order_id, $st1, $st2], 'refund_action_0811', 1);
            if ($order_info['type'] == 1) {
                $refund = D('Plat_order')->pile_order_refund(array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']), '', $refund_info['refund_money']);
                if (!$refund['error']) {
                    $res = M('House_village_pile_pay_order')->where(['id' => $order_info['id']])->data(['is_refund' => 3])->save();
                    if ($res) {
                        M('House_village_pile_refund_order')->where(['order_id' => $order_id])->data(['refund_time' => time(), 'status' => 3])->save();
                    }
                    D('House_village_pile_equipment')->handlePileChargeConsumePoint( $order_id,$refund_info['refund_money'],'pile_refund');
                    $openid = M('User')->where(['uid' => $order_info['uid']])->find();
                    if (!empty($openid['openid'])) {
                        $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $order_info['id'] . '&status=1');
                        $datamsg = [
                            'tempKey' => 'OPENTM400166399',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $openid['openid'],
                                'first' => '您的充电桩账单退款￥' . $order_info['use_money'] . '成功',
                                'keyword1' => '充电桩充电提醒',
                                'keyword2' => '已发送',
                                'keyword3' => date('H:i'),
                            ]
                        ];
                        D('Tempmsg')->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    echo json_encode(['status' => 1, 'msg' => '退款审核通过']);
                    exit;
                } else {
                    M('House_village_pile_refund_order')->where(['order_id' => $order_id])->data(['refund_money' => $refund_info['refund_money'], 'refund_reason' => $refund['msg'], 'add_time' => time()])->save();
                    echo json_encode(['status' => 1, 'msg' => '操作失败！'. $refund['msg']]);
                    exit;
                }
            }
            elseif ($order_info['type'] == 21) {
                $data = [
                    'cmd' => 'refundMoney',
                    'orderNo' => $order_info['order_no'],
                    'refund_money' => $refund_info['refund_money'],
                    'business_type' => 'house_village_pay',
                    'type' => 'adopt',
                ];
                $data = json_encode($data, true);
                $res = M('House_village_pile_command')->add(['command' => $data, 'type' => 2, 'status' => 1, 'addtime' => time()]);
                if ($res) {
                    $arr = [];
                    $time = time();
                    $unique_id = "pile_notice" . $time . rand(1, 9999);
                    $arr['plan_time'] = -time();
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_pile_notice';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $task_id = M('Process_sub_plan')->data($arr)->add();
                    echo json_encode(['status' => 1, 'msg' => '退款审核通过']);
                    exit;
                }
                echo json_encode(['status'=>1,'msg'=>'操作失败']);exit;
            }
            else{
                $data = [
                    'cmd'=>'refundMoney',
                    'orderNo'=>$order_info['order_no'],
                    'refund_money' => $refund_info['refund_money'],
                    'business_type'=>'house_village_pay',
                    'type'=>'adopt',
                ];
                $data = json_encode($data,true);
                $res = M('House_village_pile_command')->add(['command'=>$data,'type'=>2,'status'=>1,'addtime'=>time()]);
                if ($res){
                    $arr = [];
                    $time = time();
                    $unique_id = "pile_notice".$time.rand(1,9999);
                    $arr['plan_time'] = -time();
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_pile_notice';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $task_id = M('Process_sub_plan')->data($arr)->add();
                    echo json_encode(['status'=>1,'msg'=>'退款审核通过']);exit;
                }else{
                    echo json_encode(['status'=>1,'msg'=>'操作失败']);exit;
                }
            }
        }else{
            $refund_info = M('House_village_pile_refund_order')->where(['order_id'=>$order_id])->find();
            $data = [
                'cmd'=>'refundMoney',
                'orderNo'=>$order_info['order_no'],
                'refund_money' => $refund_info['refund_money'],
                'business_type'=>'house_village_pay',
                'type'=>'refuse',
                'reason'=>$refund_reason
            ];
            $data = json_encode($data,true);
            $res = M('House_village_pile_command')->add(['command'=>$data,'type'=>2,'status'=>1,'addtime'=>time()]);
            $arr = [];
            $time = time();
            $unique_id = "pile_notice".$time.rand(1,9999);
            $arr['plan_time'] = -time();
            $arr['space_time'] = 0;
            $arr['add_time'] = time();
            $arr['file'] = 'sub_pile_notice';
            $arr['time_type'] = 1;
            $arr['unique_id'] = $unique_id;
            $task_id = M('Process_sub_plan')->data($arr)->add();
            $res = M('House_village_pile_pay_order')->where(['id'=>$order_id])->save(['is_refund'=>$status]);
            D('House_village_pile_equipment')->handlePileChargeConsumePoint( $order_id,0,'pile_refund');
            if($res){
                M('House_village_pile_refund_order')->where(['order_id'=>$order_id])->save(['status'=>$status,'refund_time'=>time(),'refuse_reason'=>$refund_reason]);
                echo json_encode(['status'=>1,'msg'=>'退款审核未通过']);exit;
            }
            echo json_encode(['status'=>1,'msg'=>'操作失败']);exit;
        }
        //$refund = D('Plat_order')->pile_order_refund(array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']),'',$refund_info['refund_money']);
    }
}