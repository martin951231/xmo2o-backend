<?php
/**
 * 系统后台清除缓存
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/14 13:57
 */

namespace app\common\model\db;
use think\Model;
// use think\facade\Db;
class CacheSql extends Model {


    use \app\common\model\db\db_trait\CommonFunc;
}