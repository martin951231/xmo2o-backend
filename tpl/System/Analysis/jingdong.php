<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Analysis/jingdong')}" class="on">交易信息</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Analysis/jingdong')}" method="get" id="myform1">
                    <input type="hidden" name="c" value="Analysis"/>
                    <input type="hidden" name="a" value="jingdong"/>


                    <span class="fl" style="margin-left: 30px;">选择商家：</span>
                    <span class="fl">全部商家</span><input type="radio"  class="input-text fl " href="{pigcms{:U('Analysis/mer_list')}" data-type="all_mer" name="mer_list" value="-1" <if condition="$_GET['mer_list'] eq -1 OR !isset($_GET['mer_list'])">checked </if>>
                    <span class="fl">部分商家</span><input type="radio"  class="input-text fl "  href="{pigcms{:U('Analysis/mer_list')}" id="part_mer" data-type="part_mer" name="mer_list"  <if condition="$_GET['mer_list'] neq -1 AND !empty($_GET['mer_list'])">checked value="{pigcms{$_GET['mer_list']}" <else />value="0"</if> >
                    <a href="javascript:void(0)" id="edit_mer" style="display:none;float:left;line-height: 16px;" data-type="part_mer">编辑</a>

                    <span class="fl" style="margin-left:60px">选择店铺：</span>
                    <span class="fl">全部店铺</span><input type="radio" href="{pigcms{:U('Analysis/store_list')}" class="input-text fl "  data-type="all_store" name="store_list" value="-1" <if condition="$_GET['store_list'] eq -1 OR !isset($_GET['store_list'])">checked </if> >
                    <span class="fl">部分店铺</span><input type="radio" href="{pigcms{:U('Analysis/store_list')}" class="input-text fl " id="part_store" data-type="part_store" name="store_list" <if condition="$_GET['store_list'] neq -1 AND !empty($_GET['store_list'])">checked value="{pigcms{$_GET['store_list']}" <else />value="0"</if>>

                    <a href="javascript:void(0)" id="edit_store" style="display:none;float:left;line-height: 16px;" data-type="part_store">编辑</a>
                    <br>
                    <span class="fl">日期筛选：</span>
                    <input type="text" class="input-text fl" name="begin_time" style="width:120px;" id="d4311"  value="<if condition="$_GET['selectTimeType'] eq 1 AND empty($_GET['begin_time'])">{pigcms{:date('Y-m-d')}</if>{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text fl" name="end_time" style="width:120px;" id="d4311" value="<if condition="$_GET['selectTimeType'] eq 1 AND empty($_GET['end_time'])">{pigcms{:date('Y-m-d')}</if>{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                    <div class="fl time-choice-wrap cf stat-time-choice">
