<?php
class House_service_categoryModel extends Model{
    protected $_validate = array(
        array('cat_name','require','分类名称不能为空！'),
        array('cat_desc','require','分类描述不能为空！'),
        array('cat_img','require','分类图标不能为空！'),
    );

    protected $_auto = array(
        array('add_time','time',1,'function'),
        array('village_id','get_village_id',1,'callback'),
        array('cat_url' , 'get_htmlspecialchars_decode' , 1 , 'callback'),
    );

    protected function get_village_id(){
        return $_SESSION['house']['village_id'];
    }

    protected function get_htmlspecialchars_decode(){
        if($_POST['cat_url']){
            return htmlspecialchars_decode($_POST['cat_url']);
        }else{
            return '';
        }
    }
    public function house_service_category_add(){
        if(!$this->create()){
            return array('status'=>0,'msg'=>$this->getError());
        }else{
            $data = $this->data;
            if($data['parent_id']){
                if(!$data['cat_img']){
                    return array('status'=>0,'msg'=>L_('分类图标不能为空'));
                }
            }
            if($this->add()){
                return array('status'=>1,'msg'=>L_('添加成功！'));
            }else{
                return array('status'=>0,'msg'=>L_('添加失败！'));
            }
        }
    }

    public function house_service_category_list($where , $field = true , $order = 'id desc',$pageSize = 20){
        if(!$where){
            return false;
        }

        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $house_service_category_list = $this->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $database_house_service_info = D('House_service_info');
        foreach($house_service_category_list as $k=>$v){
            $house_service_category_list[$k]['cat_url'] = wapLbsTranform($v['cat_url'],array('title'=>$v['cat_name']));
        }

        $list['list'] = $house_service_category_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }


    public function house_service_category_del($where){
        if(!$where){
            return false;
        }
        $Map['parent_id'] = $where['id'];
        $s_cate_arr = $this->where($Map)->getField('id',true);
        if($s_cate_arr){
            foreach($s_cate_arr as $v){
                $_Map['id'] = $v;
                $s_data['status'] = 4;
                $this->where($_Map)->data($s_data)->save();
            }
        }
        $data['status'] = 4;
        $insert_id = $this->where($where)->data($data)->save();
        if($insert_id){
            return array('status'=>1,'msg'=>L_('删除成功！'));
        }else{
            return array('status'=>0,'msg'=>L_("删除失败！"));
        }
    }

    public function house_service_category_detail($where,$field=true){
        if(!$where){
            return false;
        }

        $detail = $this->where($where)->field($field)->find();

        if($detail){
            return array('status'=>1,'detail'=>$detail);
        }else{
            return array('status'=>0,'detail'=>$detail);
        }
    }

