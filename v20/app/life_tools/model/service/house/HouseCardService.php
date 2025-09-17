<?php


namespace app\life_tools\model\service\house;


use app\common\model\db\Area;
use app\common\model\db\User;
use app\life_tools\model\db\LifeToolsHouseBuilding;
use app\life_tools\model\db\LifeToolsHouseCardActivity;
use app\life_tools\model\db\LifeToolsHouseCardUser;
use app\life_tools\model\db\LifeToolsHouseFloor;
use app\life_tools\model\db\LifeToolsHousesList;
use think\facade\Db;

class HouseCardService
{
    /**
     * 活动列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getList($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where[] = ['is_del','=',0];
        $list = (new LifeToolsHouseCardActivity())->where($where)->field('*')->paginate($pageSize)->toArray();
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['house_name'] = '';
            $list['data'][$key]['house_ids'] = $item['house_ids']?explode(',',$item['house_ids']):[];
            if($list['data'][$key]['house_ids']){
                $house_name = (new LifeToolsHousesList())->where([['status','=',1],['is_del','=',0],['houses_id','in',$list['data'][$key]['house_ids']]])->column('title');
                $list['data'][$key]['house_name'] = $house_name?implode(',',$house_name):'';
            }
            $list['data'][$key]['start_time'] = $item['start_time']?date('Y-m-d H:i',$item['start_time']):'';
            $list['data'][$key]['end_time'] = $item['end_time']?date('Y-m-d H:i',$item['end_time']):'';
            $list['data'][$key]['time_txt'] = $list['data'][$key]['start_time'].'~'.$list['data'][$key]['end_time'];
        }
        return $list;
    }

    /**
     * 删除
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function del($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的活动！'));
        }
        try {
            (new LifeToolsHouseCardActivity())->where(['id'=>$id])->save(['is_del'=>1]);
            return true;
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 添加/编辑活动
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function save($params){
        $id = $params['id']??0;
        $saveData = [
            'name' => $params['name'],
            'start_time' => $params['start_time']?strtotime($params['start_time']):'',
            'end_time' => $params['end_time']?strtotime($params['end_time']):'',
            'house_ids' => $params['house_ids']
        ];
        if(!$saveData['name']){
            throw new \think\Exception(L_('活动名称不饿能为空！'));
        }
        if(!$saveData['start_time']||!$saveData['end_time']||$saveData['start_time']>=$saveData['end_time']){
            throw new \think\Exception(L_('日期格式有误！'));
        }
        if(!$saveData['house_ids']||!is_array($saveData['house_ids'])){
            throw new \think\Exception(L_('请选择楼盘！'));
        }
        $saveData['house_ids'] = implode(',',$saveData['house_ids']);
        try {
            if($id){
                $info = (new LifeToolsHouseCardActivity())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('活动不存在/已删除！'));
                }
                (new LifeToolsHouseCardActivity())->where(['id'=>$id])->save($saveData);
            }else{
                $saveData['add_time'] = time();
                (new LifeToolsHouseCardActivity())->save($saveData);
            }
            return true;
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 选择楼盘列表
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseList($params){
        $id = $params['id'];
        $h_where[] = ['is_del','=',0];
        $h_where[] = ['end_time','>',time()];
        if($id){
            $h_where[] = ['id','<>',$id];
        }
        $house_ids = (new LifeToolsHouseCardActivity())->where($h_where)->column('house_ids');
        $house_ids_arr = [];
        if($house_ids){
            foreach ($house_ids as $item){
                $house_ids_arr = array_merge($house_ids_arr,explode(',',$item));
            }
        }
        if($house_ids_arr){
            $where[] = ['houses_id','not in',$house_ids_arr];
        }
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        $list = (new LifeToolsHousesList())->field('houses_id,title')->where($where)->select()->toArray();
        return $list;
    }

    /**
     * 校验楼盘是否参与活动
     * @param $id
     * @return mixed
     */
    public function checkActivity($id){
        if(!$id){
            return [];
        }
        $where = [
            ['','exp',Db::raw("FIND_IN_SET('".$id."',house_ids)")],
            ['start_time','<',time()],
            ['end_time','>',time()]
        ];
        $activity = (new LifeToolsHouseCardActivity())->where($where)->findOrEmpty()->toArray();
        return $activity;
    }

