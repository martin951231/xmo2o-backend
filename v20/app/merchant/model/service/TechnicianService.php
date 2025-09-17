<?php
namespace app\merchant\model\service;

use app\merchant\model\db\Technician;
use app\marriage_helper\model\db\MarriageCategory;
use app\merchant\model\db\MerchantPosition;

class TechnicianService
{
    /**
     * 店铺技师认证列表
     * @return \json
     */
    public function getTechnicianList($type, $category, $position, $type_id, $name, $page, $pageSize)
    {
        $where = [];
        $where[] = ['a.is_del','=',0];
        if($type == 1){
            $where[] = ['a.is_black','=',0];
        }elseif($type == 2){
            $where[] = ['a.is_black','=',1];
        }
        // if($category > 0){
        //     $where[] = ['a.is_black','=',0];
        // }
        if($position > 0){
            $where[] = ['a.job_id','=',$position];

        }
        if($name != ''){
            if($type_id == 0){
                $where[] = ['d.name','like', '%'.$name.'%'];
            }elseif($type_id == 1){
                $where[] = ['e.name','like','%'.$name.'%'];
            }else{
                $where[] = ['c.phone','like','%'.$name.'%'];
            }
        }

        $order = 'a.id DESC';
        $list = (new Technician())->getTechnicianList($where, $order, $page, $pageSize);

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
                if($v['auth_time']){
                    $list[$k]['auth_time'] = date('Y-m-d H:i:s', $v['auth_time']);
                }else{
                    $list[$k]['auth_time'] = '-';
                }
                
            }
            $list1['list'] = $list;
            $list1['count'] = (new Technician())->getTechnicianCount($where);
            return $list1;
        } else {
            $list1['list'] = [];
            $list1['count'] = 0;
            return $list1;
        }
    }

    /**
     * 店铺技师认证详情
     * @return \json
     */
    public function getTechnicianView($id)
    {
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }
        $where = ['a.id' => $id];
        $arr = (new Technician())->getTechnicianView($where);
        if($arr['job_time']){
            $arr['job_time'] = $this->format_date($arr['job_time']);
        }
        if($arr['headimg']){
            $arr['headimg'] = replace_file_domain($arr['headimg']);
        }
        return $arr;
    }

    /**
     * 店铺技师认证审核
     * @return \json
     */
    public function getTechnicianExamine($id, $type)
    {
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }
        $where = ['id'=>$id];
        if($type){  // 给予通过
            $data = ['status'=>2];
        }else{      // 不予通过
            $data = ['status'=>3];
        }
        $result = (new Technician())->getTechnicianExamine($where, $data);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }

    /**
     * 店铺技师认证拉黑
     * @return \json
     */
    public function getTechnicianDel($id, $type)
    {
        if (empty($id)) {
            throw new \think\Exception('ID参数缺失');
        }
        $where = ['id'=>$id];
        if($type){  // 加入黑名单
            $data = ['is_black'=>1];
        }else{      // 移出黑名单
            $data = ['is_del'=>1];
        }
        $result = (new Technician())->getTechnicianDel($where, $data);
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