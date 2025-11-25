<?php
//保存用户的地理位置
class UserlonglatAction extends BaseAction{
	public function report(){
		if(IS_POST){
            $lbs_type = 1;
            if($_POST['type'] == 'gcj02'){
                $lbs_type = '3';
            }else if($_POST['type'] == 'wgs84'){
                $lbs_type = '1';
            }else if($_POST['type'] == 'gps'){
                $lbs_type = '1';
            }else if($_POST['type'] == 'baidu'){
                $lbs_type = '5';
            }
			
			if($_SESSION['openid'] && $_POST['userLong'] && $_POST['userLat']){
				D('User_long_lat')->saveLocation($_SESSION['openid'],$_POST['userLong'],$_POST['userLat'],$lbs_type);
				empty($_COOKIE['userLocationHasRecord']) && setcookie('userLocationHasRecord','1',$_SERVER['REQUEST_TIME']+120,'/');
			}
		}
	}
	/*百度经纬度转火星经纬度*/
	public function baiduToGcj02(){
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$location2 = $longlat_class->baiduToGcj02($_GET['baidu_lat'], $_GET['baidu_lng']);
		if($location2){
			$this->success($location2);
		}else{
			$this->error('失败');
		}
	}
}
?>