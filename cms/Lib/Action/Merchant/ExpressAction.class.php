<?php

class ExpressAction extends BaseAction
{
	public function index()
	{
		$where = array('mer_id' => $this->merchant_session['mer_id']);

		$count = D('Express_template')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		
		$templates = D('Express_template')->field(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$list = array();
		foreach ($templates as $row) {
			$tids[] = $row['id'];
			$row['value_list'] = null;
			$list[$row['id']] = $row;
		}
		
		$areas = D('Express_template_area')->field(true)->where(array('tid' => array('in', $tids)))->select();
		
		$area_list = array();
		foreach ($areas as $arow) {
			$area_list[$arow['vid']][] = $arow;
		}
		
		$values = D('Express_template_value')->field(true)->where(array('tid' => array('in', $tids)))->select();
		foreach ($values as $v) {
			$v['area_list'] = $area_list[$v['id']];
			if($list[$v['tid']]['freight_type']==1){
				$v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
				$v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
				$v['add_weight'] = floatval($v['add_weight']);
			}
			$list[$v['tid']]['value_list'][] = $v;
		}
// 		echo '<pre/>';
// 		print_r($list);die;
		
		$this->assign('pagebar', $p->show());
		$this->assign('template_list', $list);
		$this->display();
	}
	
	public function add()
	{
		$this->display('edit');
	}
	
	public function edit()
	{
		$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
		$template = D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tid))->find();
		if (empty($template)) {
			$this->error(L_('不合法的模板信息！'));
		}
		$areas = D('Express_template_area')->field(true)->where(array('tid' => $tid))->select();
		
		$area_list = array();
		foreach ($areas as $arow) {
			$area_list[$arow['vid']][] = $arow;
		}
		
		$values = D('Express_template_value')->field(true)->where(array('tid' => $tid))->select();
		foreach ($values as $v) {
			$v['area_list'] = $area_list[$v['id']];
			if($template['freight_type']==1){
				$v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
				$v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
				$v['add_weight'] = floatval($v['add_weight']);
			}
			$template['value_list'][] = $v;
		}
		
		$this->assign('template', $template);
		$this->display();
	}
	
	
	public function save()
	{
		$tpl_id = isset($_POST['tpl_id']) ? intval($_POST['tpl_id']) : 0;
		$name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : '';
		$areas = isset($_POST['area']) ? $_POST['area'] : '';
		if (empty($name)) {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('模板名不能为空！'))));
		}
		if (empty($areas)) {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('模板内容不能为空！'))));
		}
		$Express_template = D('Express_template');
		
		$Express_template_value = D('Express_template_value');
		
		$Express_template_area = D('Express_template_area');
		
		$template = D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'name' => $name))->find();
		if ($template && $template['id'] != $tpl_id) {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('模板名已存在！'))));
		}
		/*计费模式*/
		$freight_type=I('freight_type',1,'intval');

		$template = $Express_template->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tpl_id))->find();
		if ($template) {
			if ($Express_template->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tpl_id))->save(array('name' => $name,'freight_type'=>$freight_type,'dateline' => time()))) {
				$template_value = $Express_template_value->field(true)->where(array('tid' => $tpl_id))->select();
				$tids = array();
				foreach ($template_value as $tv) {
					$tids[] = $tv['id'];
				}
				$Express_template_area->where(array('tid' => $tpl_id))->delete();
			} else {
				$tpl_id = 0;
			}
		} else {
			$tpl_id = $Express_template->add(array('name' => $name, 'mer_id' => $this->merchant_session['mer_id'],'freight_type'=>$freight_type,'dateline' => time()));
		}
		
		if ($tpl_id) {
			$database_area = D('Area');
			foreach ($areas as $row) {
				$vid = intval($row['vid']);
				$freight = $row['freight'];
				$first_weight = $row['first_weight'];
				$first_freight = $row['first_freight'];
				$add_weight = $row['add_weight'];
				$add_freight = $row['add_freight'];
				$full_money = $row['full_money'];
				$data=array();
				$data['freight']=$freight;
				$data['first_weight']=$first_weight;
				$data['first_freight']=$first_freight;
				$data['add_weight']=$add_weight;
				$data['add_freight']=$add_freight;
				$data['full_money']=$full_money;
				$data['tid']=$tpl_id;
				$data['dateline']=time();

				if ($vid) {
					$Express_template_value->where(array('id' => $vid))->save($data);
					$tids = array_diff($tids, array($vid));
				} else {
					$vid = $Express_template_value->add($data);
				}
				foreach ($row['aids'] as $aid) {
					if ($area = $database_area->field(true)->where(array('area_id' => $aid))->find()) {
						$Express_template_area->add(array('tid' => $tpl_id, 'area_id' => $aid, 'area_name' => $area['area_name'], 'vid' => $vid));
					} elseif ($aid == 0) {
						$Express_template_area->add(array('tid' => $tpl_id, 'area_id' => $aid, 'area_name' => L_('同城'), 'vid' => $vid));
					}
				}
			}
			if ($tids) {
				$Express_template_value->where(array('id' => array('in', $tids), 'tid' => $tpl_id))->delete();
			}
			exit(json_encode(array('err_code' => 0, 'err_msg' => L_('保存成功！'))));
		} else {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('保存失败，稍后重试！'))));
		}
	}
	
	public function delete()
	{
		$tpl_id = isset($_POST['tpl_id']) ? intval($_POST['tpl_id']) : 0;
		if (D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tpl_id))->find()) {
			D('Express_template')->where(array('id' => $tpl_id))->delete();
			D('Express_template_area')->where(array('tid' => $tpl_id))->delete();
			D('Express_template_value')->where(array('tid' => $tpl_id))->delete();
			exit(json_encode(array('err_code' => 0, 'err_msg' => L_('删除成功！'))));
		} else {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('删除数据有误'))));
		}
	}
	
	public function detail(){
		if($_GET['type']){
			$express_code = D('Express')->where(['code' => $_GET['type']])->getField('kuaidiniao_code');
		}else{
			$express_code = D('Express')->where(['name' => $_GET['com']])->getField('kuaidiniao_code');
		}
		
		$kdniao = new kdniao();
		
		$result = $kdniao->getOrderTracesByJson($express_code, $_GET['postid']);
		$this->assign('result', $result);
		$this->display();
	}
}