    /**
     * 楼盘楼栋详情
     * @param $params
     * @throws \think\Exception
     */
    public function getHouseInfo($params){
        $id = $params['houses_id'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $house_info = (new LifeToolsHousesList())->where(['houses_id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$house_info){
            throw new \think\Exception(L_('楼盘不存在/已删除！'));
        }
        $activity = $this->checkActivity($id);
        if(!$activity){
            throw new \think\Exception(L_('该楼盘未参与活动/活动已结束！'));
        }
        $where = [
            'b.house_id' => $id,
            'b.status' => 1,
            'u.status' => 1,
            'f.status' => 1,
            'r.status' => 1,
        ];
        $order = 'b.sort asc,b.id asc,u.sort asc,u.id asc,f.sort asc,f.id asc,r.sort asc,r.id asc';
        $field = 'b.serial_number as b_serial_number,b.name as b_name,u.serial_number as u_serial_number,u.p_number as u_p_number,u.name as u_name,f.serial_number as f_serial_number,f.p_number as f_p_number,f.name as f_name,r.serial_number as r_serial_number,r.p_number as r_p_number,r.name as r_name';
        $info = (new LifeToolsHouseBuilding())->getInfo($where,$field,$order);
        $building = $this->resultInfo($info);
        
        return $building;
    }
    
    public function resultInfo($info,$fiel_name='serial_number'){
        $building = $unit = $floor = $room = [];
        foreach ($info as $item){
            $building[$item['b_serial_number']] = [
                'value' => $item['b_'.$fiel_name],
                'label' => $item['b_name'],
            ];
            $unit[$item['u_p_number']][$item['u_serial_number']] = [
                'value' => $item['u_'.$fiel_name],
                'label' => $item['u_name'],
            ];
            $floor[$item['u_p_number'].'_'.$item['f_p_number']][$item['f_serial_number']] = [
                'value' => $item['f_'.$fiel_name],
                'label' => $item['f_name'],
            ];
            $room[$item['u_p_number'].'_'.$item['f_p_number'].'_'.$item['r_p_number']][$item['r_serial_number']] = [
                'value' => $item['r_'.$fiel_name],
                'label' => $item['r_name'],
            ];
        }
        foreach ($floor as $key=>$item){
            foreach ($item as $k=>$v){
                $floor[$key][$k]['children'] = [];
                if(isset($room[$key.'_'.$k])){
                    $floor[$key][$k]['children'] = array_values($room[$key.'_'.$k]);
                }
                if($fiel_name=='id'){
                    $floor[$key][$k]['children'] = array_merge([['value' => 0, 'label' => '全部']],$floor[$key][$k]['children']);
                }
            }
        }
        foreach ($unit as $key=>$item){
            foreach ($item as $k=>$v){
                $unit[$key][$k]['children'] = [];
                if(isset($floor[$key.'_'.$k])){
                    $unit[$key][$k]['children'] = array_values($floor[$key.'_'.$k]);
                }
                if($fiel_name=='id'){
                    $unit[$key][$k]['children'] = array_merge([['value' => 0, 'label' => '全部']],$unit[$key][$k]['children']);
                }
            }
        }
        foreach ($building as $key=>$item){
            $building[$key]['children'] = [];
            if(isset($unit[$key])){
                $building[$key]['children'] = array_values($unit[$key]);
            }
            if($fiel_name=='id'){
                $building[$key]['children'] = array_merge([['value' => 0, 'label' => '全部']],$building[$key]['children']);
            }
        }
        $building = $building?array_values($building):[];
        return $building;
    }

    /**
     * 用户预约排卡
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function BookingCard($params){
        $saveData = [
            'uid' => $params['uid'],
            'house_id' => $params['house_id'],
            'name' => $params['name'],
            'phone' => $params['phone'],
            'b_number' => $params['b_number'],
            'u_number' => $params['u_number'],
            'f_number' => $params['f_number'],
            'r_number' => $params['r_number'],
        ];
        $house_info = (new LifeToolsHousesList())->where(['houses_id'=>$saveData['house_id'],'is_del'=>0])->findOrEmpty()->toArray();
        if(!$house_info){
            throw new \think\Exception(L_('楼盘不存在/已删除！'));
        }
        $activity = $this->checkActivity($saveData['house_id']);
        if(!$activity){
            throw new \think\Exception(L_('该楼盘未参与活动/活动已结束！'));
        }
        if(!$saveData['name']){
            throw new \think\Exception(L_('姓名不能为空！'));
        }
        if(!$saveData['phone']||!preg_match('/^1([3-9])\d{9}$/',$saveData['phone'])){
            throw new \think\Exception(L_('手机号格式有误！'));
        }
        $saveData['aid'] = $activity['id'];
        $where = [
            'b.house_id' => $saveData['house_id'],
            'b.status' => 1,
            'u.status' => 1,
            'f.status' => 1,
            'r.status' => 1,
            'b.serial_number' => $saveData['b_number'],
            'u.serial_number' => $saveData['u_number'],
            'f.serial_number' => $saveData['f_number'],
            'r.serial_number' => $saveData['r_number'],
        ];
        $order = 'b.sort asc,b.id asc,u.sort asc,u.id asc,f.sort asc,f.id asc,r.sort asc,r.id asc';
        $field = 'b.serial_number as b_serial_number,b.name as b_name,u.serial_number as u_serial_number,u.p_number as u_p_number,u.name as u_name,f.serial_number as f_serial_number,f.p_number as f_p_number,f.name as f_name,r.serial_number as r_serial_number,r.p_number as r_p_number,r.name as r_name';
        $info = (new LifeToolsHouseBuilding())->getInfo($where,$field,$order);
        if(!$info){
            throw new \think\Exception(L_('楼栋/单元/楼层/房间有误！'));
        }
        //查询是否已认购
        $r_where = [
            'uid' => $params['uid'],
            'house_id' => $params['house_id'],
            'b_number' => $params['b_number'],
            'u_number' => $params['u_number'],
            'f_number' => $params['f_number'],
            'r_number' => $params['r_number'],
            'aid' => $activity['id'],
        ];
        $ready = (new LifeToolsHouseCardUser())->where($r_where)->findOrEmpty()->toArray();
        if($ready){
            throw new \think\Exception(L_('请不要重复参与！'));
        }
        unset($r_where['uid']);
        $count = (new LifeToolsHouseCardUser())->where($r_where)->count();
        $saveData['carmi'] = $this->checkCard();
        $saveData['add_time'] = time();
        $saveData['sort'] = $count+1;
        try {
            (new LifeToolsHouseCardUser())->save($saveData);
            return true;
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 生成不重复的卡号/卡密
     * @param $no
     * @param int $type
     */
    public function checkCard($card_num=10){
        //生成卡密
        $start_num = str_pad(1,$card_num,0,STR_PAD_RIGHT);
        $end_num = str_pad(1,($card_num+1),0,STR_PAD_RIGHT)-1;
        $card_no = mt_rand($start_num,$end_num);
        $where['carmi'] = $card_no;
        
        $card = (new LifeToolsHouseCardUser())->where($where)->findOrEmpty()->toArray();
        while (count($card)>0){
            //生成卡密
            $card_no = mt_rand($start_num,$end_num);
            $where['carmi'] = $card_no;
            $card = (new LifeToolsHouseCardUser())->where($where)->findOrEmpty()->toArray();
        }
        return $card_no;
    }

    /**
     * 用户排卡楼盘列表
     * @param $params
     */
    public function myBookHouseList($params){
        $page = $params['page'];
        $pageSize = $page['pageSize'];
        $uid = $params['uid'];
        $house_ids = (new LifeToolsHouseCardUser())->where([['uid','=',$uid]])->group('house_id')->column('house_id');
        $where[] = ['h.houses_id','in',$house_ids];
        $where[] = ['h.is_del','=',0];
        $where[] = ['h.status','=',1];
        $field = 'h.houses_id,h.title,h.price,h.acreage,h.province_id,h.city_id,h.area_id,h.cover_image';
        $list = (new LifeToolsHouseCardActivity())->getHouseList($where,$field,$page,$pageSize);
        foreach ($list['data'] as $key=>$item){
            $pro_name="";
            $city_name="";
            $area_name="";
            if($item['province_id']){
                $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$item['province_id']]);
            }

            if($item['city_id']){
                $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$item['city_id']]);
            }

