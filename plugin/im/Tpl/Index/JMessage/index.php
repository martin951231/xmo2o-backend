<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{pigcms{$title}</title>
</head>
<style type="text/css">
    ul li {
        margin: 10px 3px
    }

    .conversation_checked {
        background: #0000ff
    }
</style>
<body>
<div>
    <div style="width: 30%;float:left;">
        <ul>
            <li onclick="cleanScreen()">清屏</li>
            <li onclick="login()">登录</li>
            <li onclick="sendSingleMsg()">文字</li>
            <li onclick="sendSinglePic()">图片</li>
            <input type="file" id="file_box">
            <li onclick="loginOut()">登出</li>
        </ul>
    </div>
    <div style="float: left;width: 70%">
        <div style="width:30%;float:left;height: 400px; background: black; color: #eeeeee;overflow-y: scroll;">
            <h3>会话列表</h3>
            <div id="conversation">

            </div>
        </div>
        <div style="width:30%;float:left;height: 400px; background: black; color: #eeeeee;overflow-y: scroll;">
            <h3>聊天记录</h3>
            <div id="chatrecords">

            </div>
        </div>
        <div style="width:30%;height: 400px; float:left;background: black; color: #eeeeee;overflow-y: scroll;">
            <h3>流程</h3>
            <div id="dashboard">

            </div>
        </div>
    </div>
</div>

</body>
<script src='./plugin/im/Tpl/Index/JMessage/jmessage-sdk-web.2.6.0.min.js'></script>
<script src="http://cdn.bootcss.com/jquery/3.1.1/jquery.min.js"></script>
<script>
    var domain = 'http://o2o-local-95.pigcms.com/PigO2O';

    function sendSingleCustom() {

    }


    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]);
        return null;
    }

    function loginOut() {
        JIM.loginOut();
        appendToDashboard('退出成功')
    }



    function sendSinglePic() {
        var fd = getFile();
        JIM.sendSinglePic({
            'target_username': toUser,
            'image': fd
        }).onSuccess(function (data, msg) {
            $.post(domain + '/plugin.php?plugin=im&c=JMessage&a=saveMessage', msg, function (rs) {
                getConversationList();
            })
            appendToDashboard(JSON.stringify(data));
            appendToDashboard(JSON.stringify(msg));
            return;

        }).onFail(function (data) {
            console.log('error:' + JSON.stringify(data));
            appendToDashboard('error: ' + JSON.stringify(data));
        });
    }



    function cleanScreen() {
        $('#dashboard').html('');
        $('#conversation').html('');
        $('#chatrecords').html('');
    }

    function add0(value) {
        return value >= 10 ? value : '0' + value;
    }

    function format_date(unix_timestamp) {
        var time = new Date(parseInt(unix_timestamp));
        var y = time.getFullYear();
        var m = time.getMonth() + 1;
        var d = time.getDate();
        var h = time.getHours();
        var mm = time.getMinutes();
        var s = time.getSeconds();
        return y + '-' + add0(m) + '-' + add0(d) + ' ' + add0(h) + ':' + add0(mm) + ':' + add0(s);
    }

    function appendToDashboard(text, id = 'dashboard') {
        $('#' + id).append('<p style="margin-left: 16px">' + text + '</p>')

    }

    function getFile() {
        var fd = new FormData();
        var file = document.getElementById('file_box');
        if (!file.files[0]) {
            appendToDashboard('error:' + '获取文件失败');
            throw new Error('获取文件失败');
        }
        fd.append(file.files[0].name, file.files[0]);
        return fd;
    }

    var fromType = getQueryString('type');
    var uuid = getQueryString('uuid');
    var fromUser = fromType + '_' + uuid;
    var relation = getQueryString('relation');
    var appkey = '';
    var toUser = '';

    if (fromType == '' || fromType == undefined || uuid == '' || uuid == undefined || relation == '' || relation == undefined) {
        alert('forbidden')
    } else {
        window.JIM = new JMessage({
            debug: false,
        });

        function init() {
            $.get(domain + '/plugin.php?plugin=im&c=JMessage&a=init', function (rs) {
                appkey = rs.result.appkey;
                JIM.init(rs.result).onSuccess(function (data) {
                    appendToDashboard('SDK初始化成功');
                    login()
                }).onFail(function (data) {
                    appendToDashboard('SDK初始化失败: ' + JSON.stringify(data));
                });
            })
        }

        init();


        function login() {
            if(JIM.isLogin()){
                return false;
            }
            var url = domain + '/plugin.php?c=JMessage&a=loginUser&plugin=im&type=' + fromType + '&uuid=' + uuid;
            $.get(url, function (rs) {
                JIM.login({
                    'username': rs.result.username,
                    'password': rs.result.password,
                    'is_md5': false
                }).onSuccess(function (data) {
                    //登录成功，初始化取会话列表
                    getConversationList();

                    appendToDashboard(data.username + '登录成功');
                    JIM.onMsgReceive(function (data) {
                        //监听收消息
                        var _html = '<li>' + format_date(data.messages[0].content.create_time) + '  ' + data.messages[0].content.msg_body.text + '</li>';
                        $('#chatrecords li').eq(0).before(_html)
                        getConversationList();
                        return
                    });


                }).onFail(function (data) {
                    appendToDashboard('登录失败:' + JSON.stringify(data));
                }).onTimeout(function (data) {
                    appendToDashboard('登录超时:' + JSON.stringify(data));
                });
            })
        }

        function getConversationList() {
            var url = domain + '/plugin.php?c=JMessage&a=conversations&plugin=im&type=' + fromType + '&uuid=' + uuid + '&relation=' + relation;
            $.get(url, function (rs) {
                if (rs.errorCode == 0) {
                    var _html = '';
                    if (fromType != 'user') {
                        _html += '未读消息总数:' + rs.result.sum_unread_msg_count + '<br/>';
                        _html += '最大连接数' + rs.result.connection.max + '当前连接数' + rs.result.connection.cur + '<br/>';
                    }
                    _html += '<ul>';
                    for (i in rs.result.conversations) {

                        _html += '<li onclick="select_conversation($(this),\'' + rs.result.conversations[i].username + '\')">' + rs.result.conversations[i].nickname + '|' + rs.result.conversations[i].unread_msg_count + '|' + rs.result.conversations[i].last_msg + '</li><br/>';
                    }
                    _html += '</ul>';
                    $('#conversation').html('');
                    appendToDashboard(_html, 'conversation');
                } else {
                    alert(rs.errorMsg);
                }
            })
        }

        function getResource(i, id) {
            JIM.getResource({'media_id': id})
                .onSuccess(function (data) {
                    console.log('success:' + JSON.stringify(data));
                    appendToDashboard('success: ' + JSON.stringify(data));
                }).onFail(function (data) {
                console.log('error:' + JSON.stringify(data));
                appendToDashboard('error: ' + JSON.stringify(data));
            });
        }

        function select_conversation(obj, username) {
            $(obj).addClass('conversation_checked').siblings('li').removeClass('conversation_checked')
            toUser = username;
            var url = domain + '/plugin.php?c=JMessage&a=getHistoryMessage&plugin=im&from_user=' + fromUser + '&to_user=' + toUser + '&pge=1&limit=10';
            $.get(url, function (rs) {
                if (rs.result.length > 0) {
                    var data = rs.result
                    var _html = '<ul>';
                    var cls = '';
                    for (i in data) {
                        if(data[i].is_from_user == 1){
                            cls='conversation_checked';
                        }else{
                            cls = '';
                        }
                        if(data[i].msg_type == 'image'){
                            _html += '<li class="'+cls+'">' + format_date(data[i].create_time * 1000) + '  <img style="width: 35px;height: 35px" src="' + data[i].msg_body.url + '"/></li>';
                        }else{
                            _html += '<li class="'+cls+'">' + format_date(data[i].create_time * 1000) + '  ' + data[i].msg_body.text + '</li>';
                        }

                    }
                    _html += '</ul>';
                } else {
                    _html = '暂无消息';
                }
                $('#chatrecords').html('')
                appendToDashboard(_html, 'chatrecords')
            })
        }


        function sendSingleMsg() {
            if (toUser == '') {
                alert('请选择一个会话');
                return false;
            }

            var content = prompt("请输入聊天内容:", "");

            JIM.sendSingleCustom({
                'target_username': toUser,
                'custom': {type:"text",body:{text:content}}
            }).onSuccess(function (data, msg) {
                $.post(domain + '/plugin.php?plugin=im&c=JMessage&a=saveMessage', msg, function (rs) {
                    getConversationList();
                })
                var _html = '<li>' + format_date(msg.content.create_time) + '  ' + content + '</li>';
                $('#chatrecords li').eq(0).before(_html)
                appendToDashboard('用户' + fromUser + '发送' + content);
                console.log(msg);
                return;
            }).onFail(function (data) {
                appendToDashboard('SendSingleCustom fail: ' + JSON.stringify(data));
            });
        }
    }


</script>
</html>