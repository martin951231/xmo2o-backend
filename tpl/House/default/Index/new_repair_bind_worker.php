<include file="Public:header"/>
<style>
    .div_flex{

    }
    .w50{
        width: 90%;
        margin-bottom: 10px;
    }
    #myform .t1,.t2{
        margin-bottom: 6px;
    }
    #myform .t2{
        margin-left: 20px;
    }

    #myform .t3{
        display: inline-block;
        width: 200px;
    }
    #myform .t11{
        width:322px;
        display: inline-block;
    }


    #myform .but{
        width:100%;margin-top: 20px;
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
    <div  style="padding: 10px 20px;background-color:#fff;">
        <div class="widget-head" style="width: 100%;">
            <div class="color_red mg20">
                <span>1、任务工单超时设置指定时间时，由设置指定工作人员接单。</span><br>
                <span>2、工单类目绑定工作人员A，该工单类目下的工单类别默认绑定工作人员A</span>
            </div>
            <form name="myform" id="myform" action="" method="post" >
                <div class="div_flex">

                    <volist name="res['cate_list']" id="v1">
                        <div class="w50">
                            <div class="t1">
                                <span class="t11">{pigcms{$v1['cate_name']}</span>
                                <span></span>
                            </div>
                            <volist name="v1['children']" id="v2">
                                <div class="t2">
                                    <div class="t3">{pigcms{$v2['cate_name']}</div>
                                    <span >指定工作人员：</span>
                                    <span>
                                        <select name="repair_wid[{pigcms{$v2['id']}_mark_{pigcms{$v2['cate_name']}]">
                                            <if condition="$v2['value'] eq '0'">
                                                <option value="0" selected>请选择-未绑定状态</option>
                                            </if>
                                            <volist name="res['worker_list']" id="v3">
                                                <option value="{pigcms{$v1['id']}-{pigcms{$v2['id']}-{pigcms{$v3['wid']}" <if condition="$v2['value'] eq $v3['wid']">selected="selected"</if> >{pigcms{$v3['name']}- <if condition="$v3['type'] eq 1">(技工)<else/>(客服)</if></option>
                                            </volist>
                                        </select>
                                    </span>
                                </div>
                            </volist>
                        </div>
                    </volist>
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
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script>
 var cfromfun='';

  <?php if(isset($_GET['cfromfun']) && !empty($_GET['cfromfun'])){
        echo 'cfromfun="'.trim($_GET['cfromfun']).'";';
  }
  ?>

    $('.sub1').click(function () {
        var params = $('#myform').serializeArray();
        $.post('{pigcms{:U("Index/new_repair_bind_worker")}', params, function(result){
                if(result.info.status == 0){
                    layer.msg(result.info.msg,{icon: 5});return false;
                }else{
                   if(cfromfun=='v20'){
                        layer.msg('操作成功',{icon: 1,time:3000});
                    }else{
                        parent.new_bind_work_callback(result.info.msg);
                }
            }
        });
    })
</script>
<include file="Public:footer"/>