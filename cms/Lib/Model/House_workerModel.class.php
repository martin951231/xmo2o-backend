<?php
class House_workerModel extends Model
{

	public function add_score($wid, $score = 5)
	{
		if ($worker = $this->field(true)->where(array('wid' => $wid))->find()) {
			$data = array();
			$data['reply_count'] = $worker['reply_count'] + 1;
			$data['score_all'] = $worker['score_all'] + $score;
			$data['score_mean'] = round($data['score_all']/$data['reply_count'] * 10)/10;
			$this->where(array('wid' => $wid))->save($data);
		}
	}
	
	public function add_num($wid)
	{
		$this->where(array('wid' => $wid))->setInc('num');
	}

	//todo 小区绑定工作人员  D('House_worker')->village_bind_work()
	public function village_bind_work(){
	    $village_info= D('House_village')
            ->join('as a left join '.C('DB_PREFIX').'house_village_info as b ON a.village_id = b.village_id')
            ->where([
                'a.status'      =>  1,
                'a.handle_type' =>  1,
                'a.hour'        =>  array('gt', 0),
                'b.repair_wid'  =>  array('gt', 0),
                'b.advise_wid'  =>  array('gt', 0)
            ])->field('a.village_id,a.hour,b.repair_wid,b.advise_wid')->select();
            if(empty($village_info)){
                return true;
            }
            $time=time();
            $data=[];
            $house_village_repair_list=D('House_village_repair_list');
            foreach ($village_info as $v){
                $end_time=$time - (intval($v['hour']) * 3600);
                $repair_list=$house_village_repair_list->where([
                    'village_id'=>$v['village_id'],
                    'status'=>0,
                    'time'=>array('elt', $end_time),
                    'type'=>['in', [1,3]]
                ])->field('pigcms_id,type')->order('time asc,pigcms_id asc')->select();
                if(empty($repair_list)){
                    continue;
                }
                foreach ($repair_list as $v2){
                    if($v2['type'] == 1){ //在线报修
                        $wid=$v['repair_wid'];
                    }
                    else{ //投诉建议
                        $wid=$v['advise_wid'];
                    }
                    $data[]=[
                        'param'=> serialize([
                            'type'       => 'repair_appoint_work',
                            'repair_id'  => $v2['pigcms_id'],
                            'village_id' => $v['village_id'],
                            'wid'        => $wid,
                            'repair_type'=> $v2['type']
                        ]),
                        'plan_time'   =>  -110,
                        'space_time'  =>  0,
                        'add_time'    =>  $time,
                        'file'        =>  'sub_house_village',
                        'time_type'   =>  1,
                        'unique_id'   =>  'bind_work_'.$v2['pigcms_id'].'_'.$time.'_'.uniqid(),
                        'rand_number' =>  mt_rand(1, max(C('config.sub_process_num'), 3)),
                    ];
                }
            }
            if(!empty($data)){
                M('process_sub_plan')->addAll($data);
            }
    }

    //todo 指定工作人员接单
    public function appoint_work($param){
        $database_house_village_repair_list = D('House_village_repair_list');
        $database_house_workert = D('House_worker');
        $repair = $database_house_village_repair_list->where([
            'pigcms_id' => $param['repair_id'],
            'village_id' => $param['village_id'],
            'status'=>0,
            'type'=>['in', [1,3]]
        ])->find();
        if(empty($repair)){
            fdump_api(['订单不存在=='.__LINE__,$param,$database_house_village_repair_list->_sql()],'work/appoint_work_error');
            return true;
        }
        $worker = $database_house_workert->field(true)->where([
            'wid' => $param['wid'], 'village_id' => $param['village_id']
        ])->find();
        if(empty($worker)){
            fdump_api(['工作人员不存在=='.__LINE__,$param,$worker->_sql()],'work/appoint_work_error');
            return true;
        }
        $data['wid'] = $worker['wid'];
        $data['status'] = 1;
        $where['village_id'] = $param['village_id'];
        $where['pigcms_id'] = $repair['pigcms_id'];
        if ($database_house_village_repair_list->where($where)->save($data)) {
            fdump_api(['分配成功=='.__LINE__,$database_house_village_repair_list->_sql()],'work/appoint_work_succ');
            D('House_village_repair_log')->add_log(array('status' => 1, 'repair_id' => $repair['pigcms_id'], 'phone' => $worker['phone'], 'name' => $worker['name']));
        }
        return true;

    }


    //针对小区新闻 发送工作人员 单独处理
    public function send_work($param){
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $user_list= D('House_worker')
            ->join('as a left join '.C('DB_PREFIX').'house_village_user_bind as b ON a.phone = b.phone and a.village_id = b.village_id')
            ->where([
                'a.village_id' =>$param['village_id'],
                'a.status' =>  1,
                'a.is_del' =>  0,
                'b.type' =>  4,
                'b.status' =>  1,
                'b.pigcms_id' =>  array('gt', 0),
            ])->where('a.openid !=""')->field('b.pigcms_id,a.village_id,a.openid,a.name,a.nickname')->order('a.wid desc')->select();
        if(!$user_list){
            return true;
        }
        $params = [
            'plan_send' =>1
        ];
        foreach ($user_list as $vv){
            $href = $param['site_url'] . $param['base_url'] . 'pages/village/index/villagecontent?village_id=' . $vv['village_id'] . ($vv['pigcms_id'] ? '&pigcms_id=' . $vv['pigcms_id'] : '') . '&news_id=' . $param['news_id'];
            $model->sendTempMsg('OPENTM405462911', array(
                'href' => $href,
                'wecha_id' => $vv['openid'],
                'first' => '您好，'.$param['village_name'].'发布了一条新的消息【'.$param['news_title'].'】\n',
                'keyword1' => ''.C('config.house_name').'新闻',
                'keyword2' =>'已发送',
                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                'keyword4' => $vv['nickname'],
                'remark' => '\n请点击查看详细信息！'
            ),0,$param['property_id'],2,$params);
        }
        return true;

    }



}
?>