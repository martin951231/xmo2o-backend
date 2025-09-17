<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Index/index')}">小区管理</a>
            </li>
            <li class="active">基本信息设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">



                    <if condition="$_GET['cat_key'] == 'house_app_index_top'">
                        <button class="btn btn-success" onclick="slide_add()" <if condition="!in_array(288,$house_session['menus'])">disabled="disabled"</if>>添加轮播图广告</button>
                    <elseif condition= "$_GET['cat_key'] == 'house_app_index_center'"/>
                        <button class="btn btn-success" onclick="slide_add()" <if condition="!in_array(288,$house_session['menus'])">disabled="disabled"</if>>添加轮播图广告</button>
                    <elseif condition="$_GET['cat_key'] == 'house_four_adver'"/>
                        <button class="btn btn-success" onclick="slide_add()" <if condition="!in_array(288,$house_session['menus'])">disabled="disabled"</if>>添加轮播图广告</button>
                    <elseif condition="$_GET['cat_key'] == 'house_center_adver'"/>
                        <button class="btn btn-success" onclick="slide_add()" <if condition="!in_array(288,$house_session['menus'])">disabled="disabled"</if>>添加轮播图广告</button>
                    <elseif condition="$_GET['cat_key'] == 'house_footer_adver'"/>
                        <button class="btn btn-success" onclick="slide_add()" <if condition="!in_array(288,$house_session['menus'])">disabled="disabled"</if>>添加轮播图广告</button>
                    </if>


                    </button>


                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="10%">名称</th>
                                <th width="20%">链接地址</th>
                                <th width="25%">图片</th>
                                <th width="5%">状态</th>
                                <th width="5%">排序</th>
                                <th width="10%">最后操作时间</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$adver_list">
                                <volist name="adver_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                            <td><div class="tagDiv"><a href="{pigcms{$vo.url}" target="_blank">查看链接</a></div></td>
                            <td><div class="tagDiv"><img src="{pigcms{:file_domain()}/upload/adver/{pigcms{$vo.pic}" width="140px" height="58px" /></div></td>
                            <td><div class="tagDiv">
                                    <if condition='$vo["status"] eq 0'>
                                        <span class="red">关闭</span>
                                        <else />
                                        <span class="green">开启</span>
                                    </if>
                                </div></td>

                            <td>
                                <div class="tagDiv">{pigcms{$vo.sort}</div>
                            </td>

                            <td>
                                <div class="tagDiv">{pigcms{$vo.last_time|date="Y-m-d H:i:s",###}</div>
                            </td>

                            <td class="button-column">
                                <if condition="$_GET['cat_key']">
                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('adver_edit',array('id'=>$vo['id']))}">编辑</a>&nbsp;
                                <if condition="in_array(198,$house_session['menus'])">
                                    <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('adver_del',array('id'=>$vo['id']))}'}">删除</a>
                                </if>
                                </if>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="8" >没有任何广告。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function slide_add(){
        window.location.href = "/shequ.php?g=House&c=Index&a=adver_add&cat_key="+"{pigcms{$_GET['cat_key']}";
    }
</script>
<include file="Public:footer"/>