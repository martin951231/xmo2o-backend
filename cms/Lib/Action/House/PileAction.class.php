<?php


class PileAction extends BaseAction
{
    private $http = '';
    public $status=[0=>'离线',1=>'在线'];
    public $device_status=[0=>'注册', 1=>'上线', 2=>'禁用',3=>'设备故障', 4=>'已拆机',5=>'测试设备'];
    //0：电动两轮车设备   1：汽车桩设备
    public $device_family=[0=>'电动两轮车设备', 1=>'汽车桩设备'];
    /**
     * 智能充电桩列表
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_list()
    {
        $village_id = $this->house_session['village_id'];
        $where = ['village_id'=>$village_id,'is_del'=>1,'type'=>1];
        if($_GET['find_value'] && $_GET['find_type']==1){
            $where['equipment_name'] = ['LIKE', '%' . trim($_GET['find_value']) . '%'];
        }
        if($_GET['find_value'] && $_GET['find_type']==2){
            $where['equipment_sn'] = $_GET['find_value'];
        }
        if($_GET['find_value'] && $_GET['find_type']==3){
            $where['equipment_num'] = $_GET['find_value'];
        }
        $res = D('House_village_pile_equipment')->getList($where);
        if($res['list']){
            $base_url = D('House_village')->base_url;
            $site_url = $this->config['site_url'];

            foreach ($res['list'] as $k=>$v){
                $url = $site_url.$base_url.'pages/village/smartCharge/smartCharge?id='.$v['id'];
                $img_url = $site_url.U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($url))));
                $res['list'][$k]['qrcode'] = $img_url;
                $res['list'][$k]['last_heart_time'] = (isset($v['last_heart_time']) && $v['last_heart_time']>1) ? date('Y-m-d H:i:s',$v['last_heart_time']) : '--';
            }
        }
        $qrUrl = get_base_url("pages/village/smartCharge/smartCharge?qcodeType=0&qcodeNum=【设备唯一编码】");
        $this->assign('qrUrl', $qrUrl);
        $this->assign('pagebar',$res['pagebar']);
        $this->assign('list', $res['list']);
        $this->display();
    }

    public function get_own_qrcode($qrCon, $size = 8,$is_down = true, $outfile = false){
        header('Content-Type: image/png');
        ob_clean();
//        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
//        $size = $_GET['size'] ? $_GET['size']: 10;
//        $is_down = $_GET['down'] ? true: false;
        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),$outfile,0,$size,1,$is_down);
    }

    /**
     * 新增、编辑智能充电桩
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_add()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('equipment_list'));
        }
        $village_id = $this->house_session['village_id'];
        if(IS_POST){
            $data['village_id'] = $village_id;
            $data['equipment_name'] = $_POST['equipment_name'];
            $data['equipment_sn'] = $_POST['equipment_sn'];
            $data['equipment_num'] = $_POST['equipment_num'];
            $data['socket_num'] = $_POST['socket_num'];
            $data['device_family'] = (int)$_POST['device_family'];
            $data['device_type'] = (int)$_POST['device_type'];
            $data['add_time'] = time();
            $data['type'] = 1;
            if(isset($_POST['energy_type'])){
                $data['energy_type']=intval($_POST['energy_type']);
            }
            if((int)$_POST['device_family'] == 0 && empty($_POST['equipment_sn'])){
                $this->error('电车设备必须填写二维码序列号',U('equipment_list'));
            }
            $equipment_info = M('House_village_pile_equipment')->where(['equipment_num'=>$data['equipment_num'],'is_del'=>1])->find();
           if (!empty($equipment_info)){
               $this->error('设备唯一编号不能重复',U('equipment_list'));  
           }
            import('ORG.Net.Http');
            $this->http = new Http();
            $postParams = [];
            if($_POST['lng_lat']){
                $lng_lat = explode(',',$_POST['lng_lat']);
                $data['lng'] = $lng_lat[0];
                $data['lat'] = $lng_lat[1];
                $postParams['lng'] = $lng_lat[0];
                $postParams['lat'] = $lng_lat[1];
            }
            $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
            if (!$lbbtechCenterUrl) {
                $lbbtechCenterUrl = 'https://center.lbbtech.com';
            }
            $add_device_url = $lbbtechCenterUrl . '/open/addDevice';
            $postParams['token'] = $lbbtechCenterToken;
            $postParams['simId'] = $data['equipment_num'];
            $postParams['deviceFamily'] = (int)$data['device_family'];
            $postParams['deviceType'] = (int)$data['device_type'];
            $postParams['name'] = $_POST['equipment_name'];
            $postParams['portNum'] = (int)$_POST['socket_num'];
            $res = Http::curlPostOwn($add_device_url, http_build_query($postParams));
            fdump_api(['msg' => '设备添加同步', 'line' => __LINE__,'postParams' => $postParams,'add_device_url' => $add_device_url,'res' => $res],'equipment/addDeviceLog');
            if(!is_array($res)){
                $res = json_decode($res,true);
            }
            $res = M('House_village_pile_equipment')->add($data);
            if($res){
                $this->success('保存成功',U('equipment_list'));
            }
            /*if($res['success']){
                $res = M('House_village_pile_equipment')->add($data);
                if($res){
                    $this->success('保存成功',U('equipment_list'));
                }
            }else{
                $this->error($res['error'],U('equipment_list'));
            }*/
        }else{
            $pile_charge_consume_point=D('Config_customization')->getPileChargeConsumePoint();
            $this->assign('pile_charge_consume_point',$pile_charge_consume_point);
            $this->display();
        }
    }

    /**
     * 编辑充电桩
     */
    public function equipment_edit()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('equipment_list'));
        }
        $village_id = $this->house_session['village_id'];
        if(IS_POST){
            $data['village_id'] = $village_id;
            $data['equipment_name'] = $_POST['equipment_name'];
            $data['equipment_sn'] = $_POST['equipment_sn'];
            $data['socket_num'] = $_POST['socket_num'];
            if(isset($_POST['energy_type'])){
                $data['energy_type']=intval($_POST['energy_type']);
            }
            if((int)$_POST['device_family'] == 0 && empty($_POST['equipment_sn'])){
                $this->error('电车设备必须填写二维码序列号',U('equipment_list'));
            }
            $data['add_time'] = time();
            import('ORG.Net.Http');
            $this->http = new Http();
            $postParams =[];
            if($_POST['lng_lat']){
                $lng_lat = explode(',',$_POST['lng_lat']);
                $data['lng'] = $lng_lat[0];
                $data['lat'] = $lng_lat[1];
                $postParams['lng'] = $lng_lat[0];
                $postParams['lat'] = $lng_lat[1];
            }
            $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
            if (!$lbbtechCenterUrl) {
                $lbbtechCenterUrl = 'https://center.lbbtech.com';
            }
            $postParams['token'] = $lbbtechCenterToken;
            $postParams['simId'] = $_POST['equipment_num'];
            $postParams['name'] = $_POST['equipment_name'];
            $postParams['portNum'] = (int)$_POST['socket_num'];
            $postParams['deviceType'] = (int)$_POST['device_type'];
            $update_device_url = $lbbtechCenterUrl . '/open/updateDeviceInfo';
            $res = Http::curlPostOwn($update_device_url, http_build_query($postParams));
            fdump_api(['msg' => '设备更新同步==', 'line' => __LINE__,'postParams' => $postParams,'update_device_url' => $update_device_url,'res' => $res],'equipment/updateDeviceInfo');
            if(!is_array($res)){
                $res = json_decode($res,true);
            }
            if (isset($res['error']) && $res['error'] == '设备不存在') {
                $addPostParams = [];
                if($_POST['lng_lat']){
                    $lng_lat = explode(',',$_POST['lng_lat']);
                    $data['lng'] = $lng_lat[0];
                    $data['lat'] = $lng_lat[1];
                    $addPostParams['lng'] = $lng_lat[0];
                    $addPostParams['lat'] = $lng_lat[1];
                }
                $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
                if (!$lbbtechCenterUrl) {
                    $lbbtechCenterUrl = 'https://center.lbbtech.com';
                }
                $add_device_url = $lbbtechCenterUrl . '/open/addDevice';
                $addPostParams['token'] = $lbbtechCenterToken;
                $addPostParams['simId'] = $_POST['equipment_num'];
                $addPostParams['deviceFamily'] = (int)$_POST['device_family'];
                $addPostParams['deviceType'] = (int)$_POST['device_type'];
                $addPostParams['name'] = $_POST['equipment_name'];
                $addPostParams['portNum'] = (int)$_POST['socket_num'];
                $res = Http::curlPostOwn($add_device_url, http_build_query($addPostParams));
                if(!is_array($res)){
                    $res = json_decode($res,true);
                }
                fdump_api(['msg' => '设备添加同步==', 'line' => __LINE__,'addPostParams' => $addPostParams,'add_device_url' => $add_device_url,'res' => $res],'equipment/updateDeviceInfo', 1);
            }
            if(empty($res)){
                fdump_api(['设备不存在或参数错误=='.__LINE__,$postParams,$update_device_url,$res],'equipment/updateDeviceInfo_err');
                $res['error']='设备不存在或参数错误';
            }
            if($res['success']){
                $res = M('House_village_pile_equipment')->where(['id'=>$_POST['id']])->save($data);
                if($res){
                    $this->success('保存成功',U('equipment_list'));
                }
            }else{
                $this->error($res['error'],U('equipment_list'));
            }
        }else{
            $id = $_GET['id']?$_GET['id'] : 0;
            if($id){
                $info = M('House_village_pile_equipment')->where(['id'=>$id])->find();
                $this->assign('info',$info);
            }
            $pile_charge_consume_point=D('Config_customization')->getPileChargeConsumePoint();
            $this->assign('pile_charge_consume_point',$pile_charge_consume_point);
            $this->assign('id',$id);
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
        $id = $_POST['id']?$_POST['id'] : 0;
        if(!$id){
            $this->error('删除失败',U('equipment_list'));
        }
        $res = M('House_village_pile_equipment')->where(['id' => $id])->find();
        if(M('House_village_pile_equipment')->where(['id'=>$id])->save(['is_del'=>2,'equipment_num' => $res['equipment_num'].'_'.time()])){
            $this->success('删除成功',U('equipment_list'));
        }
    }

    /**
     * 查看智能充电桩详情
     * @author lijie
     * @date_time 2021/02/05
     */
    public function equipment_info()
    {
        $id = $_GET['id']?$_GET['id'] : 0;
        if(!$id){
            $this->error('查看失败',U('equipment_list'));
        }
        $info = M('House_village_pile_equipment')->where(['id'=>$id])->find();
        /*if($info['status'] == 1)
            $info['status_txt'] = '在线';
        else
            $info['status_txt'] = '离线';*/
        $this->assign('info',$info);
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
        $count = M('House_village_pile_month_card')->where(['village_id'=>$village_id,'is_del'=>1,'type'=>1])->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $list = M('House_village_pile_month_card')->where(['village_id'=>$village_id,'is_del'=>1,'type'=>1])->order('id DESC')->limit($p->firstRow,$p->listRows)->select();
        $this->assign('pagebar',$p->show());
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
        if(IS_POST){
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
            $data['type'] = 1;
            if($_POST['id']){
                $res = M('House_village_pile_month_card')->where(['id'=>$_POST['id']])->save($data);
            }else{
                $res = M('House_village_pile_month_card')->add($data);
            }
            if($res){
                $this->success('保存成功',U('month_card_list'));
            }
        }else{
            $id = $_GET['id']?$_GET['id'] : 0;
            if($id){
                $info = M('House_village_pile_month_card')->where(['id'=>$id])->find();
                $this->assign('info',$info);
            }
            $this->assign('id',$id);
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
        $id = $_GET['id']?$_GET['id'] : 0;
        if(!$id){
            $this->error('删除失败',U('month_card_list'));
        }
        if(M('House_village_pile_month_card')->where(['id'=>$id])->save(['is_del'=>2])){
            $this->success('删除成功',U('month_card_list'));
        }
    }

    /**
     * 充电桩属性列表
     * @author lijie
     * @date_time 2021/02/06
     */
    public function attribute_list()
    {
//        $list = M('House_village_pile_attribute')->where(['type'=>1])->select();
//        $this->assign('list', $list);
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
        if(M('House_village_pile_attribute')->where(['id'=>$id])->getField('default_value')){
            echo json_encode(['status'=>1]);exit;
        }
        switch ($id){
            case 1:
                $arr = [['单价','0~100',1,'元/CNY'],['时间','0~10',4,'小时/h'],['电量','0~100',1,'度/kw.h']];
                break;
            case 2:
                $arr = [['标准功率','0~8000',220,'瓦/W']];
                break;
            case 3:
                $arr = [['功率变化阀值','0~100',15,'瓦/W'],['最小上报间隔','0~255',255,'秒/sec']];
                break;
            case 4:
                $arr = [['极限功率','100~3000',990,'瓦/W']];
                break;
            case 5:
                $arr = [['最大充电时长','64~86400',43200,'秒/sec']];
                break;
            case 6:
                $arr = [['时长功率','0~36000',1800,'秒/sec'],['功率限制','0~100',15,'瓦/W']];
                break;
            case 7:
                $arr = [['时长','0~3600',10,'秒/sec'],['功率','0~100',1,'瓦/W']];
                break;
            case 8:
                $arr = [['温度','-20~120',70,'摄氏度/℃']];
                break;
            default:
                $arr = [['单价','0~100',1,'元/CNY'],['时间','0~10',4,'小时/h'],['电量','0~100',1,'度/kw.h']];
        }
        M('House_village_pile_attribute')->where(['id'=>$id])->save(['default_value'=>serialize($arr)])
        ;
        echo json_encode(['status'=>1]);
    }

    /**
     * 消费标准列表页
     * @author lijie
     * @date_time 2021/02/18
     */
    public function attribute_standard_list()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('attribute_list'));
        }
        $attribute_id = $_GET['attribute_id'];
        $village_id = $this->house_session['village_id'];
        import('ORG.Net.Http');
        $this->http = new Http();
        $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
        if (!$lbbtechCenterUrl) {
            $lbbtechCenterUrl = 'https://center.lbbtech.com';
        }
        $postParams = [];
        $postParams['token'] = $lbbtechCenterToken;
        $request_url = $lbbtechCenterUrl . '/open/listPriceStandard';
        
        $cache_key='lvcchong_api_equipment_listPriceStandard';
        $res=cache($cache_key);
        if(empty($res) || !isset($res['data'])) {
            $res = Http::curlPostOwn($request_url, http_build_query($postParams));
            if (!is_array($res)) {
                $res = json_decode($res, true);
                cache($cache_key, $res, 60);
            }
        }
        $attribute_list = $res['data'];
        foreach ($attribute_list as $v){
            if(!M('House_village_pile_attribute_set')->where(['attribute_id'=>$attribute_id,'village_id'=>$village_id,'is_del'=>1,'id'=>$v['id']])->find()){
                M('House_village_pile_attribute_set')->data([
                    'id'=>$v['id'],
                    'village_id'=>$village_id,
                    'attribute_id'=>$attribute_id,
                    'standard_name'=>$v['name'],
                    'standard_des'=>$v['name'],
                    'standard_value'=>serialize($v),
                    'add_time'=>time()
                ])->add();
            }
        }
        $list = M('House_village_pile_attribute_set')->where(['attribute_id'=>$attribute_id,'village_id'=>$village_id,'is_del'=>1])->select();
        $this->assign('list',$list);
        $this->assign('attribute_id',$attribute_id);
        $this->display();
    }

    public function add_standard()
    {
        $village_id = $this->house_session['village_id'];
        if(IS_POST){
            $id = $_POST['id']?$_POST['id']:0;
            $data['standard_name'] = $_POST['standard_name'];
            $data['standard_des'] = $_POST['standard_des'];
            $data['village_id'] = $village_id;
            $A = $_POST['A'];
            $B = $_POST['B'];
            $C = $_POST['C'];
            $data['attribute_id'] = $_POST['attribute_id'];
            $data['add_time'] = time();
            if($_POST['attribute_id'] == 1)
                $arr = [['单价','0~100',$A,'元/CNY'],['时间','0~10',$B,'小时/h'],['电量','0~100',$C,'度/kw.h']];
            elseif($_POST['attribute_id'] == 2)
                $arr = [['标准功率','0~8000',$A,'瓦/W']];
            elseif($_POST['attribute_id'] == 3)
                $arr = [['功率变化阀值','0~100',$A,'瓦/W'],['最小上报间隔','0~255',$B,'秒/sec']];
            elseif($_POST['attribute_id'] == 4)
                $arr = [['极限功率','100~3000',$A,'瓦/W']];
            elseif($_POST['attribute_id'] == 5)
                $arr = [['最大充电时长','64~86400',$A,'秒/sec']];
            elseif($_POST['attribute_id'] == 6)
                $arr = [['时长功率','0~36000',$A,'秒/sec'],['功率限制','0~100',$B,'瓦/W']];
            elseif($_POST['attribute_id'] == 7)
                $arr = [['时长','0~3600',$A,'秒/sec'],['功率','0~100',$B,'瓦/W']];
            else
                $arr = [['温度','-20~120',$A,'摄氏度/℃']];
            $data['standard_value'] = serialize($arr);
            if($id)
                $res = M('House_village_pile_attribute_set')->where(['id'=>$id])->save($data);
            else
                $res = M('House_village_pile_attribute_set')->add($data);
            if($res) {
                echo json_encode(['status'=>1]);
                exit;
            }else{
                echo json_encode(['status'=>2]);
                exit;
            }
        }else{
            $attribute_id = $_GET['attribute_id'];
            $id = $_GET['id'];
            $info = M('House_village_pile_attribute_set')->where(['id'=>$id])->find();
            $info['standard_value'] = unserialize($info['standard_value']);
            $this->assign('attribute_id',$attribute_id);
            $this->assign('info',$info);
            if(!$id)
                $this->display();
            else
                $this->display('edit_standard');
        }
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
        if($type == 'open'){
            $res = M('House_village_pile_attribute_set')->where(['id'=>$id])->save(['status'=>1]);
        }else{
            $res = M('House_village_pile_attribute_set')->where(['id'=>$id])->save(['status'=>2]);
        }
        if($res)
            echo json_encode(['status'=>1]);
        else
            echo json_encode(['status'=>2]);
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
        if(M('House_village_pile_attribute_set')->where(['id'=>$id])->save(['is_del'=>2])){
            $this->success('删除成功',U('attribute_standard_list',array('attribute_id'=>$attribute_id)));
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
        $equipment_list = M('House_village_pile_equipment')->field('id,equipment_name,equipment_num')->where(['village_id'=>$village_id,'is_del'=>1,'type'=>1])->select();
        foreach ($equipment_list as $k=>$v){
            $equipment_list[$k]['attribute_standard_id'] = $attribute_standard_id;
            $equipment_list[$k]['attribute_id'] = $attribute_id;
            $res = M('House_village_pile_equipment_attribute')->where(['equipment_id'=>$v['id'],'attribute_id'=>$attribute_id])->find();
            if(empty($res))
                $equipment_list[$k]['is_bind'] = 1;
            elseif($res['attribute_standard_id'] == $attribute_standard_id)
                $equipment_list[$k]['is_bind'] = 2;
            else
                $equipment_list[$k]['is_bind'] = 3;
        }
        $this->assign('equipment_list',$equipment_list);
        $this->display();
    }

    /**
     * 属性绑定设备
     * @author lijie
     * @date_time 2021/02/19
     */
    public function ajax_bang_attribute()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('attribute_list'));
        }
        $equipment_id = $_POST['equipment_id'];
        $attribute_standard_id = $_POST['attribute_standard_id'];
        $attribute_id = $_POST['attribute_id'];
        $arr_equipment_id = explode(',',$equipment_id);
        $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
        if (!$lbbtechCenterUrl) {
            $lbbtechCenterUrl = 'https://center.lbbtech.com';
        }
        $request_url = $lbbtechCenterUrl. '/open/updateDeviceInfo';
        foreach ($arr_equipment_id as $k=>$v){
            $equipment_info = M('House_village_pile_equipment')->where(['id'=>$equipment_id])->find();
            $postParams = [];
            $postParams['token'] = $lbbtechCenterToken;
            $postParams['chargePriceId'] = $attribute_standard_id;
            $postParams['simId'] = $equipment_info['equipment_sn'];
            $cache_key='lvcchong_api_equipment_'.$equipment_id.$postParams['simId'];
            import('ORG.Net.Http');
            $this->http = new Http();
            $res=cache($cache_key);
            if(empty($res) || !isset($res['success'])) {
                $res = Http::curlPostOwn($request_url, http_build_query($postParams));
                if (!is_array($res)) {
                    $res = json_decode($res, true);
                    cache($cache_key, $res, 60);
                }
            }
            if($res['success'] == true){
                $data['equipment_id'] = $v;
                $data['attribute_standard_id'] = $attribute_standard_id;
                $data['attribute_id'] = $attribute_id;
                $data['add_time'] = time();
                M('House_village_pile_equipment_attribute')->add($data);
            }else{
                echo json_encode(['status'=>0]);exit;
            }
        }
        echo json_encode(['status'=>1]);
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
        M('House_village_pile_equipment_attribute')->where($data)->delete();
        echo json_encode(['status'=>1]);
    }

    /**
     * 设备绑定的属性列表
     * @author lijie
     * @date_time 2021/04/24
     */
    public function e_attribute_list()
    {
        $equipment_id = $_GET['equipment_id'];
        if(!$equipment_id){
            $this->error('缺少必传参数',U('equipment_list'));
        }
        $equipment_attribute_list = D('House_village_equipment_attribute')->get_equipment_attribute_list($equipment_id);
        $this->assign('info',$equipment_attribute_list);
        $this->display('e_attribute_list');
    }

    public function e_attribute_del()
    {
        $equipment_id = $_GET['equipment_id'];
        D('House_village_equipment_attribute')->delOne($equipment_id);
        echo json_encode(['status'=>1]);
    }

    /**
     * 充电桩详情
     * @author lijie
     * @date_time 2021/04/26
     */
    public function pile_detail()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('equipment_list'));
        }
        $equipment_id = $_GET['equipment_id'];
        $equipment_info = M('House_village_pile_equipment')->where(['id'=>$equipment_id])->find();
        $equipment_info['add_time_txt'] = date('Y-m-d H:i:s',$equipment_info['add_time']);
        $this->assign('info',$equipment_info);
        $postParams = [];
        $postParams['token'] = $lbbtechCenterToken;
        $postParams['deviceId'] = $equipment_info['equipment_num'];
        import('ORG.Net.Http');
        $this->http = new Http();
        $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
        if (!$lbbtechCenterUrl) {
            $lbbtechCenterUrl = 'https://center.lbbtech.com';
        }
        $cache_key='lvcchong_api_equipment_detail_'.$equipment_info['equipment_num'];
        $res=cache($cache_key);
        $request_url = $lbbtechCenterUrl . '/open/deviceStatus';
        if(empty($res) || !isset($res['data'])) {
            $res = Http::curlPostOwn($request_url, http_build_query($postParams));
            fdump_api(['request_url'=>$request_url,'res'=>$res],'deviceStatus_230107',1);
            if (!is_array($res)) {
                $res = json_decode($res, true);
                cache($cache_key, $res, 30);
            }
        }
        if ($res && isset($res['code']) && $res['code'] == -1 && $equipment_info['status']==1) {
            M('House_village_pile_equipment')->where(['id'=>$equipment_id])->data(array('status'=>2))->save();
            D('House_maintenan')->faultLog(2,$equipment_id,'当前设备已离线');
        }elseif ($res && isset($res['data']) && $equipment_info['status']!=1){
            M('House_village_pile_equipment')->where(['id'=>$equipment_id])->data(array('status'=>1))->save();
            D('House_maintenan')->normalLog(2,$equipment_id);
        }
        fdump_api([$res],'deviceStatus_230107',1);
        $port_list = $res['data']['device']['data']['ports'];
        unset($port_list[0]);
        $port_list = array_values($port_list);
        $a = $b = $c = 0;
        foreach ($port_list as $v){
            if($v == 0)
                $a+=1;
            elseif($v == 1)
                $b+=1;
            else
                $c+=1;
        }
        $this->assign('a',$a);
        $this->assign('b',$b);
        $this->assign('c',$c);
        $this->assign('status',$port_list[0]);
        $this->assign('ports',$port_list);
        $device_info = $res['data']['device']['data']['device'];
        $device_info['village_id']=$equipment_info['village_id'];
        $device_info['deviceFamily']=$this->device_family[$equipment_info['device_family']];
        $device_info['status']=$this->device_status[$device_info['status']];
        $device_info['online']=$this->status[$device_info['online']];
        $device_info['updateTime']=date('Y-m-d H:i:s',$device_info['updateTime']/1000);
        $device_info['inTime']=date('Y-m-d H:i:s',$device_info['inTime']/1000);
        $this->assign('device_info',$device_info);
        $priceStandard['name'] = $res['data']['priceDetail']['name'];
        $priceStandard['type'] = $res['data']['priceDetail']['type'];
        $priceStandard['viewType'] = $res['data']['priceDetail']['viewType'];
        if ($priceStandard['viewType']==1){
            $priceStandard['work_type']='按时间计费'; 
        }elseif($priceStandard['type']==2&&$priceStandard['viewType']==2){
            $priceStandard['work_type']='按电量计费';
        }else{
            $priceStandard['work_type']='按功率计费';
        }
        $this->assign('priceStandard',$priceStandard);
        $priceStandardUniteList = $res['data']['priceDetail']['priceStandardUniteList'];
        $price=[];
        if (!empty($priceStandardUniteList)){
           foreach ($priceStandardUniteList as $vvv){
               $price_data=[];
               if ($vvv['fullBlackoutForButton']==1){
                   $price_data['name']='充满自停';
               }else{
                   if ($priceStandard['viewType']==1){
                       $price_data['name']='充电'.$vvv['paramView'].'小时';
                   }elseif($priceStandard['type']==2&&$priceStandard['viewType']==2){
                       $price_data['name']='充电'.$vvv['paramView'].'度';
                   }else{
                       $price_data['name']='充电'.$vvv['paramView'].'小时';
                   }
                  
               }
               $price_data['price']='收费'.$vvv['pricePay'].'元';
               $price[]=$price_data;
           }
        }
        $this->assign('priceStandardUniteList',$price);
       
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
        $base_url = D('House_village')->base_url;
        $url = $site_url.$base_url.'pages/village/smartCharge/chargeNum?id='.$equipment_id.'&socket='.$socket;
        $img_url = $site_url.U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($url)),'down'=>true));
        echo json_encode(['url'=>$img_url,'status'=>1]);
    }

    /**
     * 批量下载充电桩插座二维码
     */
    public function downloadCode()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('equipment_list'));
        }
        $equipment_id = $_POST['equipment_id'];
        $postParams['token'] = $lbbtechCenterToken;
        $postParams['deviceId'] = $_POST['equipment_num'];
        import('ORG.Net.Http');
        $this->http = new Http();
        $lbbtechCenterUrl = $this->config['lbbtechCenterUrl'];
        if (!$lbbtechCenterUrl) {
            $lbbtechCenterUrl = 'https://center.lbbtech.com';
        }
        $request_url = $lbbtechCenterUrl . '/open/deviceStatus';
        $res = Http::curlPostOwn($request_url, http_build_query($postParams));
        if(!is_array($res)){
            $res = json_decode($res,true);
        }
        $port_list = $res['data']['device']['data']['ports'];
        unset($port_list[0]);
        $port_list = array_values($port_list);
        $site_url = $this->config['site_url'];
        $base_url = D('House_village')->base_url;
        foreach ($port_list as $k=>$v){
            $socket = $k+1;
            $url = $site_url.$base_url.'pages/village/smartCharge/chargeNum?id='.$equipment_id.'&socket='.$socket;
            $dir_path = './upload/temp/pileQrcode/'.$_POST['equipment_num'].'/'.$_POST['equipment_num'];
            if(!file_exists($dir_path)){
                mkdir($dir_path,0777,true);
            }
            $filePath = $dir_path.'/插座'.$socket.'.png';

            $this->get_own_qrcode($url, 7,true, $filePath);

        }
        if($port_list){
            $this->get_zip_originalsize('./upload/temp/pileQrcode/'.$_POST['equipment_num'].'/','./upload/temp/pileQrcode/'.$_POST['equipment_num'].'/');
        }
        echo json_encode(['msg'=>'下载成功','status'=>1,'url'=>$site_url.'/upload/temp/pileQrcode/'.$_POST['equipment_num'].'/'.$_POST['equipment_num'].'.zip']);
    }

    /**
     * 将文件夹压缩
     * @param $path
     * @param $zipPath
     * @return bool
     */
    public function get_zip_originalsize($path, $zipPath) {

        !is_dir($zipPath) ? mkdir($zipPath, 0755, true) : '';


        $zip = new ZipArchive();

        $dir_list = scandir($path);

        if (empty($dir_list)) {
            return false;
        }

        foreach($dir_list as $dir_name) {
            if ($dir_name == '.' || $dir_name == '..') {
                continue;
            }
            $dir_path = $path.$dir_name;    //待压缩目录
            if (!is_dir($dir_path)) {
                continue;
            }
            $zipFile = $zipPath.$dir_name.'.zip';   //压缩包名称
            $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $file_list = scandir($dir_path);    //文件名
            foreach ($file_list as $file_name) {
                if ($file_name == '.' || $file_name == '..') {
                    continue;
                }

                $zip->addFile($dir_path.'/'.$file_name, $file_name);    //第二个参数使压缩文件名为待压缩文件原名（否则压缩包里会有多级目录）

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
        $order_type = trim($_GET['order_type']);
        $begin_time = trim($_GET['begin_time']);
        $end_time =  trim($_GET['end_time']);
        $where['o.village_id'] = $village_id;
        $where['o.pay_time'] = array('gt',100);
        $where['o.type'] = 1;
        if($pay_type){
            $where['o.pay_type'] = $pay_type;
        }
        if($order_type){
            $where['o.order_type'] = $order_type;
        }
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        if($find_type == 1 && !empty($find_value)){
            $where['u.nickname'] = $find_value;
        }
        if($find_type == 2 && !empty($find_value)){
            $where['u.phone'] = $find_value;
        }
        if($find_type == 3 && !empty($find_value)){
            $where['o.order_no'] = $find_value;
        }
        if($find_type == 4 && !empty($find_value)){
            $where['o.order_id'] = $find_value;
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
            ->limit($p->firstRow,$p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach ($order_list as $k=>&$v){
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id'=>$v['equipment_id']])->getField('equipment_name');
            $order_list[$k]['pay_time_txt'] =  date('Y-m-d H:i:s',$v['pay_time']);
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time']/60);
            if($v['order_type'] == 1)
                $v['order_type'] = '消费订单';
            else
                $v['order_type'] = '月卡购买';
            switch ($v['pay_type']){
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
                $order_list[$k]['phone']=$d_house_village->phone_desensitization_v($order_list[$k]['phone'],$village_id,$village_info);
            }
        }

        $where_total_money = $where;
        $where_total_money['pay_type'] = ['neq', 10];
        $total_money = M('House_village_pile_pay_order')
            ->join('AS o left join ' . C('DB_PREFIX') . 'user u on u.uid =o.uid')
            ->where($where_total_money)
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
        $this->assign('total_use_ele',$total_use_ele);
        $this->display();
    }

    public function order_export(){
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
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        if ($find_value) {
            if ($find_type == 1) {
                $where['u.nickname'] = $find_value;
            } else if ($find_type == 2) {
                $where['u.phone'] = $find_value;
            }
        }
        if($pay_type){
            $where['o.pay_type'] = $pay_type;
        }
        if($order_type){
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
            ->field('o.*,u.nickname,u.phone')
            ->order('o.id DESC')
            ->select();

        if(count($order_list) <= 0 ){
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

        $length = ceil(count($order_list)/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G',
            'H','I','J','K','L','M','N',
            'O','P','Q','R','S','T',
            'U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
            'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM',
            'BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ'
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

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }

            if (!empty($order_list)) {
                $index = 2;

                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }

                foreach ($order_list as $value) {
                    $equipment_name = M('House_village_pile_equipment')->where(['id'=>$value['equipment_id']])->getField('equipment_name');
                    $pay_time =  date('Y-m-d H:i:s',$value['pay_time']);
                    if($value['order_type'] == 1)
                        $order_type = '消费订单';
                    else
                        $order_type = '月卡购买';
                    switch ($value['pay_type']){
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
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
        if($res['success'] == true){
            M('House_village_pile_equipment')->where(['id'=>$equipment_id])->data([
                'status'=>$status
            ])->save();
            echo json_encode(['status'=>1,'msg'=>'操作成功']);
        }else{
            echo json_encode(['status'=>0,'msg'=>'服务异常']);
        }
    }

    /**
     * 订单详情
     */
    public function order_info()
    {
        $order_id = $_GET['order_id'];
        $order_info = M('House_village_pile_pay_order')->where(['id'=>$order_id])->find();
        $refund_order_info = M('House_village_pile_refund_order')->where(['order_id'=>$order_id])->find();
        $order_info['equipment_name'] = M('House_village_pile_equipment')->where(['id'=>$order_info['equipment_id']])->getField('equipment_name');
        $order_info['continued_time'] = getFormatNumber($order_info['continued_time']/60);
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
        if($order_info['status'] == 0){
            $order_info['end_time'] = '----';
            $order_info['status'] = '未结束';
        }else{
            $order_info['end_time'] =$order_info['end_time']>100 ? date('Y-m-d H:i:s',$order_info['end_time']) :'无';
            $order_info['status'] = '已结束';
        }
	    if ($order_info['order_id']){
		    $orderInfo = M('House_village_pay_order')->field('order_name,pay_time,order_no')->where(['order_id' => $order_info['order_id']])->find();
		    $order_info['order_pay_no'] = $order_info['order_serial'];
		    $order_info['order_name']   = $orderInfo['order_name'];
	    }else{
		    $order_info['order_pay_no'] = '';
		    $order_info['order_name']   = '';
	    }
        $order_info['refund_time_txt'] ='';
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
            $order_info['refund_time_txt'] =$refund_order_info['refund_time']>100 ? date('Y-m-d H:i:s',$refund_order_info['refund_time']):'';
        } else {
            $actualMoney = $order_info['use_money'];
        }
        $order_info['actualMoney'] = $actualMoney;
        $this->assign('order_info',$order_info);
        $this->display();
    }

    public function qcode_num()
    {
        $lbbtechCenterToken = $this->config['lbbtechCenterToken'];
        if (!$lbbtechCenterToken) {
            $this->error('请联系管理员 进行相关调用参数配置',U('equipment_list'));
        }
        $village_id = $this->house_session['village_id'];
        if(IS_POST){
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
            $length = ceil($num/1000);

            $pCoordinate_arr = [
                'A','B','C','D','E','F','G',
                'H','I','J','K','L','M','N',
                'O','P','Q','R','S','T',
                'U','V','W','X','Y','Z',
                'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
                'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
                'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM',
                'BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ'
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

                foreach ($pValue_arr as $k1=>$v1) {
                    $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
                }

                if ($num>0) {
                    $index = 2;
                    $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                    foreach ($cell_list as $cell) {
                        $objActSheet->getColumnDimension($cell)->setWidth(25);
                    }

                    for ($j = 0;$j<$num;$j++) {
                       $qcodeNum = time().rand(100000,999999);
                       $qcodeNum = str_shuffle($qcodeNum);
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, get_base_url('pages/village/smartCharge/smartCharge?village_id='.$village_id.'&qcodeType=0&qcodeNum='.$qcodeNum));
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
                header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        }else{
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
        $where['o.type'] = 1;
        $where['o.is_refund'] = array('gt',1);
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $is_refund = $_GET['is_refund'];
        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        if($find_type == 1 && !empty($find_value)){
            $where['u.nickname'] = $find_value;
        }
        if($find_type == 2 && !empty($find_value)){
            $where['u.phone'] = $find_value;
        }
        if($find_type == 3 && !empty($find_value)){
            $where['o.order_no'] = $find_value;
        }
        if($find_type == 4 && !empty($find_value)){
            $where['o.order_id'] = $find_value;
        }
        if($is_refund){
            $where['o.is_refund'] = $is_refund;
        }
        if($begin_time && empty($end_time)){
            $where['r.add_time'] = array('egt',strtotime($begin_time));
        }elseif ($end_time && empty($begin_time)){
            $where['r.add_time'] = array('elt',strtotime($end_time.' 23:59:59'));
        }elseif ($end_time && $begin_time){
            $where['r.add_time'] = array('between',[strtotime($begin_time),strtotime($end_time.' 23:59:59')]);
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
            ->limit($p->firstRow,$p->listRows)
            ->select();
        $this->assign('pagebar',$p->show());
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        foreach ($order_list as $k=>&$v){
            $order_list[$k]['equipment_name'] = M('House_village_pile_equipment')->where(['id'=>$v['equipment_id']])->getField('equipment_name');
            $order_list[$k]['refund_time_txt'] = $v['refund_time']>0 ? date('Y-m-d H:i:s',$v['refund_time']):date('Y-m-d H:i:s',$v['add_time']);
            $order_list[$k]['continued_time'] = getFormatNumber($v['continued_time']/60);
            if($v['is_refund'] == 1)
                $order_list[$k]['refund_status'] = '退款申请通过';
            if($v['is_refund'] == 2)
                $order_list[$k]['refund_status'] = '<span style="color: red">待审核</span>';
            if($v['is_refund'] == 3)
                $order_list[$k]['refund_status'] = '<span style="color: blue">已退款</span>';
            if($v['is_refund'] == 4)
                $order_list[$k]['refund_status'] = '<span style="color: green">未通过</span>';
            if(isset($v['phone']) && $order_list[$k]['phone']){
                $order_list[$k]['phone']=$d_house_village->phone_desensitization_v($order_list[$k]['phone'],$village_id,$village_info);
            }
        }
        $this->assign('order_list',$order_list);
        $this->display();
    }

    public function refund()
    {
        $order_id = $_POST['order_id'];
        $order_info = M('House_village_pile_pay_order')->where(['id'=>$order_id])->find();
        $config_info = M('House_village_pile_config')->where(['village_id' => $order_info['village_id']])->find();
        if ($order_info['type'] == 1) {
            // 0：未申请退款 1：已取消  2：发起退款（审核中） 3：退款成功 4：退款失败
            $is_refund = isset($order_info['is_refund']) ? intval($order_info['is_refund']) : 0;
            if ($is_refund == 3) {
                echo json_encode(['status' => 1, 'msg' => '当前订单已经退款成功，如需退款请线下操作']);
            }
            $notificationTxt = isset($order_info['notificationTxt']) ? intval($order_info['notificationTxt']) : 0;
            $db_house_village_pile_refund_order = M('House_village_pile_refund_order');
            $refund_money = 0;
            if ($is_refund == 4) {
                $whereRefund = ['order_id' => $order_id];
                $orderRefundInfo = $db_house_village_pile_refund_order->where($whereRefund)->order('refund_id DESC')->find();
                if (isset($orderRefundInfo['status']) && intval($orderRefundInfo['status']) == 4 && floatval($orderRefundInfo['refund_money']) > 0) {
                    $refund_money = $orderRefundInfo['refund_money'];
                } elseif ($is_refund == 4 && (!isset($orderRefundInfo['order_id']) || !$orderRefundInfo['order_id'])) {
                    $notificationTxtArr = json_decode($notificationTxt, true);
                    $payPrice = isset($notificationTxtArr['payPrice']) ? $notificationTxtArr['payPrice'] : 0;
                    $use_money = isset($order_info['use_money']) ? $order_info['use_money'] : 0;
                    $refund_money = $use_money - $payPrice;
                    if (floatval($refund_money) < 0) {
                        $refund_money = 0;
                    }
                }
            } else {
                $whereRefund = ['order_id' => $order_id];
                $orderRefundInfo = $db_house_village_pile_refund_order->where($whereRefund)->order('refund_id DESC')->find();
                if (isset($orderRefundInfo['status']) && intval($orderRefundInfo['status']) == 4 && floatval($orderRefundInfo['refund_money']) > 0) {
                    $refund_money = $orderRefundInfo['refund_money'];
                } elseif (isset($orderRefundInfo['order_id']) && $orderRefundInfo['order_id'] && $orderRefundInfo['status'] == 3) {
                    // 1：已取消 2：发起退款（审核中）3：退款成功 4：退款失败
                    echo json_encode(['status' => 1, 'msg' => '当前订单已经退款成功，如需退款请线下操作']);
                }
            }
            $tmp_continued_time=0;
            if ($refund_money <= 0) {
                $order_use_ele = isset($order_info['use_ele']) && floatval($order_info['use_ele']) > 0 ?  $order_info['use_ele'] : 0;
                $order_buy_ele = isset($order_info['buy_ele']) && floatval($order_info['buy_ele']) > 0 ?  $order_info['buy_ele'] : 0;
                $order_buy_time = isset($order_info['buy_time']) && floatval($order_info['buy_time']) > 0 ?  $order_info['buy_time'] : 0;
                $order_continued_time = isset($order_info['continued_time']) && floatval($order_info['continued_time']) > 0 ?  $order_info['continued_time'] : 0;
                $notificationTxt= $order_info['notificationTxt'] ? json_decode($order_info['notificationTxt'],true):'';
                if($notificationTxt && is_array($notificationTxt) && isset($notificationTxt['payPrice']) && $notificationTxt['payPrice']>0){
                    $refund_money=$order_info['use_money']- $notificationTxt['payPrice'];
                    if(isset($notificationTxt['endTime']) && isset($notificationTxt['startTime'])){
                        $tmp_continued_time=$notificationTxt['endTime']-$notificationTxt['startTime'];
                        $tmp_continued_time=$tmp_continued_time/1000;
                        $tmp_continued_time=round($tmp_continued_time);
                    }
                }
                if (floatval($order_use_ele) > 0 && (floatval($order_buy_ele) > 0 || floatval($order_buy_time) <= 0)){
                    if (($order_info['end_time']<100) || (!empty($config_info['low_consume_power']) && $config_info['low_consume_power'] >= $order_use_ele) || (!empty($config_info['des_power_level']) && $config_info['des_power_level'] >= $order_use_ele)) {
                        $refund_money = $order_info['use_money'];
                    } elseif (!empty($config_info['des_power_level']) && $config_info['des_power_level'] < $order_use_ele && $order_buy_ele > $order_use_ele) {
                        $level = ceil($order_use_ele / $config_info['des_power_level']);
                        $des_power_level = $config_info['des_power_level'] * $level;
                        if ($des_power_level >= $order_use_ele && $des_power_level <= $order_buy_ele) {
                            $refund_money = $order_info['use_money'] - ($order_info['use_money'] / $order_buy_ele) * ($config_info['des_power_level'] * $level);
                        }
                        fdump_api([$level,$des_power_level,$refund_money],'pile/notification',1);
                    }else if($order_buy_ele==0 && $order_buy_time ==0){
                        $refund_money = $order_info['use_money'];
                    }
                }else{
                    $continued_time = ceil($order_buy_time / 60);
                    $consume_time1 = ceil($order_continued_time / 60);
                    if (($order_info['end_time']<100) || (!empty($config_info['low_consume_time']) && $config_info['low_consume_time'] >= $consume_time1) || (!empty($config_info['des_time_level']) && $config_info['des_time_level'] >= $consume_time1)) {
                        $refund_money = $order_info['use_money'];
                    } elseif (!empty($config_info['des_time_level']) && $config_info['des_time_level'] < $consume_time1 && $continued_time > $consume_time1) {
                        $level = ceil($consume_time1 / $config_info['des_time_level']);
                        $des_time_level = $config_info['des_time_level'] * $level;
                        if ($des_time_level >= $consume_time1 && $des_time_level <= $continued_time) {
                            $refund_money = $order_info['use_money'] - ($order_info['use_money'] / $continued_time) * ($config_info['des_time_level'] * $level);
                        }
                    }elseif($continued_time==0 && $consume_time1==0){
                        $refund_money = $order_info['use_money'];
                    }
                    fdump_api([$continued_time,$consume_time1,$refund_money],'pile/notification',1);
                }
            }
            fdump_api(['msg' => '退款','order_id' => $order_id, 'order_info' => $order_info, 'config_info' => $config_info, 'refund_money' => $refund_money], 'pile/HouseRefundLog', 1);
            if ($refund_money<=0){
                echo json_encode(['status' => 1, 'msg' => '当前订单不符合退款要求，如需退款请线下操作']);
                exit;
            }
            $refund = D('Plat_order')->pile_order_refund(array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']), '', $refund_money);
            if (!$refund['error']) {
                $refund_data = [
                    'order_id' => $order_info['id'],
                    'status' => 4,
                    'refund_money' => $refund_money,
                    'refund_reason' => $refund['msg'],
                    'refund_time' => time(),
                    'add_time' => time(),
                ];
                $refund_id = $db_house_village_pile_refund_order->add($refund_data);
                $arrayArr=['is_refund' => 3,'refund_money'=>$refund_money];
                if($order_info['status']<1){
                    $arrayArr['status']=1;
                }
                if($tmp_continued_time>0){
                    $arrayArr['continued_time']=$tmp_continued_time;
                }
                $res = M('House_village_pile_pay_order')->where(['id' => $order_info['id']])->data($arrayArr)->save();
                if ($res) {
                    $db_house_village_pile_refund_order->where(['order_id' => $order_id])->data(['refund_time' => time(), 'status' => 3])->save();
                }
                $openid = M('User')->where(['uid' => $order_info['uid']])->find();
                D('House_village_pile_equipment')->handlePileChargeConsumePoint( $order_info['id'],$refund_money,'pile_refund');
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
                echo json_encode(['status' => 1, 'msg' => '退款成功']);
                exit;
            } else {
                echo json_encode(['status' => 1, 'msg' => $refund['msg']]);
                exit;
            }
        }
        echo json_encode(['status' => 1, 'msg' => '操作失败']);
        exit;
        //$refund = D('Plat_order')->pile_order_refund(array('business_type' => 'pile', 'business_id' => $order_info['order_id'], 'pile_pay_order_id' => $order_info['id']),'',$refund_info['refund_money']);
    }
}