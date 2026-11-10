<?php
namespace app\community\controller\village_api\User;

use app\BaseController;
use app\community\model\service\CommunityAgentService;
use token\Ticket;

class CommunityAgentController extends BaseController
{
    public function start()
    {
        return $this->handle(function ($service, $uid) {
            return $service->start($uid, $this->positiveInt('village_id'), $this->positiveInt('pigcms_id'), $this->text('message'), $this->text('intent'));
        });
    }

    public function detail()
    {
        return $this->handle(function ($service, $uid) { return $service->detail($uid, $this->text('session_id')); });
    }

    public function choose()
    {
        return $this->handle(function ($service, $uid) {
            $form=$this->request->post('form',[]);
            if (is_string($form)) { $form=json_decode($form,true); }
            if (!is_array($form)) { throw new \InvalidArgumentException('表单格式错误'); }
            return $service->choose($uid, $this->text('session_id'), $this->positiveInt('version'), $this->text('decision'), $this->text('intent'), $form);
        });
    }

    public function saveDraft()
    {
        return $this->handle(function ($service, $uid) {
            $form = $this->request->post('form', []);
            if (is_string($form)) { $form = json_decode($form, true); }
            if (!is_array($form)) { throw new \InvalidArgumentException('表单格式错误'); }
            return $service->saveDraft($uid, $this->text('session_id'), $this->positiveInt('version'), $form);
        });
    }

    public function submit()
    {
        return $this->handle(function ($service, $uid) {
            $confirmed = $this->request->post('confirmed');
            return $service->submit($uid, $this->text('session_id'), $this->positiveInt('version'),
                in_array($confirmed, [true, 1, '1'], true));
        });
    }

    public function back()
    {
        return $this->handle(function ($service,$uid) {
            $form=$this->request->post('form',[]);
            if (is_string($form)) { $form=json_decode($form,true); }
            if (!is_array($form)) { throw new \InvalidArgumentException('表单格式错误'); }
            return $service->back($uid,$this->text('session_id'),$this->positiveInt('version'),$form);
        });
    }

    public function cancel()
    {
        return $this->handle(function ($service, $uid) {
            return $service->cancel($uid, $this->text('session_id'), $this->positiveInt('version'));
        });
    }

    private function handle(callable $action)
    {
        if (!$this->request->isPost()) { return api_output_error(1001, '请使用POST请求'); }
        if (!config('community_agent.enabled')) { return api_output_error(1001, '智能办事暂未开放'); }
        try {
            // The mini-program common.postV20 sends this resident ticket + Device-Id.
            // Verify it directly: a management v20 token with a colliding numeric ID
            // must never be interpreted as a resident. Posted uid is never used.
            $ticket = $this->text('ticket');
            $deviceId = $this->text('Device-Id');
            if ($ticket === '' || $deviceId === '') { return api_output_error(1002, '请先登录'); }
            $user = Ticket::get($ticket, $deviceId, true);
            $uid = isset($user['uid']) ? (int)$user['uid'] : 0;
            if ($uid < 1) { return api_output_error(1002, '登录失效，请重新登录'); }
            return api_output(0, $action(new CommunityAgentService(), $uid));
        } catch (\InvalidArgumentException $e) {
            return api_output_error(1001, $e->getMessage());
        } catch (\Throwable $e) {
            // Do not expose SQL, tickets, phone numbers or model provider errors.
            return api_output_error(-1, '办理服务暂时不可用，请稍后重试或查询办理结果');
        }
    }

    private function text($name)
    {
        $value = $this->request->post($name, '');
        if (!is_string($value)) { throw new \InvalidArgumentException('参数格式错误：'.$name); }
        return trim($value);
    }

    private function positiveInt($name)
    {
        $value = $this->request->post($name, 0);
        if ((!is_string($value) && !is_int($value)) || !ctype_digit((string)$value) || (int)$value < 1 || strlen((string)$value) > 10) {
            throw new \InvalidArgumentException('参数格式错误：'.$name);
        }
        return (int)$value;
    }
}
