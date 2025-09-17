<?php
/**
 * HR管理serice
 * Author: wangchen
 * Date Time: 2021/6/22
 */

namespace app\recruit\model\service;

use net\Http;
use app\recruit\model\db\NewRecruitWelfareLabel;

class RecruitWelfareLabelService
{
    /**
     * 列表
     */
    public function getRecruitHrList($cont, $page, $pageSize){
        $where = ['status'=>0];
        if(!empty($cont)) {
            array_push($where, ['name', 'like', '%'.$cont.'%']);
        }
        $field = "*";
        $order = 'sort DESC, id DESC';
        $list = (new NewRecruitWelfareLabel())->getRecruitHrList($where, $field, $order, $page, $pageSize);
        if($list['list']){
            foreach($list['list'] as $k=>$v){
                $list['list'][$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
            }
        }
        return $list;
    }

    /**
     * 保存
     */
    public function getRecruitHrCreate($id, $params){
        if($params['phone']){
            $phone = (new NewRecruitWelfareLabel())->getRecruitHrCount(['phone'=>$params['phone']]);
            if($phone > 0){
                throw new \think\Exception("手机号已被使用");
            }
        }
        $list = (new NewRecruitWelfareLabel())->getRecruitHrCreate($id, $params);
        return $list;
    }

    /**
     * 单条
     */
    public function getRecruitHrInfo($id){
        if($id < 1){
			return [];
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitWelfareLabel())->getRecruitHrInfo($where);
        return $list;
    }

    /**
     * 移除
     */
    public function getRecruitHrDel($id){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        // 判断是否存在发布职位
        $release = (new NewRecruitWelfareLabel())->getRecruitHrInfo($where);
        if($release){
            if($release['release'] > 0){
                // 删除对应发布职位
                // .......
            }
        }
        $list = (new NewRecruitWelfareLabel())->getRecruitHrDel($where);
        return $list;
    }
}