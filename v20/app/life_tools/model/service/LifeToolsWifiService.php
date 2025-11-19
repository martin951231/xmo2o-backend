<?php


namespace app\life_tools\model\service;


use app\life_tools\model\db\LifeToolsWifi;

class LifeToolsWifiService
{
    public $wifiDb;

    public function __construct(){
        $this->wifiDb = new LifeToolsWifi();
    }

    /**
     * 商家后台-wifi-获取列表
     * @param $param
     * @return \think\Collection|\think\Paginator
     * @throws \think\Exception
     */
    public function getWifiList($param){
        $where = [];
        $where[] = ['mer_id','=',$param['mer_id']];
        if($param['keywords']){
            $where[] = ['name','like','%'.$param['keywords'].'%'];
        }
        $list = $this->wifiDb->getList($where,$param['page'],$param['page_size']);
        foreach ($list as $key=>$item){
            $list[$key]['start_time'] = date('H:i',$item['start_time']);
            $list[$key]['end_time'] = date('H:i',$item['end_time']);
            $list[$key]['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
        }
        return $list;
    }

    /**
     * 商家后台-wifi-添加信息
     * @param $param
     */
    public function wifiAdd($param){
        $where = array(
            'name' => $param['name'],
            'wifi_pass' => $param['wifi_pass'],
            'long' => $param['long'],
            'lat' => $param['lat'],
            'effective_range' => $param['effective_range'],
            'issued_by' => $param['issued_by'],
            'status' => $param['status'],
            //'start_time' => $param['start_time'],
            //'end_time' => $param['end_time'],
            'mer_id' => $param['mer_id']
        );
        if(!$where['name']){
            throw new \think\Exception('wifi名称不能为空！');
        }
        if(!$where['wifi_pass']){
            throw new \think\Exception('wifi密码不能为空！');
        }
        if(!$where['long']||!$where['lat']){
            throw new \think\Exception('请选择经纬度！');
        }
        if(!$where['effective_range']||$where['effective_range']<=0){
            throw new \think\Exception('范围无效！');
        }
        if(!$where['issued_by']){
            throw new \think\Exception('发布单位不能为空！');
        }
        if(!in_array($where['status'],array(0,1))){
            throw new \think\Exception('启用状态无效！');
        }
        /*if(!$where['start_time']||!$where['end_time']||(strtotime($where['start_time'])>=strtotime($where['end_time']))){
            throw new \think\Exception('时间范围无效！');
        }*/
        //查询是否重复
        $check_name = $this->wifiDb->where(['name'=>$where['name']])->find();
        if($check_name&&(!$param['id']||($param['id']&&$check_name['id']!=$param['id']))){
            throw new \think\Exception('wifi名称已存在！');
        }
        /*$where['start_time'] = strtotime($where['start_time']);
        $where['end_time'] = strtotime($where['end_time']);*/
        if($param['id']){
            //查询信息是否存在
            $wifi_info = $this->wifiDb->find($param['id']);
            if(!$wifi_info){
                throw new \think\Exception('修改信息不存在！');
            }

            try {
                $this->wifiDb->where(['id'=>$param['id']])->save($where);
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }
        }else{
            $where['add_time'] = time();
            try {
                $this->wifiDb->save($where);
            } catch (\Exception $e) {
                throw new \think\Exception($e->getMessage());
            }
        }
        return true;
    }

    /**
     * 商家后台-wifi-详情
     * @param $param
     */
    public function wifiShow($param){
        $where = [
            'id' => $param['id'],
            'mer_id' => $param['mer_id']
        ];
        if(!$where['id']){
            throw new \think\Exception('参数错误！');
        }
        $wifi_info = $this->wifiDb->where($where)->find();
        if(!$wifi_info){
            throw new \think\Exception('信息不存在！');
        }
        return $wifi_info;
    }

    /**
     * 商家后台-wifi-状态修改
     * @param $param
     */
    public function wifiStatusChange($param){
        $status = $param['status'];
        $where = [
            'id' => $param['id'],
            'mer_id' => $param['mer_id']
        ];
        if(!$where['id']){
            throw new \think\Exception('参数有误！');
        }
        if(!in_array($status['status'],array(0,1))){
            throw new \think\Exception('状态信息有误！');
        }
        $wifi_info = $this->wifiDb->where($where)->find();
        if(!$wifi_info){
            throw new \think\Exception('信息不存在！');
        }
        try {
            $this->wifiDb->where($where)->save(['status'=>$status]);
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 商家后台-wifi-删除
     * @param $param
     */
    public function wifiDelete($param){
        if(!$param['id']){
            throw new \think\Exception('参数有误！');
        }
        if (!is_array($param['id'])){
            $param['id'] = array($param['id']);
        }
        $where = [
            ['id', 'in', $param['id']],
            ['mer_id', '=', $param['mer_id']]
        ];
        $wifi_info = $this->wifiDb->where($where)->find();
        if(!$wifi_info){
            throw new \think\Exception('信息已删除！');
        }
        try {
            $this->wifiDb->where($where)->delete();
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 用户端-wifi列表
     */
    public function wifiList($param){
        $lng = $param['lng'];
        $lat = $param['lat'];
        //$mer_id = $param['mer_id'];
        $keywords = $param['keywords'];
        if(!$lng||!$lat){
            throw new \think\Exception('参数信息有误！');
        }
        /*if(!$mer_id){
            throw new \think\Exception('商家信息有误！');
        }*/
        $where = [];
        //$where[] = ['mer_id','=',$mer_id];
        $where[] = ['status','=',1];
        if($keywords){
            $where[] = ['name','like','%'.$keywords.'%'];
        }
        $list = $this->wifiDb->getList($where,'','','id,name,long,lat,wifi_pass,add_time,start_time,end_time,effective_range,issued_by');
        $list = $list?$list->toArray():array();
        foreach ($list as $key=>$item){
            $list[$key]['start_time'] = date('H:i',$item['start_time']);
            $list[$key]['end_time'] = date('H:i',$item['end_time']);
            $list[$key]['add_time'] = date('Y.m.d',$item['add_time']);
            $list[$key]['distance'] = getDistance($lat,$lng,$item['lat'],$item['long']);
            $list[$key]['distance'] = ($list[$key]['distance']-$item['effective_range'])>0?($list[$key]['distance']-$item['effective_range']):0;
        }
        // 先取出要排序的字段的值
        $sort = array_column($list, 'distance');
        // 按照sort字段升序  其中SORT_ASC表示升序 SORT_DESC表示降序
        array_multisort($sort, SORT_ASC, $list);
        foreach ($list as $key=>$item){
            $list[$key]['distance'] = get_range($item['distance'],false);
        }
        return $list;
    }
}