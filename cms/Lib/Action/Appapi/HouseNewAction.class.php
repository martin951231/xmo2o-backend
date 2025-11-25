<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2020/5/6
 * Time: 15:03
 */
class HouseNewAction extends BaseAction
{
    public function get_province(){
        $database_area = D('Area');
        $condition_area['area_type'] = 1;
        $condition_area['is_open'] = 1;
        $province_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if(!empty($province_list)){
            $return['list'] = $province_list;
        }else{
            $return['list'] = array();
        }
        $res['return'] = $return['list'];
        $this->returnCode(0, $res);
    }
    public function get_city(){
        $database_area = D('Area');
        $condition_area['area_pid'] = intval(I('id'));
        $condition_area['is_open'] = 1;
        $city_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if(!empty($city_list)){

            $return['list'] = $city_list;
        }else{
            $return['list'] = array();
        }
        $res['return'] = $return['list'];
        $this->returnCode(0, $res);
    }

    public function get_user_info()
    {
        $village_id = I('village_id');
        if (empty($village_id)) {
            $this->returnCode('20090005');
        }
//        $this->_uid = 1358722;
        if (empty($this->_uid)) {
            $this->returnCode('20000009');
        }


        $pigcms_id = I('pigcms_id','');//户号
        if (empty($pigcms_id)) {
            $this->returnCode('20120004');
        }
        $arr = [];
        $user_bind = D('House_village_user_bind')->where(array('village_id'=>$village_id,'uid'=>$this->_uid,'pigcms_id'=>$pigcms_id))->find();
        if($user_bind['authentication_field']){
            $user_bind['authentication_field'] = unserialize($user_bind['authentication_field']);
        }
        $where_village['village_id'] = $village_id;
        $property_id = D('House_village')->where($where_village)->getField('property_id');
        $where_data_config = array(
            'village_id'=>0,
            'property_id'=>$property_id,
            'is_display'=>1,
            'is_close'=>0
        );
        $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
        if (!$dataList) {
            $where_data_config = array(
                'village_id'=>$village_id,
                'is_display'=>1,
                'is_close'=>0
            );
            $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
        }
        if (empty($dataList)) {
            D('House_village')->update_user_info(0, $property_id);
            $where_data_config = array(
                'village_id'=>0,
                'property_id'=>$property_id,
                'is_display'=>1,
                'is_close'=>0
            );
            $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
        }

        $d_area = M('Area');
        foreach($dataList as $k=>&$v){
            unset($v['system_value']);
            unset($v['sort']);
            unset($v['add_time']);
            if($v['type'] == 2){
                if($v['is_system'] == 1){
                    $v['use_field'] = D('House_village')->get_system_value($v['key']);
                }else{
                    $v['use_field'] = explode(',',$v['use_field']);
                }
            }elseif($v['type'] == 3){

                $v['use_field'] = [];
            }else{
                $v['use_field'] = [];
            }

            if($v['type'] == 3){
                // label
                if($user_bind['authentication_field'][$v['key']]['value']){
                    $v['value'] = $user_bind['authentication_field'][$v['key']]['value'];
                    $city = explode("#",$user_bind['authentication_field'][$v['key']]['value']);
                    $v['province_idss'] = $city[0];
                    $v['city_idss'] = $city[1];
                }else{
                    $v['value'] = '';
                }
                $v['label'] = '';
                if ($v['province_idss']) {
                    $v['label'] .= $d_area->where(['area_id' => $v['province_idss']])->getField('area_name');
                }
                if ($v['city_idss']) {
                    $v['label'] .= ' ' . $d_area->where(['area_id' => $v['city_idss']])->getField('area_name');
                }
            }else{
                $v['value'] = $user_bind['authentication_field'][$v['key']]['value']?$user_bind['authentication_field'][$v['key']]['value']:'';
            }

        }
        $res['dataList'] = $dataList;
        $set_id_code = M('House_village')->where(['village_id'=>$village_id])->getField('set_id_code');
        if($set_id_code == 1){
            $res['id_card']['type'] = true;
            $res['id_card']['value'] = !empty($user_bind['id_card']) && $user_bind['id_card']!='0' ? $user_bind['id_card']:'';
        }else{
            $res['id_card']['type'] = false;
            $res['id_card']['value'] = $user_bind['id_card'];
        }
        $this->returnCode(0, $res);
    }


