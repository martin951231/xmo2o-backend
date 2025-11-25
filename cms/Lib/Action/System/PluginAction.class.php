<?php
class PluginAction extends BaseAction {
	public function index(){
		redirect($this->config['site_url'] . '/plugin.php?plugin=index&from=system');
	}
}
?>