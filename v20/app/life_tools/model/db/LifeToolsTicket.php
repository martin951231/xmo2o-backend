<?php

/**
 * 门票model
 */


namespace app\life_tools\model\db;

use app\life_tools\model\service\LifeToolsTicketService;
use think\Model;

class LifeToolsTicket extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = "ticket_id";

    const SKU_MULTI_SPEC = 1;//多规格
    const SKU_STADIUM = 2;//场馆分布图

    public $auditStatusMap = [
        0   =>  '待审核',
        1   =>  '审核成功',
        2   =>  '审核失败'
    ];
    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getList($where)
    {
        $arr = $this->field(true)->where($where)->order('sort DESC')->select();
        if (!empty($arr)) {
            return $arr->toArray();
        }else{
            return [];
        }
    }
    public static function onBeforeUpdate($ticket)
    {
        return LifeToolsTicketService::checkSku($ticket);
    }

    /**
     * 获取详情
     * @param $where
     * @return array
     */
    public function getDetail($where, $field = 'r.*,g.type,g.long,g.lat,g.address,g.is_close')
    {
        if (!is_array($where)) {
            $where = ['ticket_id' => $where];
        }
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
            ->where($where)
            ->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getListByTool($where, $field = 'r.*',$page=1,$pageSize=10,$order='g.tools_id desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
            ->join($prefix . 'merchant m', 'm.mer_id = r.mer_id')
            ->where($where)
            ->order($order);
        $out['total']=$arr->count();
        $out['list']=$arr->page($page, $pageSize)
            ->select()->toArray();
        return $out;
    }

    public function getLabelArrAttr($value, $data)
    {
        $label_arr = [];
        if($data['label']){
            $label_arr = explode(' ', $data['label']);
            if(count($label_arr)){
                $label_arr = array_filter($label_arr);
            }
        }
        return $label_arr;
    }


    public function getAuditStatusTextAttr($value, $data)
    {
        return $this->auditStatusMap[$data['audit_status']] ?? '';
    }

    /**
     * 获取审核列表
     */
    public function getAuditList($where, $field='*', $pageSize = 10)
    {
        $prefix = config('database.connections.mysql.prefix');
        if($field){
            $where[] = ['t.is_del' ,'=', 0];
            $where[] = ['r.is_del' ,'=', 0];
            $data = $this->alias('r')
                ->field($field)
                ->join($prefix . 'life_tools t', 'r.tools_id = t.tools_id')
                ->join($prefix . 'merchant m', 'm.mer_id = r.mer_id')
                ->append(['audit_status_text'])
                ->where($where)
                ->order('r.add_audit_time DESC,r.create_time DESC')
                ->paginate($pageSize);
        }else{
            $data = $this->alias('r')
                ->join($prefix . 'life_tools t', 'r.tools_id = t.tools_id')
                ->join($prefix . 'merchant m', 'm.mer_id = r.mer_id')
                ->where($where)
                ->count();
        }

        return $data;
    }

    /**
     * 获取列表以及价格
     * @param $where
     * @return array
     */
    public function getListAndPrice($where, $field = 'r.*',$page=1,$pageSize=10,$order='g.tools_id desc',$day='')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
            ->join($prefix . 'merchant m', 'm.mer_id = r.mer_id')
            ->join($prefix . 'life_tools_ticket t', 'r.ticket_id = t.ticket_id')
            ->leftjoin($prefix . 'life_tools_ticket_sale_day s', 'r.ticket_id = s.ticket_id AND s.day = "'.$day.'"')
            ->where($where)
            ->where(function ($query){
                $query->where('s.pigcms_id',null)->whereOr('s.is_sale',1);
            })
            ->group('s.ticket_id')
            ->order($order);
        $out['total']=$arr->count();
        $out['list']=$arr->page($page, $pageSize)
            ->select()->toArray();
        return $out;

    }

    /**
     * 获取列表以及门票分销配置信息
     */
    public function getListAndDistribution($where = [], $field = true,$order=true,$page=0,$pageSize=0)
    {
        $result =  $this->alias('a')->leftjoin('life_tools_ticket_distribution b','a.ticket_id = b.ticket_id')->where($where)->field($field)->order($order);
        if($pageSize){
            $limit = [
                'page' => $page ?? 1,
                'list_rows' => $pageSize
            ];
            $result =  $result->paginate($limit);
        }else{
            $result =  $result->select();
        }
        return $result;
    }
}
