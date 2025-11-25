<?php
/**
 * 团购分类
 * Author: 衡婷妹
 * Date Time: 2020/11/16 16:44
 */

namespace app\group\model\service;

use app\group\model\db\Group;
use app\group\model\db\GroupCategory;

class GroupCategoryService
{
    public $groupCategoryModel = null;

    public function __construct()
    {
        $this->groupCategoryModel = new GroupCategory();
    }

    /**
     * 获得团购分类列表
     * @param $data array 数据
     * @return array
     */
    public function getGrouptCategorylist($param = [])
    {
        $page = $param['page'] ?? 0;//页码
        $cat_id = $param['cat_id'] ?? 0;//0：父分类 其它：子分类

        $start = 0;
        $pageSize = 0;
        if ($page) {
            $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页显示数量
            $start = ($page - 1) * $pageSize;
        }
        $where = [
            ['cat_fid', '=', $cat_id],
            ['cat_id', '>', '0'],
        ];
        // 分类列表
        $order = [
            'cat_sort' => 'DESC',
            'cat_id' => 'ASC',
        ];
        $field = 'cat_id,cat_fid,cat_name,cat_url,cat_sort,cat_status';
        $list = $this->getSome($where, $field, $order, $start, $pageSize);
        $count = $this->groupCategoryModel->getCount($where);
        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**
     * 添加团购分类列表
     * @param $data array 数据
     * @return array
     */
    public function addGroupCategory($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $cat_fid = $param['cat_fid'] ?? 0;
        $cat_name = $param['cat_name'] ?? '';
        $cat_des = $param['cat_des'] ?? '';
        $cat_url = $param['cat_url'] ?? '';
        $cat_pic = $param['cat_pic'] ?? '';
        $cat_ad_pic = $param['cat_ad_pic'] ?? '';
        $cat_sort = $param['cat_sort'] ?? 0;
        $is_hot = $param['is_hot'] ?? 0;
        $cat_status = $param['cat_status'] ?? 1;
        $is_hotel = $param['is_hotel'] ?? 0;
        $editor_num = $param['editor_num'] ?? 0;
        $editor_titles = $param['editor_title'] ?? [];


        //分类LOGO图标
        if (!empty($cat_pic)) {
            $pic = explode("/upload/", $cat_pic);
            $cat_pic = isset($pic[1]) ? '/upload/'.$pic[1] : '';
        }

        //分类广告图
        if (!empty($cat_ad_pic)) {
            $ad_pic = explode("/upload/", $cat_ad_pic);
            $cat_ad_pic = isset($ad_pic[1]) ? '/upload/'.$ad_pic[1] : '';
        }

        $data = [];
        $data['cat_fid'] = $cat_fid;
        $data['cat_name'] = $cat_name;
        $data['cat_des'] = $cat_des;
        $data['cat_url'] = $cat_url;
        $data['cat_pic'] = $cat_pic;
        $data['cat_ad_pic'] = $cat_ad_pic;
        $data['cat_sort'] = $cat_sort;
        $data['is_hot'] = $is_hot;
        $data['cat_status'] = $cat_status;
        $data['is_hotel'] = $is_hotel;
        if(!isset($param['cue_field'])){
            $data['cue_field'] ='';
        }

        // 处理下编辑相关数量问题
        $group_content_switch = cfg('group_content_switch');
        if ($group_content_switch == 1 && $editor_num > 0) {
            $editor_title = array();
            foreach ($editor_titles as $val) {
                if ($val) {
                    $editor_title[] = $val;
                }
            }
            if ($editor_num != count($editor_title)) {
                throw new \think\Exception(L_("需要对应填写编辑器标题！请重试~"), 1003);
            }
            $data['editor_num'] = $editor_num;
            $data['editor_title'] = serialize($editor_title);
        } else {
            $data['editor_num'] = $editor_num;
            $data['editor_title'] = '';
        }

        if ($cat_id > 0) {//编辑
            $where = ['cat_id' => $cat_id];
            $rs = $this->updateThis($where, $data);
            if ($rs!==false) {
                if($param['cat_fid']==0 && $param['cat_status']==0){
                    $where1[] = ['cat_fid' ,'=', $cat_id];
                    (new GroupCategory())->updateThis($where1, ['cat_status'=>0]);
                }
                $msg = '编辑成功';
            } else {
                $msg = '编辑失败';
            }
        } else {//新增
            if(!isset($param['bg_color'])){
                $data['bg_color'] ='';
            }
            $rs = $this->add($data);
            if ($rs) {
                $msg = '添加成功';
            } else {
                $msg = '添加失败';
            }
        }
        return $msg;
    }


    public function getGroupCategoryInfo($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $detail = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($detail)) {
            throw new \think\Exception(L_("分类信息不存在"), 1003);
        }
        $detail['cat_pic'] = empty($detail['cat_pic']) ? '' : file_domain() . $detail['cat_pic'];
        $detail['cat_ad_pic'] = empty($detail['cat_ad_pic']) ? '' : file_domain() . $detail['cat_ad_pic'];
        if ($detail['editor_title']) {
            $detail['editor_title'] = unserialize($detail['editor_title']);
        }
        $detail['group_content_switch'] = cfg('group_content_switch');
        $returnArr['detail'] = $detail;
        return $returnArr;
    }

