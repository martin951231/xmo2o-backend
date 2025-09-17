<?php


namespace app\common\controller\platform;

use app\common\controller\CommonBaseController;
use app\common\model\db\DiypageFeedStoreSort;
use app\common\model\db\Merchant;
use app\common\model\db\MerchantCategory;
use app\common\model\service\diypage\DiypageFeedService;
use app\group\model\db\Group;
use app\grow_grass\model\db\GrowGrassCategory;
use app\mall\model\db\MerchantStore;
use app\merchant\model\service\MerchantCategoryService;

class DiypageFeedCategoryController extends CommonBaseController
{
    /**
     * @return \json
     * 获取店铺分类页分类导航列表
     */
    public function diypageFeedCategoryList(){
         $diypageFeed=new DiypageFeedService();
         $cate_id = $this->request->param('cat_id', 0, 'intval');
         $where=[['is_del','=',0],['cat_id','=',$cate_id]];
         $page = $this->request->param('page', 1, 'intval');
         //$pageSize = Config::get('api.page_size');
         $pageSize=$this->request->param('pageSize', 10, 'intval');
         $list=$diypageFeed->getList($where,$page,$pageSize);
         return api_output(1000, $list);
   }

    /**
     * @return \json
     * 获取店铺分类页分类信息--编辑
     */
    public function diypageFeedCategoryEdit(){
        $category_id = $this->request->param('category_id', 0, 'intval');
        $cat_id = $this->request->param('cat_id', 0, 'intval');
        if($category_id){
            $diypageFeed=new DiypageFeedService();
            $where=[['category_id','=',$category_id]];
            $msg=$diypageFeed->getOne($where);
            if(!empty($msg['ids'])){
                $msg['ids']=explode(",",$msg['ids']);
                //$where2=[['cat_id','in',$msg['ids']]];
            }else{
                $msg['ids']=[];
            }
        }

        $merchantCategory=new MerchantCategoryService();
        $where1=[['cat_fid','=',$cat_id]];
        // $msg1=$merchantCategory->getSome($where1,'cat_id,cat_name')->toArray();
        $msg1 = $merchantCategory->getCategoryListTree();
        
        // $cat_sel=array();
        // if(!empty($msg1)){
        //     foreach ($msg1 as $key=>$val){
        //         $cat['cat_id']=$val['cat_id']."";
        //         $cat['cat_name']=$val['cat_name'];
        //         $cat_sel[]=$cat;
        //     }
            $msg['cat_sel']=$msg1;
        // }


        $where2=[['status','=',1],['is_del','=',0]];
        $msg2=(new GrowGrassCategory())->getSome($where2,'category_id,name')->toArray();
        $cat_sel1=array();
        if(!empty($msg2)){
            foreach ($msg2 as $key=>$val){
                $cat1['cat_id']=$val['category_id']."";
                $cat1['cat_name']=$val['name'];
                $cat_sel1[]=$cat1;
            }
            $msg['huati']=$cat_sel1;
        }
        return api_output(1000, $msg);

    }

    /**
     *
     * 编辑添加店铺分类页分类--添加修改保存
     */
   public function diypageFeedCategorySave(){
       $diypageFeed=new DiypageFeedService();

       $category_id = $this->request->param('category_id', 0, 'intval');
       $data['cat_id']=$this->request->param('cat_id', 0, 'intval');
       $data['title']=$this->request->param('title', '', 'trim');
       $data['description']=$this->request->param('description', '', 'trim');
       $data['type']=$this->request->param('type', 1, 'intval');
       $data['ids']=$this->request->param('ids', '', 'trim');
       if(empty($data['title'])) {
           return api_output_error(1003, L_('请输推荐标题'));
       }
       if(empty($data['title'])) {
           return api_output_error(1003, L_('请输推荐标题'));
       }
       if(!empty($data['ids'])){
           $data['ids']=implode(',',$data['ids']);
       }else{
           $data['ids']="";
       }
       $data['show_sort_type']=$this->request->param('show_sort_type', 1, 'intval');
       $data['show_type']=$this->request->param('show_type', 1, 'intval');
       $data['sort']=$this->request->param('sort', 0, 'intval');
       $data['is_del']=$this->request->param('is_del',0, 'intval');
       if($category_id){//修改
           $where=[['category_id','=',$category_id]];
           $status=$diypageFeed->updateThis($where,$data);
           return api_output(1000, $status);
       }else{//添加
           $data['create_time']=time();
           $uid=$diypageFeed->insertThis($data);
           if($uid){
               return api_output(1000, $uid);
           }else{
               return api_output_error(1003, L_('新增失败'));
           }
       }
   }

