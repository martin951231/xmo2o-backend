<?php
class House_village_print_templateModel extends Model{
	/*得到打印模板列表*/
	public function get_limit_list_page($column,$pageSize=20,$isSystem=false){
		if(!$column['village_id']){
			return null;
		}
    	
    	$order = '`template_id` DESC';
    	if($isSystem){
    		import('@.ORG.system_page');
    	}else{
    		import('@.ORG.merchant_page');
    	}
    	$count_order = $this->where($column)->count();
    	$p = new Page($count_order,$pageSize,'page');
    	$list = $this->where($column)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
    	
    	$return['pagebar'] = $p->show();
    	$return['list'] = $list;
    	return $return;
	}

	public function get_one($template_id){
		if(!$template_id){
			return false;
		}
        $print_template = $this->where(array('template_id'=>$template_id))->find();
        $condition_table  = array(C('DB_PREFIX').'house_village_print_custom'=>'c',C('DB_PREFIX').'house_village_print_custom_configure'=>'b');
        $condition_where = " `c`.`configure_id` = `b`.`configure_id`  AND `c`.`template_id` =".$template_id;
        
        $condition_field = 'c.*,b.*';
        $order = ' `b`.`weight` DESC,`c`.`id` ASC';
        $custom_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->select();
        $print_template['custom'] = $custom_list ? $custom_list : [];

		return $print_template;
	}

    public function get_select($where){
        $print_template = D('House_village_print_template')->where($where)->select();
        if($print_template){
            return $print_template;
        }else{
            return false;
        }
    }

    /**
     * 获取打印模板参数 兼容自定义字段 新
     * User: zhanghan
     * Date: 2022/1/7
     * Time: 20:27
     * @param $template_id
     * @return array|false|mixed|null
     */
    public function get_one_new($template_id){
        if(!$template_id){
            return false;
        }
        $print_template = $this->where(array('template_id'=>$template_id))->find();
        if(empty($print_template['custom_field'])){
            $print_template = $this->get_one($template_id);
        }else{
            $custom_list = [];
            // 模板字段处理
            $custom_field = json_decode($print_template['custom_field'],true);
            foreach ($custom_field as $value){
                if(is_numeric($value['id'])){
                    $custom_list[] = D('house_village_print_custom_configure')
                        ->where(array('configure_id' => $value['id']))
                        ->field('configure_id,configure_id as id,title,type,field_name')
                        ->find();
                }else{
                    $custom_list[] = [
                        'id' => -1,
                        'configure_id' => $value['id'],
                        'title' => $value['title'],
                        'field_name' => '',
                        'type' => $value['print_type']
                    ];
                }
            }
            $print_template['custom'] = $custom_list ? $custom_list : [];
        }
        return $print_template;
    }
	
}