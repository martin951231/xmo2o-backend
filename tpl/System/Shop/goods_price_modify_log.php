<include file="Public:header"/>
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
        line-height: 22px;
        margin-right:5px;
        margin-bottom: 20px;
    }
</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Shop/goods_price_modify_log')}" class="on">价格修改记录</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Shop/goods_price_modify_log')}" method="get" id="myform1">
                    <input type="hidden" name="c" value="Shop"/>
                    <input type="hidden" name="a" value="goods_price_modify_log"/>

                    <span class="fl">商品名称：</span><input type="text" name="goods_name" id="goods_name" class="input-text fl" value="{pigcms{$_GET['goods_name']}"/>
                    <span class="fl">操作店铺：</span><input type="text" name="store_name" id="store_name" class="input-text fl" value="{pigcms{$_GET['store_name']}"/>
                    <span class="fl">操作商家：</span><input type="text" name="merchant_name" id="merchant_name" class="input-text fl" value="{pigcms{$_GET['merchant_name']}"/>

                    <span class="fl">操作时间：</span>
                    <input type="text" class="input-text fl" name="begin_time" style="width:120px;" id="d4311"  value="<if condition="$_GET['selectTimeType'] eq 1 AND empty($_GET['begin_time'])">{pigcms{:date('Y-m-d')}</if>{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text fl" name="end_time" style="width:120px;" id="d4311" value="<if condition="$_GET['selectTimeType'] eq 1 AND empty($_GET['end_time'])">{pigcms{:date('Y-m-d')}</if>{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                    <div class="fl time-choice-wrap cf stat-time-choice">
                        <span class="time-btn time-choice  <if condition="($_GET['selectTimeType'] eq 1   AND empty($_GET['begin_time']) AND empty($_GET['end_time'])) OR (!isset($_GET['selectTimeType']) AND !isset($_GET['begin_time']) AND !isset($_GET['end_time']))">time-current</if>" timetype="1" id="choice-today">
                        今天</span>
                        <span class="time-btn time-choice bor-gray <if condition="$_GET['selectTimeType'] eq 7">time-current</if>" id="choice-week" timetype="7" >
                        近7天</span>
                        <span class="time-btn time-choice <if condition="$_GET['selectTimeType'] eq 30">time-current</if>" id="choice-month" timetype="30" >
                        近30天</span>
                        <input type="hidden" id="_selectTimeType" name="selectTimeType" value="{pigcms{$_GET['selectTimeType']}">
                    </div>


                    <input type="submit" value="筛选" class="button" style="margin-top: -8px;"/>　　
                    <input type="reset" value="重置" class="button" id="reset" style="margin-top: -8px;"/>
                </form>
            </td>

        </tr>
    </table>

    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>操作时间</th>
                    <th>操作商家</th>
                    <th>操作店铺</th>
                    <th>商品名称</th>
                    <th>规格</th>
                    <th>操作前价格</th>
                    <th>操作后价格</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($logs)">
                    <volist name="logs" id="vo">
                        <tr>
                            <td>{pigcms{$vo.time|date='Y-m-d H:i:s',###}</td>
                            <td>{pigcms{$vo.merchant_name}<br/>{pigcms{$vo.phone}</td>
                            <td>{pigcms{$vo.store_name}</td>
                            <td><php>echo $vo['real_goods_name']?:$vo['goods_name'];</php></td>
                            <td>
                                <if condition="$vo['spec_value_array']">
                                    <span onclick="window.top.artiframe('{pigcms{:U('Shop/show_spec',array('id'=>$vo['id'],'frame_show'=>true))}','规格',900,'80%',true,false,false,false,'detail',true);">点击查看</span>
                                <else/>
                                    无
                                </if>
                            </td>
                            <td>{pigcms{$vo.old_price|getFormatNumber}</td>
                            <td>{pigcms{$vo.new_price|getFormatNumber}</td>
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
<script>
    $(function(){
        $('#reset').click(function(){
            window.location.href="{pigcms{:U('Shop/goods_price_modify_log')}";
        })

        $('.time-btn').click(function(){
            $('.time-btn').removeClass('time-current')
            $(this).addClass('time-current');
            $('input[name="begin_time"]').val('')
            $('input[name="end_time"]').val('')
            $('#_selectTimeType').val($(this).attr('timetype'))
            $('#myform1').submit();
        })

        function show_spec(id){

        }
    });

    var url = "{pigcms{$config.site_url}"
</script>
<include file="Public:footer"/>