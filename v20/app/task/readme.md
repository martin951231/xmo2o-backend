如何迁移老的队列或者计划任务

比如：

新增了一个`v20/app/task/EveryFiveMinuteSysTask.php` 
对应替换 `cms/Lib/ORG/plan/plan_apDevice_status.class.php` ，则需要在`cms/Lib/ORG/plan/plan_apDevice_status.class.php`
开始执行代码之前增加:

```
class plan_apDevice_status extends plan_base
{
    public function runTask(){
        //--------------这里是新增 开始-----------
	    if (!empty(C('DISABLE_OLD_ALL_DB_PLAN')) && C('DISABLE_OLD_ALL_DB_PLAN') == true){
		    return ;
	    }
	    //--------------这里是新增 结束-----------

        $model = '\app\community\model\service\ApDeviceService/getDeviceStatus';
        invoke_v20_service($model);
        return true;
    }
}
```

另外确认 `conf/db.php` 已经新增了该字段` 'DISABLE_OLD_ALL_DB_PLAN' => true `

