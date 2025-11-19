<?php


namespace app\common\model\db;

use think\Model;
class DiypageFeedCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    const SORE_TYPE_SALES = 1;//按销量
    const SORE_TYPE_DISTANCE = 2;//按距离
    const SORE_TYPE_SCORE = 3;//按评分
    const SORE_TYPE_ADD_TIME = 4;//按店铺上架时间
}