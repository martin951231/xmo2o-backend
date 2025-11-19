<?php
/**
 * MallImage.php
 * 商城图库
 * Create on 2020/10/28 17:56
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallImage extends Model
{
    /**
     * @param $data
     * @return int|string
     * 上传时记录图片
     */
    public function addImage($data)
    {
        return $this->insert($data);
    }

    /**
     * @param $where
     * @return array
     * 根据店铺id获取图片
     */
    public function getImageByStoreId($where)
    {
        $arr = $this->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function findVideoPreview($videoUrl = '')
    {
        $baseName = pathinfo($videoUrl, PATHINFO_BASENAME);
        $thisVideo = $this->where([['type', '=', 'video'], ['url', 'like', '%' . $baseName . '%']])->findOrEmpty();
        $arr = array_filter(explode(';', $thisVideo->url));
        return count($arr) >= 2 ? $arr[1] : '';
    }
}
