<?php
class Employee_listModel extends Model
{
    /**
     * @param int $status 状态 -1 不查 0 未审核 1 审核通过 2审核拒绝    5 查询 审核通过且开启的信息
     * @param string $order 排序方法
     * @param int $cat_id  分类id
     * @param int $cat_fid  分类父id
     * @param int $cat_pfcid  分类父父id
     * @param int $area_id   区域id
     * @param int $circle_id  商圈id
     * @param int $lat  定位地址 纬度
     * @param int $long  定位地址 经度
     * @param string $keyword  搜索关键词
     * @param int $round  范围距离
     * @return mixed
     */
    public function employee_list($status=-1,$order='time',$cat_id=0, $cat_fid=0, $cat_pfcid=0, $area_id=0, $circle_id=0, $lat=0, $long=0, $keyword = '',$round=0)
    {

        $field = 'a.*,b.nickname,b.avatar,b.phone,CASE WHEN a.store_id>0 THEN c.name ELSE "" END AS  merchant_store_name,CASE WHEN a.store_id>0 THEN c.circle_id ELSE "" END AS  circle_id,d.cat_name';

        $where = '1=1';
        if ($status != -1 && $status != 5) {
            $where .= ' AND a.examine_status='.$status;
        } elseif($status==5) {
            $where .= ' AND a.examine_status=1 AND a.is_open=1';
        }

        if ($lat>0 && $long>0) {
            $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`a`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`a`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`a`.`long`*PI()/180)/2),2)))*1000) AS juli";
            if($round>0){
                $where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`a`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`a`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`a`.`long`*PI()/180)/2),2)))*1000)<{$round} ";
            }
        }
        if ($cat_id) {
            $where .= ' AND `a`.`cat_id`='.$cat_id;
        }
        if ($cat_fid) {
            $where .= ' AND `a`.`cat_fid`='.$cat_fid;
        }
        if ($cat_pfcid) {
            $where .= ' AND `a`.`cat_pfcid`='.$cat_pfcid;
        }
        if ($circle_id) {
            $where .= ' AND `c`.`circle_id`='.$circle_id;
        } elseif ($area_id) {
            $where .= ' AND `a`.`area_id`='.$area_id;
        }

