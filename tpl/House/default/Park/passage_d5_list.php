<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Charging/refund_list')}" method="get" id="myidform">
                    <input type="hidden" name="c" value="Park"/>
                    <input type="hidden" name="a" value="passage_list"/>
                    通道类型：
                    <select name="type" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['type']) || $_GET['type'] neq ''">selected="selected"</if>>全部</option>
                        <volist name="d5_park_type" id="vo">
                            <option title="{pigcms{$vo['sub_title']}" value="{pigcms{$vo.key}" <if condition="$_GET['type'] eq $vo['key']">selected="selected"</if> >{pigcms{$vo['title']}</option>
                        </volist>
                    </select>
                    通道名称：
                    <input value="{pigcms{$_GET['passage_name']}" name="passage_name" id="passage_name" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    通道号：
                    <input value="{pigcms{$_GET['device_number']}" name="device_number" id="device_number" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    通道状态：
                    <select name="status" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['status']) || $_GET['status'] neq ''">selected="selected"</if>>全部</option>
                        <option value="2" <if condition="$_GET['status'] eq '2'">selected="selected"</if>>在线</option>
                        <option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>离线</option>
                    </select>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Park/passage_list')}'">重置</a>
                </form>
            </div>
            <button class="btn btn-success" style="margin-top: 10px" onclick="passage_add()">添加通道</button>
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th >通道品牌</th>
                                <th >通道类型</th>
                                <th >通道名称</th>
                                <th >通道号</th>
                                <th >通道状态</th>
                                <th >启用时间</th>
                                <th >编辑时间</th>
                                <th class="button-column" style="width: 12%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$dataList">
                                <volist name="dataList" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                    <td><div class="tagDiv">{pigcms{$vo.park_brand}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.park_type}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.passage_name}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.device_number}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.status}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.start_time|date='Y-m-d H:i:s',###}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>

                                    <td class="button-column">
                                        <a href="javascript:;" style="width: 60px;" class="label label-sm label-info" title="修改" onclick="edit_bill({pigcms{$vo.id})">修改</a>
                                        &nbsp&nbsp;
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
        $.post("{pigcms{:U('Park/open_gate')}",{id:id},function(result){
            alert(result.info);
        })
        return false;
    }

    function close_all_page() {
        layer.closeAll();
        window.location.href = "{pigcms{:U('Park/passage_list')}";
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