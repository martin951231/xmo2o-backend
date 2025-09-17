<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->

	<!-- 内容头部 -->
	<div class="page-content">
        <div class="col-sm-8" style="background-color:#D9EDF7;padding: 5px 10px 5px;width: 1590px;margin-left: 10px;">
            被加入黑名单的用户，任何情况下无法进入停车场，如需取消黑名单，操作移出黑名单即可
		</div>
      <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;margin-top: 50px">
            <form method="get" action="{pigcms{:U('Park/park_statistics')}" id="find-form" onSubmit="return check_submit()">
                <input type="hidden" name="c" value="Park"/>
                <input type="hidden" name="a" value="park_black_list"/>
                <select name="param">
                    <option value="user_name" <if condition="$_GET['param'] eq 'user_name'"> selected </if>>姓名</option>
                    <option value="phone" <if condition="$_GET['param'] eq 'phone'"> selected </if>>手机号</option>
                </select>
                <input value="{pigcms{$_GET['name']}" name="name" id="name" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                &nbsp;&nbsp;
                车牌号：
                <select name="province" id="province" >
                    <option value="">--请选择省份--</option>
                    <volist name="city_arr" id="vo">
                        <option value="{pigcms{$vo}">{pigcms{$vo}</option>
                    </volist>
                </select>
                <input style="margin-right:10px;font-size:18px;height:42px;" name="car_number" id="car_number" type="text"  value="" />
                &nbsp;&nbsp;
                时间筛选：
                <input type="text" id="start_time" name="start_time" class="input-text" value="{pigcms{$_GET['start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>
                &nbsp;—&nbsp;
                <input type="text" id="end_time" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;
                <input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/pack_black_list')}'">重置</button>
            </form>
        </div>
        <label class="col-sm-8"><button class="btn btn-success" style="margin-top: 10px" onclick="passage_add()">添加</button></label>
        <div class="page-content-area">
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">姓名</th>
                                    <th width="20%">手机号</th>
                                    <th width="20%">车牌号</th>
                                    <th width="20%">添加时间</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.user_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.car_number}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.addTime|date="Y-m-d H:i:s",###}</div></td>
                                            <td class="button-column">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('del_pack_black',array('id'=>$vo['id']))}'}">删除</a>
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

function passage_add(){
    var url = "{pigcms{:U('Park/add_pack_black')}";

    layer.open({
        type: 2,
        title:'添加',
        content: url,
        area: ['750px', '400px'],
        maxmin: false
    });

}

function del_pack_black(car_no){

    var url = "{pigcms{:U('Park/del_pack_black')}";
    var href  = url + '&car_no='+car_no
    layer.open({
        type: 2,
        title:'修改',
        content: href,
        area: ['750px', '550px'],
        maxmin: false
    });

}

function open_gate(id) {
    $.post("{pigcms{:U('Park/open_gate')}",{id:id},function(result){
        alert(result.info);
    })
    return false;
}

function close_all_page() {
    layer.closeAll();
    window.location.href = "{pigcms{:U('Park/pack_black_list')}";
}


$('.see_qrcode').live('click',function(){
    var url = '{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode_html&down=true&qrCon=';
    var qrcode_urls = "{pigcms{$qrcode_url}";
    var hou_url = qrcode_urls + $(this).attr('href');
    var qrcode_url = url+encodeURIComponent(hou_url);
    art.dialog.open(qrcode_url,{
        init: function(){
            var iframe = this.iframe.contentWindow;
            window.top.art.dialog.data('iframe_handle',iframe);
        },
        id: 'handle',
        title:'{pigcms{:L_("如需保存，鼠标右键点击图片另存为即可")}',
        padding: 0,
        width: 312,
        height: 312,
        lock: true,
        resize: false,
        background:'black',
        button: null,
        fixed: false,
        close: null,
        left: '50%',
        top: '38.2%',
        opacity:'0.4'
    });
    return false;
});
</script>

<include file="Public:footer"/>