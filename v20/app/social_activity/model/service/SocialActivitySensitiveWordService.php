<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivitySensitiveWord;

class SocialActivitySensitiveWordService
{
    /**
     * 获取敏感词列表
     * @param $param
     * @return \think\Paginator
     */
    public function getList($param)
    {
        $paginate = (new SocialActivitySensitiveWord())
            ->withSearch(['keyword', 'status', 'start_time', 'end_time'], $param)
            ->order('id DESC')
            ->paginate([
                'list_rows' => $param['pageSize'],
                'page' => $param['page'],
            ]);
        return $paginate;
    }

    /**
     * 保存敏感词（新增/编辑）
     * @param $param
     * @return array
     */
    public function save($param)
    {
        $id = $param['id'] ?? 0;
        if ($id > 0) {
            // 编辑模式
            $info = (new SocialActivitySensitiveWord())->getRuleById($id);
            if (!$info || $info->isEmpty()) {
                throw_exception('编辑对象不存在！');
            }

            // 检查敏感词是否重复
            if (isset($param['word']) && $param['word'] != $info['word']) {
                $existWord = (new SocialActivitySensitiveWord())->where(['word' => $param['word']])->find();
                if ($existWord && !$existWord->isEmpty()) {
                    throw_exception('敏感词已存在！');
                }
            }

            $data = [
                'word' => $param['word'] ?? $info['word'],
                'status' => isset($param['status']) ? intval($param['status']) : $info['status'],
            ];

            $result = (new SocialActivitySensitiveWord())->where(['id' => $id])->save($data);
            if ($result === false) {
                throw_exception('编辑失败！');
            }
        } else {
            // 新增模式，支持批量添加（多个敏感词以逗号隔开）
            $words = preg_split('/[,，]/', $param['word']);
            $words = array_map('trim', $words);
            $words = array_filter($words);

            if (empty($words)) {
                throw_exception('请输入敏感词！');
            }

            $status = isset($param['status']) ? intval($param['status']) : 1;
            $successCount = 0;
            $failCount = 0;

            foreach ($words as $word) {
                // 检查敏感词是否已存在
                $existWord = (new SocialActivitySensitiveWord())->where(['word' => $word])->find();
                if ($existWord && !$existWord->isEmpty()) {
                    $failCount++;
                    continue;
                }

                $data = [
                    'word' => $word,
                    'status' => $status,
                ];

                $result = (new SocialActivitySensitiveWord())->save($data);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            if ($successCount == 0) {
                throw_exception('敏感词添加失败，可能已全部存在！');
            }

            return [
                'msg' => "成功添加 {$successCount} 个敏感词" . ($failCount > 0 ? "，{$failCount} 个已存在" : ""),
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                ],
            ];
        }

        return true;
    }

    /**
     * 删除敏感词
     * @param $param
     * @return bool
     */
    public function delete($param)
    {
        if (!$param['id']) {
            throw_exception('删除对象不能为空！');
        }
        $info = (new SocialActivitySensitiveWord())->getRuleById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('删除对象不存在！');
        }

        $result = (new SocialActivitySensitiveWord())->where(['id' => $param['id']])->delete();
        if (!$result) {
            throw_exception('删除失败！');
        }

        return true;
    }
}
