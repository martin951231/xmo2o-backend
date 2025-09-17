<include file="Public:header"/>
<div class="main-content">
    <style type="text/css">
        #goods_spec{width:600px;border-color: #ccc;border-style: solid;border-width: 1px 0 0 1px;margin-bottom:10px;}
        #goods_spec tr:first-child{background:#eee;}
        #goods_spec th{height:32px;}
        #goods_spec th,#goods_spec td{border-color: #ccc;border-style: solid;border-width: 0 1px 1px 0;text-align: center;height:32px;}
        #goods_spec input{text-indent:3px;margin:5px 0;height:26px;}
        select:disabled{background: #F5F5F5;}
    </style>
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div class="tab-content" style="padding-top:10px;">
                        <div class="grid-view">
                            <form enctype="multipart/form-data" class="form-horizontal" method="post">
                                <input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
                                <p style="font-size:23px;font-weight:bold;margin-bottom: 15px">{pigcms{:L_("店铺满赠基本信息")}</p>


                                <p style="font-size:14px;font-weight:bold;">{pigcms{:L_("活动信息:")}</p>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("活动名称")}</label></label>
                                    <input class="col-sm-2" size="20" name="name"  type="text" value=""/>

                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("活动时间")}</label></label>
                                    <input class="col-sm-2" size="15" name="sdate" type="text" value="{pigcms{$info.stime|default=''|date='Y-m-d H:i:s',###}" <eq name='info.is_edit' value='0'>readonly<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq> />
                                    <span style='float:left;line-height:34px;padding:0 10px;background:#eee;'>{pigcms{:L_("至")}</span>
                                    <input class="col-sm-2" size="15" name="fdate" type="text" value="{pigcms{$info.ftime|default=''|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',<eq name='info.is_edit' value='0'>minDate:'{pigcms{$info.ftime|date='Y-m-d H:i:s',###}'</eq>})"/>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("开启状态:")}</label></label>
                                    <div style="width:80px;float:left;font-size:16px;">
                                        <label><input type="radio" value="1" name="is_open" checked="checked"/>{pigcms{:L_("开启")}</label>&nbsp;&nbsp;
                                    </div>
                                    <div style="width:80px;float:left;font-size:16px;">
                                        <label><input type="radio" value="0" name="is_open"/>{pigcms{:L_("关闭")}</label>
                                    </div>
                                </div>
                                <p style="font-size:14px;font-weight:bold;">{pigcms{:L_("优惠设置:")}</p>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("优惠条件:")}</label></label>
                                    <div style="float:left;font-size:14px;color:red;">
                                        1、一个店铺只允许一个进行中的满赠活动，且一个满赠活动里最多可创建三个等级优惠。<br/>
                                        2、满赠的门槛金额不包括配送费，餐盒费将计入满减活动的触发条件。<br/>
                                        3、每人每天最多享受2单满赠活动，超过2单则即使满足条件也不会有赠送商品。<br/>
                                        4、每个满赠活动只支持赠送一个附属菜，且附属菜库存为0时，满赠活动自动结束。<br/>
                                        5、门槛条件是（限时优惠、分类折扣、店铺折扣）优惠之后的价格。
                                    </div>
                                </div>

                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>

                                        <th>{pigcms{:L_("优惠门槛")}</th>
                                        <th>{pigcms{:L_("优惠方式")}</th>
                                        <th>{pigcms{:L_("操作")}</th>

                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr id="layer">

                                        <td class="rt-border td-class">满&nbsp;<input class="specifications-input" maxlength="20" name="goods_price[]" type="text" value="">&nbsp;元</td>
                                        <td class="rt-border td-class">
                                            <select name="give_goods_id[]">
                                                <option value="" selected="selected">{pigcms{:L_("请选择赠送商品")}</option>
                                                <volist name="list" id="vo">
                                                    <option value="{pigcms{$vo.goods_id}" >{pigcms{$vo.name} &nbsp;价格:{pigcms{$vo.price}</option>
                                                </volist>
                                            </select>
                                            <span style="margin-left: 5px">只可选择附属菜</span>
                                        </td>
                                        <td>--</td>
                                    </tr>

                                    <tr id='hierarchy'>
                                        <td>
                                            <a  class="btn btn-success add" >{pigcms{:L_("+新增一级优惠")}</a>
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    </tbody>

                                </table>
                                <div class="clearfix form-actions">
                                    <div class="col-md-offset-3 col-md-9">
                                        <button class="btn btn-info" type="submit">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            {pigcms{:L_("保存")}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script>


    var old_goods_id="{pigcms{$info.goods_id}";
    old_goods_id=parseInt(old_goods_id);
    var goods_html_url='{pigcms{$this->config['site_url']}/merchant.php?c=Diypage&a=mall_good&store_id={pigcms{$_GET.store_id}&type=mall_group';
    $(function(){

        if($("input[name='activity_goods_type']").val() == 1){
            $("#goods").hide()
            $("#goods_spec").hide()
            $("#choose_good_btns").hide()
        }

        if($("input[name='type']").val() == 1){
            $("#hierarchy").hide()

        }


        $('form.form-horizontal').submit(function(){
            $(this).find('button[type="submit"]').html('{pigcms{:L_("保存中")}...').prop('disabled',true);
        });
    });

    $("#hierarchy .add").click(function(){
        var htmls = '';
        htmls = '<tr><td class="rt-border td-class">满&nbsp;<input class="specifications-input" maxlength="20" name="goods_price[]" type="text" value="">&nbsp;元</td><td class="rt-border td-class"><select name="give_goods_id[]"><option value="" selected="selected">{pigcms{:L_("请选择赠送商品")}</option><volist name="list" id="vo"><option value="{pigcms{$vo.goods_id}" >{pigcms{$vo.name} &nbsp;价格:{pigcms{$vo.price}</option></volist></select><span style="margin-left: 5px">只可选择附属菜</span></td><td><span style="cursor: pointer;" class="test btn btn-success">删除</span></td></tr>'
        //$("#layer").after(htmls);
        $("#hierarchy").before(htmls)
    });



    $(document).on('click', '.test', function() {
        console.log("==")
        $(this).parent().parent().remove();
    });

    $("input[name='activity_goods_type']").click(function(e) {

        console.log($("input[name='activity_goods_type']:checked").val())
        if($("input[name='activity_goods_type']:checked").val() == 1){
            $("#goods").hide()
            $("#goods_spec").hide()
            $("#choose_good_btns").hide()
        }else if($("input[name='activity_goods_type']:checked").val() == 2){
            $("#goods").show()
            $("#goods_spec").show()
            $("#choose_good_btns").show()
        }

    });

    $("input[name='type']").click(function(e) {

        console.log($("input[name='type']:checked").val())
        if($("input[name='type']:checked").val() == 1){
            $("#hierarchy").hide()

        }else if($("input[name='type']:checked").val() == 2){

            $("#hierarchy").show()
        }

    });

    $('#choose_good_btns').click(function(){

        parent.layer.open({
            title:false,
            closeBtn: 0,
            type:2,
            content:goods_html_url+'&goods_id='+old_goods_id,
            area:['650px','576px'],
            shade: 0.2,
            cancel:function(){
            },
            move:false
        });
    });



    function login_box_after(a,b,c,d,goods_id,index){

        if(goods_id==old_goods_id){
            layer.close(index);
            return false;
        }
        old_goods_id=goods_id;
        $.post('{pigcms{:U("Mall/get_good_info")}',{goods_id:goods_id},function(res){

            if(res.status==1){
                var data=res.info;
                var html="";

                html+="<tr>";
                html+='<input type="hidden" name="goods_str[]" value="'+data.goods_id+'">';
                html+='<td>'+data.goods_id+'</td>';
                html+='<td>'+data.name+'</td>';
                html+='<td>'+data.price+'</td>';

                html+='<th><button type="button" class="cancel" data-id="8">移除</button></th>';
                html+='</tr>';

                $('#goods_spec').append(html);
                //$('input[name=goods_id]').val(goods_id);
                layer.close(index);
            }else{
                layer.msg(res.info);
            }
        }).error(function(){
            alert('{pigcms{:L_("网络出错，请重试")}');
        });

    }
    $(document).on('click', '.cancel', function() {
        $(this).parent().parent().remove();
    });




    //"提交表单")}
    var clock=0;
    $('form').submit(function(){
        var sdate=$('input[name=sdate]').val();
        var fdate=$('input[name=fdate]').val();
        if(!sdate || !fdate){
            tip('{pigcms{:L_("请选择满减送活动时间")}');
            return false;
        }
        stime=new Date(sdate).getTime();
        ftime=new Date(fdate).getTime();
        if(stime>ftime){
            tip('{pigcms{:L_("活动开始时间不能大于结束时间")}');
            return false;
        }

    });
    $('input[name="low_price"]').bind('input propertychange',function(){
        var price=$(this).val();
        console.log(parseFloat(price).toFixed(2));
    });
    function tip(msg){
        if(!msg){
            return false;
        }
        layer.alert(msg,{icon:2,closeBtn:0},function(index){
            $('.btn.btn-info').text('{pigcms{:L_("保存")}').removeAttr('disabled');
            layer.close(index);
        });
    }
    function login_box_close(index){
        layer.close(index);
    }
</script>

<include file="Public:footer"/>
