<?php
/**
 * 图文管理model
 * Author: wangchen
 * Date Time: 2021/5/21
 */

namespace app\atlas\model\db;

use app\common\model\db\MerchantCategory;
use app\common\model\db\Config as ConfigModel;
use think\Model;

class AtlasArticle extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 图文管理列表
     * @param $data array 数据
     * @return array
     */
    public function getAtlasArticleList($where, $page, $pageSize){
        $arr = $this->where($where)->field(true)->order('sort DESC,id DESC')->page($page, $pageSize)->select();
        $url = (new ConfigModel())->where(['name'=>'site_url'])->field('value')->find()->toArray();
        foreach($arr as $k=>$v){
            $arr[$k]['edit_time'] = date('m-d H:i',$v['edit_time']);
            $arr[$k]['create'] = $url['value'].'/static/images/atlas/create.png';
            $arr[$k]['del'] = $url['value'].'/static/images/atlas/del.png';
        }

        $count = $this->where($where)->count('id');

        $list['count'] = $count;
        $list['page_size'] = intval($pageSize);
        $list['list'] = [];

        $catLis = (new MerchantCategory())->where(['cat_status'=>1,'cat_fid'=>0])->field('cat_id,cat_name')->order('cat_sort DESC,cat_id DESC')->select()->toArray();
        $aotu = [['cat_id'=>0,'cat_name'=>'全部']];
        $catList = array_merge($aotu,$catLis);
        $list['catList'] = $catList;

        if (!empty($arr)) {
            $list['list'] = $arr->toArray();
            return $list;
        } 
        return $list;
    }

    /**
     * 图文管理保存
     * @param $param
     * @return array
     */
    public function getAtlasArticleCreate($id, $data){
        if($id>0){
            //编辑
            $where = ['id' =>$id];
            if(!$data['cat_id']){ //分类为0不修改
                unset($data['cat_id']);
            }
            $result = $this->where($where)->update($data);
        }else{
            // 新增
            $result = $this->save($data);
        }
        if($result===false){
            throw new \think\Exception("操作失败请重试",1005);
            
        }
        return $result;
    }

    /**
     * 获取一条记录
     * @param $data array 数据
     * @return array
     */
    public function getAtlasArticleDetail($where){
        $result = $this->where($where)->find();

        $result['img'] = [replace_file_domain($result['pic'])];
        $result['pic'] = [replace_file_domain($result['pic'])];
        $result['add_time'] = date('Y-m-d',$result['add_time']);
        if($result['content']){
            $result['content'] = replace_file_domain_content_img(unserialize($result['content']));
        }

        $cat_fid = (new AtlasCategory())->where(['cat_status'=>1,'cat_id'=>$result['cat_id']])->find();
        $result['cat_id'] = [$cat_fid['cat_fid'],$result['cat_id']];

        $specialList = (new AtlasSpecial())->where(['cat_id'=>$result['cat_id'],'status'=>0])->select()->toArray();
        $result['specialList'] = $specialList;
        return $result;
    }

    /**
     * 图文管理分类
     */
    public function getAtlasArticleClass()
    {
        $merlist = (new MerchantCategory())->where(['cat_status'=>1,'cat_fid'=>0])->order('cat_sort DESC,cat_id DESC')->select()->toArray();
        $catlist = (new AtlasCategory())->where(['cat_status'=>1])->order('cat_sort DESC,cat_id DESC')->select()->toArray();
        $arr = array_merge($merlist,$catlist);
        $list = $this->aryTree($arr);
        if($list){
            $result=[]; $i=0;
            foreach($list as $v){
                $children=[];
                if($v['lists']){
                    $j=0;
                    foreach($v['lists'] as $vs){
                        $children[$j] = ['value'=>$vs['cat_id'],'label'=>$vs['cat_name']];
                        $j++;
                    }
                }
                $result[$i] = ['value'=>$v['cat_id'],'label'=>$v['cat_name'],'children'=>$children];
                $i++;
            }
        }
        return $result;
    }

    /**
     * 图文管理分类标签
     */
    public function getAtlasArticleOption($cat_id,$id)
    {
        $specialList = (new AtlasSpecial())->where(['cat_id'=>$cat_id])->field('id,cat_id,type_id,name')->order('sort DESC,id DESC')->select()->toArray();

        if($id){
            if($specialList){
                foreach($specialList as $k=>$v){
                    $where = [['a.article_id','=',$id],['b.special_id','=',$v['id']]];
                    $field = 'a.*';
                    $resultss = (new AtlasArticleOption())->alias('a')->where($where)->field($field)->join('atlas_special_option b', 'b.id = a.option_id');
                    $assign = $resultss->select()->toArray();
                    $assigns = [];
                    if($assign){
                        foreach($assign as $vs){
                            $assigns[] = $vs['option_id'];
                        }
                    }
                    $specialList[$k]['ownerid'] = $assigns;
                    $specialList[$k]['optionList'] = (new AtlasSpecialOption())->where(['special_id'=>$v['id']])->order('id DESC')->select()->toArray();
                }
            }
        }else{
            foreach($specialList as $k=>$v){
                $specialList[$k]['ownerid'] = [];
                $specialList[$k]['optionList'] = (new AtlasSpecialOption())->where(['special_id'=>$v['id']])->order('id DESC')->select()->toArray();
            }
        }
        return $specialList;
    }

    /**
     * 图文管理删除
     */
    public function getAtlasArticleDel($id)
    {
        $list = $this->where(array('id'=>$id))->update(array('status'=>1));
        return $list;
    }

    /**
     * 获得图集详情
     * @param $where array 条件
     * @return array
     */
    public function atlasArticleDetail($where){
        $field = 'id,cat_id,title,pic,description,content,views_num,edit_time';
        $list = $this->field($field)->where($where)->find();
        return $list;
    }  

    /**
     * 增加查看数
     * @param $id  条件
     */
    public function atlasArticleViewsNum($id){
        $list = $this->where(['id'=>$id,'status'=>0])->count();
        if($list){
            $this->where(['id'=>$id])->inc('views_num')->update();
        }
    }  

    // 文字列表
    public function atlasArticleList($page,$pageSize,$where,$field,$order,$type,$param){
        if($type){
            foreach($param as $k=>$v){
                if($v['option_id']){
                    array_push($where, ['b'.$k.'.option_id', '=', $v['option_id']]);
                }
            }
            $result = $this->alias('a');
            $result->field($field);
            $result->where($where);
            foreach($param as $k=>$v){
                if($v['option_id']){
                    $result->join('atlas_article_option b'.$k, 'b'.$k.'.article_id = a.id');
                }
            }
            $assign['list'] = $result->order($order)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            return $assign;
        }else{
            $result = $this->alias('a')
                ->where($where)
                ->field($field);
            $assign['list'] = $result->order($order)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            return $assign;
        }
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

}