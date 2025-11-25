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
                <form  class="form-horizontal" method="post">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">序号：</label></label>
                                <input class="col-sm-2" disabled size="20" name="code_num"  type="text" autocomplete="off" value="{pigcms{$info['code_num']}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2"><label for="status">状态</label></label>
                            <label style="padding-left:0px;padding-right:20px;margin-top: 5px"><input type="radio" <if condition='$info["status"] eq 0'>checked="checked"</if> checked="" class="ace" value="0" name="status"><span style="z-index: 1" class="lbl">正常</span></label>
                            <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$info["status"] eq 1'>checked="checked"</if>  class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">禁止</span></label>
                            <label style="padding-left:0px;"><input type="radio" <if condition='$info["status"] eq 2'>checked="checked"</if>  class="ace" value="2" name="status"><span style="z-index: 1" class="lbl">过期</span></label>
                        </div>
                    </div>
                    <div class="space"></div>
                    <input  name="pass_id" id="pass_id" type="hidden"  value="{pigcms{$info['pass_id']}"/>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-info" type="button" onclick="check_submits()">
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
    function check_submits(){
        var params = {
            'pass_id':$('#pass_id').val(),
            'status':$('input:radio:checked').val(),
        };
        $.post("{pigcms{:U('pass_edit')}", params, function(data) {
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
</script>

<include file="Public:footer"/>