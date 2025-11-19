<?php

/**
 * 快店评论图片上传
 */
class shop_order_reply_image
{
    public function get_image_by_path($path, $image_type = '-1')
    {
        $path = str_replace(',', '/', $path);
        $pathinfo = pathinfo($path);
        if (strstr($path, 'upload/') !== false) {
            //含有upload
            $dir = '';
        } else {
            $dir = '/upload/reply/';
        }
        if ($image_type == '-1') {
            $return['image'] = file_domain() . $dir . $path;
            $return['m_image'] = file_domain() . $dir . $pathinfo['dirname'] . '/m_' . $pathinfo['basename'];
            $return['s_image'] = file_domain() . $dir . $pathinfo['dirname'] . '/s_' . $pathinfo['basename'];
        } else {
            $return = file_domain() . $dir . $path;
        }
        return $return;
    }

    /**
     * 根据数据库分号分隔数据解析出图片地址
     * @author 张涛
     * @date 2020/4/2
     */
    public function get_allImage_by_path($path, $image_type = '-1')
    {
        $return = [];
        if (!empty($path)) {
            $tmp_pic_arr = explode(';', $path);
            foreach ($tmp_pic_arr as $key => $value) {
                $return[$key] = $this->get_image_by_path($value, $image_type);
            }
        }
        return $return;
    }
}