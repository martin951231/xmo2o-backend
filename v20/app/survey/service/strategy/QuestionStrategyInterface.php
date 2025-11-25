<?php
namespace app\survey\service\strategy;
use app\survey\model\SurveyQuestion;
use Exception;

interface QuestionStrategyInterface
{
     /**
     * 验证答案有效性
     * @param SurveyQuestion $question 问题对象
     * @param array $answer 用户提交的答案
     * @return bool
     * @throws Exception 验证失败时抛出异常
     */
    public function validateAnswer(SurveyQuestion $question, array $answer): bool;

    /**
     * 准备答案存储数据
     * @param int $submitId 提交记录ID
     * @param SurveyQuestion $question 问题对象
     * @param array $answer 用户提交的答案
     * @return array 格式化后的答案数据
     */
    public function prepareAnswerData(int $submitId, SurveyQuestion $question, array $answer): array;

    /**
     * 处理统计数据
     * @param SurveyQuestion $question 问题对象
     */
    public function processStatistics(SurveyQuestion $question);
}
