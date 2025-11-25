<?php

/**
 * 地图icon
 * @author 张涛
 * @date 2020/4/27
 */
class MapIconModel extends Model
{
    /**
     * 生成用户icon
     * @author 张涛
     * @date 2020/4/27
     */
    public function createUserMapIcon($uid)
    {
        $avatar = M('User')->where(['uid' => $uid])->getField('avatar');
        if (empty($avatar)) {
            $avatar = C('config.site_url') . '/static/images/user_avatar.jpg';
        }
        $pic = $this->buildDeliveryMapIcon($avatar);
        return $pic;
    }

    /**
     * 生成店铺icon
     * @author 张涛
     * @date 2020/4/27
     */
    public function createStoreMapIcon($storeId)
    {
        $logo = M('Merchant_store')->where(['store_id' => $storeId])->getField('logo');
        if (empty($logo)) {
            return '';
        }
        $pic = $this->buildDeliveryMapIcon($logo);
        return $pic;

    }

    /**
     * 生成圆角图片，暂仅为订单详情地图icon生成所用
     * @param $originPic  源文件
     * @param $targetPic  目标文件
     * @param $r  半径
     * @return string
     * @author 张涛
     * @date 2020/04/27
     */
    public function resize2circle($originPic, $targetPic, $r)
    {
        $info = getimagesize($originPic);
        if (!$info) {
            return '';
        }
        $originWidth = $info[0];
        $originHeight = $info[1];
        $left = intval(($originWidth - 2 * $r) / 2);
        $top = intval(($originHeight - 2 * $r) / 2);

        $src = imagecreatefromstring(file_get_contents($originPic));
        $newpic = imagecreatetruecolor(2 * $r, 2 * $r);
        imagealphablending($newpic, false);
        $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
        for ($x = $left; $x < $originWidth; $x++) {
            for ($y = $top; $y < $originHeight; $y++) {
                $c = imagecolorat($src, $x, $y);
                $_x = $x - $r - $left;
                $_y = $y - $r - $top;
                if ((($_x * $_x) + ($_y * $_y)) < ($r * $r)) {
                    imagesetpixel($newpic, $x - $left, $y - $top, $c);
                } else {
                    imagesetpixel($newpic, $x - $left, $y - $top, $transparent);
                }
            }
        }
        imagesavealpha($newpic, true);
        imagepng($newpic, $targetPic);
        imagedestroy($newpic);
        imagedestroy($src);
        return $targetPic;
    }

    /**
     * 生成地图icon
     * @param $pic
     * @return string
     * @author 张涛
     * @date 2020/04/28
     */
    public function buildDeliveryMapIcon($pic)
    {
        try {
            require_once LIB_PATH . 'ORG/Image.class.php';
            $imageClass = new Image();
            $uploadDir = './upload/map_icon/' . date('Ymd') . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            //已存在，则直接返回
            $icon = $this->where(['md5' => md5($pic), 'type' => 'deliver'])->find();
            if ($icon) {
                return $icon['icon_url'];
            }

            //生成圆形图片
            $headImage = Image::thumb2(replace_file_domain($pic), $uploadDir . md5($pic) . '.png', '', 175, 175);
            $circleHeadImage = $uploadDir . md5($pic) . '_circle.png';
            $watermarkfile = $this->resize2circle($headImage, $circleHeadImage, 87.5);

            //合成图片
            $image_water_mark = new image_water_mark();
            $watermark = [
                'watermarkstatus' => 1,
                'watermarktype' => 'png',
                'watermarkfile' => $watermarkfile,
                'watermarkminwidth' => 175,
                'watermarkminheight' => 175,
                'watermarkquality' => 90,
                'watermarktrans' => 100,
                'position' => [12, 12]
            ];
            $target = $uploadDir . md5($pic) . '_icon.png';
            $mapIconTpl = './static/images/map_icon_template.png';
            $image_water_mark->Watermark($mapIconTpl, $watermark, $target);

            //同步oss
            $file_handle = new file_handle();
            $file_handle->upload($target);
            $data = [
                'md5' => md5($pic),
                'origin_url' => $pic,
                'icon_url' => $target,
                'add_time' => time(),
                'type' => 'deliver'
            ];
            $this->add($data);
            return $target;
        } catch (\Exception $e) {
            fdump_sql($e->getMessage(), 'buildDeliveryMapIconFail');
            return '';
        }
    }
}