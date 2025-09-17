<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">

    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
<!--            <button class="btn " onclick="park_config()">停车费设置</button>&nbsp;&nbsp;-->
<!--            <button class="btn " onclick="preferential_list()">月租车管理</button>&nbsp;&nbsp;-->
<!--            <button class="btn " onclick="temporary_list()">临时停车管理</button>&nbsp;&nbsp;-->
<!--            <button class="btn " onclick="vehicle_management()">车辆管理</button>&nbsp;&nbsp;-->

            <style>
                .ace-file-input a {display:none;}
            </style>

            <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('preferential_add',array('park_month_id'=>$_GET['park_month_id']))}'">添加费用</button></span>&nbsp;&nbsp;&nbsp;&nbsp;
            <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('park_month_list')}'">月租车收费管理</button></span>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">编号</th>
                                    <th width="20%">停车缴费周期</th>
									<th width="20%">赠送停车时间</th>
                                    <th width="10%">添加时间</th>
                                    <th width="10%">状态</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list['list']">
                                    <volist name="list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.park_month_num} 个月</div></td>
											 <td><if condition='$vo["presented_park_month_num"]'><div class="tagDiv">{pigcms{$vo.presented_park_month_num} 个月</div><else /><div class="tagDiv red">暂无</div></if></td>
                                            <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                                            <td class="button-column">
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('preferential_edit',array('id'=>$vo['id'],'park_month_id'=>$_GET['park_month_id']))}">编辑</a>
                                                <if condition="in_array(31,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('preferential_del',array('id'=>$vo['id'],'park_month_id'=>$_GET['park_month_id']))}'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有任何缴费优惠。</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    function park_config(){
        window.location.href = "{pigcms{:U('park_config')}";
    }

    function preferential_list(){
        window.location.href = "{pigcms{:U('preferential_list')}";
    }

    function temporary_list(){
        window.location.href = "{pigcms{:U('temporary_list')}";
    }

    function vehicle_management(){
        window.location.href = "{pigcms{:U('Unit/vehicle_management')}";
    }

</script>
<include file="Public:footer"/>
