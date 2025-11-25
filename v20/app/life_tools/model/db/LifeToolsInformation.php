<?php

/**
 * 资讯model
 */

namespace app\life_tools\model\db;

use think\Model;

class LifeToolsInformation extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    public function getAddTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['add_time']);
    }
    public function getStartTimeTextAttr($value, $data)
    {
        return date('Y/m/d', $data['start_time']);
    }
    public function getEndTimeTextAttr($value, $data)
    {
        return date('Y/m/d', $data['end_time']);
    }

    public function getContentHtmlAttr($value, $data)
    {
        return htmlspecialchars_decode($data['content']);
    }

    public function getImagesArrAttr($value, $data)
    {
        $imagesArr = [];
        if ($data['images']) {
            $images = explode(',', $data['images']);
            foreach ($images as $image) {
                $temp['url'] = replace_file_domain($image);
                $temp['data'] = $image;
                $imagesArr[] = $temp;
            }
        }
        return $imagesArr;
    }
  
    public function getContAttr($value, $data)
    {
        $content = $data['content'];
        if($content){
            //显示的字数
            $num = 50;
            $content = htmlspecialchars_decode($content); 
            $content = preg_replace("/\s+/", '', $content); 
            $content = strip_tags($content);
            $content = mb_strlen($content, 'utf-8') > $num ? mb_substr($content, 0, $num, "utf-8").'...' : mb_substr($content, 0, $num, "utf-8");
        } 
        return $content;
    }
}
