<?php


namespace app\roundabout\model\service;


use app\roundabout\model\db\RoundaboutAdver;
use think\facade\Db;

class AdverService
{
    public $adverMod;

    public function __construct()
    {
        $this->adverMod= new RoundaboutAdver();
    }

    /**
     * 广告列表
     * @param $params
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLisit($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where[] = ['is_del','=',0];

        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['is_common']) && $params['is_common'] >= 0) {
            $where[] = ['is_common', '=', $params['is_common']];
        }
        if (isset($params['status']) && $params['status'] >= 0) {
            $where[] = ['status', '=', $params['status']];
        }
        if (isset($params['begin_time']) && !empty($params['begin_time'])) {
            $where[] = ['start_time', '<=', strtotime($params['begin_time'])];
        }
        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $where[] = ['end_time', '>=', strtotime($params['end_time']) + 86400];
        }

        $field = '*';
        if($page>0){
            $list = (new RoundaboutAdver())->field($field)->where($where)->order('id desc')->paginate($pageSize)->toArray();
            $data = $list['data'];
        }else{
            $list = (new RoundaboutAdver())->field($field)->where($where)->order('id desc')->select()->toArray();
            $data = $list;
        }
        foreach ($data as $key=>$item){
            $data[$key]['start_time'] = $item['start_time']?date('Y-m-d H:i',$item['start_time']):'';
            $data[$key]['end_time'] = $item['end_time']?date('Y-m-d H:i',$item['end_time']):'';
            $data[$key]['active_time'] =  $data[$key]['start_time'].'-'.$data[$key]['end_time'];
            $data[$key]['pic'] = $item['pic']?replace_file_domain($item['pic']):'';
            $data[$key]['show_position'] = $item['show_position']?explode(',',$item['show_position']):[];
        }
        if($page>0){
            $list['data'] = $data;
        }else{
            $list = $data;
        }
        return $list;
    }

    /**
     * 修改状态/删除
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function updataAdver($params){
        $id = $params['id'];
        $status = $params['status']??0;
        $type = $params['type']; //1-修改状态，2-删除
        if(!$id){
            throw new \think\Exception(L_('请选择要操作广告！'));
        }
        $saveData = $where = [];
        $where[] = ['id','=',$id];
        $info = (new RoundaboutAdver())->where($where)->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('广告不存在/已删除！'));
        }
        if($type==1){
            if(!in_array($status,[0,1])){
                throw new \think\Exception(L_('状态错误！'));
            }
            if($status==1){
                $business_type_name = ['home'=>'首页','shop'=>'外卖','mall'=>'商城','appoint'=>'预约'];
                $show_position = $info['show_position']?explode(',',$info['show_position']):[];
                foreach ($show_position as $item){
                    $check_where[] = ['is_del','=',0];
                    $check_where[] = ['status','=',1];
                    $where[] = ['','exp',Db::raw("FIND_IN_SET('".$item."',show_position) AND ((start_time between '".$info['start_time']."' AND '".$info['end_time']."') or (end_time between '".$info['start_time']."' AND '".$info['end_time']."') or (start_time > '".$info['start_time']."' AND end_time < '".$info['end_time']."'))")];
                    $check_where[] = ['id','<>',$id];

                    $ready = (new RoundaboutAdver())->where($check_where)->findOrEmpty()->toArray();
                    if($ready){
                        //throw new \think\Exception(L_($business_type_name[$item].'广告该时间段已有！'));
                    }
                }
            }
            $saveData['status'] = $status;
        }else{
            $saveData['is_del'] = 1;
        }
        try {
            (new RoundaboutAdver())->where($where)->save($saveData);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 保存活动信息
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function saveAdver($params){
        $id = $params['id']??0;
        $saveData = [
            'name'=>$params['name'],
            'start_time'=>$params['start_time'],
            'end_time'=>$params['end_time'],
            'pic'=>$params['pic'],
            'show_position'=>$params['show_position'],
            'link_url'=>$params['link_url'],
            'is_common'=>$params['is_common'],
        ];
        if ($saveData['is_common'] == 1) {
            $saveData['city_ids'] = '';
        }
        if(!$saveData['name']){
            throw new \think\Exception(L_('名称不能为空！'));
        }
        if(!$saveData['start_time']||!$saveData['end_time']||$saveData['end_time']<$saveData['start_time']){
            throw new \think\Exception(L_('活动时间不对！'));
        }
        if(!is_array($saveData['show_position'])){
            throw new \think\Exception(L_('弹窗位置有误！'));
        }
        $saveData['start_time'] = strtotime($saveData['start_time']);
        $saveData['end_time'] = strtotime($saveData['end_time']);
        $business_type_name = ['home'=>'首页','shop'=>'外卖','mall'=>'商城','appoint'=>'预约'];
        foreach ($saveData['show_position'] as $item){
            $where[] = ['is_del','=',0];
            $where[] = ['status','=',1];
            $where[] = ['','exp',Db::raw("FIND_IN_SET('".$item."',show_position) AND ((start_time between '".$saveData['start_time']."' AND '".$saveData['end_time']."') or (end_time between '".$saveData['start_time']."' AND '".$saveData['end_time']."') or (start_time > '".$saveData['start_time']."' AND end_time < '".$saveData['end_time']."'))")];
            if($id){
                $where[] = ['id','<>',$id];
            }
            $ready = (new RoundaboutAdver())->where($where)->findOrEmpty()->toArray();
            if($ready){
              //  throw new \think\Exception(L_($business_type_name[$item].'广告该时间段已有！'));
            }
        }
        
        $saveData['show_position'] = implode(',',$saveData['show_position']);
        try {
            if($id){
                $info = (new RoundaboutAdver())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('广告不存在/已删除！'));
                }
                (new RoundaboutAdver())->where(['id'=>$id])->save($saveData);
            }else{
                $saveData['is_del'] = 0;
                (new RoundaboutAdver())->save($saveData);
            }
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 首页广告
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function getAdver($params){
        $name_key = $params['name_key'];
        $cityId = $params['now_city']??0;
        if(!$name_key){
            throw new \think\Exception(L_('位置展示有误！'));
        }

        $nowCityId = 0;
        if ($cityId) {
            $city = Db::name('area')->where('area_id', $cityId)->field('area_id,area_type,area_pid')->find();
            if (empty($city)) {
                throw new \think\Exception(L_('参数有误'));
            }
            if ($city['area_type'] == 2) {
                $nowCityId = $cityId;
            } else {
                $nowCityId = $city['area_pid'];
            }
        }

        $where[] = ['is_del','=',0];
        $where[] = ['status','=',1];
        $where[] = ['start_time','<',time()];
        $where[] = ['end_time','>',time()];
        $where[] = ['','exp',Db::raw("FIND_IN_SET('".$name_key."',show_position)")];
        $where[] = ['', 'exp', Db::raw(sprintf("(is_common =1) OR (is_common = 0 AND FIND_IN_SET('%s',city_ids))", $nowCityId))];

        $info = (new RoundaboutAdver())->field('id,name,pic,link_url')->where($where)->order('id desc')->findOrEmpty()->toArray();
        if($info){
            $info['pic'] = $info['pic']?replace_file_domain($info['pic']):'';
        }
        return $info;
    }

    //获取平台广告指定区域
    public function getAdverArea($id)
    {
        $thisAdver = $this->adverMod->where([['id', '=', $id], ['is_del', '=', 0]])->find();
        if (empty($thisAdver)) {
            throw new \think\Exception(L_('平台广告不存在'));
        }

        //获取省份、省市两级即可
        $areas = Db::name('area')->where([['area_type', '<', 3], ['is_open', '=', 1]])->field('area_id as "key" ,area_pid as pid,area_name as title')->order('area_sort desc')->select()->toArray();
        $province = array_filter($areas, function ($r) {
            return $r['pid'] == 0;
        });

        foreach ($province as $k => $v) {
            $city = array_filter($areas, function ($r) use ($v) {
                return $v['key'] == $r['pid'];
            });
            $province[$k]['children'] = array_values($city);
        }

        $checkedKeys = empty($thisAdver->city_ids) ? [] : explode(',', $thisAdver->city_ids);
        return ['tree_data' => array_values($province), 'check_data' => array_map('intval', $checkedKeys)];
    }

    //保存指定区域
    public function adverAreaSet($id, $areaIds)
    {
        if (empty($areaIds)) {
            throw new \think\Exception(L_('指定城市选择不能为空'));
        }
        $thisAdver = $this->adverMod->where([['id', '=', $id], ['is_del', '=', 0]])->find();
        if (empty($thisAdver)) {
            throw new \think\Exception(L_('平台广告不存在'));
        }
        if ($thisAdver->is_common == 1) {
            throw new \think\Exception(L_('当前广告为通用类型，无需指定城市'));
        }
        $thisAdver->city_ids = implode(',', $areaIds);
        return $thisAdver->save();
    }
}