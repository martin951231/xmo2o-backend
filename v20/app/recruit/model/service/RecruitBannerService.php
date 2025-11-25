<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitBanner;

class RecruitBannerService
{
    /**
     * 列表
     */
    public function getRecruitBannerList($page, $pageSize){
        $where = ['status'=>0];
        $field = "*";
        $order = 'sort DESC, id DESC';
        $list = (new NewRecruitBanner())->getRecruitBannerList($where, $field, $order, $page, $pageSize);
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
    public function getRecruitBannerCreate($id, $params){
        $list = (new NewRecruitBanner())->getRecruitBannerCreate($id, $params);
        return $list;
    }

    /**
     * 单条
     */
    public function getRecruitBannerInfo($id){
        if($id < 1){
			return [];
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitBanner())->getRecruitBannerInfo($where);
        if($list){
            $images = explode(';',$list['images']);
            $list['img'] = $images;
        }
        return $list;
    }

    /**
     * 展示
     */
    public function getRecruitBannerDis($id, $type){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitBanner())->getRecruitBannerDis($where, $type);
        return $list;
    }

    /**
     * 排序
     */
    public function getRecruitBannerSort($id, $sort){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        $data = ['sort'=>$sort];
        $list = (new NewRecruitBanner())->getRecruitBannerSort($where, $data);
        return $list;
    }

    /**
     * 移除
     */
    public function getRecruitBannerDel($id){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitBanner())->getRecruitBannerDel($where);
        return $list;
    }

    public function getBanners($limit = 5){
        $where = [
            ['images', '<>', ''],
            ['is_dis', '=', '1'],
            ['status', '=', '0'],
        ];
        $order = 'sort desc';
        $list = (new NewRecruitBanner())->getSome($where, 'id,name,images, links', $order);
        if($list){
            $list = $list->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['images'] = replace_file_domain($value['images']);
            }
            return $list;
        }
        else{
            return [];
        }
    }

    /**
     * 单条
     */
    public function getRecruitBannerWhere($where){
        $list = (new NewRecruitBanner())->where($where)->count();
        return $list;
    }
}