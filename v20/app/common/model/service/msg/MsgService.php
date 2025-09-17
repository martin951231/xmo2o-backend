<?php
/**
 * 消息中心service
 * add by lumin
 */

namespace app\common\model\service\msg;
use app\common\model\db\UserMsg;
use app\common\model\db\mail;

class MsgService {
	/**
	 * 根据条件获取数据
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function getData($where = [], $field = true,$order=true,$page=0,$limit=10){
		return (new UserMsg)->getSome($where);
	}

	/**
	 * 获取满足条件的消息数量
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function getCount($where){
		return (new UserMsg)->getCount($where);
	}

	/**
	 * 查询单条消息
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function getInfo($where, $field = true, $order = ''){
		return (new UserMsg)->getOne($where, $field, $order);
	}

	/**
	 * 链表查询
	 * @param  [type] $where    [description]
	 * @param  [type] $page     [description]
	 * @param  [type] $pageSize [description]
	 * @return [type]           [description]
	 */
	public function getPageData($where, $field = true, $page = 1, $pageSize = 10){
		return (new UserMsg)->getMsg($where, $field, $page, $pageSize);
	}

	public function update($where, $data){
		return (new UserMsg)->updateThis($where, $data);
	}

	public function mailGetData($where, $field = true){
		return (new Mail)->getSome($where, $field);
	}
}