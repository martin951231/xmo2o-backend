<?php
class Live_videoModel extends Model
{
	public function getDetail($video_id, $_uid = 0, $mer_id = 0,$lat=0,$lng=0){
		if(empty($video_id)) return [];
		$now_live = $this->where(['video_id' => $video_id])->find();
		$author_user = D("User")->get_user($now_live['uid']);
		$detail = [//基础信息
				"video_id" => $video_id,
				'nickname' => $author_user['nickname'],
				'author_id' => $now_live['uid'],
				'avatar' => $author_user['avatar'],
				'title' => $now_live['title'],
				'play_url' => $now_live['play_url'],//播放地址
				'follow' => $_uid ? (D("Live_follow")->where(['uid'=>$_uid, 'is_del'=>0, 'follow_uid'=>$author_user['uid']])->find() ? 1 : 0) : 0,
				'follow_nums' => M("Live_follow")->where(['follow_uid'=>$now_live['uid'],'is_del'=>0])->count(),
				'img' => replace_file_domain($now_live['img']),
				'zan' => $now_live['zan'],
				'is_zan' => $_uid ? (M("Live_video_zan")->where(['video_id'=>$video_id, 'is_del'=>0, 'uid' => $_uid])->find() ? 1 : 0) : 0,
				'commonts' => M("Live_video_comment")->where(['video_id'=>$video_id, 'is_del'=>0])->count(),
				'share' => $now_live['share'],
				'cate_list' => D("Live_video_category")->getCategory($now_live['categorys']),
				'goods_list' => []
			];
        if($lat && $lng){
            if(empty($now_live['lng']*1) || empty($now_live['lat']*1)){
                $detail['address']=(object)[];
            }else{
                $now_address = D('Area')->cityMatching($now_live['lat'], $now_live['lng']);
                $detail['address']['area_name']=$now_address['area_info']['area_name'];
                $detail['address']['lng']=$now_live['lng'];
                $detail['address']['lat']=$now_live['lat'];
                $distance = getDistance($lat,$lng,$now_live['lat'],$now_live['lng']);
                $distance = $distance / 1000;
                if($distance){
                    $detail['address']['distance']=$distance.'km';
                }else{
                    $detail['address']['distance']=0;
                }
            }
        }
		// 非作者只能查看已上架的商品
		if($_uid != $now_live['uid']){
			$param['status'] = 1;
		}

		// 商家私域入口 只看该商家商品
		if($mer_id){
			$param['mer_id'] = $mer_id;
		}

		// 查询关联商品
		$data = D("Live_video_goods")->get_goods_by_liveid($video_id, 'no desc', $param);

		// 处理返回给前端的字段
		$detail['goods_list'] =  D("Live_video_goods")->formatGoods($video_id,$data,$_uid);

		return $detail;
	}
}