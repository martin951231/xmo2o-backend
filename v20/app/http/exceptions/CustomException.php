<?php
namespace app\http\exceptions;

use think\Exception;

class CustomException extends Exception
{
    protected $error;

    public function __construct($error, $code = 400)
    {
        parent::__construct();

        $this->error   = $error;
        $this->message = is_array($error) ? implode(PHP_EOL, $error) : $error;
        $this->code    = $code;

    }

    /**
     * 获取错误信息
     * @access public
     * @return array|string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取错误信息
     * @access public
     * @return array|string
     */
    public function getErrorCode()
    {
        return $this->code;
    }
}
