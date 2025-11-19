<?php

/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/24 16:22
 * Class House_village_third_user_infoModel
 */
class   House_village_third_logModel extends Model{



    // 处理数据
    /**
     * Notes: 初次同步处理数据
     * @param $village_id
     * @return mixed
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/10/10 13:20
     */
    public function handle_third_user_info($where = [], $time=0) {
        $msg = $this->where($where)->find();
        return $msg;
    }


    /**
     * Notes: 查询下同步的数据
     * @param array $where
     * @param int $page_size
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/29 14:36
     */
    public function handle_third_user_list($where = [], $page_size=15) {
        $count_user = $this->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, $page_size);
        $list = $this->where($where)->order('id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($list){
            foreach ($list as &$v){
                if ($v['status']==0){
                    $v['status']='同步中';
                }elseif($v['status']==1){
                    $v['status']='同步成功';
                }else{
                    $v['status']='同步失败';
                }
                if ($v['business']=='village'){
                    $v['business']='小区数据';
                }elseif($v['business']=='house'){
                    $v['business']='房屋数据';
                }else{
                    $v['business']='住户数据';
                }
            }
        }
        $arr = [];
        $arr['list'] = $list;
        $arr['pagebar'] = $p->show();
        return $arr;
    }



}
