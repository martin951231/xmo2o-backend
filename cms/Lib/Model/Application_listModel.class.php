<?php
class Application_listModel extends Model
{
    public function my_left($village_id, $property_id=0, $application_id=0) {
        $where = array(
            'ab.use_id' => $village_id,
            'ab.from' => 0,
            'ab.status' => 0,
        );
        if ($property_id>0) {
            $where['other_id'] = $property_id;
        }
        $field = 'al.application_id, al.application_name, al.application_property, al.application_house, al.application_house_type';
        $order = 'ab.add_time DESC,ab.bind_id DESC';
        $application_list = M('Application_bind')
            ->join(' as ab LEFT JOIN ' . C('DB_PREFIX') . 'application_list as al ON al.application_id=ab.application_id')
            ->field($field)
            ->order($order)
            ->where($where)
            ->select();
        $url = '';
        if ($application_list) {
            foreach ($application_list as &$item) {
                if ($item && $item['application_house']) {
                    if (strpos($item['application_house'],'http') === false && strpos($item['application_house'],'https') === false) {
                        $item['application_house'] = C('config.site_url') . $item['application_house'] . '&application_type=house';
                    } elseif ($item['application_house_type']==0 || $item['application_house_type']==1) {
                        $item['application_house'] = C('config.site_url') . $item['application_house'] . '&application_type=house';
                    }
                    if ($application_id && $application_id>0 && $item['application_id']==$application_id) {
                        $url = $item['application_house'];
                    }
                }
            }
            if (!$url) {
                $first = reset($application_list);
                $url = $first['application_house'];
                $application_id = $first['application_id'];
            }
        }
        $list = array(
            'url' => $url,
            'village_id' => $village_id,
            'application_id' => $application_id,
            'property_id' => $property_id,
            'application_list' => $application_list,
            'count' => count($application_list)
        );
        return $list;
    }

    /**
     * author wanzy
     * @param $application_id 应用id
     * @param $village_id 社区id
     * @param bool $is_open 是否为开启配置
     * @return bool
     */
    public function set_config($application_id, $village_id, $is_open=true) {
        $where = array(
            'village_id' => $village_id
        );
        if ($application_id && $application_id==28) {
            if ($is_open) {
                $has_visitor = 1;
            } else {
                $has_visitor = 0;
            }
            // 访客登记开关
            $set = M('House_village')->where($where)->setField('has_visitor',$has_visitor);
        } elseif($application_id && $application_id==14) {
            if ($is_open) {
                $has_express_service = 1;
            } else {
                $has_express_service = 0;
            }
            // 快递管理
            $set = M('House_village')->where($where)->setField('has_express_service',$has_express_service);
        } elseif($application_id && $application_id==1) {
            if ($is_open) {
                $has_kefu = 1;
            } else {
                $has_kefu = 0;
            }
            // 客服im
            $set = M('House_village')->where($where)->setField('has_kefu',$has_kefu);
        } elseif($application_id && $application_id==5) {
            if ($is_open) {
                $open_face_door = 1;
            } else {
                $open_face_door = 0;
            }
            // 人脸识别门禁
            $set = M('House_village')->where($where)->setField('open_face_door',$open_face_door);
        }
        return $set;
    }

    /**
     * 获取社区应用是否绑定使用
     * author wanzy
     * @param int $application_id 应用id
     * @param int $village_id 社区id
     * @param int $property_id 物业id
     * @return bool
     */
    public function get_application_auth($application_id, $village_id, $property_id=0) {
        if($application_id==5) {
            // 人脸识别门禁，查询下配置项，返回下应用是否可用,根据开关判断应用状态
            $open_face_door = M('House_village')->where(array('village_id'=>$village_id))->getField('open_face_door');
            if ($open_face_door==1) {
                return true;
            } else {
                return false;
            }
        }elseif (in_array($application_id,array(4,6,7))) {
            // 固定应用属于默认绑定
            return true;
        }
        $m_application_bind = M('Application_bind');
        $where = array(
            'application_id' => $application_id,
            'from' => 0,
            'use_id' => $village_id,
            'status' => 0,
        );
        if ($property_id) {
            $where['other_id'] = $property_id;
        }
        $info = $m_application_bind->where($where)->field('bind_id')->find();
        if ($info) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取社区所有绑定的应用  返回其对应id数组
     * author wanzy
     * @param int $village_id 社区id
     * @param int $property_id 物业id
     * @return bool
     */
    public function get_application_all_auth($village_id, $property_id=0) {
        $m_application_bind = M('Application_bind');
        $where_bind = array(
            'from'=>0,
            'use_id'=> $village_id,
            'status' => 0
        );
        if ( $property_id>0) {
            $where_bind['property_id'] =  $property_id;
        }
        $village_info=M('House_village_info')->where(['village_id'=>$village_id])->getField('works_order_switch');
        if($village_info == 1){
            $where_bind['application_id'] = ['not in', [16,18,19]];
        }
        $application_arr = $m_application_bind->where($where_bind)->order('application_id desc')->getField('application_id',true);
        if (empty($application_arr)) {
            $application_arr = array();
        }
        return $application_arr;
    }

    public function get_package_msg() {

    }
}
?>