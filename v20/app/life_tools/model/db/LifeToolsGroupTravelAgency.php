<?php
/**
 * 景区团体票旅行社列表
 */

namespace app\life_tools\model\db;
use think\Model;

class LifeToolsGroupTravelAgency extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取旅行社列表
     * @author nidan
     * @date 2022/3/21
     * @param $where
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getTravelList($where,$pageSize=10){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('l')->field('l.id,l.travel_agency_custom_form,l.create_time,l.audit_time,l.audit_msg,l.status,u.phone,u.nickname,l.mer_id')
            ->where($where)
            ->join($prefix.'user u','l.uid = u.uid')
            ->order('id DESC')
            ->paginate($pageSize);
        foreach ($result as $v){
            if($v['travel_agency_custom_form']){
                $v['travel_agency_custom_form'] = json_decode($v['travel_agency_custom_form'],true);
            }
            $v['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s',$v['create_time']) : '';
            $v['audit_time'] = $v['audit_time'] ? date('Y-m-d H:i:s',$v['audit_time']) : '';
        }
        return $result;
    }

    public function getStatus($merId,$uid)
    {
        $audit = $this->getOne(['mer_id'=>$merId,'uid'=>$uid,'is_del'=>0]);
        return $audit;
    }

    /**
     * 获取旅行社认证商家的数量
     * @author nidan
     * @date 2022/3/23
     */
    public function getAuthenticationTravelNum($uid)
    {
        //只统计已经拥有团体票的商家
        $where = [
            'a.status' => 1,
            'a.is_del' => 0,
            'b.is_del' => 0,
            'c.is_del' => 0,
            'd.is_del' => 0,
            'a.uid' => $uid
        ];
        $count = $this->alias('a')
            ->where($where)
            ->join('life_tools_group_ticket b','a.mer_id = b.mer_id')
            ->join('life_tools c','b.tools_id = c.tools_id')
            ->join('life_tools_ticket d','b.ticket_id = d.ticket_id')
            ->group('a.mer_id')
            ->count();
        return $count;
    }
}