<?php


namespace app\mall\controller\merchant;


use app\BaseController;
use app\mall\model\db\Area;
use app\mall\model\db\ExpressTemplate;
use app\mall\model\db\ExpressTemplateArea;
use app\mall\model\db\ExpressTemplateValue;
use app\merchant\controller\merchant\AuthBaseController;
use kdniao;

class ExpressTemplateController extends AuthBaseController
{
    public function index()
    {
        //901
        $merId = $this->merchantUser['mer_id'];
        //$merId=901;
        $where = [['mer_id','=',$merId]];
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $templates=(new ExpressTemplate())->getETByMerId($where,$page,$pageSize);

        $list = array();
        $list1 = array();
        $tids=array();
        if(empty($templates['list'])){
            return api_output(1000, $list1);
        }
        foreach ($templates['list'] as $row) {
            $tids[] = $row['id'];
            $row['value_list'] = null;
            $list[$row['id']] = $row;
            $list[$row['id']]['dateline']=date("Y-m-d H:i:s",$row['dateline']);
        }
        $assign['count']=$templates['total_count'];
        $where_area=[['tid','in',$tids]];
        $areas =(new ExpressTemplateArea())->getSome($where_area)->toArray();
        $area_list = array();
        foreach ($areas as $arow) {
            $area_list[$arow['vid']][] = $arow;
        }
        $values =(new ExpressTemplateValue())->getSome($where_area)->toArray();
        foreach ($values as $k=>$v) {
            $addr_to=array();
            if(isset($area_list[$v['id']])){
                foreach($area_list[$v['id']] as $k1=>$v1){
                    $addr_to[]=$v1['area_name'];
                }
                if(!empty($addr_to)){
                    $v['addr_to']=implode(",",$addr_to);
                }else{
                    $v['addr_to']=[];
                }

                if($list[$v['tid']]['freight_type']==1){
                    $v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
                    $v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
                    $v['add_weight'] = floatval($v['add_weight']);
                }
                $list[$v['tid']]['value_list'][] = $v;
            }
        }
        foreach ($list as $ky=>$vl){
            $list1[]=$vl;
        }
        $assign['list']=$list1;
        return api_output(1000, $assign);
    }

    public function add()
    {
        $this->display('edit');
    }

    public function edit()
    {
        $tid = $this->request->param('tid', 0, 'intval');
        $merId = $this->merchantUser['mer_id'] ?? 0;
        $where=[['mer_id','=',$merId],['id','=',$tid]];
        $template =(new ExpressTemplate())->getOne($where);
        $template['mer_id']=$merId;
        if (empty($template)) {
            $this->error(L_('不合法的模板信息！'));
        }
        $where_areas1=[['tid','=',$tid]];
        $areas =(new ExpressTemplateArea())->getSome($where_areas1)->toArray();
        $area_list = array();
        $already_check_area_id=array();
        $already_check_area_id1=array();
        foreach ($areas as $arow) {
            $area_list[$arow['vid']][] = $arow;
        }
        $where_value=[['tid','=',$tid]];
        $values =(new ExpressTemplateValue())->getSome($where_value)->toArray();
        foreach ($values as $v) {
            $arrs=array();
            $arrs_id=array();
            if(isset($area_list[$v['id']])){
                foreach ($area_list[$v['id']] as $key=>$val){
                    $arrs[]=$val['area_name'];
                    $arrs_id[]=$val;
                    $already_check_area_id1[]=$val['area_id'];
                    $already_check_area_id[]=$val['area_id']."";
                }
            }else{
                $arrs = [];
            }
            if(!empty($arrs)){
                $v['name'] =implode(",",$arrs);
            }else{
                $v['name'] ="";
            }
            /*if($template['freight_type']==1){*/
                $v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
                $v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
                $v['add_weight'] = floatval($v['add_weight']);
         /*   }*/
            $v['areas'] =$arrs_id;
            $template['value_list'][] = $v;
        }
        $template['already_check']=$already_check_area_id;
        $condition_area_prov=[
            ['area_type','=',2],
            ['area_id','in',$already_check_area_id1],
        ];
        $order="area_sort DESC,area_id ASC";
        $field="area_pid";
        $database_area = new Area();
        $data = $database_area->getSome1($condition_area_prov,$field,$order)->toArray();
        $area_pid=[];
        if(!empty($data)){
            foreach ($data as $key1=>$val1){//把省的id也带上，给页面展示用
                $area_pid[]=$val1['area_pid']."";
            }
            $template['already_check']=array_merge($already_check_area_id,$area_pid);
        }

        return api_output(1000, $template);
    }

