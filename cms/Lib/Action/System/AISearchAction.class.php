<?php


class AISearchAction extends BaseAction
{
    public function index()
    {
        $mod = M('ai_keywords');
        $where = [];
        $keyword = I('get.keyword', '', 'trim');
        if ($keyword) {
            $where['word'] = ['LIKE', '%' . $keyword . '%'];
        }
        $count_user = $mod->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 20);
        $words = $mod->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $typeNames = [
            '0' => C('config.group_alias_name'),
            '1' => C('config.meal_alias_name'),
            '2' => C('config.shop_alias_name'),
            '3' => C('config.appoint_alias_name'),
            '4' => '自定义链接'
        ];
        $this->assign('typeNames', $typeNames);
        $this->assign('words', $words);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function add()
    {
        $id = I('get.id', 0, 'intval');
        $thisWord = [];
        if ($id > 0) {
            $thisWord = M('ai_keywords')->find($id);
        }
        $this->assign('thisWord', $thisWord);
        $this->display();
    }

    public function save()
    {
        $mod = M('ai_keywords');
        $id = I('post.id', 0, 'intval');
        $data = $mod->create($_POST);
        if ($id > 0) {
            //修改
            $res = $mod->where(['id' => $id])->save($data);
        } else {
            //新增
            $res = $mod->add($data);
        }
        if ($res === false) {
            $this->error('添加失败！请检查是否重复添加');
        } else {
            $this->success('添加成功！');
        }
    }

    public function batchAdd()
    {
        if ($_POST) {
            $words = array_filter(explode(PHP_EOL, $_POST['words_list']));
            $mod = M('ai_keywords');
            if (empty($words)) {
                $this->error('请输入关键词');
            }
            foreach ($words as $w) {
                $data = [
                    'word' => $w,
                    'type' => $_POST['type'],
                    'sort' => $_POST['sort']
                ];
                $mod->add($data);
            }
            $this->success('添加成功！');
        }
        $this->display();
    }

    public function del()
    {
        $id = I('post.id', 0, 'intval');
        if ($id > 0) {
            M('ai_keywords')->where('id', $id)->delete();
        }
        $this->success('删除成功');
    }
}
