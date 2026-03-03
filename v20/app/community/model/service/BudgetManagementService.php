<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业相关新版收费逻辑相关
 */

namespace app\community\model\service;

use app\community\model\db\BudgetCategory;
use app\community\model\db\VillageBudget;


class BudgetManagementService
{
    /**
     * 获取预算分类
     * @return string[]
     */
    public function getBudgetCategoryList($where,$field=true,$order='id DESC',$page=1,$limit=15) {
        $db_budget_management = new BudgetCategory();
        $list = $db_budget_management->getList($where,$field,$page,$limit)->toArray();
        return $list;
    }

    /**
     * 新增：获取预算分类总条数（用于分页）
     * @param array $where 查询条件
     * @return int
     */
    public function getBudgetCategoryCount($where)
    {
        $model = new BudgetCategory();
        // 调用Model的getCount方法
        return $model->getCount($where);
    }


    /**
     * 获取信息
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getBudgetCategoryFind($where,$field=true,$orderby='status asc,id desc')
    {
        // 初始化 数据层
        $budgetCategoryDb = new BudgetCategory();
        $info = $budgetCategoryDb->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }


    /**
     * 新增预算分类
     * @param array $data 新增数据（含name/unit/status/property_id/village_id/addtime）
     * @return bool
     */
    public function addBudgetCategory($data)
    {
        try {
            $model = new BudgetCategory();
            // 过滤无效字段（仅保留表中存在的字段，防止注入）
            $allowFields = ['category_name', 'unit', 'status', 'property_id', 'village_id', 'add_time'];
            $insertData = array_intersect_key($data, array_flip($allowFields));
            // 插入数据
            $id = $model->insertGetId($insertData);
            return $id > 0;
        } catch (\Exception $e) {
            throw new \Exception('新增预算分类异常：' . $e->getMessage());
        }
    }

    /**
     * 修改预算分类
     * @param array $where 条件（如id/property_id）
     * @param array $updateData 要更新的数据（name/unit/status/village_id）
     * @return bool
     */
    public function editBudgetCategory($where, $updateData)
    {
        try {
            $model = new BudgetCategory();
            // 过滤无效字段
            $allowFields = ['category_name', 'unit', 'status', 'village_id'];
            $updateData = array_intersect_key($updateData, array_flip($allowFields));
            // 执行更新（返回受影响行数）
            $row = $model->where($where)->update($updateData);
            return $row > 0;
        } catch (\Exception $e) {
            throw new \Exception('修改预算分类异常：' . $e->getMessage());
        }
    }

    /**
     * 删除预算分类（带权限校验）
     * @param int|string $id 预算分类ID
     * @param int $property_id 物业ID（用于权限校验，防止越权删除）
     * @return bool
     * @throws \Exception
     */
    public function deleteBudgetCategory($id, $property_id)
    {
        try {
            $model = new BudgetCategory();

            // 1. 先查询分类是否存在，且属于当前物业（权限校验）
            $category = $model->getOne([
                'id' => $id,
                'property_id' => $property_id
            ]);

            if (empty($category)) {
                return false; // 分类不存在或不属于当前物业
            }

            // 2. 可选：校验分类是否被引用（如关联了预算数据，禁止删除）
            // 示例：可根据业务需求添加关联表校验
            // $budgetModel = new \app\community\model\db\Budget();
            // $hasRel = $budgetModel->getCount(['category_id' => $id]);
            // if ($hasRel > 0) {
            //     throw new \Exception('该分类已关联预算数据，无法删除！');
            // }

            // 3. 执行删除（物理删除，如需逻辑删除可改为更新status字段）
            $deleteResult = $model->where([
                'id' => $id,
                'property_id' => $property_id // 再次校验物业ID，防止越权
            ])->delete();

            return $deleteResult > 0;
        } catch (\Exception $e) {
            throw new \Exception('删除预算分类异常：' . $e->getMessage());
        }
    }