    /**
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 保存
     */
    public function save()
    {
        try{
        $tpl_id = $this->request->param('tpl_id', 0, 'intval');
        $name = $this->request->param("name", "", "trim");//排序
        $datas = $this->request->param("datas", "", "trim");//排序
        /*计费模式*/
        $freight_type=$this->request->param('freight_type', 0, 'intval');
        if (empty($name)) {
            $status['status']=0;
            $status['msg']="模板名不能为空";
            return api_output(1000, $status);
            //exit(json_encode(array('err_code' => 1, 'err_msg' => L_('模板名不能为空！'))));
        }
        $Express_template = new ExpressTemplate();

        $Express_template_value =new ExpressTemplateValue();

        $Express_template_area = new ExpressTemplateArea();
        if($tpl_id==0){
            $where=[['mer_id','=',$this->merchantUser['mer_id']],['name','=',$name]];
            $template = $Express_template->getOne($where);
            if ($template && $template['id'] != $tpl_id) {
                $status['status']=0;
                $status['msg']="模板名已存在";
                return api_output(1000, $status);
                //exit(json_encode(array('err_code' => 1, 'err_msg' => L_('模板名已存在！'))));
            }
        }
        $where1=[['mer_id','=',$this->merchantUser['mer_id']],['id','=',$tpl_id]];
        $template = $Express_template->getOne($where1);
        $tids = array();
        if (!empty($template)) {
            $data1['name']=$name;
            $data1['freight_type']=$freight_type;
            $data1['dateline']=time();
            if ($Express_template->updateOne($data1,$where1)) {
                $where_tval=[['tid','=',$tpl_id]];
                $template_value = $Express_template_value->getSome($where_tval)->toArray();
                foreach ($template_value as $tv) {
                    $tids[] = $tv['id'];
                }
                $where_template_area=[
                    ['tid','=',$tpl_id]
                ];
                $Express_template_area->where($where_template_area)->delete();
            } else {
                $tpl_id = 0;
            }
        }
        else {
            $add['name']=$name;
            $add['mer_id']=$this->merchantUser['mer_id'] ;
            $add['freight_type']=$freight_type;
            $add['dateline']=time();
            $tpl_id = $Express_template->addOne($add);
        }
        if ($tpl_id) {
            $database_area = new Area();
            foreach ($datas as $row) {
                if(isset($row['vid'])){
                    $vid = intval($row['vid']);
                }else{
                    $vid = 0;
                }
                $freight = $row['freight'];
                $first_weight = $row['first_weight'];
                $first_freight = $row['first_freight'];
                $add_weight = $row['add_weight'];
                $add_freight = $row['add_freight'];
                $data=array();
                $data['freight']=$freight;
                $data['first_weight']=$first_weight;
                $data['first_freight']=$first_freight;
                $data['add_weight']=$add_weight;
                $data['add_freight']=$add_freight;
                $data['tid']=$tpl_id;
                $data['dateline']=time();
                if ($vid) {
                    $where_tval=[['id','=',$vid]];
                    $Express_template_value->updateThis($where_tval,$data);
                    $tids = array_diff($tids, array($vid));
                } else {
                    $vid = $Express_template_value->add($data);
                }
                foreach ($row['areas'] as $aid) {
                    if(isset($aid['area_id'])){
                        $where_area1=[['area_id','=',$aid['area_id']]];
                    }else{
                        $where_area1=[['area_id','=',$aid]];
                    }
                    if($this->request->param('tpl_id', 0, 'intval')==0){//新增
                        if ($area = $database_area->getOne($where_area1)) {
                            $data2['tid']=$tpl_id;
                            $data2['area_id']=$area['area_id'];
                            $data2['area_name']=$area['area_name'];
                            $data2['vid']=$vid;
                            $Express_template_area->add($data2);
                        } elseif ($aid == 0) {
                            $Express_template_area->add(array('tid' => $tpl_id, 'area_id' => $area['area_id'], 'area_name' => L_('同城'), 'vid' => $vid));
                        }
                    }else{//修改

                        if ($area = $database_area->getOne($where_area1)) {
                            $data2['tid']=$tpl_id;
                            $data2['area_id']=$area['area_id'];
                            $data2['area_name']=$area['area_name'];
                            $data2['vid']=$vid;
                            $Express_template_area->add($data2);
                        } elseif ($aid == 0) {
                            $Express_template_area->add(array('tid' => $tpl_id, 'area_id' => $area['area_id'], 'area_name' => L_('同城'), 'vid' => $vid));
                        }
                    }
                }
            }
            if (!empty($tids)) {
                $where_del=[
                    ['id','in',$tids],
                    ['tid','=',$tpl_id]
                ];
                $Express_template_value->where($where_del)->delete();
            }
            $status['status']=1;
            $status['msg']="";
            return api_output(1000, $status);
        }
        else {
            $status['status']=1;
            $status['msg']="保存失败";
            return api_output(1000, $status);
        }
        }catch (\Exception $e){
            dd($e);
        }
    }

