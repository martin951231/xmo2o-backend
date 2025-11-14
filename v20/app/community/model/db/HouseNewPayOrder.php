<?php


namespace app\community\model\db;

use think\Model;

class HouseNewPayOrder extends Model
{
    /**
     * 根据分组获取未交费列表
     * @author lijie
     * @date_time 2021/06/24
     * @param array $where
     * @param array $whereOr
     * @param string $group
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getSumByGroup($where = [], $group = '', $field = true,$page=0,$limit=10,$whereOr=array())
    {
        if($whereOr){
            $data = $this->alias('p')
                ->leftJoin('house_new_charge_project j','p.project_id = j.id')
                ->field($field)
                ->where($where)
                ->where(function ($sq) use ($whereOr){
                    $sq->where([['p.room_id','=',$whereOr['room_id']]])->whereOr([['p.position_id','in',$whereOr['position_ids']]]);
                })
                ->page($page,$limit)
                ->group($group)
                ->select();
        }else{
            $data = $this->alias('p')
                ->leftJoin('house_new_charge_project j', 'p.project_id = j.id')
                ->where($where)
                ->field($field)
                ->page($page,$limit)
                ->group($group)
                ->select();
        }
        return $data;
    }

    public function getSumByGroupOrder($where = [], $group = '', $field = true,$page=1,$limit=10,$order='o.order_id desc')
    {
        $where1='';
        if(isset($where['string'])){
            $where1=$where['string'];
            unset($where['string']);
        }

        $sql = $this->alias('o')
            ->leftJoin('house_new_charge_project p', 'o.project_id = p.id')
            ->where($where);
        if($where1){
            $sql= $sql->where($where1);
        }
        $data = $sql->where($where1)
            ->field($field)
            ->page($page,$limit)
            ->group($group)
            ->order($order)
            ->select();
        return $data;
    }
    
    /**
     * 根据分组获取未交费数量
     * @author lijie
     * @date_time 2021/06/24
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getCountByGroup($where = [], $group = '')
    {
        $count = $this->alias('p')
            ->leftJoin('house_new_charge_project j', 'p.project_id = j.id')
            ->where($where)
            ->group($group)
            ->count();
        return $count;
    }

    /**
     * 修改订单
     * @param array $where
     * @param array $data
     * @return bool
     * @author lijie
     * @date_time 2021/06/15
     */
    public function saveOne($where = [], $data = [])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 添加订单
     * @param array $data
     * @return int|string
     * @author lijie
     * @date_time 2021/06/15
     */
    public function addOne($data = [])
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * 订单详情
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getOne($where = [], $field = true,$order='o.order_id DESC')
    {
        $data = $this->alias('o')
            ->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
        return $data;
    }

    public function getOneByRoom($where = [], $whereRaw = '', $field = '*', $order = 'o.order_id DESC')
    {
        $sql = $this->alias('o')
            ->leftJoin('house_village_user_vacancy rm', 'o.room_id = rm.pigcms_id')
            ->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $sql = $sql->field($field);
        if ($order) {
            $sql = $sql->order($order);
        }
        $data = $sql->find();
        return $data;
    }

    public function getListByRoom($where = [], $whereRaw = '', $field = '*', $order = 'o.order_id DESC')
    {
        $sql = $this->alias('o')
            ->leftJoin('house_village_user_vacancy rm', 'o.room_id = rm.pigcms_id')
            ->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $sql = $sql->field($field);
        if ($order) {
            $sql = $sql->order($order);
        }
        $data = $sql->select();
        return $data;
    }
    public function getCountByGroupRoom($where = [],$whereRaw = '', $group = '')
    {
        $sql = $this->alias('o')
            ->leftJoin('house_village_user_vacancy rm', 'o.room_id = rm.pigcms_id')
            ->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $count = $sql->group($group)->count();
        return $count;
    }

