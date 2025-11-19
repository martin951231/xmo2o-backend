<?php
/**
 * 系统后台用户model
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\db;
use think\Model;
class Config extends Model {

    /**
     * 根据平台配置项的数据
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigList($where=[],$field=true) {
        $result = $this->field($field)->where($where)->select();
        return $result;
    }

    /**
     * 更新阿里云oss配置信息
     * @author: chenxiang
     * @date: 2020/5/19 11:23
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveConfig($where = [],$data = '') {
        $result = $this->where($where)->data($data)->save();
        return $result;
    }

    /**
     * 获取站点配置项信息
     * @author: chenxiang
     * @date: 2020/5/19 13:57
     * @param array $where
     * @param string $order
     * @return \think\Collection
     */
    public function getTmpConfigList($where = [], $order = '') {
        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    /**
     * 获取name值为条件的个数
     * @param array $where
     * @return int
     */
    public function getDataNumByName($where = []) {
        $result = $this->where($where)->count();
        return $result;
    }

    /**
     * 查询一条数据
     * @param array $where
     * @return array|Model|null
     */
    public function getDataOne($where = []) {
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 更新数据
     * @param $data
     * @return bool
     */
    public function saveConfigData($data) {
        if(empty($data['name'])){
            return false;
        }
        $result = $this->where(['name'=>$data['name']])->find();
        $result->value = $data['value'];
        return $result->save(); 
    }

    /**
     * 添加一条数据
     * @param $data
     * @return int|string
     */
    public function addConfigData($data) {
        $result = $this->insert($data);
        return $result;
    }
    /**
     * 获取库里的BaiduMap AK
     * @author zhumengqun
     * @param
     * @return \think\Collection
     */
    public function getAk()
    {
        $where = [
            'name' => 'baidu_map_ak'
        ];
        return $this->field('value')->where($where)->select();
    }

    public static function onAfterWrite($obj){
        //TODO: 后续备用，涉及到配置更改需要同步的可以这里处理
    }
}
