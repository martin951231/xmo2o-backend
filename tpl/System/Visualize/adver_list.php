<include file="Public:header"/>
<div class="mainbox" style="margin-top: 10px">
    <div id="nav" class="mainnav_title">
        <input type="button" value="添加广告" class="button" onclick="window.top.artiframe('{pigcms{:U('Visualize/adver_add',array('cat_key'=>$cat_key))}','添加广告   ',1010,595,true,false,false,addbtn,'add',true,'refreshPage');"/>
    </div>
    <div class="page_tips">广告建议尺寸：{pigcms{:str_replace(' ','&nbsp;',$now_category['size_info'])}</div>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup>
                    <col/>
                    <col/>
                    <if condition="$many_city eq 1">
                        <col/>
                    </if>
                    <col/>
                    <col/>
                    <col width="180" align="center"/>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th>编号</th>
                    <th>名称</th>


                    <th>链接地址</th>
                    <th>图片(以下为强制小图，点击图片查看大图)</th>
                    <th class="textcenter">最后操作时间</th>
                    <th>状态</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($adver_list)">
                    <volist name="adver_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.name}</td>

                            <td><a href="{pigcms{$vo.url}" target="_blank">访问链接</a></td>
                            <td>
                                <img src="{pigcms{:file_domain()}/upload/adver/{pigcms{$vo.pic}" style="width:300px;height:80px;" class="view_msg"/>
                            </td>
                            <td class="textcenter">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
                            <td>
                                <if condition="$vo['status'] eq 1">
                                    <font color="green">正常</font>
                                    <elseif condition="$vo['status'] eq 0"/>
                                    <font color="red">关闭</font>
                                </if></td>
                            <td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Visualize/adver_edit',array('id'=>$vo['id'],'from_page'=>$_GET['from_page']))}','编辑广告信息',1010,595,true,false,false,editbtn,'add',true,'refreshPage');">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Visualize/adver_del')}">删除</a></td>
                        </tr>
                    </volist>
                    <else/>
                    <tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    function jump(title,url){
        layer.open({
            type: 2,
            title: title,
            shadeClose: false,
            maxmin: true, //开启最大化最小化按钮
            area: ['80%', '80%'],
            content: url
        });
    }
</script>
<include file="Public:footer"/>