    public function getListByGroupRoom($where = [], $whereRaw = '', $field = '*', $group = '',$having='')
    {
        $sql = $this->alias('o')
            ->leftJoin('house_village_user_vacancy rm', 'o.room_id = rm.pigcms_id')
            ->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $sql = $sql->field($field)->group($group);
        if($having){
            $sql=$sql->having($having);
        }
        $data = $sql->select();
        return $data;
    }
    public function getOrderListByRoomId($where = [], $whereRaw = '', $field = '*', $order = 'o.order_id DESC', $page = 0,$limit = 10)
    {
        $sql = $this->alias('o')
            ->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $sql = $sql->field($field);
        if ($order) {
            $sql = $sql->order($order);
        }
        if($page>0 && $limit>0){
            $sql = $sql->page($page, $limit);
        }
        $data = $sql->select();
        return $data;
    }
    /**
     * 订单详情
     * @param array $where
     * @param bool $field
     *  @return mixed
     * @author:zhubaodi
     * @date_time: 2021/6/25 19:12
     */
    public function get_one($where = [], $field = true,$order='order_id DESC')
    {
        $where1='';
        if(isset($where['string'])){
            $where1=$where['string'];
            unset($where['string']);
        }
        if(empty($where1)){
            $data = $this->alias('o')->where($where)->field($field)->order($order)->find();
        }
        else{
            $data = $this->alias('o')->where($where)->where($where1)->field($field)->order($order)->find();
        }
        return $data;
    }

