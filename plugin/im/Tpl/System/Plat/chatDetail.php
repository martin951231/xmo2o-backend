<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>管理页面</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
    <link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
    <link rel="stylesheet" href="{pigcms{$plugin_public}im/css/base.css" media="all"/>
</head>
<body>
<!--<input type="请输入搜索的聊天内容"/>-->
<div style="width: 70%;margin-left: 15%;padding: 20px;">
    <ul>
        <empty name="messages">
            <p style="text-align: center">暂无历史</p>
        <else/>
            <volist name="messages" id="m">
                <eq name="m.is_from_user" value="1">
                    <li style="margin-top: 15px;text-align: right">
                        <p style="margin-right: 5px;margin-bottom: 5px;font-size: 1em;color: green">{pigcms{$m.date} {pigcms{$m.from_nickname}</p>
                        <div>
                            <if condition="$m['msg_type'] eq 'text'">
                                <span style="padding: 5px 5px;background: #d0e1f5">{pigcms{$m['msg_body']['text']}</span>
                                <else/>
                                <div style="padding: 5px 5px;background: #d0e1f5;display: inline-block">
                                    <a href="{pigcms{$m['msg_body']['url']}"  target="_blank">
                                        <img src="{pigcms{$m['msg_body']['url']}" style="max-height: 65px">
                                    </a>
                                </div>
                            </if>
                            <img src="{pigcms{$m.from_avatar}" style="width:35px;height:35px;border-radius: 35px;border: 1px solid #ddd"/>
                        </div>
                    </li>
                    <else/>
                    <li style="margin-top: 15px">
                        <p style="margin-left: 5px;margin-bottom: 5px;font-size: 1em;color: blue">{pigcms{$m.from_nickname} {pigcms{$m.date}</p>
                        <div>
                            <img src="{pigcms{$m.from_avatar}" style="width:35px;height:35px;border-radius: 35px;border: 1px solid #ddd"/>
                            <if condition="$m['msg_type'] eq 'text'">
                                <span style="padding: 5px 5px;background: #d0e1f5">{pigcms{$m['msg_body']['text']}</span>
                                <else/>
                                <div style="padding: 5px 5px;background: #d0e1f5;display: inline-block">
                                    <a href="{pigcms{$m['msg_body']['url']}"  target="_blank">
                                        <img src="{pigcms{$m['msg_body']['url']}" style="max-height: 50px">
                                    </a>
                                </div>
                            </if>
                        </div>
                    </li>
                </eq>
            </volist>
        </empty>
    </ul>
</div>

</body>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
</html>