<?php


class Appoint_projectModel extends Model
{
    /**
     * 项目列表
     * @param $village_id
     * @param int $pageSize
     * @param array $column
     * @param string $type
     * @return mixed
     */
    public function get_limit_list_page($pageSize = 20,$params = [])
    {
        $where = [];
        if ($params['city_name']) {
            $where['city_name'] = $params['city_name'];
        }
        if ($params['project_name']) {
            $where['project_name'] = ['LIKE', '%' . $params['project_name'] . '%'];
        }
        $count_user = $this->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, $pageSize, 'page');
        $project_list = $this->where($where)->field(true)->limit($p->firstRow . ',' . $p->listRows)->select();

        if ($project_list) {
            $return['pagebar'] = $p->show();
            $return['project_list'] = $project_list;
        }

        return $return;
    }
}