//        fdump($keyword, 'keyword', true);

        if ($keyword) {
            $where .= ' AND `a`.`true_name` LIKE "%'.$keyword.'%"';
        }


        //排序
        switch($order){
            case 'thumb_num':   // 点赞数排序
                $order = '`a`.`thumb_num` DESC,`a`.`add_time` DESC';
                break;
            case 'click':   // 点击数排序
                $order = '`a`.`click_num` DESC,`a`.`add_time` DESC';
                break;
            case 'popularity':   // 人气最高 点赞数+收藏数
                $order = '(`a`.`collect_num` +`a`.`thumb_num`) DESC,`a`.`add_time` DESC';
                break;
            case 'fans':   // 粉丝数排序
                $order = '`a`.`fans_num` DESC,`a`.`add_time` DESC';
                break;
            case 'rating': // 评分排序
                $order = '`a`.`score_average` DESC,`a`.`add_time` DESC';
                break;
            case 'start': // 时间排序
                $order = '`a`.`last_time` DESC,`a`.`add_time` DESC';
                break;
            case 'juli':  // 距离排序 离我最近
                $order = 'juli ASC';
                break;
            default:        // 默认排序
                $order = '`a`.`sort` DESC, `a`.`add_time` DESC';
        }
        $count_employee_list = $this->alias('a')
            ->distinct(true)
            ->where($where)
            ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
            ->join('LEFT JOIN pigcms_merchant_store as c on a.store_id=c.store_id')
            ->COUNT();

        import('@.ORG.system_page');
        $p = new Page($count_employee_list,20);
        $employee_list = $this->alias('a')
            ->distinct(true)
            ->field($field)
            ->where($where)
            ->order($order)
            ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
            ->join('LEFT JOIN pigcms_merchant_store as c on a.store_id=c.store_id')
            ->join('LEFT JOIN pigcms_employee_category as d on a.cat_id=d.cat_id')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        $m_area = M('Area');
        if ($employee_list) {
            foreach ($employee_list as &$val) {
                if ($val['write_field']){
                    $val['write_field'] = unserialize($val['write_field']);
//                    $val['write_field'] = $this->write_field_info($val['write_field']);
                    $val['address_info'] = $this->write_field_address($val['write_field']);
                }
                if ($val['circle_id']) {
                    $val['circle_name'] = $m_area->where(array('area_id' => $val['circle_id']))->getField('area_name');
                }
                if ($val['lat'] <= 0 && $val['long'] <= 0) {
                    unset($val['juli']);
                } else {
                    $val['juli']&&$val['juli_txt'] = $this->wapRange($val['juli']);
                }
                if ($val && !$val['avatar']) {
                    // 头像没有赋予默认头像
                    $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
                }
                $val['url'] = C('config.site_url')  . '/wap.php?g=Wap&c=Employee&a=employee_detail&employee_id='.$val['id'];
            }
        }

        $pagebar = $p->show();
        $info['list'] = $employee_list;
        $info['count'] = $count_employee_list;
        $info['totalPage'] = ceil($count_employee_list/20);
        return $info;
    }


    /**
     * 计算从业者评分数（显示分值为所有人打分的总分值/打分人数）
     * @param $employee_id
     * @return mixed
     */
    public function employee_score_num($employee_id) {
        $reply_info = M('Employee_reply')
            ->field('COUNT(reply_uid) as total, SUM(score_num) as total_num')
            ->where(array('employee_id' => $employee_id))->find();
        if ($reply_info) {
            $score = intval($reply_info['total_num']) / intval($reply_info['total']);
        } else {
            $score = 0;
        }
        return $score;
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
     * 处理从业者填写信息
     * @param $write_field
     * @return string
     */
    public function write_field_info($write_field){
        $site_url = file_domain();
        foreach ($write_field as &$val) {
            if($val['type'] == 8) {
                $val['value'] = $val['date_value'] . ' ' . $val['time_value'];
            } elseif($val['type'] == 10) {
                if ($val['value']) {
                    foreach ($val['value'] as &$item) {
                        if (!$item) {
                            unset($item);
                            continue;
                        }
                        $item = $site_url . $item;
                    }
                }
            }
        }
        return $write_field;
    }


    /**
     * 处理从业者填写信息 单独提取地址信息
     * @param $write_field
     * @return string
     */
    public function write_field_address($write_field){
        $address = '';
        foreach ($write_field as &$val) {
            if($val['type'] == 2) {
                if ($val['address']) {
                    $address = $val['address'];
                } else {
                    $address = $val['value'] . ' ' . $val['value-desc'];
                }
            }
        }
        return $address;
    }



    /**
     * @param int $uid  用户id
     * @param int $status 状态 -1 不查 0 未审核 1 审核通过 2审核拒绝    5 查询 审核通过且开启的信息
     * @param string $order 排序方法
     * @param int $cat_id  分类id
     * @param int $cat_fid  分类父id
     * @param int $cat_pfcid  分类父父id
     * @param int $area_id   区域id
     * @param int $circle_id  商圈id
     * @param int $lat  定位地址 纬度
     * @param int $long  定位地址 经度
     * @param string $keyword  搜索关键词
     * @param int $round  范围距离
     * @return mixed
     */
    public function employee_collect_list($uid, $status=-1,$order='time',$cat_id=0, $cat_fid=0, $cat_pfcid=0, $area_id=0, $circle_id=0, $lat=0, $long=0, $keyword = '',$round=0)
    {

        $field = 'cc.uid as collect_uid,a.*,b.nickname,b.avatar,b.phone,CASE WHEN a.store_id>0 THEN c.name ELSE "" END AS  merchant_store_name,CASE WHEN a.store_id>0 THEN c.circle_id ELSE "" END AS  circle_id,d.cat_name';

        $where = '`cc`.`uid`='.$uid;

        if ($status != -1 && $status != 5) {
            $where .= ' AND a.examine_status='.$status;
        } elseif($status==5) {
            $where .= ' AND a.examine_status=1 AND a.is_open=1';
        }

        if ($lat>0 && $long>0) {
            $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`a`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`a`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`a`.`long`*PI()/180)/2),2)))*1000) AS juli";
            if($round>0){
                $where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`a`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`a`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`a`.`long`*PI()/180)/2),2)))*1000)<{$round} ";
            }
        }
        if ($cat_id) {
            $where .= ' AND `a`.`cat_id`='.$cat_id;
        }
        if ($cat_fid) {
            $where .= ' AND `a`.`cat_fid`='.$cat_fid;
        }
        if ($cat_pfcid) {
            $where .= ' AND `a`.`cat_pfcid`='.$cat_pfcid;
        }
        if ($circle_id) {
            $where .= ' AND `c`.`circle_id`='.$circle_id;
        } elseif ($area_id) {
            $where .= ' AND `a`.`area_id`='.$area_id;
        }

