<?php
class Live_video_categoryModel extends Model
{
	public function getCategory($ids){
		if(empty($ids)) return [];
		$ids = trim($ids,',');
		$data = $this->where(['id'=>['in', explode(',', $ids)], 'is_del'=>0])->field("id as cate_id,name")->select();
		return $data;
	}
}