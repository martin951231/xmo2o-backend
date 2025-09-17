<include file="Public:header"/>
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-desktop"></i>
                <a href="{pigcms{:U('Deliver/user')}">{pigcms{:L_("配送管理")}</a>
            </li>
            <li class="active">{pigcms{:L_("配送员列表")}</li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                	<button onclick="CreateDeliver()" class="btn btn-success">{pigcms{:L_("添加配送员")}</button>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">{pigcms{:L_("编号")}</th>
                                <th width="10%">{pigcms{:L_("昵称")}</th>
                                <th width="10%">{pigcms{:L_("手机号")}</th>
                                <th width="10%">{pigcms{:L_("常驻地址")}</th>
                                <th width="10%">{pigcms{:L_("所属店铺")}</th>
                                <th width="10%">{pigcms{:L_("最后修改时间")}</th>
                                <th width="5%">{pigcms{:L_("状态")}</th>
                                <th width="5%">{pigcms{:L_("配送总量")}</th>
                                <th width="30%" class="button-column">{pigcms{:L_("操作")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="is_array($user_list)">
                                <volist name="user_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.uid}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                            <td>{pigcms{$vo.phone|phone_demo_show}</td>
                            <td>{pigcms{$vo.site}</td>
                            <td>{pigcms{$vo.store_num}个      <a href="javascript::void(0)" onclick="show_storelist('{pigcms{$vo.store_names}')">查看</a></td>
                            <td><div class="tagDiv">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</div></td>
                            <td class="button-column" style="text-align: left">
                                <php>if ($vo['status'] == 1) {</php><font color="green">{pigcms{:L_("正常")}</font><php> }else { </php><font color="red">{pigcms{:L_("禁止")}</font><php> } </php>
                            </td>
                            <td>{pigcms{$vo.num}</td>
                            <td class="button-column">
                                <a style="width: 60px;" class="" href="{pigcms{:U('Deliver/count_log',array('uid'=>$vo['uid']))}">{pigcms{:L_("历史记录统计")}</a>　 | 　
                                <a style="width: 60px;" class="" href="{pigcms{:U('Deliver/log_list',array('uid'=>$vo['uid']))}">{pigcms{:L_("查看配送记录")}</a>　 | 　
                                <a style="width: 60px;" class="" href="{pigcms{:U('Deliver/user_edit',array('uid'=>$vo['uid']))}">{pigcms{:L_("编辑")}</a> 　 | 　
                                <a style="width: 60px;color: #dd5a43!important;cursor:pointer" class="delete_row_new" parameter="uid={pigcms{$vo.uid}" url="{pigcms{:U('Deliver/user_del')}">{pigcms{:L_("删除")}</a>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="9" >{pigcms{:L_("您没有添加配送员")}</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(function(){
    /*{pigcms{:L_("店铺状态")}*/
    updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");
    
    jQuery(document).on('click','#shopList a.red',function(){
        if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
    });
});

//删除
$('.delete_row_new').click(function () {
    var now_dom = $(this);
    var check_deliver_order_url = '{pigcms{:U('Deliver/check_deliver_order')}';
    $.post(check_deliver_order_url, now_dom.attr('parameter'), function (result) {
        if (result.status == 1) {
            var msg_txt = result.info;
            window.top.art.dialog({
                icon: 'question',
                title: '请确认',
                id: 'msg' + Math.random(),
                lock: true,
                fixed: true,
                opacity: '0.4',
                resize: false,
                content: msg_txt+'确定要执行此操作吗？',
                ok: function () {
                    $.post(now_dom.attr('url'), now_dom.attr('parameter'), function (result) {
                        if (result.status == 1) {
                            art.dialog({
                                content: result.info,
                                title: '成功提示',
                                cancelVal: '关闭',
                                cancel: true,
                            });
                            window.location.reload();
                        } else {
                            art.dialog({
                                content: result.info,
                                title: '错误提示',
                                cancelVal: '关闭',
                                cancel: true,
                            });
                        }
                    });
                },
                cancel: true
            });
        }else{
            art.dialog({
                content: result.info,
                title: '错误提示',
                cancelVal: '关闭',
                cancel: true,
            });
        }
    });
    return false;
});

/*function user_del(uid){
    var url="{pigcms{:U('Deliver/user_del')}";
    $.post(url,{'uid':uid},function(data){
        alert(data['error_msg'])
        if(data.status==1){
            location.href="{pigcms{:U('Deliver/user')}";
        }
    },'json')
}*/

function show_storelist(store_names) {
    art.dialog({id:"show_storelist"}).close();
    art.dialog({
        content: store_names,
        title: '店铺列表',
        cancelVal: '关闭',
        cancel: true,
        id: 'show_storelist'
    });
}
function CreateDeliver(){
    window.location.href = "{pigcms{:U('Deliver/user_add')}";
}
function updateStatus(dom1, dom2, status1, status2, attribute){
    $(dom1).each(function(){
        if($(this).attr("data-status")==status1){
            $(this).attr("checked",true);
        }else{
            $(this).attr("checked",false);
        }
        $(dom2).show();
    }).click(function(){
        var _this = $(this),
            type = 'open',
            id = $(this).attr("data-id");
        _this.attr("disabled",true);
        if(_this.attr("checked")){  //"开启")}
            type = 'open';
        }else{      //"关闭")}
            type = 'close';
        }
        $.ajax({
            url:"{pigcms{:U('Config/store_status')}",
            type:"post",
            data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
            dataType:"text",
            success:function(d){
                if(!d){     //"失败")}
                    if(type=='open'){
                        _this.attr("checked",false);
                    }else{
                        _this.attr("checked",true);
                    }
                    bootbox.alert("{pigcms{:L_("操作失败")}");
                }
                _this.attr("disabled",false);
            }
        });
    });
}
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>