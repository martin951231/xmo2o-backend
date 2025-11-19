<?php
/**
 * 系统后台广告服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 09:23
 */

namespace app\common\model\service;
use app\common\model\db\Adver as AdverModel;
use app\common\model\service\AdverCategoryService as AdverCategoryService;
class AdverService {
    public $adverModel = null;
    public function __construct()
    {
        $this->adverModel = new AdverModel();
    }
   
    /**
     * 根据分类获广告列表
     * @param $catKey
     * @return array
     */
    public function getAdverByCatKey($catKey, $limit = 3, $needFormart = false) {
        // 当前城市
        $nowCity = cfg('now_city');
        
        // 获取缓存
        // $cache = cache();
        // $adverList = $cache->get('adverList_' . $catKey .'_'. $nowCity.'_'.$limit);
        // if (!empty($adverList)) {
		// 	$adverList = replace_domain($adverList);
        //     return $adverList;
        // }

        // 广告分类信息
        $nowAdverCategory = (new AdverCategoryService())->getAdverCategoryByCatKey($catKey);

        if (!$nowAdverCategory) {
            return [];
        }
        // 搜索条件
        $where = [
            'cat_id' => $nowAdverCategory['cat_id'],
            'status' => 1,
        ];

        $whereRaw = '';
        // 开启多城市
        if(customization('open_view_multi_city') && cfg('many_city')){
            $whereRaw = 'FIND_IN_SET('.$nowCity.',city_ids)';
        }elseif(cfg('many_city')){
            $where['city_id'] = $nowCity;
        }

        // 排序
        $order = [
            'complete' => 'DESC',
            'sort' => 'DESC',
            'id' => 'DESC',
        ];

        // 广告列表
        $adverList = $this->getAdverListByCondition($where,$order,$limit,$whereRaw);

        
        $imgCount = count($adverList);
        if(cfg('many_city')){
            // 开启多城市
            $enough = $limit - $imgCount;

            // 排序
            $order = [
                'sort' => 'DESC',
                'id' => 'DESC',
            ];

            if (empty($adverList)) {
                // 查询不绑定城市的
                $where['city_id'] = 0;
                // 广告列表
                $adverList = $this->getAdverListByCondition($where,$order,$limit);
            } elseif ($enough > 0 && $adverList[0]['complete'] == 1) {
                $where['city_id'] = 0;
                // 广告列表
                $complete = $this->getAdverListByCondition($where,$order,$enough);

                if ($complete) {
                    if ($adverList) {
                        $adverList = array_merge_recursive($adverList, $complete);
                    } else {
                        $adverList = $complete;
                    }
                }
            }
        }

        // 替换图片路径
        foreach ($adverList as $key => $value) {
            if(strpos($value['pic'],'/upload') === 0){
                $adverList[$key]['pic'] = replace_file_domain($value['pic']);
            }else{
                $adverList[$key]['pic'] = replace_file_domain('/upload/adver/' . $value['pic']);
            }
            $adverList[$key]['img_count'] = $imgCount;
        }

        // web版导航多城市
        if(cfg('many_city')){
            foreach ($adverList as $key => $value) {
                if (substr($value['url'], - 6) == 'nocity') {
                    $adverList[$key]['url'] = substr($value['url'], 0, strlen($value['url']) - 6);
                } else if (strpos($value['url'],'/wap.php') >= 0) {
                    $adverList[$key]['url'] = $value['url'];
                } else {
                    $adverList[$key]['url'] = str_replace(cfg('config_site_url'), cfg('now_site_url'), $value['url']);
                }
            }
        }

        if(!$adverList){
            $adverList = array();
        }elseif($needFormart){
            $adverList = $this->formatAdver($adverList);
        }

        // 存入缓存
        // $cache->set('adverList_' . $catKey .'_'. $nowCity.'_'.$limit, $adverList, 3600);

        $adverList = replace_domain($adverList);
        return $adverList;
    }

    /**
     * 去掉不要的字段
     * @param $array 广告列表
     * @return array
     */
    public function formatAdver($array){
		foreach($array as &$adver_value){
			unset($adver_value['id']);
			unset($adver_value['bg_color']);
			unset($adver_value['cat_id']);
			unset($adver_value['status']);
			unset($adver_value['last_time']);
			unset($adver_value['sort']);
			unset($adver_value['province_id']);
			unset($adver_value['city_id']);
			unset($adver_value['complete']);
			unset($adver_value['img_count']);
		}
		return $array;
	}


    /**
     * 根据条件获取广告列表
     * @return array
     */
	public function getAdverListByCondition($where, $order = [], $limit = 0,$whereRaw=''){
		$adverList = $this->adverModel->getAdverListByCondition($where, $order, $limit,$whereRaw);
		if(!$adverList) {
            return [];
        }
		return $adverList->toArray();
	}
}