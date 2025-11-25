<?php

namespace app\group\model\service;

use app\common\model\db\Config;
use app\common\model\service\GoodsScoreService;
use app\group\model\db\Group;
use app\group\model\db\GroupCategory;
use app\group\model\db\GroupRenovationCustom as GroupRenovationCustomServiceModel;
use app\group\model\db\GroupRenovationCustomStoreSort;
use app\group\model\db\GroupRenovationGoodsCustom;
use app\group\model\db\GroupRenovationGoodsCustomTop;
use app\group\model\db\GroupSpecifications;
use app\group\model\service\GroupRenovationCustomGroupSortService;
use think\facade\Db;

class GroupRenovationCustomService
{
    public $groupRenovationCustomServiceModel = null;

    public function __construct()
    {
        $this->groupRenovationCustomServiceModel = new GroupRenovationCustomServiceModel();
    }

    /**添加/编辑团购自定义活动推荐
     * @param array $param
     * @return string
     */
    public function addCustom($param = [])
    {
        if(!empty($param['group_activity_recommendations'])){
            $res = $this->addGoodsCustom($param);
            return $res;
        }
        $custom_id = $param['custom_id'] ?? 0;
        $title = $param['title'] ?? '';
        $sub_title = $param['sub_title'] ?? '';
        $cat_id = $param['cat_id'] ?? 0;
        $sort = $param['sort'] ?? 0;
        $type = $param['type'] ?? 0;

        $data = [];
        $data['title'] = $title;
        $data['sub_title'] = $sub_title;
        $data['sort'] = $sort;
        $data['title'] = $title;
        $data['type'] = $type;
        if(is_array($type)){
            $data['type'] = implode(',',$type);
        }

        if ($custom_id > 0) {
            $custom_info = $this->getOne($where = ['title' => $title, 'cat_id' => $cat_id]);
            if (!empty($custom_info) && $custom_info['custom_id'] != $custom_id) {
                throw new \think\Exception('店铺活动推荐标题已存在');
            }
            $custom_info = $this->getOne($where = ['custom_id' => $custom_id]);
            $where = ['custom_id' => $custom_id];
            $data['old_type'] = $custom_info['type'];//编辑前先保存原来的团购类型，用于店铺管理去管理，如果团购类型有编辑，则删除原来的店铺排序
            $rs = $this->updateThis($where, $data);
            if ($rs!==false) {
                $msg = '编辑成功';
            } else {
                $msg = '编辑失败';
            }
        } else {
            $count = $this->groupRenovationCustomServiceModel->getCount($where = ['cat_id' => $cat_id]);
            if ($count > 3) {
                throw new \think\Exception('店铺活动推荐最多允许添加4个');
            }
            $custom_info = $this->getOne($where = ['title' => $title, 'cat_id' => $cat_id]);
            if (!empty($custom_info)) {
                throw new \think\Exception('店铺活动推荐标题已存在');
            }
            //新增
            $data['cat_id'] = $cat_id;
            $data['create_time'] = time();
            $data['old_type'] = $data['type'];
            $rs = $this->add($data);
            if ($rs) {
                $msg = '添加成功';
            } else {
                $msg = '添加失败';
            }
            //获取新增信息id
            $custom_info = $this->getOne($data);
            $custom_id = $custom_info['custom_id'];
        }
        (new GroupRenovationCustomStoreSort())->del(['custom_id' => $custom_id]);
        (new GroupRenovationCustomStoreSortService())->addCustomStoreSort($custom_id);
        return $msg;
    }

    /**删除团购自定义活动推荐
     * @param array $param
     * @return string
     */
    public function delCustom($param = [])
    {
        if(!empty($param['group_activity_recommendations'])){
            $res = $this->delRenovationGoodsCustom($param);
            return $res;
        }
        $custom_id = $param['custom_id'] ?? 0;
        $cat_id = $param['cat_id'] ?? 0;
        $where = ['custom_id' => $custom_id];
        $this->groupRenovationCustomServiceModel->del($where);
        if ($cat_id == 0) {// 首页 删除店铺
            (new GroupRenovationCustomStoreSort())->del($where);
        } else {// 发现页 删除商品
            (new GroupRenovationCustomGroupSortService())->del($custom_id);
        }

        return true;
    }

