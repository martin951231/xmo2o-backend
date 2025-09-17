<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}layer/css/layui.css"  media="all">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
        margin-top: 30px;
    }
   .tab-content{ border:none;}
</style>
<div class="page-content">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" id="haokuaichong_region_form" method="post" onSubmit="return check_submit()">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="area_name">充电区名称</label></label>
                                <input class="col-sm-5"  name="area_name" id="area_name" type="text" value="{pigcms{$hkcregion['area_name']}" required/>
                                <span style="margin-left: 20px;">必填项</span>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="common_id">费率套餐</label></label>
                                 <select id="common_id" name="common_id" class="col-sm-3" required >
                                     <volist name="hkcrate" id="vo">
                                        <option value="{pigcms{$vo['rate_id']}"  <if condition="$vo['rate_id'] eq $hkcregion['common_id'] "> selected="selected" </if> >{pigcms{$vo['rate_name']}（{pigcms{$vo['rate_type']}）</option>
                                    </volist>
                                    </select>
                                   
                            </div>
                           <div class="form-group" style="display:none;">
                                <label class="col-sm-2"><label for="area_type">充电区类型</label></label>
                                 <select id="choose_area_type" name="area_type" class="col-sm-3">
                                    <option value="0">充电桩</option>
                                    <option value="1">充电柜</option>
                                    </select>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="area_marks">备注</label></label>
                                <input class="col-sm-8"  name="area_marks" id="area_marks" type="text" value="{pigcms{$hkcregion['area_marks']}" />
                            </div>

                        </div>
                    </div>
                    <div class="space"></div>
                    <input  name="project_id" id="project_id" type="hidden"  value="{pigcms{$project_id}"/>
                    <input  name="idd" id="idd" type="hidden"  value="{pigcms{$idd}"/>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-1 col-md-9">
                            <a class="btn btn-info"  onclick="check_submit()">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </a>
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
        var params = $('#haokuaichong_region_form').serialize();
        $.post("{pigcms{:U('HkcPile/add_project_region')}", params, function(data) {
            if(data.err_code == 0){
                layer.open({
                    content: data.err_msg,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.open({
                    content:  data.err_msg,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        layer.closeAll();
                    }
                })
            }
        },'JSON');
        return false;
    }

</script>

<include file="Public:footer"/>