<?php
/**
 * 图文管理分类标签model
 * Author: wangchen
 * Date Time: 2021/5/26
 */

namespace app\atlas\model\db;
use think\Model;
class AtlasSpecial extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得分类标签列表
     * @param $where array 条件
     * @return array
     */
    public function getAtlasSpecialList($where = [], $order = '', $field = '*'){
        $field = 'id,name';
        $result = $this->where($where)->field($field)->order($order)->select()->toArray();
        return $result;
    }

    /**
     * 获得一条分类标签数据
     * @param $where array 条件
     * @return array
     */
    public function getAtlasSpecialInfo($where = [], $field = '*'){
        $result = $this->where($where)->field($field)->find();
        return $result;
    }

    /**
     * 图文管理分类标签修改/添加
     */
    public function getAtlasSpecialCreate($id, $cat_id, $name, $sort, $type_id, $content){
        // $data = explode(PHP_EOL,$content);   无效
        $data = $this->strsToArray($content);
        if($id > 0){
            // 修改
            $data2 = [];
            $data3 = [];
            $result = $this->where(['id'=>$id])->update(['name'=>$name, 'sort'=>$sort, 'type_id'=>$type_id, 'content'=>$content]);
            foreach($data as $v){
                $count = (new AtlasSpecialOption())->where(['special_id'=>$id,'name'=>$v])->find();
                if($count){
                    $data3[] = ['special_id'=>$count['special_id'],'id'=>$count['id'],'name'=>$count['name']];
                }else{
                    $data2[] = ['special_id'=>$id,'name'=>$v];
                }
            }
            (new AtlasSpecialOption())->where(['special_id'=>$id])->delete();
            if($data3){
                (new AtlasSpecialOption())->insertAll($data3);
            }
            if($data2){
                (new AtlasSpecialOption())->insertAll($data2);
            }
        }else{
            // 添加
            $result = $this->insertGetId(['name'=>$name, 'cat_id'=>$cat_id, 'sort'=>$sort, 'type_id'=>$type_id, 'content'=>$content]);
            // 写入选项值
            foreach($data as $v){
                $data2[] = ['special_id'=>$result,'name'=>$v];
            }
            (new AtlasSpecialOption())->insertAll($data2);
        }
        return $result;
    }

    /**
     * 图文管理分类标签删除
     */
    public function getAtlasSpecialDel($id){
        // 修改
        $result = $this->where(['id'=>$id])->delete();
        return $result;
    }

    /**
     * 数组转换
     */
    function strsToArray($strs) {
        $array = array();
        $strs = str_replace("\r\n", ',', $strs);
        $strs = str_replace("\n", ',', $strs);
        $strs = str_replace("\r", ',', $strs);
        $array = explode(',', $strs);
        return $array;
    }
}