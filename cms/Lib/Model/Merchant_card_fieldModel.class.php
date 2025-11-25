<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2020/1/7.
 * Time: 10:46.
 */

/**
 * Class Merchant_card_fieldModel定制：商家会员卡自定义字段类
 */
class Merchant_card_fieldModel extends Model{

    public $pre = 'diy_';//字段统一加前缀，防止和实际字段重复

    //获取所有自定义字段
    public function get_list(){
        $where = ['is_del'=>0];
        return $this->where($where)->select();
    }

    //商户后台会员卡详情页HTML页面
    public function get_detail_list($info){
        $list = $this->get_list();
        $html = '';
        if($list){
            foreach ($list as $key => $value) {
                switch ($value['type']) {//1单行文本，2单选框，3复选框，4下拉框，5多文本框
                    case '1':
                        $value_html = $this->deal_single_text($value,$info);
                        break;
                    case '2':
                        $value_html = $this->deal_single_select($value,$info);
                        break;
                    case '3':
                        $value_html = $this->deal_check_select($value,$info);
                        break;
                    case '4':
                        $value_html = $this->deal_single_select($value,$info);
                        break;
                    case '5':
                        $value_html = $this->deal_text($value,$info);
                        break;
                    default:
                        $value_html = '';
                        break;
                }
                $html .= $value_html;
            }
        }
        return $html;
    }

    //单行文本样式
    private function deal_single_text($value,$info){
        $pre_key = $this->pre.$value['url'];
        $single_text_html  = '';
        $single_text_html .= '<tr><th width="15%">'.$value['name'].'</th>';
        $single_text_html .= '<td width="85%" colspan="3"><input type="text" class="input"  name="'.$pre_key.'" value="'.$info[$pre_key].'"></td></tr>';
        return $single_text_html;
    }

    //复选框样式
    private function deal_check_select($value,$info){
        /**<input type="checkbox" name="vehicle" value="Car" checked="checked" /> I have a car**/
        $pre_key = $this->pre.$value['url'];
        $single_select_html  = '';
        $single_select_html .= '<tr><th width="15%">'.$value['name'].'</th>';
        $single_select_html .= '<td width="85%" colspan="3">';
        $option = explode(',', $value['opt']);
        foreach ($option as $op) {
            $single_select_html .= '<input type="checkbox" name="'.$pre_key.'[]" ';
            if(in_array($op, $info[$pre_key])){
                $single_select_html .= 'checked="checked"';
            }
            $single_select_html .= 'value="'.$op.'" />'.$op.'&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        $single_select_html .= '</td></tr>';
        return $single_select_html;  
    }

    //单选框
    private function deal_single_select($value,$info){
        $pre_key = $this->pre.$value['url'];
        $single_select_html  = '';
        $single_select_html .= '<tr><th width="15%">'.$value['name'].'</th>';
        $single_select_html .= '<td width="85%" colspan="3">';
        $single_select_html .= '<select name="'.$pre_key.'">';
        $single_select_html .= '<option value="">==请选择==</option>';
        $option = explode(',', $value['opt']);
        foreach ($option as $op) {
            $single_select_html .= '<option value="'.$op.'" ';
            if($op==$info[$pre_key]){
                $single_select_html .= 'selected="selected"';
            }
            $single_select_html .= '>'.$op.'</option>';
        }
        $single_select_html .= '</select></td></tr>';
        return $single_select_html;
    }

    //多文本框
    private function deal_text($value,$info){
        $pre_key = $this->pre.$value['url'];
        $text_html  = '';
        $text_html .= '<tr><th width="20%">'.$value['name'].'</th>';
        $text_html .= '<td width="80%" colspan="3"><textarea name="'.$pre_key.'" rows="4" cols="30">'.$info[$pre_key].'</textarea></td></tr>';
        return $text_html;
    }

    public function get_one($id){
        if(!$id) return false;
        $where = ['id'=>$id];
        return $this->where($where)->find();
    }

    //删除
    public function del_row($id){
        if(!$id) return false;
        $where = ['id'=>$id];
        $data  = ['is_del'=>1];
        return $this->where($where)->data($data)->save();
    }

    //新增
    public function add_row($row){
        $row['add_time'] = time();
        return $this->add($row);
    }

    //修改
    public function save_row($data,$id){
        if(!$id) return false;
        $where = ['id'=>$id];
        return $this->where($where)->data($data)->save();
    }

    /**
     * @desc 判断作为筛选项是否超过规定数量
     * @param $id：当前编辑的字段id
     * @param $num：规定数量
     * @return bool
     */
    public function check_filter($id,$num=4){
        if(!$num) return false;
        $where = [
            'isfilter' =>1,
            'is_del'=>0
        ];
        if($id){
            $where['id'] = array('neq',$id);
        }
        $count = $this->where($where)->count();
        if($count<$num){
            return true;
        }else{
            return false;
        }
    }

    //获取筛选框【商户后台】
    public function get_select_field(){
        $where = array();
        $where['is_del'] = 0;
        $where['isfilter'] = 1;
        $where['type'] = array('in',array(2,3,4));//只支持单选，复选和下拉框（不支持文本框筛选）
        $data = $this->where($where)->select();
        return $this->deal_select_html($data);
    }

    public function get_select_url(){
        $where = array();
        $where['is_del'] = 0;
        $where['type'] = array('in',array(2,3,4));//只支持单选，复选和下拉框（不支持文本框筛选）
        $data = $this->where($where)->select();
        $return = array();
        foreach ($data as $key => $value) {
            $return[] = $this->pre.$value['url'];
        }
        return $return;
    }


    private function deal_select_html($data){
        if(!$data) return '';
        $html = '';
        $pre = $this->pre;//加个前缀，防止和页面中非自定义筛选字段重复
        foreach ($data as $key => $value) {
            $option = explode(',',$value['opt']);
            $html .= '<select name="'.$pre.$value['url'].'">';
            $html .= '<option value="">全部'.$value['name'].'</option>';
            $get_key = $pre.$value['url'];
            foreach ($option as $k => $v) {
                $html .= '<option value="'.$v.'" ';
                if($_GET[$get_key]==$v){
                    $html .= 'selected="selected"';
                }
                $html .= '>'.$v.'</option>';
            }
            $html .= '</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        return $html;
    }

    public function get_field_list(){
        $where = array();
        $where['is_del'] = 0;
        $where['type'] = array('in',array(2,3,4));//只支持单选，复选和下拉框（不支持文本框筛选）
        $data = $this->where($where)->select();
        $return = array();
        if($data){
            foreach($data as $key => $val){
                $option = explode(',', $val['opt']);
                foreach ($option as $v) {
                    $temp = array();
                    if($v){
                        $v = trim($v);
                        $temp['diy_name'] = $val['name'];
                        $temp['diy_value'] = $this->pre.$val['url'].':'.$v;
                        $temp['show_value'] = $v;
                        $return[] = $temp;
                    }
                }
            }

        }
        return $return;
    }
}


?>