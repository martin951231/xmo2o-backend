<?php
/**
 * 体育预约活动model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsAppoint extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $json = ['custom_form'];
    protected $jsonAssoc = true;

    /**
     * 预约列表
     */
    public function getList($where = [], $field = true,$order='appoint_id desc',$page=0,$limit=0){
        $list= $this->getSome($where,$field,$order,$page,$limit)->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['activity_time']=date("Y-m-d H:i:s",$v['start_time']).'-'.date("Y-m-d H:i:s",$v['end_time']);
            }
        }
        return $list;
    }

    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getListAndMer($where, $field = 'a.*',$order='a.appoint_id desc', $page=1,$pageSize=10)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('a')
            ->field($field)
            ->join($prefix . 'merchant m', 'm.mer_id = a.mer_id')
            ->where($where)
            ->order($order);
        $out['total']=$arr->count();
        $out['list']=$arr->page($page, $pageSize)
            ->select()->toArray();
        return $out;
    }

    /**
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 获取预约信息
     */
    public function getToolAppointMsg($where){
        $msg=$this->where($where)->find();
        if(empty($msg)){
            $msg=[];
        }else{
            $msg=$msg->toArray();
        }
        return $msg;
    }

    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $pageSize
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     * 列表
     */
    public function AppointList($where = [], $field = true,$order=true,$page=1,$pageSize=10,$whereColumn=[]){
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $arr = $this
            ->field($field)
            ->where($where);
        if($whereColumn){
            $arr = $arr->where($whereColumn);
        }
        $arr = $arr->order($order)
            ->paginate($limit)->toArray();
        return $arr;
    }

    public function getStartTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i', $data['start_time']);
    }
    public function getEndTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i', $data['end_time']);
    }

    /**
     * 获取详情
     */
    public function getDetail($appoint_id)
    {
        $data = $this->where('appoint_id', $appoint_id)->find();
        if($data){
            return $data->toArray();
        }else{
            return false;
        }
    }
}