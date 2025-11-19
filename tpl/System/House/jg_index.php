<include file="Public:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css?t=01">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">
<link rel="stylesheet" href="{pigcms{$static_public}layui/css/layui.css"  media="all">
<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<div class="mainbox">

    <!-- 内容头部 -->
    <div class="page-content">

        <div class="tabbable">

        </div>
        <div class="page-content-area">
            <div class="row">
                <div class="form-group" style="padding:10px;">
                    <if condition="$weixin_push_jpush_appkey AND $weixin_push_jpush_secret">
                    <a class="button" onclick="add()">添加极光群发</a>
                    <else/>
                      <span style="color: red;">您还没有配置好平台极光推送。请先去相应菜单（系统设置=》站点配置=》APP支付分享配置=》APP极光推送）下配置</span>
                    </if>
                </div>
                
                    <div class="table-list">
                        <table  width="100%">
                            <thead>
                                <tr>
                                    <th width="10%">标题</th>
                                    <th width="10%">发送对象</th>
                                    <th width="10%">发送状态</th>
                                    <th width="20%">发送内容</th>
                                    <th width="10%">发送时间</th>
                                    <th width="10%">添加时间</th>
                                    <th  class="textcenter" >查看发送结果</th>
                                </tr>
                            </thead>
                            <tbody>
						<if condition="is_array($list) && !empty($list)">
							<volist name="list" id="vo">
                            <tr>
									<td>{pigcms{$vo.jg_title}</td>
									<td>{pigcms{$vo.village_detail}</td>
                                    <td>{pigcms{$vo.send_status_str}</td>
                                    <td>{pigcms{$vo.send_content}</td>
                                    <td>{pigcms{$vo.send_time_str}</td>
                                    <td>{pigcms{$vo.add_time_str}</td>
									<td class="textcenter"><a  class="" title="查看结果" href="{pigcms{:U('House/jg_send_record',array('send_id'=>$vo['id']))}">查看结果</a>&nbsp;&nbsp;</td>
                             </tr>
                            </volist>
                            <tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
						</if>
                            </tbody>
                        </table>
                    </div>
                
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">


    function add() {

        window.o2oTop.artiframe('{pigcms{:U('House/jg_add')}','添加极光群发',900,600,true,false,false,addbtn,'add',true,'refreshPage');

    }
    function close_all_page() {
        console.log('关闭弹窗');
        layer.closeAll();
        window.location.reload();
    }

</script>
<include file="Public:footer"/>
