<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<script src="{pigcms{$static_path}js/ySelect.js"></script>
<link href="{pigcms{$static_path}css/ySelect.css" rel="stylesheet" type="text/css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
    }
</style>
<div class="page-content" style="padding-top: 10px;">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" method="post" onSubmit="return check_submit()">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">绑定出入证码：</label></label>
                                <input class="col-sm-2" readonly size="20" type="text" id="code_num" name="code_num" autocomplete="off" value="{pigcms{$info['code_num']}"/>
                            </div>
                        </div>
                        <div class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">姓名：</label></label>
                                <input class="col-sm-2" size="20" name="come_name" id="come_name" type="text" autocomplete="off" value=""/>
                            </div>
                        </div>
                        <div class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">手机号：</label></label>
                                <input class="col-sm-2" size="20" name="come_phone" id="come_phone" type="text" autocomplete="off" value=""/>
                            </div>
                        </div>
                        <div class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">出入开始时间：</label></label>
                                <input class="col-sm-2" size="20" name="start_time" id="start_time" type="text" autocomplete="off" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            </div>
                        </div>
                        <div class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">出入到期时间：</label></label>
                                <input class="col-sm-2" size="20" name="due_time" id="due_time" type="text" autocomplete="off" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            </div>
                        </div>
                        <div class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">是否给人员开门权限：</label></label>
                                <label style="padding-left:0px;padding-right:20px;margin-top: 5px"><input type="radio" class="ace" value="0" name="open_status"><span style="z-index: 1" class="lbl">是</span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="1" checked name="open_status"><span style="z-index: 1" class="lbl">否</span></label>
                            </div>
                        </div>
                        <div class="tab-pane active is_status" style="display: none">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">门禁选择：</label></label>
                                <select id='guard' class="demo1" multiple="multiple" >
                                    <volist name="guard" id="val">
                                        <option value="{pigcms{$val['id']}">{pigcms{$val['title']}</option>
                                    </volist>
                                </select>
                            </div>
                        </div>
                        <if condition="$open_door_type eq 1">
                            <div class="tab-pane active is_status" style="display: none">
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="name">人脸录入：</label></label>
                                    <input type="hidden" name="face_id" id="face_id" value="">
                                    <div type="button" class="filePickers" onclick="face_entry()">人脸录入</div>
                                </div>
                            </div>
                        </if>
                        <input type="hidden" name="type" id="types" value="1">
                    </div>
                    <div class="space"></div>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-info" type="submit">
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

<script>
    $(function() {
        $('.demo').ySelect();
        $('.demo1').ySelect(
            {
                placeholder: '请选择门禁',
                searchText: '搜索',
                showSearch: true,
                numDisplayed: 4,
                overflowText: '已选中 {n}项',
                isCheck:false
            }
        );
        $("#current").click(function(){
            alert($("#m2").ySelectedValues(","));
            alert($("#m2").ySelectedTexts(","));
        });

    });
</script>
<script type="text/javascript">
    function check_submit(){
        if( !$('#code_num').val()){
            layer.alert('出入证编号异常');
            return false;
        }
        if( !$('#come_name').val()){
            layer.alert('请填写姓名');
            return false;
        }
        if( !$('#come_phone').val()){
            layer.alert('请填写电话');
            return false;
        }
        if( !$('#start_time').val()){
            layer.alert('请选择出入开始时间');
            return false;
        }
        if( !$('#due_time').val()){
            layer.alert('请填写出入到期时间');
            return false;
        }
        if($('#start_time').val()>$('#due_time').val()){
            layer.alert('出入到期时间不能小于出入开始时间');
            return false;
        }
        var open_door_type = "{pigcms{$open_door_type}";
        if($('input:radio:checked').val() != 1){
            if(!$("#guard").ySelectedValues(",")){
                layer.alert('请选择门禁');
                return false;
            }
            //人脸开门需要上传人脸图片
            if(open_door_type == 1) {
                if (!$('#face_id').val()) {
                    layer.alert('请上传人脸');
                    return false;
                }
            }
            var guard = $("#guard").ySelectedValues(",");
            var face_id = $("#face_id").val();
        }else{
            var guard = 0;
            var face_id = 0;
        }

        var params = {
            'code_num'	: $('#code_num').val(),
            'come_name'	: $('#come_name').val(),
            'come_phone'	: $('#come_phone').val(),
            'start_time'	: $('#start_time').val(),
            'due_time'	: $('#due_time').val(),
            'open_status'	: $('input:radio:checked').val(),
            'guard'	: guard,
            'face_id'	: face_id,
            'type':$('#types').val()
        };
        console.log('params',params);
        $.post("{pigcms{:U('pass_bind_member')}", params, function(data) {
            //data = JSON.parse(data);
            console.log(data)
            if(data.status == 1){
                layer.open({
                    content: '操作成功！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.alert(data.info);
            }
        });
        return false;
    }
    $('input:radio').change(function () {
        var open_status = $('input:radio:checked').val();
        if(open_status == 1){
            $('.is_status').hide();
        }else{
            $('.is_status').show();
        }
    });

    $('select').change(function(){
        if($(this).val() == 0){
            $(this).next('label').show();
        }else{
            $(this).next('label').hide();
        }
    });
    function face_entry() {
        var url = "{pigcms{:U('faces')}";
        layer.open({
            type: 2,
            title:'添加',
            content: url,
            area: ['750px', '600px'],
            maxmin: false
        });
    }
    function ChooseAdidValuePass(img_id) {
        $('#face_id').val(img_id);
    }
</script>
<style>
    .filePickers {
        background: none repeat scroll 0 0 #00B7EE;
        border-radius: 12px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        color: #FFFFFF;
        cursor: pointer;
        display: inline-block;
        font-size: 14px;
        height: 33px;
        line-height: 33px;
        min-width: 80px;
        transition: background 0.2s;
        -moz-transition: background 0.2s;
        -webkit-transition: background 0.2s;
        -o-transition: background 0.2s;
        padding-left: 10px;
    }
</style>
<include file="Public:footer"/>
