<?php

namespace app\survey\service;

use app\survey\model\SurveyForm;
use app\survey\model\SurveyQuestion;
use app\survey\model\SurveySubmit;
use app\survey\model\SurveySubmitAnswer;
use app\survey\service\strategy\QuestionStrategyFactory;
use Carbon\Carbon;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;

class SubmitService
{
	public function getFormDetail(int $formId): SurveyForm
	{
		$detail = (new FormService())->getFormDetail($formId);
		if ($detail->status != SurveyForm::STATUS_ENABLE) {
			throw_exception('问卷未启用');
		}
		if ($detail->start_time > date('Y-m-d H:i:s')) {
			throw_exception('问卷调查未开始');
		}
		if ($detail->end_time < date('Y-m-d H:i:s')) {
			throw_exception('问卷调查已结束');
		}
		return $detail;
	}

	/**
	 * 提交问卷
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 * @throws DbException
	 * @throws Exception
	 */
	public function submitForm(int $userId, int $formId, array $answers, array $params = [])
	{
		$spendTime = $params['spend_time'] ?? 0;
		$form = $this->getFormDetail($formId);
		if($form['submit_user_type'] == 'system_user' && empty($userId)) {
			throw_exception('抱歉，您无权限填写该表单');
		}
		if ($form['submit_user_type'] == 'level_user') {
			$levelInfo = (new \app\common\model\service\UserService())->getLevelByUid($userId);
			if (!$levelInfo['is_level']) {
				throw_exception('抱歉，您无权限填写该表单');
			}
		}
		$this->checkSubmitLimit($formId, $userId, $form);

		$questions = SurveyQuestion::where('form_id', $formId)->select();
		if ($questions->isEmpty()) {
			throw new Exception('表单缺失问题');
		}
		$answers = array_column($answers, null, 'id');
		$this->validateAnswers($questions, $answers);

		return Db::transaction(function () use ($form, $formId, $userId, $questions, $answers, $spendTime) {
			$submitId = $this->saveSubmitRecord($formId, $userId, $spendTime);
			$this->saveAnswers($submitId, $questions, $answers);
			return [
				'submit_id' => $submitId,
			];
		});
	}

	/**
	 * 检查提交限制
	 * @param int $formId
	 * @param int $userId
	 * @param SurveyForm $form
	 * @throws Exception
	 */
	private function checkSubmitLimit(int $formId, int $userId, SurveyForm $form): void
	{
		// 总提交次数限制
		if ($form['max_submit_num'] > 0) {
			if($userId > 0) {
				$submitCount = SurveySubmit::where('form_id', $formId)->where('user_id', $userId)->count();
			}else {
				$submitCount = SurveySubmit::where('form_id', $formId)->where('ip_address', request()->ip())->count();
			}
			if ($submitCount >= $form['max_submit_num']) {
				throw new Exception('已达到提交次数限制');
			}
		}

		// 每日提交次数限制
		if ($form['max_day_submit_num'] > 0) {
			if($userId > 0) {
				$daySubmitCount = SurveySubmit::where('form_id', $formId)
					->where('user_id', $userId)
					->whereBetweenTime('create_time', Carbon::now()->startOfDay(), Carbon::now()->endOfDay())
					->count();
			}else {
				$daySubmitCount = SurveySubmit::where('form_id', $formId)
					->whereBetweenTime('create_time', Carbon::now()->startOfDay(), Carbon::now()->endOfDay())
					->where('ip_address', request()->ip())
					->count();
			}
			if ($daySubmitCount >= $form['max_day_submit_num']) {
				throw new Exception('已达到每日提交次数限制');
			}
		}
	}

	/**
	 * 验证答案
	 * @param Collection $questions
	 * @param array $answers
	 * @throws Exception
	 * @throws \Exception
	 */
	private function validateAnswers(Collection $questions, array $answers): void
	{
		foreach ($questions as $question) {
			$questionData = $question->toArray();
			if (!isset($answers[$questionData['id']])) {
				throw_exception($questionData['title'] . '缺少问题答案');
			}
			$answer = $answers[$questionData['id']];
			$strategy = QuestionStrategyFactory::create($questionData['type']);
			$strategy->validateAnswer($question, $answer);
		}
	}

