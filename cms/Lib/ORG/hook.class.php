<?php
/* 
 * 用户数据 钩子
 * 
 */
class hook{
	public function hook_exec($hook_exec_name,$exec_param = []){
		$cache_name = 'hook_list/'.$hook_exec_name;
		$hook_list = S($cache_name);
		
		//缓存存在且为空，例如空数据等
		if($hook_list !== false && empty($hook_list)){
			return false;
		}
		
		//缓存不存在
		if($hook_list === false){
			$hook_list = M('Hook')->where(['hook_exec'=>$hook_exec_name])->select();
			S($cache_name, $hook_list);
		}
		
		//缓存存在，且为空
		if(empty($hook_list)){
			return false;
		}
		
		//执行钩子
		foreach($hook_list as $value){
			$class_name = array_pop(explode('.',$value['file']));
			//将点换为斜线
			if(strpos($value['file'],'.') !== false){
				$value['file'] = str_replace('.','/',$value['file']);
			}
			if($value['is_plugin']){
				$real_file = WEB_PATH . 'plugin/' . $value['plugin_name'] . '/Lib/ORG/' . $value['file'] . '.class.php';
			}else{
				$real_file = WEB_PATH . 'cms/Lib/ORG/' . $value['file'] . '.class.php';
			}
			
			$executeResult = true;
			$eventClassName = $class_name;
			if(!class_exists($eventClassName)){
				if(file_exists($real_file)){
					include($real_file);
					if(!class_exists($eventClassName)){
						$executeResult = false;
					}
				}else{
					$executeResult = false;
				}
			}
			if($executeResult){
				$eventClass = new $eventClassName;
				$eventFunc = $value['function'];
				//判断返回内容为 true，然后计入时间。自行可以判断还有任务需要执行，可以返回false，下一次任务还会触发。
				if(method_exists($eventClass,$eventFunc)){
					$eventClass->$eventFunc($exec_param);
				}
			}
		}
	}
}
?>