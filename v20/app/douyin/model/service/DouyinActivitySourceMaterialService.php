<?php

namespace app\douyin\model\service;

use app\douyin\model\db\DouyinActivity;
use app\douyin\model\db\DouyinActivitySourceMaterial;
use Exception;

/**
 * 抖音探店素材库
 *
 * @author: zt
 * @date: 2022/11/29
 */
class DouyinActivitySourceMaterialService
{
    protected $douyinActivityMod;

    protected $douyinActivitySouceMaterialMod;

    public function __construct()
    {
        $this->douyinActivityMod = new DouyinActivity();
        $this->douyinActivitySouceMaterialMod = new DouyinActivitySourceMaterial();
    }

    /**
     * 添加/编辑素材
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function saveSourceMaterial($params)
    {
        if (empty($params['mer_id'])) {
            throw new Exception(L_('登录信息获取失败'));
        }
        if (empty($params['material_name'])) {
            throw new Exception(L_('素材名称不能为空'));
        }
        if (empty($params['material_url'])) {
            throw new Exception(L_('请上传素材文件'));
        }
        if (empty($params['cover'])) {
            throw new Exception(L_('请上传封面图'));
        }

        $id = $params['id'] ?? 0;
        if ($id > 0) {
            unset($params['id']);
            $this->douyinActivitySouceMaterialMod->updateThis(['id' => $id, 'mer_id' => $params['mer_id']], $params);
        } else {
            $params['create_time'] = time();
            $this->douyinActivitySouceMaterialMod->add($params);
        }
        return true;
    }

    /**
     * 素材列表
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function getSourceMaterialLists($params)
    {
        $where = [['is_del', '=', 0]];
        if (isset($params['mer_id'])) {
            $where[] = ['mer_id', '=', $params['mer_id']];
        }
        if (isset($params['name'])) {
            $where[] = ['material_name', 'LIKE', '%' . $params['name'] . '%'];
        }

        $limit  = [
            'page' => $params['page'] ?? 1,
            'list_rows' => $params['pageSize'] ?? 10
        ];
        $lists = $this->douyinActivitySouceMaterialMod->withoutField('is_del')->where($where)->paginate($limit)->toArray();
        foreach ($lists['data'] as &$l) {
            $l['create_time'] = date('Y-m-d H:i:s', $l['create_time']);
            $l['material_url'] = $l['material_url']?replace_file_domain($l['material_url']):'';
            $l['cover'] = $l['cover']?replace_file_domain($l['cover']):'';
        }
        return $lists;
    }

    /**
     * 删除素材
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function delSourceMaterial($ids)
    {
        if (empty($ids)) {
            throw new Exception(L_('参数有误'));
        }
        $this->douyinActivitySouceMaterialMod->updateThis([['id', 'in', $ids]], ['is_del' => 1]);
        return true;
    }

    /**
     * 获取素材详情
     *
     * @return void
     * @author: zt
     * @date: 2022/11/29
     */
    public function getSourceMaterialById($id)
    {
        if (empty($id)) {
            throw new Exception(L_('参数有误'));
        }

        $detail = $this->douyinActivitySouceMaterialMod->withoutField('is_del')->where('id', $id)->where('is_del', 0)->findOrEmpty()->toArray();
        return $detail;
    }
}
