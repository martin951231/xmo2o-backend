<include file="Public:header"/>
<style>
    .btn {
        padding: 2px 6px;
        border-radius: 5px;
        outline: none;
    }
    .btn-success, .btn-success:focus {
        background-color: #87b87f!important;
        border-color: #87b87f;
    }
    .btn {
        display: inline-block;
        color: #FFF!important;
        text-shadow: 0 -1px 0 rgba(0,0,0,.25)!important;
        background-image: none!important;
        border: 0px solid #FFF;
        border-radius: 0;
        box-shadow: none!important;
        -webkit-transition: all ease .15s;
        -o-transition: all ease .15s;
        transition: all ease .15s;
        cursor: pointer;
        vertical-align: middle;
        margin: 0;
        position: relative;
    }

</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('House/platform_news')}" class="on">新闻列表</a>
            <a href="{pigcms{:U('House/platform_news_reply')}" >新闻评论列表</a>
        </ul>
    </div>
    <div class="page_tips" style="margin-bottom: 10px;">该功能可针对平台中所有小区的用户发布公告通知，发布后，业主可在新闻列表页固定分类“平台通知”中查看通知详情。 </div>


<!--    onclick="window.top.artiframe('{pigcms{:U('House/platform_news_add')}','新增新闻','80%',600,true,false,false,addbtn,'add',true);"-->
<!--    onclick="CreateCategory()"-->
    <button class="btn btn-success"  onclick="window.top.artiframe('{pigcms{:U('House/platform_news_add')}','新增新闻','90%','95%',true,false,false,addbtn,'add',true);" style="margin-bottom: 15px">新增新闻</button>

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
                    <th >新闻标题</th>
                    <th >发布时间</th>
                    <th >热门</th>
                    <th >所属分类</th>
                    <th >已微信通知</th>
                    <th >操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="!empty($news_list)">
                    <volist name="news_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.title}</td>
                            <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
                            <td><div class="shopNameDiv"><if condition="$vo.is_hot eq '1' ">是<else />否</if></div></td>
                            <td><div class="tagDiv">平台通知</div></td>
                            <td><div class="shopNameDiv"><if condition="$vo.is_notice eq '1' ">是<else />否</if></div></td>

                            <td class="button-column">

                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="window.top.artiframe('{pigcms{:U('House/platform_news_edit',array('news_id'=>$vo['news_id']))}','编辑新闻','90%','95%',true,false,false,editbtn,'add',true);"  href="javascript:;">编辑</a>&nbsp;|
                                <if condition="$vo['is_notice'] eq 0">
                                    <if condition=" $vo['status'] eq 0 ">
                                        <a style="width: 100px;" class="label label-sm label-info" href="javascript:;">此新闻已被关闭</a>
                                        <else/>
                                        <a style="width: 100px;" class="label label-sm label-info handle_btn" href="javascript:void(0);" news_id="{pigcms{$vo['news_id']}" onclick="send_weixin(this)">微信群发用户</a>&nbsp;|
                                    </if>
                                </if>&nbsp;
                                <a href="javascript:void(0);" class="delete_row" parameter="news_id={pigcms{$vo.news_id}" url="{pigcms{:U('House/platform_news_del')}">删除</a>
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
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<script>
    function CreateCategory(){
        window.location.href = "{pigcms{:U('House/platform_news_add')}";
    }
    //发送微信通知
    function send_weixin(obj){
        var news_id = $(obj).attr('news_id');
        if(confirm('您确定要发送？')){
            var loading = layer.msg('创建群发计划中......', {
                icon: 16,
                shade: [0.1, '#000'],
                time: false  // 取消自动关闭
            });
            $.post("{pigcms{:U('House/platform_news_send')}",{'news_id':news_id},function(result){
                layer.msg(result.info);
                if(result.status == 1){
                    setTimeout(function () {window.location.reload();}, 1000);
                }
            },'json');
        }
    }
</script>
<include file="Public:footer"/>