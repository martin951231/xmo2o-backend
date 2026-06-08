<?php

namespace app\survey\service;

use app\common\model\db\User;
use app\common\model\service\user\UserNoticeService;
use app\survey\model\SurveyForm;
use app\survey\model\SurveyQuestion;
use app\survey\model\SurveyQuestionOption;
use app\survey\validate\FormValidate;
use app\survey\validate\QuestionValidate;
use Carbon\Carbon;
use think\db\exception\DbException;
use think\Exception;
use think\facade\Db;
use think\Paginator;

class FormService
{
	// 获取问卷列表
	/**
	 * @throws DbException
	 */
	public function getFormList($params = []): Paginator
	{
		return SurveyForm::where('is_del', 0)
			->withSearch(['title', 'status', 'type'], $params)
			->order('create_time desc')
			->paginate(min($params['pageSize'] ?? 10, 100))
			->each(function ($item) {
				$page = 'pages/activity/questionnaire/form?form_id=' . $item->id;
				$item->h5_url = get_base_url($page);
				$item->h5_qrcode = cfg('site_url') . '/index.php?g=Index&c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($item->h5_url);
				if (empty(cfg('pay_wxapp_appid'))) {
					$item->wxapp_qrcode = '';
				} else {
					$item->wxapp_qrcode = cfg('site_url') . '/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page=' . urlencode($page);
				}
				return $item;
			});
	}

	/**
	 * 获取问卷详情
	 * @param int $id
	 */
	public function getFormDetail(int $id, array $params = [])
	{
		$form = SurveyForm::where([
			'is_del' => 0,
			'id' => $id,
		])
			->with([
				'questions' => function ($query) use ($params) {
					$query->field('id, form_id, type, title, placeholder, required, sort, extra_config');
					if (!empty($params['submit_id'])) {
						$query->with(['question_options', 'submit_answer_detail' => function ($query) use ($params) {
							$query->where(['submit_id' => $params['submit_id']]);
						}]);
					} else {
						$query->with('question_options');
					}
				},
			]);
		if (!empty($params['fields'])) {
			$form = $form->field($params['fields']);
		}
		$form = $form->find();

		empty($form) && throw_exception('表单不存在');
		return $form;
	}

	/**
	 * 创建新表单
	 * @param array $data 表单数据
	 * @return int
	 */
	public function createForm(array $data): int
	{
		$this->validateFormData($data);

		return Db::transaction(function () use ($data) {
			$formData = $this->prepareFormData($data);
			$form = new SurveyForm();
			if($data['type'] == SurveyForm::TYPE_SIGNUP) {
				$formData['start_time'] = '';
				$formData['end_time'] = '';
			}

			$form->type = in_array($data['type'], [SurveyForm::TYPE_SURVEY, SurveyForm::TYPE_SIGNUP]) ? $data['type'] : SurveyForm::TYPE_SURVEY;
			$form->save($formData);
			$formId = $form->id;

			$this->saveQuestions($formId, $data['questions'] ?? []);

			return $formId;
		});
	}

	/**
	 * 更新表单
	 * @param int $formId 表单ID
	 * @param array $data 表单数据
	 * @return array
	 * @throws \Exception
	 */
	public function updateForm(int $formId, array $data): int
	{
		$form = SurveyForm::where('is_del', 0)->where('id', $formId)->find();
		if (!$form) {
			throw new Exception('表单不存在');
		}
		if ($form->status != SurveyForm::STATUS_DRAFT) {
			throw new Exception('非草稿表单不能修改');
		}
		$this->validateFormData($data);

		return Db::transaction(function () use ($form, $data) {

			$formData = $this->prepareFormData($data);
			$form->save($formData);

			$this->deleteQuestions($form->id);
			$this->saveQuestions($form->id, $data['questions'] ?? []);

			return $form->id;
		});
	}

	public function changeForm(int $formId, array $params): bool
	{
		$form = $this->getFormDetail($formId);
		if (!empty($params['status'])) {
			if ($params['status'] == SurveyForm::STATUS_DRAFT && $form->status == SurveyForm::STATUS_ENABLE) {
				throw_exception('问卷已发布，不能再修改为草稿');
			}
			$form->status = $params['status'];
		}
		!empty($params['is_del']) && $form->is_del = intval(boolval($params['is_del']));
		return $form->save();
	}

