<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
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
<div class="page-content" style="margin-top: 10px;">
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
                                <label class="col-sm-2"><label for="name">请输入数量</label></label>
                                <input class="col-sm-2" style="width: 220px" size="2" oninput="value=value.replace(/[^\d]/g,'')" name="code_num" id="code_num" type="number" min="1" max="100" autocomplete="off" value=""/>
                                会根据数据自动递增生成
                            </div>
                        </div>
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
<script type="text/javascript">
    function check_submit(){
        if($('#code_num').val() == '')
        {
            layer.alert('请填写数量');
            return false;
        }
        if($('#code_num').val()>100){
            layer.alert('数量不得大于100');
            return false;
        }
        var params = {
            'num'	: $('#code_num').val(),
        };
        $.post("{pigcms{:U('pass_add')}", params, function(data) {
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
                layer.alert(data['info']);
            }
        });
        return false;
    }

    $('select').change(function(){
        if($(this).val() == 0){
            $(this).next('label').show();
        }else{
            $(this).next('label').hide();
        }
    });

    $('input[name="diy_type"]').each(function(i){
        $(this).click(function(){
            $('.diy_property_content').each(function(){
                $(this).hide();
            });
            $('.diy_property_content').eq(i).show();
        });

    });
</script>

<include file="Public:footer"/>