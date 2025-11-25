<?php
//平台后台操作日志
class System_logModel extends Model
{
	/**
	 * 添加日志
	 * @param string  $content     [描述操作内容]
	 * @param string  $business    [业务模块名称]
	 * @param integer $data_id     [业务数据ID]
	 * @param array   $before_form [操作之前的数据数组]
	 * @param array   $after_form  [操作之后的数据数组]
	 */
	public function add_log($content, $business = '', $data_id = 0, $before_form = [], $after_form = []){
		if(empty($content)) return false;
		$data = [
			'addtime' => time(),
			'admin_id' => session('system')['id'],
			'content' => $content,
			'business' => $business,
			'data_id' => $data_id,
			'post_content' => serialize($_REQUEST),
			'before_form' => serialize($before_form),
			'after_form' => serialize($after_form)
		];
		return $this->data($data)->add() ? true : false;
	}
}