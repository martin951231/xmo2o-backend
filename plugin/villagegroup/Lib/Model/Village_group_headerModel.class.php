<?php

class Village_group_headerModel extends Model
{
    //获取团长信息
    public function getInfo($header_id, $field = 'h.*',$hide = true, $uid=0)
    {
        if(empty($header_id) && empty($uid)) return [];
        if($header_id){
            $info = $this->alias("h")->join("left join ".C("DB_PREFIX")."user as u on h.uid=u.uid")->join("left join ".C("DB_PREFIX")."village_group_pick_address as a on h.id=a.header_uid")->where(['h.id' => $header_id, 'h.status'=>1])->field($field)->find();
        }
        else{
            $info = $this->alias("h")->join("left join ".C("DB_PREFIX")."user as u on h.uid=u.uid")->join("left join ".C("DB_PREFIX")."village_group_pick_address as a on h.id=a.header_uid")->where(['h.uid' => $uid, 'h.status'=>1])->field($field)->find();   
        }
        if(empty($info)){
            return [];
        }
        if (!$info['avatar']) {
            $info['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
        } else {
            if(stripos($info['avatar'],'/upload/') === 0){
                $info['avatar'] = file_domain().$info['avatar'];
            }
            else{
                $info['avatar'] = replace_file_domain($info['avatar']);
            }
        }
        if (C('config.village_group_hide_detail')) {
            if($info['pick_addr'] && $hide){
                if (mb_strlen($info['pick_addr'],'UTF8') > 3) {
                    if (mb_strlen($info['pick_addr'],'UTF8') == 4) {
                        $info['pick_addr'] =  mb_substr($info['pick_addr'],0,-3).'****';
                    }else{
                        $info['pick_addr'] =  mb_substr($info['pick_addr'],0,-4).'****';
                    }
                }
            }
            if($info['name'] && $hide){
                $info['name'] = str_replace_name($info['name']);
            }
            if($info['phone']){
                $info['phone'] = "";
            }
        }
        if($info['province_id'] || $info['city_id'] || $info['area_id']){
            $area = array_column(M("Area")->where(['area_id'=>['in',[$info['province_id'],$info['city_id'],$info['area_id']]]])->field('area_id,area_name')->select(),'area_name','area_id');
            if($info['province_id']){
                $info['province'] = $area[$info['province_id']];
            }
            if($info['city_id']){
                $info['city'] = $area[$info['city_id']];
            }
            if($info['area_id']){
                $info['area'] = $area[$info['area_id']];
            }
        }
        return $info;
    }

    //判断团长加价
    public function check_add_price($leader_id, $goods_id, $uid){
        if(empty($leader_id) || empty($goods_id) || empty($uid)) return [];

        $leader = $this->getInfo($leader_id, 'a.province_id,a.city_id,a.area_id,u.uid');
        if(empty($leader)) return [];
        if($leader['uid'] != $uid) return [];

        if($bind = PD("Village_group_goods_bind_area")->get_area_goods_bind_info($goods_id, $leader['province_id'], $leader['city_id'], $leader['area_id'])){
            if($bind['is_add_price'] != 1) return [];
            $return = [
                'min_price' => $bind['min_price'],
                'max_price' => $bind['max_price'],
                'old_price' => 0,
                'old_id' => 0
            ];
            //历史加价
            $history = M("Village_group_goods_increase_price")->where(['goods_id'=>$goods_id, 'header_uid'=>$leader_id])->find();
            if($history){
                $return['old_price'] = $history['increase_price'];
                $return['old_id'] = $history['pigcms_id'];
            }
            return $return;
        }
        else{
            return [];
        }
    }

    //判断用户是不是团长
    public function checkIsHeader($uid){
        if(empty($uid)) return 0;
        $info = $this->alias("h")->join("right join ".C("DB_PREFIX")."user as u on h.uid=u.uid")->where(['u.uid' => $uid,'h.status' => '1'])->find();
        return $info ? 1 : 0;
    }
}