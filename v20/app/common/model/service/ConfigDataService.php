<?php
/**
 * 极光数据 操作
 * Author: chenxiang
 * Date Time: 2020/5/23 15:01
 */

namespace app\common\model\service;

use app\common\model\db\ConfigData;
use think\facade\Cache;

class ConfigDataService
{
    public $configDataObj = null;
    public function __construct()
    {
        $this->configDataObj = new configData();
    }

    /**
     * 获取配置项所有的数据
     * @param array $param
     * @return array|\think\Model|null
     */
    public function getConfigData($expire = 3600, $forceUpdate = false)
    {
        $configData = Cache::get('system_config_data');

        if (empty($configData) || $forceUpdate) {
            $configData = [];
            $configs = $this->getSome([],'`name`,`value`');
            foreach ($configs as $key => $value) {
                $configData[$value['name']] = $value['value'];
            }
            Cache::set('system_config_data', $configData, $expire);
        }

        return $configData;
    }

    /**
     * 系统后台获取某个分组的数据
     * @param array $param
     * @return array|\think\Model|null
     */
    public function getDataList($param = []) {
        $gid = $param['gid'] ?? '';
        if (empty($gid)){
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        // 获取配置项列表
        $where = [
            'gid' => $gid
        ];
        $list = $this->getSome($where,true,['sort'=>'desc']);
        foreach ($list as &$_config){
            $_config['desc'] = $_config['config_desc'];
        }

        // 组装数据
        $configList = [[
            'name' => L_('基本设置'),
            'tab_id' => 'base',
            'list' => $list
        ]];
        $configList = (new ConfigService())->build_list($configList);

        $returnArr = [
            'gid' => $gid,
            'group_list' => ['gid'=>$gid],
            'config_list' => $configList,
            'domain' => cfg('site_url')
        ];

        return $returnArr;
    }


    /**
     * 系统后台提交表单
     * User: 衡婷妹
     * Date: 2021/03/29
     * @return bool
     */
    public function amend($param = []) {
        if (empty($param)){
            return true;
        }

        foreach($param as $key => $value) {
            $data['name'] = $key;
            $data['value'] = trim(stripslashes(htmlspecialchars_decode($value)));

            $where['name'] = $key;
            $before = $this->getDataOne($where);
            if ($before) {// 编辑数据
                $this->saveData($where, $data);
            } else {// 保存数据
                $this->addData($data);
            }
        }

        Cache::set('system_config_data', []);
        invoke_cms_model('Config_data/remove_cache');
        return true;
    }

    /**
     * 获取体育首页热门推荐
     * @param array $param
     * @return array|\think\Model|null
     */
    public function getSportsIndexHotRecommond($gid) {
        // 获取配置项列表
        $where = [
            'gid' => $gid
        ];
        $configData = [];
        $list = $this->getSome($where,true,['sort'=>'desc']);
        foreach ($list as $key => $value) {
            $configData[$value['name']] = $value['value'];
        }

        $tabArr = [
            [
                'name'=> L_('场馆'),
                'type'=> 'stadium',
            ],
            [
                'name'=> L_('课程'),
                'type'=> 'course',
            ],
            [
                'name'=> L_('活动'),
                'type'=> 'competition',
            ],
            [
                'name'=> L_('商城'),
                'type'=> 'mall',
            ],
        ];

        $returnArr = [
            'tab_arr' => $tabArr,
            'config_list' => $configData,
        ];

        return $returnArr;
    }

    /**
     * 获取景区首页热门推荐
     * @param array $param
     * @return array|\think\Model|null
     */
    public function getScenicIndexHotRecommond($gid) {
        // 获取配置项列表
        $where = [
            'gid' => $gid
        ];
        $configData = [];
        $list = $this->getSome($where,true,['sort'=>'desc']);
        foreach ($list as $key => $value) {
            $configData[$value['name']] = $value['value'];
        }

        $tabArr = [
            [
                'name'=> L_('文旅'),
                'type'=> 'scenic',
            ],
            [
                'name'=> L_('酒店'),
                'type'=> 'hotel',
            ],
            [
                'name'=> L_('商城'),
                'type'=> 'mall',
            ],
        ];
        
        if(!empty(customization('life_tools'))){
            array_push($tabArr,[
                'name'=> L_('活动'),
                'type'=> 'appoint',
            ]);
        }

        $returnArr = [
            'tab_arr' => $tabArr,
            'config_list' => $configData,
        ];

        return $returnArr;
    }

    /**
     * 获取一条数据
     * @param array $where
     * @return array|\think\Model|null
     */
    public function getDataOne($where = []) {
        $data = $this->configDataObj->getDataOne($where);
        return $data;
    }

    /**
     * 更新数据
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveData($where = [], $data = []) {
        $result = $this->configDataObj->saveData($where, $data);
        return $result;
    }

    /**
     * 添加一条数据
     * @param array $data
     * @return int|string
     */
    public function addData($data = []) {
        $id = $this->configDataObj->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 获得多条数据
     * @param array $where
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0) {
        $result = $this->configDataObj->getSome($where, $field,$order,$page,$limit);

        if (empty($result)){
            return [];
        }
        return $result->toArray();
    }

    public function get_one($whereArr) {
        // 初始化 物业管理员 数据层
        $db_config = new ConfigData();
        $info = $db_config->getOne($whereArr);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        }else{
            $info = array();
        }
        return $info;
    }

}
