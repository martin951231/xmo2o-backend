<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->

	<!-- 内容头部 -->
	<div class="page-content">
        <label class="col-sm-8" style="background-color:#D9EDF7;padding: 10px 10px 5px;margin-left: 10px;width: 98.75%">
			每个通道二维码可自行打印出来，张贴在对应的通道处。<br/>
			入口二维码：用于无牌车扫码登记进入。<br/>
			出口二维码：用户车辆到达出口扫码付费时，系统会自动快速读取当前车辆的车牌号，免输入、方便快捷。
		</label>
        <label class="col-sm-8"><button class="btn btn-success" style="margin-top: 10px" onclick="passage_add()">添加通道</button></label>
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">通道名称</th>
                                    <th width="15%">通道进出方向</th>
                                    <th width="15%">通道状态</th>
                                    <th width="20%">查看二维码</th>
                                    <th class="button-column" width="25%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.passage_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.passage_direction_desc}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.status_desc}</div></td>
                                            <td>
                                                <div class="tagDiv">
													<div class="tagDiv">
                                                        <if condition="$vo.passage_direction eq 2">
                                                            <a href="?channel_id={pigcms{$vo.channel_number}&spread=3&village_id={pigcms{$vo.village_id}" data-key="{pigcms{$vo.passage_direction}" data-channel="{pigcms{$vo.channel_number}" class="see_qrcode">{pigcms{:L_("查看入口二维码")}</a> |
                                                            <a href="?channel_id={pigcms{$vo.channel_number}&spread=2&village_id={pigcms{$vo.village_id}" data-key="{pigcms{$vo.passage_direction}" data-channel="{pigcms{$vo.channel_number}" class="see_qrcode">{pigcms{:L_("查看出口二维码")}</a>
														<elseif condition="$vo.passage_direction eq 1"/>
                                                            <a href="?channel_id={pigcms{$vo.channel_number}&spread=3&village_id={pigcms{$vo.village_id}" data-key="{pigcms{$vo.passage_direction}" data-channel="{pigcms{$vo.channel_number}" class="see_qrcode">{pigcms{:L_("查看入口二维码")}</a>
														<else/>
                                                            <a href="?channel_id={pigcms{$vo.channel_number}&spread=2&village_id={pigcms{$vo.village_id}" data-key="{pigcms{$vo.passage_direction}" data-channel="{pigcms{$vo.channel_number}" class="see_qrcode">{pigcms{:L_("查看出口二维码")}</a>
                                                        </if>
                                                    </div>
												</div>
                                            </td>
                                            <td class="button-column">
                                                <if condition="$village_park_config['park_sys_type']  eq D3">
                                                <a href="javascript:;" style="width: 60px;" class="label label-sm label-info" title="开杆" onclick="open_gate({pigcms{$vo.id})">开杆</a>
                                                <a href="javascript:;" style="width: 110px;" class="label label-sm label-info" title="设置显屏/语音内容" onclick="set_show({pigcms{$vo.id})">设置显屏/语音内容</a>
                                                </if>
                                                &nbsp;&nbsp;
                                                <a href="javascript:;" style="width: 60px;" class="label label-sm label-info" title="修改" onclick="edit_bill({pigcms{$vo.id})">修改</a>
                                                &nbsp;&nbsp;
                                               &nbsp;&nbsp;
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('passage_del',array('id'=>$vo['id']))}'}">删除</a>

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

function park_month_add(){
	var url = "{pigcms{:U('park_month_add')}";
	window.location.href = url;
}
<if condition="$village_park_config['park_sys_type']  eq 'D5'">
    var area=['850px', '650px'];
    <else/>
    var area=['750px', '550px'];
</if>


function passage_add(){
    var url = "{pigcms{:U('Park/passage_add')}";
    layer.open({
        type: 2,
        title:'添加',
        content: url,
        area: area,
        maxmin: false
    });

}

function set_show(id){
    console.log(11111);
    var href  ="/v20/public/platform/#/community/village/park/showScreenSet?id="+id
    layer.open({
        type: 2,
        title:'绑定',
        content: href,
        area: ['700px', '850px'],
        shadeClose: false,
        maxmin: false
    });
    return false;
};

function edit_bill(id){

    var url = "{pigcms{:U('Park/passage_edit')}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'修改',
        content: href,
        area: area,
        maxmin: false
    });

}

function open_gate(id) {
    $.post("{pigcms{:U('Park/open_gate')}",{id:id,'type':'admin'},function(result){
        console.log('result',result)
        alert(result.info);
    })
    return false;
}

function close_all_page() {
    layer.closeAll();
    window.location.href = "{pigcms{:U('Park/passage_list')}";
}

$('.see_qrcode').live('click',function(){
<if condition="in_array($village_park_config['park_sys_type'],['D3','D6'])">
        var park_type=1;
<else/>
    var park_type=2;
</if>
    var url = '{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode_html&down=true&qrCon=';
    var qrcode_urls = "{pigcms{$qrcode_url}";
    var key =$(this).attr('data-key');
   if (key==1&&park_type==1){
       var channel =$(this).attr('data-channel');
       var hou_url = "{pigcms{$qrcode_url1}"+"&channel_id="+channel;
   }else{
       var hou_url = qrcode_urls + $(this).attr('href');
    }
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