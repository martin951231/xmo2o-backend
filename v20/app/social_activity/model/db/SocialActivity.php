<?php


namespace app\social_activity\model\db;


use think\Model;

class SocialActivity extends Model
{
    const STATUS_0 = 0;
    const STATUS_10 = 10;
    const STATUS_20 = 20;
    const STATUS_30 = 30;

    const STATUS_MAP = [
        self::STATUS_0 => '待审核',
        self::STATUS_10 => '已通过',
        self::STATUS_20 => '已拒绝',
        self::STATUS_30 => '已取消',
    ];

    public function getList($field, $where, $page = 0, $pageSize = 10, $uid = 0){
        $list = $this->alias('a')->field($field)
            ->join('social_activity_category c', 'a.category_id = c.id')
            ->join('social_activity_admin u', 'a.creator_uid = u.id')
            ->where($where)
            ->order('id desc')
            ->paginate($pageSize)
            ->append(['status_text', 'activity_status_text'])
            ->each(function(&$item) use ($uid){
                $item->share_url = get_base_url('pages/socialActivity/detail?id='.$item->id);
                $item->is_view = ($uid != 1 && $item->creator_uid != $uid) ? 1 : 0;
            })
            ->toArray();
        return $list;
    }

    public function getStatusTextAttr($value, $data){
        return $data ? (self::STATUS_MAP[$data['is_audit']] ?? '') : '';
    }

    public function getActivityStatusTextAttr($value, $data){
        $status_text = '';
        if($data){
            if($data['status'] == self::STATUS_30){
                $status_text = '已取消';
            }elseif(strtotime($data['start_time']) > time()){
                $status_text = '待开始';
            }elseif(strtotime($data['end_time']) >= time()){
                $status_text = '进行中';
            }else{
                $status_text = '已结束';
            }
        }
        return $status_text;
    }

    public function getPosterAttr($value, $data){
        return $data ? ($data['poster'] ? replace_file_domain($data['poster']) : '') : '';
    }
    public function getPositionPosterAttr($value, $data){
        return $data ? ($data['position_poster'] ? replace_file_domain($data['position_poster']) : '') : '';
    }

    /**
     * 关联活动分类
     */
    public function category()
    {
        return $this->belongsTo(SocialActivityCategory::class, 'category_id', 'id');
    }

    /**
     * 关联活动场次
     */
    public function sessions()
    {
        return $this->hasMany(SocialActivitySession::class, 'activity_id', 'id');
    }
}