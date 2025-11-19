<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/4/22 11:08
 */
namespace app\community\model\service;

use app\community\model\db\HouseMaintenanLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageAc;
use app\community\model\db\HouseVillageAcDevice;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use FreeDSx\Snmp\SnmpClient;
use think\Exception;
use think\facade\Db;

class ApDeviceService
{
    //无线ap配置信息
    public $ac_ip = '116.171.8.229';
    public $ac_port = 161;
    public $ac_community='futurecity';//团体名
    public $ac_count=50;//设备总数
    public $series_oid='1.3.6.1.4.1.3902.154.8.11.1.3.1.1.3.1.1.12';//设备序列号
    public $brand_oid='1.3.6.1.4.1.3902.154.8.11.1.3.1.1.3.1.1.13';//制造厂商
    public $type_oid='1.3.6.1.4.1.3902.154.8.11.1.3.1.1.3.1.1.14';//设备型号
    public $status_oid='1.3.6.1.4.1.3902.154.8.11.1.3.1.1.3.1.1.114';//AP与AC的关联状态


    /**
     * 查询列表
     * @author:zhubaodi
     * @date_time: 2022/4/22 13:56
     */
    public function getDeviceList($data){
        $db_house_village_ac=new HouseVillageAc();
        $db_house_village_single=new HouseVillageSingle();
        $db_house_village_public_area=new HouseVillagePublicArea();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['is_del','=',1];
        if (!empty($data['ac_num'])){
            $where[]=['ac_series','like','%'.$data['ac_num'].'%'];
        }
        if (!empty($data['ac_name'])){
            $where[]=['ac_name','like','%'.$data['ac_name'].'%'];
        }
        if (!empty($data['status'])){
            $where[]=['status','=',$data['status']];
        }
        $list=$db_house_village_ac->getList($where,'*',$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
        }
        if (!empty($list)){
            foreach ($list as &$v){
                $v['use_time']=$v['use_time']>1?date('Y-m-d H:i:s',$v['use_time']):'--';
                $v['last_time']=$v['last_time']>1?date('Y-m-d H:i:s',$v['last_time']):'--';
                $v['status']=$v['status']==1?'在线':'离线';
                if ($v['area_type']==1){
                    $area_info=$db_house_village_public_area->getOne(['village_id'=>$data['village_id'],'public_area_id'=>$v['ac_area']],'public_area_id,public_area_name');
                    $v['address']='小区-'.$area_info['public_area_name'];
                }elseif($v['area_type']==2){
                    $where=['village_id'=>$data['village_id'],'id'=>$v['ac_area']];
                    $single_info=$db_house_village_single->getOne($where,'id,single_name');
                    $v['address']='楼栋-'.$single_info['single_name'];
                }
            }
        }
        $count=$db_house_village_ac->getCount($where);
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;

    }

    /**
     * 查询设备详情
     * @author:zhubaodi
     * @date_time: 2022/4/22 13:58
     */
    public function getDeviceInfo($data){
        $db_house_village_ac=new HouseVillageAc();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['is_del','=',1];
        $where[]=['id','=',$data['id']];
        $info=$db_house_village_ac->get_one($where);
        if(!empty($info)){
            $info['use_time']=$info['use_time']>1?date('Y-m-d H:i:s',$info['use_time']):'--';
            $info['area_type']=(string)$info['area_type'];
            $info['ac_num']=$info['ac_series'];
           // $info['ac_area']=(string)$info['ac_area'];
        }
        return $info;
    }

    /**
     * 编辑设备信息
     * @author:zhubaodi
     * @date_time: 2022/4/22 14:06
     */
    public function editDevice($data){
        $db_house_village_ac=new HouseVillageAc();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['is_del','=',1];
        $where[]=['id','=',$data['id']];
        $info=$db_house_village_ac->get_one($where);
        if (empty($info)){
            throw new \think\Exception('设备信息不存在，无法编辑');
        }
        $data_arr=[];
        $data_arr['ac_name']=$data['ac_name'];
        $data_arr['ac_area']=$data['ac_area'];
        $data_arr['area_type']=$data['area_type'];
        $data_arr['remark']=$data['remark'];
        $data_arr['use_time']=strtotime($data['use_time']);
        $data_arr['last_time']=time();
        $res=$db_house_village_ac->save_one($where,$data_arr);
        return $res;
    }