    public function delete()
    {
        $tpl_id = $this->request->param('tpl_id', 0, 'intval');
        $mer_id=$this->merchantUser['mer_id'];
        $Express_template = new ExpressTemplate();

        $Express_template_value =new ExpressTemplateValue();

        $Express_template_area = new ExpressTemplateArea();
        $where1=[['mer_id','=',$mer_id],['id','=',$tpl_id]];
        if ($Express_template->getOne($where1)) {
            $where_template=[
                ['id','=',$tpl_id]
            ];
            $Express_template->where($where_template)->delete();
            $where_template_value=[
                ['tid','=',$tpl_id]
            ];
            $Express_template_value->where($where_template_value)->delete();
            $Express_template_area->where($where_template_value)->delete();
            return api_output(1000, L_('删除成功！'));
        } else {
            return api_output(1003, L_('删除数据有误！'));
        }
    }

    public function detail(){
        $type = $this->request->param('type', 0, 'intval');
        if($type){
            $express_code = D('Express')->where(['code' => $_GET['type']])->getField('kuaidiniao_code');
        }else{
            $express_code = D('Express')->where(['name' => $_GET['com']])->getField('kuaidiniao_code');
        }

        $kdniao = new kdniao();

        $result = $kdniao->getOrderTracesByJson($express_code, $_GET['postid']);
        $this->assign('result', $result);
        $this->display();
    }

