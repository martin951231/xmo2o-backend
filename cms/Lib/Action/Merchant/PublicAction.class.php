<?php
/*
 * 公共文件
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/05 17:40
 * 
 */

class PublicAction extends Action{
    public function header(){
		$this->display();
    }
	public function footer(){
		$this->display();
    }

    /**
     * 切换系统当前语言环境
     */
    public function change_lang(){
    	$lang = I('lang');
    	if(empty($lang)){
    		echo json_encode(array('status' => 2));
    		exit;
    	}
    	$now_lang = cookie('system_lang');
    	if($now_lang == $lang){
    		echo json_encode(array('status' => 2));
    		exit;	
    	}
    	cookie('system_lang',$lang, 60*60*24*365*10);//有效期搞长一点
    	echo json_encode(array('status' => 1));
    	exit;
    }
}