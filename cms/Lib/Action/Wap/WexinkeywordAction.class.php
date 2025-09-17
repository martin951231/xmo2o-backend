<?php
class WexinkeywordAction extends BaseAction{
    public function index(){
		$key = safeValue($_GET['keyword']);
    	if(empty($key)){
			redirect(U('Home/index'));
		}
		$pageTitle = safeValue($_GET['title']);
		if(empty($pageTitle)){
			$pageTitle = $key;
		}
		$this->assign('pageTitle',$pageTitle);
		
		$platforms = M("Platform")->field(true)->where(array('key' => $key))->order('id ASC')->limit('0,10')->select();
		
		$returnArr = array();
		foreach ($platforms as $platform){
			$url = $platform['url'] ? $platform['url'] : $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=index&wxscan=1&sid=' . $platform['id'];
			$returnArr[] = array(
				'title' => $platform['title'], 
				'info'  => $platform['info'], 
				'pic'   => $this->config['site_url'] . $platform['pic'], 
				'url'   => $url
			);
		}
		// $returnArr[count($returnArr)-1]['title'] = '更正在接入更多更正在接入更多更正在接入更多更正在接入更多更正在接入更正在接入更多更正在接入更多更正在接入更多更正在接入更多更正在接入';
		$this->assign('returnArr',$returnArr);
		
		$this->display();
	}
}
?>