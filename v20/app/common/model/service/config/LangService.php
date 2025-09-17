<?php
/**
 * 多语言配置model
 * Author: 衡婷妹
 * Date Time: 2020/12/09 10:16
 */
namespace app\common\model\service\config;

use app\common\model\service\ConfigService;
use app\common\model\db\Lang;
use think\facade\Cache;
class LangService
{
    public $serviceLang = array();
    public $langModel = array();

    public function __construct(){
        $this->langModel = new Lang();
        $this->serviceLang = Cache::get('service_lang');

        if(empty($this->serviceLang)){
            $this->serviceLang = (new LangServiceService())->getSome([['id','>','0']]);
            Cache::set('service_lang', $this->serviceLang);
        }
    }

    /**
     * 获得多语言列表
     * @return array|\think\Model|null
     */
    public function langList(){

        $default = '';
        $return = array(
            'lang_list' => array(),
            'now_lang' => (new ConfigService())->getOneField( 'default_language')
        );
        
        // 默认中文
        if(empty($return['now_lang'])){
            $return['now_lang'] = 'chinese';
        }
        
        $clientLang = request()->server('HTTP_ACCEPT_LANGUAGE');
        $userAgent = request()->server('HTTP_USER_AGENT');
        if($clientLang){
            $clientLang_arr = explode(',', $clientLang);
            $first = $clientLang_arr[0] ? strtolower($clientLang_arr[0]) : strtolower($default);
        } elseif (isset($userAgent)){
            preg_match_all('/[lL]anguage\/([a-zA-Z_]+){1}/', $userAgent, $result);
            $first = $result[1] ? strtolower($result[1][0]) : strtolower($default);
        }

        if($this->serviceLang){
            foreach ($this->serviceLang as $key => $value) {
                $value['as'] = strtolower($value['as']);
                if($first && strpos($value['as'],$first) !== false){
                    $return['now_lang'] = $value['val'];
                }
                $return['lang_list'][] = $value;
            }
        }

        if(cfg('system_lang')){
            $return['now_lang'] = cfg('system_lang');
        }
        return $return;
    }


    /**
     * 插入一条数据
     * @param $where array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->langModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->langModel->getSome($where,$field,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }
        return $result->toArray();
    }

}
