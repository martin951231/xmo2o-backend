<?php

namespace app\social_activity\model\service;

use app\common\model\db\Config;
use app\common\model\service\CacheSqlService;
use app\social_activity\model\db\SocialActivityAdver;
use app\social_activity\model\db\SocialActivityAdverCategory;

class DecorationService
{
    /**
     * 获取装修渲染的信息
     */
    public function getUrlAndRecSwitch()
    {
        $url = get_base_url('pages/socialActivity/index');

        $social_info = (new Config())->where([
            ['name', 'in', ['social_activity_slider_num', 'social_activity_slider_row', 'social_activity_hot_open', 
	            'social_activity_show_previous', 'social_activity_footer_font_color',  'social_activity_main_color',
	            'social_activity_footer_select_font_color', 'social_activity_footer_bg_color',
            ]],
        ])->column('value', 'name');
        return [
            'url' => $url,
            'social_activity_slider_num' => $social_info['social_activity_slider_num'] ?? '',
            'social_activity_slider_row' => $social_info['social_activity_slider_row'] ?? '',
            'social_activity_hot_open' => !empty($social_info['social_activity_hot_open']) ? true : false,
            'social_activity_show_previous' => !empty($social_info['social_activity_show_previous']) ? true : false,
            'social_activity_footer_font_color' => $social_info['social_activity_footer_font_color'] ?? '',
            'social_activity_footer_select_font_color' => $social_info['social_activity_footer_select_font_color'] ?? '',
            'social_activity_footer_bg_color' => $social_info['social_activity_footer_bg_color'] ?? '',
            'social_activity_main_color' => $social_info['social_activity_main_color'] ?? '',
        ];
    }

    /**
     * 获取列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAdverList($params)
    {
        $catKey = $params['cat_key'] ?? '';
        if (!$catKey) {
            throw new \think\Exception('请选择分类');
        }
        $cat_id = (new SocialActivityAdverCategory())->where('cat_key', '=', $catKey)->value('cat_id');
        if (!$cat_id) {
            throw new \think\Exception('分类不存在');
        }
        $adverModel = new SocialActivityAdver();
        $list = $adverModel->where(['cat_id' => $cat_id])->order('sort desc, id desc')->select()->toArray();

        return ['data' => $list, 'cat_key' => $catKey];
    }

    /**
     * 获取详情
     * @param int $adverId
     * @return array|null
     */
    public function getAdverDetail($id)
    {
        if (!is_numeric($id) || $id < 1) {
            throw new \think\Exception('ID参数错误');
        }

        $adverModel = new SocialActivityAdver();
        $result = $adverModel->where(['id' => $id])->findOrEmpty()->toArray();

        if (!$result) {
            throw new \think\Exception('信息不存在');
        }

        return $result;
    }

    /**
     * 保存
     * @param array $data
     * @param int|null $adverId
     * @return int|bool
     */
    public function saveAdver($data)
    {
        $id = $data['id'] ?? 0;
        $cat_key = $data['cat_key'] ?? '';
        unset($data['cat_key']);
        unset($data['id']);
        // 验证数据
        if (empty($data['name'])) {
            throw new \think\Exception('请输入标题');
        }

        if (empty($data['pic'])) {
            throw new \think\Exception('请上传图片');
        }

        $adverModel = new SocialActivityAdver();

        $date = date('Y-m-d H:i:s');
        $data['last_time'] = $date;
        try {
            if ($id) {
                if (!is_numeric($id) || $id < 1) {
                    throw new \think\Exception('ID参数错误');
                }

                $exists = $adverModel->where('id', '=', $id)->find();
                if (!$exists) {
                    throw new \think\Exception('信息不存在');
                }
                $result = $adverModel->where('id', '=', $id)->save($data);
            } else {
                if (!$cat_key) {
                    throw new \think\Exception('请选择分类');
                }

                $cat_id = (new SocialActivityAdverCategory())->where('cat_key', '=', $cat_key)->value('cat_id');
                if (!$cat_id) {
                    throw new \think\Exception('分类不存在');
                }
                $data['cat_id'] = $cat_id;
                $data['create_time'] = $date;
                $result = $adverModel->insertGetId($data);
            }
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 删除
     * @param int $adverId
     * @return bool
     */
    public function deleteAdver($id)
    {
        if (!is_numeric($id) || $id < 1) {
            throw new \think\Exception('ID参数错误');
        }

        $adverModel = new SocialActivityAdver();

        $exists = $adverModel->where(['id' => $id])->find();
        if (!$exists) {
            throw new \think\Exception('信息不存在');
        }

        try {
            $result = $adverModel->where('id', '=', $id)->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 修改导航栏布局
     */
    public function changeSelect($params)
    {
        $name = $params['name'] ?? '';
        $value = $params['value'] ?? '';
        if (!$name || (!$value && $value != 0)) {
            throw new \think\Exception('参数错误');
        }
        $configModel = new Config();
        try {
            $configModel->where('name', '=', $name)->update(['value' => $value]);
            (new CacheSqlService())->clearCache();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 获取顶部背景图片
     * @return string
     */
    public function getTopBgPic()
    {
        $cat_id = (new SocialActivityAdverCategory())->where('cat_key', '=', 'top_bg_pic')->value('cat_id');
        if (!$cat_id) {
            return '';
        }

        $pic = (new SocialActivityAdver())
            ->where(['cat_id' => $cat_id])
            ->order('sort desc, id desc')
            ->value('pic');

        return $pic ? replace_file_domain($pic) : '';
    }
}
