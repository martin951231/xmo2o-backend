<?php

declare(strict_types=1);

namespace app\api\controller;

use app\Request;
use think\facade\Db;

class KeywordsController
{
    private function apiOutput($code = 0, $data = [], $msg = '')
    {
        $output = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        return json($output)->code(200);
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $content = request()->param('content', '', 'trim');
        if (empty($content)) {
            return $this->apiOutput(1001, [], '参数不能为空');
        }

        $words = Db::name('ai_keywords')->field('word,type,link')->order('sort DESC')->select()->toArray();
        //type  0:团购  1：餐饮  2：外卖  3：预约
        $siteUrl = cfg('site_url');
        $search = [];
        $replace = [];
        foreach ($words as $v) {
            if ($v['link']) {
                $jumpUrl = $v['link'];
            } else {
                if ($v['type'] == 0) { //团购
                    $jumpUrl = $siteUrl . '/wap.php?g=Wap&c=Groupnew&a=search&key=' . $v['word'];
                } else if ($v['type'] == 1) { //餐饮
                    $jumpUrl = get_base_url('pages/foodshop/search/searchList?keyword=') . $v['word'];
                } else if ($v['type'] == 2) { //外卖
                    $jumpUrl = get_base_url('pages/shop_new/search/search?keywords=') . $v['word'];
                } else if ($v['type'] == 3) { //预约
                    $jumpUrl = get_base_url('pages/appoint/goods/searchCate?name=') . $v['word'];
                } else {
                    continue;
                }
            }
            $search[] = $v['word'];
            $replace[] = sprintf('<a href="%s">%s</a>', $v['link'] ?: $jumpUrl, $v['word']);
        }
        $afterContent = str_replace($search, $replace, $content);
        return $this->apiOutput(1000, ['content' => $afterContent], '替换成功');
    }
}
