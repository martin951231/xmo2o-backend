<?php


class PileHzAction extends BaseAction
{
    private $http = '';
    private $userId = '03101369';
    private $url = 'http://ap3204.haokuaichong.cn/outeruser';
    private $projectUser = 'zbd';
    private $projectPhone = '15055152821';

    /**
     * 艾特充充电桩同步小区信息
     * @author:zhubaodi
     * @date_time: 2021/7/8 11:29
     */





    public function pile_village()
    {
        $list = D('House_village')->where(['status' => 1])->order('id DESC')->select();
        if (!empty($list)) {
            $code = $this->getCityCode();
            foreach ($list as $value) {
                $add_device_url = $this->url . '/project/saveProject?userId=' . $this->userId . '&code=' . $code . '&name=' . $value['village_name'] . '&address=' . $value['village_address'] . '&projectUser=' . $this->projectUser . '&projectPhone=' . $this->projectPhone;
                $res = Http::curlGet($add_device_url);
                if (!is_array($res)) {
                    $res = json_decode($res, true);
                    if ($res['cose'] == 200) {
                        $data_village = [];
                        $data_village['village_id'] = $value['village_id'];
                        $data_village['pile_village_id'] = $res['id'];
                        D('House_village_pile')->add($data_village);
                    }
                }
            }
        }
    }

    /**
     * 获取区code参数
     * @author:zhubaodi
     * @date_time: 2021/7/8 16:58
     */
    public function getCityCode()
    {
        $code = 0;
        $add_device_url = $this->url . '/operatorApplication/workUnit';
        $res = Http::curlGet($add_device_url);
        if (!is_array($res)) {
            $res = json_decode($res, true);
        }
        return $code;
    }

    /**
     * 创建充电区并设置费率
     * @author:zhubaodi
     * @date_time: 2021/7/8 17:40
     */
    public function addPoint()
    {
        $village_id = $this->house_session['village_id'];
        $info = M('House_village_pile')->where(['village_id' => $village_id])->find();
        if (empty($info)) {
            $this->error('请先创建小区', U('equipment_list'));
        }
        $data['projectId'] = $info['pile_village_id'];
        $data['userId'] = $this->userId;
        $data['name'] = $_POST['name'];
        $data['type'] = $_POST['type'];
        $data['typeCharge'] = $_POST['typeCharge'];
        $data['minPower'] = $_POST['minPower'];
        $data['maxPower'] = $_POST['maxPower'];
        $data['standard'] = $_POST['standard'];
        $data['money'] = $_POST['money'];
        $data['num'] = $_POST['num'];
        $data['word'] = $_POST['word'];
        $data['state'] = $_POST['state'];
        $data['state'] = $_POST['state'];
        $data['one'] = $_POST['one'];
        $data['two'] = $_POST['two'];
        $data['three'] = $_POST['three'];
        $data['four'] = $_POST['four'];
        if (empty($data['name'])) {
            $this->error('充电区名称不能为空', U('equipment_list'));
        }
        if (empty($data['type'])) {
            $this->error('请选择是否包月', U('equipment_list'));
        }
        if (empty($data['word'])) {
            $this->error('请选择是否为单次', U('equipment_list'));
        }
        if ($data['word'] == 1) {
            if (empty($data['one']) || empty($data['two']) || empty($data['three']) || empty($data['four'])) {
                $this->error('临时金额不能为空', U('equipment_list'));
            }
        }
        $attribute_info = D('House_village_pile_attribute_set')->where(['standard_name' => $data['name']])->find();
        if (!empty($attribute_info)) {
            $this->error('充电区名称已存在', U('equipment_list'));
        }

        if ($data['typeCharge'] == 1) {
            $typeCharge = 0;
        } elseif ($data['typeCharge'] == 2) {
            $typeCharge = 2;

        } elseif ($data['typeCharge'] == 3) {
            $typeCharge = 1;
        }
        $data_pile = [
            'village_id' => $village_id,
            'attribute_id' => 1,
            'type' => $typeCharge,
            'standard_name' => $data['name'],
            'standard_value' => serialize($data),
            'status' => 1,
            'is_del' => 1,
            'add_time' => time()
        ];
        $attribute = D('House_village_pile_attribute_set')->add($data_pile);

        if (empty($attribute)) {
            
            $this->error('添加失败', U('equipment_list'));
        } else {
            $add_device_url = $this->url . '/projectManager/pointAdd';
            $res = Http::curlPostOwn($add_device_url, $data);
            if (!is_array($res)) {
                $res = json_decode($res, true);
                if ($res['code'] == 200) {
                    D('House_village_pile_attribute_set')->where(['id' => $attribute])->data(['standard_des' => $res['id']])->save();
                    $this->success('添加成功', U('equipment_list'));

                } else {
                    $this->error($res['error'], U('equipment_list'));
                }
            }
        }
        $this->error('添加失败', U('equipment_list'));
    }

