<?php
/*
 * 异步加载评论
 *
 */
class ReplyAction extends BaseAction{
    public function ajax_get_list(){
    	$goods_id=I('get.goods_id',0,'intval');
		$reply_return = D('Reply')->get_page_reply_list($_GET['parent_id'],$_GET['order_type'],$_POST['tab'],$_POST['order'],$_GET['store_count'],$_POST['showCount'],0,$goods_id);
		if($goods_id>0){
			echo json_encode($reply_return);
		}else{
			if($reply_return['count']){
				echo json_encode($reply_return);
			}else{
				echo '0';
			}	
		}		
    }
}