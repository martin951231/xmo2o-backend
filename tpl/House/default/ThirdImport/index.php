<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('ThirdImport/index')}">点击处理已绑定业主的车位订单绑定对应房屋</a>
            </li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <form id="edit_form" method="post" class="form-horizontal" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <button id="bindPositionOrderAndRoom" type="button" class="btn btn-info">点击处理已绑定业主的车位订单绑定对应房屋</button>
                    </div>
                </form>
                </div>
            </div>
            </div>
        </div>
    </div>

<style>
    .form-group>label{font-size:12px;line-height:24px;}
</style>

<script type="text/javascript">
    var lock = 0, test;
    $('#edit_form').submit(function(){
        return false;
    });

    $('#bindPositionOrderAndRoom').click(function (){
        $('#bindPositionOrderAndRoom').prop('disabled',true).html('已绑定业主的车位订单绑定对应房屋处理中...');
        if (lock==1) {
            return;
        }
        lock = 1;
        $.post("{pigcms{:U('ThirdImport/bindPositionOrderAndRoom')}",{},function(result){
            lock = 0;
            console.log('result',result);
            if (result.status == 0 && result.info) {
                alert(result.info);
            } else {
                alert('处理完成');
            }
            $('#bindPositionOrderAndRoom').prop('disabled',false).html('点击处理已绑定业主的车位订单绑定对应房屋');
        })
        return false;
    })
</script>

<include file="Public:footer"/>