    /**团购自定义活动推荐基本信息
     * @param array $param
     * @return string
     */
    public function getRenovationCustomInfo($param = [])
    {
        if(!empty($param['group_activity_recommendations'])){
            $res = $this->getRenovationGoodsCustomInfo($param);
            return $res;
        }
        $custom_id = $param['custom_id'] ?? 0;
        $where = ['custom_id' => $custom_id];
        $info = $this->getOne($where);
        $info['type'] = !empty($info['type']) ? explode(',',$info['type']) : [];
        $returnArr['detail'] = $info;
        return $returnArr;
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getList($param = [])
    {
        if($param['group_activity_recommendations']){
            $res = $this->getRenovationGoodsCustomList($param);
            return $res;
        }
        $page = request()->param('page', '0', 'intval');//页码

        $start = 0;
        $pageSize = 0;
        if ($page) {
            $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页显示数量
            $start = ($page - 1) * $pageSize;
        }

        $condition = [];

        // 排序
        $order = [
            'sort' => 'DESC',
        ];
        $condition[] = ['cat_id', '=', $param['cat_id']];

        // 商品列表
        $list = $this->getSome($condition, true, $order, $start, $pageSize);
        $count = $this->groupRenovationCustomServiceModel->getCount($condition);

        // 查看分类相关信息
        $catIdArr = [];
        foreach ($list as $value) {
            if ($value['type'] != 0) {
                $catIdArr[$value['type']] = $value['type'];
//                $typeArr = explode(",", $value['type']);
//                foreach ($typeArr as $item) {
//                    $catIdArr[$item] = $item;
//                }
                $typeArr = explode(",", $value['type']);
                foreach ($typeArr as $item) {
                    $catIdArr[$item] = $item;
                }
            }
        }
        $catIdArr = array_values($catIdArr);
        $catArr = [];
        if (!empty($catIdArr)) {
            $where = [
                ['cat_id', 'in', implode(',', $catIdArr)]
            ];
            $catArr = (new GroupCategoryService())->getSome($where);
            $catArr = array_column($catArr, NULL, 'cat_id');
        }

        foreach ($list as &$_group) {
            if ($_group['type'] != 0) {
//                $typeArr = explode(",", $_group['type']);
//                $category = [];
//                foreach ($typeArr as $value) {
//                    isset($catArr[$value]['cat_name']) ? array_push($category, $catArr[$value]['cat_name']) : '';
//                }
//                $_group['category'] = implode(",", $category);
                $_group['category'] = isset($catArr[$_group['type']]['cat_name']) ? $catArr[$_group['type']]['cat_name'] : '全部';
                $typeArr = explode(",", $_group['type']);
                $category = [];
                foreach ($typeArr as $value) {
                    isset($catArr[$value]['cat_name']) ? array_push($category, $catArr[$value]['cat_name']) : '';
                }
                $_group['category'] = implode(",", $category);
                //$_group['category'] = isset($catArr[$_group['type']]['cat_name']) ? $catArr[$_group['type']]['cat_name'] : '全部';
            } else {
                $_group['category'] = '全部';
            }
        }

        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupRenovationCustomServiceModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupRenovationCustomServiceModel->insertAll($data);
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
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        try {
            $result = $this->groupRenovationCustomServiceModel->where($where)->update($data);
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
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupRenovationCustomServiceModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        try {
            $result = $this->groupRenovationCustomServiceModel->getSome($where, $field, $order, $page, $limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 商品活动推荐列表
     */
    public function getRenovationGoodsCustomList($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $list = (new GroupRenovationGoodsCustom())->getLsit([],'c.*,gc.cat_name as category',$page,$pageSize);
        $list['list'] = $list['data'];
        $list['count'] = $list['total'];
        if($list){
            foreach ($list['data'] as $key=>$val){
                $list['list'][$key]['img'] = $val['img']?replace_file_domain($val['img']):'';
                $list['list'][$key]['type'] = (int)$val['type'];
                if($val['type']==0){
                    $list['list'][$key]['category'] = '全部';
                }
            }
        }
        return $list;
    }

    /**
     * 商品活动推荐删除
     * @param $params
     * @throws \think\Exception
     */
    public function delRenovationGoodsCustom($params){
        $custom_id = $params['custom_id'];
        $info = (new GroupRenovationGoodsCustom())->where(['custom_id'=>$custom_id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动推荐已删除'));
        }
        Db::startTrans();
        try {
            (new GroupRenovationGoodsCustom())->where(['custom_id'=>$custom_id])->delete();
            (new GroupRenovationGoodsCustomTop())->where(['custom_id'=>$custom_id])->delete();
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 商品活动推荐添加/编辑
     * @param $param
     * @throws \think\Exception
     */
    public function addGoodsCustom($param){
        $custom_id = $param['custom_id'] ?? 0;
        $data = [
            'title' => $param['title'] ?? '',
            'sort' => $param['sort'] ?? 0,
            'type' => $param['type'] ?? 0,
            'img' => $param['img'] ?? '',
        ];
        if(!$data['title']){
            throw new \think\Exception(L_('推荐标题必填！'));
        }
        if(!$data['img']){
            throw new \think\Exception(L_('请上传图标！'));
        }
        if($data['type']) {
            $category = (new GroupCategory())->where(['cat_id' => $data['type']])->findOrEmpty()->toArray();
            if(!$category){
                throw new \think\Exception(L_('团购类型不存在！'));
            }
        }
        Db::startTrans();
        try {
            if ($custom_id > 0) {
                $custom_detail = (new GroupRenovationGoodsCustom())->where([['custom_id', '=', $custom_id]])->findOrEmpty()->toArray();
                if(!$custom_detail){
                    throw new \think\Exception(L_('商品活动推荐已删除！'));
                }
                $custom_info = (new GroupRenovationGoodsCustom())->where([['title', '=', $data['title']], ['custom_id', '<>', $custom_id]])->findOrEmpty()->toArray();
                if ($custom_info) {
                    throw new \think\Exception(L_('商品活动推荐标题已存在'));
                }
                if($custom_detail['type']!=$data['type']){
                    (new GroupRenovationGoodsCustomTop())->where(['custom_id'=>$custom_id])->delete();
                }
                (new GroupRenovationGoodsCustom())->where([['custom_id', '=', $custom_id]])->save($data);
                $msg = '编辑成功';
            } else {
                /*$count = (new GroupRenovationGoodsCustom())->count();
                if ($count > 3) {
                    throw new \think\Exception(L_('商品活动推荐最多允许添加4个'));
                }*/
                $custom_info = (new GroupRenovationGoodsCustom())->where([['title', '=', $data['title']]])->findOrEmpty()->toArray();
                if ($custom_info) {
                    throw new \think\Exception(L_('商品活动推荐标题已存在'));
                }
                $data['create_time'] = time();
                (new GroupRenovationGoodsCustom())->save($data);
                $msg = '添加成功';
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return $msg;
    }

    /**
     * 商品活动推荐详情
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getRenovationGoodsCustomInfo($param){
        $custom_id = $param['custom_id'] ?? 0;
        if(!$custom_id){
            throw new \think\Exception('参数错误!');
        }
        $where = ['custom_id' => $custom_id];
        $info = (new GroupRenovationGoodsCustom())->where($where)->findOrEmpty()->toArray();
        $info['type'] = !empty($info['type']) ? (int)$info['type']:0;
        $info['img'] = $info['img']?replace_file_domain($info['img']):'';
        return ['detail' => $info];
    }

    /**
     * 商品活动推荐-置顶商品列表
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsCustomTopList($param){
        $custom_id = $param['custom_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $pageSize = $param['pageSize'] ?? 10;
        $keyword = $param['keyword'] ?? '';
        $custom_info = (new GroupRenovationGoodsCustom())->where(['custom_id' => $custom_id])->findOrEmpty()->toArray();

        if (empty($custom_info)) {
            throw new \think\Exception(L_("商品活动推荐信息不存在"), 1003);
        }
        
        // 排序
        $order = [
            'gt.sort' => 'DESC',
            'gt.id' => 'ASC'
        ];
        $where = [
            ['gt.custom_id', '=', $custom_id],
            ['g.status', '=', 1],
            ['g.begin_time', '<', time()],
            ['g.end_time', '>', time()],
            ['g.group_cate', '=', 'normal'],
        ];

        if (!empty($keyword)) {
            $where[] = ['g.name', 'like', '%' . $keyword . '%'];
        }
        if($custom_info['type']){
            $where[] = ['g.cat_id', '=', $custom_info['type']];
        }
        $field = 'gt.id,gt.sort,g.group_id,g.name,g.old_price,g.price,g.pic,m.mer_id,m.name as merchant_name';
        $list = (new GroupRenovationGoodsCustomTop())->getList($where,$field,$page,$pageSize,$order,'gt.group_id');
        foreach ($list['data'] as $key=>$item){
            $pic = $item['pic']?explode(';',$item['pic']):'';
            $pic = (new GroupImageService())->getImageByPath($pic[0], 's');
            $list['data'][$key]['pic'] = $pic?thumb_img($pic, 320, 205, 'fill'):'';
            $list['data'][$key]['nums'] = '无限量';
            $group_specifications = (new GroupSpecifications())->where(['group_id'=>$item['group_id']])->select()->toArray();
            if($group_specifications){
                $group_specifications_num = array_sum(array_column($group_specifications,'count_num'));
                $list['data'][$key]['nums'] = (string)$group_specifications_num;
            }
        }
        return $list;
    }

    /**
     * 商品活动推荐-添加置顶商品
     * @param $params
     */
    public function addGoodsCustomGroup($params){
        $custom_id = $params['custom_id'];
        $goods_arr = $params['goods_arr'];
        if(!$custom_id){
            throw new \think\Exception(L_('活动推荐不存在！'));
        }
        if(!$goods_arr||!is_array($goods_arr)){
            throw new \think\Exception(L_('请选择商品！'));
        }
        $custom_info = (new GroupRenovationGoodsCustom())->where(['custom_id'=>$custom_id])->findOrEmpty()->toArray();
        if(!$custom_info){
            throw new \think\Exception(L_('活动不存在！'));
        }
        $ready_ids = (new GroupRenovationGoodsCustomTop())->where([['group_id','in',$goods_arr]])->column('group_id');
        
        try {
            foreach ($goods_arr as $item){
                if(!in_array($item,$ready_ids)){
                    $save_data = [
                        'group_id' => $item,
                        'custom_id' => $custom_id,
                        'create_time' => time(),
                    ];
                    (new GroupRenovationGoodsCustomTop())->save($save_data);
                }
            }
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 商品活动推荐-删除置顶商品
     * @param $params
     * @throws \think\Exception
     */
    public function delGoodsCustomGroup($params){
        $custom_id = $params['custom_id'];
        $id = $params['id'];
        if(!$custom_id){
            throw new \think\Exception(L_('活动推荐不存在！'));
        }
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的商品！'));
        }
        try {
            (new GroupRenovationGoodsCustomTop())->where(['custom_id'=>$custom_id,'id'=>$id])->delete();
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 用户端-商品活动推荐列表
     * @param $params
     */
    public function getCustomGroupList($params){
        $custom_id = $params['custom_id']??0;
        $user_long = $params['user_long'];
        $user_lat = $params['user_lat'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $sort = $params['sort'];
        $from_category = $params['from_category']??0; //1-从分类进来，0-团购首页
        $cat_id = $params['sort_id']??0; //分类id
        $cat_fid = $params['cat_fid']??0; //分类父级id
        $new_nearby = $params['new_nearby'];
        $score = $params['score'];
        $city_id = $params['city_id']??0;
        $area_id = $params['area_id']??0;
        
        $where = [
            ['g.status', '=', 1],
            ['g.begin_time', '<', time()],
            ['g.end_time', '>', time()],
            ['g.group_cate','=','normal'],
            ['s.status','=',1],
            ['m.status','=',1],
            ['s.have_group', '=', 1]
        ];
        if($city_id){
            $where[] = ['s.city_id','=',$city_id];
        }
        if($area_id){
            $where[] = ['s.area_id','=',$area_id];
        }
        if($from_category){
            if($cat_fid){
                $where[] = ['g.cat_fid','=',$cat_fid];
            }
            if($cat_id){
                $where[] = ['g.cat_id','=',$cat_id];
            }
        }else{
            $group_renovation_custom = (new GroupRenovationGoodsCustom())->where(['custom_id'=>$custom_id])->findOrEmpty()->toArray();
            if(!$group_renovation_custom){
                throw new \think\Exception(L_('商品活动推荐不存在！'));
            }
            if($group_renovation_custom&&$group_renovation_custom['type']){
                $where[] = ['g.cat_id', '=', $group_renovation_custom['type']];
            }
            $order['gt.sort'] = 'DESC';
        }
        $order['distance'] = 'ASC';
        $field = "g.group_id,g.name,g.old_price,g.price,g.pic,g.appoint_time,g.cancel_type,g.sale_count_total,g.score_mean as score,g.score_percent,s.store_id,s.name as store_name,ACOS(SIN(({$user_lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$user_lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(({$user_long} * 3.1415) / 180 - (s.`long` * 3.1415) / 180)) * 6370.996 AS distance";

        // 智能排序
        if ($new_nearby) { //附近排序
            if(strpos($new_nearby, 'km') !== false){
                $new_nearby = str_replace('km','',$new_nearby);
            }else{
                $new_nearby = str_replace('m','',$new_nearby);
                $new_nearby = $new_nearby/1000;
            }
            $where[] = ['','exp',Db::raw("ACOS(SIN(({$user_lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$user_lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(({$user_long} * 3.1415) / 180 - (s.`long` * 3.1415) / 180)) * 6370.996 <= ".round($new_nearby,2))];
        }
        $order['g.score_mean'] = 'DESC';
        if ($score) { //评分排序
            $score = strpos($score, '分') !== false ? str_replace('分','',$score):$score;
            $where[] = ['g.score_mean','>=',round($score,2)];
            unset($order['distance']);
        }
        if ($sort == 'brain_sort') { //智能排序
            unset($order['distance']);
        } elseif ($sort == 'distance') { //距离排序
            //$order['distance'] = 'ASC';
        }

        $order['g.sort'] = 'DESC';
        $order['g.group_id'] = 'DESC';
        $list = (new Group())->getGoodsCustomList($where,$field,$page,$pageSize,$order,'g.group_id',$custom_id);
        foreach ($list['data'] as $key=>$item){
            $pic = $item['pic']?explode(';',$item['pic']):'';
            $pic = (new GroupImageService())->getImageByPath($pic[0], 's');
            $list['data'][$key]['pic'] = $pic?thumb_img($pic, 320, 205, 'fill'):'';
            $note_txt = '免预约';
            if($item['appoint_time']>0){
                $note_txt = '需预约';
            }
            if($item['cancel_type']==0){
                $note_txt .= ' | 随时可退';
            }else{
                $note_txt .= ' | 到期前可退';
            }
            $list['data'][$key]['score'] = !empty($value['score']) ? get_format_number($item['score']) : '5.0';
            $list['data'][$key]['note_txt'] = $note_txt;
            $list['data'][$key]['url'] = cfg('site_url').'/packapp/platn/pages/group/v1/groupDetail/index?group_id='.$item['group_id'];
            $distance = sprintf("%.2f", $item['distance']);
            $list['data'][$key]['distance'] = $distance <= 0 ? '0km' : $distance . 'km';
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($item['price'],'group',false,$item['score_percent']);
            $list['data'][$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $list['data'][$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }
        $list['list'] = $list['data'];
        unset($list['data']);
        $list['count'] = $list['total'];
        return $list;
    }

    /**
     * 商品活动推荐-修改置顶商品排序值
     * @param $params
     * @throws \think\Exception
     */
    public function changeGoodsCustomSort($params){
        $id = $params['id'];
        $sort = $params['sort'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        if($sort<0){
            throw new \think\Exception(L_('排序值不能小于0！'));
        }
        $where = [
            'id' => $id
        ];
        $goods_info = (new GroupRenovationGoodsCustomTop())->where($where)->findOrEmpty()->toArray();
        if(!$goods_info){
            throw new \think\Exception(L_('信息不存在！'));
        }
        try {
            (new GroupRenovationGoodsCustomTop())->where($where)->save(['sort'=>$sort]);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }
}