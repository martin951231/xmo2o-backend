<?php
class Employee_claim_storeModel extends Model
{
    /**
     * @param int $cat_id   分类id
     * @param int $cat_fid  分类父id
     * @param int $area_id   区域id
     * @param string $order   排序
     * @param int $lat 经纬度
     * @param int $long 经纬度
     * @param int $circle_id 商圈
     * @param string $search_txt 搜索字段值
     * @return mixed
     */
    public function employee_claim_store_list($cat_id,$cat_fid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$search_txt='')
    {
        $condition_where = "`ecs`.`city_id`='".C('config.now_city')."' AND `ecs`.`claim_status`='1' AND `ecs`.`is_claim`='1' ";
        //分类相关
        if(!empty($cat_fid)){
            $condition_where .= " AND `ecs`.`cat_fid`='$cat_fid'";
        }else if(!empty($cat_id)){
            $condition_where .= " AND `ecs`.`cat_id`='$cat_id'";
        }
        $condition_field  = '`ecs`.`name` AS `store_name`,`ecs`.*';
        $condition_table  = array(C('DB_PREFIX').'merchant_store'=>'ecs');
        //区域
        if($area_id || $circle_id){
            if ($circle_id) {
                $condition_where .= " AND `ecs`.`circle_id`='$circle_id'";
            } else {
                $condition_where .= " AND `ecs`.`area_id`='$area_id'";
            }
            if ($order == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`ecs`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`ecs`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`ecs`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }
        }else{
            if ($order == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`ecs`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`ecs`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`ecs`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }

        }
        if($search_txt){
            $condition_where.=" AND `ecs`.`name` like '%$search_txt%' ";
        }
        //排序
        switch($order){
            case 'solds':		//	人气最高
                $order = '`ecs`.`hits` DESC,`ecs`.`store_id` DESC';
                break;
            case 'start':		//	最新发布
                $order = '`ecs`.`last_time` DESC,`ecs`.`store_id` DESC';
                break;
            case 'juli':	//	距离
                $order = '`juli` asc,`ecs`.`store_id` DESC';
                break;
            default:		//	默认
                $order = '`ecs`.`sort` DESC,`ecs`.`store_id` DESC';
        }
        import('@.ORG.wap_group_page');

        $count_group = M('')->table($condition_table)->where($condition_where)->count();
        $p = new Page($count_group, 10,'page');
        $store_list = M('')->field($condition_field)->table($condition_table)->where($condition_where)->group('ecs.`store_id`')->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $return['totalPage'] = ceil($count_group/10);
        if ($store_list) {
            foreach ($store_list as $k => $v) {
                if ($v['mer_id']<=0 && $v['is_claim']==1) {
                    $is_claim_store = 1;
                } else {
                    $is_claim_store = 0;
                }
                $store_list[$k]['url'] = U('Employee/shop', array(
                    'store_id' => $v['store_id'],
                    'is_claim_store' => $is_claim_store
                ));
            }
        }
        $return['store_list'] = $store_list;
        $return['store_count'] = count($store_list);
        return $return;
    }

    /**
     * 获得店铺详情-可点击点击数
     * @param $store_id 店铺id
     * @param string $other 操作类型
     * @return mixed
     */
    public function get_store_by_storeId($store_id, $other=''){
        $now_store = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ecs',C('DB_PREFIX').'area'=>'a'))->where("`ecs`.`store_id`='$store_id' AND `ecs`.`area_id`=`a`.`area_id`")->find();

        if($other){
            $condition_store['store_id'] = $store_id;
            switch($other){
                case 'hits-setInc':// 增加点击数
                    M('Merchant_store')->where($condition_store)->setInc('hits');
                    break;
            }
        }
        return $now_store;
    }

    /**
     * 处理距离显示
     * @param $meter
     * @return string
     */
    public function wapRange($meter){
        if($meter < 100){
            return '<100m';
        }else if($meter <1000){
            return $meter.'m';
        }else{
            return round($meter/1000,1).'km';
        }
    }

    /**
     * 根据商品数据表的图片字段的一段来得到图片
     * @param $path
     * @param string $image_type
     * @return bool|string
     */
    public function get_image_by_path($path,$image_type='-1'){
        if(!empty($path)){
            if($image_type == '-1'){
                $return['image'] = file_domain().$path;
                $return['m_image'] = file_domain().$path;
                $return['s_image'] = file_domain().$path;
            }else{
                $return = file_domain().$path;
            }
            return $return;
        }else{
            return false;
        }
    }



