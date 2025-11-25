<?php
namespace app\marriage_helper\model\service;

use app\marriage_helper\model\db\MarriageBudget;
use app\marriage_helper\model\db\MarriageUserBudget;

class MarriageBudgetService
{
    /**
     * 预算列表
     */
    public function getBudgetList($where,$order,$page,$pageSize)
    {
        $list['list']=(new MarriageBudget())->getBudgetList($where,$order,$page,$pageSize);
        $list['count']=(new MarriageBudget())->getBudgetCount($where);
        return $list;
    }

    /**
     *预算操作
     */
    public function getBudgetCreate($id, $name){
        $ret = (new MarriageBudget())->getBudgetCreate($id, $name);
        return $ret;
    }

    /**
     *预算详情
     */
    public function getBudgetInfo($id){
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }

        $ret = (new MarriageBudget())->getBudgetInfo($id);
        return $ret;
    }

    /**
     *预算比例操作
     */
    public function getBudgetScaleCreate($number_list){
        $number = 0; $data_list = [];
        foreach($number_list as $k=>$v){
            if($k != 'system_type'){
                $number = $number + $v;
                $ks = explode("scale", $k)[1];
                $data_list[] = ['id'=>$ks, 'scale'=>$v];
            }
        }
        if(empty($number == 100)){
            throw new \think\Exception('各项相加之和必须等于100');
        }
        $ret = (new MarriageBudget())->getBudgetScaleCreate($data_list);
        return $ret;
    }

    /**
     *预算比例详情
     */
    public function getBudgetScaleInfo(){
        $ret = (new MarriageBudget())->getBudgetScaleInfo();
        $number = 0;
        foreach($ret as $v){
            $number = $number + $v['scale'];
        }
        $result['list'] = $ret;
        $result['number'] = $number;
        return $result;
    }

    /**
     *预算删除
     */
    public function getBudgetDel($id){
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }
        // 获取此条预算的比例
        $def = (new MarriageBudget())->getBudgetInfo($id);
        $scale = $def['scale'];
        if($scale > 0){
            // 获取剩余预算礼包数量
            $count = (new MarriageBudget())->getBudgetCount([['id','<>',$id]]);
            $except = intval($scale / $count);
            $than = $scale % $count;
            // 自增数据
            (new MarriageBudget())->getBudgetInc($id, $except);
            if($than > 0){
                (new MarriageBudget())->getBudgetInc($id, 1, 2, $than);
            }
        }
        $ret = (new MarriageBudget())->getBudgetDel($id);
        return $ret;
    }

    public function getAllPercent(){
        $where = [
            ['status', '=', 0],
            ['scale', '>', 0]
        ];

        $data = (new MarriageBudget())->getSome($where,true,'sort desc');
        if($data){
            $data = $data->toArray();
            foreach ($data as $key => $value) {
                $data[$key]['icon'] = replace_file_domain($value['icon']);
            }
            return $data;
        }
        else{
            return [];
        }
    }

    //初始化用户的预算，按照系统设置的比例
    public function initUserBudget($uid, $money){
        (new MarriageUserBudget)->del([['uid','=',$uid]]);
        if($money == '0') {
            return ;
        }
        $money = $money*100;
        $percent = $this->getAllPercent();
        $count = 0;
        foreach ($percent as $key => $value) {
            if($key < count($percent) -1){
                $data = [
                    'uid' => $uid, 
                    'budget' => $value['id'],
                    'display' => 1,
                    'money' => ceil($money*$value['scale']/100)
                ];
                $count += $data['money'];
            }
            else{
                $data = [
                    'uid' => $uid, 
                    'budget' => $value['id'],
                    'display' => 1,
                    'money' => $money-$count
                ];
            }
            (new MarriageUserBudget)->add($data);
        }
        return ;
    }

    public function getUserBudget($uid){
        $where = [
            ['uid', '=', $uid],
            ['display', '=', 1]
        ];
        $data = (new MarriageUserBudget)->getSome($where);
        if($data){
            return $data->toArray();
        }
        else{
            return [];
        }
    }

    public function setSingleBudgetMoney($uid, $budget_id, $money){
        $where = ['uid'=>$uid, 'budget' => $budget_id];
        $find = (new MarriageUserBudget)->getOne($where);

        (new MarriageUserBudget)->updateThis($where, ['money'=>get_format_number($money*100)]);
        return $money*100 - $find['money'];
    }

    public function configBudget($uid, $budget_config){
        $return  = 0;
        foreach ($budget_config as $key => $value) {
            $where = ['uid'=>$uid, 'budget' => $key];
            $find = (new MarriageUserBudget)->getOne($where);
            if(empty($find) && $value != '0'){
                $data = [
                    'uid' => $uid, 
                    'budget' => $key,
                    'display' => 1,
                    'money' => 0
                ];
                (new MarriageUserBudget)->add($data);
            }elseif(!empty($find) && $find['display'] != $value){
                (new MarriageUserBudget)->updateThis(['id'=>$find['id']], ['display'=>$value]);
                if($value == '0'){
                    $return = $return - $find['money'];
                }
                elseif($value == '1' && $find['money'] > 0){
                    $return = $return + $find['money'];
                }
            }
        }
        return $return;
    }
}