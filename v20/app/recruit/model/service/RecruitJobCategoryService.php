<?php


namespace app\recruit\model\service;


use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitJobCategory;

class RecruitJobCategoryService
{
    /**
     * 结婚攻略列表
     */
    public function categoryList()
    {
        $where = [['cat_fid', '=', 0], ['is_del', '=', 0]];
        $list = (new NewRecruitJobCategory())->getSome($where, true, 'sort desc,cat_id desc')->toArray();
        // 返回前端需要格式
        $fomartList = [];
        foreach ($list as $_sort) {
            // 商品数量
            $where = [
                ['cat_fid', '=', $_sort['cat_id']]
            ];
            $childCount = (new NewRecruitJobCategory())->getCount($where);
            $temp = [
                'title' => $_sort['cat_title'],//分类名
                'id' => $_sort['cat_id'],//分类id
                'fid' => 0,//父id
                /* 'goods_count' => $childCount,//分类下商品总数*/
                'children' => [],//子分类（餐饮只有一级分类）
            ];
            $fomartList[] = $temp;
        }
        return $fomartList;
    }

    /**
     * 子分类列表
     */
    public function childList($param, $field = true)
    {
        $where = [['cat_fid', '=', $param['cat_fid']], ['is_del', '=', 0]];
        if(!empty($param['cat_title'])){
           array_push($where,['cat_title','like','%'.$param['cat_title'].'%']);
        }
        $list['list'] = (new NewRecruitJobCategory())->getSome($where, $field, 'sort desc,cat_id desc',($param['page']-1)*$param['pageSize'],$param['pageSize'])->toArray();
        if(!empty($list['list'])){
            foreach ($list['list'] as $k=>$v){
                $where1=[['cat_fid', '=', $v['cat_id']], ['is_del', '=', 0]];
                $count=(new NewRecruitJobCategory())->getCount($where1);
                if($count){
                    $list['list'][$k]['child_nums']=$count;
                }else{
                    $list['list'][$k]['child_nums']=0;
                }
            }
        }
        $list['count'] = (new NewRecruitJobCategory())->getCount($where);
        return $list;
    }

    /**
     * 获取子分类
     */
    public function getChildCatList($param)
    {
        $where = [['cat_fid', '=', $param['cat_fid']], ['is_del', '=', 0]];
        $list = (new NewRecruitJobCategory())->getSome($where, 'cat_id,cat_title', 'sort desc,cat_id desc')->toArray();
        return $list;
    }

    public function updateChildCategory($param){
        $where = [['cat_fid', '=', $param['cat_fid']]];
        $data['is_del'] = 1;
        $data['last_time'] =time();
        $list=(new NewRecruitJobCategory())->getSome($where)->toArray();
        if(!empty($list)){
            $ret = (new NewRecruitJobCategory())->updateThis($where, $data);
            if($ret!==false){
                if(!empty($param['cat_arr'])){
                    foreach ($param['cat_arr'] as $k=>$v){
                        if(empty($v['cat_id'])){
                            if(!empty($v['cat_title'])){
                                $data['cat_title'] = $v['cat_title'];
                                $data['cat_fid'] = $param['cat_fid'];
                                $data['add_time'] =time();
                                $data['is_del'] = 0;
                                $data['level'] = 3;
                                (new NewRecruitJobCategory())->add($data);
                            }
                        }else{
                            $where = [['cat_id', '=', $v['cat_id']]];
                            $data['is_del'] = 0;
                            $data['cat_title'] = $v['cat_title'];
                            $data['last_time'] =time();
                            (new NewRecruitJobCategory())->updateThis($where, $data);
                        }
                    }
                }
                return true;
            }else{
                return false;
            }
        }else{
            foreach ($param['cat_arr'] as $k=>$v){
                if(empty($v['cat_id'])){
                    $data['cat_title'] = $v['cat_title'];
                    $data['cat_fid'] = $param['cat_fid'];
                    $data['add_time'] =time();
                    $data['is_del'] = 0;
                    $data['level'] = 3;
                    (new NewRecruitJobCategory())->add($data);
                }else{
                    $where = [['cat_id', '=', $v['cat_id']]];
                    $data['is_del'] = 0;
                    $data['cat_title'] = $v['cat_title'];
                    $data['last_time'] =time();
                    (new NewRecruitJobCategory())->updateThis($where, $data);
                }
            }
            return true;
        }
    }
    /**
     *获取分类
     */
    public function getCategory($param){
        $where = [['cat_id', '<>', $param['cat_id']], ['is_del', '=', 0], ['cat_fid', '=', 0]];
        $list['list'] = (new NewRecruitJobCategory())->getSome($where, true, 'sort desc,cat_id desc')->toArray();
        return $list;
    }

