<?php

class AppintroAction extends BaseAction
{

    public function index()
    {
        $intro = D(_view('Appintro'))->select();
        $this->assign('intro', $intro);
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            $data['title'] = $_POST['title'];
            if (empty($_POST['content'])) {
                $this->error('内容不能为空！');
            }
            $data['content'] = htmlspecialchars_decode($_POST['content']);
            if (D('Appintro')->add($data)) {
                //多语言表增加数据
                $_POST['id'] = M('Appintro')->getLastInsID();
                D('Lang')->add_lang_data('Appintro',$_POST);

                $this->success('添加公告成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            $this->display();
        }
    }

    public function edit()
    {
        if (IS_POST) {
            $data['title'] = $_POST['title'];
            $data['content'] = htmlspecialchars_decode($_POST['content']);
            if (D('Appintro')->where('id=' . $_POST['id'])->save($data)) {
                //多语言表修改数据
                D('Lang')->edit_lang_data('Appintro',$_POST);

                $this->success('保存成功！');
            } else {
                $this->error('保存失败！');
            }
        } else {
            $intro = D(_view('Appintro'))->where('id=' . $_GET['id'])->select();
            $this->assign("intro", $intro[0]);

            $this->display();
        }
    }

    public function del()
    {
        if (!empty($_POST['id'])) {
            if (D('Appintro')->where('id=' . $_POST['id'])->delete()) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败！');
            }
        }
    }
}