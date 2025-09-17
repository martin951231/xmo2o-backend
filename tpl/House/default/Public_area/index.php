<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Public_area/index')}">公共区域管理</a>
            </li>
            <li class="active">公共区域列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <button class="btn btn-success" onclick="publicAreaAdd()">添加公共区域</button>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">公共区域名称</th>
                                    <th width="30%">状态</th>
                                    <th width="20%">最后修改时间</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$public_area && $public_area['list']">
                                    <volist name="public_area['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.public_area_name}</div></td>
                                           <td><div class="tagDiv">{pigcms{$vo.status_txt}</div></td>
                                           <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>

                                            <td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a title="编辑" href="{pigcms{:U('edit',array('public_area_id'=>$vo['public_area_id']))}">编辑</a>
												</div>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="4" >暂无数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$public_area['pagebar']}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
function publicAreaAdd(){
	window.location.href = "{pigcms{:U('add')}";
}
</script>
<include file="Public:footer"/>
