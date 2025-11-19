<?php
/*
 * @  通联优惠管理
 * @  BuildTime  2018/12/21 15:00
 */

class AllinpayAction extends BaseAction
{
	public function discount()
	{
	    $type = isset($_GET['searchstatus']) ? intval($_GET['searchstatus']) : -1;
	    $province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
	    $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
	    $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
	    $sort = isset($_GET['sort']) ? intval($_GET['sort']) : 0;
	    $_GET['searchstatus'] = $type;
        $begin_time =  isset($_GET['begin_time']) ? strtotime($_GET['begin_time']) : 0;
        $end_time =  isset($_GET['end_time']) ? strtotime($_GET['end_time'].' 23:59:59') : 0;
	    
	    
	    if ($this->system_session['area_id']) {
	        $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
	        if ($now_area['area_type'] == 3) {
	            $area_index = 'area_id';
	            $area_id = $this->system_session['area_id'];
	            $city_id = $now_area['area_pid'];
	            $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
	            $province_id = $temp_area['area_pid'];
	        } elseif($now_area['area_type'] == 2) {
	            $area_index = 'city_id';
	            $city_id = $this->system_session['area_id'];
	            $province_id = $now_area['area_pid'];
	        } elseif ($now_area['area_type'] == 1) {
	            $area_index = 'province_id';
	            $province_id = $this->system_session['area_id'];
	        }
	        $this->assign('admin_area', $now_area['area_type']);
	    }
	    
	    if ($province_id && $city_id && $area_id) {
	        $sql = "SELECT DISTINCT d.* FROM " . C('DB_PREFIX') . "allinpay_discount AS d LEFT JOIN " . C('DB_PREFIX') . "allinpay_discount_area AS a ON a.did=d.id WHERE (a.aid=" . $area_id . ' OR (d.use_area=0))';
	        if ($type != -1) {
	            $sql .= ' AND type=' . $type;
	        }
            $sql .= ' AND status!=4';

	        if($begin_time && $end_time){
	            $sql .= ' AND d.create_time BETWEEN '.$begin_time.' AND '.$end_time;
            }
	    } elseif ($province_id && $city_id) {
	        $ids = array($city_id);
	        $areas = D('Area')->field('area_id')->where(array('area_pid' => $city_id, 'is_open' => 1))->select();
	        foreach ($areas as $area) {
	            $ids[] = $area['area_id'];
	        }
	        $sql = "SELECT DISTINCT d.* FROM " . C('DB_PREFIX') . "allinpay_discount AS d LEFT JOIN " . C('DB_PREFIX') . "allinpay_discount_area AS a ON a.did=d.id WHERE (a.aid IN (" . implode(',', $ids) . ') OR (d.use_area=0))';
	        if ($type != -1) {
	            $sql .= ' AND type=' . $type;
	        }
	        $sql .= ' AND status!=4';

            if($begin_time && $end_time){
                $sql .= ' AND d.create_time BETWEEN '.$begin_time.' AND '.$end_time .' GROUP BY d.id';
            }
	    } elseif ($province_id) {
	        $ids = array($province_id);
	        $areas = D('Area')->field('area_id')->where(array('area_pid' => $province_id, 'is_open' => 1))->select();
	        $cityIds = array();
	        foreach ($areas as $area) {
	            $ids[] = $area['area_id'];
	            $cityIds[] = $area['area_id'];
	        }
	        if ($cityIds) {
	            $areas = D('Area')->field('area_id')->where(array('area_pid' => array('in', $cityIds), 'is_open' => 1))->select();
	            foreach ($areas as $area) {
	                $ids[] = $area['area_id'];
	            }
	        }
	        $sql = "SELECT DISTINCT d.* FROM " . C('DB_PREFIX') . "allinpay_discount AS d LEFT JOIN " . C('DB_PREFIX') . "allinpay_discount_area AS a ON a.did=d.id WHERE (a.aid IN (" . implode(',', $ids) . ') OR (d.use_area=0))';
	        if ($type != -1) {
	            $sql .= ' AND d.type=' . $type;
	        }
	        $sql .= ' AND status!=4';
            if($begin_time && $end_time){
                $sql .= ' AND d.create_time BETWEEN '.$begin_time.' AND '.$end_time.'  GROUP BY d.id';
            }
	    } else {
	        $sql = "SELECT DISTINCT * FROM " . C('DB_PREFIX') . "allinpay_discount as d WHERE status!=4";
            if ($type != -1) {
                $sql .= ' AND d.type=' . $type;
            }
            if($begin_time && $end_time){
                $sql .= ' AND create_time BETWEEN '.$begin_time.' AND '.$end_time;
            }
	    }
	    if ($sort) {
	        $sql .= ' ORDER BY d.id ASC';
	    } else {
	        $sql .= ' ORDER BY d.id DESC';
	    }
	    $discounts = D()->query($sql);
	    $count = count($discounts);
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $sql .= ' Limit '.$p->firstRow.','.$p->listRows;
        $discounts = D()->query($sql);
	    $now_time = time();
        foreach($discounts as &$discount){
            if($now_time > $discount['startdate'] && $now_time < $discount['enddate']){
                $discount['state'] = 1;
            }else{
                $discount['state'] = 0;
            }
        }
        $this->assign('pagebar',$p->show());
		$this->assign('discount_list', $discounts);
		$this->display();
	}