    /**
     * 获取用户添加的表格信息(主要针对 状态1 开启（审核通过） 2 审核中)
     * @param int $uid   用户id
     * @param int $cat_id   分类id
     * @param int $cat_fid  分类父id
     * @param int $area_id   区域id
     * @param string $order   排序
     * @param int $lat 经纬度
     * @param int $long 经纬度
     * @param int $circle_id 商圈
     * @param string $search_txt 搜索字段值
     * @return mixed
     */
    public function employee_add_store_list($uid, $cat_id,$cat_fid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$search_txt='')
    {
        $condition_where = "`ecs`.`city_id`='".C('config.now_city')."' AND (`ecs`.`status`='1' OR `ecs`.`status`='2') AND `ecs`.`add_uid`=".$uid;

        //分类相关
        if(!empty($cat_fid)){
            $condition_where .= " AND `ecs`.`cat_fid`='$cat_fid'";
        }else if(!empty($cat_id)){
            $condition_where .= " AND `ecs`.`cat_id`='$cat_id'";
        }
        $condition_field  = '`ecs`.`name` AS `store_name`,`ecs`.*';
        $condition_table  = array(C('DB_PREFIX').'employee_claim_store'=>'ecs');
        //区域
        if($area_id || $circle_id){
            if ($circle_id) {
                $condition_where .= " AND `ecs`.`circle_id`='$circle_id'";
            } else {
                $condition_where .= " AND `ecs`.`area_id`='$area_id'";
            }
            if ($order == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`ecs`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`ecs`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`ecs`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }
        }else{
            if ($order == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`ecs`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`ecs`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`ecs`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }

        }
        if($search_txt){
            $condition_where.=" AND `ecs`.`name` like '%$search_txt%' ";
        }
        //排序
        switch($order){
            case 'solds':		//	人气最高
                $order = '`ecs`.`hits` DESC,`ecs`.`store_id` DESC';
                break;
            case 'start':		//	最新发布
                $order = '`ecs`.`last_time` DESC,`ecs`.`store_id` DESC';
                break;
            case 'juli':	//	距离
                $order = '`juli` asc,`ecs`.`store_id` DESC';
                break;
            default:		//	默认
                $order = '`ecs`.`sort` DESC,`ecs`.`store_id` DESC';
        }
        import('@.ORG.wap_group_page');

        $count_group = M('')->table($condition_table)->where($condition_where)->count();
        fdump(M(''), '$condition_where', true);
        $p = new Page($count_group, 10,'page');
        $store_list = M('')->field($condition_field)->table($condition_table)->where($condition_where)->group('ecs.`store_id`')->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $return['totalPage'] = ceil($count_group/10);
        if ($store_list) {
            foreach ($store_list as $k => $v) {
                $store_list[$k]['url'] = U('Employee/claim_store', array(
                    'store_id' => $v['store_id']
                ));
            }
        }
        $return['store_list'] = $store_list;
        $return['store_count'] = count($store_list);
        return $return;
    }


    /**
     * 获取
     * @param $get_grouplist_catid
     * @param $get_grouplist_catfid
     * @param $area_id
     * @param $order
     * @param int $lat
     * @param int $long
     * @param int $circle_id
     * @param string $search_txt
     * @return mixed
     */

    /*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
    public function wap_get_storeList_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$search_txt=''){
        $condition_where = "`m`.`city_id`='".C('config.now_city')."' AND (`m`.`status`='1' OR `m`.`status`='6') AND (`s`.`status`='1' OR `m`.`mer_id`<='0')";
        //分类相关
        if(!empty($get_grouplist_catfid)){
            $condition_where .= " AND `m`.`cat_fid`='$get_grouplist_catfid'";
        }else if(!empty($get_grouplist_catid)){
            $condition_where .= " AND `m`.`cat_id`='$get_grouplist_catid'";
        }
        $condition_field  = 'CASE WHEN `m`.`mer_id`>0 THEN `s`.`fans_count` ELSE 0 END AS fans_count,`m`.`name` AS `store_name`,`m`.*, FORMAT(`ms`.`score_all`/`ms`.`reply_count`,1) AS pingjun,`ms`.`type`,CASE WHEN `m`.`mer_id`>0 THEN mm.isverify ELSE 0 END AS isverify';
        $condition_table  = array(C('DB_PREFIX').'merchant_store'=>'m');
        //区域
        if($area_id || $circle_id){
            if ($circle_id) {
                $condition_where .= " AND `m`.`circle_id`='$circle_id'";
            } else {
                $condition_where .= " AND `m`.`area_id`='$area_id'";
            }
            if ($order == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`m`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`m`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`m`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }
        }else{
            if ($order == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`m`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`m`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`m`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }

        }
        if($search_txt){
            $condition_where.=" AND `m`.`name` like '%$search_txt%' ";
        }
        //排序
        switch($order){
            case 'solds':		//	人气最高
                $order = '`m`.`hits` DESC,`m`.`store_id` DESC';
                break;
            case 'rating':		//	评价最高
                $order = '`pingjun` DESC,`m`.`store_id` DESC';
                break;
            case 'start':		//	最新发布
                $order = '`m`.`last_time` DESC,`m`.`store_id` DESC';
                break;
            case 'juli':	//	距离
                $order = '`juli` asc,`m`.`store_id` DESC';
                break;
            default:		//	默认
                $order = '`m`.`sort` DESC,`m`.`store_id` DESC';
        }
        import('@.ORG.wap_group_page');
        $count_group = M('')->table($condition_table)->join('LEFT JOIN '.C('DB_PREFIX').'merchant as s ON s.mer_id = m.mer_id')->where($condition_where)->count();
        $p = new Page($count_group, 10,'page');
        $store_list = M('')->field($condition_field)
            ->table($condition_table)
            ->join('LEFT JOIN `'.C('DB_PREFIX').'merchant_score` as `ms` ON m.`store_id` = ms.`parent_id` AND ms.type=2')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'merchant` as `s` ON m.`mer_id` = s.`mer_id`')
            ->join('LEFT JOIN  `'.C('DB_PREFIX').'merchant` as `mm` ON mm.mer_id = m.mer_id')
            ->where($condition_where)
            ->group('m.`store_id`')
            ->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $return['totalPage'] = ceil($count_group/10);
        if ($store_list) {
            foreach ($store_list as $k => $v) {
                if (!empty($v['pingjun'])) {
                    $store_list[$k]['xing'] = $v['pingjun'] * 20;
                } else {
                    $v['pingjun'] = 0;
                }
                if (empty($v['pic_info']) && $v['is_claim']==1) {
                    $store_list[$k]['list_pic'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/claim_store.png?t=001';
                } else {
                    $tmp_pic_arr = explode(';', $v['pic_info']);
                    $store_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0], '1');
                }
            }
        }
        $return['store_list'] = $store_list;
        $return['store_count'] = count($store_list);
        return $return;
    }
}
?>