//        fdump($keyword, 'keyword', true);

        if ($keyword) {
            $where .= ' AND `a`.`true_name` LIKE "%'.$keyword.'%"';
        }


        //排序
        switch($order){
            case 'thumb_num':   // 点赞数排序
                $order = '`a`.`thumb_num` DESC,`a`.`add_time` DESC';
                break;
            case 'click':   // 点击数排序
                $order = '`a`.`click_num` DESC,`a`.`add_time` DESC';
                break;
            case 'popularity':   // 人气最高 点赞数+收藏数
                $order = '(`a`.`collect_num` +`a`.`thumb_num`) DESC,`a`.`add_time` DESC';
                break;
            case 'fans':   // 粉丝数排序
                $order = '`a`.`fans_num` DESC,`a`.`add_time` DESC';
                break;
            case 'rating': // 评分排序
                $order = '`a`.`score_average` DESC,`a`.`add_time` DESC';
                break;
            case 'start': // 时间排序
                $order = '`a`.`last_time` DESC,`a`.`add_time` DESC';
                break;
            case 'juli':  // 距离排序 离我最近
                $order = 'juli ASC';
                break;
            default:        // 默认排序
                $order = '`cc`.`add_time` DESC';
        }
        $count_employee_list = M('Employee_collect_bind')->alias('cc')
            ->distinct(true)
            ->where($where)
            ->join('LEFT JOIN pigcms_employee_list as a on a.id=cc.collect_id')
            ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
            ->join('LEFT JOIN pigcms_merchant_store as c on a.store_id=c.store_id')
            ->COUNT();

        import('@.ORG.system_page');
        $p = new Page($count_employee_list,20);
        $employee_list = M('Employee_collect_bind')->alias('cc')
            ->distinct(true)
            ->field($field)
            ->where($where)
            ->order($order)
            ->join('LEFT JOIN pigcms_employee_list as a on a.id=cc.collect_id')
            ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
            ->join('LEFT JOIN pigcms_merchant_store as c on a.store_id=c.store_id')
            ->join('LEFT JOIN pigcms_employee_category as d on a.cat_id=d.cat_id')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        $m_area = M('Area');
        if ($employee_list) {
            foreach ($employee_list as &$val) {
                if ($val['write_field']){
                    $val['write_field'] = unserialize($val['write_field']);
//                    $val['write_field'] = $this->write_field_info($val['write_field']);
                    $val['address_info'] = $this->write_field_address($val['write_field']);
                }
                if ($val['circle_id']) {
                    $val['circle_name'] = $m_area->where(array('area_id' => $val['circle_id']))->getField('area_name');
                }
                if ($val['lat'] <= 0 && $val['long'] <= 0) {
                    unset($val['juli']);
                } else {
                    $val['juli']&&$val['juli_txt'] = $this->wapRange($val['juli']);
                }
                if ($val && !$val['avatar']) {
                    // 头像没有赋予默认头像
                    $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
                }
                $val['url'] = C('config.site_url')  . '/wap.php?g=Wap&c=Employee&a=employee_detail&employee_id='.$val['id'];
            }
        }

        $pagebar = $p->show();
        $info['list'] = $employee_list;
        $info['count'] = $count_employee_list;
        $info['totalPage'] = ceil($count_employee_list/20);
        return $info;
    }


}
?>