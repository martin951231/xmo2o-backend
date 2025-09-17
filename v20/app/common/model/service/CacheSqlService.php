<?php
/**
 * 系统后台清除缓存
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/14 13:57
 */

namespace app\common\model\service;
use app\common\model\db\CacheSql as CacheSql;
use app\common\model\service\config\LangServiceService;
use app\traits\WebIpLocationTraits;
use think\facade\Cache;
class CacheSqlService {
	
	use WebIpLocationTraits;
	
    public $cacheSqlModel = null;
    public function __construct()
    {
        $this->cacheSqlModel = new CacheSql();
    }
    
    /**
     * 清除全部缓存
     */
    public function clearCache() {
        Cache::clear();
        invoke_cms_model('Cache_sql/clearCache');
        return true;
    }

    /**
     * 删除缓存
     */
    public function deleteCache($param) {
        $name = $param['name'] ?? '';
        if ($name == 'o2oconfig') {
            //旧版系统配置更改删除V20对应的全局配置缓存
            Cache::delete('o2oconfig');
            Cache::delete('o2oconfig_');
            Cache::delete('o2oconfig_chinese');
            $langLists = (new LangServiceService())->getSome([['id', '>', '0']]);
            foreach ($langLists as $l) {
                Cache::delete('o2oconfig_' . $l['val']);
            }
        } else {
            Cache::delete($name);
        }
        return true;
    }
	
	public function getIpLocationV20($param)
	{
		$ip = $param['ip'];
		return $this->getIpLocation($ip);
	}
	
	
}