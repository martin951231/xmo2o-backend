<?php
/**
 * 景区体育健身列表model
 */

namespace app\life_tools\model\db;

use app\common\model\db\Area;
use \think\Model;

class LifeTools extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'tools_id';

    public $typeMap = [
        'scenic'    =>      '景区',
        'stadium'   =>      '场馆',
        'course'   =>      '课程'
    ];

    public $auditStatusMap = [
        0   =>  '待审核',
        1   =>  '审核成功',
        2   =>  '审核失败'
    ];


    public function searchToolsAttr($query, $value)
    {
       if(!empty($value)){
           $query->whereNotIn('t.tools_id', $value);
       }
    }

    public function searchMerIdAttr($query, $value)
    {
       !empty($value) && $query->where('mer_id', $value);

    }

    public function getLabelArrAttr($value, $data)
    {
        $label_arr = [];
        if($data['label']){
            $label_arr = explode(' ', $data['label']);
            if(count($label_arr)){
                $label_arr = array_filter($label_arr);
            }
        }
        return $label_arr;
    }

    public function getAreaAttr($value, $data)
    {
        $location = '';
        if($data['area_id']){
            $location = (new Area())->getAreaByAreaId($data['area_id']);
        }
       return $location;
    }

    public function getImagesArrAttr($value, $data)
    {
        $imagesArr = [];
        if($data['images']){
            $images = explode(',', $data['images']);
            foreach ($images as $key => $image) {
                $temp['url'] = replace_file_domain($image);
                $temp['data'] = $image;
                $imagesArr[] = $temp;
            }
        }
        return $imagesArr;
    }
    
    public function getLonglatAttr($value, $data)
    {
        return $data['long'] . ',' . $data['lat'];
    }

    public function getList($where = [], $field = '*', $order = 'sort desc', $limit = 20)
    {
        if (is_array($limit)) {
            $list = $this->field($field)
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit) {
            $arr = $this->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
            $list = [
                'data' => []
            ];
            if (!empty($arr)) {
                $list['data'] = $arr->toArray();
            }
        } else {
            $arr = $this->field($field)->where($where)->order($order)->select();
            if (!empty($arr)) {
                $list = $arr->toArray();
            } else {
                $list = [];
            }
        }
        return $list;
    }
    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getListTool($where, $field = 'r.*',$page=1,$pageSize=10,$order='r.tools_id desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'merchant m', 'm.mer_id = r.mer_id')
            ->where($where)
            ->order($order);
             if($page){
                 $out['total']=$arr->count();
                 $out['list']=$arr->page($page, $pageSize)
                     ->select()->toArray();
             }else{
                 $out=$arr->select()->toArray();
             }
        return $out;
    }

    public function getDetail($where, $field = '*')
    {
        if (!is_array($where)) {
            $where = ['tools_id' => $where];
        }
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    /**
     * @param: string $where
     * @return :  int
     * @Desc:   获取总数
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取指定商户的所有景区
     * @author nidan
     * @date 2022/3/23
     */
    public function getListByMerchant($where,$merIdAry,$field,$order)
    {
        $arr = $this->field($field)->where($where)->whereIn('mer_id',$merIdAry)->order($order)->select();
        if (!empty($arr)) {
            $list = $arr->toArray();
        } else {
            $list = [];
        }
        return $list;
    }


    public function tickets()
    {
        return $this->hasMany(LifeToolsTicket::class, 'tools_id', 'tools_id');
    }

    public function getShareScenicNum($where)
    {

        return $this->alias('l')
            ->join('life_tools_ticket t', 't.tools_id = l.tools_id')
            ->join('life_tools_ticket_distribution dt', 'dt.ticket_id = t.ticket_id')
            ->join('life_tools_distribution_user_bind_merchant ubm', 'ubm.mer_id = l.mer_id')
            ->where($where)
            ->count();

    }
    public function getAuditStatusTextAttr($value, $data)
    {
        return $this->auditStatusMap[$data['audit_status']] ?? '';
    }

    public function getTypeTextAttr($value, $data)
    {
        return $this->typeMap[$data['type']] ?? '';
    }
}