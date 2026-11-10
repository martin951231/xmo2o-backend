<?php
namespace app\community\model\service;

class CommunityAgentIntentClient
{
    public function recognize($message)
    {
        $key = (string)config('community_agent.service_key');
        $url = (string)config('community_agent.intent_url');
        $parts = parse_url($url);
        if (strlen($key) < 32 || !$parts || !isset($parts['host']) ||
            !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) { return $this->fallback('agent_config_invalid'); }
        // Unencrypted transport is allowed only on loopback.
        if ($parts['scheme'] === 'http' && !in_array($parts['host'], ['127.0.0.1', 'localhost', '[::1]'], true)) {
            return $this->fallback('agent_insecure_url');
        }
        if (!function_exists('curl_init')) { return $this->fallback('agent_curl_unavailable'); }
        $curl = curl_init($url);
        if (!$curl) { return $this->fallback('agent_connection_failed'); }
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['message'=>$message], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Community-Agent-Key: '.$key],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errno = curl_errno($curl);
        curl_close($curl);
        return $this->decodeResponse($message, $body, (int)$status, $errno);
    }

    public function decodeResponse($message, $body, $status, $errno = 0)
    {
        if ($errno) {
            $code = $errno === 28 ? 'agent_timeout' : (in_array($errno, [35, 51, 58, 60], true) ? 'agent_tls_error' : 'agent_connection_failed');
            return $this->fallback($code, $status);
        }
        if ($status !== 200) {
            $code = in_array($status, [401, 403], true) ? 'agent_auth_failed' : ($status === 404 ? 'agent_endpoint_not_found' : ($status === 503 ? 'agent_unavailable' : 'agent_http_error'));
            return $this->fallback($code, $status);
        }
        if (!is_string($body)) { return $this->fallback('agent_invalid_response', $status); }
        $result = json_decode($body, true);
        $allowed = array_column(CommunityAgentPickupPolicy::options(), 'value');
        $routing = [];
        if (is_array($result) && (array_key_exists('decision', $result) || array_key_exists('operation', $result))) {
            if (!in_array($result['decision'] ?? '', ['OPEN_BUSINESS','CLARIFY','UNSUPPORTED'], true) ||
                !in_array($result['operation'] ?? '', ['create','query','consult','cancel','update','unknown'], true) ||
                !is_string($result['decision_reason'] ?? null) || strlen($result['decision_reason']) > 80 ||
                !is_string($result['decision_message'] ?? null) || mb_strlen($result['decision_message'], 'UTF-8') > 300) {
                return $this->fallback('agent_invalid_response', $status);
            }
            $open = $result['decision'] === 'OPEN_BUSINESS';
            $candidate=$result['decision']==='CLARIFY' && ($result['action'] ?? '')==='confirm_intent' &&
                ($result['status'] ?? '')==='uncertain' && in_array($result['intent'] ?? '',$allowed,true) &&
                in_array($result['operation'],['create','unknown'],true);
            if (($open && ($result['operation'] !== 'create' || !in_array($result['action'] ?? '', ['confirm_intent','open_business'],true) ||
                    !in_array($result['intent'] ?? '', $allowed, true) || ($result['status'] ?? '') !== 'recognized')) ||
                (!$open && !$candidate && (($result['action'] ?? '') !== 'select_intent' || ($result['intent'] ?? '') !== 'unknown'))) {
                return $this->fallback('agent_invalid_response', $status);
            }
            foreach (['decision','operation','decision_reason','decision_message'] as $field) { $routing[$field] = $result[$field]; }
        }
        if (is_array($result) && ($result['action'] ?? '') === 'select_intent' && ($result['intent'] ?? '') === 'unknown') {
            $reason = $result['reason'] ?? '';
            $known = ['uncertain','no_matching_intent','low_confidence','multiple_intents','recognition_unavailable',
                'model_error','model_timeout','model_connection_failed','model_auth_failed','model_rate_limited','model_invalid_response'];
            $failure = $this->fallback(in_array($reason, $known, true) ? $reason : 'agent_invalid_response', $status);
            if ($failure['recognition']['status'] === 'error' && isset($result['diagnostics']) && is_array($result['diagnostics'])) {
                $failure['recognition']['diagnostics'] = $result['diagnostics'];
            }
            // Preserve provider failure diagnostics; only navigation explanations replace ordinary ambiguity messages.
            if ($routing && $failure['recognition']['status'] !== 'error') {
                $failure['recognition']['message'] = $routing['decision_message'];
                $failure['recognition']['code'] = $routing['decision_reason'];
            }
            return array_merge($failure, $routing);
        }
        if (!is_array($result) || !in_array($result['action'] ?? '', ['confirm_intent','open_business'],true) ||
            !in_array($result['intent'] ?? '', $allowed, true)) { return $this->fallback('agent_invalid_response', $status); }
        if (!$routing) {
            if (($result['action'] ?? '')!=='confirm_intent' || !in_array($result['status'] ?? 'recognized',['recognized','uncertain'],true)) { return $this->fallback('agent_invalid_response',$status); }
            // Older classifiers used confirm_intent for every recognized request.
            // Do not reintroduce that redundant screen, or turn explicit queries into creation.
            if (preg_match('/取消|撤销|修改|改期|进度|查询|查一下|多久|怎么办|多少钱|规则|如何|怎样|怎么|不要|不想|不是|假如|假设/u',$message)) {
                return $this->fallback('no_matching_intent',$status);
            }
            $uncertain=($result['status'] ?? '')==='uncertain' || ($result['reason'] ?? '')==='low_confidence';
            $routing=['decision'=>$uncertain ? 'CLARIFY' : 'OPEN_BUSINESS','operation'=>'create','decision_reason'=>$uncertain ? 'low_confidence' : 'recognized','decision_message'=>$uncertain ? '请确认是否需要办理这项服务' : '请核对业务信息后提交'];
        }
        $prefill = [];
        if ($result['intent'] === 'express_pickup') {
            foreach (['pickup_code', 'track_num', 'recipient_name', 'recipient_phone'] as $field) {
                $value = $result['prefill'][$field] ?? '';
                if (is_string($value) && $value !== '' && mb_strlen($value, 'UTF-8') <= 64 && strpos($message, $value) !== false) {
                    $prefill[$field] = $value;
                }
            }
        }
        // Accept older Python responses during a rolling deployment.
        if ($result['intent']==='service_booking' && ($result['booking_kind'] ?? '')==='activity') { $result['intent']='activity_signup'; }
        $kind=$result['intent']==='activity_signup' ? 'activity' : ($result['intent']==='service_booking' ? 'merchant' : '');
        $keyword=$result['activity_keyword'] ?? '';
        if ($kind!=='activity' || !is_string($keyword) || mb_strlen($keyword)>60 || ($keyword!=='' && strpos($message,$keyword)===false)) { $keyword=''; }
        $confirm=$routing['decision']==='CLARIFY';
        return array_merge(['intent'=>$result['intent'], 'action'=>$confirm ? 'confirm_intent' : 'open_business', 'prefill'=>$prefill, 'booking_kind'=>$kind, 'activity_keyword'=>$keyword,
            'recognition'=>['status'=>$confirm ? 'uncertain' : 'recognized', 'code'=>$routing['decision_reason'], 'message'=>$confirm ? '请确认是否需要办理这项服务' : $routing['decision_message'], 'retryable'=>false, 'http_status'=>$status]], $routing);
    }