    /**
     * 删除团购分类列表
     * @param $data array 数据
     * @return array
     */
    public function delGroupCategory($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $count = $this->groupCategoryModel->getCount($where = ['cat_fid' => $cat_id]);
        if ($count > 0) {
            throw new \think\Exception(L_("当前分类有子分类"), 1003);
        }
        $where=[['cat_id|cat_fid','=',$cat_id]];
        $list=(new Group())->getSome($where)->toArray();
        if(!empty($list)){
            throw new \think\Exception(L_("当前分类有团购商品"), 1003);
        }
        $this->groupCategoryModel->del($where = ['cat_id' => $cat_id]);
        return true;
    }

    /**
     * 团购分类购买须知填写项列表
     * @param $data array 数据
     * @return array
     */
    public function getGroupCategoryCueList($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;

        $res = $this->getOne($where = ['cat_id' => $cat_id]);
        $cue_field = [];
        if (!empty($res['cue_field'])) {
            $cue_field = unserialize($res['cue_field']);
        }
        foreach ($cue_field as $key => &$value)
        {
            $value['id'] = $key;
        }

        $returnArr['list'] = $cue_field;
        return $returnArr;
    }

    /**团购分类购买须知填写项编辑
     * @param array $param
     */
    public function editGroupCategoryCue($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $id = $param['id'] ?? '';
        $name = $param['name'] ?? 0;
        $type = $param['type'] ?? 0;//0：单行文本 1：多行文本
        $sort = $param['sort'] ?? 0;

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }

        if ($now_category['cat_fid'] != 0) {
            throw new \think\Exception(L_("该分类不是主分类，无法使用商品字段功能！"), 1003);
        }
        $cue_field = [];
        if (!empty($now_category['cue_field'])) {
            $cue_field = unserialize($now_category['cue_field']);
        }
        if (isset($cue_field[$id]) && !empty($cue_field[$id])) {//编辑
            if (!empty($cue_field)) {
                foreach($cue_field as $key=>$value){
                    if($value['name'] == $name && $key != $id){
                        throw new \think\Exception(L_("该填写项已经添加，请勿重复添加！"), 1003);
                    }
                }
            }

            $cue['name'] = $name;
            $cue['type'] = $type;
            $cue['sort'] = strval($sort);
            $cue_field[$id] = $cue;
        } else {//添加
            if (!empty($cue_field)) {
                foreach($cue_field as $key=>$value){
                    if($value['name'] == $name){
                        throw new \think\Exception(L_("该填写项已经添加，请勿重复添加！"), 1003);
                    }
                }
            }
            $cue = [];
            $cue['name'] = $name;
            $cue['type'] = $type;
            $cue['sort'] = $sort;
            $cue_field[] = $cue;
        }

