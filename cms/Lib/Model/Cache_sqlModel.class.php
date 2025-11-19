<?php
/*
 *
 */
class Cache_sqlModel extends Model
{
    /* 通过分类的KEY得到广告列表 */
    public function clearCache()
    {
        import('ORG.Util.Dir');
        Dir::delDirnotself(WEB_PATH.'/runtime/');
        Dir::delDirnotself(WEB_PATH.'/source/plan/time/');
        Dir::delDirnotself(WEB_PATH.'/source/plan/sub_plan/');
		
		Dir::delDirnotself(WEB_PATH.'/v20/runtime/');

        M('')->execute('repair table '.C('DB_PREFIX').'process_plan');
        M('')->execute('repair table '.C('DB_PREFIX').'process_sub_plan');

        //清空SQL版的S数据
        M('Cache_sql')->where('1=1')->data(['expire' => '1'])->save();

        $dir_class = new Dir(WEB_PATH.'/api/log/');
        $dir_class->listFile(WEB_PATH.'/api/log/');
        //dump($dir_class);
        //die;
        foreach($dir_class->_values as $value){
            if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && $value['filename'] < date('Ymd', time()-15*24*60*60)){
                Dir::delDir(WEB_PATH.'/api/log/' . $value['filename'], false);
            }
        }
		
		//删除开门日志
		$dir_class = new Dir(WEB_PATH.'/api/door_log/');
		$dir_class->listFile(WEB_PATH.'/api/door_log/');
		if($dir_class->_values){
			foreach($dir_class->_values as $value){
				if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && $value['filename'] < date('Ymd', time()-15*24*60*60)){
					Dir::delDir(WEB_PATH.'/api/door_log/' . $value['filename'], false);
				}
			}
		}
		
		//删除智能调度日志
		$dir_class = new Dir(WEB_PATH.'/api/smart_deliver_log/');
		$dir_class->listFile(WEB_PATH.'/api/smart_deliver_log/');
		if($dir_class->_values){
			foreach($dir_class->_values as $value){
				if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && $value['filename'] < date('Ymd', time()-15*24*60*60)){
					Dir::delDir(WEB_PATH.'/api/smart_deliver_log/' . $value['filename'], false);
				}
			}
		}
    }
}
?>