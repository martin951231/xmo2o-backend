<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitCompanyUserCollect;
use app\common\model\db\Merchant;

class CompanyUserCollectService
{
    /**
     * 公司收藏列表
     */
    public function companyUserCollectList($uid, $order='id DESC', $fields='*', $page = 0, $pageSize = 20){
        $where = [['g.uid','=',$uid]];
        $fields = 'g.*, a.*, b.name as industry_name1, c.name as industry_name2';
        $list = (new NewRecruitCompanyUserCollect())->companyUserCollectList($where, $order, $fields, $page, $pageSize);
        foreach($list as $k=>$v){
            // 公司规模
            if($v['people_scale'] == 1){
                $list[$k]['people_scale'] = '<50人';
            }elseif($v['people_scale'] == 2){
                $list[$k]['people_scale'] = '50~100人';
            }elseif($v['people_scale'] == 3){
                $list[$k]['people_scale'] = '101-200人';
            }elseif($v['people_scale'] == 4){
                $list[$k]['people_scale'] = '201~500人';
            }elseif($v['people_scale'] == 5){
                $list[$k]['people_scale'] = '500人~1000人以上';
            }
            // 公司性质
            if($v['nature'] == 1){
                $list[$k]['nature'] = '民营';
            }elseif($v['nature'] == 2){
                $list[$k]['nature'] = '国企';
            }elseif($v['nature'] == 3){
                $list[$k]['nature'] = '外企';
            }elseif($v['nature'] == 4){
                $list[$k]['nature'] = '合资';
            }elseif($v['nature'] == 5){
                $list[$k]['nature'] = '股份制企业';
            }elseif($v['nature'] == 6){
                $list[$k]['nature'] = '事业单位';
            }elseif($v['nature'] == 7){
                $list[$k]['nature'] = '个体';
            }elseif($v['nature'] == 8){
                $list[$k]['nature'] = '其他';
            }
            // 图片
            $mer_find = (new Merchant())->where(['mer_id'=>$v['mer_id']])->field('logo')->find();
            if(empty($mer_find)){
                return api_output_error(1003, '没有找到该公司!');
            }
            $img = $mer_find['logo'] ? explode(';', $mer_find['logo']) : [];
            $list[$k]['images'] = replace_file_domain($img[0]);
        }
        return $list;
    }
}