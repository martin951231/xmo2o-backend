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
                <input  name="id" type="hidden"  value="{pigcms{$_GET['id']}"/>
                <input  name="type_id" type="hidden"  value="{pigcms{$_GET['type_id']}"/>

                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-1"><label for="num	">物料数量：</label></label>
                        <if condition="$_GET['id'] && $_GET['id'] gt 0">
                            <input class="col-sm-2" size="20" name="num" id="num" placeholder="请填写大于0的物料数量" type="text" value="{pigcms{$msg['num']}" readonly/>
                            <label><span class="red">编辑时数量不可改</span></label>
                            <else/>
                            <input class="col-sm-2" size="20" name="num" id="num" placeholder="请填写大于0的物料数量" type="text" value="{pigcms{$msg['num']}"/>
                            <label><span class="red">*必填</span></label>
                        </if>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="price">物料单价：</label></label>
                        <input class="col-sm-2" size="20" name="price" id="price" placeholder="请填写大于0的物料单价" type="text" value="{pigcms{$msg['price']}"/>
                        <label><span class="red">*必填，仅保留两位小数数</span></label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="maintenance_time">保养时间：</label></label>
                        每
                        <input class="col-sm-2" size="20" name="maintenance_time" id="maintenance_time" placeholder="请输入保养时间" type="text" value="{pigcms{$msg['maintenance_time']}"/>
                        月保养一次
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="warehousing_time">入库时间：</label></label>
                        <input type="text" name="warehousing_time" class="input-text" value="{pigcms{$msg['warehousing_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="请选择入库时间"/>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-1"><label for="supplier_name">供应商名称：</label></label>
                        <input class="col-sm-2" size="20" name="supplier_name" id="supplier_name" placeholder="请输入供应商名称" type="text" value="{pigcms{$msg['supplier_name']}"/>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-1"><label for="supplier_phone">供应商联系方式：</label></label>
                        <input class="col-sm-2" size="20" name="supplier_phone" id="supplier_phone" placeholder="请输入供应商联系方式" type="text" value="{pigcms{$msg['supplier_phone']}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="remark">备注</label></label>
                        <textarea name="remark" id="remark"  style="width:300px; height:80px">{pigcms{$msg.remark}</textarea>
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
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">

    $(document).ready(function (){


        $('#add_info').submit(function(){
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
                    layer.open({content: '添加失败'});
                }
            });
            return false;
        });

            var village_id = "{pigcms{$_GET['village_id']}"
        if(village_id){
//		$(".section_type").hide();
//		$(".group_name").hide();

            $(".village_id").hide();
        }else{
            $(".section_type").hide();
            $(".group_name").hide();
        }
    });


    $('#type').change(function(){
        if($(this).children('option:selected').val() == 0){
            $(".group_name").hide();
            $(".village_id").show();
            $(".section_type").hide();
        }else if($(this).children('option:selected').val() == 1){
            $(".group_name").show();
            $(".village_id").hide();
            $(".section_type").show();
        }else if($(this).children('option:selected').val() == 2){
            $(".group_name").show();
            $(".village_id").hide();
            $(".section_type").show();
        }
    })
</script>
<include file="Publics:footer"/>