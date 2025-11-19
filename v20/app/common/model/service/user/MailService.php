<?php


namespace app\common\model\service\user;


use app\common\model\db\Area;
use app\common\model\db\Mail;
use app\common\model\db\MerchantCategory;
use Model;

class MailService
{

    public function cat_sel(){
        $where=[['cat_fid','=',0]];
        $list=(new MerchantCategory())->getSome($where)->toArray();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $list[$key]['cat_id']="0-".$val['cat_id'];
            }
        }
        return $list;
    }
	public function addData($data){
		$result = (new Mail)->add($data);
		return $result;
	}

    /**
     * @param $data
     * @return mixed
     * 更新站内信
     */
    public function saveData($data){
	    $where=[['id','=',$data['id']]];
        $result = (new Mail)->updateThis($where,$data);
        return $result;
    }

    /**
     * @param $data
     * @return mixed
     * 编辑页面
     */
    public function editMail($data){
        $where=[['id','=',$data['id']]];
        $result = (new Mail)->getOne($where);
        if(!empty($result)){
            $result=$result->toArray();
            if(!empty($result)){
                $result['users_label']=unserialize($result['users_label']);
                if($result['set_send_time']>0){
                    $result['set_send_time_date']=date("Y-m-d",$result['set_send_time']);
                    $result['set_send_time_min']=date("H:i",$result['set_send_time']);
                    $result['img']=empty($result['img'])?"":replace_file_domain($result['img']);
                }
            }
        }
        return $result;
    }
    /**
     * @param $where
     * @param $page
     * @param $pageSize
     * @return mixed
     * 系统后台站内信列表
     */
	public function mailList($where,$page,$pageSize){
        $result=(new Mail())->getSome($where,true,'id desc',($page-1)*$pageSize,$pageSize);
        if(!empty($result)){
            foreach ($result as $key=>$val){
                if($val['category_type']==1){
                    $where=[['cat_id','=',$val['category_id']]];
                    $result[$key]['cat_name']=(new MerchantCategory())->getVal($where,'cat_name');
                }elseif ($val['category_type']==2){
                    $result[$key]['cat_name']="商城";
                }elseif ($val['category_type']==3){
                    $result[$key]['cat_name']="买单";
                }elseif ($val['category_type']==4){
                    $result[$key]['cat_name']="团购";
                }else{
                    $result[$key]['cat_name']="外卖";
                }
                $result[$key]['title1']=$val['title'];

                if($val['set_send_time']>0){
                    $result[$key]['set_send_time']=date("Y-m-d H:i",$val['set_send_time']);
                } else {
                    $result[$key]['set_send_time'] = '-';
                }
            }
        }
        return $result;
    }


    /**
     * @param $where
     * @return mixed
     * 查找站内信列表
     */
    public function getSome($where){
        $result=(new Mail())->getSome($where)->toArray();
        return $result;
    }
    /**
     *删除后台站内信
     */
    public function delData($where){
	    $result=(new Mail())->delData($where);
	    return $result;
    }

    /**
     * @return array
     * 省
     */
    public function ajax_province()
    {
        $database_area = (new Area());
        $condition_area = [['is_open', '=', 1], ['area_type', '=', 1]];
        $order = "area_sort DESC,area_id ASC";
        $data = $database_area->getSome($condition_area, true, $order)->toArray();
        $cat_id_arrs = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['area_id'];
                $cat_id_arr['label'] = $value['area_name'];
                $cat_id_arr['children'] = $this->ajax_city($value['area_id']);
                $cat_id_arrs[] = $cat_id_arr;
            }
        }

        return $cat_id_arrs;
    }

    public function ajax_city($area_id)
    {
        $database_area = (new Area());
        $condition_area = [['is_open', '=', 1], ['area_pid', '=', $area_id]];
        $order = "area_sort DESC,area_id ASC";
        $data = $database_area->getSome($condition_area, true, $order)->toArray();
        $city_list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['area_id'];
                $cat_id_arr['label'] = $value['area_name'];
                $city_list[] = $cat_id_arr;
            }
        }
        return $city_list;
    }
}