    public function add_user_info()
    {
        $village_id = I('village_id');
        if (empty($village_id)) {
            $this->returnCode('20090005');
        }

//        $this->_uid = 1358722;
        if (empty($this->_uid)) {
            $this->returnCode('20000009');
        }
        $pigcms_id = I('pigcms_id','');//户号
        if (empty($pigcms_id)) {
            $this->returnCode('20120004');
        }
        $data_post = $_POST['dataList'];
        $id_card = $_POST['id_card'];
        $house_village_info = D('House_village')->get_village_info($village_id,'set_id_code');
        if($house_village_info['set_id_code'] == 1){//强制填写
            if(empty($id_card)){
                $this->returnCodeError('身份证号码不能为空');
            }
            if(is_idcard($id_card) == false){
                $this->returnCodeError('请填写正确的身份证号码');
            }
        }
        $arr_data = [];
        if($data_post) {
            foreach ($data_post as $k => $v) {
                if ($v['is_must'] == 1 && empty($v['value'])) {
                    $this->returnCodeError($v['title'] . '为必填项！');
                }
            }
            $arr_data = [];
            foreach ($data_post as $kk => $vv) {
                if ($vv['value']) {
                    $arr1[$vv['key']] = array(
                        'type' => $vv['type'],
                        'is_must' => $vv['is_must'],
                        'key' => $vv['key'],
                        'title' => $vv['title'],
                        'value' => $vv['value'],

                    );
                } else {
                    $arr1[$vv['key']] = array(
                        'type' => $vv['type'],
                        'is_must' => $vv['is_must'],
                        'key' => $vv['key'],
                        'title' => $vv['title'],
                        'value' => '',

                    );
                }

                $arr_data = $arr1;
            }
        }
        $res = D('House_village_user_bind')->where(array('village_id'=>$village_id,'uid'=>$this->_uid,'pigcms_id'=>$pigcms_id))->data(array('authentication_field'=>serialize($arr_data)))->save();
        D('House_village_user_bind')->add_user_info($village_id,$pigcms_id,$this->_uid,$data_post,1);
        if(!empty($id_card)){
            M('House_village_user_bind')->where(['village_id'=>$village_id,'uid'=>$this->_uid,'pigcms_id'=>$pigcms_id])->save(['id_card'=>$id_card]);
        }
        // 用户打标签生成计划任务
        D('Property_group')->add_mark_user_plan($pigcms_id,$village_id,$house_village_info['property_id']);

        //todo 用户同步写入属性表记录
        invoke_v20_service('\app\community\model\service\TaskReleaseService/userAttributeRecord', ['pigcms_id'=>$pigcms_id,'field'=>$arr_data]);
        $this->returnCodeOk(L_('修改成功!'));
    }

    /**
     * Notes: 记录广告点击
     * @author: wanzy
     * @date_time: 2021/1/16 15:46
     */
    public function click_count_advert() {
        $adver_id = I('id','', 'intval');
        $village_id = I('village_id','', 'intval');
        if (!$adver_id) {
            $this->returnCodeError(L_('缺少广告id!'));
        }
        $where_advert = [
            'id' => $adver_id
        ];
        $house_adver = M('House_adver')->where($where_advert)->find();
        if (!$house_adver) {
            $this->returnCodeError(L_('对应广告不存在!'));
        }
        $param = [
        //    'village_id' => $house_adver['village_id'] ? $house_adver['village_id'] : 0,
            'village_id' => I('village_id','', 'intval') ? I('village_id','', 'intval') : 0,
            'lm_day_look_max' => $house_adver['lm_day_look_max'] ? $house_adver['lm_day_look_max'] : 0,
            'lm_day_click_max' => $house_adver['lm_day_click_max'] ? $house_adver['lm_day_click_max'] : 0
        ];
        $info = D('House_adver')->add_house_adver_record($house_adver['id'],2,$param);
        $where_advert_village = [
            'adver_id' => $adver_id,
            'village_id' => $village_id,
        ];
        $house_adver_village = M('House_adver_village_count')->where($where_advert_village)->find();
        if (!empty($house_adver_village)){
            $data_adver=[];
            $data_adver['click_num']=$house_adver_village['click_num']+1;
            $data_adver['last_time']=time();
            M('House_adver_village_count')->where($where_advert_village)->data($data_adver)->save();
        }else{
            $data_count = [];
            $data_count['village_id'] = $village_id;
            $data_count['click_num']=1;
            $data_count['adver_id'] = $adver_id;
            $data_count['last_time'] = time();
            $data_count['add_time'] = time();
            M('House_adver_village_count')->data($data_count)->add();
        }

        $this->returnCode(0, ['id' => $adver_id]);
    }

    /**
     * Notes: 获取联系物业相关信息
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/1/26 14:26
     */
    public function property_contact() {
        $property_id = I('property_id', '0', 'intval');
        $village_id = I('village_id', '0', 'intval');
        $pigcms_id = I('pigcms_id', '0', 'intval');
        $wxapp_own_village_id = I('wxapp_own_village_id',0);
        if($wxapp_own_village_id){
            $property_id = $wxapp_own_village_id;
        }
        $info = D('House_village')->property_contact($property_id,$village_id,$pigcms_id);
        $this->returnCode(0, $info);
    }

    public function getProvinceCity() {
        $database_area = D('Area');
        $condition_area['area_type'] = 1;
        $condition_area['is_open'] = 1;
        $province_list = $database_area->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->getField('`area_id`, `area_id` `id`, `area_id` `value`,`area_name` `label`,`area_name` `name`',true);
        if (!empty($province_list)) {
            foreach ($province_list as &$item) {
                $item['children'] = [];
            }
            $database_area = D('Area');
            $condition_area['area_type'] = 2;
            $condition_area['area_pid'] = ['gt',0];
            $condition_area['is_open'] = 1;
            $city_list = $database_area->field('`area_id` `id`,`area_pid`, `area_id` `value`,`area_name` `label`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
            if (!empty($city_list)) {
                foreach ($city_list as &$items) {
                    if (isset($province_list[$items['area_pid']]) && $province_list[$items['area_pid']]) {
                        $province_list[$items['area_pid']]['children'][] = $items;
                    }
                }
            }
        } else {
            $province_list = [];
        }
        $res['list'] = (empty($province_list)) ? $province_list : array_values($province_list);
        $this->returnCode(0, $res);
    }
}