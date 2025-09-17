<?php
/**
 * @author : liukezhu
 * @date : 2022/3/30
 */
class House_new_repair_works_order_timelyModel extends Model{

    //生成随机颜色
    public function randColor($start=10,$end=15){
        $colors = array();
        for($i = 0;$i<6;$i++){
            $colors[] = dechex(rand($start,$end));
        }
        return '#'.implode('',$colors);
    }

    //todo 组装tree数据
    public function getTree($list,$pid=0,$itemprefix = ''){
        static $icon = array('│', '├', '└');
        static $nbsp = "&nbsp;";
        static $arr = array();
        $number = 1;
        foreach ($list as $row) {
            if ($row['pid'] == $pid) {
                $brotherCount = 0;
                //判断当前有多少个兄弟分类
                foreach ($list as $r) {
                    if ($row['pid'] == $r['pid']) {
                        $brotherCount++;
                    }
                }
                if ($brotherCount > 0) {
                    $j = $k = '';
                    if ($number == $brotherCount) {
                        $j .= $icon[2];
                        $k = $itemprefix ? $nbsp : '';
                    } else {
                        $j .= $icon[1];
                        $k = $itemprefix ? $icon[0] : '';
                    }
                    $spacer = $itemprefix ? $itemprefix . $j : '';
                    $row['name'] = $spacer . $row['name'];
                    $arr[] = $row;
                    $number++;
                    $this->getTree($list, $row['id'], $itemprefix . $k . $nbsp);
                }
            }
        }
        return $arr;
    }

    //todo 获取部门下所有部门和人员
    public function get_tissue_user($village_id,$group_id,$wid){
        $house_worker=D('House_worker');
        $property_group=D('Property_group');
        $list=$property_group->where(['village_id'=>$village_id,'is_del'=>0,'type'=>['in',[0,1]]])->field('id,fid,name')->order('id asc')->select();
        if(!$list){
            return [];
        }
        $department_id[]=$group_id;
        foreach ($list as $val) {
            if(in_array($val['fid'],$department_id)){
                $department_id[]=$val['id'];
            }
        }
        $list=$property_group->where(['id'=>['in',$department_id]])->field('0 as wid,id,fid as pid,name,1 as type')->order('id asc')->select();
        $worker=$house_worker
            ->where(['department_id'=>['in',$department_id],'is_del'=>0,'status'=>['neq', 4]])
            ->field('wid,-1 as id,name,department_id as pid,2 as type')->select();
        if($worker){
            $list=array_merge($list,$worker);
        }
        $list=$this->getTree($list,$group_id);
        return $list;
    }

    //todo 获取部门id集合
    public function get_department_all($village_id,$wid){
        $house_worker=D('House_worker');
        $worker=$house_worker->where(['wid'=>$wid,'is_del'=>0,'status'=>['neq', 4]])->field('department_id')->find();
        if(!$worker){
            return ['error'=>false,'msg'=>'当前关联身份被删除，请联系管理员','data'=>[]];
        }
        $department_id=$this->get_department_id($village_id,$worker['department_id']);
        return ['error'=>true,'msg'=>'ok','data'=>$department_id];
    }

    //todo 获取下级部门id集合
    public function get_department_id($village_id,$department_id){
        $property_group=D('Property_group');
        $org_list=$property_group->where(['village_id'=>$village_id,'is_del'=>0,'type'=>['in',[0,1]]])->field('id,fid,name')->order('id asc')->select();
        $data=[];
        $data[]=$department_id;
        foreach ($org_list as $val) {
            if(in_array($val['fid'],$data)){
                $data[]=$val['id'];
            }
        }
        return $data;
    }