    /**
     * @return \json
     * 获取省市
     */
    public function ajax_area(){
        $database_area = (new Area());
        $condition_area_prov['area_type'] = 1;
        $vid=$this->request->param('vid','','intval');
        $where=[
            ['vid','=',$vid]
        ];
        $Express_template_area = new ExpressTemplateArea();
        if(!empty($vid)){
            $template_value = $Express_template_area->getSome($where)->toArray();
        }else{
            $template_value=[];
        }
        $condition_area_prov=[
            ['area_type','=',1],
            /*['area_id','=',104],*/
        ];
        $order="area_sort DESC,area_id ASC";
        $field="area_id,area_name,area_type";
        $data = $database_area->getSome($condition_area_prov,$field,$order)->toArray();
        $province_list = array();
        $provinces=array();
        $provinces_id=array();
        $data_pro=array();
        $data_check_pro=array();
        if(!empty($template_value)){
            foreach ($template_value as $ke=>$va){
                $provinces_id[]=$va['area_id'];
            }
            $condition_area_pro=[['area_id','in',$provinces_id]];
            $field2="area_pid";
            $data_pro = $database_area->getSome1($condition_area_pro,$field2,$order)->toArray();
            foreach ($data_pro as $values){
                $data_pid[]=$values['area_pid'];
            }
            //选中得省
            if($data_pid){
                $where_datda=[['area_id','in',$data_pid]];
                $field="area_id,area_name,area_type";
                $data_check = $database_area->getSome($where_datda,$field,$order)->toArray();
                foreach ($data_check as $data_chek_pro){
                    $data_chek_pro_id['id']=0;
                    $data_chek_pro_id['tid']=0;
                    $data_chek_pro_id['vid']=0;
                    $data_chek_pro_id['area_name']=$data_chek_pro['area_name'];
                    $data_chek_pro_id['area_id']=$data_chek_pro['area_id'];
                    $data_check_pro[]=$data_chek_pro_id;
                }
            }
        }

        foreach ($data as $key => $value) {
            $temp = array(
                'area_id' => $value['area_id'],
                'area_name' => $value['area_name'],
                'area_type' => $value['area_type']
            );
            $condition_area_city=[
                ['area_type','=',2],
                ['area_pid','=',$value['area_id']],
                ['area_ip_desc','<>',''],
            ];
 /*           $condition_area_city['area_pid'] = $value['area_id'];
            $condition_area_city['area_ip_desc'] = $value['area_id'];*/
            $data_city = $database_area->getSome($condition_area_city,$field,$order)->toArray();
            if(!empty($data_city)){
                $temp['son_list']=$data_city;
            }else{
                $temp['son_list']=[];
            }
            $province_list[] = $temp;
            /*$data_pro['area_id']=$value['area_id'];
            $data_pro['area_name']=$value['area_name'];
            $data_pro['area_type']=$value['area_type'];
            $provinces[]=$data_pro;*/
        }
        $data['province_list']=$province_list;
        $data['provinces_msg']=$provinces;
        //合并选中得省
        $jjc=array_merge($template_value,$data_check_pro);
        array_values($jjc);
        $data['area_list']=$jjc;//选中的数据
        return api_output(1000, $data);
    }


    public function get_area_name(){
        $database_area = (new Area());
        $area_arrs= $this->request->param('arrs');
        $my_arrs=array();
        if(!empty($area_arrs)){
            foreach ($area_arrs as $k=>$v){
                $my_arrs[]=$v;
            }
        }else{
            return api_output(1000, "");
        }

        $condition_area_prov=[
            ['area_type','=',2],
            ['area_id','in',$my_arrs],
        ];
        $order="area_sort DESC,area_id ASC";
        $field="area_id,area_name";
        $data = $database_area->getSome($condition_area_prov,$field,$order)->toArray();
        $province_list = array();
        $province_list1['id'] = time();
        foreach ($data as $key => $value) {
            $province_list[] = $value['area_name'];
        }
        $province_list1['area_name']=implode(',',$province_list);

        $condition_area_prov=[
            ['area_type','=',2],
            ['area_id','in',$my_arrs],
        ];
        $order="area_sort DESC,area_id ASC";
        $field="area_pid";
        $database_area = new Area();
        $data2 = $database_area->getSome1($condition_area_prov,$field,$order)->toArray();
        $area_pid=array();
        if(!empty($data2)){
            foreach ($data2 as $key1=>$val1){//把省的id也带上，给页面展示用
                $area_pid[]=$val1['area_pid']."";
            }
            $province_list1['area_pid']=$area_pid;
        }

        return api_output(1000, $province_list1);
    }

}