        foreach ($cue_field as $val){
            $order[] =  $val['sort'];
        }
        array_multisort($order, SORT_DESC, $cue_field);
        $new_cue_field = serialize($cue_field);
        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['cue_field' => $new_cue_field]);
        return $res;
    }

    /**团购分类购买须知填写项编辑
     * @param array $param
     */
    public function delGroupCategoryCue($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $name = $param['name'] ?? '';

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }

        $cue_field = [];
        if (!empty($now_category['cue_field'])) {
            $cue_field = unserialize($now_category['cue_field']);
        }
        $new_cue_field = array();
        foreach($cue_field as $key=>$value){
            if($value['name'] != $name){
                array_push($new_cue_field,$value);
            }
        }
        if (!empty($new_cue_field)) {
            foreach ($new_cue_field as $val){
                $order[] =  $val['sort'];
            }
            array_multisort($order, SORT_DESC, $new_cue_field);
            $new_cue_field = serialize($new_cue_field);
        } else {
            $new_cue_field = '';
        }

        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['cue_field' => $new_cue_field]);
        return $res;
    }

    /**
     * 定制-管理商品属性字段 列表
     * @param $data array 数据
     * @return array
     */
    public function getCatFieldList($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if(empty($now_category)){
            throw new \think\Exception(L_("没有找到该分类信息"), 1003);
        }
        if(!empty($now_category['cat_fid'])){
            throw new \think\Exception(L_("该分类不是主分类，无法使用商品字段功能！"), 1003);
        }
        if(!empty($now_category['cat_field'])){
            $now_category['cat_field'] = unserialize($now_category['cat_field']);
            foreach($now_category['cat_field'] as $key=>$value){
                if(isset($value['use_field']) && $value['use_field'] == 'area'){
                    $now_category['cat_field'][$key]['name'] = '区域(内置)';
                    $now_category['cat_field'][$key]['url'] = 'area';
                    $now_category['cat_field'][$key]['type'] = '0';
                }
                if(isset($value['use_field']) &&  $value['use_field'] == 'price'){
                    $now_category['cat_field'][$key]['name'] = '价格(内置)';
                    $now_category['cat_field'][$key]['type'] = '0';
                }
            }
        }

        $returnArr['list'] = empty($now_category['cat_field']) ? [] : $now_category['cat_field'];
        return $returnArr;
    }

    /**定制-管理商品属性字段 添加
     * @param array $param
     */
    public function addCatField($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $use_field = $param['use_field'] ?? '';
        $sort = $param['sort'] ?? '';
        $name = $param['name'] ?? '';
        $url = $param['url'] ?? '';
        $type = $param['type'] ?? 0;
        $value = $param['value'] ?? '';
        $is_show = $param['is_show'] ?? '';

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }

        if ($now_category['cat_fid'] != 0) {
            throw new \think\Exception(L_("该分类不是主分类，无法使用商品字段功能！"), 1003);
        }

        if(!empty($now_category['cat_field'])){
            $cat_field = unserialize($now_category['cat_field']);
            foreach($cat_field as $key=>$val){
                if(isset($val['name']) && $val['name'] == $name){
                    throw new \think\Exception(L_("自定义字段名称已经添加，请勿重复添加！"), 1003);
                }

                if(isset($val['url']) && $val['url'] == $url){
                    throw new \think\Exception(L_("自定义字段短标记(url)已经添加，请勿重复添加！"), 1003);
                }

                if (isset($val['use_field']) && $val['use_field'] == $use_field) {
                    throw new \think\Exception(L_("内置字段已经添加，请勿重复添加！"), 1003);
                }
            }
        }else{
            $cat_field = array();
        }
        if(count($cat_field) >= 5){
            throw new \think\Exception(L_("添加字段失败，最多5个自定义字段！"), 1003);
        }

        if(empty($use_field)){
            $data['name'] = $name;
            $data['url'] = $url;
            $data['value'] = explode(PHP_EOL,$value);
            $data['type'] = $type;
            $data['is_show'] = $is_show;
        }else{
            $data['use_field'] = $use_field;
            $data['sort'] = $sort;
            $data['is_show'] = $is_show;
        }

        array_push($cat_field,$data);
        $new_cat_field = serialize($cat_field);
        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['cat_field' => $new_cat_field]);
        return $res;
    }

    /**定制-管理商品属性字段 前端显示隐藏
     * @param array $param
     */
    public function catFieldShow($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $name = $param['name'] ?? '';
        $is_show = $param['is_show'] ?? 0;
        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }

        if ($now_category['cat_fid'] != 0) {
            throw new \think\Exception(L_("该分类不是主分类，无法使用商品字段功能！"), 1003);
        }

        if(!empty($now_category['cat_field'])){
            $cat_field = unserialize($now_category['cat_field']);
            $new_cat_field = array();

            foreach($cat_field as $key=>$value){
                if(isset($value['name']) && $value['name'] == $name){
                    $value['is_show'] = $is_show;
                }
                if (isset($value['use_field']) && $name == '区域(内置)' && $value['use_field'] == 'area') {
                    $value['is_show'] = $is_show;
                }
                if (isset($value['use_field']) && $name == '价格(内置)' && $value['use_field'] == 'price') {
                    $value['is_show'] = $is_show;
                }
                array_push($new_cat_field,$value);
            }
        }else{
            throw new \think\Exception(L_("此填写项不存在"), 1003);
        }
        $new_cat_field = serialize($new_cat_field);
        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['cat_field' => $new_cat_field]);
        return $res;
    }


    /**
     * 定制-自定义填写选项列表
     * @param $data array 数据
     * @return array
     */
    public function getWriteFieldList($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if(empty($now_category)){
            throw new \think\Exception(L_("没有找到该分类信息"), 1003);
        }
        if(!empty($now_category['cat_fid'])){
            throw new \think\Exception(L_("该分类是子分类，无法使用自定义填写字段功能！！"), 1003);
        }
        if(!empty($now_category['write_field'])){
            $now_category['write_field'] = unserialize($now_category['write_field']);
            if(!empty($now_category['write_field'])){
                foreach ($now_category['write_field'] as $val){
                    $sort[] = $val['sort'];
                }
                array_multisort($sort, SORT_DESC, $now_category['write_field']);
            }
        }

        $returnArr['list'] = empty($now_category['write_field']) ? [] : $now_category['write_field'];
        return $returnArr;
    }

    /**
     * 定制-自定义填写选项添加字段 操作
     */
    public function addWriteField($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $name = $param['name'] ?? '';
        $sort = $param['sort'] ?? 0;
        $iswrite = $param['iswrite'] ?? 1;
        $type = $param['type'] ?? 0;
        $use_field = $param['use_field'] ?? '';

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if(empty($now_category)){
            throw new \think\Exception(L_("没有找到该分类信息"), 1003);
        }
        if(!empty($now_category['cat_fid'])){
            throw new \think\Exception(L_("该分类是子分类，无法使用自定义填写字段功能！！"), 1003);
        }

        if(!empty($now_category['write_field'])){
            $write_field = unserialize($now_category['write_field']);
            foreach($write_field as $key=>$val){
                if(isset($val['name']) && $val['name'] == $name){
                    throw new \think\Exception(L_("该填写项已经添加，请勿重复添加！"), 1003);
                }
            }
        }else{
            $write_field = array();
        }

        if($use_field){
            $data['use_field'] = explode("\n", $use_field);
        }

        $data['name'] = $name;
        $data['sort'] = strval($sort);
        $data['iswrite'] = $iswrite;
        $data['type'] = $type;
        array_push($write_field,$data);
        foreach ($write_field as $val){
            $order[] =  $val['sort'];
        }
        array_multisort($order, SORT_DESC, $write_field);
        $new_write_field = serialize($write_field);
        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['write_field' => $new_write_field]);
        return $res;
    }

    /**
     * 定制-自定义填写选项添加字段 删除
     */
    public function delWriteField($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $name = $param['name'] ?? '';

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }

        $write_field = [];
        if (!empty($now_category['write_field'])) {
            $write_field = unserialize($now_category['write_field']);
        }
        $new_write_field = array();
        foreach($write_field as $key=>$value){
            if($value['name'] != $name){
                array_push($new_write_field,$value);
            }
        }
        if (!empty($new_write_field)) {
            foreach ($new_write_field as $val){
                $order[] =  $val['sort'];
            }
            array_multisort($order, SORT_DESC, $new_write_field);
            $new_write_field = serialize($new_write_field);
        } else {
            $new_write_field = '';
        }

        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['write_field' => $new_write_field]);
        return $res;
    }

    /**
     * 保存排序
     */
    public function saveSort($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $cat_sort = $param['cat_sort'] ?? 0;

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }
        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['cat_sort' => $cat_sort]);
        return $res;
    }

    /**
     * 保存团购分类背景色
     */
    public function updateGroupCategoryBgColor($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $bg_color = $param['bg_color'] ?? '';

        $now_category = $this->getOne($where = ['cat_id' => $cat_id]);
        if (empty($now_category)) {
            throw new \think\Exception(L_("当前分类不存在"), 1003);
        }
        $res = $this->updateThis($where = ['cat_id' => $cat_id], $data = ['bg_color' => $bg_color]);
        return $res;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function getGroupFirstCategorylist($param=[]){
        $where = [
            ['cat_fid','=','0'],
            ['cat_id','>','0'],
			['cat_status', '=', '1']
        ];
        $list = $this->getSome($where);

        return $list;
    }

    /**
     * 获得树形数据
     * @param $data array 数据
     * @return array
     */
    public function getCategoryTree($param=[]){
        $cat_id = $param['cat_id'] ?? 0;
        $where = [['cat_status','<>','4'],['cat_status','<>','0']];
        $order = [
            'cat_id' => 'ASC'
        ];
        $list = $this->getSome($where,true,$order);

        $returnArr = [];
        foreach ($list as $key => $value){
            if ($cat_id > 0) {
                if ($value['cat_fid'] == 0 && $value['cat_id'] != $cat_id) {
                    continue;
                }

                if ($value['cat_fid'] != 0 && $value['cat_fid'] != $cat_id) {
                    continue;
                }
            }
            if($value['cat_fid'] > 0){
                if(isset($returnArr[$value['cat_fid']])){
                    $temp = [
                        'sort_name' => $value['cat_name'],
                        'sort_id' => $value['cat_id'],
                        'key' => $value['cat_fid'] . '-' . $value['cat_id'],
                    ];
                    $returnArr[$value['cat_fid']]['children'][] = $temp;
                }
            }else{
                $returnArr[$value['cat_id']]['sort_name'] = $value['cat_name'];
                $returnArr[$value['cat_id']]['sort_id'] = $value['cat_id'];
                $returnArr[$value['cat_id']]['key'] = $value['cat_id'];
            }
        }
        $arr = [
            [
                'key' => 0,
                'sort_id' => 0,
                'sort_name' => '请选择',
                'children' => [
                    [
                        'key' => '0-0',
                        'sort_id' => 0,
                        'sort_name' => '请选择',
                    ]
                ]
            ]
        ];
        $returnArr = array_values(array_merge($arr, $returnArr));
        return $returnArr;
    }

    /*得到列表所有分类*/
    public function getAllCategory($where=[]){
        // 获取所有显示的分类
        if (empty($where)) {
            $where = [['cat_status', '<>', '4']];
        }

        $order = 'cat_id asc,cat_sort desc';
        $tmpGroupCategory = $this->getSome($where, true, $order);
        $groupCategory = array();
        $tmpCategory = array();//顶级
        foreach($tmpGroupCategory as $key=>$value){
            if(empty($value['cat_fid'])){
                $tmpCategory[$value['cat_id']] = $key;
                $value['cat_count'] = 0;
                $groupCategory[$key] = $value;
                unset($tmpGroupCategory[$key]);
            }
        }

        foreach($tmpGroupCategory as $key=>$value){
            if(isset($tmpCategory[$value['cat_fid']])){
                $groupCategory[$tmpCategory[$value['cat_fid']]]['cat_count'] += 1;
                $groupCategory[$tmpCategory[$value['cat_fid']]]['category_list'][$key] = $value;
            }
        }

        foreach($groupCategory as $key=>$value){
            if(empty($value['cat_id'])){
                unset($groupCategory[$key]);
            }
            if(isset($groupCategory[$key]['category_list'])){
                $groupCategory[$key]['category_list'] = array_values($value['category_list']);
            }else{
                $groupCategory[$key]['category_list'] = [];
            }
        }

        $groupCategory = array_values($groupCategory);
        return $groupCategory;

    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCategoryModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupCategoryModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->groupCategoryModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupCategoryModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where,$field = true,$order=true,$page=0,$limit=0){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->groupCategoryModel->getSome($where,$field,$order,$page,$limit);
        } catch (\Exception $e) {
            return false;
        }

        return $result->toArray();
    }

    /**
     * 获取店铺分类
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGroupCategoryList($param){
        // 获取所有显示的分类
        $where = [['cat_status','=','1']];

        $order = 'cat_sort desc,cat_id asc';
        $list = (new GroupCategory())->field('cat_id,cat_fid,cat_name')->where($where)->order($order)->select();

        $list = $list?$list->toArray():[];
        $parents = $children = [];
        $list_arr[] = [
            'cat_id' => 0,
            'cat_name' => '全部',
            'key' => 0,
            'level' => 1,
            'title' => '全部',
            'value' => 0,
        ];
        foreach ($list as $item){
            if($item['cat_fid']){
                $item['key'] = $item['cat_fid'].'-'.$item['cat_id'];
                $item['level'] = 2;
                $item['title'] = $item['cat_name'];
                $item['value'] = $item['cat_id'];
                $children[$item['cat_fid']][] = $item;
            }else{
                $item['key'] = $item['cat_id'];
                $item['level'] = 1;
                $item['title'] = $item['cat_name'];
                $item['value'] = $item['cat_id'];
                $item['disabled'] = true;
                $parents[] = $item;
            }
        }
        foreach ($parents as $k=>$v){
            $v['children'] = [];
            if(isset($children[$v['cat_id']])){
                $v['children'] = $children[$v['cat_id']];
            }else{
                $v['disabled'] = false;
            }
            $list_arr[] = $v;
        }
        $data['list'] = $list_arr;

        return $data;
    }
}