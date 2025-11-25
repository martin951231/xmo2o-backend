<?php

class IndexAction extends BaseAction{
    public function index(){

    }
	
	public function redirect(){
        if($_GET['from'] == 'system'){
            redirect(U('System/Plat/kefu',['from'=>$_GET['from']]));
        }else if($_GET['from'] == 'house'){
            redirect(U('System/Village/kefu',['from'=>$_GET['from']]));
        }else{
            redirect(U('System/Config/store',['from'=>$_GET['from']]));
        }
	}
}
