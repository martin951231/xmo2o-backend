<?php

class Market_expressAction extends BaseAction
{
	public function index()
	{
		$where = array('mer_id' => $this->merchant_session['mer_id']);

		$count = D('Market_express_template')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		
		$templates = D('Market_express_template')->field(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();



		$list = array();
        $tids = array();
		foreach ($templates as $row) {
			$tids[] = $row['id'];
			$list[$row['id']] = $row;
		}

        $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => array('in', $tids)))->select();

        $tmids = array();
		if ($template_msgs) {
		    foreach ($template_msgs as $val) {
                $tmids[] = $val['id'];
		        $val['value_list'] = null;
		        if (empty($list[$val['tid']]['msg'])) {
                    $list[$val['tid']]['msg'] = array();
                }
                $list[$val['tid']]['msg'][$val['id']] = $val;
            }
        }

		if ($tmids) {
            $areas = D('Market_express_template_area')->field(true)->where(array('tmid' => array('in', $tmids)))->select();
        } else {
            $areas = D('Market_express_template_area')->field(true)->where(array('tid' => array('in', $tids)))->select();
        }

        $area_list = array();
        $area_info = array();
        foreach ($areas as $arow) {
            $area_list[$arow['vid']][] = $arow;
            if (!$area_info[$arow['vid']]['area_info'] && $arow['area_name']) {
                $area_info[$arow['vid']]['area_info'] .= $arow['area_name'];
            } elseif ($arow['area_name']) {
                $area_info[$arow['vid']]['area_info'] .= ',' . $arow['area_name'];
            }
        }