	public function discount_add()
	{
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	public function discount_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		$database_shop_discount = D('Allinpay_discount');
		$condition_now_shop_discount['id'] = intval($_GET['id']);
		$now_discount = $database_shop_discount->field(true)->where($condition_now_shop_discount)->find();
		if (empty($now_discount)) {
			$this->frame_error_tips('没有找到该优惠信息！');
		}
		$this->assign('now_discount', $now_discount);
		$this->display();
	}

	public function discount_modify()
	{
		if (IS_POST) {
		    $data = $_POST;
            $data['create_time'] = time();
            $data['startdate'] = strtotime($_POST['startdate']);
            $data['enddate'] = strtotime($_POST['enddate'].' 23:59:59');
			if ($did = M('Allinpay_discount')->add($data)) {
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}

	public function discount_amend()
	{
		if (IS_POST) {
            $data = $_POST;
            $data['startdate'] = strtotime($_POST['startdate']);
            $data['enddate'] = strtotime($_POST['enddate'].' 23:59:59');
			$database_shop_discount = D('Allinpay_discount');
			$where = array('id' => $_POST['id']);
			unset($data['id']);

			if ($database_shop_discount->where($where)->save($data)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
    
    public function area()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($discount = D('Allinpay_discount')->field(true)->where(array('id' => $id))->find()) {
            if ($discount['use_area'] == 0) {
                $this->error_tips('此优惠无需指定区域');
            }
            if (IS_POST) {
                $areaArray = $_POST['areaIds'];
                $data = array();
                foreach ($areaArray as $row) {
                    $arr = explode('-', $row);
                    if (count($arr) == 1) {
                        $data[$arr[0]] = 1;
                    } elseif (count($arr) == 2) {
                        unset($data[$arr[0]]);
                        $data[$arr[1]] = 2;
                    } elseif (count($arr) == 3) {
                        unset($data[$arr[0]], $data[$arr[1]]);
                        $data[$arr[2]] = 3;
                    }
                }
                if (empty($data)) {
                    $this->error_tips('请选择区域');
                }
                
                $discountAreaDB = D('Allinpay_discount_area');
                //删除已经设置的区域
                $discountAreaDB->where(array('did' => $id))->delete();
                $provinceIds = array();
                $cityIds = array();
                $areaIds = array();
                foreach ($data as $aid => $level) {
                    if ($level == 1) {
                        $provinceIds[] = $aid;
                    } elseif ($level == 2) {
                        $cityIds[] = $aid;
                    } elseif ($level == 3) {
                        $areaIds[] = $aid;
                    }
                    $discountAreaDB->add(array('did' => $id, 'aid' => $aid, 'level' => $level));
                }
                //删除已经设置的商家，但是不在新设置的区域内
                $merchantWhere = 'd.did=' . $id . ' AND m.status=1';
                $tempWhere = '';
                if ($provinceIds) {
                    $tempWhere = 'province_id NOT IN (' . implode(',', $provinceIds) . ')';
                }
                if ($cityIds) {
                    $tempWhere = empty($tempWhere) ? 'city_id NOT IN (' . implode(',', $cityIds) . ')': $tempWhere . ' AND city_id IN (' . implode(',', $cityIds) . ')';
                }
                if ($areaIds) {
                    $tempWhere = empty($tempWhere) ? 'area_id NOT IN (' . implode(',', $areaIds) . ')' : $tempWhere . ' AND area_id IN (' . implode(',', $areaIds) . ')';
                }
                if (!empty($tempWhere)) {
                    $merchantWhere .= ' AND (' . $tempWhere . ')';
                }
                $sql = "SELECT d.mer_id, d.id FROM" . C('DB_PREFIX') . 'allinpay_discount_merchant AS d INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON d.mer_id=m.mer_id WHERE ' . $merchantWhere;
                $deleteList = D()->query($sql);
                $discountMerchantDB = D('Allinpay_discount_merchant');
                foreach ($deleteList as $del) {
                    $discountMerchantDB->where(array('id' => $del['id'], 'mer_id' => $del['mer_id']))->delete();
                }
                $this->success('设置成功');
            } else {
                $where = array('area_type' => array('lt', 4), 'is_open' => 1);
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $where['area_id'] = $this->system_session['area_id'];
                    }  else {
                        $where['area_pid'] = $this->system_session['area_id'];
                    }
                }
                $areaList = D('Area')->field('area_id, area_pid, area_name')->where($where)->select();
                $list = D('Allinpay_discount_area')->field('aid')->where(array('did' => $id))->select();
                $areaIds = array();
                foreach ($list as $row) {
                    $areaIds[] = $row['aid'];
                }
                
                $tmpMap = array();
                foreach ($areaList as $item) {
                    $item['select'] = 0;
                    if (in_array($item['area_id'], $areaIds)) {
                        $item['select'] = 1;
                    }
                    $tmpMap[$item['area_id']] = $item;
                }
                
                $list = array();
                foreach ($areaList as $item) {
                    if (isset($tmpMap[$item['area_pid']])) {
                        $tmpMap[$item['area_pid']]['son_list'][$item['area_id']] = &$tmpMap[$item['area_id']];
                    } else {
                        $list[$item['area_id']] = &$tmpMap[$item['area_id']];
                    }
                }
                $this->assign('area_list', $list);
                $this->display();
            }
        } else {
            $this->error_tips('此优惠不存在');
        }
    }
    
    public function merchant()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($discount = D('Allinpay_discount')->field(true)->where(array('id' => $id))->find()) {
            if ($discount['use_type'] == 0) {
                $this->error_tips('此优惠无需指定商家');
            }
            $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
            
            $merchantWhere = 'm.status=1';
            
            if (! empty($_GET['keyword'])) {
                if ($_GET['searchtype'] == 'mer_id') {
                    $merchantWhere .= ' AND m.mer_id=' . $_GET['keyword'];
                } elseif ($_GET['searchtype'] == 'account') {
                    $merchantWhere .= ' AND m.account LIKE \'%' . $_GET['keyword'] . '%\'';
                } elseif ($_GET['searchtype'] == 'name') {
                    $merchantWhere .= ' AND m.name LIKE \'%' . $_GET['keyword'] . '%\'';
                } elseif ($_GET['searchtype'] == 'phone') {
                    $merchantWhere .= ' AND m.phone LIKE \'%' . $_GET['keyword'] . '%\'';
                }
            }
            
            
            if ($discount['use_area']) {
                $areaList = D('Allinpay_discount_area')->field(true)->where(array('did' => $id))->select();
                if (empty($areaList)) {
                    $this->error_tips('请先指定使用的区域');
                }
                $provinceIds = array();
                $cityIds = array();
                $areaIds = array();
                foreach ($areaList as $area) {
                    if ($area['level'] == 1) {
                        $provinceIds[] = $area['aid'];
                    } elseif ($area['level'] == 2) {
                        $cityIds[] = $area['aid'];
                    } elseif ($area['level'] == 3) {
                        $areaIds[] = $area['aid'];
                    }
                }
                $tempWhere = '';
                if ($provinceIds) {
                    $tempWhere = 'province_id IN (' . implode(',', $provinceIds) . ')';
                }
                if ($cityIds) {
                    $tempWhere = empty($tempWhere) ? 'city_id IN (' . implode(',', $cityIds) . ')': $tempWhere . ' OR city_id IN (' . implode(',', $cityIds) . ')';
                }
                if ($areaIds) {
                    $tempWhere = empty($tempWhere) ? 'area_id IN (' . implode(',', $areaIds) . ')' : $tempWhere . ' OR area_id IN (' . implode(',', $areaIds) . ')';
                }
                if (!empty($tempWhere)) {
                    $merchantWhere .= ' AND (' . $tempWhere . ')';
                }
            } else {
                if ($this->system_session['area_id']) {
                    $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
                    $merchantWhere .= ' AND ' . $area_index . '=' . $this->system_session['area_id'];
                }
            }
            if ($status != 0) {
                $list = D('Allinpay_discount_merchant')->field('mer_id')->where(array('did' => $id))->select();
                $merIdArr = array();
                foreach ($list as $l) {
                    $merIdArr[] = $l['mer_id'];
                }
                if ($merIdArr) {
                    if ($status == 1) {
                        $merchantWhere .= ' AND m.mer_id IN (' . implode(',', $merIdArr) . ')';
                    } else {
                        $merchantWhere .= ' AND m.mer_id NOT IN (' . implode(',', $merIdArr) . ')';
                    }
                }
            }
            
            $databaseMerchant = D('Merchant');
            $sqlCount = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . 'merchant AS m LEFT JOIN ' . C('DB_PREFIX') . 'allinpay_discount_merchant AS sm ON sm.did=' . $id . ' AND m.mer_id=sm.mer_id WHERE ' . $merchantWhere;
            $countList = $databaseMerchant->query($sqlCount);
            $count = isset($countList[0]['cnt']) ? intval($countList[0]['cnt']) : 0;
            import('@.ORG.system_page');
            $p = new Page($count, 15);
            $sql = "SELECT m.*, sm.id AS did FROM " . C('DB_PREFIX') . 'merchant AS m LEFT JOIN ' . C('DB_PREFIX') . 'allinpay_discount_merchant AS sm ON sm.did=' . $id . ' AND m.mer_id=sm.mer_id WHERE ' . $merchantWhere . ' ORDER BY m.mer_id DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
            $merchantList = $databaseMerchant->query($sql);
            $this->assign('merchantList', $merchantList);
            $this->assign('pagebar', $p->show());
            $this->assign('did', $id);
            $this->assign('status', $status);
            $this->display();
        } else {
            $this->error_tips('此优惠不存在');
        }
    }

    public function card()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($discount = D('Allinpay_discount')->field(true)->where(array('id' => $id))->find()) {
            if ($discount['card_limit'] == 0) {
                $this->error_tips('此优惠无需指定卡');
            }
            $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
            $cardWhere = 'c.cardbin != 0';
            if (! empty($_GET['keyword'])) {
                if ($_GET['searchtype'] == 'cardbin') {
                    if(!intval($_GET['keyword'])>0){
                        $this->error_tips('请输入有效卡bin');
                    }
                    $cardWhere .= ' AND c.cardbin=' . $_GET['keyword'];
                } elseif ($_GET['searchtype'] == 'bankname') {
                    $cardWhere .= ' AND c.bankname LIKE \'%' . $_GET['keyword'] . '%\'';
                } elseif ($_GET['searchtype'] == 'cardname') {
                    $cardWhere .= ' AND c.cardname LIKE \'%' . $_GET['keyword'] . '%\'';
                }
            }
            if ($status != 0) {
                $list = D('Allinpay_discount_card')->field('cardbin')->where(array('did' => $id))->select();
                $cardArr = array();
                foreach ($list as $l) {
                    $cardArr[] = $l['cardbin'];
                }
                if ($cardArr) {
                    if ($status == 1) {
                        $cardWhere .= ' AND c.cardbin IN (' . implode(',', $cardArr) . ')';
                    } else {
                        $cardWhere .= ' AND c.cardbin NOT IN (' . implode(',', $cardArr) . ')';
                    }
                }else{
                    if ($status == 1) {
                        $cardWhere .= ' AND c.cardbin = 1';
                    }
                }
            }
            $sqlCount = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . 'banklist AS c LEFT JOIN ' . C('DB_PREFIX') . 'allinpay_discount_card AS dc ON dc.did=' . $id . ' AND c.cardbin=dc.cardbin WHERE ' . $cardWhere;
            $countList = M()->query($sqlCount);
            $count = isset($countList[0]['cnt']) ? intval($countList[0]['cnt']) : 0;
            import('@.ORG.system_page');
            $p = new Page($count, 15);
            $sql = "SELECT c.*, dc.id AS did FROM " . C('DB_PREFIX') . 'banklist AS c LEFT JOIN ' . C('DB_PREFIX') . 'allinpay_discount_card AS dc ON dc.did=' . $id . ' AND c.cardbin=dc.cardbin WHERE ' . $cardWhere . ' ORDER BY dc.id DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
            $cardList = M()->query($sql);
//            echo $sql;die;
            $this->assign('cardList', $cardList);
            $this->assign('pagebar', $p->show());
            $this->assign('did', $id);
            $this->assign('status', $status);
            $this->display();
        } else {
            $this->error_tips('此优惠不存在');
        }
    }

