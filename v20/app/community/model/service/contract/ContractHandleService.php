<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      合同信息
 */
namespace app\community\model\service\contract;

use app\common\model\service\send_message\SmsService;
use app\community\model\db\contract\HouseCommunityContract;
use app\community\model\db\contract\HouseCommunityContractAppendix;
use app\community\model\db\contract\HouseCommunityContractClassify;
use app\community\model\db\contract\HouseCommunityContractConfig;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseWorker;
use app\community\model\service\message\PushMessageHandleService;
use app\consts\ContractConst;
use app\consts\MessageConst;

class ContractHandleService
{
    /**** ↓合同类型 ****/

    /**
     * 获取合同类型状态信息.
     */
    public function getContractClassifyStatusText($status)
    {
        // 状态 1-正常 2-禁止 4-删除
        $statusArr = [1 => '启用', 2 => '禁用', 4 => '已删除'];
        $status = intval($status);
        return isset($statusArr[$status]) ? $statusArr[$status] : '';
    }

    /**
     * 获取合同分类信息.
     */
    public function getContractClassifyInfo($where = [], $field = 'id, name, status, add_time, update_time, delete_time', $isHandle = 0, $order = [])
    {
        $info = (new HouseCommunityContractClassify())->getOne($where, $field, $order);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        !$info && $info = [];
        if (isset($info['delete_time']) && intval($info['delete_time']) < 1) {
            unset($info['delete_time']);
        }
        if ($isHandle == 1) {
            if (isset($info['status'])) {
                $info['status_text'] = $this->getContractClassifyStatusText($info['status']);
            }
            if (isset($info['add_time']) && intval($info['add_time']) > 1) {
                $info['add_time_text'] = date('Y-m-d H:i:s', $info['add_time']);
            }
            if (isset($info['update_time']) && intval($info['update_time']) > 1) {
                $info['update_time_text'] = date('Y-m-d H:i:s', $info['update_time']);
            } elseif (isset($info['update_time'])) {
                unset($info['update_time']);
            }
            if (isset($info['delete_time']) && intval($info['delete_time']) > 1) {
                $info['delete_time_text'] = date('Y-m-d H:i:s', $info['delete_time']);
            } elseif (isset($info['delete_time'])) {
                unset($info['delete_time']);
            }
        }
        return $info;
    }

    /**
     * 添加/变更/删除 合同分类.
     */
    public function setContractClassify($params)
    {
        $data = [];
        $nowTime = time();
        $contractClassifyId = isset($params['id']) ? intval($params['id']) : 0;
        $is_del = isset($params['is_del']) ? intval($params['is_del']) : 0;
        if (isset($params['name'])) {
            $data['name'] = trim($params['name']);
        }
        if (!$contractClassifyId && (!isset($data['name']) || !$data['name'])) {
            throw new \think\Exception("请填写合同类型名称");
        }
        if (isset($params['status']) && intval($params['status']) > 0) {
            $data['status'] = intval($params['status']);
        }
        if (isset($params['delete_time']) && intval($params['delete_time']) > 0) {
            $data['delete_time'] = intval($params['delete_time']);
        } elseif ($is_del) {
            $data['delete_time'] = $nowTime;
        }
        if (!isset($data['delete_time']) && isset($data['name']) && $data['name']) {
            // 去重
            $whereUnique = [];
            $whereUnique[] = ['name', '=', $data['name']];
            $whereUnique[] = ['delete_time', '=', 0];
            $contractClassifyId && $whereUnique[] = ['id', '<>', $contractClassifyId];
            $uniqueInfo = $this->getContractClassifyInfo($whereUnique, 'id');
            if (isset($uniqueInfo['id']) && $uniqueInfo['id']) {
                throw new \think\Exception("合同类型【{$data['name']}】已经存在了，请更换名称！");
            }
        }
        $update_from_user = [];
        if ($contractClassifyId) {
            // 检查是否存在 如果不存在报错 如果是删除操作 不存在直接正常返回
            $whereCat = [];
            $whereCat[] = ['id', '=', $contractClassifyId];
            $whereCat[] = ['delete_time', '=', 0];
            $catInfo = $this->getContractClassifyInfo($whereCat, 'id, from_user');
            if (!isset($catInfo['id']) || !$catInfo['id']) {
                if (isset($data['delete_time']) && intval($data['delete_time']) > 0) {
                    // 删除 都找不到对象了 就是已经删除了
                    return $contractClassifyId;
                }
                throw new \think\Exception("操作的合同类型已经不存在或者已经被删除！");
            }
            $update_from_user = isset($catInfo['from_user']) && $catInfo['from_user'] ? json_decode($catInfo['from_user'], 1) : [];
        }
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $dbHouseCommunityContractClassify = new HouseCommunityContractClassify();
        if ($contractClassifyId && isset($data['delete_time']) && intval($data['delete_time']) > 0) {
            // 删除操作
            if ($from_user) {
                if (is_array($from_user)) {
                    $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
                }
                $data['delete_user'] = $from_user;
            }
            $data['status'] = 4;
            $delete = $dbHouseCommunityContractClassify->updateThis(['id' => $contractClassifyId], $data);
            if ($delete === false) {
                throw new \think\Exception("删除操作失败");
            }
            return $contractClassifyId;
        }
        if ($contractClassifyId) {
            // 更新操作
            $data['update_time'] = $nowTime;
            if ($from_user) {
                if (!is_array($from_user)) {
                    $from_user = json_decode($from_user, 1);
                }
                $from_user['data'] = $data;
                $update_from_user['update'] = $from_user;
                $data['from_user'] = json_encode($update_from_user, JSON_UNESCAPED_UNICODE);
            }
            $delete = $dbHouseCommunityContractClassify->updateThis(['id' => $contractClassifyId], $data);
            if ($delete === false) {
                throw new \think\Exception("更新操作失败");
            }
            return $contractClassifyId;
        } else {
            // 添加操作
            $data['add_time'] = $nowTime;
            if ($from_user) {
                if (!is_array($from_user)) {
                    $from_user = json_decode($from_user, 1);
                }
                $from_user['data'] = $data;
                $update_from_user['add'] = $from_user;
                $data['from_user'] = json_encode($update_from_user, JSON_UNESCAPED_UNICODE);
            }
            $contractClassifyId = $dbHouseCommunityContractClassify->add($data);
            if (!$contractClassifyId) {
                throw new \think\Exception("新增操作失败");
            }
            return $contractClassifyId;
        }
    }