<!--                        <span class="time-btn time-choice  <if condition="($_GET['selectTimeType'] eq 1   AND empty($_GET['begin_time']) AND empty($_GET['end_time'])) OR (!isset($_GET['selectTimeType']) AND !isset($_GET['begin_time']) AND !isset($_GET['end_time']))">time-current</if>" timetype="1" id="choice-today">-->
                        <span class="time-btn time-choice  <if condition="$_GET['selectTimeType'] eq 1">time-current</if>" timetype="1" id="choice-today">
                        今天</span>
                        <span class="time-btn time-choice bor-gray <if condition="$_GET['selectTimeType'] eq 7">time-current</if>" id="choice-week" timetype="7" >
                        近7天</span>
                        <span class="time-btn time-choice <if condition="$_GET['selectTimeType'] eq 30">time-current</if>" id="choice-month" timetype="30" >
                        近30天</span>
                        <input type="hidden" id="_selectTimeType" name="selectTimeType" value="{pigcms{$_GET['selectTimeType']}">
                    </div>

                    <input type="submit" value="查询" class="button" style="margin-top: -8px;"/>
                    <input type="reset" value="清空" class="button" id="reset" style="margin-top: -8px;"/>　　
                </form>
            </td>

        </tr>
    </table>

    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <style>
                .table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
                .statist-wrap{
                    width: 15%;
                    margin-right: 2.3%;
                    padding:0 15px;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    margin-bottom:15px;
                }
                .sta-mon{

                    font-size: 25px;
                    color: #333;
                    width: 100%;
                    text-align: center;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    line-height: 1;
                }
                .sta-num{
                    font-size: 18px;
                    color: #999;
                    width: 100%;
                    text-align: center;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .sta-total{
                    background: #1c84c6;
                    padding: 2px 7px;
                    color: #fff;
                }
                .sta-trade{
                    background: #23c6c8;
                    padding: 2px 7px;
                    color: #fff;
                }
                .sta-refund{
                    background: #ed5565;
                    padding: 2px 7px;
                    color: #fff;
                }

                .time-choice-wrap {
                    margin: 0 10px;
                    border-radius: 3px;
                    border: 1px solid #ccc
                }

                .time-choice {
                    float: left;
                    display: block;
                    padding: 0 10px;
                    line-height: 22px;
                    font-size: 12px;
                    color: #333;
                    outline: 0;
                    cursor: pointer
                }

                .time-choice.time-current {
                    color: #fff;
                    background: #1ab394
                }

                .bor-gray {
                    border-left: 1px solid #ccc;
                    border-right: 1px solid #ccc
                }
                .fl{
                    line-height: 16px;
                    margin-right:5px;
                }
                /*#layer-tip:hover{
                    color:red!important;
                }
                #layer-tip:hover .com-layer-text{
                    display:block!important;
                }*/

            </style>
            <div class="white-bg receivables-statist cf" style="padding: 15px;margin-bottom: 15px;">
                <h3 class="payment-titile clearfix">
                    <span class="title-text">关键指标 <i id="layer-tip" class="layer-tip" style="border-radius: 50%;border: 1px solid orange;padding: 1px 5px;color: orange;">?</i></span>
                    <div class="com-layer-box">
                        <i class="icon-question-sign com-layer" ></i>
                        <span class="com-layer-text dn" style="display:none">
									<p>1. 账单总额：即支付成功+退款失败的订单金额之和;</p>
									<p>2. 营业单数：即支付成功+退款失败的订单数之和;</p>
									<p>3. 商品总额：即支付成功+退款失败的商品售出金额之和;</p>
									<p>4. 运费总额：即支付成功+退款失败的京东运费之和;</p>
									<p>5. 进价总额：售出商品的京东进价之和</p>
								</span>
                    </div>
                </h3>

                <div class="statist-wrap-list">
                    <div class="statist-wrap fl" style="float:left;margin-right:50px;">
                        <p><span class="statist-wrap-title sta-total">帐单总额</span></p>
                        <p class="sta-mon">{pigcms{$total_money_sum|floatval}</p>
                        <p class="sta-num">{pigcms{$total_money_count|floatval}笔</p>
                    </div>
                    <div class="statist-wrap fl"  style="float:left;margin-right:50px;">
                        <p><span class="statist-wrap-title sta-trade">商品总额</span></p>
                        <p class="sta-mon">{pigcms{$total_goods_money_sum|floatval}</p><p class="sta-num">{pigcms{$total_money_count|floatval}笔</p>
                    </div>
                    <div class="statist-wrap fl"  style="float:left;margin-right:50px;">
                        <p><span class="statist-wrap-title sta-trade">运费总额</span></p>
                        <p class="sta-mon">{pigcms{$total_freight_money_sum|floatval}</p><p class="sta-num">{pigcms{$total_freight_money_count|floatval}笔</p>
                    </div>
                    <div class="statist-wrap fl statist-wrap-spe"  style="float:left">
                        <p><span class="statist-wrap-title sta-refund">进价总额</span></p>
                        <p class="sta-mon">{pigcms{$total_cost_money_sum|floatval}</p><p class="sta-num">{pigcms{$total_money_count|floatval}笔</p>
                    </div>
                </div>
            </div>
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>订单号</th>
                    <th>时间</th>
                    <th>商家信息</th>
                    <th>店铺信息</th>
                    <th>订单金额</th>
                    <th>运费金额</th>
                    <th>商品总计</th>
                    <th>进价总计</th>
                    <th>京东单号</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($sale_date)">
                    <volist name="sale_date" id="vo">
                        <tr>
                            <td>{pigcms{$vo.real_orderid}</td>
                            <td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
                            <td>{pigcms{$vo.mer_name}</td>
                            <td>{pigcms{$vo.store_name}</td>
                            <td>{pigcms{$vo.total|floatval}</td>
                            <td>{pigcms{$vo.jd_freight|floatval}</td>
                            <td>{pigcms{$vo.income|floatval}</td>
                            <td>{pigcms{$vo.costPay|floatval}</td>
                            <td>{pigcms{$vo.jd_order_id}</td>

                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script>
    $(function(){
        // $('#status').change(function(){
        // location.href = "{pigcms{:U('Group/order', array('type' => $type, 'sort' => $sort))}&status=" + $(this).val();
        // });
        // $('#pay_type').change(function(){
        // location.href = "{pigcms{:U('Group/order', array('type' => $type, 'sort' => $sort))}&pay_type=" + $(this).val();
        // });
        $('#reset').click(function(){
            window.location.href="{pigcms{:U('jingdong')}";
        })

        $('#layer-tip').on('mouseover',function(){
            //$('.com-layer-text').show();
            layer.tips($('.com-layer-text').html(), '#layer-tip',{
                tips: 1
            });
        });
        $('#layer-tip').on('mouseout',function(){
            layer.closeAll()
        });
        $('.time-btn').click(function(){
            $('.time-btn').removeClass('time-current')
            $(this).addClass('time-current');
            $('input[name="begin_time"]').val('')
            $('input[name="end_time"]').val('')
            $('#_selectTimeType').val($(this).attr('timetype'))
            $('#myform1').submit();
        })
        $('input:radio').click(function(){
            var obj = $(this);
            if($(obj).attr('name')=='mer_list'){
                var title= '选择商家'
            }else{
                var title= '选择店铺'
            }
            var type= $(obj).data('type')


            if(type=='part_mer' || type=='part_store'){
                if(type=='part_mer'){
                    window.top.art.dialog.data('mer_list', $(obj).val());
                    var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&mer_list='+$(obj).val()+'&jd=jd';
                    $('#edit_mer').show();
                }else{
                    window.top.art.dialog.data('store_list', $(obj).val());
                    var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&store_list='+$(obj).val()+'&mer_list='+$('#part_mer').val()+'&jd=jd';
                    $('#edit_store').show();
                }

                window.top.art.dialog.open(href,{
                    init: function(){
                        var iframe = this.iframe.contentWindow;
                        window.top.art.dialog.data('iframe_handle',iframe);
                    },
                    id: type,
                    title:title,
                    padding: 0,
                    width: 600,
                    height: 500,
                    lock: true,
                    resize: false,
                    background:'black',
                    button: null,
                    fixed: false,
                    close: null,
                    left: '50%',
                    top: '38.2%',
                    opacity:'0.4',
                    close:function(){
                        if(type=='part_mer'){
                            var mer_list = window.top.art.dialog.data('mer_list');
                            $(obj).val(mer_list)
                            $('#part_store').val(0)
                            $("input[name='store_list']:eq(0)").attr("checked",'checked');
                            $("input[name='store_list']:eq(1)").removeAttr("checked");
                            $('#edit_store').hide();
                        }else{
                            var store_list = window.top.art.dialog.data('store_list');
                            $(obj).val(store_list)


                        }
                    }
                });

            }else{
                if(type=='all_mer'){
                    $('#edit_mer').hide();
                }else{
                    $('#edit_store').hide();
                }
            }

        })

        $('#edit_mer').click(function(){
            var obj = $('#part_mer');
            var title= '选择商家'
            var type= $(obj).data('type')
            window.top.art.dialog.data('mer_list', $(obj).val());
            var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&mer_list='+$(obj).val()+'&jd=jd';

            window.top.art.dialog.open(href,{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: type,
                title:title,
                padding: 0,
                width: 600,
                height: 500,
                lock: true,
                resize: false,
                background:'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity:'0.4',
                close:function(){
                    var mer_list = window.top.art.dialog.data('mer_list');
                    $(obj).val(mer_list)
                    $('#part_store').val(0)
                    $("input[name='store_list']:eq(0)").attr("checked",'checked');
                    $("input[name='store_list']:eq(1)").removeAttr("checked");
                    $('#edit_store').hide();
                }
            });
        })


        $('#edit_store').click(function(){
            var obj = $('#part_store');
            var title= '选择店铺'
            var type= $(obj).data('type')
            window.top.art.dialog.data('store_list', $(obj).val());
            var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&store_list='+$(obj).val()+'&mer_list='+$('#part_mer').val()+'&jd=jd';

            window.top.art.dialog.open(href,{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: type,
                title:title,
                padding: 0,
                width: 600,
                height: 500,
                lock: true,
                resize: false,
                background:'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity:'0.4',
                close:function(){
                    var store_list = window.top.art.dialog.data('store_list');
                    console.log(store_list)
                    $(obj).val(store_list)
                }
            });
        })
    });

    var url = "{pigcms{$config.site_url}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>