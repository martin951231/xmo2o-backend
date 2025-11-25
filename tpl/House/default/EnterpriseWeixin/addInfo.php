<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tablet"></i>
                <a href="{pigcms{:U('camera_add')}">门禁设置</a>
            </li>
            <li class="active">添加摄像机设备</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
                .form_tips {
                    line-height: 28px !important;
                }
                .tip_title {
                    color: rgb(38, 129, 243);
                }
                .other_tip {
                    color: #FF744A !important;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <button style="margin-bottom: 10px;" type="button" class="btn btn-success" onclick="return window.history.go(-1);">返回上一页</button>

                    <ul id="myTab" class="nav nav-tabs">
                        <li class="active">
                            <a href="#step1" data-toggle="tab">第一步</a>
                        </li>
                        <li>
                            <a href="#step2" data-toggle="tab">第二步</a>
                        </li>
                    </ul>
                    <form  class="form-horizontal" method="post" id="edit_form" action="{pigcms{:U('addInfo')}" enctype="multipart/form-data" onSubmit="return check_submit()" action="__SELF__">
                        <div class="tab-content">
                            <div id="step1" class="tab-pane active">

                                <div class="form-group" style="padding-left: 15px;">
                                    <if condition="$enterprise_wx_bind['book_permanent_code'] && $enterprise_wx_bind['book_permanent_code'] neq ''">
                                        <div style="font-weight: bolder;color: green;">授权状态：已经扫码授权绑定</div>
                                        <if condition="$enterprise_wx_bind['book_permanent_code_time'] && $enterprise_wx_bind['book_permanent_code_time'] gt 1">
                                            <span>【绑定时间：{pigcms{$enterprise_wx_bind['book_permanent_code_time']|date='Y-m-d H:i:s',###}】</span>
                                        </if>
                                        <else/>
                                        <div style="font-weight: bolder;color: red;">授权状态：未扫码授权</div>
                                    </if>
                                    <div>注意事项：</div>
                                    <div style="padding: 5px;">
                                        <div style="padding-top: 5px;">1. 如果没有出现授权二维码，请在pc登录企业微信然后进入【工作台】-【服务商助手】-【工具】-【通用配置】-【通讯录编辑授权】中下载【授权二维码】然后对应企业微信配置【通讯录编辑授权的授权二维码】处进行上传</div>
                                        <div style="padding-top: 5px;">
                                            2. 客户企业扫码授权服务商通讯录编辑权限时，需同时满足以下条件：
                                            <div style="padding: 3px 0 0 15px;;">（1）当前企业未开启“企业管理后台 - 管理工具 - 通讯录同步”的api接口同步</div>
                                            <div style="padding: 3px 0 0 15px;;">（2）当前企业未授权其他服务商通讯录编辑权限或第三方通讯录应用</div>
                                            <div style="padding: 3px 0 0 15px;;">（3）当前企业已安装该服务商分类为“人力资源”的第三方应用或代开发应用</div>
                                        </div>
                                        <div style="padding-top: 5px;">3. 如果已经扫码授权了，不用2次进行扫码直接进入第二步</div>
                                        <if condition="$enterprise_wx_bind['book_permanent_code'] && $enterprise_wx_bind['book_permanent_code'] neq ''">
                                            <div style="padding-top: 5px;color: #b33737">4. 如果已经绑定但是手动在企微那边解绑或者删除了请重新扫码</div>
                                        </if>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="enterprise_book_authorization_qrcode">授权二维码：</label></label>
                                    <img style="width:334px;height:520px;" src="{pigcms{$config['enterprise_book_authorization_qrcode']}"/>
                                </div>
                            </div>
                            <div id="step2" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="qy_address_book_secret">当前绑定企业ID</label></label>
                                    <input class="col-sm-4" value="{pigcms{$enterprise_wx_bind['PlaintextCorpId']}" size="40" name="PlaintextCorpId" id="PlaintextCorpId" type="text"/>
                                    <span class="form_tips">
                                        前往授权企业微信<span class="other_tip">我的企业</span>><span class="other_tip">企业ID</span>复制填写到这里,<span style="color: #FF4A4AFF !important;">注意优先配置</span>
                                    </span>
                                </div>
<!--                                <if condition="$oneJudge">-->
<!--                                    <div class="form-group">-->
<!--                                        <label class="col-sm-1"><label for="qy_address_book_secret">通讯录同步secret</label></label>-->
<!--                                        <input class="col-sm-4" value="{pigcms{$enterprise_wx_bind['qy_address_book_secret']}" size="40" name="qy_address_book_secret" id="qy_address_book_secret" type="text"/>-->
<!--                                        <span class="form_tips">前往授权企业微信 <span class="other_tip">管理工具</span>><span class="other_tip">通讯录同步</span>><span class="other_tip">权限[编辑]</span>><span class="other_tip">选择API编辑通讯录</span>><span class="other_tip">Secret查看</span>><span class="other_tip">发送</span> 复制获取到的Secret填写到这里</span>-->
<!--                                    </div>-->
<!--                                </if>-->

                                <if condition="$hasAlreadyConfig eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="">&nbsp;</label></label>
                                        <label class="tip_title">
                                            完成下面客户联系配置填写完成后
                                            前往授权企业微信<span class="other_tip">客户联系</span>><span class="other_tip">客户</span>><span class="other_tip">API</span>><span class="other_tip">接收事件服务器[编辑]</span> 点击保存</span>
                                            &nbsp;记得保存成功后 在<span class="other_tip">可调用应用</span>选中 聊天侧边栏 中的自建应用
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="contact_way_secret">客户Secret</label></label>
                                        <if condition="$enterprise_wx_bind['contact_way_time'] gt 0 && $enterprise_wx_bind['contact_way_time']">
                                            <input class="col-sm-4" name="contact_way_secret" value="{pigcms{$enterprise_wx_bind['contact_way_secret']}" type="text" />
                                            <else/>
                                            <input class="col-sm-4" value="{pigcms{$enterprise_wx_bind['contact_way_secret']}" size="40" name="contact_way_secret" id="contact_way_secret" type="text"/>
                                        </if>
                                        <span class="form_tips">前往授权企业微信 <span class="other_tip">客户联系</span>><span class="other_tip">客户</span>><span class="other_tip">API</span>><span class="other_tip">Secret查看</span>><span class="other_tip">发送</span> 复制获取到的Secret填写到这里</span>
                                    </div>
                                    <if condition="$receiveUrl">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label for="qy_address_book_secret">接收事件服务器URL</label></label>
                                            <input class="col-sm-4" id="receiveUrl" value="{pigcms{$receiveUrl}" type="text" readonly/>
                                            <span class="form_tips">
                                                <div style="line-height: 16px;" class="btn btn-success" onclick="copyContent();">点击复制</div>
                                                复制该链接到填写到授权企业微信 <span class="other_tip">客户联系</span>><span class="other_tip">客户</span>><span class="other_tip">API</span>><span class="other_tip">接收事件服务器[编辑]</span>><span class="other_tip">URL</span></span>
                                            </span>
                                        </div>
                                    </if>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="contact_way_token">接收事件服务器Token</label></label>
                                        <if condition="$enterprise_wx_bind['contact_way_time'] gt 0 && $enterprise_wx_bind['contact_way_token']">
                                            <input class="col-sm-4" name="contact_way_token" value="{pigcms{$enterprise_wx_bind['contact_way_token']}" type="text" />
                                            <else/>
                                            <input class="col-sm-3" value="{pigcms{$enterprise_wx_bind['contact_way_token']}" size="40" name="contact_way_token" id="contact_way_token" type="text"/>
                                        </if>
                                        <span class="form_tips">前往授权企业微信 <span class="other_tip">客户联系</span>><span class="other_tip">客户</span>><span class="other_tip">API</span>><span class="other_tip">接收事件服务器[编辑]</span>><span class="other_tip">Token[随机获取]</span></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="contact_way_encodingaeskey">接收事件服务器EncodingAESKe</label></label>
                                        <if condition="$enterprise_wx_bind['contact_way_time'] gt 0 && $enterprise_wx_bind['contact_way_encodingaeskey']">
                                            <input class="col-sm-4" name="contact_way_encodingaeskey" value="{pigcms{$enterprise_wx_bind['contact_way_encodingaeskey']}" type="text" />
                                            <else/>
                                            <input class="col-sm-3" value="{pigcms{$enterprise_wx_bind['contact_way_encodingaeskey']}" size="40" name="contact_way_encodingaeskey" id="contact_way_encodingaeskey" type="text"/>
                                        </if>
                                        <span class="form_tips">前往授权企业微信 <span class="other_tip">客户联系</span>><span class="other_tip">客户</span>><span class="other_tip">API</span>><span class="other_tip">接收事件服务器[编辑]</span>><span class="other_tip">EncodingAESKey[随机获取]</span></span>
                                    </div>
                                </if>
                            </div>
                        </div>
                        <div class="space"></div>
                        <if condition="$enterprise_wx_bind['qy_address_book_secret'] eq '' || $house_worker_count elt 0 || $enterprise_wx_bind['contact_way_time'] elt 0 || !$enterprise_wx_bind['PlaintextCorpId']">
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info" type="submit" >
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                </div>
                            </div>
                        </if>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .form-group>label{font-size:12px;line-height:24px;}
</style>
<script language="javascript">
    function copyContent(){
        var copyobject=document.getElementById("receiveUrl");
        copyobject.select();
        document.execCommand("Copy");
        layer.alert('已复制成功哦~');
    };
    $('.page-go').click(function(){
        window.top.GenerateRoutes();
    });
</script>
<include file="Public:footer"/>