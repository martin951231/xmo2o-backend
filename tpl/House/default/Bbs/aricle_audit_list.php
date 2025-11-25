<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-file-excel-o"></i>
	            <a href="{pigcms{:U('Bbs/index')}">社区邻里</a>
            </li>

            <li>待审核文章列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="5%">No.</th>
                                	<th width="15%">文章标题</th>
                                    <th width="6%">文章图片</th>
                                    <th width="12%">业主信息</th>
                                    <th width="10%">发言状态</th>
                                    <th width="5%">赞数量</th>
                                   	<th width="5%">评论数</th>
                                   	<th width="5%">状态</th>
                                   	<th width="3%">排序</th>
                                   	<th width="6%">更新时间</th>
                                   	<!--th width="8%">过期时间</th-->
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$aBbsAricle">
                                    <volist name="aBbsAricle" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.aricle_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.aricle_title}</div></td>
                                            <td><div class="tagDiv text-center"><if condition="$vo['aricle_img']"><img src="{pigcms{$vo.aricle_img}" width="45" height="45" /><else/>无</if></div></td>

                                            <td>

                                                <div class="tagDiv">姓名： {pigcms{$vo.village_user_info.name|default='--'}

                                                <if condition="in_array(277,$house_session['menus'])">
                                                    <if condition="$vo['is_ban'] eq 1">
                                                        <a style="height: 20px; line-height: 16px;margin-left: 5px;" data-uid="{pigcms{$vo.uid}" class="label label-sm label-info" title="解除禁言" href="javascript:void(0)" onclick="if(confirm('确认解除用户禁言？')){location.href='{pigcms{:U('unban',array('uid'=>$vo['uid']))}'}">解除禁言</a>
                                                    <else/>
                                                        <a style="height: 20px; line-height: 16px;margin-left: 5px;" data-uid="{pigcms{$vo.uid}" class="label label-sm label-info" title="设置禁言" href="javascript:void(0)" onclick="ban_speak(this)">设置禁言</a>
                                                    </if>
                                                </if>
                                                </div>
                                                <div class="tagDiv">电话： {pigcms{$vo.village_user_info.phone|default='--'}</div>
                                            </td>

                                            <td>
                                                <if condition="$vo['is_ban'] eq 1">
                                                    <div class="tagDiv">
                                                    已禁言（
                                                    <if condition="$vo['ban_type'] eq 1">
                                                        永久禁言
                                                    <else/>
                                                    禁言到{pigcms{$vo.ban_end_time}
                                                    </if>
                                                    ）
                                                    </div>
                                                <else/>
                                                <div class="tagDiv" style="color:green;">正常</div>
                                                </if>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.aricle_praise_num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.aricle_comment_total}</div></td>
                                            <if condition="$vo.aricle_status eq 1">
                                            	<td><div class="tagDiv" style="color:green;">审核通过</div></td>
                                            <elseif condition="$vo.aricle_status eq 3" />
                                            	<td><div class="tagDiv" style="color:red;">审核不通过</div></td>
                                            <elseif condition="$vo.aricle_status eq 2" />
                                            	<td><div class="tagDiv" style="color:#0044BB;">待审核</div></td>
                                            <elseif condition="$vo.aricle_status eq 4" />
                                            	<td><div class="tagDiv" style="color:Gray;">用户删除</div></td>
                                            </if>
                                            <td><div class="tagDiv">{pigcms{$vo.aricle_sort}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.update_time|date='Y-m-d',###}</div></td>

                                            <td class="button-column">
                                            	<a style="width:80px;height:26px;line-height:20px;" class="label label-sm label-info" title="去审核" href="{pigcms{:U('aricle_status_show',array('aricle_id'=>$vo['aricle_id'],'source'=>1))}">去审核</a>
                                                &nbsp;
                                                <a style="width:80px;height:26px;line-height:20px;" class="label label-sm label-info handle_btn" title="查看详情" href="{pigcms{:U('aricle_list_details',array('aricle_id'=>$vo['aricle_id'],'cat_id'=>$vo['cat_id']))}">查看详情</a>
                                            </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="11" >暂无数据。</td></tr>
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
<div id="print_template" style="display: none;">
    <table class="table table-striped table-bordered table-hover" style="width: 300px;">
        <tbody>
            <tr >
                <th>禁言类型：</th>
                <td>
                    <select name="type" id="type">
                        <option value="0">自定义</option>
                        <option value="1">永久禁言</option>
                    </select>
                </td>
            </tr>
            <tr class="days">
                <th>禁言天数：</th>
                <td>
                    <select name="days" id="days">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                    </select>
                    天
                </td>
            </tr>
        </tbody>
    </table>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
	$('.handle_btn').live('click',function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'文章详情',
			padding: 0,
			width: 720,
			height: 520,
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

    $('#type').on('change',function(){
        if ($(this).val()==1) {
            $('.days').hide();
        }else{
            $('.days').show();
        }
    })

    function ban_speak(obj){
        var uid = $(obj).data('uid')
        var post_url = '{pigcms{:U('ban_speak')}';
        art.dialog({
            content: document.getElementById('print_template'),
            id: 'handle',
            title:'选择打印模板',
            padding: 0,
            width: 350,
            height: 200,
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
                var type = $('#type').val();
                var days = $('#days').val();
                $.post(post_url,{uid:uid,type:type,days:days,is_ban:1},function(data){
                    console.log(data)
                    if (data.error) {
                        layer.alert(data.message)
                    }else{
                        layer.msg(data.message)
                        setTimeout(function(){
                            window.location.reload()
                        },2000)
                    }
                },'json');
            }
        });

    }
</script>
<include file="Public:footer"/>
