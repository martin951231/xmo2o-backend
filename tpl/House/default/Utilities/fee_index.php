<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <button class="btn btn-success" style="margin-bottom: 10px" onclick="fee_add()">添加记录</button>
<!--            <button class="btn btn-success" style="margin: 0 0 10px 10px;" onclick="fee_import()">导入分类</button>-->
<!--            <button class="btn btn-success" style="margin: 0 0 10px 10px;" onclick="fee_export()">导出分类</button>-->
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="15%">序号</th>
                                <th width="25%">类别名称</th>
                                <th width="15%">时间</th>
                                <th width="15%">用量</th>
                                <th width="15%">总费用</th>
                                <th class="button-column" width="15%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$dataList">
                                <volist name="dataList" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.category_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.add_date}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.consumption}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.rate}</div></td>
                            <td class="button-column">
                                <if condition="!isset($vo['from_type']) OR $vo['from_type'] neq 1">
                                    <a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('fee_edit',array('id'=>$vo['id']))}">修改</a>&nbsp;
                                    <if condition="in_array(206,$house_session['menus'])">
                                        <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('fee_del',array('id'=>$vo['id']))}'}">删除</a>
                                    </if>

                                </if>
                            </td>

                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    function fee_add(){
        var url = "{pigcms{:U('fee_add')}";
        window.location.href = url;
    }
</script>

<include file="Public:footer"/>