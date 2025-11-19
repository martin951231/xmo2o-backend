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
    .form-group .col-sm-1{width: 110px;}
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form  id="add_info" class="form-horizontal" method="post" action="__SELF__">
                <input  name="type_id" type="hidden"  value="{pigcms{$_GET['type_id']}"/>
                <input type="hidden" value="{pigcms{$_GET['ftype_id']}" name="ftype_id">
                <div class="tab-content">
                 <if condition="$ftype_name">
                   <div class="form-group">
                        <label class="col-sm-1" style="width: 140px;"><label for="type_name">父级物料类别名称</label></label>
                        <input class="col-sm-2"  style="width: 220px;" name="type_name"  type="text" value="{pigcms{$ftype_name}" disabled />
                    </div>
                    </if>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="type_name">物料类别名称</label></label>
                        <input class="col-sm-2"  style="width: 220px;" name="type_name" id="type_name" type="text" value="{pigcms{$type_msg['type_name']}" required />
                    </div>
                     <div class="form-group">
                        <label class="col-sm-1"><label for="type_name">排序</label></label>
                        <input class="col-sm-2" style="width: 180px;"  placeholder="值越大越靠前" name="xsort" min="0" step="1" max="999999" id="xsort" type="number" value="{pigcms{$type_msg['xsort']}"/>
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
    });

</script>
<include file="Publics:footer"/>