    /**
     * 获取合同分类数量.
     */
    public function getContractClassifyCount($where)
    {
        return (new HouseCommunityContractClassify())->getCount($where);
    }

    /**
     * 获取合同分类列表.
     */
    public function getContractClassifyList($params)
    {
        $where = isset($params['where']) && !empty($params['where']) ? $params['where'] : [];
        $field = isset($params['field']) && $params['field'] ? $params['field'] : 'id, name, status, add_time, update_time, delete_time';
        $order = isset($params['order']) && $params['order'] ? $params['order'] : 'add_time DESC';
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 10;
        $isHandle = isset($params['isHandle']) ? intval($params['isHandle']) : 1;

        $list = (new HouseCommunityContractClassify())->getList($where, $field, $order, $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        foreach ($list as &$info) {
            if (isset($info['delete_time']) && intval($info['delete_time']) < 1) {
                unset($info['delete_time']);
            }
            if ($isHandle == 1) {
                if (isset($info['status'])) {
                    $info['status_text'] = $this->getContractClassifyStatusText($info['status']);
                }
                if (isset($info['add_time']) && intval($info['add_time']) > 1) {
                    $info['add_time_text'] = date('Y-m-d H:i:s', $info['add_time']);
                }
                if (isset($info['update_time']) && intval($info['update_time']) > 1) {
                    $info['update_time_text'] = date('Y-m-d H:i:s', $info['update_time']);
                } elseif (isset($info['update_time'])) {
                    unset($info['update_time']);
                }
                if (isset($info['delete_time']) && intval($info['delete_time']) > 1) {
                    $info['delete_time_text'] = date('Y-m-d H:i:s', $info['delete_time']);
                } elseif (isset($info['delete_time'])) {
                    unset($info['delete_time']);
                }
            }
        }
        return $list;
    }

    /**** ↑合同类型 ****/
    
    /**** ↓合同信息 ****/

    /**
     * 添加文件附件.
     */
    public function addContractAppendix($params) {
        $url = isset($params['url']) && $params['url'] ? trim($params['url']) : '';
        $path = isset($params['path']) && $params['path'] ? $params['path'] : '';
        if (!$url || !$path) {
            throw new \think\Exception("上传失败");
        }
        $file = isset($params['file']) && $params['file'] ? $params['file'] : [];
        $name = isset($params['name']) && $params['name'] ? trim($params['name']) : '';
        !$name && $name = isset($file['originalName']) && $file['originalName'] ? trim($file['originalName']) : '';
        $mimeType = isset($params['mimeType']) && $params['mimeType'] ? trim($params['mimeType']) : '';
        !$mimeType && $mimeType = isset($file['mimeType']) && $file['mimeType'] ? trim($file['mimeType']) : '';

        $fileInfo = pathinfo($path);
        if (!$name && isset($fileInfo['basename']) && $fileInfo['basename']) {
            $name = trim($fileInfo['basename']);
        } else {
            $fileInfo = pathinfo('.' . $url);
            !$name && $name = isset($fileInfo['basename']) && $fileInfo['basename'] ? trim($fileInfo['basename']) : '';
        }
        $file_extension = isset($fileInfo['extension']) && $fileInfo['extension'] ? trim($fileInfo['extension']) : '';
        $file_type = '';
        switch ($mimeType) {
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/msword':
                $file_type = 'word';
                break;
            case 'application/pdf':
                $file_type = 'pdf';
                break;
            case 'image/png':
            case 'image/x-png':
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpeg':
            case 'image/gif':
            case 'image/x-icon':
            case 'image/bmp':
                $file_type = 'image';
                break;
        }
        $upload_dir = isset($params['upload_dir']) && $params['upload_dir'] ? trim($params['upload_dir']) : 'house/contractAppendix';
        $path_md5 = md5($url);
        $nowTime = time();
        $appendix = [
            'file_name' => $name, 'upload_dir' => $upload_dir, 'file_type' => $file_type, 'mime_type' => $mimeType,
            'file_extension' => $file_extension, 'path' => $url, 'path_md5' => $path_md5, 'add_time' => $nowTime,
        ];
        $db_house_community_contract_appendix = new HouseCommunityContractAppendix();
        $imageId =  $db_house_community_contract_appendix->add($appendix);
        return [
            'imageId' => $imageId, 'file_name' => $name, 'url' => $url, 'path' => $path, 'imageKey' => $path_md5
        ];
    }

    /**
     * 获取合同生效状态.
     */
    public function getContractStatusText($contract_start_time, $contract_end_time, $nowTime = 0)
    {
        !$nowTime && $nowTime = time();
        $startTime = strtotime($contract_start_time);
        !$startTime && $startTime = $contract_start_time;
        $endTime = strtotime($contract_end_time);
        !$endTime && $endTime = $contract_end_time;
        if ($startTime > $nowTime) {
            return '待生效';
        }
        if ($endTime <= $nowTime) {
            return '已失效';
        }
        return '生效中';
    }
    
    /**
     * 获取合同信息.
     */
    public function getContractInfo($where = [], $field = '', $isHandle = 0, $order = []) {
        !$field && $field = 'id, contract_number, contract_name, classify_id, classify_name, contract_start_time, contract_end_time, contract_content, village_id, work_id, phone, from, add_time, update_time';
        $info = (new HouseCommunityContract())->getOne($where, $field, $order);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        !$info && $info = [];
        if (isset($info['delete_time']) && intval($info['delete_time']) < 1) {
            unset($info['delete_time']);
        }
        $nowTime = time();
        if ($isHandle >= 1 && !empty($info)) {
            if (isset($info['contract_start_time']) && trim($info['contract_start_time']) && isset($info['contract_end_time']) && trim($info['contract_end_time'])) {
                $info['status_text'] = $this->getContractStatusText($info['contract_start_time'], $info['contract_end_time'], $nowTime);
            }
            if (isset($info['add_time']) && intval($info['add_time']) > 1) {
                $info['add_time_text'] = date('Y-m-d H:i:s', $info['add_time']);
            }
            if (isset($info['contract_start_time']) && intval($info['contract_start_time']) > 100) {
                $info['contract_start_time_text'] = date('Y-m-d', $info['contract_start_time']);
            }
            if (isset($info['contract_end_time']) && intval($info['contract_end_time']) > 100) {
                $info['contract_end_time_text'] = date('Y-m-d', $info['contract_end_time']);
            }
            if (isset($info['delete_time']) && intval($info['delete_time']) > 1) {
                $info['delete_time_text'] = date('Y-m-d H:i:s', $info['delete_time']);
            } elseif (isset($info['delete_time'])) {
                unset($info['delete_time']);
            }
            if (isset($info['classify_id']) && $info['classify_id']) {
                $whereClassify = [];
                $whereClassify[] = ['id', '=', $info['classify_id']];
                $classifyInfo = $this->getContractClassifyInfo($whereClassify, 'id, name');
                if (isset($classifyInfo['name']) && trim($classifyInfo['name'])) {
                    $info['classify_name'] = $classifyInfo['name'];
                }
            }
            if (isset($info['village_id']) && $info['village_id']) {
                $villageInfo = (new HouseVillage())->getOne($info['village_id'], 'village_id,village_name');
                if (isset($villageInfo['village_name']) && trim($villageInfo['village_name'])) {
                    $info['village_name'] = $villageInfo['village_name'];
                }
            }
            if (isset($info['work_id']) && $info['work_id']) {
                $whereWork = [];
                $whereWork[] = ['wid', '=', $info['work_id']];
                $workInfo = (new HouseWorker())->getOne($whereWork, 'wid, name');
                if (isset($workInfo['name']) && trim($workInfo['name'])) {
                    $info['work_name'] = $workInfo['name'];
                }
            }
            // 查询下附件
            $db_house_community_contract_appendix = new HouseCommunityContractAppendix();
            $whereAppendix = [];
            $whereAppendix[] = ['contract_id', '=', $info['id']];
            $whereAppendix[] = ['delete_time', '=', 0];
            $appendixs = $db_house_community_contract_appendix->getList($whereAppendix, 'file_name, path, file_type');
            if ($appendixs && !is_array($appendixs)) {
                $appendixs = $appendixs->toArray();
            }
            !$appendixs && $appendixs = [];
            foreach ($appendixs as &$appendix) {
                $appendix['full_path'] = replace_file_domain($appendix['path']);
            }
            $info['appendix'] = $appendixs;
        }
        if (isset($info['contract_content']) && $info['contract_content']) {
            $info['contract_content'] = trim(replace_file_domain_content(htmlspecialchars_decode($info['contract_content'])));
        }
        if ($isHandle == 2) {
            // 处理成数组反馈
            $detail_content = [];
            isset($info['contract_number']) && $info['contract_number'] && $detail_content[]  = ['label' => '合同编号', 'value' => $info['contract_number'], 'type' => 'text'];
            isset($info['contract_name']) && $info['contract_name'] && $detail_content[]  = ['label' => '合同名称', 'value' => $info['contract_name'], 'type' => 'text'];
            isset($info['classify_name']) && $info['classify_name'] && $detail_content[]  = ['label' => '合同类型', 'value' => $info['classify_name'], 'type' => 'text'];
            isset($info['contract_start_time_text']) && $info['contract_start_time_text'] && $detail_content[]  = ['label' => '合同开始时间', 'value' => $info['contract_start_time_text'], 'type' => 'text'];
            isset($info['contract_end_time_text']) && $info['contract_end_time_text'] && $detail_content[]  = ['label' => '合同结束时间', 'value' => $info['contract_end_time_text'], 'type' => 'text'];
            isset($info['contract_content']) && $info['contract_content'] && $detail_content[]  = ['label' => '合同内容', 'value' => $info['contract_content'], 'type' => 'textarea'];
            isset($info['appendix']) && $info['appendix'] && $detail_content[]  = ['label' => '合同附件', 'value' => $info['appendix'], 'type' => 'appendix'];
            $info['detail_content'] = $detail_content;
        }
        return $info;
    }

    /**
     * 添加/变更/删除 合同.
     */
    public function setContract($params)
    {
        $data = [];
        $nowTime = time();
        $contractId = isset($params['id']) ? intval($params['id']) : 0;
        $is_del = isset($params['is_del']) ? intval($params['is_del']) : 0;
        $from = isset($params['from']) ? trim($params['from']) : '';
        if (isset($params['contract_number'])) {
            $data['contract_number'] = trim($params['contract_number']);
        }
        if (!$contractId && (!isset($data['contract_number']) || !$data['contract_number'])) {
            throw new \think\Exception("请填写合同编号");
        }
        if (isset($params['contract_name'])) {
            $data['contract_name'] = trim($params['contract_name']);
        }
        if (!$contractId && (!isset($data['contract_name']) || !$data['contract_name'])) {
            throw new \think\Exception("请填写合同名称");
        }
        if (isset($params['classify_id'])) {
            $data['classify_id'] = intval($params['classify_id']);
        }
        if (!$contractId && (!isset($data['classify_id']) || !$data['classify_id'])) {
            throw new \think\Exception("请选择合同类别");
        }
        if (isset($data['classify_id']) && $data['classify_id']) {
            $classifyInfo = $this->getContractClassifyInfo(['id' => $data['classify_id']], 'id, name');
            if (!isset($classifyInfo['id']) || !$classifyInfo['id']) {
                throw new \think\Exception("选择合同类别不存在或者已删除，请重新选择！");
            }
            $data['classify_name'] = trim($classifyInfo['name']);
        }
        if (isset($params['contract_start_time']) && strtotime($params['contract_start_time']) > 100) {
            $data['contract_start_time'] = strtotime($params['contract_start_time']);
        } elseif (isset($params['contract_start_time']) && intval($params['contract_start_time']) > 100) {
            $data['contract_start_time'] = intval($params['contract_start_time']);
        }
        if (isset($params['contract_end_time']) && strtotime($params['contract_end_time']) > 100) {
            $data['contract_end_time'] = strtotime($params['contract_end_time']);
        } elseif (isset($params['contract_end_time']) && intval($params['contract_end_time']) > 100) {
            $data['contract_end_time'] = intval($params['contract_end_time']);
        } 
        if (!$contractId && (!isset($data['contract_start_time']) || !$data['contract_start_time'] || !isset($data['contract_end_time']) || !$data['contract_end_time'])) {
            throw new \think\Exception("请选择合同开始结束时间");
        }
        if ($contractId && isset($params['contract_start_time']) && isset($params['contract_end_time']) && (!$data['contract_start_time'] || !$data['contract_end_time'])) {
            throw new \think\Exception("请选择合同开始结束时间");
        }
        // 处理开始时间为当日0点  结束时间为当天 23:59:59
        isset($data['contract_start_time']) && $data['contract_start_time'] = strtotime(date('Y-m-d', $data['contract_start_time']));
        isset($data['contract_end_time']) && $data['contract_end_time'] = strtotime(date('Y-m-d 23:59:59', $data['contract_end_time']));
        if (isset($data['contract_start_time']) && isset($data['contract_end_time']) && $data['contract_end_time'] < $data['contract_start_time']) {
            throw new \think\Exception("合同结束时间不能小于开始时间");
        }
        if (isset($params['contract_content']) && $params['contract_content']) {
            $data['contract_content'] = trim(stripslashes(htmlspecialchars_decode($params['contract_content'])));
        }
        if (!$contractId && (!isset($data['contract_content']) || !$data['contract_content'])) {
            throw new \think\Exception("请填写合同信息");
        }
        if (isset($params['village_id']) && intval($params['village_id']) > 0) {
            $data['village_id'] = intval($params['village_id']);
        }
        if (!$contractId && (!isset($data['village_id']) || !$data['village_id'])) {
            throw new \think\Exception("请选择小区");
        }
        if (isset($params['work_id']) && intval($params['work_id']) > 0) {
            $data['work_id'] = intval($params['work_id']);
        }
        if (!$contractId && (!isset($data['work_id']) || !$data['work_id'])) {
            throw new \think\Exception("请选择工作人员");
        }
        if (isset($params['phone']) && intval($params['phone']) > 0) {
            $data['phone'] = trim($params['phone']);
        }
        if (!$contractId && (!isset($data['phone']) || !$data['phone'])) {
            throw new \think\Exception("请填写联系电话");
        }
        
        if (isset($params['delete_time']) && intval($params['delete_time']) > 0) {
            $data['delete_time'] = intval($params['delete_time']);
        } elseif ($is_del) {
            $data['delete_time'] = $nowTime;
        }
        if (!isset($data['delete_time']) && isset($data['contract_number']) && $data['contract_number']) {
            // 去重
            $whereUnique = [];
            $whereUnique[] = ['contract_number', '=', $data['contract_number']];
            $whereUnique[] = ['delete_time', '=', 0];
            $contractId && $whereUnique[] = ['id', '<>', $contractId];
            $uniqueInfo = $this->getContractInfo($whereUnique, 'id');
            if (isset($uniqueInfo['id']) && $uniqueInfo['id']) {
                throw new \think\Exception("合同编号【{$data['contract_number']}】已经存在了，请更换合同编号！");
            }
        }
        $max = ContractConst::CONTRACT_APPENDIX_MAX;
        if (!empty($contract_appendix) && count($contract_appendix) > $max) {
            throw new \think\Exception("合同附件最多上传{$max}份！");
        }
        $update_from_user = [];
        if ($contractId) {
            // 检查是否存在 如果不存在报错 如果是删除操作 不存在直接正常返回
            $where = [];
            $where[] = ['id', '=', $contractId];
            $where[] = ['delete_time', '=', 0];
            if ($from == 'village' && isset($params['village_id']) && $params['village_id']) {
                $where[] = ['village_id', '=', $params['village_id']];
            }
            $contractInfo = $this->getContractInfo($where, 'id, village_id, from_user');
            if (!isset($contractInfo['id']) || !$contractInfo['id']) {
                if (isset($data['delete_time']) && intval($data['delete_time']) > 0) {
                    // 删除 都找不到对象了 就是已经删除了
                    return $contractId;
                }
                throw new \think\Exception("操作的合同已经不存在或者已经被删除！");
            }
            if (isset($data['village_id']) && $data['village_id'] != $contractInfo['village_id']) {
                throw new \think\Exception("当前合同归属小区不允许进行变更！");
            }
            $update_from_user = isset($contractInfo['from_user']) && $contractInfo['from_user'] ? json_decode($contractInfo['from_user'], 1) : [];
        }
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $dbHouseCommunityContract = new HouseCommunityContract();
        if ($contractId && isset($data['delete_time']) && intval($data['delete_time']) > 0) {
            // 删除操作
            if ($from_user) {
                if (is_array($from_user)) {
                    $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
                }
                $data['delete_user'] = $from_user;
            }
            $delete = $dbHouseCommunityContract->updateThis(['id' => $contractId], $data);
            if ($delete === false) {
                throw new \think\Exception("删除操作失败");
            }
            return $contractId;
        }
        $contract_appendix = isset($params['contract_appendix']) ? $params['contract_appendix'] : [];
        $db_house_community_contract_appendix = new HouseCommunityContractAppendix();
        if ($contractId) {
            // 更新操作
            $data['update_time'] = $nowTime;
            $deleteUpdate = ['delete_time' => $nowTime];
            $bindAppendix = ['update_time' => $nowTime];
            if ($from_user) {
                if (!is_array($from_user)) {
                    $from_user = json_decode($from_user, 1);
                }
                $from_user['data'] = $data;
                $update_from_user['update'] = $from_user;
                $data['from_user'] = json_encode($update_from_user, JSON_UNESCAPED_UNICODE);
                $deleteUpdate['delete_user'] = json_encode($from_user, JSON_UNESCAPED_UNICODE);
                $bindAppendix['from_user'] = json_encode($from_user, JSON_UNESCAPED_UNICODE);
            }
            $delete = $dbHouseCommunityContract->updateThis(['id' => $contractId], $data);
            if ($delete === false) {
                throw new \think\Exception("更新操作失败");
            }
            // 检查对应附件并处理
            if (empty($contract_appendix)) {
                // 没有附件上传 解绑所有
                $whereDelete = ['contract_id' => $contractId, 'delete_time' => 0];
                $db_house_community_contract_appendix->updateThis($whereDelete, $deleteUpdate);
            } else {
                $appendixIdArr = [];
                foreach ($contract_appendix as $appendix) {
                    $imageId = isset($appendix['imageId']) ? intval($appendix['imageId']) : 0;
                    $imageKey = isset($appendix['imageKey']) ? trim($appendix['imageKey']) : '';
                    $appendixIdArr[] = $imageId;
                }
                $bindAppendix['contract_id'] = $contractId;
                // 绑定下附件
                $whereBind = [];
                $whereBind[] = ['id', 'in', $appendixIdArr];
                $whereBind[] = ['delete_time', '=', 0];
                $db_house_community_contract_appendix->updateThis($whereBind, $bindAppendix);
                // 不是本次绑定范围的附件 删除
                $whereDelete = [];
                $whereDelete[] = ['id', 'not in', $appendixIdArr];;
                $whereDelete[] = ['contract_id', '=', $contractId];
                $whereDelete[] = ['delete_time', '=', 0];
                $db_house_community_contract_appendix->updateThis($whereDelete, $deleteUpdate);
            }
            return $contractId;
        } else {
            // 添加操作
            if ($from) {
                $data['from'] = $from;
            }
            $data['add_time'] = $nowTime;
            if ($from_user) {
                if (!is_array($from_user)) {
                    $from_user = json_decode($from_user, 1);
                }
                $from_user['data'] = $data;
                $update_from_user['add'] = $from_user;
                $data['from_user'] = json_encode($update_from_user, JSON_UNESCAPED_UNICODE);
            }
            $contractId = $dbHouseCommunityContract->add($data);
            if (!$contractId) {
                throw new \think\Exception("新增操作失败");
            }
            // 检查对应附件并处理
            if (!empty($contract_appendix)) {
                $appendixIdArr = [];
                foreach ($contract_appendix as $appendix) {
                    $imageId = isset($appendix['imageId']) ? intval($appendix['imageId']) : 0;
                    $imageKey = isset($appendix['imageKey']) ? trim($appendix['imageKey']) : '';
                    $appendixIdArr[] = $imageId;
                }
                $bindAppendix['contract_id'] = $contractId;
                // 绑定下附件
                $whereBind = [];
                $whereBind[] = ['id', 'in', $appendixIdArr];
                $whereBind[] = ['delete_time', '=', 0];
                $db_house_community_contract_appendix->updateThis($whereBind, $bindAppendix);
            }
            return $contractId;
        }
    }

    /**
     * 获取合同数量.
     */
    public function getContractCount($where)
    {
        return (new HouseCommunityContract())->getCount($where);
    }

    /**
     * 获取合同列表.
     */
    public function getContractList($params)
    {
        $where = isset($params['where']) && !empty($params['where']) ? $params['where'] : [];
        $field = isset($params['field']) && $params['field'] ? $params['field'] : '';
        !$field && $field = 'id, contract_number, contract_name, classify_id, classify_name, contract_start_time, contract_end_time, village_id, work_id, phone, from, add_time, update_time';
        $order = isset($params['order']) && $params['order'] ? $params['order'] : 'add_time DESC';
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 10;
        $isHandle = isset($params['isHandle']) ? intval($params['isHandle']) : 1;

        $list = (new HouseCommunityContract())->getList($where, $field, $order, $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $nowTime = time();
        $classifyInfoArr = [];
        $villageInfoArr = [];
        $workInfoArr = [];
        if ($isHandle == 1) {
            $classifyIdArr = array_unique(array_column($list,'classify_id'));
            $villageIdArr = array_unique(array_column($list,'village_id'));
            $workIdArr = array_unique(array_column($list,'work_id'));
            $whereClassify = [];
            $whereClassify[] = ['id', 'in', $classifyIdArr];
            $classifyInfoArr = (new HouseCommunityContractClassify())->getColumn($whereClassify, 'id, name', 'id');
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $villageIdArr];
            $villageInfoArr = (new HouseVillage())->getColumn($whereVillage, 'village_id, village_name', 'village_id');
            $whereWork = [];
            $whereWork[] = ['wid', 'in', $workIdArr];
            $workInfoArr = (new HouseWorker())->getColumn($whereWork, 'wid, name', 'wid');
        }
        foreach ($list as &$info) {
            if (isset($info['delete_time']) && intval($info['delete_time']) < 1) {
                unset($info['delete_time']);
            }
            if ($isHandle == 1) {
                $classify_id = isset($info['classify_id']) ? intval($info['classify_id']) : 0;
                $village_id = isset($info['village_id']) ? intval($info['village_id']) : 0;
                $work_id = isset($info['work_id']) ? intval($info['work_id']) : 0;
                if (isset($classifyInfoArr[$classify_id]) && isset($classifyInfoArr[$classify_id]['name'])) {
                    $info['classify_name'] = $classifyInfoArr[$classify_id]['name'];
                }
                if (isset($villageInfoArr[$village_id]) && isset($villageInfoArr[$village_id]['village_name'])) {
                    $info['village_name'] = $villageInfoArr[$village_id]['village_name'];
                }
                if (isset($workInfoArr[$work_id]) && isset($workInfoArr[$work_id]['name'])) {
                    $info['work_name'] = $workInfoArr[$work_id]['name'];
                }
                
                if (isset($info['contract_start_time']) && trim($info['contract_start_time']) && isset($info['contract_end_time']) && trim($info['contract_end_time'])) {
                    $info['status_text'] = $this->getContractStatusText($info['contract_start_time'], $info['contract_end_time'], $nowTime);
                }
                if (isset($info['add_time']) && intval($info['add_time']) > 1) {
                    $info['add_time_text'] = date('Y-m-d H:i:s', $info['add_time']);
                }
                if (isset($info['contract_start_time']) && intval($info['contract_start_time']) > 100) {
                    $info['contract_start_time_text'] = date('Y-m-d', $info['contract_start_time']);
                }
                if (isset($info['contract_end_time']) && intval($info['contract_end_time']) > 100) {
                    $info['contract_end_time_text'] = date('Y-m-d', $info['contract_end_time']);
                }
                if (isset($info['delete_time']) && intval($info['delete_time']) > 1) {
                    $info['delete_time_text'] = date('Y-m-d H:i:s', $info['delete_time']);
                } elseif (isset($info['delete_time'])) {
                    unset($info['delete_time']);
                }
            }
        }
        return $list;
    }

    /**
     * 增加或更新前获取接口.
     */
    public function getContractConfig($params) {
        $data = [];
        $contractId = isset($params['id']) ? intval($params['id']) : 0;
        if ($contractId > 0) {
            $where = [];
            $where[] = ['id', '=', $contractId];
            if (isset($params['village_id']) && $params['village_id']) {
                $where[] = ['village_id', '=', $params['village_id']];
            }
            $where[] = ['delete_time', '=', 0];
            $contractInfo = $this->getContractInfo($where, '', 1);
            if (!isset($contractInfo['id']) || !$contractInfo['id']) {
                throw new \think\Exception("操作的合同已经不存在或者已经被删除！");
            }
            $data['contractInfo'] = $contractInfo;
        }
        $data['config'] = [
            'appendixMax' => ContractConst::CONTRACT_APPENDIX_MAX,
            'addRequire' => [
                'contract_number' => 1, 'contract_name' => 1, 'classify_id' => 1, 'contract_start_time' => 1, 'contract_end_time' => 1, 'contract_content' => 1,
                'village_id' => 1, 'work_id' => 1, 'phone' => 0, 'contract_appendix' => 0,
            ],
            'editRequire' => [
                'contract_number' => 1, 'contract_name' => 1, 'classify_id' => 1, 'contract_start_time' => 1, 'contract_end_time' => 1, 'contract_content' => 1,
                'village_id' => 1, 'work_id' => 1, 'phone' => 0, 'contract_appendix' => 0,
            ],
        ];
        return $data;
    }
    
    /**** ↑合同信息 ****/

    /**** ↓合同配置 ****/

    /**
     * 获取配置.
     */
    public function getConfig($params = []) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $db_house_community_contract_config = new HouseCommunityContractConfig();
        $whereConfig = [];
        $whereConfig[] = ['village_id', '=', $village_id];
        $field = isset($params['field']) ? trim($params['field']) : 'id, village_id, expire_to_report_day, add_time';
        return $db_house_community_contract_config->getOne($whereConfig, $field);
    }

