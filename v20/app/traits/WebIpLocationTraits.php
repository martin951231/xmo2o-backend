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
	use itbdw\Ip\IpLocation;
	use think\Exception;
	use WhichBrowser\Parser as BrowserParse;

	trait WebIpLocationTraits
	{
		/**
		 * 通过输入 IPv4、IPv6 地址，获取IP地理等信息
		 * @param $ip 支持 IPv4、IPv6
		 *
		 * @return array|mixed|string[]
		 */
		public function getIpLocation($ip)
		{
			return IpLocation::getLocation($ip);
		}

		/**
		 * 通过浏览器 UA ，获取系统类型、版本、浏览器类型等信息
		 * @return array
		 */
		public function whichBrowserInfo()
		{
			try {
				if (function_exists('getallheaders')) {
					$result =  new BrowserParse(\getallheaders());
				}else{
					$result =  new BrowserParse($_SERVER['HTTP_USER_AGENT']);
				}

				return [
					'os'                => $result->os->name,
					'os_version'        => $result->os->version ? $result->os->version->toString() : '',
					'browser_name'      => $result->browser->name,
					'browser_version'   => $result->browser->version->value,
					'engine'            => $result->engine->name,
					'manufacturer'      => $result->device->manufacturer,
					'model'             => $result->device->model
				];
			}catch (Exception $e){
				return [];
			}
		}

		/** 通过输入 IPv4、IPv6 地址，获取IP地理、浏览器、系统等信息
		 * @param $ip 支持 IPv4、IPv6
		 *
		 * @return array|string[]
		 */
		public function getIpBrowserInfo($ip)
		{
			$ipInfo = $this->getIpLocation($ip);
			$brwoser = $this->whichBrowserInfo();
			return array_merge($ipInfo,$brwoser);
		}
	}