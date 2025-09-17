<?php
/**
 * 门票预约推荐
 */

namespace app\life_tools\model\db;

use think\facade\Db;
use think\Model;

class LifeToolsRecommendTools extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getDetail($where, $page, $pageSize, $order = 'r.sort DESC')
    {
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('r')
        ->field('(CASE WHEN r.type = "appoint" THEN a.title ELSE g.title END) as title,g.type as tools_type,g.status as t_status,g.is_del as t_is_del,a.status as a_status,a.is_del as a_is_del,r.*')
        ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id AND r.type <> "appoint"', 'LEFT')
            ->join($prefix . 'life_tools_appoint a', 'r.tools_id = a.appoint_id AND r.type = "appoint"', 'LEFT')
            ->where($where)
            ->order($order)
            ->paginate($limit)
            ->toArray();
        return $arr;
    }

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getList($where, $filed = 'g.*,r.status,r.create_time,r.sort,r.name,r.show', $order = 'r.sort DESC', $limit = 3)
    {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('r')
                ->field($filed)
                ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
                ->where($where)
                ->order($order)
                ->paginate();
        } else {
            $arr = $this->alias('r')
                ->field($filed)
                ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
        }
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    public function getRecommendList($page_size = 10)
    {
        $prefix = config('database.connections.mysql.prefix');
 
        $sql = '((SELECT `t`.`tools_id`,`t`.`title`,`t`.`long`,`t`.`lat`,`t`.`cover_image` AS `image`,`t`.`sale_count`,`t`.`money`,"tools" AS `r_type`,`t`.`label`,`t`.`type`,`t`.`is_close`,`t`.`is_close_body`,`r`.sort FROM '.$prefix.'life_tools AS t LEFT JOIN '.$prefix.'life_tools_recommend_tools AS r ON t.tools_id = r.tools_id AND r.type <> "appoint" WHERE `t`.tools_id IN ( SELECT tools_id FROM '.$prefix.'life_tools_recommend_tools WHERE type <> "appoint" AND status = 1 ) AND `t`.is_del = 0 AND `t`.status = 1 ) UNION ALL (SELECT `a`.`appoint_id`AS `tools_id`,`a`.`title`,`a`.`long`,`a`.`lat`,`a`.`image_small` AS `image`,`a`.`join_num` AS `sale_count`,`a`.`price` AS `money`,"appoint" AS `r_type`,"通用预约" AS `label`,"" AS `type`,0 AS `is_close`,"" AS `is_close_body`,`r`.sort FROM '.$prefix.'life_tools_appoint AS a LEFT JOIN '.$prefix.'life_tools_recommend_tools AS r ON a.appoint_id = r.tools_id AND r.type = "appoint" WHERE `a`.appoint_id IN ( SELECT tools_id FROM '.$prefix.'life_tools_recommend_tools WHERE type = "appoint" AND status = 1) AND `a`.is_del = 0 AND `a`.status = 1)) AS a ';

        return Db::table($sql)
            ->order('sort DESC,sale_count DESC')
            ->paginate($page_size)
            ->toArray();
    }

}