    //todo 获取部门柱状图
    public function get_work_order_stack($village_id,$date_type,$data_type=0,$wid=0){
        $title=$xaxis=$data=$group=$succ_num=$total_num=[];
        $where=[
            'a.village_id'=>$village_id
        ];
        if($wid > 0){
            $department=$this->get_department_all($village_id,$wid);
            if(!$department['error']){
                return ['error'=>$department['error'], 'msg'=>$department['msg']];
            }
            $where['a.group_id'] = ['in',$department['data']];
        }
        //本年
        $year=strtotime(date('Y-01-01 00:00:00'));
        //本月开始、结束
        $start=strtotime(date('Y-m-01 00:00:00'));
        $end = strtotime(date('Y-m-d H:i:s'));
        //本日
        $today=strtotime(date('Y-m-d 00:00:00'));
        if($date_type == 1){//本年
            $where['a.add_time'] = array('egt',$year);
        }
        elseif ($date_type == 2){//本月
            $where['a.add_time'] =array('between',array($start,$end));
        }
        elseif ($date_type == 3){//本日
            $where['a.add_time'] = array('egt',$today);
        }
        if($data_type == 1){
            unset($where['a.add_time']);
            $field='b.name as group_name,a.group_id,SUM( CASE WHEN a.add_time >= '.$today.' AND a.is_status = 2 THEN 1 ELSE 0 END ) AS today_succ_num,
	SUM( CASE WHEN a.add_time >= '.$today.' AND a.is_status >= 0 THEN 1 ELSE 0 END ) AS today_total_num,
	SUM( CASE WHEN a.add_time BETWEEN '.$start.' AND '.$end.' AND a.is_status = 2 THEN 1 ELSE 0 END ) AS month_succ_num,
	SUM( CASE WHEN a.add_time BETWEEN '.$start.' AND '.$end.' AND a.is_status >= 0 THEN 1 ELSE 0 END ) AS month_total_num,
	SUM( CASE WHEN a.add_time >= '.$year.' AND a.is_status = 2 THEN 1 ELSE 0 END ) AS year_succ_num,
	SUM( CASE WHEN a.add_time >= '.$year.' AND a.is_status >= 0 THEN 1 ELSE 0 END ) AS year_total_num ';
        }
        else{
            $field='b.name as group_name,a.group_id,SUM(IF(a.is_status = 2,"1","0")) AS succ_num,SUM(IF(a.is_status >= 0,"1","0")) AS total_num';
        }
        $list = $this->alias('a')
            ->join('LEFT JOIN '.C('DB_PREFIX').'property_group as b on b.id=a.group_id')
            ->field($field)
            ->where($where)
            ->group('a.group_id')->order('a.group_id asc')->select();
        if($data_type == 1){
            if($list){
                foreach ($list as $k=>$v){
                    $year_rate=$month_rate=$today_rate=0;
                    if($v['year_succ_num'] > 0 && $v['year_total_num'] > 0){
                        $year_rate=round_number(($v['year_succ_num'] / $v['year_total_num']) * 100) ;
                    }
                    if($v['month_succ_num'] > 0 && $v['month_total_num'] > 0){
                        $month_rate=round_number(($v['month_succ_num'] / $v['month_total_num']) * 100) ;
                    }
                    if($v['today_succ_num'] > 0 && $v['today_total_num'] > 0){
                        $today_rate=round_number(($v['today_succ_num'] / $v['today_total_num']) * 100) ;
                    }
                    $data[]=[
                        'group_id'=>$v['group_id'],
                        'title'=>$v['group_name'],
                        'background'=>$this->randColor(),
                        'year_data'=>[
                            'total'=>$v['year_total_num'],
                            'timely_num'=>$v['year_succ_num'],
                            'timely_rate'=>$year_rate.'%'
                        ],
                        'month_data'=>[
                            'total'=>$v['month_total_num'],
                            'timely_num'=>$v['month_succ_num'],
                            'timely_rate'=>$month_rate.'%'
                        ],
                        'day_data'=>[
                            'total'=>$v['today_total_num'],
                            'timely_num'=>$v['today_succ_num'],
                            'timely_rate'=>$today_rate.'%'
                        ]
                    ];
                }
            }
        }else{
            if($list){
                foreach ($list as $k=>$v){
                    $group[$v['group_name']]=$v['group_id'];
                    $title[]=$v['group_name'];
                    $succ_num[]=$v['succ_num'];
                    $total_num[]=$v['total_num'];
                }
            }
            $seriesData=[
                [
                    'name'=>'报修总数',
                    'type'=>'bar',
                    'data'=>$total_num,
                    'itemStyle'=>[
                        'normal'=>[
                            'label'=>[
                                'show'=>true,
                                'position'=>'top',
                            ]
                        ]
                    ]
                ],
                [
                    'name'=>'及时数',
                    'type'=>'bar',
                    'data'=>$succ_num,
                    'itemStyle'=>[
                        'normal'=>[
                            'label'=>[
                                'show'=>true,
                                'position'=>'top',
                            ]
                        ]
                    ]
                ]
            ];
            $xaxis=[
                'type'=>'category',
                'data'=>$title
            ];
            if(count($title) >= 10){
                $xaxis['axisLabel']=[
                    'interval'=>0,
                    'rotate'=>35
                ];
            }
            $data=[
                'xAxisData'=>$xaxis,
                'seriesData'=>$seriesData,
                'groupData'=>$group
            ];
        }
        return ['error'=>true, 'msg'=>'ok', 'data'=> $data];
    }