    /**
     * 删除预算分类（带权限校验）
     * @param int|string $id 预算分类ID
     * @param int $property_id 物业ID（用于权限校验，防止越权删除）
     * @return bool
     * @throws \Exception
     */
    public function deleteVillageBudget($id)
    {
        try {
            $model = new VillageBudget();

            // 3. 执行删除（物理删除，如需逻辑删除可改为更新status字段）
            $deleteResult = $model->where([
                'id' => $id,
            ])->delete();

            return $deleteResult > 0;
        } catch (\Exception $e) {
            throw new \Exception('删除预算异常：' . $e->getMessage());
        }
    }

    /**
     * 获取预算
     * @return string[]
     */
    public function getBudgetList($where,$field=true,$order='vb.id DESC',$page=1,$limit=15) {
        $db_budget_management = new VillageBudget();
        $list = $db_budget_management->getList($where,$field,$page,$limit)->toArray();
        return $list;
    }

    /**
     * 新增：获取预算总条数（用于分页）
     * @param array $where 查询条件
     * @return int
     */
    public function getBudgetCount($where)
    {
        $model = new VillageBudget();
        // 调用Model的getCount方法
        return $model->getCount($where);
    }


    /**
     * 获取信息
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getVillageBudgetFind($where,$field=true,$orderby='status asc,id desc')
    {
        // 初始化 数据层
        $budgetCategoryDb = new VillageBudget();
        $info = $budgetCategoryDb->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 新增预算
     * @param array $data 新增数据（含'property_id', 'village_id', 'amount', 'month', 'operator', 'status', 'add_time'）
     * @return bool
     */
    public function addBudget($data)
    {
        try {
            $model = new VillageBudget();
            // 过滤无效字段（仅保留表中存在的字段，防止注入）
            $allowFields = ['property_id', 'village_id', 'category_id', 'amount', 'month', 'operator', 'status', 'add_time'];
            $insertData = array_intersect_key($data, array_flip($allowFields));
            // 插入数据
            $id = $model->insertGetId($insertData);
            return $id > 0;
        } catch (\Exception $e) {
            throw new \Exception('新增预算异常：' . $e->getMessage());
        }
    }

    /**
     * 修改预算
     * @param array $where 条件（如id/property_id）
     * @param array $updateData 要更新的数据（'property_id', 'village_id', 'amount', 'month', 'operator', 'status', 'add_time'）
     * @return bool
     */
    public function editBudget($where, $updateData)
    {
        try {
            $model = new VillageBudget();
            // 过滤无效字段
            $allowFields = ['property_id', 'village_id', 'category_id', 'amount', 'month', 'operator', 'status', 'add_time'];
            $updateData = array_intersect_key($updateData, array_flip($allowFields));
            // 执行更新（返回受影响行数）
            $row = $model->where($where)->update($updateData);
            return $row > 0;
        } catch (\Exception $e) {
            throw new \Exception('修改预算异常：' . $e->getMessage());
        }
    }

    /**
     * 删除预算（带权限校验）
     * @param int|string $id 预算ID
     * @param int $property_id 物业ID（用于权限校验，防止越权删除）
     * @return bool
     * @throws \Exception
     */
    public function deleteBudget($id, $property_id)
    {
        try {
            $model = new VillageBudget();

            // 1. 先查询分类是否存在，且属于当前物业（权限校验）
            $category = $model->getOne([
                'id' => $id,
                'property_id' => $property_id
            ]);

            if (empty($category)) {
                return false; // 分类不存在或不属于当前物业
            }

            // 2. 可选：校验分类是否被引用（如关联了预算数据，禁止删除）
            // 示例：可根据业务需求添加关联表校验
            // $budgetModel = new \app\community\model\db\Budget();
            // $hasRel = $budgetModel->getCount(['category_id' => $id]);
            // if ($hasRel > 0) {
            //     throw new \Exception('该分类已关联预算数据，无法删除！');
            // }

            // 3. 执行删除（物理删除，如需逻辑删除可改为更新status字段）
            $deleteResult = $model->where([
                'id' => $id,
                'property_id' => $property_id // 再次校验物业ID，防止越权
            ])->delete();

            return $deleteResult > 0;
        } catch (\Exception $e) {
            throw new \Exception('删除预算异常：' . $e->getMessage());
        }
    }

}