    public function get_one_join_summary($where = [], $field = true,$order='o.order_id DESC',$joinTableArr=array())
    {
        $where1='';
        if(isset($where['string'])){
            $where1=$where['string'];
            unset($where['string']);
        }
        if(empty($where1)){
            $sql = $this->alias('o')->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
            if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
            }
            $data =$sql->where($where)->field($field)->order($order)->find();
        }
        else{
            $sql = $this->alias('o')->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
            if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
            }
            $data =$sql->where($where)->where($where1)->field($field)->order($order)->find();
        }
        return $data;
    }
    /**
     * 订单列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getList($where = [], $field = true, $page = 0, $limit = 10, $order = 'o.order_id DESC')
    {
        if ($page)
            $data = $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->where($where)->field($field)->page($page, $limit)->order($order)->select();
        else
            $data = $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->where($where)->field($field)->order($order)->select();

        return $data;
    }

    public function getPayOrders($where = [], $field = true, $page = 0, $limit = 10, $order = 'o.order_id DESC',$joinTableArr=array())
    {
        if($page){
            $sql = $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->leftJoin('house_village_parking_position pp', 'pp.position_id = o.position_id');
            if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
            }
            $data =$sql->where($where)->field($field)->page($page,$limit)->order($order)->select();
        } else{
            $sql = $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->leftJoin('house_village_parking_position pp', 'pp.position_id = o.position_id');
            if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
            }
            $data =$sql->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }
    public function getJoinSum($where=[],$sum='modify_money',$joinTableArr=array())
    {
        $sql = $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->leftJoin('house_village_parking_position pp', 'pp.position_id = o.position_id');
        if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
            $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
        }
        $sum =$sql->where($where)->sum($sum);
        return $sum;
    }
    public function getPayOrdersCount($where = [],$joinTableArr=array())
    {
        $sql= $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->leftJoin('house_village_parking_position pp', 'pp.position_id = o.position_id');
        if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
            $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
        }
        $count=$sql->where($where)->count();
        return $count;
    }

    /**
     * 订单数量
     * @param $where
     * @return int
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getCount($where)
    {
        $count = $this->alias('o')->where($where)->count();
        return $count;
    }



    /**
     * 获取车辆列表
     * @author lijie
     * @date_time 2020/07/17 14:09
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPayLists($where,$field=true,$page=1,$limit=15,$order='order_id DESC')
    {
        if($page == 0){
            $data = $this->where($where)->field($field)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }
        return $data;
    }

    /**
     * 订单数量
     * @param $where
     * @return int
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getPayCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 查询作废账单
     * @author:zhubaodi
     * @date_time: 2021/6/29 14:47
     */
    public function getCancelOrder($where = [],$whereRaw='', $field = true, $page = 0, $limit = 10, $order = 'o.order_id DESC',$where_summary=array(),$joinTableArr=array())
    {
        if (empty($order)) {
            $order = 'o.order_id DESC';
        }
        if ($whereRaw) {
            if ($page) {
                $sql = $this->alias('o')
                    ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
                    ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id');
                if (!empty($where_summary)) {
                    $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
                    foreach ($where_summary as $suwhere) {
                        $where[] = $suwhere;
                    }
                }
                if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                    $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
                }
                $data = $sql->where($where)
                    ->whereRaw($whereRaw)
                    ->field($field)
                    ->page($page, $limit)
                    ->order($order)
                    ->select();
            } else {
                $sql = $this->alias('o')
                    ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
                    ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id');
                if (!empty($where_summary)) {
                    $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
                    foreach ($where_summary as $suwhere) {
                        $where[] = $suwhere;
                    }
                }
                if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                    $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
                }
                $data = $sql->where($where)
                    ->whereRaw($whereRaw)
                    ->field($field)
                    ->order($order)
                    ->select();
            }

        } else {
            if ($page) {
                $sql = $this->alias('o')
                    ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
                    ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id');
                if (!empty($where_summary)) {
                    $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
                    foreach ($where_summary as $suwhere) {
                        $where[] = $suwhere;
                    }
                }
                if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                    $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
                }
                $data = $sql->where($where)
                    ->field($field)
                    ->page($page, $limit)
                    ->order($order)
                    ->select();
            } else {
                $sql = $this->alias('o')
                    ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
                    ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id');
                if (!empty($where_summary)) {
                    $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
                    foreach ($where_summary as $suwhere) {
                        $where[] = $suwhere;
                    }
                }
                if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                    $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
                }
                $data = $sql->where($where)
                    ->field($field)
                    ->order($order)
                    ->select();
            }

        }
        return $data;
    }


    /**
     * 导出账单
     * @author:zhubaodi
     * @date_time: 2021/6/29 14:42
     */
    public function getPayOrder($where = [], $whereRaw = '', $field = true, $order = 'o.order_id DESC', $where_summary = array(),$joinTableArr=array())
    {
        $sql = $this->alias('o')
            ->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')
            ->leftJoin('house_village_parking_position pp', 'pp.position_id = o.position_id');
        if (!empty($where_summary)) {
            $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
            foreach ($where_summary as $suwhere) {
                $where[] = $suwhere;
            }
        }
        if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
            $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
        }
        $sql = $sql->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $data = $sql->field($field)
            ->order($order)
            ->select();

        return $data;
    }


    //todo 作废订单总数
    public function getCancelOrderCount($where = [], $whereRaw = '', $where_summary = array(),$joinTableArr=array())
    {
        if ($whereRaw) {
            $sql = $this->alias('o')
                ->leftJoin('house_village_user_bind u', 'u.pigcms_id = o.pay_bind_id')
                ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
                ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id');
            if (!empty($where_summary)) {
                $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
                foreach ($where_summary as $suwhere) {
                    $where[] = $suwhere;
                }
            }
            if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
            }
            $data = $sql->where($where)
                ->whereRaw($whereRaw)
                ->count();
        } else {
            $sql = $this->alias('o')
                ->leftJoin('house_village_user_bind u', 'u.pigcms_id = o.pay_bind_id')
                ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
                ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id');
            if (!empty($where_summary)) {
                $sql = $sql->leftJoin('house_new_pay_order_summary su', 'o.summary_id = su.summary_id');
                foreach ($where_summary as $suwhere) {
                    $where[] = $suwhere;
                }
            }
            if($joinTableArr && isset($joinTableArr['house_village_user_vacancy']) && $joinTableArr['house_village_user_vacancy']){
                $sql = $sql->leftJoin('house_village_user_vacancy vy', 'o.room_id = vy.pigcms_id');
            }
            $data = $sql->where($where)->count();
        }

        return $data;
    }

    /**
     * 根据分组获取账单列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param string $group
     * @return mixed
     * @author lijie
     * @date_time 2021/06/25
     */
    public function getListByGroup($where = [], $field = true, $page = 0, $limit = 15, $order = 'o.order_id DESC', $group = 'o.room_id,o.position_id')
    {
        if($page){
            $data = $this->alias('o')
                ->leftJoin('house_village_user_vacancy v','o.room_id = v.pigcms_id')
                ->leftJoin('house_village_parking_position p','p.position_id = o.position_id')
                ->where($where)->field($field)->page($page,$limit)->order($order)->group($group)->select();
        } else{
            $data = $this->alias('o')
                ->leftJoin('house_village_user_vacancy v','o.room_id = v.pigcms_id')
                ->leftJoin('house_village_parking_position p','p.position_id = o.position_id')
                ->leftJoin('house_new_charge_project c','c.id = o.project_id')
                ->where($where)->field($field)->order($order)->group($group)->select();
        }
        return $data;
    }

    public function getListByGroupCount($where = [], $group = 'o.room_id,o.position_id')
    {

           $countV = $this->alias('o')
                ->leftJoin('house_village_user_vacancy v','o.room_id = v.pigcms_id')
                ->leftJoin('house_village_parking_position p','p.position_id = o.position_id')
                ->leftJoin('house_new_charge_project c','c.id = o.project_id')
                ->where($where)->group($group)->count();
        
        return $countV;
    }
    public function  getJoin2Sum($where=[],$sum='modify_money'){
        $sum = $this->alias('o')->leftJoin('house_village_user_vacancy v','o.room_id = v.pigcms_id')
                ->leftJoin('house_village_parking_position p','p.position_id = o.position_id')->leftJoin('house_new_charge_project c','c.id = o.project_id')->where($where)->sum($sum);
        return $sum;
    }
    /**
     * 查询历史账单
     * @author:zhubaodi
     * @date_time: 2021/6/29 14:47
     */
    public function getHistoryOrder($where = [],$whereRaw='', $field = true, $order = 'o.order_id DESC')
    {
        if ($whereRaw){
            $data = $this->alias('o')
                ->leftJoin('house_new_charge_project cp','cp.id = o.project_id')
                ->where($where)
                ->whereRaw($whereRaw)
                ->field($field)
                ->order($order)
                ->select();
        }else{
            $data = $this->alias('o')
                ->leftJoin('house_new_charge_project cp','cp.id = o.project_id')
                ->where($where)
                ->field($field)
                ->order($order)
                ->select();
        }


       // print_r($this->getLastSql());exit;
        return $data;
    }

    public function getOrderByGroup($where=[],$field=true,$group='project_id', $order = 'order_id DESC',$page=0,$limit=10)
    {
        $sql = $this->alias('o')->where($where)->field($field)->group($group)->order($order);
        if($page>0){
            $sql= $sql->page($page,$limit);
        }
        $data=$sql->select();
        //fdump_api(['LastSql'=>$sql->getLastSql()],'getOrderByGroup',1);
        return $data;
    }

    public function getOrderByGroupRelateVacancy($where=[],$field=true,$group='o.project_id', $order = 'o.order_id DESC',$page=0,$limit=10)
    {
        $sql = $this->alias('o')->leftJoin('house_village_user_vacancy rr','o.room_id = rr.pigcms_id')->where($where)->field($field)->group($group)->order($order);
        if($page>0){
            $sql= $sql->page($page,$limit);
        }
        $data=$sql->select();
        return $data;
    }
    public function getCount2ByGroup($where = [], $group = '')
    {
        $count = $this->alias('o')->where($where)
            ->group($group)
            ->count();
        return $count;
    }
    /**
     * 获取订单总额
     * @author lijie
     * @date_time 2021/07/01
     * @param array $where
     * @param string $sum
     * @return float|string
     */
    public function getSum($where=[],$sum='modify_money')
    {
        $sum = $this->alias('o')->where($where)->sum($sum);
        return $sum;
    }

    /**获取最多收费项目
     * @author: liukezhu
     * @date : 2021/7/17
     */
    public function getMostChargeProject($where,$field,$order,$group,$num=2){
        $where1='';
        if(isset($where['string'])){
            $where1=$where['string'];
            unset($where['string']);
        }
        $data= $this->alias('o')
            ->leftJoin('house_new_charge_project p','p.id = o.project_id')
            ->leftJoin('house_new_charge_number n','n.id = p.subject_id')
            ->where($where)->where($where1)
            ->field($field)
            ->order($order)
            ->group($group)
            ->limit(0,$num)->select();
        return $data;
    }

    /**
     * 订单统计
     * @author: liukezhu
     * @date : 2021/7/17
     * @param $where
     * @param array $whereAnd
     * @param string $field
     * @return mixed
     */
    public function sumMoney($where,$where1='',$field='pay_money')
    {
        $sumMoney = $this->alias('o')->where($where)->where($where1)->sum($field);
        return $sumMoney;
    }


    /**
     * 查询订单
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getLists($where,$field='*',$page=0,$limit=20,$order='o.order_id desc')
    {
        $sql = $this->alias('o')
            ->leftJoin('user u', 'u.uid = o.uid')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_village_meter_reading r', 'r.id = o.meter_reading_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * 查询订单总数
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $where
     * @return mixed
     */
    public function getCounts($where) {
        $count = $this->alias('o')
            ->leftJoin('user u', 'u.uid = o.uid')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_village_meter_reading r', 'r.id = o.meter_reading_id')
            ->leftJoin('admin a','a.id = r.role_id')
            ->where($where)->count();
        return $count;
    }

    public function get_ones($where = [], $field = true)
    {
        $data = $this->alias('o')
            ->leftJoin('house_village_meter_reading r', 'r.id = o.meter_reading_id')
            ->field($field)->where($where)->find();
        return $data;
    }
    /**
     * 获取订单列表 不联表查询
     * @param $whereOrder
     * @param bool $fieldOrder
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderSingleList($whereOrder, $fieldOrder = true, $page = 1, $limit = 10,$order='order_id DESC'){
        if(empty($whereOrder)){
            return [];
        }
        $count = $this->where($whereOrder)->count();
        $data = $this->where($whereOrder)->field($fieldOrder)->page($page, $limit)->order($order)->select();
        if($data && !$data->isEmpty()){
            return ['list' => $data->toArray(),'count' => $count,'limit' => $limit];
        }else{
            return ['list' => [],'count' => $count,'limit' => $limit];
        }
    }

    /**
     * 查询多个账单
     * @author: liukezhu
     * @date : 2021/12/6
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getOrderList($where,$field = true,$order = 'o.order_id DESC')
    {
        $data = $this->alias('o')
            ->leftJoin('house_new_pay_order_summary s', 's.summary_id = o.summary_id')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
    /**
     * Notes: 获取按照分组的相关信息  支持分页
     * @param $where
     * @param bool $field
     * @param string $group
     * @param int $page
     * @param int $limit
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/11/17 11:46
     */
    public function HouseFeeSummaryList($where, $field=true, $group='uid',$page = 0, $limit = 10) {
        if($page){
            $data = $this->where($where)->field($field)->group($group)->page($page,$limit)->select();
        } else {
            $data = $this->where($where)->field($field)->group($group)->select();
        }
        return $data;
    }


    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return WorkMsgAuditInfo
     */
    public function editFind($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }


    /**
     * 查询订单数据
     * @author: liukezhu
     * @date : 2022/2/8
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     */
    public function getOrder($where,$field='*',$order='order_id desc'){
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    public function getNewPayOrderList($whereArr, $fieldOrder = true, $page = 1, $limit = 10, $order = 'order_id DESC')
    {
        if (empty($whereArr)) {
            return ['list' => [], 'count' => 0, 'limit' => $limit];
        }
        $data = array();
        if ($page > 0) {
            $dataObj = $this->where($whereArr)->field($fieldOrder)->page($page, $limit)->order($order)->select();
            if ($dataObj && !$dataObj->isEmpty()) {
                $data = $dataObj->toArray();
            }
            $count = $this->where($whereArr)->count();
        } else {
            $dataObj = $this->where($whereArr)->field($fieldOrder)->order($order)->select();
            if ($dataObj && !$dataObj->isEmpty()) {
                $data = $dataObj->toArray();
                $count = count($data);
            }
        }
        return ['list' => $data, 'count' => $count, 'limit' => $limit];
    }
    

    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }


    public function getRoomPayOrderList($where = [],$whereRaw='', $field = true, $order = 'o.order_id DESC',$page = 0, $limit = 10)
    {
        $sql = $this->alias('o')
            ->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($whereRaw){
            $sql->whereRaw($whereRaw);
        }
        if ($page) {
            $sql->page($page, $limit);
        }
        $list = $sql->select();
        return $list;
    }

    public function getRoomPayOrderCount($where = [],$whereRaw='')
    {
        $sql = $this->alias('o')
            ->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')
            ->where($where);
        if($whereRaw){
            $sql->whereRaw($whereRaw);
        }
        $list = $sql->count();
        return $list;
    }
    public function getListOr($where = [],$whereRaw ='', $field = true, $page = 0, $limit = 10, $order = 'o.order_id DESC')
    {
        $sql = $this->alias('o')->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')->leftJoin('house_new_charge_project p', 'p.id = o.project_id')->leftJoin('house_new_charge_number n', 'n.id = p.subject_id')->where($where);
       if (!empty($whereRaw)){
          $sql=$sql->whereRaw($whereRaw);
       }
        if ($page){
            $data = $sql->field($field)->page($page, $limit)->order($order)->select();
        }
        else{
            $data =$sql->field($field)->order($order)->select();  
        }

        return $data;
    }
    
    /**
     * 获取停暖用户推送的账单
     */
    public function getCloseHeartingOrderLists($where,$whereRaw,$field,$order='o.order_id desc',$limit='30')
    {
        $data = $this->alias('o')
            ->join('heating_protocol p','p.village_id=o.village_id')
            ->join('close_heating_room r','r.room_id=o.room_id AND r.notice_status=1')
            ->join('close_heating_send s','s.order_id=o.order_id','left')
            ->join('house_new_charge_project cp','cp.id=o.project_id','left')
            ->where($where)
            ->whereRaw($whereRaw)
            ->field($field)
            ->order($order)
            ->limit($limit)
            ->select()
            ->toArray();
        return $data;
    }
    
    /**
     * 获取所有小区的缴费率列表
     */
    public function getContributionRateList($where,$field,$order,$group,$limit)
    {
        $list = $this->alias('o')
            ->where($where)
            ->field($field)
            ->order($order)->group($group)
            ->paginate($limit)
            ->toArray();
        return $list;
    }

    public function getOrderRoomCount($where,$field='*')
    {
        $count = $this->alias('o')->rightJoin('house_village_user_vacancy r', 'o.room_id = r.pigcms_id')->where($where)->group('o.room_id')->count($field);
        return $count;
    }

    public function getOrderRoomSum($where,$field='modify_money')
    {
        $count = $this->alias('o')->leftJoin('house_village_user_vacancy r', 'o.room_id = r.pigcms_id')->where($where)->sum($field);
        return $count;
    }

    public function getMeterReadingSplitOrders($where,$field,$page=0,$limit=10,$order='o.order_id'){
        $sql = $this->alias('o')
            ->leftJoin('house_village_meter_reading m', 'o.meter_reading_id=m.id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
    public function getMeterReadingSplitOrderCount($where){
        $count = $this->alias('o')->leftJoin('house_village_meter_reading m', 'o.meter_reading_id=m.id')->where($where)->count();
        return $count;
    }
}