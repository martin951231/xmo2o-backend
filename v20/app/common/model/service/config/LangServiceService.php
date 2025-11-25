<?php
/**
 * 多语言配置model
 * Author: 衡婷妹
 * Date Time: 2020/12/09 10:16
 */
namespace app\common\model\service\config;

use app\common\model\service\ConfigService;
use app\common\model\db\LangService as LangServiceModel;

use think\facade\Cache;
class LangServiceService
{
    public $langServiceModel = null;

    public function __construct(){
        $this->langServiceModel = new LangServiceModel();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where=[]){
        try {
            $result = $this->langServiceModel->getSome($where);
        } catch (\Exception $e) {
            return [];
        }
        return $result->toArray();
    }

}
