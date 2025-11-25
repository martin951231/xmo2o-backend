<?php
class Area_street_newsModel extends Model{
	
	protected $tableName = 'area_street_news';
	
	public function getlist($column){
		if(!$column['area_id']){
			return '';
		}
		$condition_table  = array(C('DB_PREFIX').'area_street_news'=>'n',C('DB_PREFIX').'area_street_news_category'=>'c',C('DB_PREFIX').'area_street'=>'a');
		$condition_where = " a.area_id = n.area_id  AND n.area_id = c.area_id AND  n.cat_id = c.cat_id AND c.cat_status=1  AND n.area_id=".$column['area_id'];
		if($column['status']){
			$condition_where .= " AND n.status = ".intval($column['status']);
		}
		$condition_field = 'n.*,c.cat_name';
		
		$order = ' n.is_hot DESC,n.news_id DESC ';
		import('@.ORG.merchant_page');
		$count_news = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_news,20,'page');
		$village_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();


		$return['pagebar'] = $p->show();
		$return['news_list'] = $village_list;
		
		return $return;
	}
	
	/*得到小区的新闻列表*/
	public function get_limit_list($area_id,$limit){
		$condition_table = array(C('DB_PREFIX').'area_street_news'=>'n',C('DB_PREFIX').'area_street_news_category'=>'c');
		$condition_where = 'n.area_id='.$area_id.' AND n.status=1 AND n.village_id = c.village_id AND  n.cat_id = c.cat_id AND c.cat_status=1';
		$order = '`is_hot` DESC,`add_time` DESC';
		return D('')->table($condition_table)->where($condition_where)->order($order)->limit($limit)->select();
	}
	
	/*得到分类下的新闻列表*/
	public function get_list_by_cid($cat_id,$area_id,$page=1)
    {
        $where = array(
            'status'=>1,
			'cat_id'=>$cat_id,
			'area_id'=>$area_id
        );
		return $this->field(true)->where($where)->order('`is_hot` DESC,`add_time` DESC')->page($page, 10)->select();
	}
	
	public function get_one($news_id){
		return $this->field(true)->where(array('status'=>'1','news_id'=>$news_id))->find();
	}

	public function get_one_new($news_id,$area_id=''){
		$where = array('status'=>'1','news_id'=>$news_id);
		if($area_id){
           $where['area_id'] = $area_id;
		}
		return $this->field(true)->where($where)->find();
	}



	public function get_notice_new($village_id, $floor_id=0)
	{
	    $where = array(
            'status'=>1,
            'village_id'=>$village_id
        );
        if ($floor_id>0) {
            $where['_string'] .= " AND FIND_IN_SET('$floor_id', floor_txt)";
        } else {
            $where['_string'] .= " AND (floor_txt='' OR floor_txt<=0)";
        }
		$new = $this->where($where)->order('news_id desc')->limit(1)->find();
		if($new){
			$new['add_time'] = date('m-d',$new['add_time']);
			return $new;
		}

		return [];
	}

	public function get_popup_new_list($village_id,$time, $floor_id=0)
	{
		$data['_string'] = ' add_time > '.$time;
		if ($floor_id>0) {
            $data['_string'] .= " AND FIND_IN_SET('$floor_id', floor_txt)";
        } else {
            $data['_string'] .= " AND (floor_txt='' OR floor_txt<=0)";
        }
		$new_list = $this->where(array('status'=>1,'village_id'=>$village_id))->where($data)->order('news_id desc')->limit(3)->select();
		foreach($new_list as $k=>&$v){
			$v['add_time'] = date('m-d',$v['add_time']);
		}
		return $new_list;

	}


	public function implement_send_notice($userInfo,$param){
        fdump_api(['获取参数=='.__LINE__,$userInfo,$param],'area_street_send_notice/send_log',1);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $result='';
        switch ($param['send_user_type']) {
            case '1': // 仅业主
                if ($userInfo['type'] != 4) {
                    // 新增模板消息（消息发送状态提醒）
                    $result=$model->sendTempMsg('OPENTM405462911', array(
                        'href' => $param['href'], 
                        'wecha_id' => $userInfo['openid'], 
                        'first' => '您好，'.$param['area_name'].'发布了一条新的消息【'.$param['title'].'】\n', 
                        'keyword1' => ''.C('config.house_name').'新闻', 
                        'keyword2' =>'已发送',
                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                        'keyword4' => $userInfo['nickname'], 
                        'remark' => '\n请点击查看详细信息！',
                        'new_info' => [//新版本发送需要的信息
                            'tempKey'=>'44716',//新模板号
                            'thing12'=>$param['title']?:(C('config.house_name').'新闻'),//服务项目
                            'phrase2'=>'新闻',//订单类型
                            'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                            'thing11'=>'无',//服务地址
                        ],
                    ));
                }
                break;
            case '2': // 业主和工作人员
                // 新增模板消息（消息发送状态提醒）
                $result=$model->sendTempMsg('OPENTM405462911', array(
                    'href' => $param['href'], 
                    'wecha_id' => $userInfo['openid'], 
                    'first' => '您好，'.$param['area_name'].'发布了一条新的消息【'.$param['title'].'】\n', 
                    'keyword1' => ''.C('config.house_name').'新闻', 
                    'keyword2' =>'已发送',
                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                    'keyword4' => $userInfo['nickname'], 
                    'remark' => '\n请点击查看详细信息！',
                    'new_info' => [//新版本发送需要的信息
                        'tempKey'=>'44716',//新模板号
                        'thing12'=>$param['title']?:(C('config.house_name').'新闻'),//服务项目
                        'phrase2'=>'新闻',//订单类型
                        'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                        'thing11'=>'无',//服务地址
                    ],
                ));
                break;
            case '3': // 仅工作人员
                if ($userInfo['type'] == 4) {
                    // 新增模板消息（消息发送状态提醒）
                    $result=$model->sendTempMsg('OPENTM405462911', array(
                        'href' => $param['href'], 
                        'wecha_id' => $userInfo['openid'], 
                        'first' => '您好，'.$param['area_name'].'发布了一条新的消息【'.$param['title'].'】\n', 
                        'keyword1' => ''.C('config.house_name').'新闻', 
                        'keyword2' =>'已发送','keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                        'keyword4' => $userInfo['nickname'], 
                        'remark' => '\n请点击查看详细信息！',
                        'new_info' => [//新版本发送需要的信息
                            'tempKey'=>'44716',//新模板号
                            'thing12'=>$param['title']?:(C('config.house_name').'新闻'),//服务项目
                            'phrase2'=>'新闻',//订单类型
                            'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                            'thing11'=>'无',//服务地址
                        ],
                    ));
                }
                break;
        }
        fdump_api(['处理参数=='.__LINE__,$userInfo,$param,$result],'area_street_send_notice/send_log',1);
        return true;
    }

}