    /**
     * 重新绑定分类
     */
    public function byOtherCategory($param)
    {
        $where = [['cat_id', 'in', $param['ids']]];
        $data['cat_fid'] = $param['cat_id'];
        $data['last_time'] =time();
        $ret = (new NewRecruitJobCategory())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 分类排序
     */
    public function changeSort($list)
    {
        $sort = 0;
        $sortList = array_reverse($list);
        foreach ($sortList as $_sort) {
            $sort += 10;
            // 条件
            $where = [
                'cat_id' => $_sort['id']
            ];
            // 更新排序值
            $data = [
                'sort' => $sort
            ];
            $res = (new NewRecruitJobCategory())->updateThis($where, $data);
        }

        return true;
    }

    /**
     *  子分类拖拽排序
     */
    public function childChangeSort($param)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $data['sort'] = $param['sort'];
        $data['last_time'] =time();
        $ret = (new NewRecruitJobCategory())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *新增分类
     */
    public function addCategory($param)
    {
        $ret = (new NewRecruitJobCategory())->add($param);
        return $ret;
    }

    /**
     *  更新分类
     */
    public function updateCategory($param)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $param['last_time'] =time();
        $ret = (new NewRecruitJobCategory())->updateThis($where, $param);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *删除分类
     */
    public function delCategory($param)
    {
        $where = [['cat_id', 'in', $param['cat_id']],['is_del', '=', 0]];
        $list = (new NewRecruitJobCategory())->getOne($where)->toArray();
        $data['is_del'] = 1;
        $data['last_time'] =time();
        if (!empty($list)) {
            if ($list['cat_fid'] == 0) {
                $where1 = [['cat_fid', '=', $list['cat_id']]];
                (new NewRecruitJobCategory())->updateThis($where1, $data);

                $list1=$this->getChildList($where1);
                foreach ($list1 as $key=>$val){
                    $where3=[['cat_fid','=',$val['cat_id']]];
                    $list2=$this->getChildList($where3);
                    if(!empty($list2)){
                            (new NewRecruitJobCategory())->updateThis($where3, $data);
                    }
                }
            }else{
                $where2 = [['cat_id', '=', $list['cat_id']]];
                (new NewRecruitJobCategory())->updateThis($where2, $data);
                $where1 = [['cat_fid', '=', $list['cat_id']]];
                $list1=$this->getChildList($where1);
                if(!empty($list1)){
                   (new NewRecruitJobCategory())->updateThis($where1, $data);
                }
            }
        }
        $ret = (new NewRecruitJobCategory())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *删除分类
     */
    public function delCategorys($param)
    {
        $where = [['cat_id', 'in', $param['cat_id']],['is_del', '=', 0]];
        $list = (new NewRecruitJobCategory())->getSome($where)->toArray();
        $data['is_del'] = 1;
        $data['last_time'] =time();
        if (!empty($list)) {
                foreach ($list as $key=>$val){
                    $where3=[['cat_fid','=',$val['cat_id']]];
                    $list2=$this->getChildList($where3);
                    if(!empty($list2)){
                        (new NewRecruitJobCategory())->updateThis($where3, $data);
                    }
                }
        }
        $ret = (new NewRecruitJobCategory())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $where
     * 找到子分类
     */
    public function getChildList($where){
        return (new NewRecruitJobCategory())->getSome($where)->toArray();
    }
    /**
     *自增
     */
    public function setInc($param, $field)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $ret = (new NewRecruitJobCategory())->setInc($where, $field);
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 编辑
     */
    public function editCategory($param)
    {
        $where1 = [
            ['first_cate|second_cate|third_cate', '=', $param['cat_id']],
        ];
        $retJob=(new NewRecruitJob())->getOne($where1);
        if(empty($retJob)){
            $where = [['cat_id', '=', $param['cat_id']]];
            $ret = (new NewRecruitJobCategory())->getOne($where);

            if ($ret) {
                return $ret->toArray();
            } else {
                return false;
            }
        }else{
            return false;
        }
    }

    public function getCateName($id){
        if(empty($id)) return "";
        $where = [
            ['cat_id', '=', $id]
        ];
        $info = (new NewRecruitJobCategory())->getOne($where);
        if($info){
            return $info->toArray()['cat_title'];
        }
        return '';
    }

	/**
	 * 找人才职位分类列表
	 */
	public function getRecruitJobPersonnelScreen($where, $order, $fields)
	{
		$return = (new NewRecruitJobCategory())->where($where)->order($order)->field($fields)->select();
        $return = $return ? $return->toArray() : [];
        $return = $this->aryTree($return);
        $return = array_values($return);
		return $return;
	}

    /**
     * 利用递归法获取无限极类别的树状数组
     * @param array $ary 数据库读取数组
     * @param int $cat_fid 父级ID(顶级类别的cat_fid为0)
     * @param int $level 返回的树状层级
     * @param int $i 层级起始值
     * @return array 返回树状数组
     */
    function aryTree($ary = array(), $cat_fid = 0, $level = 3, $i = 1){
        $arr = array();
        foreach($ary as $rs){
            if($rs['cat_fid'] == $cat_fid){
                if($i <= $level){
                    $arr[$rs['cat_id']] = $rs;
                    $arr[$rs['cat_id']]['lists'] = [];
                }else{
                    break;
                }
                $n = $i;
                $n++;
                $lists = $this->aryTree($ary, $rs['cat_id'],  $level, $n);
                empty($lists) OR $arr[$rs['cat_id']]['lists'] = $lists;
            }else{
                continue;
            }
        }
        return $arr;
    }

    public function resumePosiontList($job){
        $where = ['cat_id'=>$job];
        $info = (new NewRecruitJobCategory())->where($where)->find();
        return $info;
    }
}