<?php
/**
 * @author : liukezhu
 * @date : 2021/11/12
 */
namespace app\community\model\service;

use app\community\model\db\HouseUserTrajectoryLog;

class HouseUserTrajectoryLogService{


    public $model = '';
    public $business_type;
    public $device_type;
    public $type;
    public $client;

    public function __construct()
    {
        $this->model = new HouseUserTrajectoryLog();

        //来源类型
        $this->business_type=[
            1=>'system',  //系统后台
            2=>'merchant',//商家
            3=>'property',//物业
            4=>'village',//小区
            5=>'user',   //用户
            6=>'qywx',//企业微信

        ];
        //设备操作方式
        $this->device_type=[
            1=>'device_face_add',      //录入人脸
            2=>'device_remote_open',   //远程开门
            3=>'device_face_open',     //刷脸开门
            4=>'device_card_open',     //刷卡开门
        ];
        //操作类型
        $this->type=[
            1=>'user_add',  //用户添加
            2=>'user_edit', //用户编辑
            3=>'user_del',  //用户删除
            4=>'parking_position_add',//车位添加
            5=>'parking_position_edit',//车位编辑
            6=>'parking_position_del',//车位删除
        ];
        //登录设备
        $this->client=[
            10,//安卓-平台APP
            11,//安卓-社区APP
            12,//安卓-跑腿APP
            20,//ios-平台APP
            21,//ios-社区APP
            22,//ios-跑腿APP
            30,//小程序-平台小程序
            31 //小程序-物业小程序
        ];

    }

    //todo 写入记录
    public function addLog($business_type,$business_id=0,$business_role=0,$uid=0,$role_id=0,$type='',$content='',$source='',$client=0,$device_id=''){
        if(!isset($this->business_type[$business_type]) && !empty($this->business_type[$business_type])){
            return ['error'=>false,'mag'=>'来源类型不存在'];
        }
        $business_type=$this->business_type[$business_type];
        if(strpos($type, '-') !== false){ //例如 a7添加人脸：a7-device_face_add
            $type_all=explode('-',$type);
            if(!isset($type_all[0]) || !isset($type_all[1]) || !array_search($type_all[1],$this->device_type)){
                return ['error'=>false,'mag'=>'操作类型不合法'];
            }
            $type_=$type_all[0].'_'.$this->device_type[array_search($type_all[1],$this->device_type)];
        }elseif ($type=isset($this->type[$type]) && !empty($this->type[$type])){
            $type_=$type;
        }else{
            return ['error'=>false,'mag'=>'操作类型不存在'];
        }
        if(intval($client) > 0){
            if (!in_array($client,$this->client)){
                return ['error'=>false,'mag'=>'登录设备不存在'];
            }
        }
        $data=[
            'business_type'=>$business_type,
            'business_id'=>$business_id,
            'business_role'=>$business_role,
            'uid'=>$uid,
            'role_id'=>$role_id,
            'type'=>$type_,
            'content'=>$content,
            'source'=>$source,
            'client'=>$client,
            'device_id'=>$device_id,
            'add_ip'=>$_SERVER["REMOTE_ADDR"],
            'create_at'=>$_SERVER['REQUEST_TIME']
        ];
        $rel= $this->model->addOne($data);
        if(!$rel){
            return ['error'=>false,'mag'=>'操作失败'];
        }
        else{
            return ['error'=>true,'mag'=>'操作成功'];
        }

    }


    /**
     * 获取用户行为轨迹
     * @author: liukezhu
     * @date : 2021/11/12
     * @param $where
     * @param int $type 1:移动端接口 0：后端接口
     * @param $field
     * @param $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getUserLog($where,$type=0,$field,$order,$page=0,$limit=10){
        $list = $this->model->getList($where,$field,$order,$page,$limit);
        $count=0;
        if($list){
            $list=$list->toArray();
            foreach ($list as &$v){
                if(isset($v['create_at']) && !empty($v['create_at'])){
                    if($type == 1){
                        $v['create_day']=date('Y-m-d',$v['create_at']);
                        $v['create_time']=date('H:i',$v['create_at']);
                        unset($v['create_at']);
                    }
                    else{
                        $v['create_at']=date('Y-m-d H:i:s',$v['create_at']);
                    }
                }
            }
            unset($v);
            $count= $this->model->getCount($where);
        }
        return ['list'=>$list,'count'=>$count];

    }


}