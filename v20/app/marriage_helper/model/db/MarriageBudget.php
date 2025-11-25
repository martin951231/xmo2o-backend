<?php
namespace app\marriage_helper\model\db;

use think\Model;

class MarriageBudget extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 预算列表
     */
    public function getBudgetList($where, $order, $page, $pageSize)
    {
        $arr = $this->where($where)->order($order)->page($page, $pageSize)->select();
        if (!empty($arr)) {
            foreach($arr as $k=>$v){
                $arr[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 预算列表总数
     */
    public function getBudgetCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
 
    /**
     * 预算操作
     */
    public function getBudgetCreate($id, $name)
    {
        $data = ['name'=>$name, 'create_time'=>time()];
        if($id > 0){
            // 修改
            $where = ['id' => $id];
            $result = $this->where($where)->update($data);
        }else{
            // 新增
            $result = $this->insert($data);
        }
        if($result===false){
            throw new \think\Exception("操作失败请重试",1005);
        }
        return $result;
    }

    /**
     * 预算详情
     */
    public function getBudgetInfo($id)
    {
        $finds = $this->where(['id'=>$id])->find();
        return $finds;
    }

    /**
     * 预算比例操作
     */
    public function getBudgetScaleCreate($data_list)
    {
        foreach($data_list as $v){
            $this->where(['id'=>$v['id']])->update(['scale'=>$v['scale']]);
        }
        return true;
    }

    /**
     * 预算比例详情
     */
    public function getBudgetScaleInfo()
    {
        $result = $this->field('id, name, scale')->order('sort DESC, id DESC')->select();
        if(!empty($result)){
            return $result->toArray();
        }else{
            return [];
        }
    }

    /**
     * 预算删除
     */
    public function getBudgetDel($id)
    {
        $result = $this->where(['id'=>$id])->delete();
        if($result===false){
            throw new \think\Exception("操作失败请重试",1005);
        }
        return $result;
    }

    /**
     * 预算删除比例均衡自增(除数、余数)
     * type: 1 除数  type 2 余数
     */
    public function getBudgetInc($id,$number,$type=1,$than=0)
    {
        if($type == 1){
            $result = $this->where([['id','<>',$id]])->inc('scale',$number)->update(); 
        }else{
            $result = $this->where([['id','<>',$id]])->inc('scale',$number)->limit($than)->update();
        }
        return $result;
    }
}