	private function prepareFormData(array $data): array
	{
		return [
			'title' => $data['title'],
			'description' => $data['description'] ?? '',
			'status' => $data['status'] ?? surveyForm::STATUS_DRAFT,
			'start_time' => !empty($data['start_time']) ? $data['start_time'] : Carbon::now()->startOfDay()->toDateTimeString(),
			'end_time' => !empty($data['end_time']) ? $data['end_time'] : Carbon::now()->endOfDay()->toDateTimeString(),
			'theme_config' => $data['theme_config'] ?? [],
			'submit_user_type' => !empty($data['submit_user_type']) ? $data['submit_user_type'] : 'all',
			'user_notice_switch' => !empty($data['user_notice_switch']) ? $data['user_notice_switch'] : 0,
			'max_submit_num' => !empty($data['max_submit_num']) ? $data['max_submit_num'] : 0,
			'max_day_submit_num' => !empty($data['max_day_submit_num']) ? $data['max_day_submit_num'] : 0,
			'show_result' => !empty($data['show_result']) ? $data['show_result'] : 0,
			'is_del' => 0,
		];
	}

	/**
	 * 保存问题及选项（公共方法）
	 * @param int $formId
	 * @param array $questions
	 * @throws Exception
	 */
	private function saveQuestions(int $formId, array $questions): void
	{
		foreach ($questions as $key => $question) {
			$questionData = [
				'form_id' => $formId,
				'type' => $question['type'],
				'title' => $question['title'],
				'placeholder' => $question['placeholder'] ?? '',
				'required' => $question['required'] ?? 0,
				'sort' => $key + 1,
				'extra_config' => $question['extra_config'] ?? '',
			];
			$surveyQuestion = new SurveyQuestion();
			$surveyQuestion->save($questionData);
			$this->saveOptions($surveyQuestion->id, $question['question_options'] ?? []);
		}
	}

	private function validateFormData(array $data): void
	{
		validate(FormValidate::class)->scene('save')->check($data);
		if (!empty($data['questions'])) {
			foreach ($data['questions'] as $question) {
				validate(QuestionValidate::class)->scene('add')->check($question);
			}
		}
	}

	/**
	 * 保存选项（公共方法）
	 * @param int $questionId
	 * @param array $options
	 * @throws Exception
	 */
	private function saveOptions(int $questionId, array $options): void
	{
		if (empty($options)) return;
		foreach ($options as $optSort => $option) {
			$optionData = [
				'question_id' => $questionId,
				'value' => $option['value'],
				'label' => $option['label'],
				'sort' => $optSort + 1,
				'extra_data' => $option['extra_data'] ?? '',
			];

			$optionModel = new SurveyQuestionOption();
			if (!$optionModel->save($optionData)) {
				throw new Exception('选项保存失败');
			}
		}
	}

	/**
	 * 删除问题及关联选项
	 * @param int $formId
	 * @throws \Exception
	 */
	private function deleteQuestions(int $formId): void
	{
		$questionIds = SurveyQuestion::where('form_id', $formId)->column('id');
		if ($questionIds) {
			SurveyQuestionOption::where('question_id', 'in', $questionIds)->delete();
		}
		SurveyQuestion::where('form_id', $formId)->delete();
	}

	public function sendFormNotice(): bool
	{
		$form = SurveyForm::where('status', SurveyForm::STATUS_ENABLE)
			->where('start_time', '<', Carbon::now())
			->where('end_time', '>', Carbon::now())
			->where('is_del', 0)
			->where('is_send_notice', 0)
			->find();
		if (empty($form)) {
			return false;
		}

		User::where('status', 1)->chunk(300, function ($users) use ($form) {
			$userType = $form->submit_user_type;
			foreach ($users as $user) {
				if ($userType == 'level_user') {
					$levelInfo = (new \app\common\model\service\UserService())->getLevelByUid($user->uid);
					if (!$levelInfo['is_level']) {
						continue;
					}
				}

				(new UserNoticeService())->addNotice([
					'type' => 8,
					'business' => 'survey',
					'order_id' => $form['id'],
					'uid' => $user['uid'],
					'title' => '问卷调查',
					'content' => $form->title,
				]);
			}
		});
		$form->is_send_notice = 1;
		return $form->save();
	}
}