    /**
     * 设置配置.
     */
    public function setConfig($params = []) {
        $data = [];
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $expire_to_report_day = isset($params['expire_to_report_day']) ? $params['expire_to_report_day'] : 0;
        if (!is_numeric($expire_to_report_day)) {
            throw new \think\Exception("合同到期提前提醒天数只能只能是数字");
        }
        if (intval($expire_to_report_day) < 0) {
            throw new \think\Exception("合同到期提前提醒天数必须是不小于0的数字");
        }
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $db_house_community_contract_config = new HouseCommunityContractConfig();
        $whereConfig = [];
        $whereConfig[] = ['village_id', '=', $village_id];
        $config = $db_house_community_contract_config->getOne($whereConfig, 'id, from_user');
        $data['expire_to_report_day'] = $expire_to_report_day;
        $nowTime = time();
        $update_from_user = isset($config['from_user']) && $config['from_user'] ? json_decode($config['from_user'], 1) : [];
        if (isset($config['id']) && intval($config['id']) > 0) {
            $data['update_time'] = $nowTime;
            if ($from_user) {
                if (!is_array($from_user)) {
                    $from_user = json_decode($from_user, 1);
                }
                $from_user['data'] = $data;
                $update_from_user['update'] = $from_user;
            }
            $data['from_user'] = json_encode($update_from_user, JSON_UNESCAPED_UNICODE);
            $db_house_community_contract_config->updateThis(['id' => $config['id']], $data);
            return $config['id'];
        } else {
            $data['village_id'] = $village_id;
            $data['add_time'] = $nowTime;
            if ($from_user) {
                if (!is_array($from_user)) {
                    $from_user = json_decode($from_user, 1);
                }
                $from_user['data'] = $data;
                $update_from_user['add'] = $from_user;
                $data['from_user'] = json_encode($update_from_user, JSON_UNESCAPED_UNICODE);
            }
            return $db_house_community_contract_config->add($data);
        }
    }

