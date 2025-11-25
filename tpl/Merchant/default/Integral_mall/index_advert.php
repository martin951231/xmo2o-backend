<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Integral_mall/index_advert', array('type_key' => $_GET['type_key']))}">{pigcms{:L_("商家兑换商城积分商城首页配置")}</a>
			</li>
			<li class="active">{pigcms{:L_("积分商城导航管理")}</li>
		</ul>
	</div>
    <style>
        .flex {
            display: flex !important;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            align-items: center;
        }
    </style>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">

                        <div class="flex">
						<a class="btn btn-success handle_btn" href="{pigcms{:U('Integral_mall/advert_add', array('type_key' => $_GET['type_key']))}" style="margin-bottom:5px;"><if condition="$_GET['type_key'] && $_GET['type_key'] eq 'index_top_advert'">{pigcms{:L_("添加轮播图")}<elseif condition="$_GET['type_key'] && $_GET['type_key'] eq 'index_middle_single_advert'" />{pigcms{:L_("添加中部单图")}</if></a>
                        <div style="padding-left: 20px;color: #333;">
                            <if condition="$_GET['type_key'] && $_GET['type_key'] eq 'index_middle_single_advert'">
                                【wap站商家积分商城首页中部单图】仅取用一张排序最靠前的一张广告图
                                <elseif condition="$_GET['type_key'] && $_GET['type_key'] eq 'index_top_advert'"/>
                                【wap站商家积分商城首页轮播图】仅取用前5张排序最靠前的广告图
                            </if>
                        </div>
                        </div>

                        <table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("排序")}</th>
									<th width="50">{pigcms{:L_("编号")}</th>
									<th width="50">{pigcms{:L_("名称")}</th>
									<th width="50">{pigcms{:L_("链接地址")}</th>
									<th class="button-column" width="150">	{pigcms{:L_("图片(以下为强制小图，点击图片查看大图")})</th>
									<th class="button-column" width="140">{pigcms{:L_("状态")}</th>
									<th class="button-column" width="140">{pigcms{:L_("最后操作时间")}</th>
									<th class="button-column" width="140">{pigcms{:L_("操作")}</th>
									
								</tr>
							</thead>
							<tbody>
								<if condition="$advert_list">
									<volist name="advert_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.id}</div></td>
											<td><div class="shopNameDiv">{pigcms{$vo.name}</div></td>
											<td><a href="{pigcms{$vo.url}" >{pigcms{:L_("访问链接")}</a></td>
											
											
											<td>
												<if condition="$vo['pic']">
													<img src="{pigcms{:file_domain()}/upload/advert/{pigcms{$vo.pic}" style="width:50px;height:50px;" class="view_msg"/>
												<else/>
													{pigcms{:L_("没有图片")}
												</if>
											</td>
									
											<td class="textcenter"><if condition="$vo.status eq 0">{pigcms{:L_("禁止")}<else />{pigcms{:L_("正常")}</if></td>
											<td class="textcenter">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
											<td>
											
											<a class="handle_btn" href="{pigcms{:U('advert_add',array('id'=>$vo['id'],'type_key' => $_GET['type_key']))}">{pigcms{:L_("编辑")}</a> | <a onclick="del('{pigcms{:U('advert_del',array('id'=>$vo['id']))}')" href="javascript:void(0)" >{pigcms{:L_("删除")}</a>
											</td>
										
											
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="12" >{pigcms{:L_("您没有添加轮播图")}</td></tr>
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
	$(function(){
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 430,
				height: 433,
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
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'<if condition="$_GET['type_key'] && $_GET['type_key'] eq 'index_top_advert'">{pigcms{:L_("轮播图管理")}<elseif condition="$_GET['type_key'] && $_GET['type_key'] eq 'index_middle_single_advert'" />{pigcms{:L_("中部单图管理")}</if>',
				padding: 0,
				width: 600,
				height: 370,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: function(){
					window.location.reload()
				},
				left: '50%',
				top: '38.2%',
				opacity:'0.4',
				
			});
			return false;
		});
		
		$('.view_msg').click(function () {
			window.top.art.preview({
				padding: 0,
				title: '大图',
				url: $(this).attr('src'),
				width: 600,
				height: 400,
				click: false,
				lock: true
			});
		});
	});
	
	function del(url){
	
		if(confirm('{pigcms{:L_("确定删除？")}')){
			window.location.href=url
		}else{
			window.location.reload();
		}
	}
</script>
<include file="Public:footer"/>