    public function house_service_category_edit($where){
        if(!$this->create()){
            return array('status'=>0,'msg'=>$this->getError());
        }else{
            $data = $this->data;
            if($data['parent_id']){
                if(!$data['cat_img']){
                    return array('status'=>0,'msg'=>L_('分类图标不能为空'));
                }
            }


            $status = $data['status'];
            if($this->where($where)->save()){
               $Map['parent_id'] = $where['id'];
               $this->where($Map)->setField('status',$status);

                return array('status'=>1,'msg'=>L_('修改成功！'));
            }else{
                return array('status'=>0,'msg'=>L_('修改失败！'));
            }
        }
    }
	public function getAllCatList($village_id, $where = array()){
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $cat_list = $this->where($where)->order('`sort` DESC,`id` asc')->getField('`id`,`cat_name`,`cat_url`,`cat_img`,`parent_id`,`is_test`');
        foreach($cat_list as $key=>$value){
			if(empty($value['parent_id'])){
				$cat_relation[$value['id']] = $key;
			}
            if(empty($cat_list[$value['parent_id']]) && $value['parent_id']!=0){
                unset($cat_list[$key]);

            }

        }
		foreach($cat_list as $key=>$value){
            if(!empty($value['parent_id'])){
                if(strpos($value['cat_img'],'tpl/Wap/')!=false){
                    $value['cat_img'] = file_domain().'/upload/service/'.$value['cat_img'];
                }

                if(defined('IS_INDEP_HOUSE')){
                    $value['cat_url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $value['cat_url']);
                }
				$cat_list[$cat_relation[$value['parent_id']]]['son_list'][] = $value;
				unset($cat_list[$key]);


                if(defined('IS_INDEP_HOUSE')){
                        $cat_list[$key]['cat_url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $value['cat_url']);
                }
			}
		}
		foreach($cat_list as $key=>$value){
			if(empty($value['parent_id']) && empty($value['son_list'])){
				unset($cat_list[$key]);
			}
		}
		return $cat_list;
	}

    public function getAllCatListApi($village_id){
        $cat_list = $this->where(array('village_id'=>$village_id,'status'=>1))->order('`sort` DESC,`id` asc')->getField('`id`,`cat_name`,`cat_url`,`cat_img`,`parent_id`,`is_test`');

        foreach($cat_list as $key=>$value){
            if(empty($value['parent_id'])){
                $cat_relation[$value['id']] = $key;
            }

            if(empty($cat_list[$value['parent_id']]) && $value['parent_id']!=0){
                unset($cat_list[$key]);

            }

        }
        foreach($cat_list as $key=>$value){
            if(!empty($value['parent_id'])){
                if(strpos($value['cat_img'],'tpl/Wap/')==0){

                    $value['cat_img'] = file_domain().'/upload/service/'.$value['cat_img'];
                }else{

                    $value['cat_img'] = file_domain().$value['cat_img'];
                }

                if(defined('IS_INDEP_HOUSE')){
                    $value['cat_url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $value['cat_url']);
                }
                $cat_list[$cat_relation[$value['parent_id']]]['son_list'][] = $value;
                unset($cat_list[$key]);


                if(defined('IS_INDEP_HOUSE')){
                    $cat_list[$key]['cat_url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $value['cat_url']);
                }
            }
        }

        foreach($cat_list as $key=>$value){

            if(empty($value['parent_id']) && empty($value['son_list'])){
                unset($cat_list[$key]);
            }
        }

        return $cat_list;
    }

    public function getAllCatListApiNew($village_id){
        $cat_list = $this->where(array('village_id'=>$village_id,'status'=>1))->order('`sort` DESC,`id` asc')->getField('`id`,`cat_name`,`cat_url`,`cat_img`,`parent_id`,`is_test`');

        foreach($cat_list as $key=>$value){
            if(empty($value['parent_id'])){
                $cat_relation[$value['id']] = $key;
            }

            if(empty($cat_list[$value['parent_id']]) && $value['parent_id']!=0){
                unset($cat_list[$key]);

            }

        }
        // 2020/8/25 start
        $cat_list = D('PrivilegePackage')->getApplication($cat_list,$village_id);
        //2020/8/25 end
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'convenientList',
            'isAll' => true,
            'urlParam' => 'village_id='.$village_id,
        ];
        $convenientListUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        
        foreach($cat_list as $key=>$value){
            if(!empty($value['parent_id'])){
                if(strpos($value['cat_img'],'/static') !== false){
                    $value['cat_img'] = C('config.site_url') . $value['cat_img'];
                }elseif(strpos($value['cat_img'],'/upload/') !== false){
                    $value['cat_img'] = replace_file_domain($value['cat_img']);
                }elseif(strpos($value['cat_img'],'/tpl/Wap/') !== false){
                    $value['cat_img'] = C('config.site_url') . $value['cat_img'];
                }else{
                    $value['cat_img'] = file_domain().'/upload/service/'.$value['cat_img'];
                }

                if(defined('IS_INDEP_HOUSE')){
                    $value['cat_url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $value['cat_url']);
                }
                if(empty($value['cat_url'])){
                    $value['cat_url'] = $convenientListUrl .'&id='.$value['id'];
                }
                if ($value['cat_url'] && strpos($value['cat_url'],'http') === false && strpos($value['cat_url'],'https') === false) {
                    $value['cat_url'] = C('config.site_url').$value['cat_url'];
                }
                if (isset($value['cat_url']) && $value['cat_url']) {
                    $paramCatUrl = [
                        'pagePath' =>  $value['cat_url'],
                    ];
                    $value['cat_url'] = $db_house_village->villagePagePath($app_version,$deviceId,$paramCatUrl);
                }
                
                $cat_list[$cat_relation[$value['parent_id']]]['son_list'][] = $value;
                unset($cat_list[$key]);
            }
        }

        foreach($cat_list as $key=>$value){

            if(empty($value['parent_id']) && empty($value['son_list'])){
                unset($cat_list[$key]);
            }
        }
		
		$cat_list = array_values($cat_list);

        return $cat_list;
    }

	public function getHotCatList($village_id,$limit=6){
		$cat_list = $this->field('`id`,`cat_name`,`cat_url`,`cat_img`,`parent_id`')->where(array('village_id'=>$village_id,'status'=>'1','parent_id'=>array('neq','0')))->order('`sort` DESC')->limit($limit)->select();
		foreach($cat_list as &$hotValue){
			$hotValue['cat_img'] = file_domain().'/upload/service/'.$hotValue['cat_img'];
			if($hotValue['cat_url']){
				$hotValue['cat_url'] = wapLbsTranform($hotValue['cat_url'],array('title'=>$hotValue['cat_name'],'pic'=>$hotValue['cat_img']));
			}else{
				$hotValue['cat_url'] = U('Houseservice/cat_list',array('village_id'=>$village_id,'id'=>$hotValue['id']));
			}

                        if(defined('IS_INDEP_HOUSE')){
                                $hotValue['cat_url'] = str_replace('wap.php', 'wap_house.php', $hotValue['cat_url']);
                        }
		}

		return $cat_list;
	}
	public function getIndexCatList($village_id,$limit=6){
		$cat_list = $this->field('`id`,`cat_name`,`cat_url`,`cat_img`,`parent_id`')->where(array('village_id'=>$village_id,'status'=>'1','is_index_show'=>'1'))->order('`sort` DESC')->limit($limit)->select();
		foreach($cat_list as &$indexValue){
			$indexValue['cat_img'] = file_domain().'/upload/service/'.$indexValue['cat_img'];
			if($indexValue['cat_url']){
				$indexValue['cat_url'] = wapLbsTranform($indexValue['cat_url'],array('title'=>$indexValue['cat_name'],'pic'=>$indexValue['cat_img']));
			}else{
				$indexValue['cat_url'] = C('config.site_url').U('Houseservice/cat_list',array('village_id'=>$village_id,'id'=>$indexValue['id']));
			}

                        if(defined('IS_INDEP_HOUSE')){
                             $indexValue['cat_url'] = str_replace('wap.php', C('INDEP_HOUSE_URL'), $indexValue['cat_url']);
                        }
		}
		return $cat_list;
	}
}