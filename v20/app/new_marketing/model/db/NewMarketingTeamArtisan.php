<?php
/**
 * 汪晨
 * 2021/08/17
 * 技术人员
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingTeamArtisan extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 技术人员团队列表
    public function getMarketingArtisanTeamList($where,$field) {
        if(!$where){
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
        ->field($field)
        ->leftJoin($prefix . 'new_marketing_team a', 'a.id=g.team_id')
        ->where($where)
        ->select();
        return $result;
    }

    // 技术人员团队列表
    public function getMarketingArtisanTeamName($where,$field) {
        if(!$where){
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->join($prefix . 'new_marketing_artisan n', 'n.id=g.artisan_id')
            ->join($prefix . 'new_marketing_team a', 'a.id=g.team_id')
            ->where($where)
            ->column($field);
        return $result;
    }

    // 团队技术员列表
    public function getMarketingTeamArtisanList($where,$field) {
        if(!$where){
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
        ->field($field)
        ->join($prefix . 'new_marketing_artisan a', 'a.id=g.artisan_id')
        ->where($where)
        ->select();
        return $result;
    }

    /**
     * Notes: 获取字段值
     * @param string|\think\model\concern\string $where
     * @param mixed $value
     * @return mixed
     */
    public function getValues($where,$value)
    {
        $data = $this->where($where)->value($value);
        return $data;
    }
}