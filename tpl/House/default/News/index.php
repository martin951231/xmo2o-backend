<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tablet"></i>
                <a href="{pigcms{:U('News/index')}">新闻列表</a>
            </li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="tabbable">
                <ul id="myTab" class="nav nav-tabs">
                    <li>
                        <a href="{pigcms{:U('News/cate',$_GET)}">新闻分类</a>
                    </li>
                    <li class="active">
                        <a href="javascript:;" data-toggle="tab">新闻列表</a>
                    </li>
                    <li>
                        <a href="{pigcms{:U('News/reply',$_GET)}">新闻评论列表</a>
                    </li>
                </ul>
            </div>
            <div style="padding-top: 15px;">
                <button class="btn btn-success" onclick="CreateCategory()" <if condition="!in_array(159,$house_session['menus'])">disabled="disabled"</if>>新增新闻</button>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="40%">新闻标题</th>
                                <th width="10%">发布时间</th>
                                <th width="10%">到期时间</th>
                                <th width="10%">热门</th>
                                <th width="10%">所属分类</th>
                                <th width="10%">已微信通知</th>
                                <th class="button-column" width="15%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
								<if condition="$news_list">
                                    <volist name="news_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.title}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                <td><div class="tagDiv"><if condition="$vo.end_time gt 0 ">{pigcms{$vo.end_time|date='Y-m-d H:i:s',###}<else />--</if></div></td>
											<td><div class="shopNameDiv"><if condition="$vo.is_hot eq '1' ">是<else />否</if></div></td>
											<td><div class="tagDiv">{pigcms{$vo.cat_name}</div></td>
											<td><div class="shopNameDiv"><if condition="$vo.is_notice eq '1' ">是<else />否</if></div></td>

											<td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('News/news_edit',array('news_id'=>$vo['news_id']))}">编辑</a>&nbsp;

												<if condition="$vo['is_notice'] eq 0">
                                                    <if condition=" $vo['status'] eq 0 ">
													<a style="width: 100px;" class="label label-sm label-info" href="javascript:;">此新闻已被关闭</a>
                                                    <else/>
                                                    <if condition="in_array(162,$house_session['menus'])">　
                                                        <a style="width: 100px;" class="label label-sm label-info handle_btn" href="javascript:void(0);" news_id="{pigcms{$vo['news_id']}" onclick="send_weixin(this)">微信群发业主</a>
                                                    </if>
                                                    </if>
												</if>&nbsp;
                                                <if condition="in_array(161,$house_session['menus'])">　
												<a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认进行删除')){location.href='{pigcms{:U('News/news_del',array('news_id'=>$vo['news_id']))}'}">删除</a>
                                                </if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="6" >您没有添加任何新闻。</td></tr>
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
<div id="send_user_type_div" class="grid-view" style="display: none;">
    <table class="table table-striped table-bordered table-hover">
        <tbody>
            <tr >
                <th style="width: 150px; text-align: right;">发送给：</th>
                <td>
                    <select name="send_user_type" id="send_user_type" style="width: 300px">
                        <option value="1">仅业主</option>
                        <option value="2">业主及家属</option>
                        <option value="3">租客</option>
                        <option value="4">仅工作人员</option>
                        <option value="5">业主和家属和租客</option>
                        <option value="6">业主和工作人员</option>
                    </select>
                </td>
            </tr>
        <tr>
            <th style="width: 150px; text-align: right;">群发方式：</th>
            <td>
                <select name="send_type" id="send_type" style="width: 300px">
                    <option value="1">优先企业微信，没加好友则通过公众号</option>
                    <option value="2">企业微信</option>
                    <option value="3">公众号</option>
                </select>
            </td>
        </tr>
        </tbody>
    </table>
    <!-- <img src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id={pigcms{$now_order['order_id']+700000000}" style="width:250px;height:250px;"/> -->
    <input type="hidden" name="pigcms_id" value="{pigcms{$vo.pigcms_id}">
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    function CreateCategory(){
        window.location.href = "{pigcms{:U('News/news_edit')}";
    }

    $(function(){
        $('.handle_btn').live('click',function(){
            art.dialog.open($(this).attr('href'),{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'提示',
                padding: 0,
                width: 720,
                height: 420,
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

    });

    //发送微信通知 
    function send_weixin(obj){
       
        var news_id = $(obj).attr('news_id');
         art.dialog({
            content: document.getElementById('send_user_type_div'),
            id: 'handle',
            title:'发送微信通知',
            padding: 0,
            width: 450,
            height: 280,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            okVal:'确定',
            cancelVal:'取消',
            left: '50%',
            top: '38.2%',
            opacity:'0.4',
            ok:function (argument) {
                var send_user_type = $('#send_user_type').val();
                var send_type = $('#send_type').val();
                var url = "{pigcms{:U('send')}";
                $.get(url , {'news_id':news_id,send_user_type:send_user_type,'send_type':send_type},function(data){
                    if (data.code == 0) {
                        alert(data['msg']);
                        window.location.reload();
                    }else{
                        alert(data['msg']);
                    }
                },'json')
            }
        });
    }
</script>
<include file="Public:footer"/>