    /**** ↑合同配置 ****/

    /**** ↓合同快过期提醒 ****/
    public function pushMessageToWork() {
        $db_house_community_contract_config = new HouseCommunityContractConfig();
        $whereConfig = [];
        $whereConfig[] = ['expire_to_report_day', '>', 0];
        $configList = $db_house_community_contract_config->getList($whereConfig, 'village_id, expire_to_report_day', 'village_id DESC');
        $villageIdArr = [];
        $nowEndTime = strtotime(date('Y-m-d 23:59:59'));
        $db_house_community_contract = new HouseCommunityContract();
        $classify_mark = MessageConst::CONTRACT_TO_WORK;
        $classifyInfo = (new PushMessageHandleService())->getPushMessageClassify($classify_mark);
        $send_classify_id = isset($classifyInfo['id']) ? intval($classifyInfo['id']) : 0;
        // 恒定每天8:30发送
        $send_time = strtotime(date('Y-m-d 08:30:00'));
        foreach ($configList as $config) {
            $village_id = isset($config['village_id']) && intval($config['village_id']) > 0 ? intval($config['village_id']) : 0;
            $expire_to_report_day = isset($config['expire_to_report_day']) && intval($config['expire_to_report_day']) > 0 ? intval($config['expire_to_report_day']) : 0;
            $where = [];
            $start = $expire_to_report_day - 1;
            $end = $expire_to_report_day;
            $startTime = $nowEndTime + $start * 86400;
            $endTime = $nowEndTime + $end * 86400;
            $village_id && $where[] = ['village_id', '=', $village_id];
            $where[] = ['contract_end_time', '>', $startTime];
            $where[] = ['contract_end_time', '<=', $endTime];
            if (!empty($villageIdArr)) {
                $where[] = ['village_id', 'not in', $villageIdArr];
            }
            $where[] = ['work_id', '>', 0];
            $field = 'id, contract_number, contract_name, contract_start_time, contract_end_time, village_id, work_id, phone';
            $list = $db_house_community_contract->getList($where, $field);
            fdump_api(['where' => $where, 'list' => $list], '$configList', 1);
            if ($list && !is_array($list)) {
                $list = $list->toArray();
            }
            !$list && $list = [];
            $villageIdArr = array_unique(array_column($list,'village_id'));
            $workIdArr = array_unique(array_column($list,'work_id'));

            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $villageIdArr];
            $villageNameArr = (new HouseVillage())->getColumn($whereVillage, 'village_name', 'village_id');
            $whereWork = [];
            $whereWork[] = ['wid', 'in', $workIdArr];
            $workNameArr = (new HouseWorker())->getColumn($whereWork, 'name', 'wid');
            
            $messageService = new PushMessageHandleService();
            $site_url = cfg('site_url');
            foreach ($list as $item) {
                // 先走下内部推送
                // todo 名字从工作人员取  链接等前端写好再填充
                $message_url = $site_url . '/packapp/community/pages/Community/contractManage/pages/contractDetail?contract_id='.$item['id'];
                $contract_end_time = date('Y-m-d', $item['contract_end_time']);
                $phone = isset($item['phone']) && trim($item['phone']) ? trim($item['phone']) : '';
                $village_id = isset($item['village_id']) && intval($item['village_id']) > 0 ? intval($item['village_id']) : 0;
                $work_id = isset($item['work_id']) && intval($item['work_id']) > 0 ? intval($item['work_id']) : 0;

                $send_name = isset($workNameArr[$work_id]) && trim($workNameArr[$work_id]) ? trim($workNameArr[$work_id]) : '';
                $village_name = isset($villageNameArr[$village_id]) && trim($villageNameArr[$village_id]) ? trim($villageNameArr[$village_id]) : '';
                if ($work_id) {
                    $params = [
                        'send_classify_id' => $send_classify_id, 'send_to' => MessageConst::SEND_TO_HOUSE_WORK, 'send_id' => $work_id,
                        'send_name' => $send_name, 'message_url' => $message_url, 'send_time' => $send_time, 'message_belong' => 'village',
                        'belong_id' => $item['village_id'], 'contract_id' => $item['id'], 'contract_number' => $item['contract_number'],
                        'contract_name' => $item['contract_name'],'contract_end_time' => $contract_end_time,
                    ];
                    fdump_api(['params' => $params], '$params', 1);
                    $messageService->sendContractMessage($params);
                }
                if ($phone) {
                    if (strpos($village_name,'小区') !== false) {
                        $x1 = $village_name;
                    } else {
                        $x1 = $village_name . '小区';
                    }
                    $x2 = $item['contract_name'];
                    $smsParams = [
                        'phone' => $phone, 'village_id' => $village_id, 'x1' => $x1, 'x2' => $x2,
                    ];
                    $this->sendSms($smsParams);
                }
            }
        }
    }

    /**
     * 模板：您在{1}管理的{2}即将到期，请前往移动管理端查看详情。
     * 实例：您在蓝光金色小区管理的物业费签约合同即将到期，请前往移动管理端查看详情。
     */
    public function sendSms($params) {
        $sms_data = array(
            'mer_id'     => 0,
            'store_id'   => 0,
            'sendto'     => 'worker',
            'mobile'     => $params['phone'],
            'uid'        => 0,
            'type'       => 'house_worker',
            'village_id' => $params['village_id']
        );
        $sms_content = L_('您在x1管理的x2即将到期，请前往移动管理端查看详情。', array('x1' => $params['x1'], 'x2' => $params['x2']));
        $sms_data['content']=$sms_content;
        (new SmsService())->sendSms($sms_data);
        return true;
    }
    /**** ↑合同快过期提醒 ****/
}