    //todo 获取部门分类
    public function get_work_order_class($where){
        $field='a.id,g.name as group_name,a.type,a.group_id,a.cat_fid,w.wid,w.name as user_name,a.is_status as status,w.is_del';
        $list = $this->alias('a')
            ->join('LEFT JOIN '.C('DB_PREFIX').'property_group as g on g.id=a.group_id')
            ->join('LEFT JOIN '.C('DB_PREFIX').'house_worker as w on w.wid = a.worker_id')
            ->field($field)
            ->where($where)
            ->order('a.group_id asc,a.type asc')
            ->select();
        return $list;
        $field='a.type,a.group_id,SUM(IF(a.is_status = 2,"1","0")) AS succ_num,SUM(IF(a.is_status <> 2,"1","0")) AS total_num';
        $list = $this->alias('a')
            ->join('LEFT JOIN '.C('DB_PREFIX').'house_new_repair_cate as b on b.id=a.cat_fid')
            ->field('b.cate_name as name,'.$field)->where($where)->where(['a.type'=>1])->group('a.cat_fid')->select();
        $list2 = $this->alias('a')->field('"日常巡检统计" as name,'.$field)->where($where)->where(['a.type'=>2])->find();
        if($list2 && isset($list2['group_id']) && $list2['group_id']){
            $list[]=$list2;
        }
        return $list;
    }

