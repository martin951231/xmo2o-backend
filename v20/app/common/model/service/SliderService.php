<?php
/**
 * 系统后台导航服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 11:47
 */

namespace app\common\model\service;
use app\common\model\db\Slider as SliderModel;
use app\common\model\service\SliderCategoryService as SliderCategoryService;
use think\facade\Db;

class SliderService {
    public $sliderModel = null;
    public function __construct()
    {
        $this->sliderModel = new SliderModel();
    }
    
    /**
     * 通过导航分类的KEY获取到导航列表
     * @param $catKey
     * @return array
     */
	public function getSliderByCatKey($catKey, $limit = 20, $needFormart=false){
        
        // 导航分类信息
        $nowSliderCategory = (new SliderCategoryService())->getSliderCategoryByCatKey($catKey);
        
        // 分类不存在
		if(!$nowSliderCategory){
			return [];
        }
			
        // 搜索条件
        $where = [
            ['cat_id' , '=', $nowSliderCategory['cat_id']],
            ['status', '=' , 1],
        ];

        // 开启多城市
        if(cfg('many_city') && cfg('now_city')){
            //$where[] = ['city_id' ,'IN',[0, cfg('now_city')]];
            $where[] = Db::raw('city_id=0 OR FIND_IN_SET(' . cfg('now_city') . ',city_ids)');
        }
        
        // 排序
        $order = [
            'sort' => 'DESC',
            'id' => 'DESC',
        ];

        // 导航列表
        $slidrList = $this->getSliderListByCondition($where,$order,$limit);

        foreach($slidrList as $key=>$value){
            if($value['pic']){
                // 替换图片路径
                $slidrList[$key]['pic'] = replace_file_domain('/upload/slider/'.$value['pic']);
            }
        }

        //web版导航多城市
        if(cfg('many_city') && ($catKey == 'web_slider')){
            foreach($slidrList as $key=>$value){
                if(substr($value['url'],-6) == 'nocity'){
                    $slidrList[$key]['url'] = substr($value['url'],0,strlen($value['url'])-6);
                }else{
                    $slidrList[$key]['url'] = str_replace(cfg('config_site_url'),cfg('now_site_url'),$value['url']);
                }
            }
        }
        $slidrList = replace_domain($slidrList);
        if(empty($slidrList)){
            $slidrList = [];
        }elseif($needFormart){
            $slidrList = $this->formatSlider($slidrList);
        }
        return $slidrList;
	}

    /**
     * 去掉不要的字段
     * @param $array 广告列表
     * @return array
     */
    public function formatSlider($array){
		foreach($array as &$slider_value){
			unset($slider_value['id']);
			unset($slider_value['cat_id']);
			unset($slider_value['sort']);
			unset($slider_value['status']);
			unset($slider_value['last_time']);
		}
		return $array;
	}


    /**
     * 根据条件获取广告列表
     * @return array
     */
	public function getSliderListByCondition($where, $order = [], $limit = 0){
		$sliderList = $this->sliderModel->getSliderListByCondition($where, $order, $limit);
		if(!$sliderList) {
            return [];
        }
		return $sliderList->toArray();
	}
}