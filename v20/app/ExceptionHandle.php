<?php
namespace app;

use app\http\exceptions\CustomException;
use app\http\exceptions\ParametersException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response | \json
     */
    public function render($request, Throwable $e): Response
    {
        $data = [
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTraceAsString()
        ];
        fdump_sql($data, 'system_error');

        // 参数验证错误
        if ($e instanceof ParametersException || $e instanceof ValidateException) {

            return api_output(1001, [], $e->getError());
        }else if ($e instanceof CustomException) {//自定义异常

            return api_output( $e->getErrorCode(), [], $e->getError());
        }else if($e instanceof \think\Exception){
            if($e->getCode() > 0){
                return api_output_error($e->getCode(), $e->getMessage());
            }
            else{
                $filter = '\\app\\';
                $exp = explode($filter, $e->getFile());
                return api_output_error(1005, 'File:'. ($exp[1] ?? '').' Line:'.$e->getLine().' Msg:'.$e->getMessage());
            }
        }
        // 添加自定义异常处理机制
        // return api_output_error(1005, $e->getMessage());
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
