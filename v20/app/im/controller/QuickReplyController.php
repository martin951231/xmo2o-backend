<?php

namespace app\im\controller;

use app\BaseController;
use app\im\model\MerchantStoreKefu;
use app\im\model\PersonalShortPhrases;
use app\im\model\PersonalShortPhrasesGroup;
use app\im\model\ShortPhrasesGroup;

/**
 * 快捷回复
 *
 * @author: zt
 * @date: 2024/07/08
 */
class QuickReplyController extends BaseController
{
    public function lists()
    {
        $username = $this->request->param('username', '', 'trim');
        if (!$username) {
            return api_output_error(1001, '参数有误!');
        }
        $kefu = (new MerchantStoreKefu())->where('username', $username)->field('id,store_id,belong')->find();
        if (!$kefu) {
            return api_output_error(1001, '客服不存在!');
        }
        $personalPhrases = (new PersonalShortPhrasesGroup())->getPhrases($kefu['id']);
        $storePhrases = (new ShortPhrasesGroup())->getPhrases($kefu['store_id'], $kefu['belong']);
        $return = array('store_phrases' => $storePhrases, 'personal_phrases' => $personalPhrases);
        return api_output(0, $return);
    }

    /**
     * 保存个人快捷回复分组
     *
     * @return void
     * @author: zt
     * @date: 2024/07/08
     */
    public function savePharseGroup()
    {
        $username = $this->request->param('username', '', 'trim');
        $id = $this->request->param('id', 0, 'intval');
        $name = $this->request->param('name', '', 'trim');
        $sort = $this->request->param('sort', 0, 'intval');
        if (!$username) {
            return api_output_error(1001, '参数有误!');
        }
        if (!$name) {
            return api_output_error(1001, '分组名称不能为空!');
        }
        $kefu = (new MerchantStoreKefu())->where(array('username' => $username))->field('id,store_id')->find();
        if (!$kefu) {
            return api_output_error(1001, '客服用户不存在!');
        }

        if ($id > 0) {
            $group = (new PersonalShortPhrasesGroup())->where(array('id' => $id, 'kefu_id' => $kefu->id))->find();
            if (empty($group)) {
                return api_output_error(1001, '当前分组不存在，无法编辑!');
            }
            $group->name = $name;
            $group->sort = $sort;
            $group->save();
        } else {
            $data = array(
                'kefu_id' => $kefu->id,
                'name' => $name,
                'sort' => $sort
            );
            (new PersonalShortPhrasesGroup())->insert($data);
        }
        return api_output();
    }

    /**
     * 删除分组
     *
     * @return void
     * @author: zt
     * @date: 2024/07/08
     */
    public function delPharseGroup()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id < 1) {
            return api_output_error(1001, '参数有误!');
        }

        (new PersonalShortPhrasesGroup())->delById($id);
        return api_output();
    }


    /**
     * 新增、编辑快捷回复
     *
     * @return void
     * @author: zt
     * @date: 2024/07/08
     */
    public function savePharse()
    {
        $id = $this->request->param('id', 0, 'intval');
        $groupId = $this->request->param('group_id', 0, 'intval');
        $context = $this->request->param('context', '', 'trim');
        $sort = $this->request->param('sort', 0, 'intval');
        if ($groupId < 1) {
            return api_output_error(1001, '参数有误!');
        }
        if (!$context) {
            return api_output_error(1001, '短语内容不能为空!');
        }

        $group = (new PersonalShortPhrasesGroup())->where('id', $groupId)->find();
        if (!$group) {
            return api_output_error(1001, '快捷短语分组不存在!');
        }

        if ($id > 0) {
            $phrase = (new PersonalShortPhrases())->where('id', $id)->find();
            if (empty($phrase)) {
                return api_output_error(1001, '当前快捷短语不存在，无法编辑!');
            }
            $phrase->context = $context;
            $phrase->sort = $sort;
            $phrase->save();
        } else {
            $data = array(
                'group_id' => $groupId,
                'context' => $context,
                'sort' => $sort
            );
            (new PersonalShortPhrases())->insert($data);
        }
        return api_output();
    }

    /**
     * 删除快捷回复
     *
     * @return void
     * @author: zt
     * @date: 2024/07/08
     */
    public function delPharse()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id < 1) {
            return api_output_error(1001, '参数有误!');
        }

        (new PersonalShortPhrases())->where('id', $id)->delete();
        return api_output();
    }
}
