<?php
/**
 * 团购优惠组合
 * Author: 衡婷妹
 * Date Time: 2020/11/16 16:29
 */

namespace app\group\model\service\order;

use app\group\model\db\GroupCombineActivityBuyLog;
use app\group\model\service\group_combine\GroupCombineActivityService;

class GroupCombineActivityBuyLogService
{
    public $groupCombineActivityBuyLogModel = null;

    public function __construct()
    {
        $this->groupCombineActivityBuyLogModel = new GroupCombineActivityBuyLog();
    }

    /**
     * 团购业务的优惠组合购买日志
     * @param $where
     * @return array
     */
    public function getGroupCombineBuyList($param = [])
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 0;
        $page = ($page-1)*$pageSize;
        $combineId = $param['combine_id'];
        if(empty($combineId)){
            return [];
        }

        $where = [
            'combine_id' => $combineId
        ];
        $order = [
            'id' => 'DESC'
        ];

        $list = $this->getSome($where,true,$order,$page,$pageSize);

        foreach ($list as &$_log){
            $_log['content'] = L_('X1购买了该优惠组合',str_replace_name($_log['user_name']));
            $_log['avatar'] = $_log['avatar'] ? replace_file_domain($_log['avatar']) : '';
            $_log['price'] = get_format_number($_log['price']);
            $_log['pay_time'] = $_log['pay_time'] ? date('Y-m-d H:i:s',$_log['pay_time']) : '';
        }

        return $list;
    }

    /**
     * 优惠组合订单添加一条支付记录
     * @param array $param
     */
    public function addBuyLog($param = [])
    {
        $where = [
            'combine_id' => $param['combine_id'],
        ];
        $combine = (new GroupCombineActivityService())->getOne($where);

        $data = [
            'combine_id' => $param['combine_id'],
            'order_id' => $param['order_id'],
            'user_name' => $param['nickname'],
            'avatar' => $param['avatar'],
            'group_name' => $combine['title'],
            'price' => $param['price'],
            'pay_time' => $param['pay_time'],
            'create_time' => time(),
        ];
        $res = $this->add($data);
        return true;
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCombineActivityBuyLogModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupCombineActivityBuyLogModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
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
            $result = $this->groupCombineActivityBuyLogModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 删除数据
     * @param $data array
     * @return array
     */
    public function del($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->groupCombineActivityBuyLogModel->where($where)->delete();
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }
    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where){
        $result = $this->groupCombineActivityBuyLogModel->getCount($where);
        if(empty($result)){
            return 0;
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
            return false;
        }

        $result = $this->groupCombineActivityBuyLogModel->getOne($where, $order);
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
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupCombineActivityBuyLogModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}