    /**
     * 添加设备信息
     * @author:zhubaodi
     * @date_time: 2022/4/22 14:06
     */
    public function addDevice($data){
        $db_house_village_ac=new HouseVillageAc();
        $db_house_village_ac_device=new HouseVillageAcDevice();
        $device_info=$db_house_village_ac_device->get_one(['id'=>$data['ac_num'],'is_del'=>1]);
        if (empty($device_info)){
            throw new \think\Exception('当前设备未部署，无法添加');
        }
        $device_info_add=$db_house_village_ac->get_one(['ac_num'=>$device_info['ac_num'],'is_del'=>1]);
        if (!empty($device_info_add)){
            throw new \think\Exception('当前设备已存在，无法添加');
        }
        $data_arr=[];
        $data_arr['village_id']=$data['village_id'];
        $data_arr['ac_name']=$data['ac_name'];
        $data_arr['ac_type']='无线AP';
        $data_arr['ac_num']=$device_info['ac_num'];
        $data_arr['ac_series']=$device_info['ac_series'];
        $data_arr['status']=$device_info['status'];
        $data_arr['ac_area']=$data['ac_area'];
        $data_arr['area_type']=$data['area_type'];
        $data_arr['remark']=$data['remark'];
        $data_arr['use_time']=strtotime($data['use_time']);
        $data_arr['last_time']=time();
        $data_arr['add_time']=time();
        $res=$db_house_village_ac->addOne($data_arr);
        return $res;
    }

    /**
     * 删除设备
     * @author:zhubaodi
     * @date_time: 2022/4/22 14:44
     */
    public function delDevice($data){
        $db_house_village_ac=new HouseVillageAc();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['is_del','=',1];
        $where[]=['id','=',$data['id']];
        $info=$db_house_village_ac->get_one($where);
        if (empty($info)){
            throw new \think\Exception('设备信息不存在，无法编辑');
        }
        $data_arr=[];
        $data_arr['is_del']=2;
        $data_arr['last_time']=time();
        $res=$db_house_village_ac->save_one($where,$data_arr);
        return $res;
    }

    /**
     * 获取位置列表信息
     * @author:zhubaodi
     * @date_time: 2022/4/22 15:24
     */
    public function getAddressList($data){
        $db_house_village_single=new HouseVillageSingle();
        $db_house_village_public_area=new HouseVillagePublicArea();
        $data_list=[];
        if (!empty($data['id'])){
            $db_house_village_ac=new HouseVillageAc();
            $where[]=['village_id','=',$data['village_id']];
            $where[]=['is_del','=',1];
            $where[]=['id','=',$data['id']];
            $info=$db_house_village_ac->get_one($where);
            if (!empty($info['area_type'])){
                $data['type']=$info['area_type'];
            }
        }
        if ($data['type']==1){
            $area_list=$db_house_village_public_area->getList(['village_id'=>$data['village_id'],'status'=>1],'public_area_id,public_area_name');
            if (!empty($area_list)){
                $area_list=$area_list->toArray();
            }
            if (!empty($area_list)){
                foreach ($area_list as $v11){
                    $list=[];
                    $list['id']=$v11['public_area_id'];
                    $list['name']=$v11['public_area_name'];
                    $list['area_type']=1;
                    $data_list[]= $list;
                }
            }
        }else{
            $where=['village_id'=>$data['village_id'],'status'=>1];
            $single_list=$db_house_village_single->getList($where,'id,single_name');

            if (!empty($single_list)){
                $single_list=$single_list->toArray();
            }

            if (!empty($single_list)){
                foreach ($single_list as $v1){
                    $list=[];
                    $list['id']=$v1['id'];
                    $list['name']=$v1['single_name'];
                    $list['area_type']=2;
                    $data_list[]= $list;
                }
            }
        }
        return ['list'=>$data_list];
    }


    /**
     *添加设备获取设备下拉框列表
     * @author:zhubaodi
     * @date_time: 2022/4/25 17:55
     */
    public function getAddDeviceList($data){
        $db_house_village_ac=new HouseVillageAc();
        $db_house_village_ac_device=new HouseVillageAcDevice();
        $num_arr=$db_house_village_ac->getColumn(['village_id'=>$data['village_id'],'is_del'=>1],'ac_num');
        if (!empty($num_arr)){
            $where[]=['ac_num','not in',$num_arr];
        }
        $where[]=['is_del','=',1];
        $device_list=$db_house_village_ac_device->getList($where,'id,ac_num,ac_series');
        return $device_list;
    }

