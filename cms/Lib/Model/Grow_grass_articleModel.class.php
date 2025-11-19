<?php

class Grow_grass_articleModel extends Model
{
    public function getArticleList(array $condition, int $page, int $pageSize, $lat='', $lng='')
    {
        $configData = D('Config_data')->where(['name' => 'index_guess_like_nearby'])->find();
        $field = "a.*,u.nickname,u.avatar";
        $where = 'a.is_del=0 AND a.is_system_del=0 AND a.is_manuscript=0 AND a.status=20';
        $have = '';
        if ($lat * 1 > 0 && $lng * 1 > 0) {
            $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`a`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`a`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`a`.`lng`*PI()/180)/2),2)))*1000) AS juli";
            $order = "juli ASC,a.publish_time DESC";
            if ($configData['value'] > 0) {
                // 附近范围，单位km
                $have = 'juli < ' . $configData['value'] * 1000;
            }
        } else {
            $order = "a.publish_time DESC";
        }
        // 查询
        $count = $this->alias('a')
            ->field($field)
            ->where($where)
            ->join('left join pigcms_user u on u.uid = a.uid')
            ->having($have)
            ->count();

        $list = $this->alias('a')
            ->field($field)
            ->where($where)
            ->join('left join pigcms_user u on u.uid = a.uid')
            ->order($order)
            ->limit(($page - 1) * $pageSize.','.$pageSize)
            ->having($have)
            ->select();

        return ['list' => $list, 'count' => $count];
    }

    public function formatData($list, int $uid = 0)
    {
        $cate_id_set = array_unique(array_column($list, 'category_id'));
        $cate_name_set = D('Grow_grass_category')->where(['category_id' =>['in', $cate_id_set]])->getField('category_id,name', true);

        foreach ($list as &$val) {
            // 封面图
            if (isset($val['video_img']) && $val['video_img']) {// 视频首图
                $val['image'] = thumb_img($val['video_img'], 240, 240, 'fill');// 文章图片
            } elseif (isset($val['img']) && $val['img']) {
                $images = explode(',', $val['img']);
                $val['image'] = thumb_img($images[0], 240, 240, 'fill');
            }
            $val['have_video'] = isset($val['video_url']) && $val['video_url'] ? true : false;// 是否有视频
            $val['avatar'] = (isset($val['avatar']) && $val['avatar']) ? replace_file_domain($val['avatar']) : $_SERVER['HTTP_ORIGIN'] . '/static/images/user-avatar.jpg';// 发布者头像
            $val['url'] = get_base_url('',1).'pages/wantToBuy/v1/articleDetail/index?article_id='.$val['article_id'];// 跳转链接
            $arr = D('Grow_grass_article_like')->where("uid={$uid} and article_id={$val['article_id']} and is_del=0")->count();
            $val['is_like'] = empty($arr) ? false : true;// 当前用户是否点赞
            $val['re_type'] = 'grass';
            $val['category_name'] = $cate_name_set[$val['category_id']];
        }

        return $list;
    }
}