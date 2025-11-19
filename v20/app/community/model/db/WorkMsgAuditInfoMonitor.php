<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/11 9:26
 */

namespace app\community\model\db;

use think\Model;
class WorkMsgAuditInfoMonitor extends Model
{
    /**
     * 获取列表
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getList($where,$page=0,$field =true,$order='id ASC',$page_size=10,$whereOr='') {
        $db_list = $this->alias('a')
            ->leftjoin('work_msg_audit_info b','b.id=a.audit_info_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        if ($whereOr){
            $list = $db_list->where($where)->whereRaw($whereOr)->select();
        }else{
            $list = $db_list->where($where)->select();
        }

      //   print_r($db_list->buildSql());exit;
        return $list;
    }

    /**
     * Notes:获取数量
     * @param $where
     * @return int
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     */
    public function getCount($where){
        $count = $this->alias('a')->leftjoin('work_msg_audit_info b','b.id=a.audit_info_id')->where($where)->count();
        return $count;
    }

    /**
     * 添加
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $pigcms_id = $this->insertGetId($data);
        return $pigcms_id;
    }

}