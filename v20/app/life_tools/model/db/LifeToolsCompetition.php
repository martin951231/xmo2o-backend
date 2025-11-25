<?php
/**
 * 体育赛事活动model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsCompetition extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $json = ['custom_form'];
    protected $jsonAssoc = true;

    /**
     * 赛事列表
     */
    public function getList($where = [], $field = true,$order='competition_id desc',$page=0,$limit=0){
        $list=$this->getSome($where,$field,$order,$page,$limit)->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['activity_time']=date("Y-m-d H:i:s",$v['start_time']).'-'.date("Y-m-d H:i:s",$v['end_time']);
            }
        }
        return $list;
    }

    /**
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 获取赛事信息
     */
    public function getToolCompetitionMsg($where){
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
    public function competitionList($where = [], $field = true,$order=true,$page=1,$pageSize=10){
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $arr = $this
            ->field($field)
            ->where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $arr;
    }

}