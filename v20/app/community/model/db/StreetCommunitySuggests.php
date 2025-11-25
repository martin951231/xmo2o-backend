<?php
/**
 * 获取街道社区留言建议
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/27 15:36
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class StreetCommunitySuggests extends Model{

    /**
     * 获取列表信息
     * @author:wanziyang
     * @date_time: 2020/5/27 15:36
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitSuggestsList($where,$page=0,$field =true,$order='suggestions_id ASC',$page_size=10) {
        $db_list = $this
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }
    /**
     * 获取列表信息数量
     * @author:wanziyang
     * @date_time: 2020/5/27 16:01
     * @param array $where 查询条件
     * @return array|null|\think\Model
     */
    public function getLimitSuggestsCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取列表信息详情
     * @author:wanziyang
     * @date_time: 2020/5/27 17:21
     * @param array $where 查询条件
     * @param bool|string $field 查询出的字段
     * @return array|null|\think\Model
     */
    public function getSuggestsDetail($where, $field =true) {
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/28 10:08
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 添加留言
     * @author lijie
     * @date_time 2020/09/14
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }
	
	public function deleteStreetCommunitySuggests ($where)
	{
		return $this->where($where)->delete();
	}
}