<include file="Public:header"/>
<!---->
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
            <a href="{pigcms{:U('SendCoupon/index')}" class="on">{pigcms{:L_('发券功能')}</a>
            <a href="{pigcms{:U('SendCoupon/addActivity')}">{pigcms{:L_('新建活动')}</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('SendCoupon/index')}" method="get" id="myform1">
                    <input type="hidden" name="c" value="SendCoupon"/>
                    <input type="hidden" name="a" value="index"/>

                    <span class="fl">活动名称：</span><input type="text" name="activity_name" id="activity_name" class="input-text fl" value="{pigcms{$_GET['activity_name']}"/>

                    <span class="fl">活动日期：</span>
                    <input type="text" class="input-text fl" name="start_date" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_date}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <span style="float: left">&nbsp;至&nbsp;&nbsp;&nbsp;</span>
                    <input type="text" class="input-text fl" name="end_date" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_date}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                    <span class="fl">发券条件：</span>
                    <select name="send_type" class="fl">
                        <option value="0">全部</option>
                        <option value="1" <if condition="$_GET['send_type'] eq 1">selected</if>>新客条件</option>
                        <option value="2" <if condition="$_GET['send_type'] eq 2">selected</if>>老客条件</option>
                    </select>

                    <span class="fl">展示页面：</span>
                    <select name="show_page" class="fl">
                        <option value="0">全部</option>
                        <option value="1" <if condition="$_GET['show_page'] eq 1">selected</if>>平台首页</option>
                        <option value="2" <if condition="$_GET['show_page'] eq 2">selected</if>>外卖首页</option>
                        <option value="4" <if condition="$_GET['show_page'] eq 4">selected</if>>商城首页</option>
                        <option value="8" <if condition="$_GET['show_page'] eq 8">selected</if>>预约首页</option>
                    </select>

                    <input type="submit" value="筛选" class="button" style="margin-top: -8px;"/>　
                    <input type="button" onclick="reset_search()" value="重置筛选" class="button" style="margin-top: -8px;"/>　
                </form>
            </td>
        </tr>
    </table>
    <p style="padding: 5px;line-height: 1.3rem;color: red;">
        <span style="display:inline-block;width: 1rem;height: 1rem;border-radius: 1rem;border: 1px solid red;text-align: center">?</span>
        温馨提示：当展示页面满足多个活动时，以最新配置活动为准。
    </p>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th style="width: 5%;">{pigcms{:L_('ID')}</th>
                    <th style="width: 15%;">{pigcms{:L_('活动名称')}</th>
                    <th style="width: 10%;">{pigcms{:L_('活动时间')}</th>
                    <th style="width: 10%;">{pigcms{:L_('活动状态')}</th>
                    <th style="width: 10%;">{pigcms{:L_('发券条件')}</th>
                    <th style="width: 15%;">{pigcms{:L_('展示页面')}</th>
                    <th style="width: 10%;">{pigcms{:L_('领取券数')}</th>
                    <th style="width: 10%;">{pigcms{:L_('使用券数')}</th>
                    <th style="width: 10%;">{pigcms{:L_('操作')}</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($lists)">
                    <volist name="lists" id="o">
                        <tr>
                            <td>{pigcms{$o.id}</td>
                            <td>{pigcms{$o.activity_name}</td>
                            <td>{pigcms{$o.start_date} 至 {pigcms{$o.end_date}</td>
                            <td>
                                <if condition="$o['progress_status'] eq 1">
                                    <span style="color: green;">{pigcms{$o.status_txt}</span>
                                <else/>
                                    <span style="color: red;">{pigcms{$o.status_txt}</span>
                                </if>
                                
                            </td>
                            <td><php>echo $o['send_type'] == 1?'新客条件':'老客条件';</php></td>
                            <td>
                                <php>
                                    $pages=[];
                                    if($o['show_page']&1){
                                        $pages[]='平台首页';
                                    }
                                    if($o['show_page']&2){
                                        $pages[]='外卖首页';
                                    }
                                    if($o['show_page']&4){
                                        $pages[]='商城首页';
                                    }
                                    if($o['show_page']&8){
                                        $pages[]='预约首页';
                                    }
                                    echo implode('、',$pages);
                                </php>
                            </td>
                            <td>{pigcms{$o.had_pull}</td>
                            <td>{pigcms{$o.use_num}</td>
                            <td>
                                <if condition="$o['status'] eq 0">
                                    <a href="javascript:void(0)" onclick="setDel('{pigcms{$o.id}')">删除</a>
                                <else/>
                                    <a href="{pigcms{:U('SendCoupon/editActivity',array('id'=>$o['id']))}">编辑</a>
                                    |
                                    <a href="javascript:void(0)" onclick="setLose('{pigcms{$o.id}')">设为失效</a>
                                </if>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
                <else/>
                    <tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    $(function(){
        $('.time-btn').click(function(){
            $('.time-btn').removeClass('time-current')
            $(this).addClass('time-current');
            $('input[name="begin_time"]').val('')
            $('input[name="end_time"]').val('')
            $('#_selectTimeType').val($(this).attr('timetype'))
            $('#myform1').submit();
        })
    });

    function reset_search(){
        $('#myform1').find('input[type=text],select').each(function(){
            $(this).val('')
        })
        $('#myform1').submit();
    }

    function addSendCouponActivity(){
        window.top.artiframe('{pigcms{:U('capture_store_goods')}','抓取店铺最新商品数据',800,600,true,false,false,addbtn,'capture_store_goods',true);
    }
    function setLose(id) {
        if(confirm('{pigcms{:L_("确定把该活动设为失效？")}')){
            $.post("{pigcms{:U('SendCoupon/setLose')}", {id: id}, function (rs) {
                if(rs.error_no == 1){
                    alert(rs.error_msg);
                    return false;
                }else{
                    location.reload();
                }
            })
        }
    }

    function setDel(id) {
        if(confirm('{pigcms{:L_("确定删除该活动？")}')){
            $.post("{pigcms{:U('SendCoupon/setDel')}", {id: id}, function (rs) {
                if(rs.error_no == 1){
                    alert(rs.error_msg);
                    return false;
                }else{
                    location.reload();
                }
            })
        }
    }

    var url = "{pigcms{$config.site_url}"
</script>
<include file="Public:footer"/>