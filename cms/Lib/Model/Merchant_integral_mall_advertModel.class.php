<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/5/15.
 * Time: 14:54.
 */
class Merchant_integral_mall_advertModel extends Model{
    /**
     * @desc 获取列表数据；
     * @param $mer_id
     * @param $type
     * @param $num：数量
     * @return array
     */
    public function get_list($mer_id,$type,$num){
        if(!$mer_id || !$type) return array();
        $where = array();
        $where['mer_id'] = $mer_id;
        $where['status'] = 1;
        if($type){
            $where['type_key'] = $type;
        }
        $order = 'sort DESC,id DESC';
        $data = $this->where($where)->order($order)->field('id,name,url,pic')->select();
        $data = $this->deal_data($data);
        return intval($num)?array_slice($data,0,$num):$data;
    }

    /**
     * @desc 数据处理，主要是图片需要处理
     * @param $data
     * @return mixed
     */
    protected function deal_data($data){
        if($data){
            foreach($data as $key =>$val){
                $data[$key]['img_url'] = file_domain().'/upload/advert/'.$val['pic'];
            }
        }
        return $data;
    }

}
?>