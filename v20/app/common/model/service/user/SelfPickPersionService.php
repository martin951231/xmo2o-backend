<?php

namespace app\common\model\service\user;

use app\common\model\db\SelfPickPerson;
use Exception;

/**
 * 自提人
 *
 * @author: zt
 * @date: 2024/04/26
 */
class SelfPickPersionService
{
    public $selfPickPersonMod;

    public function __construct()
    {
        $this->selfPickPersonMod = new SelfPickPerson();
    }

    public function save($uid, $data)
    {
        if (empty($data['name'])) {
            throw new Exception(L_('提货人不能为空'));
        }
        if (empty($data['phone'])) {
            throw new Exception(L_('手机号不能'));
        }

        $id = $data['id'] ?? 0;
        if ($id > 0) {
            $this->selfPickPersonMod->update($data, ['id' => $id], ['name', 'phone', 'is_default', 'tag']);
        } else {
            $data['uid'] = $uid;
            $this->selfPickPersonMod->allowField(['uid', 'name', 'phone', 'is_default', 'tag'])->save($data);
            $id = $this->selfPickPersonMod->id;
        }

        if ($data['is_default'] == 1 && $id > 0) {
            //其他自提人全部更新成非默认
            $this->selfPickPersonMod->where('uid', '=', $uid)->where('id', '<>', $id)->update(['is_default' => 0]);
        }
        return true;
    }

    public function deleteById($uid, $id)
    {
        return $this->selfPickPersonMod->where('uid', $uid)->where('id', $id)->delete();
    }

    public function getLists($uid)
    {
        $lists = $this->selfPickPersonMod->field('id,name,phone,is_default,tag')->where('uid', $uid)->order('is_default DESC,id ASC')->select()->toArray();
        $lists = array_map(function ($r) {
            $r['phone'] = phone_show($r['phone']);
            return $r;
        }, $lists);
        return $lists;
    }

    public function detail($id, $uid = 0)
    {
        return $this->selfPickPersonMod->where('id', $id)->where('uid', $uid)->withOutField('uid')->find();
    }
}
