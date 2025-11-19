<?php
/**
 * 商户
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 18:18
 */

namespace app\common\model\service;

use app\common\model\db\Merchant;
use app\merchant\model\db\MerchantUserAccount;
use app\merchant\model\db\MerchantUserStations;
use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitJob;
use think\Exception;

class MerchantService
{
    public $merchantObj = null;

    public function __construct()
    {
        $this->merchantObj = new Merchant();
    }

    /**
     * 根据商户id 获取商户信息
     * User: chenxiang
     * Date: 2020/6/1 18:29
     * @param $mer_id
     * @return array|bool|\think\Model
     */
    public function getInfo($mer_id)
    {
        if (empty($mer_id)) {
            return [];
        }
        $result = $this->merchantObj->getInfo($mer_id);
        return $result;
    }

    public function getMerBySearchName($keyword, $limit = 0)
    {
        if (empty($keyword)) {
            return [];
        }
        $where   = [['status', '=', 1]];
        $where[] = ['name', 'like', '%' . $keyword . '%'];
        if ($limit > 0) {
            $mer = $this->merchantObj->field('mer_id,name')->where($where)->limit($limit)->select()->toArray();
        } else {
            $mer = $this->merchantObj->field('mer_id,name')->where($where)->select()->toArray();
        }
        return $mer;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     * 商家列表
     */
    public function getSome($where, $field, $order, $page, $pageSize)
    {
        $assign['list']  = $this->merchantObj->getSome($where, $field, $order, ($page - 1) * $pageSize, $pageSize)->toArray();
        $assign['count'] = $this->merchantObj->getCount($where);
        return $assign;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     * 商家列表
     */
    public function getSome1($where, $field, $order, $page, $pageSize)
    {
        $assign['list'] = (new NewRecruitCompany())->getCompanyByMer($where, $field, $order, ($page - 1) * $pageSize, $pageSize)->toArray();
        foreach ($assign['list'] as $k => $v) {
            $assign['list'][$k]['recruit_publish_nums'] = (new NewRecruitJob())->getCount(['mer_id' => $v['mer_id'], 'is_del' => 0, 'status' => 1]);
        }
        $assign['count'] = (new NewRecruitCompany())->getCompanyByMerCount($where);
        return $assign;
    }

    /**
     * @param $where
     * @param $data
     * @return mixed
     * 更新
     */
    public function updateThis($data)
    {
        $where = [['mer_id', '=', $data['mer_id']]];
        $data1 = array();
        if ($data['recruit_sort'] != "") {
            $data1['recruit_sort'] = $data['recruit_sort'];
        }
        if ($data['recruit_status'] != "") {
            $data1['recruit_status'] = $data['recruit_status'];
        }
        $ret = $this->merchantObj->updateThis($where, $data1);
        if ($ret !== false) {
            if ($data['recruit_status'] == 0) {
                // 拉黑
                (new NewRecruitJob())->where(['mer_id' => $data['mer_id'], 'is_del' => 0])->update(['is_del' => 1]);
            } elseif ($data['recruit_status'] == 1) {
                // 取消拉黑
                (new NewRecruitJob())->where(['mer_id' => $data['mer_id'], 'is_del' => 1])->update(['is_del' => 0]);
            }
            return true;
        } else {
            return false;
        }
    }

    public function userAccountAddOrEdit(array $params)
    {
        $userAccount = new MerchantUserAccount();
        $id = $params['id'] ?? 0;
        $mobile = $params['mobile'];

        $existAccount = $userAccount->where(['mer_id' => $params['mer_id'], 'mobile' => $mobile, 'is_del' => 0])->find();
        if ($existAccount && $existAccount->id != $id) {
            throw new Exception(L_('该手机号已使用，请更换'));
        }

        $data = [
            "account" => $params['account'],
            "mobile" => $params['mobile'] ?? '',
            "station_id" => $params['station_id'] ?? 0,
            "status" => $params['status'],
            "mer_id" => $params['mer_id'],
            "is_del" => 0,
        ];
        if ($params['password']) {
            $data['password'] = md5($params['password']);
        }
        if ($id) {
            $userAccount->where(['mer_id' => $params['mer_id'], 'id' => $id])->update($data);
        } else {
            $userAccount->insert($data);
        }
        return true;
    }

    public function userAccountDelete(array $params)
    {
        MerchantUserAccount::where([
            ['mer_id', '=', $params['mer_id']],
            ['id', 'IN', $params['id']]
        ])->select()
            ->each(function ($item) {
                $item->is_del = 1;
                $item->save();
            });

        return true;
    }

    public function userAccountList(array $params)
    {
        $data = MerchantUserAccount::where([
            'mer_id' => $params['mer_id'],
            'is_del' => 0,
        ])
            ->withoutField('password')
            ->paginate()->toArray();
        $account = (new \app\common\model\db\Merchant())->where('mer_id',$params['mer_id'])->value('account');
        $stations = (new MerchantUserStations())->where('mer_id', $params['mer_id'])->column('station_name', 'id');
        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['station_name'] = isset($stations[$v['station_id']]) ? $stations[$v['station_id']] : '--';
            $data['data'][$k]['login_username'] = $v['account'] . '@' . $account;
        }
        return $data;
    }

    /**
     * 岗位管理列表
     * @author: zt
     * @date: 2023/04/12
     */
    public function stations($merId, $page = 1, $pageSize = 15)
    {
        $stationMod = new MerchantUserStations();
        $data = $stationMod->where(['mer_id' => $merId])->paginate(['page' => $page, 'list_rows' => $pageSize])->toArray();
        $menuMod = new \app\merchant\model\db\NewMerchantMenu();
        if (isset($data['data'])) {
            $data['data'] = array_map(function ($r) use ($menuMod) {
                $r['create_time'] = date('Y-m-d H:i:s', $r['create_time']);
                $r['last_time'] = date('Y-m-d H:i:s', $r['last_time']);
                $r['menus'] = $r['menus'] ? explode(',', $r['menus']) : [];
                if($r['menus']){
                    $isFidNode = $menuMod->whereIn('fid', $r['menus'])->where('is_hide',0)->distinct(true)->column('fid');
                    $r['menus_select']= array_values(array_diff($r['menus'],$isFidNode));
                }else{
                    $r['menus_select'] = [];
                }
                 $r['menus'] ?  : [];
                return $r;
            }, $data['data']);
        }
        return $data;
    }

    /**
     * 新增/编辑岗位
     * @author: zt
     * @date: 2023/04/12
     */
    public function saveStation($params)
    {
        $rules = [
            'mer_id' => 'require|gt:0',
            'station_name' => 'require',
            'status' => 'in:0,1',
        ];
        $messages = [
            'mer_id.require' => L_('商家参数有误'),
            'mer_id.gt' => L_('商家参数有误'),
            'station_name.require' => L_('岗位名称不能为空'),
            'status.in' => L_('状态参数有误'),
        ];
        $validate = \think\facade\Validate::rule($rules)->message($messages);
        if (!$validate->check($params)) {
            throw new  Exception($validate->getError());
        }
        $params['last_time'] = time();
        $params['menus'] = implode(',',$params['menus']);
        if (isset($params['id']) && $params['id'] > 0) {
            //修改
            (new MerchantUserStations())->where('id', $params['id'])->where('mer_id', $params['mer_id'])->update($params);
        } else {
            //新增
            $params['create_time'] = time();
            (new MerchantUserStations())->insert($params);
        }
        return true;
    }

    /**
     * 删除岗位
     * @author: zt
     * @date: 2023/04/12
     */
    public function delStation($merId, $id)
    {
        if (empty($merId) || empty($id)) {
            throw new  Exception(L_('参数有误'), 1003);
        }
        return (new MerchantUserStations())->where('id', $id)->where('mer_id', $merId)->delete();
    }

    /**
     * 批量导入子账号
     *
     * @param \think\file\UploadedFile $file
     * @return void
     * @author: zt
     * @date: 2023/04/13
     */
    public function importAccount(\think\file\UploadedFile $file, $merId)
    {
        if (!$file || !$merId) {
            throw new Exception(L_('参数有误'));
        }
        validate(['excelFile' => [
            'fileSize' => 1024 * 1024 * 10,   //10M
            'fileExt' => 'xls,xlsx',
            'fileMime' => 'application/octet-stream,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]])->check(['excelFile' => $file]);

        $runtimeFile = runtime_path() . uniqid() . '.' . $file->getOriginalExtension();
        $tmpFile = $file->getPathname();
        if (!file_exists($tmpFile)) {
            throw new Exception(L_('上传文件不存在'));
        }
        $res = file_put_contents($runtimeFile, file_get_contents($tmpFile));
        if (!$res) {
            throw new Exception(L_('读取文件失败'));
        }

        $errorMsg = [];
        $success = 0;
        $stations = (new MerchantUserStations())->where('mer_id', $merId)->column('id', 'station_name');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($runtimeFile);
        $sheetDatas = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        foreach ($sheetDatas as $k => $v) {
            $v = array_map(function ($r) {
                return trim($r);
            }, $v);
            if ($k == 1 || $k == 2) {
                continue;
            }
            if (empty($v['A'])) {
                break;
            }
            $errorMsg[$k] = [];
            if (!isset($stations[$v['D']])) {
                $errorMsg[$k][] = L_('填写岗位不存在');
            }
            if ($v['E'] !== '正常' && $v['E'] !== '禁止') {
                $errorMsg[$k][] = L_('状态一栏填写有误');
            }
            if ($errorMsg[$k]) {
                continue;
            }

            $params['mer_id'] = $merId;
            $params['account'] = $v['A'];
            $params['password']  = $v['B'];
            $params['mobile']  = $v['C'];
            $params['station_id'] = $stations[$v['D']];
            $params['status'] = $v['E'] == '正常' ? 1 : 0;

            $validate = validate(\app\merchant\validate\MerchantUserAccount::class);
            if (!$validate->scene('add')->check($params)) {
                $errorMsg[$k][] = $validate->getError();
                continue;
            }
            try {
                $this->userAccountAddOrEdit($params);
                $success++;
                unset($errorMsg[$k]);
            } catch (\Exception $e) {
                $errorMsg[$k][] = $e->getMessage();
                continue;
            }
        }


        $failMessage = '';
        if ($errorMsg) {
            foreach ($errorMsg as $line => $messge) {
                $failMessage .= sprintf('行号：%s，错误信息：%s', $line, implode(';', $messge) . PHP_EOL);
            }
        }
        $rs['result'] = sprintf('导入完成，成功新增%d条，失败%d条',$success,count($errorMsg));
        $rs['fail_message'] = nl2br($failMessage);
        return $rs;
    }
}