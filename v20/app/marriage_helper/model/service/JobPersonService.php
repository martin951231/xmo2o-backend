<?php
namespace app\marriage_helper\model\service;

use app\marriage_helper\model\db\JobPerson;
use app\marriage_helper\model\db\MarriageCategory;
use app\merchant\model\db\MerchantPosition;

class JobPersonService
{
    /**
     * 高手列表
     * @return \json
     */
    public function getPersonList($category, $position, $type_id, $name, $page, $pageSize)
    {
        $where = [];
        $where[] = ['a.is_black','=',0];
        $where[] = ['a.status','=',2];
        $where[] = ['is_del','=',0];
        // if($category > 0){
        //     $where[] = ['a.is_black','=',0];
        // }
        if($position > 0){
            $where[] = ['a.job_id','=',$position];

        }
        if(!empty($name)){
            if($type_id == 0){
                $where[] = ['d.name','like', '%'.$name.'%'];
            }elseif($type_id == 1){
                $where[] = ['e.name','like','%'.$name.'%'];
            }else{
                $where[] = ['c.phone','like','%'.$name.'%'];
            }
        }

        $order = 'a.id DESC';
        $list = (new JobPerson())->getPersonList($where, $order, $page, $pageSize);

        $cate = (new MarriageCategory())->getCategoryLists(['is_del'=>0,'status'=>0],'sort DESC, cat_id DESC','cat_id,cat_name');
        $cateAll[] = ['cat_id'=>0, 'cat_name'=>'全部'];
        $categoryList = array_merge($cateAll, $cate);
        $list1['categoryList'] = $categoryList;
        $posi = (new MerchantPosition())->getPosition(['status'=>0], 'sort DESC, id DESC', 'id,name');
        $posiAll[] = ['id'=>0, 'name'=>'全部'];
        $positionList = array_merge($posiAll, $posi);
        $list1['positionList'] = $positionList;
        if (!empty($list)) {
            //获取总数
            foreach($list as $k=>$v){
                $list[$k]['job_time'] = $this->format_date($v['job_time']);
                if($v['headimg']){
                    $list[$k]['headimg'] = replace_file_domain($v['headimg']);
                }
            }
            $list1['list'] = $list;
            $list1['count'] = (new JobPerson())->getPersonCount($where);
            return $list1;
        } else {
            $list1['list'] = [];
            $list1['count'] = 0;
            return $list1;
        }
    }

    /**
     * 高手详情
     * @return \json
     */
    public function getPersonView($id)
    {
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }
        $where = ['a.id' => $id];
        $arr = (new JobPerson())->getPersonView($where);
        if($arr['job_time']){
            $arr['job_time'] = $this->format_date($arr['job_time']);
        }
        if($arr['headimg']){
            $arr['headimg'] = replace_file_domain($arr['headimg']);
        }
        return $arr;
    }

    /**
     * 高手拉黑
     * @return \json
     */
    public function getPersonDel($id)
    {
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }
        $where = ['id'=>$id];
        $result = (new JobPerson())->getPersonDel($where);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }

    // 年限计算
    function format_date($time){
        $t=time()-$time;
        $f=array(
            '31536000'=>'年',
            '2592000'=>'个月',
            '604800'=>'星期',
            '86400'=>'天',
            '3600'=>'小时',
            '60'=>'分钟',
            '1'=>'秒'
        );
        foreach ($f as $k=>$v)    {
            if (0 !=$c=floor($t/(int)$k)) {
                return $c.$v;
            }
        }
    }
}