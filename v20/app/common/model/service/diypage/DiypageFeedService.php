<?php
namespace app\common\model\service\diypage;

use app\common\model\db\Diypage;
use app\common\model\db\DiypageFeedCategory;
use app\common\model\db\MerchantCategory;
use app\grow_grass\model\db\GrowGrassCategory;
use app\merchant\model\db\MerchantStore;

class DiypageFeedService
{
    public $diypageFeed = null;
    public $merchantCategory = null;
    public function __construct()
    {
        $this->diypageFeed=new DiypageFeedCategory();
        $this->merchantCategory=new MerchantCategory();
    }

    /**
     * @param $where
     * @return mixed
     * 获取店铺分类页分类导航列表
     */
     public function getList($where,$page,$pageSize){
         $diy=$this->diypageFeed->getSome($where,true,"sort desc",($page-1)*$pageSize,$pageSize)->toArray();

         if(!empty($diy)){
             foreach ($diy as $key=>$val){
                 $ids=explode(',',$val['ids']);
                 $where=[['cat_id','in',$ids]];
                 if($val['type']==1){
                     $cat_name=$this->merchantCategory->getCategoryName($where,"cat_name");
                     if(empty($cat_name)){
                         $cat_name="";
                     }else{
                         $cat_name=implode(',',$cat_name);
                     }

                 }else{
                     $ids=explode(',',$val['ids']);
                     $where=[['category_id','in',$ids]];
                     $cat_name=(new GrowGrassCategory())->getCategoryName($where,"name");
                     if(empty($cat_name)){
                         $cat_name="";
                     }else{
                         $cat_name=implode(',',$cat_name);
                     }
                 }
                 $diy[$key]['cat_name']=$cat_name;
                 $diy[$key]['title_content']=$val['title'];
             }
         }
         $assign['list']=$diy;
         $assign['count']=count($diy);
         return $assign;
     }

    /**
     * @param $where
     * @return mixed
     * 获取店铺分类页分类信息
     */
     public function getOne($where){
        $msg= $this->diypageFeed->getOne($where);
         if(!empty($msg)){
             $msg=$msg->toArray();
         }
         return $msg;
     }

    /**
     * @param $where
     * @param $data
     * @return mixed
     * 店铺分类导航--数据更新吗
     */
     public function updateThis($where,$data,$category_id=0){
         $ret=$this->diypageFeed->updateThis($where,$data);
         if($ret!==false){
             if(isset($data['is_del']) && $data['is_del']==1){
                 if($category_id){
                     $where_cate=[['category_id','=',$category_id]];
                     $detail=$this->getOne($where_cate);
                     if(!empty($detail)){
                         $where1 = [['s.source', '=', 'category'], ['s.source_id', '=', $detail['cat_id']], ['s.is_del', '=', 0]];
                         $msg = (new Diypage())->getDiypageDetail('category', $where1, "s.source_id,s.source,s.content,m.cat_name,m.cat_fid");
                         if (!empty($msg)) {
                             $list = unserialize($msg['content']);
                             if (!empty($list)) {
                                 foreach ($list as $key => $val) {
                                     if($val['type']=='feedModule'){//判断该分类下没有绑定门店则不显示该分类
                                         if(isset($val['content']['list'])){
                                             foreach ($val['content']['list'] as $k=>$v){
                                                 if($v['category_id']==$category_id){
                                                     unset($list[$key]['content']['list'][$k]);
                                                 }
                                             }
                                             $list[$key]['content']['list']=array_values($list[$key]['content']['list']);
                                         }
                                     }
                                 }
                             }
                             $data1['content']=serialize($list);
                             $where_update = [['source', '=', 'category'], ['source_id', '=', $detail['cat_id']]];
                             (new Diypage())->updateThis($where_update,$data1);
                         }
                     }

                 }
             }else{
                 if($category_id) {
                     $where_cate=[['category_id','=',$category_id]];
                     $detail=$this->getOne($where_cate);
                     if(!empty($detail)){
                         $where1 = [['s.source', '=', 'category'], ['s.source_id', '=', $detail['cat_id']], ['s.is_del', '=', 0]];
                         $msg = (new Diypage())->getDiypageDetail('category', $where1, "s.source_id,s.source,s.content,m.cat_name,m.cat_fid");
                         if (!empty($msg)) {
                             $list = unserialize($msg['content']);
                             if (!empty($list)) {
                                 foreach ($list as $key => $val) {
                                     if($val['type']=='feedModule'){//判断该分类下没有绑定门店则不显示该分类
                                         if(isset($val['content']['list'])){
                                             foreach ($val['content']['list'] as $k=>$v){
                                                 if($v['category_id']==$category_id){
                                                     $list[$key]['content']['list'][$k]=$detail;
                                                 }
                                             }
                                         }
                                     }
                                 }
                             }
                             $data1['content']=serialize($list);
                             $where_update = [['source', '=', 'category'], ['source_id', '=', $detail['cat_id']]];
                             (new Diypage())->updateThis($where_update,$data1);
                         }
                     }
                 }
             }
             return true;
         }else{
             return false;
         }
     }

     public function insertThis($data){
         return $this->diypageFeed->add($data);
     }
}