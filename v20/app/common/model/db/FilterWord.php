<?php


namespace app\common\model\db;

use think\facade\Cache;
use think\facade\Db;
use think\Model;

class FilterWord extends Model
{
    public function filter($content = '')
    {
        $cacheKey = 'filter_words';
        $filterWords = Cache::get($cacheKey);
        if (empty($filterWords)) {
            $filterWords = (new FilterWord())->where('1=1')->order(Db::raw('LENGTH(`word`) DESC'))->column('word');
            $filterWords = array_map(function ($r) {
                $rstr = trim($r);
                if (!preg_match("/^[" . chr(0x80) . "-" . chr(0xff) . "]+$/", $rstr)) {
                    $rstr = '';
                }
                return $rstr;
            }, $filterWords);
            $filterWords = array_filter($filterWords);
            Cache::set($cacheKey, $filterWords, 86400);
        }
        $filterWords && $content = str_replace($filterWords, '***', $content);
        return $content;
    }
}
