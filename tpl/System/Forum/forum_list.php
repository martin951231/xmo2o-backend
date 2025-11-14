<include file="Public:header"/>
<style>
    img{
        width:100px;
        height:50px;
    }
</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('System/Forum/forum_list')}" class="on">论坛精选帖子</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('System/Forum/forum_add')}','添加',900,600,true,false,false,addbtn,'add',true);">添加</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('System/Forum/forum_list')}" method="get">
                    <input type="hidden" name="g" value="System"/>
                    <input type="hidden" name="c" value="Forum"/>
                    <input type="hidden" name="a" value="forum_list"/>
                    时间筛选:&nbsp;&nbsp;
                    <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="submit" value="查询" class="button"/>

                    <span class="btn-success button" onclick="location.href='{pigcms{:U('System/Forum/forum_list')}'">重置</span>
                </form>


            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup><col> <col> <col><col><col><col><col><col width="180" align="center"> </colgroup>
                <thead>
                <tr>
                    <th>标题</th>
                    <th>描述</th>
                    <th>上次推送时间</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($adver_list)">
                    <volist name="adver_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.title}</td>
                            <td>{pigcms{$vo.content}</td>
                            <td>
                                {pigcms{$vo.push_time}
                            </td>
                            <td class="textcenter">
                                <a href="javascript:void(0);" class="push" data-id="{pigcms{$vo.id}">一键推送</a> |
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('System/Forum/forum_edit',array('id'=>$vo['id']))}','编辑',900,600,true,false,false,editbtn,'add',true);">编辑</a> |
                                <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('System/Forum/forum_del')}">删除</a></td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="4">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="4">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    $('.push').click(function () {
        var that=$(this),id=that.attr('data-id');

       var loading = layer.msg('正在加载', {
            icon: 16,
            shade: [0.1, '#000'],
            time: false  // 取消自动关闭
        });
        $.ajax({
            url:'{pigcms{:U('System/Forum/forum_push')}',
            type:"post",
            dataType:"JSON",
            data: {id:id},
            success:function(d){
                layer.close(loading);
                layer.msg(d.info);
                if(d.status == 1){
                    setTimeout(function(){window.location.reload();},800);
                }
            }
        });
    })
</script>
<include file="Public:footer"/>