    public function change()
    {
        $did = isset($_POST['did']) ? intval($_POST['did']) : 0;
        $merIds = isset($_POST['merIds']) ? $_POST['merIds'] : '';
        $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
        if (empty($merIds)) {
            $this->error('请选择商家');
        }
        
        if ($discount = D('Allinpay_discount')->field(true)->where(array('id' => $did))->find()) {
            if ($discount['use_type'] == 0) {
                $this->error('此优惠无需指定商家');
            }
        }
        
        $discountMerchantDB = D('Allinpay_discount_merchant');
        if ($type == 0) {
            $discountMerchantDB->where(array('did' => $did, 'mer_id' => array('in', $merIds)))->delete();
            $this->success('下线成功');
        } else {
            foreach ($merIds as $mer_id) {
                if (!($discountMerchantDB->where(array('did' => $did, 'mer_id' => $mer_id))->find())) {
                    $discountMerchantDB->add(array('did' => $did, 'mer_id' => $mer_id));
                }
            }
            $this->success('上线成功');
        }
        
    }

    public function changeCard()
    {
        $did = isset($_POST['did']) ? intval($_POST['did']) : 0;
        $cardIds = isset($_POST['cardIds']) ? $_POST['cardIds'] : '';
        $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
        if (empty($cardIds)) {
            $this->error('请选择卡bin');
        }

        if ($discount = D('Allinpay_discount')->field(true)->where(array('id' => $did))->find()) {
            if ($discount['card_limit'] == 0) {
                $this->error('此优惠无需指定卡bin');
            }
        }

        $discountMerchantDB = D('Allinpay_discount_card');
        if ($type == 0) {
            $discountMerchantDB->where(array('did' => $did, 'cardbin' => array('in', $cardIds)))->delete();
            $this->success('下线成功');
        } else {
            foreach ($cardIds as $cardid) {
                if (!($discountMerchantDB->where(array('did' => $did, 'cardbin' => $cardid))->find())) {
                    $discountMerchantDB->add(array('did' => $did, 'cardbin' => $cardid));
                }
            }
            $this->success('上线成功');
        }

    }
    
    
    public function discount_use()
    {
        $id = $_GET['id'];
        $keyword = isset($_GET['keyword']) ? htmlspecialchars(trim($_GET['keyword'])) : '';
        $where = 'a.is_paid = 1 AND did='.$id;

        if(!empty($keyword)) {
            if ($_GET['searchtype'] == 'order_id') {
                $where .= ' AND a.order_id=' . $keyword;
            } else if ($_GET['searchtype'] == 'name') {
                $where .= " AND a.order_name LIKE '%" . $keyword . "%'";
            }
        }
        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $where .= " AND (a.pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }
        
        $sql = "SELECT sum(discount_money) as total  FROM " . C('DB_PREFIX') . "allinpay_order WHERE";
        $sql .= " is_paid=1 AND did =".$id;
        $res = D()->query($sql);
        $discount_total_money = $res[0]['total'] ? $res[0]['total'] : 0;
        $this->assign('total_discount_money',$discount_total_money);

        import('@.ORG.system_page');
        $sql = "SELECT count(1) as count_num FROM " . C('DB_PREFIX') . "allinpay_order AS a LEFT JOIN " . C('DB_PREFIX') . "user AS u ON a.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant as m ON a.mer_id = m.mer_id" ;
        $sql .= " WHERE " . $where;
        $count = D()->query($sql);
        $p = new Page($count[0]['count_num'], 20);
        
        $sql = "SELECT a.*,u.nickname,m.name FROM " . C('DB_PREFIX') . "allinpay_order AS a LEFT JOIN " . C('DB_PREFIX') . "user AS u ON a.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant as m ON a.mer_id = m.mer_id" ;
        $sql .= " WHERE " . $where;
        $sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
        $result = D()->query($sql);

        $b_names = D('Allinpay_discount')->get_alias_name();
        foreach($result as &$res){
            $res['business'] = $b_names[$res['order_type']];
        }
        $this->assign('list', $result);
        $this->assign('pagebar', $p->show());
        $this->display();
    }
    
