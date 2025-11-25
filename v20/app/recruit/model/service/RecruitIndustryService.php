<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitIndustry;

class RecruitIndustryService
{
    /**
     * 列表
     */
    public function getRecruitIndustryList($page, $pageSize){
        $where = ['status'=>0, 'fid'=>0];
        $field = "*";
        $order = 'sort DESC, id DESC';
        $list = (new NewRecruitIndustry())->getRecruitIndustryList($where, $field, $order, $page, $pageSize);
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
    public function getRecruitIndustryCreate($id, $params){
        $list = (new NewRecruitIndustry())->getRecruitIndustryCreate($id, $params);
        return $list;
    }

    /**
     * 单条
     */
    public function getRecruitIndustryInfo($id){
        if($id < 1){
			return [];
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitIndustry())->getRecruitIndustryInfo($where);
        return $list;
    }

    /**
     * 排序
     */
    public function getRecruitIndustrySort($id, $sort){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        $data = ['sort'=>$sort];
        $list = (new NewRecruitIndustry())->getRecruitIndustrySort($where, $data);
        return $list;
    }

    /**
     * 移除
     */
    public function getRecruitIndustryDel($id){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitIndustry())->getRecruitIndustryDel($where);
        return $list;
    }

    //获取所有一级行业
    public function getFirstIndustry(){
        $where = [
            ['fid', '=', 0],
            ['status', '=', 0]
        ];
        $data = (new NewRecruitIndustry())->getSome($where, 'id as industry_id,name as val', 'sort desc');
        if($data){
            return $data->toArray();
        }
        return [];
    }

    /**
     * 二级列表
     */
    public function getRecruitIndustryLevelList($fid, $page, $pageSize){
        $where = ['status'=>0, 'fid'=>$fid];
        $field = "*";
        $order = 'sort DESC, id DESC';
        $list = (new NewRecruitIndustry())->getRecruitIndustryList($where, $field, $order, $page, $pageSize);
        if($list['list']){
            foreach($list['list'] as $k=>$v){
                $list['list'][$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
            }
        }
        return $list;
    }

    /**
     * 求职意向职位列表
     */
    public function resumeIntentionList($industry_array){
        $where = [['id','in',$industry_array]];
        $order = 'sort DESC, id DESC';
        $list = (new NewRecruitIndustry())->where($where)->order($order)->select()->toArray();
        return $list;
    }
}