<include file="Public:header"/>
<style>
    img{
        width:100px;
        height:50px;
    }
    .color_red{
        color: red
    }

</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('House/maintenan_list')}" class="on">运维管理人员列表</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('House/maintenan_add')}','添加人员',800,450,true,false,false,addbtn,'add',true);">添加人员</a>
        </ul>
    </div>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>姓名</th>
                    <th>手机号</th>
                    <th>添加时间</th>
                    <th>微信昵称</th>
                    <th>状态</th>
                    <th>报警信息</th>
                    <th class="textcenter" style="width: 160px">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="!empty($list)">
                    <volist name="list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.name}</td>
                            <td>{pigcms{$vo.phone}</td>
                            <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
                            <td class="nickname">
                                <if condition="empty($vo['openid'])">
                                    <a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_tmp_qrcode&qrcode_id={pigcms{$vo['id'] + 3400000000}&img=1" data-wid="{pigcms{$vo['id']}" class="see_qrcode color_red" id="qr_{pigcms{$vo['id']}">绑定微信账号</a>
                                    <elseif condition="$vo['openid']"/>
                                    {pigcms{$vo.nickname}
                                    <else/>
                                    -
                                </if>
                            </td>
                            <td>
                                <if condition="$vo.status eq 1">
                                    <span style="color: green">正常</span>
                                    <else/>
                                    <span style="color: red">禁用</span>
                                </if>
                            </td>
                            <td>
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('House/maintenan_log',array('id'=>$vo['id'],'frame_show'=>true))}','查看报警信息',900,500,true,false,false,false,'add',true);">报警信息</a>
                                </td>
                            <td class="textcenter">
                                <if condition="!empty($vo['openid'])">
                                    <span>
                                        <a class="label label-warning cancel" title="取消微信绑定" data-wid="{pigcms{$vo['id']}" <if condition="empty($vo['openid'])">style="display:none"</if> style="color: #FF9900">取消微信绑定</a> |
                                    </span>
                                </if>
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('House/maintenan_edit',array('id'=>$vo['id']))}','编辑人员',800,450,true,false,false,editbtn,'add',true);">编辑</a> |
                                <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('House/maintenan_del')}">删除</a>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="7">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script type="text/javascript">
    var test;
    $(document).ready(function(){
        $('.see_qrcode').live('click', function(){
            test = art.dialog.open($(this).attr('href'),{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.o2oTop.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'扫描二维码绑定微信号',
                padding: 0,
                width: 430,
                height: 433,
                lock: true,
                resize: false,
                background:'black',
                button: null,
                fixed: false,
                close: function(){clearInterval(t);},
                left: '50%',
                top: '38.2%',
                opacity:'0.4'
            });
            var wid = $(this).attr('data-wid'), obj = $(this);
            var t = window.setInterval(function(){
                console.log('校验+++')
                $.get("{pigcms{:U('House/check_maintenan_bind')}", {id:wid},  function(result){
                    if (result.error_code == 0) {
                        test.close();
                        clearInterval(t);
                        obj.parent('td').html(result.nickname).siblings('.button-column').children('.cancel').show();
                    }
                }, 'json');
            },3000);
            return false;
        });

        //取消绑定
        $('.cancel').click(function(){
            var wid = $(this).attr('data-wid'), obj = $(this);
            obj.attr('disabled', true);
            console.log('取消+++')
            $.get("{pigcms{:U('House/cancel_maintenan_bind')}", {id:wid}, function(result){
                obj.attr('disabled', false);
                if (result.error_code == 1) {
                    alert(result.msg);
                } else {
                    var qrcode_id = 3400000000 + wid*1;
                    obj.parent().hide().parent('td').siblings('.nickname').html('<a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_tmp_qrcode&qrcode_id=' + qrcode_id + '&img=1" data-wid="' + wid + '" class="see_qrcode color_red">绑定微信账号</a>');
                }
            }, 'json');
        });
    });
</script>
<include file="Public:footer"/>