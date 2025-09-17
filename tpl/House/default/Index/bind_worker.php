<include file="Public:header"/>
<style>
    .div_flex{
        display: flex
    }
    .w50{
        width: 50%;
    }
    #myform .t1{
        margin-bottom: 20px;
    }
    #myform .but{
      width:100%;margin-top: 180px;
    }
    #myform .but button{
        display:block;margin:0 auto
    }
    .color_red{
        color: red;
    }
    .mg20{
        margin-bottom: 20px;
    }
</style>
<div class="main-content">
    <div class="page-content page-home" style="max-height: 450px !important;padding: 10px 20px;">
        <div class="widget-head" style="width: 100% !important;">
            <div class="color_red mg20">任务工单超时设置指定时间时，由设置指定工作人员接单。</div>
            <form name="myform" id="myform" action="" method="post" >
                <div class="div_flex">
                    <div class="w50">
                        <div class="widget-title t1">在线报修</div>
                        <span><i class="color_red">*</i> 指定工作人员：</span>
                        <span>
                        <select name="repair_wid">
                            <if condition="$village_info['repair_wid'] eq '0'">
                                <option value="0" selected>请选择</option>
                            </if>
                            <volist name="workers" id="worker">
                                <option value="{pigcms{$worker['wid']}" <if condition="$village_info['repair_wid'] eq $worker['wid']">selected="selected"</if> >{pigcms{$worker['name']}- <if condition="$worker['type'] eq 1">(技工)<else/>(客服)</if></option>
                            </volist>
                        </select>
                    </span>
                    </div>
                    <div class="w50">
                        <div class="widget-title t1">投诉建议</div>
                        <span><i class="color_red">*</i> 指定工作人员：</span>
                        <span>
                        <select name="advise_wid">
                            <if condition="$village_info['advise_wid'] eq '0'">
                                <option value="0" selected>请选择</option>
                            </if>
                            <volist name="workers" id="worker">
                                <option value="{pigcms{$worker['wid']}" <if condition="$village_info['advise_wid'] eq $worker['wid']">selected="selected"</if> >{pigcms{$worker['name']}- <if condition="$worker['type'] eq 1">(技工)<else/>(客服)</if></option>
                            </volist>
                        </select>
                    </span>
                    </div>
                </div>
                <div class="but">
                    <button class="btn btn-info sub1" type="button">
                        <i class="ace-icon fa fa-check bigger-110"></i>
                        确认
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $('.sub1').click(function () {
        var repair_wid=$("select[name='repair_wid']").val();
        var advise_wid=$("select[name='advise_wid']").val();
        if(repair_wid <= 0){
            layer.msg('在线报修=>请选择指定工作人员',{icon: 2});return false;
        }
        if(advise_wid <= 0){
            layer.msg('投诉建议=>请选择指定工作人员',{icon: 2});return false;
        }
        parent.bind_work_callback(repair_wid,advise_wid);
    })
</script>
<include file="Public:footer"/>