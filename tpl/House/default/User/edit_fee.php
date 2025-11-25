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
<div class="page-content">
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
                                <label class="col-sm-2"><label for="name">{pigcms{$type_name}</label></label>
                                <input class="col-sm-2" size="20" name="price" id="price" type="text" value="{pigcms{$price}" readonly/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="type">修改类型：</label></label>
                                <select class="col-sm-2" name="type" id="type" style="height: 34px">
                                    <option value="1">增加</option>
                                    <option value="2">减少</option>
                                </select>
                                &nbsp;&nbsp;&nbsp;
                                <input class="col-sm-2" name="edit_price" id="edit_price" type="text" placeholder="请输入增加/减少金额"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="name">修改原因</label></label>
                                <input class="col-sm-4" size="20" name="reason" id="reason" type="text" placeholder="请输入原因"/>
                            </div>

                        </div>
                    </div>
                    <div class="space"></div>
                    <input  name="pigcms_id" id="pigcms_id" type="hidden"  value="{pigcms{$pigcms_id}"/>
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
        var params = {
            'pigcms_id'	: $('#pigcms_id').val(),
            'reason'	: $('#reason').val(),
            'kind':$('#type').val(),
            'edit_price':$('#edit_price').val(),
            'type':{pigcms{$type}
        };
        var price = parseInt($('#price').val());
        var edit_price = parseInt($('#edit_price').val());
        if($('#type').val() == 2 && edit_price > price){
            alert('减少金额不能大于现存金额');
            return false;
        }
        $.post("{pigcms{:U('User/edit_fee')}", params, function(data) {
            layer.open({
                content: '操作成功！',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
                ,yes:function(index){
                    parent.location.href = location.protocol + '//' + location.host + '/'+"shequ.php?g=House&c=User&a=edit&pigcms_id={pigcms{$_GET['pigcms_id']}&usernum={pigcms{$_GET['usernum']}&page=1";
                }
            })
        });
        return false;
    }

</script>

<include file="Public:footer"/>