<include file="Public:header"/>



<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-cubes"></i>
                <a href="{pigcms{:U('category_list')}">{pigcms{:L_("积分商城分类列表")}</a>
            </li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div style="margin-bottom:10px;">
                <a href="{pigcms{:U('category_add',array('cat_fid'=>intval($_GET['cat_fid'])))}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">

                    <if condition="empty($_GET['cat_fid'])">
                        {pigcms{:L_("添加主分类")}
                        <else/>
                        {pigcms{:L_("添加子分类")}
                    </if>
                </a>
            </div>
            <style>
                .ace-file-input a {display:none;}
                td.textcenter{text-align: center;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="50">{pigcms{:L_("排序")}</th>
                                <th width="50">{pigcms{:L_("编号")}</th>
                                <th width="100">{pigcms{:L_("名称")}</th>
                                <if condition="empty($_GET['cat_fid'])">
                                    <th>查看子分类</th>
                                </if>
                                <th>状态</th>
                                <th class="textcenter">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <notempty name='category_list.list'>
                                <volist name="category_list['list']" id="vo">
                                    <tr>
                                        <td>{pigcms{$vo.cat_sort}</td>
                                        <td>{pigcms{$vo.cat_id}</td>
                                        <td><if condition="$vo['is_hot']"><font color="red">{pigcms{$vo.cat_name}</font><else/>{pigcms{$vo.cat_name}</if></td>
                                        <if condition="empty($_GET['cat_fid'])">
                                            <td><a href="{pigcms{:U('category_list',array('cat_fid'=>$vo['cat_id']))}">查看子分类</a></td>
                                        </if>

                                        <td><if condition="$vo['cat_status'] eq 1"><font color="green">启用</font><elseif condition="$vo['cat_status'] eq 2"/><font color="red">待审核</font><else/><font color="red">关闭</font></if></td>


                                        <td>
                                            <a href="{pigcms{:U('category_edit',array('cat_id'=>$vo['cat_id'],'frame_show'=>true))}">查看</a> | <a href="{pigcms{:U('category_edit',array('cat_id'=>$vo['cat_id']))}">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="cat_id={pigcms{$vo.cat_id}" url="{pigcms{:U('category_del')}">删除</a>
                                        </td>
                                    </tr>
                                </volist>
                                <tr><td class="textcenter pagebar" <if condition='$_GET["cat_fid"]'>colspan="7"<else />colspan="6"</if>>{pigcms{$pagebar}</td></tr>
                            <else/>
                                <tr><td class="textcenter red" <if condition='$_GET["cat_fid"]'>colspan="7"<else />colspan="6"</if>>列表为空！</td></tr>
                            </notempty>
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
        $('.delete_row').click(function(){
            var obj=$(this);
            layer.open({
                content:'{pigcms{:L_("你确定这样操作吗？操作后可能不能恢复！")}',
                btn:['{pigcms{:L_("确定','取消")}'],
                yes:function(index){
                    var url=obj.attr('url');
                    var parameter=obj.attr('parameter');
                    url+='&'+parameter;
                    $.post(url,function(res){
                        layer.close(index);
                        layer.open({
                            content:res.info,
                            btn:['{pigcms{:L_("确定")}'],
                            yes:function(index2){
                                if(res.status){
                                    layer.close(index2);
                                    window.location.reload();
                                }else{
                                    layer.close(index2);
                                }
                            }
                        });
                    });
                },
                cancel:function(index){
                    layer.close(index);
                }
            });
        });
    });
</script>
<include file="Public:footer"/>