    /**
     * 获取设备
     * @author:zhubaodi
     * @date_time: 2022/4/25 15:46
     */
    public function getDevice(){
        $db_house_village_ac_device=new HouseVillageAcDevice();
        $snmp = new SnmpClient([
            'host' => $this->ac_ip,
            'version' => 2, //版本
            'port' => $this->ac_port , //端口
            'community' =>$this->ac_community,
        ]);
        for($i=1;$i<$this->ac_count;$i++){
            $oid_arr=$this->series_oid.'.'.$i;
            $oid_value=$snmp->getValue($oid_arr);
           //  print_r($oid_value);
            if (!empty($oid_value)){
                $device_info=$db_house_village_ac_device->get_one(['ac_num'=>$i,'is_del'=>1]);
                if (empty($device_info)){
                    $data_arr=[];
                    $data_arr['ac_num']=$i;
                    $data_arr['status']=1;
                    $data_arr['add_time']=time();
                    $data_arr['is_del']=1;
                    $data_arr['last_time']=time();
                    $data_arr['ac_series']=$oid_value;
                    $db_house_village_ac_device->addOne($data_arr);
                }
            }
        }
        return true;
    }

    /**
     * 获取设备状态
     * @author:zhubaodi
     * @date_time: 2022/4/25 15:46
     */
    public function getDeviceStatus(){
        $db_house_village_ac_device=new HouseVillageAc();
        $db_house_village=new HouseVillage();
        $houseMaintenanLog = new HouseMaintenanLog();
        $db_house_village_single=new HouseVillageSingle();
        $db_house_village_public_area=new HouseVillagePublicArea();
        $snmp = new SnmpClient([
            'host' => $this->ac_ip,
            'version' => 2, //版本
            'port' => $this->ac_port , //端口
            'community' =>$this->ac_community,
        ]);
        $device_list=$db_house_village_ac_device->getList(['is_del'=>1]);
        if (!empty($device_list)){
            $device_list=$device_list->toArray();
        }
        if (!empty($device_list)){
            foreach ($device_list as $v){
                $oid_arr=$this->status_oid.'.'.$v['ac_num'];
                $oid_value=$snmp->getValue($oid_arr);
                $status=1;
                if ($oid_value=='deassociated'){
                   $status=2;
                }elseif($oid_value=='associated'){
                    $status=1;
                }
                $data_arr=['status'=>$status,'last_time'=>time()];
                $db_house_village_ac_device->save_one(['id'=>$v['id']],$data_arr);
                $where_log=[];
                $where_log['village_id']=$v['village_id'];
                $where_log['device_id']=$v['id'];
                $log_info=$houseMaintenanLog->get_one($where_log);
                $village_name=$db_house_village->getOne($v['village_id'],'village_name');
                if ($v['area_type']==1){
                    $area_list=$db_house_village_public_area->getOne(['village_id'=>$v['village_id'],'status'=>1,'public_area_id'=>$v['ac_area']],'public_area_id,public_area_name');
                    $address='小区-'.$area_list['public_area_name'];
                }else{
                    $where=['village_id'=>$v['village_id'],'status'=>1,'id'=>$v['ac_area']];
                    $single_list=$db_house_village_single->getOne($where,'id,single_name');
                    $address='楼栋—'.$single_list['single_name'];
                }
                if (empty($log_info)&&$status==2){
                    $data_log=[];
                    $data_log['village_id']=$v['village_id'];
                    $data_log['village_name']=$village_name['village_name'];
                    $data_log['device_id']=$v['id'];
                    $data_log['device_type']=7;
                    $data_log['address']=$address;
                    $data_log['device_name']=$v['ac_name'];
                    $data_log['reason']='设备离线';
                    $data_log['next_key']=30;
                    $data_log['next_time']=time();
                    $data_log['add_time']=time();
                    $houseMaintenanLog->addOne($data_log);
                }elseif(!empty($log_info)){
                    if (!empty($log_info['next_key'])&&$status==1){
                        $data_log=[];
                        $data_log['next_key']=0;
                        $where_log_save=['id'=>$log_info['id']];
                        $houseMaintenanLog->save_one($where_log_save,$data_log);
                    }elseif (empty($log_info['next_key'])&&$status==2){
                        $data_log=[];
                        $data_log['village_id']=$v['village_id'];
                        $data_log['village_name']=$village_name['village_name'];
                        $data_log['device_id']=$v['id'];
                        $data_log['device_type']=7;
                        $data_log['address']=$address;
                        $data_log['device_name']=$v['ac_name'];
                        $data_log['reason']='设备离线';
                        $data_log['next_key']=30;
                        $data_log['next_time']=time();
                        $data_log['add_time']=time();
                        $houseMaintenanLog->addOne($data_log);
                    }

                }

            }
        }
        return true;
    }
}