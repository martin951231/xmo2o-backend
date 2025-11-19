<?php


class National_phoneAction extends BaseAction {

    /**
     * 区号列表
     */
    public function index(){
        $where = array();
        $name = !empty($_GET['keyword']) ? $_GET['keyword'] : '';
        if($_GET['searchtype']=='code') {
            $where['code'] =  $name;
        }else if($_GET['searchtype']=='national'){

            $where['national'] = array('like','%' . $name .'%');
        }
        $count = M('National_phone')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,15);
        $list = M('National_phone')->where($where)->limit($p->firstRow,$p->listRows)->select();

        $this->assign('list',$list);

        $page = $p->show();
        $this->assign('page',$page);
        $this->display();
    }

    public function create(){
        $this->display();
    }

    /**
     * 创建区号
     */
    public function add(){
        $data['code'] = $_POST['code'];
        $data['national'] = $_POST['national'];
        $data['status'] = $_POST['status'];
        $data['sort'] = $_POST['sort'];
        $data['length'] = $_POST['length'];
        $data['match'] = $_POST['match'];
        if(!$_POST['id'] && M('National_phone')->where(array('code'=>$data['code'],'national'=>$_POST['national']))->find()){
            $this->frame_submit_tips(0,'区号重复');
        }
        if($_POST['id']){
            $result = M('National_phone')->where(array('id'=>$_POST['id']))->data($data)->save();
            $data['id'] = $_POST['id'];
            D('Lang')->edit_lang_data('National_phone',$data);
        }else{
            $result = D('National_phone')->data($data)->add();
            $data['id'] = $result;
            D('Lang')->add_lang_data('National_phone',$data);
        }

        if($result !== false){
			S('app_national_list',NULL);
            $this->frame_submit_tips(1,'编辑成功！');
        }else{
            $this->frame_submit_tips(0,'编辑失败！请重试~');
        }
    }

    public function edit(){
        $id = intval($_GET['id']);
        $national_phone = D('National_phone')->where(array('id'=>$id))->find();
        $this->assign('national_phone',$national_phone);
        $this->display('create');
    }


    public function delete(){
        $id = intval($_POST['id']);
        if(D("National_phone")->where(array('id'=>$id))->delete()){
			S('app_national_list',NULL);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败');
        }
    }
}
