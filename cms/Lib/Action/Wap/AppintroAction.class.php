<?php

class AppintroAction extends BaseAction
{
    public function intro()
    {
        $intro = D(_view('Appintro'))->where('id=' . $_GET['id'])->find();
        $src = '<img src="' . file_domain() . '/';
        $intro['content'] = str_replace('<img src="/', $src, $intro['content']);
        $intro['content'] = str_replace('<img alt="" src="/', $src, $intro['content']);
        $intro['content'] = replace_file_domain($intro['content']);
        $this->assign('intro', $intro);
        $this->display();
    }
}