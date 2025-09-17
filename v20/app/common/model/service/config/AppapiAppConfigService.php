<?php
/**
 * 系统后台app配置model
 * Author: 衡婷妹
 * Date Time: 2020/12/09 09:50
 */
namespace app\common\model\service\config;

use app\common\model\db\AppapiAppConfig;
use think\facade\Cache;
class AppapiAppConfigService
{
    public $appapiAppConfigObj = null;
    public function __construct()
    {
        $this->appapiAppConfigObj = new AppapiAppConfig();
    }
    /**
     * 获取配置数据
     * @param string $field 字段名 默认空
     * @return array|\think\Model|null
     */
    public function get($field = ''){
        $appConfig = Cache::get('app_config');
        if(empty($appConfig)){
            $appapiAppConfig = $this->getSome([['pigcms_id','>','0']]);
            foreach($appapiAppConfig as $value){
                $appConfig[$value['var']] = replace_file_domain($value['value']);
            }
            Cache::set('app_config',$appConfig);
        }

        if($field == ''){
            // 返回所有
            return $appConfig;
        }else{
            // 返回单个配置项的值
            return $app_config[$field] ?? '';
        }
    }


    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where,$field = true ){
        if(empty($where)){
            return false;
        }

        $result = $this->appapiAppConfigObj->getOne($where,$field);
        if(empty($result)){
            return [];
        }

        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where=[]){
        try {
            $result = $this->appapiAppConfigObj->getSome($where);
        } catch (\Exception $e) {
            return [];
        }
        return $result->toArray();
    }

    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->appapiAppConfigObj->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->appapiAppConfigObj->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }


}
