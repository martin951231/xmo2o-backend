<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use app\common\model\service\AreaService as AreaService;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        // 当前语言
        $nowLang = $this->request->param('now_lang');
        if(empty($nowLang) && cookie('system_lang')){
            $nowLang = cookie('system_lang');
        }
        $cache = cache();
        $cache->set('now_lang',$nowLang);

		//多城市情况下，动态调整城市区域的值
		if (cfg('many_city') || cfg('location_mode')) 
		{
			//动态调整config里的now_city值，当前城市
			$nowCity = $this->request->param("now_city", "0", "intval");
			if($nowCity)
			{
				cfg('now_city', $nowCity);
			}
			
			//动态调整config里的now_area值，当前区域
			$nowArea = $this->request->param("now_area", "0", "intval");
			if($nowArea)
			{
				cfg('now_area', $nowArea);
			}
			
			//动态调整config里的now_shop_city值，外卖业务的当前城市
			$nowShopCity = $this->request->param("now_shop_city", "0", "intval");
			if($nowShopCity)
			{
				cfg('now_shop_city', $nowShopCity);
			}
		}
		
		//国外情况下设置当前城市的时区，根据使用谷歌地图来判断系统是否是国外的运营
        if (cfg('google_map_ak') && (!empty($nowCity) || !empty($nowArea))) 
		{
            if (!empty($nowArea)) 
			{
                $areaInfo = (new AreaService())->getAreaByAreaId($nowArea);
            }
			
			//如果区域有，以区域为准，不查城市级别
			if (empty($areaInfo['timezone']) && !empty($nowCity))
			{
                $cityInfo = (new AreaService())->getAreaByAreaId($nowCity);
            }

            //area的时区优先于城市的时区
            if (!empty($areaInfo['timezone']) || !empty($cityInfo['timezone']))
			{
                date_default_timezone_set(!empty($areaInfo['timezone']) ? $areaInfo['timezone'] : $cityInfo['timezone']);
            }
        }

        $lng = $this->request->param("lng", "", "trim");
        $lat = $this->request->param("lat", "", "trim");
        if($lng && $lat){
            $this->request->lng = $lng;
            $this->request->lat = $lat;
        }
        
	}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    public function __call($name, $arguments){
        //这里返回不弹窗的状态码，避免前端代码最新后台接口未更新用户端报错。尤其是小程序常见这种情况
        return api_output(1009, [], "找不到{$name}方法", 404);
    }
}
