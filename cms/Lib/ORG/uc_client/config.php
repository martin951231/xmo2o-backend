<?php
//======以下代码是从UCenter用户中心后台拷贝出来粘贴到下面即可===============
/**
 * 操作步骤如下
 * 1.进入UCenter用户中心后台
 * 2.找到左侧菜单[应用管理]
 * 3.点击[添加新应用]按钮
 * 4.进入新应用界面,[应用类型]选择[其他]
 * 5.[应用名称]这里可以取个好记的名字
 * 6.[应用的主URL]这里填写[http://域名|IP:PORT/index.php/UCenter/Api]
 * 7.[通信密钥]可以填写自己的密钥,不填写保存后会自动生成
 * 8.[是否开启同步登录/是否接受通知]选择[是]单选按钮
 * 9.保存成功后,返回[应用列表]后[通信情况]是红色的[通信失败]
 * 10.进入编辑,把UCenter后台生成的配置文件拷贝到UCenter模块的Conf目录中的config.php文件中保存即可(可参考我写的文件).
 * 11.再次保存后,返回应用列表会变成绿色的[通信成功]字样
 */
//参考以下是测试生成的代码
define('UC_CONNECT', 'mysql');
define('UC_DBHOST', '127.0.0.1');
define('UC_DBUSER', 'root');
define('UC_DBPW', 'cxk4DiD2J25KdY8K');
define('UC_DBNAME', 'bbs');
define('UC_DBCHARSET', 'utf8');
define('UC_DBTABLEPRE', '`bbs`.zx2020_ucenter_');
define('UC_DBCONNECT', '0');
define('UC_KEY', 'Tek0S1c2tdX768d3bf0bNcqd31h0MftfRetfz594C03eReb3p7ldI1J8Jdf3U1C1');
define('UC_API', 'https://bbs.zhongxiang2020.com/uc_server');
define('UC_CHARSET', 'utf-8');
define('UC_IP', '');
define('UC_APPID', '3');
define('UC_PPP', '20');



define('DZ_DBTABLEPRE', '`cter1332`.pre_');

define('IN_DISCUZ', TRUE);
define('UC_CLIENT_VERSION', '1.6.0');	//note UCenter 版本标识
define('UC_CLIENT_RELEASE', '20110501');
define('API_DELETEUSER', 1);		//note 用户删除 API 接口开关
define('API_RENAMEUSER', 1);		//note 用户改名 API 接口开关
define('API_GETTAG', 1);		//note 获取标签 API 接口开关
define('API_SYNLOGIN', 1);		//note 同步登录 API 接口开关
define('API_SYNLOGOUT', 1);		//note 同步登出 API 接口开关
define('API_UPDATEPW', 1);		//note 更改用户密码 开关
define('API_UPDATEBADWORDS', 1);	//note 更新关键字列表 开关
define('API_UPDATEHOSTS', 1);		//note 更新域名解析缓存 开关
define('API_UPDATEAPPS', 1);		//note 更新应用列表 开关
define('API_UPDATECLIENT', 1);		//note 更新客户端缓存 开关
define('API_UPDATECREDIT', 1);		//note 更新用户积分 开关
define('API_GETCREDITSETTINGS', 1);	//note 向 UCenter 提供积分设置 开关
define('API_GETCREDIT', 1);		//note 获取用户的某项积分 开关
define('API_UPDATECREDITSETTINGS', 1);	//note 更新应用积分设置 开关

define('API_RETURN_SUCCEED', '1');
define('API_RETURN_FAILED', '-1');
define('API_RETURN_FORBIDDEN', '-2');

define('UCMSG_REGISTER_SUCCESS', '用户注册成功');

define('COOKIE_PREFIX', 'zqYC_2132_');
define('UCMSG_REGISTER_CODE1', '用户名不合法');
define('UCMSG_REGISTER_CODE2', '包含不允许注册的词语');
define('UCMSG_REGISTER_CODE3', '用户名已经存在');
define('UCMSG_REGISTER_CODE4', 'Email 格式有误');
define('UCMSG_REGISTER_CODE5', 'Email 不允许注册');
define('UCMSG_REGISTER_CODE6', '用该 Email 已经被注册');
define('UCMSG_REGISTER_CODE7', '未定义或未知的注册错误');
define('UCMSG_NOT_LOGIN', '用户尚未登录，请先登录！');
define('UCMSG_LOGIN_SUCCESS', '用户登录成功');
define('UCMSG_LOGIN_CODE1', '用户不存在,或者被删除');
define('UCMSG_LOGIN_CODE2', '用户名和密码不对应，或密码错误');
define('UCMSG_LOGIN_CODE3', '安全提问错误');
define('UCMSG_LOGIN_CODE4', '未定义或未知的登录错误');
define('UCMSG_USEREDIT_SUCCESS', '用户资料更新成功');
define('UCMSG_USEREDIT_NOTHING', '没有做任何修改');
define('UCMSG_USEREDIT_CODE1', '旧密码不正确');
define('UCMSG_USEREDIT_CODE4', 'Email 格式有误');
define('UCMSG_USEREDIT_CODE5', 'Email 不允许注册');
define('UCMSG_USEREDIT_CODE6', '该 Email 已经被注册');
define('UCMSG_USEREDIT_CODE7', '没有做任何修改');
define('UCMSG_USEREDIT_CODE8', '该用户受保护无权限更改');
define('UCMSG_LOGOUT_SUCCESS', '成功退出系统登录状态');
