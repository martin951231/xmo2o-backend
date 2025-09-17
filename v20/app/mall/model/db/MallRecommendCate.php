<?php
/**
 * 商城商品推荐分类model
 * Created by vscode.
 * Author: jjc
 * Date Time: 2020/5/19 10:50
 */
namespace app\mall\model\db;
use think\Model;
class MallRecommendCate extends Model {

	/**
	 * [getNormalList 获取正常自定义推荐列表]
	 * @Author   JJC
	 * @DateTime 2020-06-08T13:48:15+0800
	 * @return   [type]                   [description]
	 */
	public function getNormalList($field="*"){
		$where = [
			'is_del' => 0,
		];
		return $this->field($field)->where($where)->select();
	}

	/**
	 * [getOne 获取单个自定义数据]
	 * @Author   JJC
	 * @DateTime 2020-06-09T13:46:06+0800
	 * @param    [type]                   $id [description]
	 * @return   [type]                       [description]
	 */
	public function getOne($id){
		if(!$id) return [];
		$where = [
			'id' => $id,
		];
		return $this->where($where)->find()->toArray();
	}

}