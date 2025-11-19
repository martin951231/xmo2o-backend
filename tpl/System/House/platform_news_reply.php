<include file="Public:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css?t=01">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<style>
    body{
        background: #FFF !important;
        height: 100% !important;
        font: normal 12PX Helvetica,STHeiti,"Microsoft YaHei",Verdana,Arial,Tahoma,sans-serif !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        font-size: 14px !important;
    }
    #nav ul{
        margin: 0 !important;
    }
    .read{
        background-color: #87b87f!important;
        border-color: #87b87f;
        color: white;
        padding: 5px;
    }
    .read2{
        background-color: white !important;
        padding: 5px;
    }
</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('House/platform_news')}" >新闻列表</a>
            <a href="{pigcms{:U('House/platform_news_reply')}" class="on">新闻评论列表</a>
        </ul>
    </div>
    <span style="font-weight: bold">是否开启评论审核</span>
    <td class="button-column" height="40px;">
        <label class="statusSwitch1" style="display:inline-block;line-height:20px;vertical-align:middle;">
            <input name="switch-field-1" class="ace ace-switch ace-switch-888" type="checkbox" <php>if ($is_check == 1) {</php>checked="checked" data-status="OPEN"<php>}else{</php>data-status="CLOSED"<php>}</php>/>
            <span class="lbl" style="margin-left:10px;"></span>
        </label>
    </td>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0" >
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                <tr>
                    <th width="20%">新闻标题</th>
                    <th width="40%">评论内容</th>
                    <th width="10%">评论人</th>
                    <th width="10%">前台是否显示</th>
                    <th width="10%">回复时间</th>
                    <th class="button-column" width="10%">操作</th>
                </tr>
                </tr>
                </thead>
                <tbody>
                <if condition="!empty($news_list)">
                    <volist name="news_list" id="vo">
                        <tr>
                            <td><div class="tagDiv">{pigcms{$vo.title}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.content}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.nickname}</div></td>
                            <td class="button-column">
                                <label class="statusSwitch" style="display:inline-block;">
                                    <input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="{pigcms{$vo['pigcms_id']}" <php>if ($vo['status'] == 1) {</php>checked="checked" data-status="OPEN"<php>}else{</php>data-status="CLOSED"<php>}</php>/>
                                    <span class="lbl"></span>
                                </label>

                            </td>
                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                            <td class="button-column">
                                    <if condition="$vo['is_read'] eq 0">
                                        <a class="read"  title="未读" href="javascript:;" onclick="read(this)" cmsid='{pigcms{$vo.pigcms_id}'>未读</a>&nbsp;|&nbsp;
                                        <else/>
                                        <span class="read2">已读</span>&nbsp;|&nbsp;
                                    </if>

                                <a href="javascript:void(0);" class="delete_row" parameter="pigcms_id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('House/platform_news_reply_del')}">删除</a>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="6">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>



<script>
    $('.statusSwitch1 .ace-switch-888').click(function() {
        var _this = $(this), type = 'open';
        _this.attr("disabled", true);
        if (_this.attr("checked")) {	//开启
            type = 'open';
        } else {		//关闭
            type = 'close';
        }
        $.ajax({
            url: "{pigcms{:U('House/platform_news_reply_check')}",
            type: "post",
            data: {"type": type},
            dataType: "text",
            success: function (d) {
                if (d != '1') {		//失败
                    if (type == 'open') {
                        _this.attr("checked", false);
                    } else {
                        _this.attr("checked", true);
                    }
                    layer.msg('操作失败',{icon: 2});
                }else{
                    layer.msg('操作成功',{icon: 1});
                }
                _this.attr("disabled", false);
            }
        });
    })
</script>
<script>

    function read(obj){
        if(confirm('您确定要标记为已读？')){
            var cmsid = $(obj).attr('cmsid');
            $.post("{pigcms{:U('House/platform_news_reply_read')}",{cmsid:cmsid},function(result){
                if(result.status == 1){
                    window.location.reload();
                }else{
                    alert(result.msg);
                }
            })
        }
    }

    $(function(){
        updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED");
    });
    function updateStatus(dom1, dom2, status1, status2){
        $(dom1).each(function(){
            if($(this).attr("data-status")==status1){
                $(this).attr("checked",true);
            }else{
                $(this).attr("checked",false);
            }
            $(dom2).show();
        }).click(function(){
            var _this = $(this), type = 'open', id = $(this).attr("data-id");
            _this.attr("disabled",true);
            if(_this.attr("checked")){	//开启
                type = 'open';
            }else{		//关闭
                type = 'close';
            }
            $.ajax({
                url:"{pigcms{:U('House/platform_news_reply_status')}",
                type:"post",
                data:{"type":type,"id":id,"status1":status1,"status2":status2},
                dataType:"text",
                success:function(d){
                    if(d != '1'){		//失败
                        if(type=='open'){
                            _this.attr("checked",false);
                        }else{
                            _this.attr("checked",true);
                        }
                        layer.msg('操作失败',{icon: 2});
                    }
                    _this.attr("disabled",false);
                }
            });
        });
    }


    function CreateCategory(){
        window.location.href = "{pigcms{:U('House/platform_news_add')}";
    }
    //发送微信通知
    function send_weixin(obj){
        var news_id = $(obj).attr('news_id');
        alert(news_id)
    }
</script>
<include file="Public:footer"/>