    /**
     * @return \json
     * 编辑添加店铺分类页分类--删除
     */
    public function diypageFeedCategoryDel()
    {
        $category_id = $this->request->param('category_id', 0, 'intval');
        $diypageFeed=new DiypageFeedService();
        if($category_id) {
            $where=[['category_id','=',$category_id]];
            $data['is_del']=1;
            $status=$diypageFeed->updateThis($where,$data,$category_id);
            return api_output(1000, $status);
        }else{
            return api_output_error(1003, L_('ID缺失'));
        }
    }

    /**
     * @return \json
     * 获取店铺分类页分类内容管理列表
     */
   public function diypageFeedCategoryStoreList(){
       $cat_id=$this->request->param('ids', 0, 'trim');
       $page=$this->request->param('page', 1, 'intval');
       $mer_name=$this->request->param('mer_name', '', 'trim');
       $pageSize=$this->request->param('pageSize', 10, 'intval');
       $merchantStore=new MerchantStore();
       $group=new Group();
       $merchant=new Merchant();
       $where[]=['a.status','=',1];
       $where[]=['m.status','=',1];
       if($cat_id){
           $cat_id1=explode(',',$cat_id);
           $where[]=['a.cat_id','in',$cat_id1];
       }else{
           $where[]=['a.cat_id','>',0];
       }
           $mer=array();
           if($mer_name){
               $where1=[['name', 'like', '%' . $mer_name . '%']];
               $merList=$merchant->getSome($where1,"mer_id")->toArray();
               foreach ($merList as $k=>$v){
                   $mer[]=$v['mer_id'];
               }
               $where[]=['a.mer_id','in',$mer];
           }
            $list=$merchantStore->getListByStoreSort($where,"a.name,a.mer_id,a.store_id,a.phone,a.last_time,s.sort","s.sort desc",$page,$pageSize);
           if($list['count']>0){
               foreach ($list['list'] as $key=>$val){
                   if(empty($val['sort'])){
                       $list['list'][$key]['sort']=0;
                   }
                   $where_cat=[['mer_id','=',$val['mer_id']]];
                   $count=$group->getCount($where_cat);
                   $mer=$merchant->getInfo($val['mer_id']);
                   if($mer){
                       $list['list'][$key]['mer_name']=$mer['name'];
                   }else{
                       $list['list'][$key]['mer_name']="";
                   }

                   if(!empty($val['last_time'])){
                       $list['list'][$key]['last_time']=date("Y-m-d H:i:s",$val['last_time']);
                   }
                   $list['list'][$key]['group_num']=$count;
               }
           }
           return api_output(1000, $list);

   }

    /**
     * @return \json
     * 添加更新分类排序
     */
    public function diypageFeedCategoryStoreSortEdit(){
        $data['category_id']=$this->request->param('category_id', 0, 'intval');
        $data['mer_id']=$this->request->param('mer_id', 0, 'intval');
        $data['store_id']=$this->request->param('store_id', 0, 'intval');
        $data['sort']=$this->request->param('sort', 0, 'intval');
        $where=[['category_id','=',$data['category_id']],['mer_id','=',$data['mer_id']],['store_id','=',$data['store_id']]];
        $diypageFeedStoreSort=new DiypageFeedStoreSort();
        $msg=$diypageFeedStoreSort->getOne($where);
        if(empty($msg)){//添加
            $uid=$diypageFeedStoreSort->add($data);
            return api_output(1000, $uid);
        }else{//编辑
            $msg=$msg->toArray();
            $where=[['pigcms_id','=',$msg['pigcms_id']]];
            $status=$diypageFeedStoreSort->updateThis($where,$data);
            if($status!==false){
                return api_output(1000, $status);
            }else{
                return api_output_error(1003, L_('更新失败'));
            }
        }
    }
}