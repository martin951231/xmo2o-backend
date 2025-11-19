<include file="Public:header"/>
<style>
    .a185_face_notice{
        margin:0 20px 0 0
    }

    .layui-elem-field  {
        margin-bottom: 10px;
        padding: 0;
        border-width: 1px;
        border-style: dashed;
        border-color: #e1e1e1;
    }
    .legend-my {
        width: auto;
    }
</style>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Library/set_send_price_config')}">代送基本设置</a>
            </li>
            <li class="active">基本信息设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Face_door/set_face_config')}" enctype="multipart/form-data">
                        <div class="tab-content">


                            <fieldset class="layui-elem-field"> <legend class="legend-my">kumike设备支持的配置项</legend>
                                <div class="layui-field-box">
                                    <if condition="$is_device">
                                        <div class="form-group">
                                            <label class="col-sm-2"><label for="village_name">同步社区到人脸门禁</label></label>
                                            <a class="btn btn-info sync_house_village_info" id="sync_house_village_info">同步</a>
                                            <span class="form_tips">点击同步立即将社区、单元楼栋、房间等信息同步至人脸识别门禁中(仅支持A2智能门禁)。</span>
                                        </div>
                                    </if>
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="advance_payment_days">物业费预交期限</label></label>
                                        <div>
                                            提前
                                            <input type="text" name="advance_payment_days" class="input-text" value="{pigcms{$config_info.advance_payment_days}"  placeholder="默认不通知" autocomplete="new-password" style="background-color: #FFFFFF!important;"/>
                                            天通知业主缴纳物业费
                                            <span class="form_tips">设置后，业主人脸开门后会根据物业服务日期到期前该时间进行提醒消息推送,默认15，可进行编辑修改(仅支持A3智能门禁)。</span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="layui-elem-field"> <legend class="legend-my">海康、kumike设备支持的配置项</legend>
                                <div class="layui-field-box">
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="advance_payment_days">业主申请入住录入人脸设置</label></label>
                                        <div>
                                            <label><input value="1" name="face_status" type="radio" <if condition="$config_info['face_status'] eq 1"> checked="checked" </if> />强制录入</label>
                                            &nbsp;&nbsp;&nbsp;
                                            <label><input value="2" name="face_status" type="radio" <if condition="$config_info['face_status'] eq 2"> checked="checked" </if> />可选录入</label>
                                            &nbsp;&nbsp;&nbsp;
                                            <label><input value="3" name="face_status" type="radio" <if condition="$config_info['face_status'] eq 3"> checked="checked" </if> />无需录入</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="tourist">访客开门方式设置</label></label>
                                        <label style="padding-left:0px;padding-right:20px;"><input name="open_door_type" value="1" type="radio" class="ace" <if condition="$config_info.open_door_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">人脸开门</span></label>
                                        <label style="padding-left:0px;"><input name="open_door_type" value="2" type="radio" class="ace" <if condition="$config_info.open_door_type eq 2">checked</if>/><span class="lbl" style="z-index: 1">远程开门</span></label>
                                    </div>


                                </div>
                            </fieldset>

                            <fieldset class="layui-elem-field"> <legend class="legend-my">快鲸设备支持的配置项</legend>
                                <div class="layui-field-box">
                                    <div class="form-group">
                                        <label class="col-sm-2" ><label for="advance_payment_days">门禁开门成功提示语音</label></label>
                                        <div>
                                            <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_open_succ" type="text" value="{pigcms{$config_info.a185_open_succ}" maxlength="50"/>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2" ><label for="advance_payment_days">门禁开门失败提示语音</label></label>
                                        <div>
                                            <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_open_fail" type="text" value="{pigcms{$config_info.a185_open_fail}" maxlength="50"/>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2" ><label for="advance_payment_days">APP消息推送提示语音</label></label>
                                        <div>
                                            <input class="col-xs-4 col-sm-4 col-md-2" size="20" name="a185_app_tips" type="text" value="{pigcms{$config_info.a185_app_tips}" maxlength="50"/>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="tourist">A185人脸门禁识别失败通知</label></label>
                                        <label class="a185_face_notice" title="关闭后不通知用户">
                                            <input name="a185_face_notice" value="0" type="radio" class="ace" <if condition="$config_info.a185_face_notice eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭通知</span>
                                        </label>
                                        <label class="a185_face_notice" title="微信公众号模板消息通知">
                                            <input name="a185_face_notice" value="1" type="radio" class="ace" <if condition="$config_info.a185_face_notice eq 1">checked</if>/><span class="lbl" style="z-index: 1">微信模板消息通知</span>
                                        </label>
                                        <label class="a185_face_notice" title="手机短信通知用户">
                                            <input name="a185_face_notice" value="2" type="radio" class="ace" <if condition="$config_info.a185_face_notice eq 2">checked</if>/><span class="lbl" style="z-index: 1">短信通知</span>
                                        </label>
                                        <label class="a185_face_notice" title="微信公众号模板消息+手机短信通知用户">
                                            <input name="a185_face_notice" value="3" type="radio" class="ace" <if condition="$config_info.a185_face_notice eq 3">checked</if>/><span class="lbl" style="z-index: 1">微信模板消息+短信通知</span>
                                        </label>
                                    </div>
                                </div>
                            </fieldset>


                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button type="submit" class="btn btn-info">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>

    $('#edit_form').submit(function(){

        var express_send_price = $("#express_send_price").val();
        if(express_send_price<=0){
            alert('请设置不小于0的代发价格！');
            return false;
        }

        $('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
        if (lock = 1) {
            return;
        }
        lock = 1;


        $.post("{pigcms{:U('Face_door/set_face_config')}",$('#edit_form').serialize(),function(result){
            lock = 0;
            if(result.status == 1){
                window.location.href = "{pigcms{:U('Face_door/set_face_config')}";
            }else{
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                alert(result.info);
            }
        })
        return false;
    });


    // 【A2智能门禁机】人脸识别门禁同步
    $('.sync_house_village_info').click(function () {
        var that = $(this);
        layer.open({
            title: '提示'
            ,type:1
            ,content: '<div style="width:250px;height:80px;text-align:center;margin-top:30px;">同步中,请勿关闭网页</div>'
        });

        $.post('{pigcms{:U("Face_door/sync_village_data")}',{type:'all'},function(data){
            console.log(data)
            layer.closeAll();
            if (data.code==1) {
                that.html('已同步')
            }else{
                layer.alert(data.msg)
            }
        },'json')
        return false;
    });
</script>

<include file="Public:footer"/>