    //todo 组织饼状图数据
    public function get_work_order_simple($param){
        $data=[];
        $where=[
            'a.village_id'=>$param['village_id'],
        ];
        $department_id=$this->get_department_id($param['village_id'],$param['group_id']);
        if($department_id){
            $where['a.group_id']= ['in', $department_id];
        }
//        if(!empty($param['name'])){
//            $where['b.cate_name']=['like','%'.$param['name'].'%'];
//        }
        if(!empty($param['personnel_id'])){
            $where['a.worker_id']=$param['personnel_id'];
        }
        if($param['date_type'] == 1){//本年
            $year=strtotime(date('Y-01-01 00:00:00'));
            $where['a.add_time'] = array('egt',$year);
        }elseif ($param['date_type'] == 2){//本月
            $start=strtotime(date('Y-m-01 00:00:00'));
            $end = strtotime(date('Y-m-d H:i:s'));
            $where['a.add_time'] =array('between',array($start,$end));
        }elseif ($param['date_type'] == 3){//本日
            $today=strtotime(date('Y-m-d 00:00:00'));
            $where['a.add_time'] = array('egt',$today);
        }elseif ($param['date_type'] == 4){//日期范围
            if($param['start_time'] && $param['end_time']){
                if($param['start_time'] >= $param['end_time']){
                    return ['status'=>0,'msg'=>'请选择合法的日期范围','data'=>[]];
                }
            }
            if($param['start_time'] && $param['end_time']){
                $where['a.add_time'] =array('between',array($param['start_time'],$param['end_time']));
            }
        }
        $key=md5(json_encode($where,JSON_UNESCAPED_UNICODE));
        $lockDate = S($key);
        $is_cache=false;
        if($lockDate){
            $list=$lockDate;
            $is_cache=true;
        }else{
            $list=$this->get_work_order_class($where);
            if(count($list) >= 3000){
                $is_cache=true;
                S($key,$list,86400);
            }
        }
        if($is_cache){
            $lockMsg='<br>提示：查询数据量达到缓存条件，时效为一天（存在数据统计的不及时性，需要的话可手动清除缓存）。';
        }else{
            $lockMsg='';
        }
        if(!$list){
            return [
                'status'=>1,
                'msg'=>'ok',
                'data'=>[
                    'cache_msg'=>$lockMsg,
                    'is_tab'=>$param['is_tab'],
                    'list'=>$data
                ]];
        }
        $group_id=array_values(array_unique(array_column($list,'group_id')));
        $result=$idd=[];
        $succ_num21=$total_num21=0;
        $user21=[];
        foreach ($group_id as $k=>$v){
            $user=[];
            foreach ($list as $vv){
                if($vv['type'] == 1 && $v == $vv['group_id']){
                    if(empty($idd) || !in_array($vv['group_id'],$idd)){
                        $result[$k]=[
                            'cat_fid'=>$vv['cat_fid'],
                            'name'=>$vv['group_name'],
                            'group_id'=>$vv['group_id']
                        ];
                    }
                    $idd[]=$vv['group_id'];
                    if(isset($user[$vv['wid']])){
                        $succ_num=$user[$vv['wid']]['succ_num'];
                        $total_num=$user[$vv['wid']]['total_num'];
                    }
                    else{
                        $succ_num=0;
                        $total_num=0;
                    }
                    if($vv['status'] == 2){
                        $succ_num++;
                    }
                    $total_num++;
                    $succ_rate=0;
                    if($succ_num > 0 && $total_num > 0){
                        $succ_rate=round_number(($succ_num / $total_num)) ;
                    }
                    $user[$vv['wid']]=[
                        'id'=>$vv['id'],
                        'type'=>$vv['type'],
                        'cat_fid'=>$vv['cat_fid'],
                        'group_id'=>$vv['group_id'],
                        'user_id'=>$vv['wid'],
                        'is_del'=>$vv['is_del'],
                        'user_name'=>$vv['user_name'],
                        'succ_num'=>$succ_num,
                        'total_num'=>$total_num,
                        'delay_num'=>$total_num - $succ_num,
                        'succ_rate'=>$succ_rate
                    ];
                    $result[$k]['user']=$user;
                }
                else if($vv['type'] == 2){
                    if(isset($user1[$vv['wid']])){
                        continue;
                    }
                    if($vv['status'] == 2){
                        $succ_num21++;
                    }
                    $total_num21++;
                    $succ_rate=0;
                    if($succ_num21 > 0 && $total_num21 > 0){
                        $succ_rate=round_number(($succ_num21 / $total_num21) ) ;
                    }
                    $user21[$vv['wid']]=[
                        'id'=>$vv['id'],
                        'type'=>$vv['type'],
                        'cat_fid'=>$vv['cat_fid'],
                        'group_id'=>$vv['group_id'],
                        'user_id'=>$vv['wid'],
                        'is_del'=>$vv['is_del'],
                        'user_name'=>$vv['user_name'],
                        'succ_num'=>$succ_num21,
                        'total_num'=>$total_num21,
                        'delay_num'=>$total_num21 - $succ_num21,
                        'succ_rate'=>$succ_rate
                    ];
                }
            }
        }
        if($user21){
            $result[]=[
                'cat_fid'=>0,
                'name'=>'日常巡检统计',
                'group_id'=>0,
                'user'=>$user21
            ];
        }
        if($result){
            foreach ($result as &$vv){
                if($vv['user']){
                    $vv['user']=array_values($vv['user']);
                }
            }
            unset($vv);
            if($param['is_tab']){
                $data=$result;
            }
            else{
                foreach ($result as $k=>$v){
                    $rr=$bg_color=[];
                    if(!empty($v['user'])){
                        foreach ($v['user'] as $v2){
                            $rr[]=[
                                'value'=>$v2['succ_num'] ? $v2['succ_num'] : 0,
                                'name'=>$v2['user_name'],
                            ];
                            $bg_color[]=$this->randColor(3,8);
                        }
                    }
                    $value=$rr ? (array_sum(array_column($rr,'value'))) : 0;
                    if($value <= 0){ //针对echart值为0不显示图 formatter转化处理下
                        foreach ($rr as &$v3){
                            $v3['value']=-1;
                        }
                    }
                    $data[]=[
                        'name'=>$v['name'],
                        'minAngle'=>5,
                        'avoidLabelOverlap'=>true,
                        'type'=>'pie',
                        'radius'=>'55%',
                        'center'=>['50%', '40%'],
                        'label'=>[
                            'align'=>'left',
                            'normal'=>[
                                'show'=>true,
                                'position'=>'inner',
                                'formatter'=>($value <= 0) ? '{b}：0' : '{b}：{c}',
                                'rich'=>[
                                    'color1'=>[
                                        'color'=>'#333333',
                                        'fontSize'=>14,
                                        'lineHeight'=>20
                                    ]
                                ],
                            ]

                        ],
                        'data'=>$rr,
                        'bg_color'=>$bg_color
                    ];
                }
            }
        }
        return [
            'status'=>1,
            'msg'=>'ok',
            'data'=>[
                'cache_msg'=>$lockMsg,
                'is_tab'=>$param['is_tab'],
                'list'=>$data
            ]];
    }
}