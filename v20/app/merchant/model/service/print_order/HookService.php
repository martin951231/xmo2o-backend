<?php
/**
 * 打印机service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/12 11:45
 */

namespace app\merchant\model\service\print_order;
use app\merchant\model\db\Hook as HookModel;
class HookService {
    public $hookModel = null;
    public function __construct()
    {
        $this->hookModel = new HookModel();
    }
    
    /**
     * 执行钩子
     * Created by subline.
     * Author: hengtingmei
     * Date Time: 2020/06/12 11:45
     */
	public function hookExec($hook_exec_name,$exec_param = []){
        $cacheName = 'hookList/'.$hook_exec_name;
        
        $cache = cache();
		$hookList = $cache->get($cacheName);
		
		//缓存存在且为空，例如空数据等
		if($hookList !== false && empty($hookList)){
			return false;
		}
		
		//缓存不存在
		if($hookList === false){
            $where = [
                'hook_exec' => $hook_exec_name
            ];
            $hookList = (new HookService())->getList();
            $cache->set($cacheName, $hookList);
		}
		
		//缓存存在，且为空
		if(empty($hookList)){
			return false;
		}
		
		//执行钩子
		foreach($hookList as $value){
			$class_name = array_pop(explode('.',$value['file']));
			//将点换为斜线
			if(strpos($value['file'],'.') !== false){
				$value['file'] = str_replace('.','\\',$value['file']);
			}
			if($value['is_plugin']){
				// $real_file = WEB_PATH . 'plugin/' . $value['plugin_name'] . '/Lib/ORG/' . $value['file'] . '.class.php';
			}else{
				$real_file = WEB_PATH . 'extend/' . $value['file'] . '.php';
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
                $eventClassName = '\\'.$value['file'].'()';
				$eventClass = new $eventClassName;
				$eventFunc = $value['function'];
				//判断返回内容为 true，然后计入时间。自行可以判断还有任务需要执行，可以返回false，下一次任务还会触发。
				if(method_exists($eventClass,$eventFunc)){
					$eventClass->$eventFunc($exec_param);
				}
			}
		}
    }
    
    /**
     * 根据条件一条数据
     * @param $where array 
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return [];
        }

        $hook = $this->hookModel->getOne($where);
        if(!$hook) {
            return [];
        }
        
        return $hook->toArray(); 
    }

    /**
     * 根据条件获取数据
     * @param $where array 
     * @return array
     */
    public function getList($where, $order) {
        if(empty($where)){
           return [];
        }

        $hookList = $this->hookModel->getList($where, $order);
        if(!$hookList) {
            return [];
        }
        
        return $hookList->toArray(); 
    }

}