<?php

namespace app\marriage_helper\model\service;

use app\common\model\db\User;
use app\mall\model\db\Area;
use app\marriage_helper\model\db\MarriageMethodCategory;
use app\marriage_helper\model\db\MarriageMethodUse;
use net\Http;
class MarriageToolService
{
    /**
     * 结婚攻略列表
     */
    public function toolList()
    {
        $where = [['cat_fid', '=', 0], ['is_del', '=', 0]];
        $list = (new MarriageMethodCategory())->getSome($where, true, 'sort desc,cat_id desc')->toArray();
        // 返回前端需要格式
        $fomartList = [];
        foreach ($list as $_sort) {
            // 商品数量
            $where = [
                ['cat_fid', '=', $_sort['cat_id']]
            ];
            $childCount = (new MarriageMethodCategory())->getCount($where);
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
        $list['list'] = (new MarriageMethodCategory())->getSome($where, $field, 'sort desc,cat_id desc')->toArray();
        $list['count'] = (new MarriageMethodCategory())->getCount($where);
        return $list;
    }

    /**
     *前端页面列表
     */
    public function getToolList()
    {
        $category_list = $this->toolList();
        if (!empty($category_list)) {
            foreach ($category_list as $key => $val) {
                $field = 'cat_id,cat_title,cat_description,logo_title,cat_url,use_num,no_use_num';
                $where = [['cat_fid', '=', $val['id']], ['is_del', '=', 0]];
                $category_list[$key]['children'] = (new MarriageMethodCategory())->getSome($where, $field, 'sort desc,cat_id desc')->toArray();
            }
        }
        $list['list'] = $category_list;
        return $list;
    }

    /**
     * 前端攻略详情页--有用无用
     */
    public function isToolUse($param)
    {
        $where = [['uid', '=', $param['uid']], ['cat_id', '=', $param['cat_id']]];
        $data['is_use']=$param['is_use'];
        $where1 = [['cat_id', '=', $param['cat_id']]];
        $arr = (new MarriageMethodUse())->getOne($where);
        $rets=array();
        if (empty($arr)) {
            if($data['is_use']==0){
                (new MarriageMethodCategory())->setInc($where1,'no_use_num');;
            }else{
                (new MarriageMethodCategory())->setInc($where1,'use_num');
            }
            $ret=(new MarriageMethodUse())->add($param);
        } else {
            $arr=$arr->toArray();
            if($data['is_use']==0 && $arr['is_use']=1) {
                (new MarriageMethodCategory())->setInc($where1,'no_use_num');
                (new MarriageMethodCategory())->setInc($where1,'use_num');
                $ret=(new MarriageMethodUse())->updateThis($where,$data);
            }elseif($data['is_use']==1 && $arr['is_use']=0){
                (new MarriageMethodCategory())->setInc($where1,'use_num');
                (new MarriageMethodCategory())->setInc($where1,'no_use_num');
                $ret=(new MarriageMethodUse())->updateThis($where,$data);
            }
        }

        $arr1=(new MarriageMethodCategory())->getOne($where1)->toArray();
        $rets['use_num']=$arr1['use_num'];
        $rets['no_use_num']=$arr1['no_use_num'];
        return $rets;
    }

    /**
     * 前端结婚计划设置婚期
     */
    public function setMarriageDate($param)
    {
        $where = [['uid', '=', $param['uid']]];
        $data['marry_date']=strtotime($param['marry_date']);
        $arr = (new User())->getOne($where);
        if(empty($arr)){
            $ret=false;
        }else{
            $ret=(new User())->updateThis($where,$data);
        }
        return $ret;
    }

    /**
     * 前端结婚计划设获取婚期
     */
    public function getMarriageDate($param){
        $where = [['uid', '=', $param['uid']]];
        $arr = (new User())->getOne($where);
        if(empty($arr)){
            $ret1['status']=0;
            $ret1['data']=[];
        }else{
            $arr=$arr->toArray();
            $one=date('Y-m-d',$arr['marry_date']);
            $two=date('Y-m-d',time());
            if((!empty($arr['marry_date']) && $arr['marry_date'] > time()) || $one==$two){
                $left=$arr['marry_date']-time();
                if($left<0){
                    $ret['days']=0;
                }else{
                    $ret['days']=ceil($left/86400);
                }
                $ret['date']=date("Y/m/d",$arr['marry_date']);
                $ret1['status']=1;
                $ret1['data']=$ret;
            }else{
                $ret1['status']=1;
                $ret1['data']=[];
            }

        }
        return $ret1;
    }

    /**
     * 前端婚姻登记处
     */
    public function getMarriageAddr($param)
    {
        $http = new Http();
        if(empty($param['area_name'])){
            $where = [['uid', '=', $param['uid']]];
            $arr = (new User())->getOne($where);
            if(empty($arr)){
                $ret1['status']=0;
                $ret1['data']=[];
            }else {
                $arr = $arr->toArray();
                if(empty($arr['city'])){
                    $sel['area_name']="北京";
                }else{
                    $sel['area_name']=$arr['city'];
                }
            }
        }else{
            $sel['area_name']=$param['area_name'];
        }
        $sel['area_type']=2;
        header("Content-type: application/json");
        $where=[['area_name','like','%'.$sel['area_name'].'%'],['area_type','=',2]];
        $city_limit=true;
        $query="民政局";
        $now_city = (new Area())->getOne($where);
        if(!empty($now_city)){
            $now_city=$now_city->toArray();
        }else{
            $where=[['area_name','=','北京'],['area_type','=',2]];
            $now_city = (new Area())->getOne($where)->toArray();
        }

        if(!empty($param['user_lng']) && !empty($param['user_lat'])){
            $url = 'http://api.map.baidu.com/geocoder/v2/?output=json&pois=1&ak=4c1bb2055e24296bbaef36574877b4e2&location=' . $param['user_lat'] . ',' . $param['user_lng'];
            $result = $http->curlGet($url);
            $result = json_decode($result, true);
            $now_city['area_name'] = $result['result']['addressComponent']['city'];
        }else{
            $param['user_lng']=empty($now_city['area_lng'])?"":$now_city['area_lng'];
            $param['user_lat']=empty($now_city['area_lat'])?"":$now_city['area_lat'];
            $assign['city_name']=$now_city['area_name'];
            $now_city['area_name'] = str_replace('澳門','澳门',$now_city['area_name']);
        }

        if($now_city['area_name'] == '黔东南'){
            $now_city['area_name'] = '黔东南苗族侗族自治州';
        }else if($now_city['area_name'] == '西双版纳'){
            $now_city['area_name'] = '西双版纳傣族自治州';
        }

        $assign['status']=1;
        $url = 'http://api.map.baidu.com/place/v2/search?query='.urlencode($query).'&region='.urlencode($now_city['area_name']).'&ak='.cfg('baidu_map_ak').'&city_limit='.$city_limit.'&output=json&page_size=20';
        $result = $http->curlGet($url);
        if($result){
            $result = json_decode($result,true);
            if($result['status'] == 0 && $result['results']){
                $return = array();
                foreach($result['results'] as $value){
                    if (!isset($value['location'])) continue;
                    $return[] = array(
                        'name'=>$value['name'],
                        'lat'=>$value['location']['lat'],
                        'lng'=>$value['location']['lng'],
                        'city'=>$value['city'] ? $value['city'] : "",
                        'district'=>$value['area'] ? $value['area'] : "",
                        'address'=>$value['address'] ? $value['address'] : $value['area'],
                        'telephone'=>isset($value['telephone'])?$value['telephone']:"",
                    );
                }
                if($param['user_lng'] && $param['user_lat']){
                    foreach($return as $key=>$value){
                        $return[$key]['distance'] = getDistance($param['user_lat'],$param['user_lng'],$value['lat'],$value['lng']);
                        $return[$key]['distance'] = getFormatNumber($return[$key]['distance']/1000);
                    }
                    $return = sortArr($return,'distance');
                }
                $assign['list']=$return;
            }else{
                //尝试地点输入提示服务
                $url = 'http://api.map.baidu.com/place/v2/suggestion?query='.urlencode($query).'&region='.urlencode($now_city['area_name']).'&ak='.cfg('baidu_map_ak').'&city_limit='.$city_limit.'&output=json&page_size=20';
                //echo $url;die;
                $http = new Http();
                $result = $http->curlGet($url);
                if($result){
                    $result = json_decode($result,true);
                    if($result['status'] == 0 && $result['result']){
                        $return = array();
                        foreach($result['result'] as $value){
                            if (!isset($value['location'])) continue;
                            $return[] = array(
                                'name'=>$value['name'],
                                'lat'=>$value['location']['lat'],
                                'lng'=>$value['location']['lng'],
                                'city'=>$value['city'],
                                'district'=>$value['district'],
                                'address'=>$value['district'],
                                'telephone'=>isset($value['telephone'])?$value['telephone']:"",
                            );
                        }
                        if($param['user_lng'] && $param['user_lat']){
                            foreach($return as $key=>$value){
                                $return[$key]['distance'] = getDistance($param['user_lng'],$param['user_lat'],$value['lat'],$value['lng']);
                                $return[$key]['distance'] = getFormatNumber($return[$key]['distance']/1000);
                            }
                            $return = sortArr($return,'distance');
                        }
                        $assign['list']=$return;
                    }else{
                        $assign['status']=0;
                        $assign['msg']=L_('没有查找到内容');
                    }
                }else{
                    $assign['status']=0;
                    $assign['msg']=L_('获取失败');
                }
            }
        }else{
            $assign['status']=0;
            $assign['msg']=L_('获取失败');
        }
        return $assign;
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
            $res = (new MarriageMethodCategory())->updateThis($where, $data);
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
        $ret = (new MarriageMethodCategory())->updateThis($where, $data);
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
        $ret = (new MarriageMethodCategory())->add($param);
        return $ret;
    }

    /**
     *  更新分类
     */
    public function updateCategory($param)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $ret = (new MarriageMethodCategory())->updateThis($where, $param);
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
        $where = [['cat_id', 'in', $param['cat_id']]];
        $list = (new MarriageMethodCategory())->getOne($where)->toArray();
        if (!empty($list)) {
            if ($list['cat_fid'] == 0) {
                $where1 = [['cat_fid', '=', $list['cat_id']]];
                $data['is_del'] = 1;
                (new MarriageMethodCategory())->updateThis($where1, $data);
            }
        }
        $ret = (new MarriageMethodCategory())->updateThis($where, $param);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *自增
     */
    public function setInc($param, $field)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $ret = (new MarriageMethodCategory())->setInc($where, $field);
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
        $where = [['cat_id', '=', $param['cat_id']]];
        $ret = (new MarriageMethodCategory())->getOne($where);
        if ($ret) {
            return $ret->toArray();
        } else {
            return false;
        }
    }


}