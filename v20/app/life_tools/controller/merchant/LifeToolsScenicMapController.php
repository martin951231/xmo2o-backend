<?php
declare (strict_types = 1);

namespace app\life_tools\controller\merchant;

use app\http\exceptions\ParametersException;
use app\life_tools\model\service\LifeToolsScenicMapService;
use app\life_tools\validate\merchant\LifeToolsScenicMap as LifeToolsScenicMapValidate;
use app\merchant\controller\merchant\AuthBaseController;
use map\Coordinate;
use think\helper\Str;

class LifeToolsScenicMapController extends AuthBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常
    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    private function validateParameter($method = 'post',array $params = [], string $scenario = '') : array
    {
        empty($params) && $params = input($method.'.');
        $params['mer_id'] = $this->merId;
        $validate = validate(LifeToolsScenicMapValidate::class);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException($validate->getError());
        }

        return $params;
    }
    //endregion
    //region 景区地图
    /**
     * 景区列表
     */
    public function scenicList()
    {
        $data = app(LifeToolsScenicMapService::class)->scenicList($this->merId);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图列表
     */
    public function mapList()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->mapList($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图创建/修改
     */
    public function saveMap()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->saveMap($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图状态修改
     */
    public function saveMapStatus()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->saveMapStatus($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图删除
     */
    public function mapDel()
    {
        $params = $this->validateParameter();
        foreach ($params['map_ids'] as $map_id){
            $data = app(LifeToolsScenicMapService::class)->mapDel($map_id,$params);
        }

        return api_output(self::STATUS_OK, $data);
    }
    //endregion
    //region 景区地图标注
    /**
     * 景区地图标注列表
     */
    public function mapPlaceList()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->mapPlaceList($params);

        if($data && cfg('tianditu_web_key')){
            foreach ($data as $value){
                list($value->longitude,$value->latitude) = Coordinate::bd09ToWgs84($value->longitude,$value->latitude);
            }
        }

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图标注创建/修改
     */
    public function saveMapPlace()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->saveMapPlace($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图标注删除
     */
    public function mapPlaceDel()
    {
        $params = $this->validateParameter();
        foreach ($params['place_ids'] as $place_id){
            $data = app(LifeToolsScenicMapService::class)->mapPlaceDel($place_id,$params);
        }

        return api_output(self::STATUS_OK, $data);
    }
    //endregion
    //region 景区地图路线

    /**
     * 景区地图路线创建/修改
     */
    public function mapLineList()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->mapLineList($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图路线创建/修改
     */
    public function saveMapLine()
    {
        $mapLine = input('post.map_lines',[]);

        foreach ($mapLine as $line){
            $params = $this->validateParameter('post',$line);
            foreach ($params['scenic_location_line'] as $position) {
                $this->validateParameter('post', $position,'position');
            }

            $data = app(LifeToolsScenicMapService::class)->saveMapLine($params);
        }

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区地图路线删除
     */
    public function mapLineDel()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->mapLineDel($params);

        return api_output(self::STATUS_OK, $data);
    }
    //endregion
    //region 景区标注点分类
    /**
     * 景区标注点分类列表
     * @return \think\response\Json
     */
    public function categoryList()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->categoryList($params);

        return api_output(self::STATUS_OK, $data);
    }
    /**
     * 景区标注点创建/修改
     */
    public function saveCategory()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->saveCategory($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 景区标注点删除
     */
    public function categoryDel()
    {
        $params = $this->validateParameter();

        $data = app(LifeToolsScenicMapService::class)->categoryDel($params);

        return api_output(self::STATUS_OK, $data);
    }
    //endregion

}
