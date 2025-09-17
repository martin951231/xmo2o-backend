<?php
/**
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/5/14 13:54
 */

namespace app\community\model\service;


use app\community\model\db\HouseVillageDoor;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillagePublicArea;
class DoorService
{
    /**
     * 门禁获取
     * @author: wanziyang
     * @date_time: 2020/5/14 14:51
     * @param array $where 搜索条件
     * @param int $page 分页数
     * @param string $field 查询字段
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getHouseVillageDoorList($where,$page=0,$field ='a.*,hvf.floor_name,hvf.floor_layer,hvs.id as single_id,hvs.single_name,c.public_area_name',$order='a.door_id DESC',$page_size=10) {
        $db_house_village_door = new HouseVillageDoor();
        $list = $db_house_village_door->getList($where,$page,$field, $order,$page_size);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as &$v){
                if ( $v['floor_id'] == -1 && !$v['public_area_name']) {
                    $v['floor_name']	=	cfg('house_name');
                    $v['floor_layer']	=	'大门';
                } elseif($v['floor_id'] == -1 && $v['public_area_name']) {
                    $v['floor_name']	=	cfg('house_name');
                    $v['floor_layer']	=	$v['public_area_name'];
                }
                if (!$v['public_area_name']) {
                    $v['public_area_name'] = '';
                }
//                unset($v['door_psword']);
                if ($v['public_area_id']>0 && $v['public_area_name']) {
                    $v['address_txt'] = $v['public_area_name'];
                } elseif ($v['floor_id']>0) {
                    $v['address_txt'] = ($v['single_name'] ? $v['single_name'] : $v['floor_layer']) . ' ' . $v['floor_name'];
                } elseif ($v['floor_id']<=0) {
                    $v['address_txt'] = $v['floor_layer'] . ' ' . $v['floor_name'];
                }
                // 去除前后多余空格
                $v['address_txt'] = trim($v['address_txt']);
            }
        }
        return $list;
    }

    /**
     * 添加信息
     * @author: wanziyang
     * @date_time: 2020/5/15 11:55
     * @param array $data 添加内容
     * @return bool|integer
     */
    public function addHouseVillageDoorOne($data) {
        $db_house_village_door = new HouseVillageDoor();
        $door_id = $db_house_village_door->addOne($data);
        return $door_id;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/15 14:16
     * @param array $where 修改条件
     * @param array $data 修改内容
     * @return bool|integer
     */
    public function saveHouseVillageDoorOne($where,$data) {
        $db_house_village_door = new HouseVillageDoor();
        $set = $db_house_village_door->saveOne($where,$data);
        return $set;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/5/15 14:15
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getHouseVillageDoorOne($where,$field =true) {
        $db_house_village_door = new HouseVillageDoor();
        $info = $db_house_village_door->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            if (isset($info['floor_id']) && $info['floor_id']>0) {
                $db_house_village_floor = new HouseVillageFloor();
                $db_house_village_single = new HouseVillageSingle();
                $where_floor = [];
                $where_floor[] = ['floor_id','=',$info['floor_id']];
                $field_floor = 'single_id,floor_name';
                $floor_info = $db_house_village_floor->getOne($where_floor,$field_floor);
                if (isset($floor_info['single_id']) && $floor_info['single_id']>0) {
                    $info['single_id'] = $floor_info['single_id'];
                    $field_single = 'id,single_name';
                    $where_single = [];
                    $where_single[] = ['id','=',$info['single_id']];
                    $single_info = $db_house_village_single->getOne($where_single,$field_single);
                    if ($single_info && isset($single_info['single_name'])) {
                        $info['single_name'] = $floor_info['single_name'];
                    }
                }
                if ($floor_info && isset($floor_info['floor_name'])) {
                    $info['floor_name'] = $floor_info['floor_name'];
                }
                $info['address'] = '';
                if (isset($info['single_name'])) {
                    $info['address'] .= $info['single_name'];
                }
                if (isset($info['floor_name'])) {
                    $info['address'] .= $info['floor_name'];
                }
            } elseif (isset($info['public_area_id']) && $info['public_area_id']>0) {
                $db_house_village_public_area = new HouseVillagePublicArea();
                $field_public_area = 'public_area_id,public_area_name';
                $where_public_area = [];
                $where_public_area[] = ['public_area_id','=',$info['public_area_id']];
                $public_area_info = $db_house_village_public_area->getOne($where_public_area,$field_public_area);
                if ($public_area_info && isset($public_area_info['public_area_name'])) {
                    $info['public_area_name'] = $public_area_info['public_area_name'];
                }
                $info['address'] = '';
                if (isset($info['public_area_name'])) {
                    $info['address'] .= $info['public_area_name'];
                }
            }
        }
        return $info;
    }

    /**
     * 删除信息
     * @author: wanziyang
     * @date_time: 2020/5/14 15:57
     * @param array $where 删除内容的条件
     * @return bool
     */
    public function delHouseVillageDoorOne($where) {
        $db_house_village_door = new HouseVillageDoor();
        $del = $db_house_village_door->delOne($where);
        return $del;
    }


    //时间转换
    public function convert($time,$door_control_str){

        $twoy = decbin(date('y',$time));
        $twom = decbin(date('m',$time));
        $twod = decbin(date('d',$time));

        $len_nian = strlen($twoy);
        if($len_nian == 7){
            $str_nian = $twoy;
        }else{
            $str_nian = $this->str_prefix($twoy,intval( 7-$len_nian),'0');
        }

        $len_yue = strlen($twom);
        if($len_yue == 4){
            $str_yue = $twom;
        }else{
            $str_yue = $this->str_prefix($twom,intval( 4-$len_yue),'0');
        }

        $len_ri = strlen($twod);
        if($len_ri == 5){
            $str_ri = $twod;
        }else{
            $str_ri = $this->str_prefix($twod,intval( 5-$len_ri),'0');
        }

        // '二进制组合，日的第一部分放在最前+年+日的第二部分+月 -- 结果：';
        $total_str = substr($str_ri, 0,1).$str_nian.substr($str_ri, 1,4).$str_yue;
        // '组合之后转换成十六进制：';
        $ymdshiliu = dechex(bindec($total_str));
        //数据组合

        $data = '5100'.$ymdshiliu.'000000'.'000000000000000000'.$this->door_control_convert($door_control_str);
        // $data = '5000'.$ymdshiliu.'000000'.'000000000000000000'.'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF';
        return $data;

    }

    public function str_prefix($str, $n=1, $char="0"){
        for ($x=0;$x<$n;$x++){$str = $char.$str;}
        return $str;
    }

    //门禁机转换
    public function door_control_convert($door_control_str){

        if(strlen($door_control_str) > 0){
            $door_array = explode(',', $door_control_str);
        }
        $door_two_str = '';
        for ($i=0; $i <= 127; $i++) {
            if(in_array($i, $door_array)){
                $door_two_str .= '1';
            }else{
                $door_two_str .= '0';
            }
        }

        $start_position = 0;
        $total_door_str = '';
        for ($c=0; $c < 16; $c++) {
            $byte = substr($door_two_str, $start_position,8);
            $byte_sixteen = dechex(bindec($byte));
            if(strlen($byte_sixteen) < 2){
                $byte_sixteen = $this->str_prefix($byte_sixteen,intval( 2-strlen($byte_sixteen)),'0');
            }
            $total_door_str .= $byte_sixteen;
            $start_position += 8;
        }

        return $total_door_str;
    }

    //密码转换
    public function pwd_convert($door_pwd){
        $door_pwd = explode(',', $door_pwd);

        $pwd1 = $door_pwd[0];
        $pwd2 = $door_pwd[1];
        $pwd3 = $door_pwd[2];
        $pwd4 = $door_pwd[3];
        $pwd5 = $door_pwd[4];
        $pwd6 = $door_pwd[5];

        $pwd_16_1 = dechex($pwd1);
        if(strlen($pwd_16_1) == 2){
            $pwd_16_1 = $pwd_16_1;
        }else{
            $pwd_16_1 = $this->str_prefix($pwd_16_1,intval( 2-strlen($pwd_16_1)),'0');
        }

        $pwd_16_2 = dechex($pwd2);
        if(strlen($pwd_16_2) == 2){
            $pwd_16_2 = $pwd_16_2;
        }else{
            $pwd_16_2 = $this->str_prefix($pwd_16_2,intval( 2-strlen($pwd_16_2)),'0');
        }

        $pwd_16_3 = dechex($pwd3);
        if(strlen($pwd_16_3) == 2){
            $pwd_16_3 = $pwd_16_3;
        }else{
            $pwd_16_3 = $this->str_prefix($pwd_16_3,intval( 2-strlen($pwd_16_3)),'0');
        }

        $pwd_16_4 = dechex($pwd4);
        if(strlen($pwd_16_4) == 2){
            $pwd_16_4 = $pwd_16_4;
        }else{
            $pwd_16_4 = $this->str_prefix($pwd_16_4,intval( 2-strlen($pwd_16_4)),'0');
        }

        $pwd_16_5 = dechex($pwd5);
        if(strlen($pwd_16_5) == 2){
            $pwd_16_5 = $pwd_16_5;
        }else{
            $pwd_16_5 = $this->str_prefix($pwd_16_5,intval( 2-strlen($pwd_16_5)),'0');
        }

        $pwd_16_6 = dechex($pwd6);
        if(strlen($pwd_16_6) == 2){
            $pwd_16_6 = $pwd_16_6;
        }else{
            $pwd_16_6 = $this->str_prefix($pwd_16_6,intval( 2-strlen($pwd_16_6)),'0');
        }

        $pwd_total = $pwd_16_1.$pwd_16_2.$pwd_16_3.$pwd_16_4.$pwd_16_5.$pwd_16_6;

        return $pwd_total;

    }

}