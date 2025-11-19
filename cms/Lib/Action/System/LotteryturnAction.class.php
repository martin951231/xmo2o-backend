<?php 
/*
 *2018年11月30日14:59:35
 *平台幸运大转盘
 */
class LotteryturnAction extends BaseAction {

    public $token = '';

    public function index()
    {
        $activeType='Lotteryturn';
        $this->assign('activeType', $activeType);

        $where = array('mer_id' => -1,'type'=>1);
        $count = M('Lottery')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,10);
        $list = M('Lottery')->where($where)->limit($p->firstRow,$p->listRows)->select();
        $this->assign('list', $list);
        $this->assign('pagebar',$p->show());
        $this->display();
    }
    public function add()
    {
        $activeType='Lotteryturn';
        $this->assign('activeType', $activeType);

        if (IS_POST) {
            $data=D('Lottery');
            $_POST['statdate'] = strtotime($this->_post('statdate'));
            $_POST['enddate'] = strtotime($this->_post('enddate'));
            $_POST['token'] = -1;
            $_POST['mer_id'] = -1;
            $_POST['type'] = 1;
            $image = D('Image')->handle(-1, 'lottery', 1);
            if (!$image['error']) {
                if(is_array($image['url']['starpicurl'])){
                    $image['url']['starpicurl'] = $image['url']['starpicurl'][0];
                }
                if(is_array($image['url']['endpicurl'])){
                    $image['url']['endpicurl'] = $image['url']['endpicurl'][0];
                }
                if(is_array($image['url']['backpicurl'])){
                    $image['url']['backpicurl'] = $image['url']['backpicurl'][0];
                }
                $_POST = array_merge($_POST, $image['url']);
            }

            (!isset($_POST['starpicurl']) || empty($_POST['starpicurl'])) && $_POST['starpicurl'] = '/static/images/activity-lottery-start.jpg';
            (!isset($_POST['endpicurl']) || empty($_POST['endpicurl'])) && $_POST['endpicurl'] = '/static/images/activity-lottery-end.jpg';
            $error = $this->checknotnull($_POST);
            if ($error['error']) $this->error($error['msg']);
            if ($_POST['enddate'] < $_POST['statdate']) {
                $this->error('结束时间不能小于开始时间');
            } else {
                    if ($id = $data->add($_POST)) {
                        /*****************更新全局的图片记录**********************/
                        D('Image')->update_table_id($_POST['starpicurl'], $id, 'lottery');
                        D('Image')->update_table_id($_POST['endpicurl'], $id, 'lottery');
                        /*****************更新全局的图片记录**********************/


                        $data1['third_id'] = $id;
                        $data1['third_type'] = 'lottery';
                        $data1['keyword']=$this->_post('keyword');
                        D('Keywords')->add($data1);


                        if ($_POST['statdate'] < time()) {
                            $this->_start($id);
                        }
                        $this->frame_submit_tips(1,'活动创建成功！');
                    } else {
                        $this->frame_submit_tips(0,'服务器繁忙,请稍后重试~');
                    }
            }

        } else {
            $now = time();
            $lottery['statdate'] = $now;
            $lottery['enddate'] = $now + 30*24*3600;
            $this->assign('lottery', $lottery);
            $this->assign('activity', 'lottery');
            $this->display();
        }
    }
    public function edit()
    {
        $activeType='Lotteryturn';
        $tips = '幸运大转盘';

        $this->assign('tips', $tips);
        $this->assign('activeType', $activeType);


        if (IS_POST) {

            $data = D('Lottery');
            $_POST['id'] = $this->_get('id');
            $_POST['token'] = -1;
            $_POST['mer_id'] = -1;
            $_POST['statdate'] = strtotime($_POST['statdate']);
            $_POST['enddate'] = strtotime($_POST['enddate']);

            $error = $this->checknotnull($_POST);
            if ($error['error']) $this->error($error['msg']);
            if ($_POST['enddate'] < $_POST['statdate']) {
                $this->error('结束时间不能小于开始时间');
            } else {
                $where = array('id' => $_POST['id'], 'mer_id' => -1,'type' => 1);
                $check = $data->where($where)->find();
                if ($check == false) $this->error('非法操作');
                    $image = D('Image')->handle(-1, 'lottery', 1);
                    if (!$image['error']) {
                        if(is_array($image['url']['starpicurl'])){
                            $image['url']['starpicurl'] = $image['url']['starpicurl'][0];
                        }
                        if(is_array($image['url']['endpicurl'])){
                            $image['url']['endpicurl'] = $image['url']['endpicurl'][0];
                        }
                        if(is_array($image['url']['backpicurl'])){
                            $image['url']['backpicurl'] = $image['url']['backpicurl'][0];
                        }
                        $_POST = array_merge($_POST, $image['url']);
                    }
                if ($data->where($where)->save($_POST)) {
                    /*****************更新全局的图片记录**********************/
                    D('Image')->update_table_id($_POST['starpicurl'], $_POST['id'], 'lottery');
                    D('Image')->update_table_id($_POST['endpicurl'], $_POST['id'], 'lottery');
                    /*****************更新全局的图片记录**********************/
                    $data1['third_id'] = intval($_POST['id']);
                    $data1['third_type'] = 'lottery';
                    $da['keyword'] = $_POST['keyword'];
                    D('Keywords')->where($data1)->save($da);
                    $this->frame_submit_tips(1,'修改成功！');
                } else {
                    $this->frame_submit_tips(0,'操作失败！');
                }
            }
        } else {
            $id = $this->_get('id');
            $where = array('id' => $id,'mer_id' => -1);
            $data = M('Lottery');
            $check = $data->where($where)->find();
            if($check == false) $this->error('非法操作');
            $lottery = $data->where($where)->find();
            $this->assign('lottery', $lottery);

            $this->assign('activity', strtolower($activeType));
            $this->display('Lotteryturn:add');
        }

    }
    public function cheat($type)
    {
        switch ($type) {
            case 1:
                $activeType='Lottery';
                $url = U('Lottery/index');
                $tips = '幸运大转盘';
                break;
            case 2:
                $activeType='Guajiang';
                $url = U('Guajiang/index');
                $tips = '刮刮卡';
                break;
            case 3:
                $activeType='Coupon';
                $url = U('Coupon/index');
                $tips = '优惠券';
                break;
            case 4:
                $activeType='LuckyFruit';
                $url = U('LuckyFruit/index');
                $tips = '水果达人';
                break;
            case 5:
                $activeType='GoldenEgg';
                $url = U('GoldenEgg/index');
                $tips = '砸金蛋';
                break;
            case 6:
                $activeType='Research';
                $url = U('Research/index');
                $tips = '有奖调研';
                break;
            case 7:
                $activeType='AppleGame';
                $url = U('AppleGame/index');
                break;
            case 8:
                $activeType='Lovers';
                $url = U('Lovers/index');
                break;
            case 9:
                $activeType='Autumn';
                $url = U('Autumn/index');
                break;

        }
        $this->assign('url', $url);
        $this->assign('tips', $tips);
        $this->assign('activeType', $activeType);

        $id = intval($_GET['id']);
        $where = array('id' => $id,'mer_id' => $this->merchant_session['mer_id']);
        $thisLottery = M('Lottery')->where($where)->find();
        $this->assign('thisLottery',$thisLottery);
        //
        $records=M('Lottery_cheat')->where(array('lid'=>$thisLottery['id']))->order('prizetype asc')->select();
        $this->assign('records',$records);
    }
    public function deleteCheat()
    {
        $record = M('Lottery_cheat')->where(array('id'=>intval($_GET['id'])))->find();
        $thisLottery = M('Lottery')->where(array('id'=>$record['lid']))->find();
        if ($thisLottery['token'] != $this->merchant_session['mer_id']){
            $this->error('非法操作');
        }else{
            M('Lottery_cheat')->where(array('id' => intval($record['id'])))->delete();
            $this->success('删除成功');
        }
    }

    public function sn()
    {
        $Lottery_record_db=M('Lottery_record');
        $id=intval($this->_get('id'));
        $data=M('Lottery')->where(array('mer_id' => -1,'id'=>$id,'type'=>1))->find();
        $this->assign('thisLottery',$data);
        $recordWhere = 'token = -1 and lid = '.$id.' and sn!=""';
        $recordcount=M('Lottery_record')->where($recordWhere)->count();
        import('@.ORG.system_page');
        $p = new Page($recordcount,10);
        $record = $Lottery_record_db->where($recordWhere)->limit($p->firstRow,$p->listRows)->select();
        $recordcount = $data['fistlucknums']+$data['secondlucknums']+$data['thirdlucknums']+$data['fourlucknums']+$data['fivelucknums']+$data['sixlucknums'];
        $datacount = $data['fistnums']+$data['secondnums']+$data['thirdnums']+$data['fournums']+$data['fivenums']+$data['sixnums'];
        $sendCount = $Lottery_record_db->where(array('lid'=>$id,'sendstutas'=>1,'islottery'=>1))->count();
        $this->assign('sendCount',$sendCount);
        $this->assign('datacount',$datacount);
        $this->assign('recordcount',$recordcount);
        if ($record){
            $i=0;
            foreach ($record as $r){
                switch (intval($r['prizetype'])){
                    default:
                        $record[$i]['prizeName']=$r['prize'];
                        break;
                    case 1:
                        $record[$i]['prizeName']=$data['fist'];
                        break;
                    case 2:
                        $record[$i]['prizeName']=$data['second'];
                        break;
                    case 3:
                        $record[$i]['prizeName']=$data['third'];
                        break;
                    case 4:
                        $record[$i]['prizeName']=$data['four'];
                        break;
                    case 5:
                        $record[$i]['prizeName']=$data['five'];
                        break;
                    case 6:
                        $record[$i]['prizeName']=$data['six'];
                        break;
                }
                $i++;
            }
        }

        $activeType='Lotteryturn';
        $tips = '幸运大转盘';

        $this->assign('tips', $tips);
        $this->assign('activeType', $activeType);
        $this->assign('record',$record);
        $this->assign('pagebar',$p->show());
        $this->display();
    }

    public function sendnull()
    {
        $id = intval($this->_get('id'));
        $where = array('id' => $id,'token' => -1);
        $data['sendtime'] = '';
        $data['sendstutas'] = 0;
        $back = M('Lottery_record')->where($where)->save($data);
        if ($back == true) {
            $this->success('已经取消');
        } else {
            $this->error('操作失败');
        }
    }
    public function sendprize()
    {
        $id = $this->_get('id');
        $where=array('id' => $id,'token'=> -1);
        $data['sendtime'] = time();
        $data['sendstutas'] = 1;
        $back = M('Lottery_record')->where($where)->save($data);
        if ($back == true) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }
    public function endLottery()
    {
        $id = intval($this->_get('id'));
        $where = array('id' => $id,'token' => -1);
        $data = M('Lottery')->where($where)->save(array('status' => 0));
        if($data != false){
            $this->success('活动已结束');
        }else{
            $this->error('服务器繁忙,请稍候再试');
        }
    }
    public function startLottery()
    {
        $id = intval($this->_get('id'));
        $rt = $this->_start($id);
        if ($rt > 0) {
            $this->success('活动已经开始');
        } else {
            switch ($rt) {
                case -1:
                    $this->error('您的免费活动创建数已经全部使用完,请充值后再使用');
                    break;
                case -2:
                    $this->error('服务器繁忙,请稍候再试');
                    break;
            }
        }
    }
    public function _start($id){
        $error = 0;
        $where = array('id' => $id, 'token' => -1);
        $data = M('Lottery')->where($where)->save(array('status' => 1));
        if ($data != false) {
            return 1;
        } else {
            $error =- 2;
        }
        return $error;
    }

    public function del()
    {
        $id = intval($this->_get('id'));
        $where = array('id' => $id, 'token' => -1);
        $data = M('Lottery');
        $check = $data->where($where)->find();
        if($check == false) $this->error('非法操作');
        $back = $data->where($where)->delete();
        if ($back == true) {
            M('Keywords')->where(array('third_id'=>$id,'third_type'=>'lottery'))->delete();
            $this->success('删除成功');
        } else {
            $this->error('操作失败');
        }
    }
    public function snDelete(){
        $db=M('Lottery_record');
        $rt=$db->where(array('id'=>intval($_GET['id'])))->find();
        if (-1!=$rt['token']){
            exit('no permission');
        }
        switch ($rt['prize']){
            case 1:
                M('Lottery')->where(array('id'=>$rt['lid']))->setDec('fistlucknums');
                break;
            case 2:
                M('Lottery')->where(array('id'=>$rt['lid']))->setDec('secondlucknums');
                break;
            case 3:
                M('Lottery')->where(array('id'=>$rt['lid']))->setDec('thirdlucknums');
                break;
            case 4:
                M('Lottery')->where(array('id'=>$rt['lid']))->setDec('fourlucknums');
                break;
            case 5:
                M('Lottery')->where(array('id'=>$rt['lid']))->setDec('fivelucknums');
                break;
            case 6:
                M('Lottery')->where(array('id'=>$rt['lid']))->setDec('sixlucknums');
                break;

        }
        $db->where(array('id'=>intval($_GET['id'])))->delete();
        $this->success('操作成功');
    }
    public function exportSN(){
        //$objReader = PHPExcel_IOFactory::createReader('Excel5');
        header("Content-Type: text/html; charset=utf-8");
        header("Content-type:application/vnd.ms-execl");
        header("Content-Disposition:filename=huizong.xls");
        //   以下\t代表横向跨越一格，\n 代表跳到下一行，可以根据自己的要求，增加相应的输出相，要和循环中的对应哈
        //字段
        $letterArr=explode(',',strtoupper('a,b,c,d,e,f,g'));
        $arr=array(
            array('en'=>'sn','cn'=>'SN码(中奖号)'),
            array('en'=>'prize','cn'=>'奖项'),
            array('en'=>'sendstutas','cn'=>'是否已发奖品'),
            array('en'=>'sendtime','cn'=>'奖品发送时间'),
            array('en'=>'phone','cn'=>'中奖者手机号'),
            array('en'=>'wecha_name','cn'=>'中奖者微信码'),
            array('en'=>'time','cn'=>'中奖时间'),
        );
        $chengItem=array('piaomianjia','shuifei','yingshoujine','yingfupiaomianjia','yingfushuifei','yingfujine','dailishouru','fandian','jichangjianshefei','ranyoufei');

        $i=0;
        $fieldCount=count($arr);
        $s=0;
        //thead
        foreach ($arr as $f){
            if ($s<$fieldCount-1){
                echo iconv('utf-8','gbk',$f['cn'])."\t";
            }else {
                echo iconv('utf-8','gbk',$f['cn'])."\n";
            }
            $s++;
        }
        //
        $db=M('Lottery_record');
        $sns=$db->where(array('lid'=>intval($_GET['id']),'islottery'=>1))->order('id ASC')->select();
        if ($sns){
            if ($sns[0]['token']!=$this->token){
                exit('no permission');
            }
            foreach ($sns as $sn){
                $j=0;
                foreach ($arr as $field){
                    $fieldValue=$sn[$field['en']];
                    switch ($field['en']){
                        default:
                            break;
                        case 'time':
                        case 'sendtime':
                            if ($fieldValue){
                                $fieldValue=date('Y-m-d H:i:s',$fieldValue);
                            }else {
                                $fieldValue='';
                            }
                            break;
                        case 'wecha_name':
                        case 'prize':
                            $fieldValue=iconv('utf-8','gbk',$fieldValue);
                            break;
                    }
                    if ($j<$fieldCount-1){
                        echo $fieldValue."\t";
                    }else {
                        echo $fieldValue."\n";
                    }
                    $j++;
                }
                $i++;
            }
        }
        exit();
    }


    private function checknotnull($data)
    {
        if (empty($data['keyword'])) {
            $msg = '关键词不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['title'])) {
            $msg = '活动名称不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['title'])) {
            $msg = '兑奖信息不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['sttxt'])) {
            $msg = '中奖提示不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['statdate']) || empty($data['enddate'])) {
            $msg = '活动时间不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['aginfo'])) {
            $msg = '重复抽奖回复不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['endtite'])) {
            $msg = '活动结束公告主题不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['fist'])) {
            $msg = '一等奖奖品设置不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['fistnums'])) {
            $msg = '一等奖奖品数量不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['allpeople'])) {
            $msg = '预计活动的人数不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['canrqnums'])) {
            $msg = '每人最多允许抽奖次数不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        if (empty($data['parssword'])) {
            $msg = '兑奖密码不能为空';
            return array('error' => 1, 'msg' => $msg);
        }
        return array('error' => 0, 'msg' => '');
    }

    public function ajax_upload_pic(){
        if($_FILES['imgFile']['error'] != 4){

            $image = D('Image')->handle(-1, 'lottery', 0);
            if (!$image['error']) {
                exit(json_encode(array('error' => 0,'url' => $image['url']['imgFile'], 'title' => $image['title']['imgFile'])));
            }
            exit(json_encode($image));
        }else{
            exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
        }
    }
}
