<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/5/15.
 * Time: 14:54.
 */
class Merchant_integral_mall_sliderModel extends Model{
    /**
     * @desc获取列表数据
     * @param $mer_id
     * @param $type_key
     * @param $num
     * @return array
     */
    public function get_slider($mer_id,$type_key,$num=0){
        if(!$mer_id || !$type_key) return array();
        $where = array();
        $where['mer_id'] = $mer_id;
        $where['status'] = 1;
        $where['type_key'] = $type_key;

        $order = 'sort DESC,id DESC';
        $data = $this->where($where)->field(true)->order($order)->field('id,name,url,pic')->select();
        $data = $this->deal_data($data);
        return intval($num)?array_slice($data,0,$num):$data;
    }

    protected function deal_data($data){
        if($data){
            foreach($data as $key => $val){
                $data[$key]['img_url'] = file_domain().'/upload/slider/'.$val['pic'];
            }
        }
        return $data;
    }
}