<?php
/*
 * 小程序和H5交互
 *
 */
class AppworkAction extends BaseAction{
	public function js(){
		$this->display('./tpl/Wap/pure/Appwork/appwork.js', 'utf-8', 'text/javascript');
	}
}