    /**
     * 查询充电区列表信息
     * @author:zhubaodi
     * @date_time: 2021/7/9 13:53
     */
    public function allpoint_list()
    {
        $village_id = $this->house_session['village_id'];
        $info = M('House_village_pile')->where(['village_id' => $village_id])->find();
        $info['pile_village_id']=1;
        if (empty($info)) {
            $this->error('请先创建小区', U('equipment_list'));
        }
        $name = $_GET['name'];
        $add_device_url = $this->url . '/projectManager/allpoint?projectId=' . $info['pile_village_id'] . '&name=' . $name;
        $res = Http::curlGet($add_device_url);
        $res='{
    "msg": "操作成功！",
    "total": 4,
    "code": 200,
    "rows": [
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "w50hi",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "sMpdHh",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "1!y",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "ch&",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "xIydcuE",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "jNh*e",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "Mj4&",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "7nY8N",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "F)7QU^",
            "attr1": "1500"
        },
        {
            "attr0": "10",
            "areaId": 3,
            "creatTime": "2019-12-25 11:23:59",
            "name": "计次+临时充电区",
            "id": "19122583SK0PYCH0",
            "type": "1",
            "operatorId": "X8jeLY7",
            "attr1": "1500"
        }
    ]
}';
        $list = [];
        if (!is_array($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                $list = $res['rows'];
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 查询充电区费率
     * @author:zhubaodi
     * @date_time: 2021/7/9 13:57
     */
    public function point_info()
    {
        $pointId = $_GET['pointId'];
        if (empty($pointId)) {
            $this->error('充电区id必传', U('equipment_list'));
        }
        $add_device_url = $this->url . '/projectManager/point?pointId=' . $pointId;
        $res = Http::curlGet($add_device_url);
        $data = [];
        if (!is_array($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                $data = $res['data'];
            }
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 智能充电桩列表
     * @author:zhubaodi
     * @date_time: 2021/7/9 14:51
     */
    public function equipment_list()
    {
        $pointId = $_GET['pointId'];
        if (empty($pointId)) {
            $this->error('充电区id必传', U('equipment_list'));
        }
        $add_device_url = $this->url . '/projectManager/queList?pointId=' . $pointId;
       //  $res = Http::curlGet($add_device_url);
        $res='{
    "msg": "操作成功！",
    "total": 1,
    "code": 200,
    "rows": [
        {
            "num": "19122583SK",
            "port": "",
            "portState": "",
            "quPrPoId": "",
            "endTime": "2019-12-25 11:23:59",
            "state": "联网",
            "eqDeviceId": "19122583SK0PYCH0"
        }
    ]
}';
        $data = [];
        if (!is_array($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                $data = $res['rows'];
            }
        }
        $this->assign('list', $data);
        $this->display();
    }


    /**
     * 查询设备插座状态
     * @author:zhubaodi
     * @date_time: 2021/7/9 14:12
     */
    public function getPorts()
    {

        $deviceId = $_GET['deviceId'];
        if (empty($deviceId)) {
            $this->error('设备编号必传', U('equipment_list'));
        }
        $add_device_url = $this->url . '/api/charge/outGetPorts?deviceId=' . $deviceId;
      //   $res = Http::curlGet($add_device_url);
        $res='{
    "msg": "b8$",
    "code": "GGml^",
    "ports": "[        {
            "portType": "0",
            "lastTime": "2019-12-25 11:23:59",
            "port": "1"
        },
        {
            "portType": "0",
            "lastTime": "2019-12-25 11:23:59",
            "port": "2"
        }
    ]"
}';
        $data = [];
        if (!is_array($res)) {
            $res = json_decode($res, true);
            if ($res['code'] == 200) {
                $data = $res['ports'];
            }
        }
        $this->assign('list', $data);
        $this->display();
    }


    /**
     * 智能充电桩绑定到充电区
     * @author:zhubaodi
     * @date_time: 2021/7/9 14:51
     */
    public function equipment_add()
    {
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $add_device_url = $this->url . '/api/charge/scanCode';
            $postParams['areaId'] = $_POST['areaId'];
            $postParams['device'] = $_POST['device'];
            $res = Http::curlPostOwn($add_device_url, http_build_query($postParams));
            if (!is_array($res)) {
                $res = json_decode($res, true);
            }
            if ($res['code'] == 200) {
                $this->success('保存成功', U('equipment_list'));
            } else {
                $this->error($res['error'], U('equipment_list'));
            }
        } else {
            $this->display();
        }
    }


    /**
     * 删除设备
     * @author:zhubaodi
     * @date_time: 2021/7/9 14:51
     */
    public function queDelete(){

        $num = $_GET['num'];
        $add_device_url = $this->url . '/projectManager/queDelete?num='.$num;
        $res = Http::curlGet($add_device_url);
        if (!is_array($res)) {
            $res = json_decode($res, true);
        }
        if ($res['code'] == 200) {
            $this->success('删除成功', U('equipment_list'));
        } else {
            $this->error($res['error'], U('equipment_list'));
        }
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
        $where['o.pay_time'] = array('gt', 1);
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
        foreach ($order_list as $k => &$v) {
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id' => $v['equipment_id']])->getField('equipment_name');
            $order_list[$k]['pay_time_txt'] = date('Y-m-d H:i:s', $v['pay_time']);
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time'] / 60);
            if ($v['order_type'] == 1)
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
        }
        $this->assign('order_list', $order_list);
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
        $where['o.type'] = 1;
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $order_type = $_GET['order_type'];
        $pay_type = $_GET['pay_type'];
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

        $order_list = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where)
            ->field('o.*,u.nickname,u.phone')
            ->order('o.id DESC')
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
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $value['continued_time']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $value['status']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $value['use_money']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $value['use_ele']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, $value['end_power']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, $value['nickname']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[9] . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[10] . $index, $pay_type);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[11] . $index, $pay_time);
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
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('equipment_list'));
        }
        $equipment_id = $_POST['equipment_id'];
        $status = $_POST['status'];
        $simId = $_POST['simId'];
        import('ORG.Net.Http');
        $postParams['token'] = $lbbtechCenterToken;
        $postParams['status'] = $status;
        $postParams['simId'] = $simId;
        $this->http = new Http();
        $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
        if (!$lbbtechCenterUrl) {
            $lbbtechCenterUrl = 'https://center.lbbtech.com';
        }
        $request_url = $lbbtechCenterUrl . '/open/updateDeviceInfo';
        $res = Http::curlPostOwn($request_url, http_build_query($postParams));
        if (!is_array($res)) {
            $res = json_decode($res, true);
        }
        if ($res['success'] == true) {
            M('House_village_pile_equipment')->where(['id' => $equipment_id])->data([
                'status' => $status
            ])->save();
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
        $order_info['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
        if ($order_info['status'] == 0) {
            $order_info['end_time'] = '----';
            $order_info['status'] = '未结束';
        } else {
            $order_info['end_time'] = date('Y-m-d H:i:s', $order_info['end_time']);
            $order_info['status'] = '已结束';
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }

    public function qcode_num()
    {
        if (IS_POST) {
            //业主-导出 权限
            if (!in_array(98, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

            $title = '二维码序列号导出';

            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);
            $num = $_POST['number'];
            $length = ceil($num / 1000);

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
                '二维码内容',
                '二维码序列号',
            ];
            $key_length = count($pValue_arr) - 1;
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('共' . $num . '个二维码');
                $objActSheet = $objExcel->getActiveSheet();

                foreach ($pValue_arr as $k1 => $v1) {
                    $objActSheet->setCellValue($pCoordinate_arr[$k1] . '1', $v1);
                }

                if ($num > 0) {
                    $index = 2;
                    $cell_list = range($pCoordinate_arr[0], $pCoordinate_arr[$key_length]);
                    foreach ($cell_list as $cell) {
                        $objActSheet->getColumnDimension($cell)->setWidth(25);
                    }

                    for ($j = 0; $j < $num; $j++) {
                        $qcodeNum = time() . rand(100000, 999999);
                        $qcodeNum = str_shuffle($qcodeNum);
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, get_base_url('/pages/village/smartCharge/smartCharge?qcodeType=0&qcodeNum=' . $qcodeNum));
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $qcodeNum);
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
        } else {
            $this->display();
        }
    }
}