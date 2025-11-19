<?php

class Third_robot_delivery_orderModel extends Model{
    /**
     * 获取对应机器人图片.
     */
    public function getRobotImg($robot_type, $static = '')
    {
        if (!$static) {
            $siteUrl = C('config.site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/robot_type/';
        }
        switch ($robot_type) {
            case 'CloudFarming':
                $robot_img = $static . 'CloudFarming.png';
                break;
            case 'cleaning':
                $robot_img = $static . 'cleaning.png';
                break;
            case 'ginger':
                $robot_img = $static . 'ginger.png';
                break;
            case 'cloudia':
                $robot_img = $static . 'cloudia.png';
                break;
            case 'vending':
                $robot_img = $static . 'vending.png';
                break;
            case 'pepper':
                $robot_img = $static . 'pepper.png';
                break;
            case 'CloudIRIS':
                $robot_img = $static . 'CloudIRIS.png';
                break;
            case 'ginger-lite':
            case 'CloudGingerLite':
                $robot_img = $static . 'CloudGingerLite.png';
                break;
            case 'MoverMini':
            case 'MoverMiniD':
                $robot_img = $static . 'MoverMini.png';
                break;
            case 'patrol':
                $robot_img = $static . 'patrol.png';
                break;
            case 'seeds':
                $robot_img = $static . 'seeds.png';
                break;
            default:
                $robot_img = $static . 'ginger.png';
                break;
        }
        return $robot_img;
    }
}