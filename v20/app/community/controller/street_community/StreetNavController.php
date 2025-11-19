<?php
/**
 * 街道导航相关
 * @author weili
 * @date 2020/9//9
 */

namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\StreetNavService;
class StreetNavController extends CommunityBaseController
{
    /**
     * Notes: 获取街道列表
     * @return \json
     * @author: weili
     * @datetime: 2020/9/9 18:29
     */
    public function getStreetNavList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = 10;
        $serviceStreetNav = new StreetNavService();
        try {
            $list = $serviceStreetNav->getList($street_id,$page,$limit,true);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        return api_output(0, $list, "成功");
    }

    /**
     * Notes: 添加编辑街道导航
     * @return \json
     * @author: weili
     * @datetime: 2020/9/9 19:05
     */
    public function addStreetNav()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id',0,'intval');
        $url = $this->request->param('url','','trim');
        $name = $this->request->param('name','','trim');
        $status = $this->request->param('status','','trim');
        $sort = $this->request->param('sort',0,'intval');
        $img = $this->request->param('img','','trim');
        if ($status) {
            $status = 1;
        } else {
            $status = 0;
        }
        if(!$url || !$name)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $url = htmlspecialchars_decode($url);
        $data = [
            'street_id'=>$street_id,
            'name'=>$name,
            'sort'=>$sort,
            'status'=>$status,
            'url'=>$url,
            'add_time' => time(),
        ];
        if($img){
            $data['img'] = $img;
        }
        $serviceStreetNav = new StreetNavService();
        try {
            $res = $serviceStreetNav->saveNav($data,$id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes:获取街道导航详情
     * @return \json
     * @author: weili
     * @datetime: 2020/9/9 19:06
     */
    public function getStreetNavInfo()
    {
        $id = $this->request->param('id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceStreetNav = new StreetNavService();
        try {
            $data = $serviceStreetNav->getStreetNav($id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes: 删除街道导航
     * @return \json
     * @author: weili
     * @datetime: 2020/9/9 19:06
     */
    public function del()
    {
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceStreetNav = new StreetNavService();
        try {
            $res = $serviceStreetNav->del($id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res)
        {
            return api_output(0, '', "成功");
        }else{
            return api_output_error(1002,  "失败");
        }

    }
    public function addStreetServiceNav()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        $nav_url = $this->request->post('nav_url','','trim');
        $nav_name = $this->request->post('nav_name','','trim');
        $xstatus = $this->request->post('xstatus','','intval');
        $xsort = $this->request->post('xsort',0,'intval');
        $img_url = $this->request->post('img_url','','trim');
        $level_show = $this->request->post('level_show','','intval');
        $cfrtype = $this->request->post('cfrtype','','trim');
        $cat_id = $this->request->post('cat_id',0,'intval');
        $xcontent = $this->request->post('xcontent','','trim');
        $xcontent=  $xcontent ? htmlspecialchars($xcontent,ENT_QUOTES):'';
        $nav_url = $nav_url ? urldecode($nav_url):'';
        $data = [
            'nav_name'=>$nav_name,
            'xsort'=>$xsort,
            'xstatus'=>$xstatus,
            'nav_url'=>$nav_url,
            'img_url'=>$img_url,
            'level_show'=>$level_show,
            'cat_id'=>0,
        ];
        if($cfrtype=='serviceCentre'){
            $data['xtype']=1;
            $data['cat_id']=$cat_id;
            $data['xcontent']=$xcontent;
        }
        try {
            $serviceStreetNav = new StreetNavService();
            $whereArr=array();
            if($this->adminUser['area_type']==1){
                $data['community_id']=$this->adminUser['area_id'];
                $data['street_id']=$this->adminUser['area_pid'];
                $data['nav_type']=1; //社区
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('nav_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $data['street_id']=$street_id;
                $data['community_id']=0;
                $data['nav_type']=0; //街道
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('nav_type','=',0);   //街道
            }
            $res = $serviceStreetNav->saveOneStreetServiceNav($data,$idd,$whereArr);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }
    public function getStreetServiceNavInfo(){
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output_error(1001,'记录数据ID不存在！');
        }
        $cfrtype = $this->request->post('cfrtype','','trim');
        $cat_id = $this->request->post('cat_id',0,'intval');
        $serviceStreetNav = new StreetNavService();
        try {
            $whereArr=array(['id','=',$idd]);
            if($this->adminUser['area_type']==1){
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('nav_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('nav_type','=',0);   //街道
            }
            if($cfrtype=='serviceCentre'){
                $whereArr[]=array('cat_id','=',$cat_id);
                $whereArr[]=array('xtype','=',1);
            }else{
                $whereArr[]=array('cat_id','=',0);
                $whereArr[]=array('xtype','=',0);
            }
            $dataInfo = $serviceStreetNav->getOneStreetServiceNav($whereArr,'*','id DESC');
            if(empty($dataInfo)){
                return api_output_error(1001,'编辑数据不存在！');
            }
            $returnArr=array('detail'=>$dataInfo);
            return api_output(0, $returnArr);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getStreetServiceNavList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $page = $this->request->post('page',1,'intval');
        $nav_name = $this->request->post('nav_name','','trim');
        $cfrtype = $this->request->post('cfrtype','','trim');
        $cat_id = $this->request->post('cat_id',0,'intval');
        $page=$page>0 ? $page:1;
        $limit = 10;
        $serviceStreetNav = new StreetNavService();
        try {
            $whereArr=array();
            if($this->adminUser['area_type']==1){
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('nav_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('nav_type','=',0);   //街道
            }
            if($nav_name){
                $whereArr[]=array('nav_name','like','%'.$nav_name.'%');
            }
            $whereArr[]=array('xstatus','<',4);
            if($cfrtype=='serviceCentre'){
                $whereArr[]=array('cat_id','=',$cat_id);
                $whereArr[]=array('xtype','=',1);
            }else{
                $whereArr[]=array('cat_id','=',0);
                $whereArr[]=array('xtype','=',0);
            }
            $listData = $serviceStreetNav->getStreetServiceNavList($whereArr,'*','xsort desc,id DESC',$page,$limit);
            return api_output(0, $listData);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function delStreetServiceNav(){
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output_error(1001,'记录数据ID不存在！');
        }
        $cfrtype = $this->request->post('cfrtype','','trim');
        $cat_id = $this->request->post('cat_id',0,'intval');
        try {
            $serviceStreetNav = new StreetNavService();
            $whereArr=array(['id','=',$idd]);
            if($this->adminUser['area_type']==1){
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('nav_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('nav_type','=',0);   //街道
            }
            if($cfrtype=='serviceCentre'){
                $whereArr[]=array('cat_id','=',$cat_id);
                $whereArr[]=array('xtype','=',1);
            }else{
                $whereArr[]=array('cat_id','=',0);
                $whereArr[]=array('xtype','=',0);
            }
            $ret = $serviceStreetNav->delOneStreetServiceNav($whereArr);
            
            return api_output(0, array('opt_ret'=>$ret));
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    public function addStreetServiceNavCat()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        $cat_name = $this->request->post('cat_name','','trim');
        $xstatus = $this->request->post('xstatus','','intval');
        $xsort = $this->request->post('xsort',0,'intval');
        $level_show = $this->request->post('level_show','','intval');

        $data = [
            'cat_name'=>$cat_name,
            'xsort'=>$xsort,
            'xstatus'=>$xstatus,
            'level_show'=>$level_show,
        ];
        try {
            $serviceStreetNav = new StreetNavService();
            $whereArr=array();
            if($this->adminUser['area_type']==1){
                $data['community_id']=$this->adminUser['area_id'];
                $data['street_id']=$this->adminUser['area_pid'];
                $data['cat_type']=1; //社区
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('cat_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $data['street_id']=$street_id;
                $data['community_id']=0;
                $data['cat_type']=0; //街道
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('cat_type','=',0);   //街道
            }
            $res = $serviceStreetNav->saveOneStreetServiceNavCat($data,$idd,$whereArr);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }
    public function getStreetServiceNavCatInfo(){
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output_error(1001,'记录数据ID不存在！');
        }
        $serviceStreetNav = new StreetNavService();
        try {
            $whereArr=array(['id','=',$idd]);
            if($this->adminUser['area_type']==1){
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('cat_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('cat_type','=',0);   //街道
            }
            $field='id,cat_type,cat_name,level_show,xstatus,xsort';
            $dataInfo = $serviceStreetNav->getOneStreetServiceNavCat($whereArr,$field,'id DESC');
            if(empty($dataInfo)){
                return api_output_error(1001,'编辑数据不存在！');
            }
            $returnArr=array('detail'=>$dataInfo);
            return api_output(0, $returnArr);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getStreetServiceNavCatList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $page = $this->request->post('page',1,'intval');
        $cat_name = $this->request->post('cat_name','','trim');
        $page=$page>0 ? $page:1;
        $limit = 10;
        $serviceStreetNav = new StreetNavService();
        try {
            $whereArr=array();
            if($this->adminUser['area_type']==1){
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('cat_type','=',1);   //社区
            }else if($this->adminUser['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('cat_type','=',0);   //街道
            }
            $whereArr[]=array('del_time','<',10);
            if($cat_name){
                $whereArr[]=array('cat_name','like','%'.$cat_name.'%');
            }
            $listData = $serviceStreetNav->getStreetServiceNavCatList($whereArr,'*','xsort desc,id DESC',$page,$limit);
            return api_output(0, $listData);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function delStreetServiceCatNav(){
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'街道参数错误，请重新登录！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output_error(1001,'记录数据ID不存在！');
        }
        try {
            $serviceStreetNav = new StreetNavService();
            $whereArr=array(['id','=',$idd]);
            $whereNavArr=array(['cat_id','=',$idd],['xtype','=',1]);
            if($this->adminUser['area_type']==1){
                $whereArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereArr[]=array('cat_type','=',1);   //社区

                $whereNavArr[]=array('community_id','=',$this->adminUser['area_id']);
                $whereNavArr[]=array('nav_type','=',1);
            }else if($this->adminUser['area_type']==0){
                $whereArr[]=array('street_id','=',$street_id);
                $whereArr[]=array('community_id','=',0);
                $whereArr[]=array('cat_type','=',0);   //街道
                $whereNavArr[]=array('street_id','=',$street_id);
                $whereNavArr[]=array('community_id','=',0);
                $whereNavArr[]=array('nav_type','=',0);
            }
            $saveData=array('del_time'=>time());
            $ret = $serviceStreetNav->delOneStreetServiceNavCat($whereArr,$saveData);
            if($ret){
                $serviceStreetNav->editOneStreetServiceNav($whereNavArr,array('update_time'=>time(),'xstatus'=>4));
            }
            return api_output(0, array('opt_ret'=>$ret));
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
}