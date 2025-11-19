<?php
/**
 * 商城商品系统后台自定义推荐
 * Created by vscode.
 * Author: jjc
 * Date Time: 2020/6/8 10:50
 */
namespace app\mall\model\service;
use app\mall\model\db\MallRecommendCate as  MallRecommendCateModel;

class MallRecommendCateService{

	public $MallRecommendCateModel = null;

    public function __construct()
    {
        $this->MallRecommendCateModel = new MallRecommendCateModel();
    }

    /**
     * [getNormalList 获取后台设置的推荐栏目]
     * @Author   JJC
     * @DateTime 2020-06-08T14:08:56+0800
     * @param    string                  $deal [针对不同的端口处理成不同的数据格式]
     * @return   [type]                  [description]
     */
    public function getNormalList($deal='dealListToHome'){
    	$list = $this->MallRecommendCateModel->getNormalList('id,title,sub_title');
    	return $deal?$this->$deal($list):$list->toArray();
    }

    //格式化成首页需要的数据格式
    private function dealListToHome($list){
    	$return = [
    		['type'=>'recommend','diy_cate_id'=>0,'title'=>'精选','sub_title'=>'爆款巡礼'],
    	];
    	foreach ($list as $key => $val) {
    		$temp = [
    			'type'=>'diy',
    			'diy_cate_id'=>$val['id'],
    			'title'=>$val['title'],
    			'sub_title'=>$val['sub_title'],
    		];
    		$return[] = $temp;
    	}
    	return $return;
    }

    public function getOne($id){
        if(!$id) return [];
        $info = $this->MallRecommendCateModel->getOne($id);
        return  $this->dealOne($info);
    }

    private function dealOne($info){
        if($info){
            $info['bind_arr'] = explode(',', $info['bind_content']);
        }
        return $info;
    }
}