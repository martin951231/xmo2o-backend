<?php

class UploadAction extends BaseAction
{
    public function ajaxKfPic()
    {
        if ($_FILES['imgFile']['error'] != 4) {
            $path = $_GET['path'] ? $_GET['path'] : 'merchant';
            $image = PD('Image')->handle(0, 'kefu', 4);
            if ($image['error']) {
                json($image);
            } else {
                $title = $image['title']['imgFile'];
                json(array('error' => 0, 'url' => file_domain() . $image['url']['file'], 'title' => $title));
            }
        } else {
            json(array('error' => 1, 'message' => L_('没有选择图片')));
        }
    }
}