            if($item['area_id']){
                $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$item['area_id']]);
            }

            $list['data'][$key]['area_name']=$pro_name.'、'.$city_name.' '.$area_name;
            if(!empty($item['cover_image'])){
                $list['data'][$key]['cover_image']=thumb_img($item['cover_image'], 400, 400, 'fill');
            }else{
                $list['data'][$key]['cover_image']="";
            }
        }
        return $list;
    }

    /**
     * 获取用户排卡楼盘信息
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function getMyCardHouseInfo($params){
        $house_id = $params['house_id'];
        $uid = $params['uid'];
        $house_info = (new LifeToolsHousesList())->where(['houses_id'=>$house_id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$house_info){
            throw new \think\Exception(L_('楼盘不存在/已删除！'));
        }
        
        $where = [
            'uc.uid' => $uid,
            'uc.house_id' => $house_id,
            'b.status' => 1,
            'u.status' => 1,
            'f.status' => 1,
            'r.status' => 1,
        ];
        $order = 'b.sort asc,b.id asc,u.sort asc,u.id asc,f.sort asc,f.id asc,r.sort asc,r.id asc';
        $field = 'b.serial_number as b_serial_number,b.name as b_name,u.serial_number as u_serial_number,u.p_number as u_p_number,u.name as u_name,f.serial_number as f_serial_number,f.p_number as f_p_number,f.name as f_name,r.serial_number as r_serial_number,r.p_number as r_p_number,r.name as r_name';
        $list = (new LifeToolsHouseCardUser())->getList($where,$field,$order);
        $list = $this->resultInfo($list);
        return $list;
    }

    /**
     * 我的排卡-排卡详情
     */
    public function getMyCardDetail($params){
        $house_id = $params['house_id'];
        $uid = $params['uid'];
        $b_number = $params['b_number'];
        $u_number = $params['u_number'];
        $f_number = $params['f_number'];
        $r_number = $params['r_number'];
        $detail = (new LifeToolsHousesList())->where(['houses_id'=>$house_id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('楼盘不存在/已删除！'));
        }
        $pro_name="";
        $city_name="";
        $area_name="";

        if($detail['province_id']){
            $pro_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['province_id']]);
        }

        if($detail['city_id']){
            $city_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['city_id']]);
        }

        if($detail['area_id']){
            $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$detail['area_id']]);
        }

        $detail['area_name']=$pro_name.'、'.$city_name.' '.$area_name;
        $arr=[];
        if(!empty($detail['images'])){
            $imgs=explode(',',$detail['images']);
            foreach ($imgs as $k=>$v){
                $arr[]=thumb_img($v, 400, 400, 'fill');
            }
        }
        $detail['swiper_image']=$arr;
        
        $where = [
            'b.status' => 1,
            'u.status' => 1,
            'f.status' => 1,
            'r.status' => 1,
            'b.serial_number' => $b_number,
            'u.serial_number' => $u_number,
            'f.serial_number' => $f_number,
            'r.serial_number' => $r_number,
        ];
        $field = 'b.serial_number as b_serial_number,b.name as b_name,u.serial_number as u_serial_number,u.p_number as u_p_number,u.name as u_name,f.serial_number as f_serial_number,f.p_number as f_p_number,f.name as f_name,r.serial_number as r_serial_number,r.p_number as r_p_number,r.name as r_name';
        $order = 'b.sort asc,b.id asc,u.sort asc,u.id asc,f.sort asc,f.id asc,r.sort asc,r.id asc';
        
        $h_where = array_merge($where,['b.house_id' => $house_id]);
        $house_info = (new LifeToolsHouseBuilding())->getInfo($h_where,$field,$order);
        if(!$house_info){
            throw new \think\Exception(L_('楼栋/单元/楼层/房间不存在/已删除！'));
        }
        $house_info = $house_info[0];
        $detail['house_info'] = [
            'b_name' => $house_info['b_name'],
            'u_name' => $house_info['u_name'],
            'f_name' => $house_info['f_name'],
            'r_name' => $house_info['r_name'],
        ];
        
        $u_where = array_merge($where,['uc.house_id' => $house_id]);
        $u_order = 'uc.add_time asc,'.$order;
        $u_field = 'uc.uid,uc.carmi,uc.status,uc.is_end,'.$field;
        $info = (new LifeToolsHouseCardUser())->getList($u_where,$u_field,$u_order);
        $detail['user_list'] = [];
        $detail['carmi'] = '';
        $detail['can_cancel'] = 0;
        $detail['can_join'] = 1;
        $status_info = [0=>'待认购',1=>'已认购',2=>'已放弃'];
        foreach ($info as $item){
            $user = (new User())->field('uid,avatar,nickname')->where(['uid'=>$item['uid']])->findOrEmpty()->toArray();
            if($user){
                $detail['user_list'][] = [
                    'nickname' => $user['nickname'],
                    'avatar' => $user['avatar']?replace_file_domain($user['avatar']):'',
                    'status' => $item['status'],
                    'status_txt' => $status_info[$item['status']]??''
                ];
                if($item['is_end']==1){
                    $detail['can_join'] = 0;
                }
                if($user['uid']==$uid){
                    $detail['can_join'] = 0;
                    $detail['carmi'] = $item['carmi'];
                    $detail['can_cancel'] = 1;
                }
            }
        }
        return $detail;
    }

    /**
     * 用户取消认购
     * @param $params
     * @throws \think\Exception
     */
    public function myCardCancel($params){
        $house_id = $params['house_id'];
        $uid = $params['uid'];
        $b_number = $params['b_number'];
        $u_number = $params['u_number'];
        $f_number = $params['f_number'];
        $r_number = $params['r_number'];
        $detail = (new LifeToolsHousesList())->where(['houses_id'=>$house_id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('楼盘不存在/已删除！'));
        }
        $where = [
            'uc.house_id' => $house_id,
            'uc.uid' => $uid,
            'b_number' => $b_number,
            'u_number' => $u_number,
            'f_number' => $f_number,
            'r_number' => $r_number,
        ];
        $info = (new LifeToolsHouseCardUser())->where($where)->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('您还未排卡认购，请先排卡！'));
        }
        if($info['status']==1){
            throw new \think\Exception(L_('已认购无法放弃！'));
        }
        if($info['status']==2){
            throw new \think\Exception(L_('已放弃！'));
        }
        try {
            (new LifeToolsHouseCardUser())->where($where)->save(['status'=>2]);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 认购用户列表
     * @param $params
     */
    public function getCardUser($params){
        $aid = $params['aid'];
        $search_type = $params['search_type'];
        $keywords = $params['keywords'];
        $start_date = $params['start_date']?strtotime($params['start_date']):'';
        $end_date = $params['end_date']?strtotime($params['end_date']):'';
        $page = $params['page']??0;
        $pageSize = $params['pageSize']??10;
        $h_id = $params['h_id'];
        $b_id = $params['b_id'];
        $u_id = $params['u_id'];
        $f_id = $params['f_id'];
        $r_id = $params['r_id'];
        if(!$aid){
            throw new \think\Exception(L_('请选择活动！'));
        }
        $where[] = ['uc.aid','=',$aid];
        if($keywords){
            if($search_type==1){
                $where[] = ['uc.name','like','%'.$keywords.'%'];
            }else{
                $where[] = ['uc.phone','=',$keywords];
            }
        }
        if($h_id){
            $where[] = ['h.houses_id','=',$h_id];
            if($b_id){
                $where[] = ['b.id','=',$b_id];
                if($u_id){
                    $where[] = ['u.id','=',$u_id];
                    if($f_id){
                        $where[] = ['f.id','=',$f_id];
                        if($r_id){
                            $where[] = ['r.id','=',$r_id];
                        }
                    }
                }
            }
        }
        if($start_date&&$end_date){
            $where[] = ['uc.add_time','between',[$start_date,$end_date]];
        }
        $field = 'uc.id,uc.name,uc.phone,uc.add_time,uc.status,uc.is_end,uc.sort,h.title,b.name as bname,u.name as uname,f.name as fname,r.name as rname';
        $list = (new LifeToolsHouseCardUser())->getCardUserList($where,$field,$page,$pageSize);
        if($page>0){
            $data = $list['data'];
        }else{
            $data = $list;
        }
        $status_txt = [0=>'待认购',1=>'已认购',2=>'已放弃'];
        foreach ($data as $key=>$item){
            $data[$key]['book_info'] = $item['title'].' '.$item['bname'].' '.$item['uname'].' '.$item['fname'].' '.$item['rname'];
            $data[$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
            $data[$key]['rank_txt'] = '第'.$item['sort'].'位认购';
            $data[$key]['status_txt'] = $status_txt[$item['status']]??'未知';
        }
        if($page>0){
            $list['data'] = $data;
        }else{
            $list = $data;
        }
        return $list;
    }

    /**
     * 修改状态
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function changeStatus($params){
        $id = $params['id'];
        $type = $params['type'];
        $carmi = $params['carmi'];
        if(!$id){
            throw new \think\Exception(L_('请选择要修改的排卡用户！'));
        }
        $info = (new LifeToolsHouseCardUser())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在！'));
        }
        Db::startTrans();
        try {
            if($type==3){
                (new LifeToolsHouseCardUser())->where(['id'=>$id])->save(['status'=>2]);
            }else{
                if(!$carmi){
                    throw new \think\Exception(L_('请输入卡密！'));
                }
                if($info['carmi']!=$carmi){
                    throw new \think\Exception(L_('卡密信息有误！'));
                }
                (new LifeToolsHouseCardUser())->where(['id'=>$info['id']])->save(['status'=>1,'is_end'=>1]);
                $other_where = [
                    ['aid','=',$info['aid']],
                    ['house_id','=',$info['house_id']],
                    ['aid','=',$info['aid']],
                    ['b_number','=',$info['b_number']],
                    ['u_number','=',$info['u_number']],
                    ['f_number','=',$info['f_number']],
                    ['r_number','=',$info['r_number']],
                    ['id','<>',$info['id']]
                ];
                (new LifeToolsHouseCardUser())->where($other_where)->save(['status'=>2,'is_end'=>1]);
            }
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 认卡用户-导出
     */
    public function exportCardUser($params){
        $data = $this->getCardUser($params);
        $title = '认购用户记录';
        $xlsCell = [
            ['name', '姓名'],
            ['phone', '手机号'],
            ['rank_txt', '认购位次'],
            ['book_info', '认购信息'],
            ['add_time', '认购时间'],
            ['status_txt', '状态'],
        ];
        $fileName = date("Y-m-d", time()) . '-' . $params['rand_number'];
        $exportResult = export_excel($title, $xlsCell, $data, $fileName);
        return ['file_url' => $exportResult['url']];
    }

    /**
     * 获取楼盘、楼栋、单元、楼层、房间信息
     */
    public function getHouseDetail($params){
        $aid = $params['aid'];
        if(!$aid){
            throw new \think\Exception(L_('请选择活动!'));
        }
        $info = (new LifeToolsHouseCardActivity())->where(['id'=>$aid,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动不存在/已删除!'));
        }
        $house_ids = $info['house_ids']?explode(',',$info['house_ids']):[];
        if(!$house_ids){
            throw new \think\Exception(L_('活动未选择楼盘!'));
        }
        $house_name = (new LifeToolsHousesList())->where([['houses_id','in',$house_ids],['is_del','=',0]])->column('title','houses_id');
        if(!$house_name){
            throw new \think\Exception(L_('活动未选择楼盘!'));
        }
        
        $where[] = ['b.house_id','in',$house_ids];
        $order = 'b.sort asc,b.id asc,u.sort asc,u.id asc,f.sort asc,f.id asc,r.sort asc,r.id asc';
        $field = 'b.house_id,b.id as b_id,b.serial_number as b_serial_number,b.name as b_name,u.id as u_id,u.serial_number as u_serial_number,u.p_number as u_p_number,u.name as u_name,f.id as f_id,f.serial_number as f_serial_number,f.p_number as f_p_number,f.name as f_name,r.id as r_id,r.serial_number as r_serial_number,r.p_number as r_p_number,r.name as r_name';
        $list = (new LifeToolsHouseBuilding())->getInfo($where,$field,$order);
        $house_list = [];
        foreach ($list as $item){
            $house_list[$item['house_id']][] = $item;
        }
        $house_arr = [];
        foreach ($house_list as $key=>$val){
            $b_info = $this->resultInfo($val,'id');
            $house_arr[] = [
                'value' => $key,
                'label' => $house_name[$key]??'',
                'children' => array_merge([['value' => 0, 'label' => '全部']],$b_info)
            ];
        }
        $house_arr = array_merge([['value' => 0, 'label' => '全部']],$house_arr);
        return $house_arr;
    }
}