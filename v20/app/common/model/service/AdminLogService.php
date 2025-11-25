<?php
/**
 * 系统后台清除缓存
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/14 13:57
 */

namespace app\common\model\service;
use app\common\model\db\AdminLog as AdminLog;
use think\facade\Cache;
class AdminLogService {
	/**
	 * 增加系统管理员日志
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-03
	 *
	 * @return boolean
	 */
	public function addLog($data){
		$adminLogDb = new AdminLog();
		$adminLogDb->insert($data);
	}
}