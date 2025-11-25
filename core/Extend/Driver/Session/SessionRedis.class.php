<?php

defined('THINK_PATH') or exit();
class SessionRedis {
    //Redis连接对象
    private $redis;
    //Session过期时间
    private $expire;

    /**
     * 打开方法
     * @param type $path
     * @param type $name
     * @return type
     */
    public function open($path, $name) {
		$this->expire = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxLifetime');
		$this->redis = new Redis();
		$connected = $this->redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
		if(!$connected){
			die('REDIS 连接失败！请检查主机和端口，然后再检查REDIS有没有白名单限制');
		}
		$auth = C('REDIS_AUTH');
		if($auth){
			$authed    = $this->redis->auth($auth);
			if(!$authed){
				die('REDIS 验证密码时发生错误！请检查验证密码，然后再检查REDIS有没有白名单限制');
			}
		}
		return isset($authed) ? $authed : $connected;
    }

    /**
     * 关闭
     * @return type
     */
    public function close() {
        return $this->redis->close();
    }

    /**
     * 读取
     * @param string $id
     * @return type
     */
    public function read($id) {
        $id =  $id;
        $data = $this->redis->get($id);
        return $data ? $data : '';
    }

    /**
     * 写入
     * @param string $id
     * @param type $data
     * @return type
     */
    public function write($id, $data) {
        $id = $id;

        return $this->redis->set($id, $data, $this->expire);
    }

    /**
     * 销毁
     * @param string $id
     */
    public function destroy($id) {
        $id = $id;
        $this->redis->delete($id);
    }

    /**
     * 垃圾回收
     * @param type $maxLifeTime
     * @return boolean
     */
    public function gc($maxLifeTime) {
        return true;
    }
    /**
     * 打开Session
     * @access public
     */
    public function execute() {
        session_set_save_handler(array(&$this,"open"),
            array(&$this,"close"),
            array(&$this,"read"),
            array(&$this,"write"),
            array(&$this,"destroy"),
            array(&$this,"gc"));
    }
}