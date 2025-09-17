<?php
/**
 * 获取街道社区留言建议回复
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/27 17:24
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class StreetCommunitySuggestsReply extends Model{
    /**
     * 获取列表信息
     * @author:wanziyang
     * @date_time: 2020/5/27 17:36
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitSuggestsReplyList($where,$page=0,$field =true,$order='reply_id ASC',$page_size=10) {
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
     * @date_time: 2020/5/27 17:27
     * @param array $where 查询条件
     * @return array|null|\think\Model
     */
    public function getLimitSuggestsReplyCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 添加信息
     * @author: wanziyang
     * @date_time: 2020/5/27 18:49
     * @param array $data 添加内容
     * @return bool
     */
    public function addOne($data) {
        $reply_id = $this->insertGetId($data);
        return $reply_id;
    }

	/**
	 * 删除
	 * @param $where
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteStreetCommunitySuggestsReply ($where)
	{
		return $this->where($where)->delete();
	}
}