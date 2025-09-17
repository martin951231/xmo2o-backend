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
<style type="text/css">
    .layui-elem-quote {
        margin-bottom: 10px;
        padding: 8px;
        line-height: 20px;
        border-left: 5px solid #009688;
        border-radius: 0 2px 2px 0;
        background-color: #fff
    }

    .layui-form-label {
        width: 180px;
    }

    .layui-input {
        width: 30%;
    }

    .layui-textarea {
        width: 50%;
    }

    .item_div {
        background: #f2f2f2;
        padding: 10px;
        margin: 8px
    }

    .notice {
        color: #9f9f9f
    }

    .notice_margin {
        margin: 8px 8px 8px 100px;
        padding: 6px 0px;
    }
</style>

<div class="layui-tab layui-tab-card">
    <div class="layui-tab-content">
        <span class="layui-breadcrumb">
          <a>应用</a>
          <a>客服IM</a>
          <a href="{pigcms{:U('System/Config/store')}"><cite style="color: #0a94ff">店铺列表</cite></a>
        </span>
    </div>
    <ul class="layui-tab-title">
        <a href="{pigcms{:U('System/Config/kefu',array('store_id'=>$store_id))}"><li>客服列表</li></a>
        <a href="{pigcms{:U('System/Config/shortPhrases',array('store_id'=>$store_id))}"><li>快捷回复</li></a>
        <a href="{pigcms{:U('System/Config/menu',array('store_id'=>$store_id))}"><li>自助菜单</li></a>
        <a href="{pigcms{:U('System/Config/privilege',array('store_id'=>$store_id))}"><li class="layui-this">权限配置</li></a>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab-item layui-show">
            <form class="layui-form" action="">
                <input type="hidden" name="store_id" value="{pigcms{$store_id}"/>
                <p class="layui-elem-quote">会话超时设置</p>

                <!--客户未响应设置-->
                <div class="item_div">
                    <div class="layui-form-item">
                        <label class="layui-form-label">客户未响应设置</label>
                        <div class="layui-input-block">
                            <input type="checkbox" <if condition="empty($kefuParams) || $kefuParams['customer_unrespon_switch'] == 1">checked="" value="1"</if> name="customer_unrespon_switch" lay-skin="switch" lay-filter="switchTest" lay-text="ON|OFF">
                        </div>
                        <p class="notice">
                            开启后，在设置的时间内客户没有新消息产生，系统将自动关闭会话，客户再发消息会触发重新分配；在自动关闭前2分钟，客户和客服都会收到即将关闭的提示语</p>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">超时时间</label>
                        <div class="layui-input-block">
                            <input type="number" name="customer_unrespon_min" lay-verify="up_3_minute" value="{pigcms{$kefuParams['customer_unrespon_min']|default=3}"
                                   class="layui-input"
                                   style="display: inline-block;width: 20%" min="3"/>
                            分钟&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: red">只可设置大于或等于3的正整数</span>
                        </div>
                    </div>

                    <div class="layui-form-item layui-form-text">
                        <label class="layui-form-label">超时提示</label>
                        <div class="layui-input-block">
                            <textarea placeholder="请输入内容" class="layui-textarea"
                                      name="customer_unrespon_tips">{pigcms{$kefuParams['customer_unrespon_tips']|default='您好，由于我们长时间没有收到您的回复，稍后将自动结束本次对话。'}</textarea>
                            <p class="notice notice_margin">该提示将在会话关闭前2分钟发送给客户</p>
                        </div>
                    </div>
                </div>

                <!--客服未接待-->
                <div class="item_div">
                    <div class="layui-form-item">
                        <label class="layui-form-label">客服未接待设置</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="customer_unreceived_switch" lay-skin="switch"
                                   lay-filter="switchTest"
                                   lay-text="ON|OFF" <if condition="empty($kefuParams) || $kefuParams['customer_unreceived_switch'] == 1">checked="" value="1"</if>>
                        </div>
                        <p class="notice">开启后，在设置的时间内客服没有回复客户，系统将自动关闭会话，客户继续发消息会触发重新分配；在自动关闭前2分钟，客服与客户均会收到即将关闭的提示语</p>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">超时时间</label>
                        <div class="layui-input-block">
                            <input type="number" name="customer_unreceived_min" lay-verify="up_30_minute" value="{pigcms{$kefuParams['customer_unreceived_min']|default=30}"
                                   class="layui-input"
                                   style="display: inline-block;width: 20%"  min="30"/>
                            分钟&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: red">只可设置大于或等于30的正整数</span>
                        </div>
                    </div>

                    <div class="layui-form-item layui-form-text">
                        <label class="layui-form-label">超时提示</label>
                        <div class="layui-input-block">
                            <textarea placeholder="请输入内容" class="layui-textarea"
                                      name="customer_unreceived_tips">{pigcms{$kefuParams['customer_unreceived_tips']|default='由于长时间没有回复客户的咨询，稍后系统将自动结束会话。'}</textarea>
                            <p class="notice notice_margin">该提示将在会话关闭前2分钟发送给客服</p>
                        </div>
                    </div>
                </div>
                <!-- <p class="layui-elem-quote">会话分配规则</p>
                <div class="item_div">
                    <div class="layui-form-item">
                        <label class="layui-form-label">按饱和度分配客户给客服</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="saturation_assign_kefu" lay-skin="switch" disabled
                                   lay-filter="switchTest" lay-text="ON|OFF" <if condition="empty($kefuParams) || $kefuParams['saturation_assign_kefu'] == 1">checked="" value="1"</if>>
                        </div>
                        <p class="notice">即当有新客户需要分配给客服时，会按照每个客服的接待饱和度进行智能分配（饱和度=正在接待客户数/最大同时接待客户数）；强制开启，不可关闭；当需要用到商家维度的客服时，则优先使用主店铺里的客服人员。 </p>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">是否开启优先分配到上次的客服</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="priority_assign_kefu" lay-skin="switch"
                                   lay-filter="switchTest"
                                   lay-text="ON|OFF" <if condition="empty($kefuParams) || $kefuParams['priority_assign_kefu'] == 1">checked="" value="1"</if>>
                        </div>
                        <p class="notice">即当有新客户需要分配给客服时，如果优先分配的客服饱和度不是百分百，会优先分配给上次服务过的客服</p>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">是否开启指定客服分配</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="appoint_assign_kefu" lay-skin="switch"
                                   lay-filter="switchTest"
                                   lay-text="ON|OFF" <if condition="empty($kefuParams) || $kefuParams['appoint_assign_kefu'] == 1">checked="" value="1"</if>>
                        </div>
                        <p class="notice">即当有新客户需要分配给客服时，如果分配的客服饱和度不是百分百，会将指定客户指定分配给客服</p>
                    </div>
                </div> -->

                <p class="layui-elem-quote">自动回复</p>
                <div class="item_div">
                    <div class="layui-form-item">
                        <label class="layui-form-label">首句欢迎语</label>
                        <div class="layui-input-block">
                            <input type="text" name="welcome" lay-verify="title" autocomplete="off"
                                   placeholder="请输入首句欢迎语"
                                   class="layui-input" value="{pigcms{$kefuParams['welcome']|default='你好，欢迎光临。'}">
                            <p style="color: blue;margin:10px 100px;cursor:pointer" onclick="inert_kf_nickname()">插入客服昵称</p>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">下班提示语</label>
                        <div class="layui-input-block">
                            <input type="text" name="off_duty_notice" lay-verify="title" autocomplete="off"
                                   placeholder="请输入下班提示语"
                                   class="layui-input" value="{pigcms{$kefuParams['off_duty_notice']|default=''}">
                        </div>
                    </div>
                </div>
                <div class="layui-form-item" style="margin: 8px;padding: 20px">
                    <button class="layui-btn layui-btn-radius" lay-submit="" lay-filter="save_privilege">保存更改</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>

<script type="text/javascript">
    function inert_kf_nickname(){
        layui.use('jquery', function () {
            var $ = layui.$;
            var obj = $('input[name=welcome]');
            obj.val(obj.val()+'{nickname}')
        })
    }

    layui.use('form', function () {
        var $ = layui.jquery
            , form = layui.form;

        form.verify({
            up_3_minute: function (value) {
                if (value < 3) {
                    return '请输入大于或等于3的正整数';
                }
            },
            up_30_minute: function(value){
                if (value < 30) {
                    return '请输入大于或等于30的正整数';
                }
            }
        });

        //监听提交
        form.on('submit(save_privilege)', function (data) {
            $.post("{pigcms{:U('System/Config/privilege')}", data.field, function (rs) {
                if (rs.error_code > 0) {
                    layer.msg(rs.error_msg, {time: 2000});
                } else {
                    layer.msg('保存成功', {time: 1000}, function () {
                        history.go(0)
                    })
                }
            })
            return false;
        });
    });
</script>

</body>
</html>