<?php
/**
 * 首页常用应用表
 * add by 衡婷妹
 */

namespace app\common\model\service\plugin;

use app\common\model\db\HotMenu;
use app\common\model\service\admin_user\SystemMenuService;

class HotMenuService{
    public $hotMenuModel = null;
    public function __construct()
    {
        $this->hotMenuModel = new HotMenu();
    }

    /**
     * 获得已添加的数据
     * @param $data array 数据
     * @return array
     */
    public function getList($from,$user){
        $returnArr = [];
        $where = [
            ['h.id' , '>', 0],
            ['p.status' , '=', 1],
            ['p.show' , '=', 1],
            ['p.plugin_system' , '=', 1],
        ];
        $order = [
            'h.sort' => 'ASC',
            'h.id' => 'ASC',
        ];

        $field = 'h.*,p.plugin_name,p.plugin_logo,p.plugin_ico,p.plugin_txt,p.plugin_desc,p.plugin_system,p.plugin_label,p.system_menu_id,m.id as sid, m.path';
        $plugin = $this->hotMenuModel->getList($where,$field,$order);
        if(empty($plugin)) {
            return ['list'=>[]];
        }
        $plugin = $plugin->toArray();

        $pluginModel = new PluginService();
        foreach($plugin as $key => &$value){
            $value = $pluginModel->formatMenuList($value,$from,$user);
            if(!$value){
                unset($plugin[$key]);
                continue;
            }
        }
        $returnArr['list'] = array_values($plugin);
        return $returnArr;
    }

    /**
     * 新增编辑数据
     * @param $data array 数据
     * @return array
     */
    public function editHotMenu($param){
        $menuList =  $param['menu_list'] ?? [];

        // 删除原来的
        $this->hotMenuModel->where([['id','>',0]])->delete();
        
        // 没有要添加的
        if(empty($menuList)) {
            return true;
        }

        $data = [];
        foreach ($menuList as $menu){
            $data[] = [
                'plugin_id' => $menu['plugin_id'],
                'sort' => $menu['sort'],
            ];
        }
        $res = $this->hotMenuModel->addAll($data);
        if($res === false) {
            throw new \think\Exception('编辑失败');
        }

        return true;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->hotMenuModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 添加多条记录
     * @param $data array 数据
     * @return array
     */
    public function addAll($data){
        $res = $this->hotMenuModel->AddAll($data);
        if(!$res) {
            return false;
        }

        return $res;
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
            $result = $this->hotMenuModel->updateThis($where, $data);
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

        $result = $this->hotMenuModel->getOne($where, $order);
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

        $result = $this->hotMenuModel->getPluginMenuByJoin($table, $where, $field, $order);
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
        $result = $this->hotMenuModel->getSome($where, $field, $order, $page, $limit);
        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->hotMenuModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }
}