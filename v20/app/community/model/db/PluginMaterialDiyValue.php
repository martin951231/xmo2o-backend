<?php
/**
 * @author : liukezhu
 * @date : 2021/11/12
 */
namespace app\community\model\db;
use think\Model;

class PluginMaterialDiyValue extends Model{


    public function getList($where,$field='*',$order='id desc',$page=0,$limit=10)
    {
        $sql = $this->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true,$order='id desc'){
        $sql = $this->where($where)
            ->field($field)->order($order)
            ->find();
        if($sql && !$sql->isEmpty()){
            $list = $sql->toArray();
        }else{
            $list = [];
        }
        return $list;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    public function getLeftList($where,$field =true,$order='a.id DESC',$page=0,$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('plugin_material_diy_template b', 'b.template_id=a.template_id AND b.from_id=a.from_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    public function getLeftCount($where) {
        $list = $this->alias('a')
            ->leftJoin('plugin_material_diy_template b', 'b.template_id=a.template_id AND b.from_id=a.from_id')
            ->where($where)
            ->count();
        return $list;
    }


    public function getLeftFind($where,$field)
    {
        $list = $this->alias('a')
            ->leftJoin('plugin_material_diy_template b', 'b.template_id=a.template_id AND b.from_id=a.from_id')
            ->where($where)->order('a.id desc')->field($field)->find();
        return $list;
    }


}