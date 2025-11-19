<?php
/**
 * 【平台首页&&外卖首页】发现好菜分类自定义管理功能
 */
class Diy_categoryAction extends BaseAction
{

    public function index(){

        $type = intval($_GET['type']);
        $type = $type?$type:1;
        if(!in_array($type, [1,2])){
            $this->error('类型错误！');
        }

        $this->assign('type',$type);


        $model = D('Diy_category');

        $where = ['is_del'=>0];
        $where['type'] = $type;

        $count = $model->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $model->field(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        if($list){
            $area = D('Area')->get_area_key_value();
            foreach ($list as $key => $value) {
                $list[$key]['city_name'] = $area[$value['city_id']];
                $list[$key]['goods_count'] = $value['goods_ids']?count(explode(',', $value['goods_ids'])):0;
            }
        }
        
        $this->assign('list', $list);
        $this->display();
    }

    public function add(){
        $type = intval($_GET['type']);
        $this->assign('type',$type);
        $this->display();
    }

    public function edit()
    {
        $pigcms_id = intval($_GET['id']);
        $model = D('Diy_category');
        $now_group = $model->where(array('pigcms_id' => $pigcms_id))->find();
        if ($now_group['city_id']) {
            $now_city = D('Area')->where(array('area_id' => $now_group['city_id']))->find();
            $now_group['province_id'] = $now_city['area_pid'];
        }
        $this->assign('now_group', $now_group);
        $this->display();
    }

    public function modify()
    {
        $_POST['begin_time'] = strtotime($_POST['begin_time']);
        $_POST['end_time'] = strtotime($_POST['end_time']);
        $_POST['city_id'] = $_POST['city_idss'];
        $_POST['province_id'] = $_POST['province_idss'];

        $model = D('Diy_category');
        if ($activity_id = $model->data($_POST)->add()) {
            $this->frame_submit_tips(1,'添加成功！');
        } else {
            $this->frame_submit_tips(1,'添加失败，请重试~');
        }
    }

    public function del()
    {
        $where['pigcms_id'] = intval($_POST['pigcms_id']);

        $model = D('Diy_category');
        if ($activity_id = $model->where($where)->data(['is_del'=>1])->save()) {
            $this->success('删除成功！');
        } else {
           $this->error('删除失败！请重试~');
        }
    }

    public function amend()
    {
        $_POST['begin_time'] = strtotime($_POST['begin_time']);
        $_POST['end_time'] = strtotime($_POST['end_time']);
        $_POST['city_id'] = $_POST['city_idss'];
        $_POST['province_id'] = $_POST['province_idss'];
        
        $model = D('Diy_category');
        //dump($_POST);exit;
        if($model->data($_POST)->save() !== false){

            //$this->success('修改成功！',U('index',['type'=>$_POST['type']]));
            $this->frame_submit_tips(1,'修改成功！');
        } else {
            //$this->error('修改失败！请重试~');
            $this->frame_submit_tips(0,'修改失败！请重试~');

        } 
    }

    public function category_goods(){

        $model = D('Diy_category');

        $id = intval($_GET['category_id']);

        $info = $model->get_one($id);
        $this->assign('info',$info);

        /**
        $select_goods = $model->get_same_category_goods($info['type']);
        $this->assign('goods_ids',implode(',', $select_goods));**/

        $this->assign('goods_ids',$info['goods_ids']);

        $this->display();
    }

    public function category_goods_post(){
        $model = D('Diy_category');
        $result = $model->save_category_goods($_POST['category_id'],$_POST['relation_goods_id']);
        if($result){
            $this->success('保存成功！');
        }else{
            $this->error('保存失败！请重试~');
        }
    }

    


    
}
