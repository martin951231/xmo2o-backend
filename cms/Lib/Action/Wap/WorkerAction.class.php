<?php
class WorkerAction extends BaseAction
{
	protected $openid;
	
	protected $worker;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->openid = $_SESSION['openid'];// = 'oG77RjiV06w5wBzs_sO4vSHvFqrM';
		if (empty($this->openid)) {
			$this->error_tips('在微信中操作');
		}
		$this->worker = D('House_worker')->field(true)->where(array('openid' => $this->openid,'is_del'=>0))->find();
		if (empty($this->worker)) {
			$this->error_tips('您未加入到任何小区的工作人员，请联系社区物业添加');
		}else if ($this->worker['status'] != 1) {
			$this->error_tips('您的账号不正常，请联系社区物业');
		}
	}
	
	public function repair_list()
	{
// 		$repair_list = D('House_village_repair_list')->field(true)->where(array('village_id' => $this->worker['village_id'], 'wid' => $this->worker['wid']))->select();
// 		$this->assign('repair_list', $repair_list);
		$this->display();
	}
	
	public function index()
	{
// 		$repair_list = D('House_village_repair_list')->field(true)->where(array('village_id' => $this->worker['village_id'], 'wid' => $this->worker['wid']))->select();
// 		$this->assign('repair_list', $repair_list);
		$this->display();
	}
	
	public function ajax_list()
	{
		$village = D('House_village')->field(true)->where(array('village_id' => $this->worker['village_id']))->find();
		if (empty($village))exit(json_encode(array('status' => 0)));
		$status = isset($_GET['status']) ? intval($_GET['status']) : -2;
		//$where = 'village_id=' . $this->worker['village_id'] . ' AND type='. $this->worker['type'] ;

		$where = 'village_id=' . $this->worker['village_id'];

		if($this->worker['type'] == 0){
			$where .= ' AND type=3';
		}elseif($this->worker['type'] == 1){
			$where .= ' AND type in (1,4)';
		}

		if ($status == -2) {
			exit(json_encode(array('status' => 0)));
		} elseif ($status == 1) {
			$where .= ' AND status<2';
			if ($village['handle_type']) {
				$where .= ' AND (wid=0 OR wid=' . $this->worker['wid'] . ')';
			} else {
				$where .= ' AND wid=' . $this->worker['wid'];
			}
		} elseif ($status == 2) {
			$where .= ' AND status=2 AND wid=' . $this->worker['wid'];
		} elseif ($status == 3) {
			$where .= ' AND status>2 AND wid=' . $this->worker['wid'];
		} else {
			if ($village['handle_type']) {
				$where .= ' AND (wid=0 OR wid=' . $this->worker['wid'] . ')';
			} else {
				$where .= ' AND wid=' . $this->worker['wid'];
			}
		}
		$repair_list = D('House_village_repair_list')->field(true)->where($where)->order('pigcms_id DESC')->select();
		if ($repair_list) {
			foreach ($repair_list as &$r) {
				$r['url'] = U('Worker/detail', array('pigcms_id' => $r['pigcms_id']));
				$r['content'] = msubstr($r['content'], 0, 20);
				$r['time'] = date('Y-m-d H:i', $r['time']);
			}
			exit(json_encode(array('status' => 1, 'order_list' => $repair_list)));
		}
		exit(json_encode(array('status' => 0)));
	}
	
	public function detail()
	{
		$pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
		$repair_detail = D('House_village_repair_list')->field(true)->where(array('pigcms_id' => $pigcms_id, 'village_id' => $this->worker['village_id']))->find();
		if(empty($repair_detail)){
			$this->error_tips('当前内容不存在');
		}
		if ($repair_detail['wid'] && $repair_detail['wid'] != $this->worker['wid']) $this->error_tips('该任务已经被其他人接收了');
		if($repair_detail['pic']){
            if (strpos($repair_detail['pic'],'|')!==false) {
                $repair_detail['picArr'] = explode('|', $repair_detail['pic']);
            } elseif (strpos($repair_detail['pic'],'；')!==false) {
                $repair_detail['picArr'] = explode('；', $repair_detail['pic']);
            } else {
                $repair_detail['picArr'] = explode(';', $repair_detail['pic']);
            }
			foreach ($repair_detail['picArr'] as &$v1) {
                if (strpos($v1,'/upload/')!==false){
                    $v1 = replace_file_domain($v1);
                }elseif(substr($v1,3,7) == '000/000'){
                    $v1 = replace_file_domain('/upload/activity/'.$v1);
                } else {
                    $v1 = replace_file_domain('/upload/house/'.$v1);
                }
			}
		}
		if($repair_detail['reply_pic']){
            if (strpos($repair_detail['reply_pic'],'|')!==false) {
                $repair_detail['reply_picArr'] = explode('|', $repair_detail['reply_pic']);
            } elseif (strpos($repair_detail['reply_pic'],'；')!==false) {
                $repair_detail['reply_picArr'] = explode('；', $repair_detail['reply_pic']);
            } else {
                $repair_detail['reply_picArr'] = explode(';', $repair_detail['reply_pic']);
            }
            if ($repair_detail['reply_picArr']) {
                foreach ($repair_detail['reply_picArr'] as $key1=>$item1) {
                    if (strpos($item1,'/upload/')!==false){
                        $repair_detail['reply_picArr'][$key1] = replace_file_domain($item1);
                    } else {
                        $repair_detail['reply_picArr'][$key1] = replace_file_domain('/upload/worker/'.$item1);
                    }
                }
            }
		}
		if($repair_detail['comment_pic']){
            if (strpos($repair_detail['comment_pic'],'|')!==false) {
                $repair_detail['comment_picArr'] = explode('|', $repair_detail['comment_pic']);
            } elseif (strpos($repair_detail['comment_pic'],'；')!==false) {
                $repair_detail['comment_picArr'] = explode('；', $repair_detail['comment_pic']);
            } else {
                $repair_detail['comment_picArr'] = explode(';', $repair_detail['comment_pic']);
            }
            if ($repair_detail['comment_picArr']) {
                foreach ($repair_detail['comment_picArr'] as $key2=>$item2) {
                    if (strpos($item2,'/upload/')!==false){
                        $repair_detail['comment_picArr'][$key2] = replace_file_domain($item2);
                    } else {
                        $repair_detail['comment_picArr'][$key2] = replace_file_domain('/upload/house/'.$item2);
                    }
                }
            }
		}
		//查询跟进内容
		$follow = array();
		if ($repair_detail['status'] >= 2) {
			$follow = D('House_village_repair_follow')->field(true)->where(array('repair_id' => $pigcms_id))->select();
			if ($follow) {
				foreach ($follow as &$value) {
					$value['time'] = date('Y-m-d H:i:s',$value['time']);
				}
			}
		}
		$this->assign('follow', $follow);
		
		$now_user_info = D('House_village_user_bind')->field(true)->where(array('uid' => $repair_detail['uid'], 'pigcms_id' => $repair_detail['bind_id']))->find();

		if ($repair_detail['waste_type']) {
			$repair_detail['waste_type_name'] = D('House_village_repair_list')->waste_type($repair_detail['waste_type']);
		}
        if (!$now_user_info['name']) {
            $now_user_info['name'] = $repair_detail['user_name'] ? trim($repair_detail['user_name']) : '暂无';
        }
        if (!$now_user_info['phone']) {
            $now_user_info['phone'] = $repair_detail['phone'] ? trim($repair_detail['phone']) : '暂无';
        }
        if ($now_user_info['single_id'] && $now_user_info['floor_id'] && $now_user_info['layer_id'] && $now_user_info['vacancy_id'] && $now_user_info['village_id']) {
            $now_user_info['address'] = D('House_village')->get_single_floor_room($now_user_info['single_id'],$now_user_info['floor_id'],$now_user_info['layer_id'],$now_user_info['vacancy_id'],$now_user_info['village_id']);
        } elseif (!$now_user_info['address']) {
            $now_user_info['address'] = '暂无';
        }




        $this->assign('repair_detail', $repair_detail);
		$this->assign('now_user_info', $now_user_info);
		$this->display();
	}
	
	public function do_work()
	{
		$pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
		$status = isset($_POST['status']) ? intval($_POST['status']) : 2;
		$msg = null;
		if (isset($_POST['msg'])) {
			$msg = htmlspecialchars($_POST['msg']);
		}
		$status = isset($_POST['status']) ? intval($_POST['status']) : 2;
		
		$where = array('pigcms_id' => $pigcms_id, 'village_id' => $this->worker['village_id']);
		
		$repair_detail = D('House_village_repair_list')->field(true)->where($where)->find();
		if(empty($repair_detail)){
			exit(json_encode(array('status' => 0, 'msg' => '当前内容不存在')));
		}
		if ($repair_detail['wid'] && $repair_detail['wid'] != $this->worker['wid']) exit(json_encode(array('status' => 0, 'msg' => '该任务已经被其他人接收了')));
		
		if ($status != 2 && $status != 3) exit(json_encode(array('status' => 0, 'msg' => '更新的状态不正确')));
		if ($repair_detail['status'] > 1 && $status == 2 && $msg === null) exit(json_encode(array('status' => 0, 'msg' => '该任务已接')));
		if ($repair_detail['status'] != 2 && $status == 3) exit(json_encode(array('status' => 0, 'msg' => '该任务所处的状态不能修改成已处理')));
		$data = array('status' => $status);
		$repair_detail['wid'] || $data['wid'] = $this->worker['wid'];
		if ($status == 3) {
			$inputimg = isset($_POST['inputimg']) ? $_POST['inputimg'] :'';
			$picArr = array();
			if (!empty($inputimg)) {
				foreach ($inputimg as $imgv) {
					$imgv = str_replace('/upload/worker/', '', $imgv);
					$picArr[] = $imgv;
				}
			}
			$data['reply_pic'] = implode('|', $picArr);
			$data['reply_content'] = isset($_POST['content']) ? htmlspecialchars(trim($_POST['content'])) : '';
			$data['reply_time'] = time();
		}
		if ($msg !== null) {
			$data['msg'] = $msg;
		}
		if (D('House_village_repair_list')->where($where)->save($data)) {
			if ($status == 2) {
				if ($msg !== null) D('House_village_repair_log')->add_log(array('status' => $data['status'], 'repair_id' => $pigcms_id, 'phone' => $this->worker['phone'], 'name' => $this->worker['name']));
			} else {
				D('House_village_repair_log')->add_log(array('status' => $data['status'], 'repair_id' => $pigcms_id, 'phone' => $this->worker['phone'], 'name' => $this->worker['name']));
			}
			
			if ($status == 3 && $repair_detail['status'] == 2) D('House_worker')->add_num($this->worker['wid']);
			exit(json_encode(array('status' => 1, 'msg' => 'ok')));
		} else {
			exit(json_encode(array('status' => 0, 'msg' => '处理失败')));
		}
	}
	
	public function ajaxImgUpload()
	{
		$filename = trim($_POST['filename']);
		$img = $_POST[$filename];
		$img = str_replace('data:image/png;base64,', '', $img);
		$img = str_replace(' ', '+', $img);
		$imgdata = base64_decode($img);
		$img_order_id = sprintf("%09d",$this->worker['wid']);
		$rand_num = mt_rand(10,99).'/'.substr($img_order_id,0,3).'/'.substr($img_order_id,3,3).'/'.substr($img_order_id,6,3);
		$getupload_dir = "/upload/worker/" .$rand_num;

		$upload_dir = "." . $getupload_dir;
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
		$newfilename = date('YmdHis') . '.jpg';
		$save = file_put_contents($upload_dir . '/' . $newfilename, $imgdata);
		
		if ($save) {
			//判断是否需要上传至云存储
			$file_handle = new file_handle();
			$file_handle->upload($upload_dir.'/'.$newfilename);
		
			exit(json_encode(array('error' => 0, 'data' => array('code' => 1, 'siteurl'=>$this->config['site_url'],'imgurl' =>$getupload_dir . '/' . $newfilename, 'msg' => ''))));
		} else {
			exit(json_encode(array('error' => 1, 'data' => array('code' => 0, 'url' => '', 'msg' => '保存失败！'))));
		}
	}
	
	// 保存跟进内容
	public function do_follow()
	{
		$pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
		
		
		$where = array('pigcms_id' => $pigcms_id, 'village_id' => $this->worker['village_id']);
		
		$repair_detail = D('House_village_repair_list')->field(true)->where($where)->find();
		if(empty($repair_detail)){
			exit(json_encode(array('status' => 0, 'msg' => '当前内容不存在')));
		}
		
		$data = array();
		$repair_detail['wid'] || $data['worker_id'] = $this->worker['wid'];

		$data['content'] = isset($_POST['followcontent']) ? htmlspecialchars(trim($_POST['followcontent'])) : '';
		if (!$data['content']) {
			exit(json_encode(array('status' => 0, 'msg' => '请填写跟进内容')));
		}
		$data['time'] = time();
		$data['village_id'] = $this->worker['village_id'];
		$data['repair_id'] = $pigcms_id;

		if (D('House_village_repair_follow')->where($where)->add($data)) {
			exit(json_encode(array('status' => 1, 'msg' => 'ok')));
		} else {
			exit(json_encode(array('status' => 0, 'msg' => '处理失败')));
		}
	}
}
?>