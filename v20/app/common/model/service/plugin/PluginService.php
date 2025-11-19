<?php
/**
 * 应用中心
 * add by 衡婷妹
 */

namespace app\common\model\service\plugin;

use app\common\model\db\Plugin;
use app\common\model\service\admin_user\SystemMenuService;

class PluginService{
    public $pluginModel = null;
    public function __construct()
    {
        $this->pluginModel = new Plugin();
    }

    /**
     * 获得所有菜单
     * @param $data array 数据
     * @return array
     */
    public function getAllMenuTree($param , $user = []){
        $from = $param['from'] ?? 'system';
        $keyword = $param['keyword'] ?? '';
        $conditionPlugin = [];
        switch($from){
            case 'merchant':
                $conditionPlugin[] = ['p.plugin_merchant', '=', 1];
                $conditionPlugin['p.plugin_merchant'] = 1;
                break;
            case 'system':
                $conditionPlugin[] = ['p.plugin_system', '=', 1];
                break;
            case 'house':
                $conditionPlugin[] = ['p.plugin_house', '=', 1];
                break;
        }

        $conditionPlugin[] = ['p.status', '=', 1];
        $conditionPlugin[] = ['p.show', '=', 1];

        // 关键字搜索
        if($keyword){
            $conditionPlugin[] = ['p.plugin_name','like','%'.$keyword.'%'];
        }

        //插件分类列表
        $order = [
            'cat_sort' => 'DESC',
            'cat_id' => 'ASC',
        ];
        $pluginCatList = (new PluginCatService())->getSome([['cat_id','>',0]], '', $order);
        foreach($pluginCatList as $value){
            $pluginList[$value['cat_id']] = $value;
            $pluginList[$value['cat_id']]['plugin_list'] = [];
        }

        // 插件列表
        $order = [
            'p.plugin_sort' => 'DESC',
            'p.plugin_id' => 'ASC',
        ];
        $field = 'p.*,m.component,m.path,m.id as sid';
        if($from == 'system'){
            $plugin = $this->getPluginMenuByJoin('system_menu',$conditionPlugin,$field,$order);

        }else{
            $plugin = $this->getPluginMenuByJoin('new_merchant_menu',$conditionPlugin,$field,$order);
        }

        foreach($plugin as $value){
            $value = $this->formatMenuList($value,$from,$user);
            if(!$value){
                continue;
            }

            $pluginList[$value['cat_id']]['plugin_list'][] = $value;
        }

        foreach($pluginList as $key =>$value){
            if(empty($value['plugin_list'])){
                unset($pluginList[$key]);
            }
        }
        $pluginList = array_values($pluginList);
        
        return $pluginList;
    }

    /**
     * 组装数据
     * @param $plugin array 数据
     * @param $from string 来源 system merchant
     * @param $user array 用户信息
     * @return array
     */
    public function formatMenuList($plugin,$from,$user){
        if(!empty($plugin)){
            $plugin['plugin_ico_arr'] = array_flip(explode(' ',$plugin['plugin_ico']));
        }

        if($plugin['system_menu_id']){
            // 营销活动 权限
            if($from=='system' && !cfg('wxapp_url') && $plugin['system_menu_id']==96){
                return false;
            }

            if(!$plugin['sid']){
                // 没有此菜单
                return false;
            }

            if($from=='system'){
                //没权限的不展示
                if($user['level']==2 || in_array($plugin['system_menu_id'],$user['menus'])){
                    $plugin['link_url'] = $this->getMenuPath($plugin);
                    $plugin['link_type'] = 'new_blank';
                }else{
                    return false;
                }
            }else{
                //没权限的不展示
                if(in_array($plugin['system_menu_id'],explode(',',$user['menus']))){
                    $plugin['link_url'] = $this->getMenuPath($plugin);
                    $plugin['new_tab'] = 1;
                }else{
                    return false;
                }
            }

        }else{
            $plugin['link_type'] = 'new_blank';
            $plugin['link_url'] = (new SystemMenuService())->getUrl('Index','detail',['plugin_id'=>$plugin['plugin_id'],'from'=>$from],'','plugin.php');
        }
        $plugin['image'] = cfg('site_url').'/plugin/logo/'.$plugin['plugin_label'].'.png';
        // 替换自定义设置的菜单名称
        $plugin = (new SystemMenuService())->replaceMenuName($plugin);
        return $plugin;
    }

    // 组合前端所需字段
    public function getMenuPath($value){
        $name = 'menu_'.$value['system_menu_id']; // 菜单name，保持唯一
        if($value['plugin_system']){
            //自定义装修的path处理
            if (!empty($value['path']) && $value['path'] == '/common/platform.custom/index') {
                $value['path'] = $value['path'] . '?source=platform';
            }
            $path = cfg('site_url').'/v20/public/platform/#'.(isset($value['path'])&&$value['path'] ? $value['path'] : '/common/platform.iframe/'.$name);
        }else{
            $path = cfg('site_url').'/v20/public/platform/#'.(isset($value['path'])&&$value['path'] ? $value['path'] : '/merchant/merchant.iframe/'.$name);
        }
        return $path;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->pluginModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->pluginModel->updateThis($where, $data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return [];
        }

        $result = $this->pluginModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getPluginMenuByJoin($table='',$where = [], $field = true,$order=true){
        if(empty($table)){
            return [];
        }

        $result = $this->pluginModel->getPluginMenuByJoin($table, $where, $field, $order);
        if(empty($result)){
            return [];
        }
        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->pluginModel->getSome($where, $field, $order, $page, $limit);
        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->pluginModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }
}