    private function fallback($code, $httpStatus = 0)
    {
        $messages = [
            'agent_config_invalid'=>'Agent 地址或服务密钥未正确配置',
            'agent_insecure_url'=>'Agent 非本机地址必须使用 HTTPS',
            'agent_curl_unavailable'=>'PHP 未启用 cURL 扩展',
            'agent_timeout'=>'调用 Agent 服务超时，请稍后重试或手动选择',
            'agent_connection_failed'=>'无法连接 Agent 服务，请检查服务和网络',
            'agent_tls_error'=>'Agent HTTPS 连接或证书校验失败',
            'agent_auth_failed'=>'Agent 服务鉴权失败，请检查服务密钥',
            'agent_endpoint_not_found'=>'Agent 接口不存在，请检查接口地址和部署版本',
            'agent_unavailable'=>'Agent 服务不可用或尚未配置',
            'agent_http_error'=>'Agent 返回异常 HTTP 状态',
            'agent_invalid_response'=>'Agent 返回格式异常，请检查接口版本',
            'uncertain'=>'未能明确识别需求，请手动选择',
            'no_matching_intent'=>'未识别到支持的业务类型，请手动选择',
            'low_confidence'=>'识别结果置信度不足，请手动确认需求类型',
            'multiple_intents'=>'识别到多个需求，请先选择一项办理',
            'recognition_unavailable'=>'Agent 报告识别服务不可用（旧版响应）',
            'model_error'=>'大模型调用失败，可稍后重试或手动选择',
            'model_timeout'=>'大模型识别超时，可重试或手动选择',
            'model_connection_failed'=>'Agent 无法连接大模型服务',
            'model_auth_failed'=>'大模型服务鉴权失败，请联系管理员',
            'model_rate_limited'=>'大模型服务限流，请稍后重试',
            'model_invalid_response'=>'大模型返回格式异常，可重试或手动选择',
        ];
        $unrecognized = in_array($code, ['uncertain','no_matching_intent','low_confidence','multiple_intents'], true);
        return ['intent'=>'unknown', 'action'=>'select_intent', 'prefill'=>[], 'recognition'=>[
            'status'=>$unrecognized ? 'unrecognized' : 'error', 'code'=>$code, 'message'=>$messages[$code],
            'retryable'=>in_array($code, ['agent_timeout','agent_connection_failed','agent_unavailable','agent_http_error',
                'model_error','model_timeout','model_connection_failed','model_rate_limited','model_invalid_response','recognition_unavailable'], true),
            'http_status'=>(int)$httpStatus,
        ]];
    }
}