    public function delete_discount(){
		$id = $_POST['id'];
		M('Allinpay_discount')->where(array('id'=>$id))->setField('status',4);
		$this->success('删除成功');
	}

    
    public function discount_export()
    {
        set_time_limit(0);
        $id = $_GET['id'];
        $keyword = isset($_GET['keyword']) ? htmlspecialchars(trim($_GET['keyword'])) : '';
        $where = 'a.is_paid = 1 AND did='.$id;

        if(!empty($keyword)) {
            if ($_GET['searchtype'] == 'order_id') {
                $where .= ' AND a.order_id=' . $keyword;
            } else if ($_GET['searchtype'] == 'name') {
                $where .= " AND a.order_name LIKE '%" . $keyword . "%'";
            }
        }
        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $where .= " AND (a.pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        $sql = "SELECT sum(discount_money) as total  FROM " . C('DB_PREFIX') . "allinpay_order WHERE";
        $sql .= " is_paid=1 AND did =".$id;
        $res = D()->query($sql);
        $discount_total_money = $res[0]['total'] ? $res[0]['total'] : 0;
        $this->assign('total_discount_money',$discount_total_money);

        import('@.ORG.system_page');
        $sql = "SELECT count(1) as count_num FROM " . C('DB_PREFIX') . "allinpay_order AS a LEFT JOIN " . C('DB_PREFIX') . "user AS u ON a.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant as m ON a.mer_id = m.mer_id" ;
        $sql .= " WHERE " . $where;
        $count = D()->query($sql);
        $p = new Page($count[0]['count_num'], 20);

        $sql = "SELECT a.*,u.nickname,m.name FROM " . C('DB_PREFIX') . "allinpay_order AS a LEFT JOIN " . C('DB_PREFIX') . "user AS u ON a.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant as m ON a.mer_id = m.mer_id" ;
        $sql .= " WHERE " . $where;
        $result = D()->query($sql);

        $b_names = D('Allinpay_discount')->get_alias_name();

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title =  date('YmdHis').'支付优惠详情';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle( '支付优惠详情');
        $objActSheet = $objExcel->getActiveSheet();
        $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        
        $objActSheet->setCellValue('A1', '订单流水号');
        $objActSheet->setCellValue('B1', '业务类型');
        $objActSheet->setCellValue('C1', '商家名称');
        $objActSheet->setCellValue('D1', '订单名');
        $objActSheet->setCellValue('E1', '支付时间');
        $objActSheet->setCellValue('F1', '用户名');
        $objActSheet->setCellValue('G1', '订单金额');
        $objActSheet->setCellValue('H1', '优惠类型');
        $objActSheet->setCellValue('I1', '优惠金额');
        $objActSheet->setCellValue('J1', '实付金额');
        $index = 1;
        foreach ($result as $order) {
            $index++;
//            <td>{pigcms{$vo.allinpay_order_id}</td>
//                            <td>{pigcms{$vo.order_id}</td>
//                            <td>{pigcms{$vo.business}</td>
//                            <td>{pigcms{$vo.name}</td>
//                            <td>{pigcms{$vo.order_name}</td>
//                            <td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
//                            <td>{pigcms{$vo.nickname}</td>
//                            <td>{pigcms{$vo['order_price']|floatval}</td>
//                            <td><if condition="$vo['discount_type'] eq 0">满减<elseif condition="$vo['discount_type'] eq 1" />折扣<else/>随机立减</if></td>
//                            <td>{pigcms{$vo['discount_money']|floatval}</td>
//                            <td>{pigcms{$vo['paid_money']|floatval}</td>
            
            $objActSheet->setCellValueExplicit('A' . $index, $order['order_id']);
            $order['business'] = $b_names[$order['order_type']];
            $objActSheet->setCellValueExplicit('B' . $index, $order['business']);
            $objActSheet->setCellValueExplicit('C' . $index, $order['name']);
            $objActSheet->setCellValueExplicit('D' . $index, $order['order_name']);
            $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $order['pay_time']));
            $objActSheet->setCellValueExplicit('F' . $index, ($order['nickname']));
            $objActSheet->setCellValueExplicit('G' . $index, strval(($order['order_price'])));
            if($order['discount_type'] ==0){
                $order['discount_type'] = '满减';
            }elseif($order['discount_type'] ==1){
                $order['discount_type'] = '折扣';
            }elseif($order['discount_type'] ==2){
                $order['discount_type'] = '随机立减';
            }
            $objActSheet->setCellValueExplicit('H' . $index, $order['discount_type']);
            $objActSheet->setCellValueExplicit('I' . $index, strval(($order['discount_money'])));
            $objActSheet->setCellValueExplicit('J' . $index, strval(($order['paid_money'])));
        }
        
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

}