        if ($tmids) {
            $values = D('Market_express_template_value')->field(true)->where(array('tmid' => array('in', $tmids)))->select();
        } else {
            $values = D('Market_express_template_value')->field(true)->where(array('tid' => array('in', $tids)))->select();
        }
		foreach ($values as $v) {
            $v['area_list'] = $area_list[$v['id']];
            $v['area_info'] = $area_info[$v['id']]['area_info'];
			if($list[$v['tid']]['msg'][$v['tmid']]['freight_type']==1){
				$v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
				$v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
				$v['add_weight'] = floatval($v['add_weight']);
			}
			$list[$v['tid']]['msg'][$v['tmid']]['value_list'][] = $v;
		}
		
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
		$template = D('Market_express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tid))->find();
		if (empty($template)) {
			$this->error(L_('不合法的模板信息！'));
		}

        $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $tid))->select();

        $tmids = array();
        if ($template_msgs) {
            foreach ($template_msgs as $val) {
                $tmids[] = $val['id'];
                if (empty($template['msg'])) {
                    $template['msg'] = array();
                }
                $template['msg'][$val['id']] = $val;
            }
        }

        if ($tmids) {
            $areas = D('Market_express_template_area')->field(true)->where(array('tmid' => array('in', $tmids)))->select();
        } else {
            $areas = D('Market_express_template_area')->field(true)->where(array('tid' => $tid))->select();
        }
		
		$area_list = array();
        $area_info = array();
        foreach ($areas as $arow) {
            $area_list[$arow['vid']][] = $arow;
            if (!$area_info[$arow['vid']]['area_info'] && $arow['area_name']) {
                $area_info[$arow['vid']]['area_info'] .= "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
            } elseif ($arow['area_name']) {
                $area_info[$arow['vid']]['area_info'] .= ',' . "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
            }
        }

		$values = D('Market_express_template_value')->field(true)->where(array('tid' => $tid))->select();
        $template['value_list'] = array();
        foreach ($values as $v) {
            $v['area_list'] = $area_list[$v['id']];
            $v['area_info'] = $area_info[$v['id']]['area_info'];
            $msg = $template['msg'][$v['tmid']];
            if($msg['freight_type']==1){
                $v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
                $v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
                $v['add_weight'] = floatval($v['add_weight']);
            }
            $template['freight_type_'.$msg['type']] = $msg['freight_type'];
            $template['value_list'][$msg['type']][] = $v;
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
        $market_express_template = D('Market_express_template');
        $market_express_template_msg = D('Market_express_template_msg');
        $market_express_template_value = D('Market_express_template_value');
        $market_express_template_area = D('Market_express_template_area');
		
		$template = $market_express_template->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'name' => $name))->find();
		if ($template && $template['id'] != $tpl_id) {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('模板名已存在！'))));
		}
		/*配送方式*/
		$is_type=I('is_type',0,'intval');

		$template = $market_express_template->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tpl_id))->find();
		if ($template) {
			if ($market_express_template->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tpl_id))->save(array('name' => $name,'is_type'=>$is_type,'dateline' => time()))) {
				$template_value = $market_express_template_value->field(true)->where(array('tid' => $tpl_id))->select();
				$tids = array();
				foreach ($template_value as $tv) {
					$tids[] = $tv['id'];
				}
                $market_express_template_area->where(array('tid' => $tpl_id))->delete();
			} else {
				$tpl_id = 0;
			}
		} else {
			$tpl_id = $market_express_template->add(array('name' => $name, 'mer_id' => $this->merchant_session['mer_id'],'is_type'=>$is_type,'dateline' => time()));
		}
		
		if ($tpl_id) {
			$database_area = D('Area');
			$freight_type = isset($_POST['freight_type']) ? $_POST['freight_type'] : array();
			if (count($freight_type) != count($areas)) {
			    fdump_sql($_POST, 'market_express_template_save');
                exit(json_encode(array('err_code' => 1, 'err_msg' => L_('参数错误！'))));
            }
            $market_express_template_msg->where(array('tid' => $tpl_id))->delete();
			foreach ($areas as $key=>$item) {
			    $msg = array();
                $msg['tid']=$tpl_id;
                $msg['mer_id']=$this->merchant_session['mer_id'];
                $msg['freight_type'] = $freight_type[$key];
                $msg['type'] = $key + 1;
                $msg['dateline']=time();
                $tmid = $market_express_template_msg->add($msg);
                if (!$tmid) {
                    fdump_sql($market_express_template_msg, 'market_express_template_msg_add');
                    exit(json_encode(array('err_code' => 1, 'err_msg' => L_('保存失败，稍后重试！'))));
                }
                foreach ($item as $row) {
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
                    $data['tmid']=$tmid;
                    $data['dateline']=time();

                    if ($vid) {
                        $market_express_template_value->where(array('id' => $vid))->save($data);
                        $tids = array_diff($tids, array($vid));
                    } else {
                        $vid = $market_express_template_value->add($data);
                    }
                    foreach ($row['aids'] as $aid) {
                        if ($area = $database_area->field(true)->where(array('area_id' => $aid))->find()) {
                            $market_express_template_area->add(array('tid' => $tpl_id, 'tmid' => $tmid, 'area_id' => $aid, 'area_name' => $area['area_name'], 'vid' => $vid));
                        } elseif ($aid == 0) {
                            $market_express_template_area->add(array('tid' => $tpl_id, 'tmid' => $tmid, 'area_id' => $aid, 'area_name' => L_('同城'), 'vid' => $vid));
                        }
                    }
                }
            }
			if ($tids) {
                $market_express_template_value->where(array('id' => array('in', $tids), 'tid' => $tpl_id))->delete();
			}
			exit(json_encode(array('err_code' => 0, 'err_msg' => L_('保存成功！'))));
		} else {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('保存失败，稍后重试！'))));
		}
	}
	
	public function delete()
	{
		$tpl_id = isset($_POST['tpl_id']) ? intval($_POST['tpl_id']) : 0;
		if (D('Market_express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'id' => $tpl_id))->find()) {
			D('Market_express_template')->where(array('id' => $tpl_id))->delete();
            D('Market_express_template_msg')->where(array('tid' => $tpl_id))->delete();
			D('Market_express_template_area')->where(array('tid' => $tpl_id))->delete();
			D('Market_express_template_value')->where(array('tid' => $tpl_id))->delete();
			exit(json_encode(array('err_code' => 0, 'err_msg' => L_('删除成功！'))));
		} else {
			exit(json_encode(array('err_code' => 1, 'err_msg' => L_('删除数据有误'))));
		}
	}
}