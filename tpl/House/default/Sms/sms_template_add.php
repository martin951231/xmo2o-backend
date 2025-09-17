<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form  id="add_info" class="form-horizontal" method="post" action="__SELF__">
                <input  name="template_id" type="hidden"  value="{pigcms{$_GET['template_id']}"/>
                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-2"><label for="template_name">模板标题：</label></label>
                        <input size="40" value="{pigcms{$sms_template['template_name']}" name="template_name" id="template_name" placeholder="请输入短信模板标题" type="text" />
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2"><label for="template_content">短信内容：</label></label>
                        <label style="width: 310px;float: left;">
                            <textarea style="width:303px;" id="template_content" name="template_content" placeholder="请输入短信内容">{pigcms{$sms_template['template_content']}</textarea>
                        </label>
                        <div style="float: right;">
                            <div>1条短信长度70个字符（包括标点符号）内。</div><div>超过70个字符算2条短信数量。</div>
                            <div style="color: #777;font-size: 12px;">注：1个汉字（包括中文标点符号）2个字符，1个英文1个字符</div>
                            <div>(当前已输入<span class="word_length">0</span>字符)</div>
                        </div>
                    </div>
                    <div class="form-group" style="clear:both;">
                        <label class="col-sm-2">&nbsp;</label>
                        <label>
                            变量填写规则(可对应复制到短信内容)：
                            <p style="font-size: 16px;color: #333;">{pigcms{$sms_param_txt}</p>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2"><label for="status">状态：</label></label>
                        <label><input style="margin:0;" name="status" type="radio" value="0" <if condition="$sms_template.status eq 0">checked</if> />&nbsp;&nbsp;正常</label>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input style="margin:0;" name="status" type="radio" value="1" <if condition="$sms_template.status eq 1">checked</if> />&nbsp;&nbsp;禁止</label>
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

        $.post("{pigcms{:U('Sms/sms_param_length')}",{},function(result){
            if (result) {
                sms_param_length = result;
                word_num();
            }
        },'json');
        // 输入短信内容信息监听 template_content
        $('#template_content').change(function () {
            word_num();
        });


        $('#add_info').submit(function(){
            if($('#template_name').val()==''){
                alert('短信模板标题不能为空！');
                return false;
            }
            if($('#template_content').val()==''){
                alert('短信内容不能为空！');
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
                            window.parent.close_all_page();
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