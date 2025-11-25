<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    body fieldset {
        border: none;
    }
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form  id="add_info" class="form-horizontal" method="post" action="__SELF__">
                <input  name="template_id" type="hidden"  value="{pigcms{$_GET['template_id']}"/>
                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-2"><label for="template_name">短信模板：</label></label>
                        <select name="template_id" id="template_id" >
                            <option value="" style="display: none;">请选择短信模板</option>
                            <volist name="template_select" id="item">
                                <option value="{pigcms{$item['template_id']}" >{pigcms{$item['template_name']}</option>
                            </volist>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2"><label for="send_type">发送类型：</label></label>
                        <label><input style="margin:0;" name="send_type" type="radio" value="1" checked/>&nbsp;&nbsp;指定身份发送</label>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="send_type" type="radio" value="2" />&nbsp;&nbsp;指定号码发送</label>
                    </div>
                    <div class="form-group send_type_1">
                        <label class="col-sm-2"><label for="floor_id">发送对象：</label></label>
                        <label> <fieldset id="choose_village" single_id="0" floor_id="0"></fieldset></label>
<!--                        <select name="floor_id" id="floor_id" >-->
<!--                            <option value="" style="display: none;">请选择发送对象</option>-->
<!--                            <option value="">全部单元</option>-->
<!---->
<!--                            <fieldset id="choose_village" single_id="0" floor_id="0"></fieldset>-->
<!---->
<!---->
<!--                            <volist name="floor_list" id="items">-->
<!--                                <option value="{pigcms{$items['floor_id']}" >{pigcms{$items['floor_layer']} - {pigcms{$items['floor_name']}</option>-->
<!--                            </volist>-->
<!--                        </select>-->
                    </div>
                    <div class="form-group send_type_2" style="display: none;">
                        <label class="col-sm-2"><label for="template_content">手机号：</label></label>
                        <label style="width: 310px;">
                            <textarea style="width:303px;" id="send_phone" name="send_phone" placeholder="请输入手机号码，多号码换行输入"></textarea>
                        </label>
                    </div>
                    <div class="form-group send_type_1">
                        <label class="col-sm-2"><label for="send_user">住户身份(必填，可多选)：</label></label>
                        <label><input style="margin:0;" name="send_user[]" type="checkbox" value="0"/>&nbsp;&nbsp;业主</label>
                        &nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="send_user[]" type="checkbox" value="1"/>&nbsp;&nbsp;配偶</label>
                        &nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="send_user[]" type="checkbox" value="2"/>&nbsp;&nbsp;父母</label>
                        &nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="send_user[]" type="checkbox" value="3"/>&nbsp;&nbsp;子女</label>
                        &nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="send_user[]" type="checkbox" value="4"/>&nbsp;&nbsp;亲朋好友</label>
                        &nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="send_user[]" type="checkbox" value="5"/>&nbsp;&nbsp;租客</label>
                    </div>
                </div>
                <input type="hidden" id="sms_param_length" value="{pigcms{$sms_param_length}">
                <div class="space"></div>
                <div class="clearfix form-actions">
                    <div style="width: 100%;">
                        <button class="btn btn-info" type="submit">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                            提交
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var choose_single = "{pigcms{:U('Service/ajax_get_single')}";
    var choose_floor = "{pigcms{:U('Service/ajax_get_floor')}";
    var choose_vacancy = "{pigcms{:U('Service/ajax_get_vacancy')}";
    var is_door = 0;
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose.js?t=008"></script>

<script type="text/javascript">


    var sms_param_length=[];

    function word_num() {
        var val = $('#template_content').val();
        var font_num = 0; // 字数数量
        if (val && sms_param_length) {
            for (var i in sms_param_length) {
                if (sms_param_length[i] && sms_param_length[i]['key']) {
                    var n = (val.split(sms_param_length[i]['key'])).length-1;
                    font_num += n*2;// 1个汉字2个字符
                    var re = new RegExp(sms_param_length[i]['key'],"g"); //定义正则表达式
                    val = val.replace(re,"");
                }
            }
        }
        var word_info = val.replace(/[^\x00-\xff]/g,'01');
        var word_length = word_info.length;
        font_num += word_length;
        $('.word_length').html(font_num);
    }

    $(document).ready(function (){


        $('input[name=send_type]').click(function(){
            var val = $(this).val();
            if (1==val) {
                $('.send_type_1').show();
                $('.send_type_2').hide();
            } else {
                $('.send_type_1').hide();
                $('.send_type_2').show();
            }
        });


        var is_empty = "{pigcms{$is_empty}";
        if (is_empty) {
            layer.open({
                content: '请先添加短信模板！',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
                ,yes:function(index){
                    window.parent.close_all_page();
                }
            })
        }
        var is_pay_sms = "{pigcms{$is_pay_sms}";
        if (is_pay_sms) {
            layer.open({
                content: '短信数量不足，请购买短信数量！',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
                ,yes:function(index){
                    window.parent.close_all_page();
                }
            })
        }

        $('#add_info').submit(function(){

            if($('#template_id').val()==''){
                alert('请选择短信模板！');
                return false;
            }
            if($('#send_type').val()==''){
                alert('请选择发送类型！');
                return false;
            }
            if ($('#send_type').val()==2 && $('#send_phone').val()=='') {
                alert('请填写发送手机号！');
                return false;
            }
            if ($('#send_type').val()==1 && $('#floor_id').val()=='') {
                alert('请填写发送手机号！');
                return false;
            }

            $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
                console.log('result', result);
                if (result && result.status==1) {
                    layer.open({
                        content: '操作成功！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.parent.close_all_page(result);
                        }
                    })
                } else if (result.info) {
                    layer.open({
                        content: result.info,
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['知道了']
                    })
                } else {
                    layer.open({content: '操作失败'});
                }
            });
            return false;
        });
    });

</script>
<include file="Publics:footer"/>