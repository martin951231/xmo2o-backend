<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitJobCollect;
use app\common\model\db\Merchant;

class JobCollectService
{
    /**
     * 职位收藏列表
     */
    public function recruitJobCollectList($uid, $order='id DESC', $fields='*', $page = 0, $pageSize = 20){
        $where = [['g.uid','=',$uid],['g.is_del','=',0]];
        $fields='g.*, a.job_name, a.wages_start, a.wages_end, a.job_age, a.education, a.area_id, a.status,a.is_del as job_del, b.area_name, c.name, c.images, c.mer_id';
        $list = (new NewRecruitJobCollect())->recruitJobCollectList($where, $order, $fields,$page,$pageSize);
        foreach($list as $k=>$v){
            // 上线下线
            if($v['job_del'] ==1){
                $list[$k]['status'] = 0;
                $v['status'] = 0;
            }
            if($v['status'] == 0){
                $list[$k]['status_txt'] = '下线';
            }else{
                $list[$k]['status_txt'] = '上线';
            }
            // 工作经验
            if($v['job_age'] == -1){
                $list[$k]['job_age'] = '应届生';
            }elseif($v['job_age'] == 0){
                $list[$k]['job_age'] = '不限';
            }else{
                $list[$k]['job_age'] = $v['job_age'].'年';
            }
            // 学历
            if($v['education'] == 0){
                $list[$k]['education'] = '不限学历';
            }elseif($v['education'] == 1){
                $list[$k]['education'] = '初中';
            }elseif($v['education'] == 2){
                $list[$k]['education'] = '高中';
            }elseif($v['education'] == 3){
                $list[$k]['education'] = '中技';
            }elseif($v['education'] == 4){
                $list[$k]['education'] = '中专';
            }elseif($v['education'] == 5){
                $list[$k]['education'] = '大专';
            }elseif($v['education'] == 6){
                $list[$k]['education'] = '本科';
            }elseif($v['education'] == 7){
                $list[$k]['education'] = '硕士';
            }elseif($v['education'] == 8){
                $list[$k]['education'] = '博士';
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