	/**
	 * 保存提交记录
	 * @param int $formId
	 * @param int $userId
	 * @return int
	 * @throws Exception
	 */
	private function saveSubmitRecord(int $formId, int $userId, int $spendTime): int
	{
		$submitModel = new SurveySubmit();
		$submitSuccess = $submitModel->save([
			'form_id' => $formId,
			'user_id' => $userId,
			'ip_address' => request()->ip(),
			'spend_time' => $spendTime,
			'device_type' => request()->agent,
		]);

		if (!$submitSuccess) {
			throw new Exception('提交记录保存失败');
		}

		return $submitModel->id;
	}

	/**
	 * 保存答案
	 * @param int $submitId
	 * @param int $formId
	 * @param Collection $questions
	 * @param array $answers
	 * @throws Exception
	 */
	private function saveAnswers(int $submitId, Collection $questions, array $answers): void
	{
		foreach ($questions as $question) {
			$questionId = $question->id;
			$answer = $answers[$questionId];

			$strategy = QuestionStrategyFactory::create($question->type);
			$answerData = $strategy->prepareAnswerData($submitId, $question, $answer);

			(new SurveySubmitAnswer())->save($answerData);
		}
	}

	/**
	 * 获取用户提交记录
	 */
	public function getUserSubmits(int $formId, int $userId = 0, array $params = []): array
	{
		$where['form_id'] = $formId;
		$userId && $where['user_id'] = $userId;
		$surveySubmit = SurveySubmit::where($where)
			->with(['user' => function ($query) {
				$query->field('uid, nickname, phone');
			}, 'answers' => function ($query) {
				$query->with(['question' => function ($query) {
					$query->field('id,title,type');
				}]);
			}])
			->order('create_time DESC');
		if (!empty($params['is_export'])) {
			return $this->exportSubmits($formId, $surveySubmit->select()->toArray());
		}
		return $surveySubmit->paginate(min($params['pageSize'] ?? 10, 100))->toArray();
	}

	/**
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws Exception
	 */
	private function exportSubmits($formId, array $submits): array
	{
		$xlsCell = [
			['nickname', '提交人昵称'],
			['phone', '提交人手机号'],
			['create_time', '提交时间'],
			['spend_time', '耗时(秒)'],
		];
		$form = (new FormService())->getFormDetail($formId)->toArray();
		foreach ($form['questions'] as $question) {
			$xlsCell[] = [$question['type'] . '_' . $question['id'], $question['title']];
		}
		$xlsName = '问卷记录--' . date('YmdHis');
		$answerList = [];
		foreach ($submits as $submit) {
			$answer = [
				'nickname' => $submit['user']['nickname'] ?? $submit['ip_address'],
				'phone' => $submit['user']['phone'] ?? '',
				'create_time' => $submit['create_time'],
				'spend_time' => $submit['spend_time'],
			];
			foreach ($submit['answers'] as $value) {
				$answer[$value['question']['type'] . '_' . $value['question']['id']] = $value['answer'];
			}
			$answerList[] = $answer;
		}
		return export_excel($xlsName, $xlsCell, $answerList);
	}

	/**
	 * 获取提交详情
	 * @throws DataNotFoundException
	 * @throws ModelNotFoundException
	 * @throws DbException
	 * @throws Exception
	 */
	public function getSubmitDetail($submitId, $userId = 0)
	{
		$submit = SurveySubmit::find($submitId);
		if (!$submit) {
			throw new Exception('提交记录不存在');
		}

		// 检查权限
		$form = SurveyForm::find($submit['form_id']);
		if ($form['show_result'] != 1 || ($userId > 0 && $submit['user_id'] != $userId)) {
			throw new Exception('无权限查看该提交记录');
		}

		return (new FormService())->getFormDetail($submit['form_id'], ['submit_id' => $submitId]);
	}

	public function getUserSubmitForm($userId)
	{
		return SurveySubmit::alias('s')
			->join('survey_form f', 'f.id = s.form_id')
			->where('s.user_id', $userId)
			->order('s.create_time desc')
			->field('s.id as submit_id, s.create_time, f.id as form_id, f.title, f.show_result')
			->paginate();
	}
}
