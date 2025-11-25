<?php

/**
 * 出入证相关
 * Class Pass
 */
class House_village_passModel extends Model
{
    //出入证列表
    public function getPassList($village_id,$field=true,$order='use_status asc,status asc,pass_id desc',$pageSize = 20)
    {
        $where['village_id'] = $village_id;
        $where['status'] = ['neq','-1'];
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $pass_list = $this->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $base_url = D('House_village')->base_url;
        foreach ($pass_list as $k=>&$val){
            if($val['status'] == 0) {
                $bind_member = D('House_village_pass_bind_member')->where(['pass_id' => $val['pass_id']])->order('id desc')->find();
                if ($bind_member) {
//                $day = ceil(($bind_member['due_time']+86400-time())/86400);
                    $day = ceil(($bind_member['due_time'] - time()) / 86400);
                    if ($day > 0) {
                        $val['text'] = '距离到期时间还有' . $day . '天';
                    } else {
                        $this->where(['pass_id' => $val['pass_id']])->data(['use_status' => 2])->save();
                        $val['use_status'] = 2;
                        $val['text'] = '';
                    }
                }
            }
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
            //$val['pass_url_code'] = C('config.site_url').U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($val['pass_url']))));
        }
        $list['list'] = $pass_list;
        $list['pagebar'] = $p->show();
        return $list;
    }
    //添加出入证
    public function addPass($num,$village_id)
    {
        $arr = [];
        for($i=1;$i<=$num;$i++){
            $code_num = $this->getRandNumber();
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'addVisitorinfoTwo',
                'isAll' => true,
                'urlParam' => 'village_id='.$village_id.'&code_num='.$code_num,
            ];
            $addVisitorinfoTwoUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $url = $addVisitorinfoTwoUrl;
            $pass_url = $this->create_code($code_num,$url);
            $arr[] = [
              'code_num'=>$code_num,
              'pass_url'=>$pass_url,
              'village_id'=>$village_id,
              'add_time'=>time(),
            ];
        }
        $res = 0;
        if($arr && count($arr)>0){
            $res = $this->addAll($arr);
        }
        return $res;
    }
    public function create_code($code_no,$url)
    {
        import('@.ORG.phpqrcode');
        $filename = 'upload/qrcode/pass_qrcode/';
        if(!file_exists($filename)){
            mkdir($filename,0777,true);
        }
        $errorLevel = "L";
        $size = "8";
        $filename = $filename.$code_no.'.png';
        QRcode::png($url, $filename, $errorLevel, $size);
        $QR = $filename;        //已经生成的原始二维码图片文件
        $data =  C('config.site_url').'/'.$QR;
        return $data;
    }
    //获取详情
    public function getInfo($where)
    {
        $info = $this->where($where)->find();
        return $info;
    }
    //编辑数据
    public function edit($pass_id,$data)
    {
        $where['pass_id'] = $pass_id;
        $data['edit_time'] = time();
        if($data['status'] == 1 || $data['status'] == 2){
            $data['use_status'] = 2;
        }
        $info = $this->where($where)->find();
        D('House_village_pass_bind_member')->where(['pass_id'=>$info['pass_id']])->data(['status'=>2])->save();
        $res = $this->where($where)->data($data)->save();
        return $res;
    }
    /**
     * Notes: 生成随机8位数
     * @param int $start
     * @param int $end
     * @param int $length
     * @return mixed
     * @datetime: 2020/12/9 14:57
     */
    public function getRandNumber($start=0,$end=9,$length=8){
        $connt = 0;
        $temp = array();
        while($connt < $length){
            $temp[] = mt_rand($start, $end);
            $data = array_flip(array_flip($temp));
            $connt = count($data);
        }
        shuffle($data);
        $str=implode(",", $data);
        $number=str_replace(',', '', $str);
        return $number;
    }
}
