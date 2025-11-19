<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\traits;

	use think\Exception;
	use think\facade\Cache;
	use think\facade\Config;

	trait CacheTypeTraits
	{
		/**
		 * 队列 Redis 注意非队列业务请勿使用，请勿随意清空DB或者删除 key
		 * @return Cache
		 * @throws Exception
		 */
		public function queueReids() 
		{
			if (empty(Config::get('cache.stores.queueRedis'))){
				throw new Exception("请先配置 Redis 缓存项[queueRedis] " . app()->getRootPath() . 'config/cache.php');
			}
			return Cache::store('queueRedis');
		}

		/**
		 * 数据层级的缓存，介于临时缓存和长久缓存之间，可以考虑在总后台增加一个 清除数据缓存按钮
		 * @return Cache
		 * @throws Exception
		 */
		public function dataRedis()
		{
			if (empty(Config::get('cache.stores.redis'))){
				throw new Exception("请先配置 Redis 缓存项[redis] " . app()->getRootPath() . 'config/cache.php');
			}
			return Cache::store('redis');
		}
	}