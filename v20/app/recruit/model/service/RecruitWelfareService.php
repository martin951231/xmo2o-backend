<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitWelfare;

class RecruitWelfareService
{
    /**
     * 列表
     */
    public function getRecruitWelfareList($page, $pageSize){
        $where = ['status'=>0];
        $field = "*";
        $order = 'id DESC';
        $list = (new NewRecruitWelfare())->getRecruitWelfareList($where, $field, $order, $page, $pageSize);
        if($list['list']){
            foreach($list['list'] as $k=>$v){
                $list['list'][$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
            }
        }
        return $list;
    }

    /**
     * 列表-无分页
     */
    public function getRecruitWelfareLabelList($fileds = '*')
    {
        $datas = (new NewRecruitWelfare())->field($fileds)->order('id', 'desc')->select()->toArray();
        $return = [];
        foreach ($datas as $k => $v) {
            $return[] = ['label' => $v['name'], 'value' => (string)$v['id']];
        }
        return array_values($return);
    }

    /**
     * 保存
     */
    public function getRecruitWelfareCreate($id, $params){
        $list = (new NewRecruitWelfare())->getRecruitWelfareCreate($id, $params);
        return $list;
    }

    /**
     * 单条
     */
    public function getRecruitWelfareInfo($id){
        if($id < 1){
			return [];
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitWelfare())->getRecruitWelfareInfo($where);
        return $list;
    }

    /**
     * 移除
     */
    public function getRecruitWelfareDel($id){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitWelfare())->getRecruitWelfareDel($where);
        return $list;
    }

    //获取所有福利
    public function getAllWelfare(){
        $where = [
            ['status', '=', 0]
        ];
        $data = (new NewRecruitWelfare())->getSome($where, 'id as welfare_id,name as val', 'id DESC');
        if($data){
            return $data->toArray();
        }
        return [];
    }

    public function getFuli($ids){
        if(empty($ids)) return [];
        $where = [
            ['id', 'in', $ids]
        ];
        $data = (new NewRecruitWelfare())->getSome($where, 'id,name');
        if($data){
            return $data->toArray();
        }
        return [];
    }
}