<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{$config.ecard_alias_name}列表</title>
		<link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				//设置高度
				$('.js-modal iframe',parent.document).height($('body').height()+16);

				//关闭
				$('.modal-header .close').live("click", function(){
					var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
					parent.layer.close(index); //再执行关闭
				});

				//确定选取
				$('.js-choose2').live('click',function(){
					var ecard_id=$(this).data('ecard_id');
					var card_name=$(this).data('card_name');
					var index = parent.layer.getFrameIndex(window.name);
					parent.choice_ecard(ecard_id,card_name,index);
				});

				//分页
				$('.js-page-list a').live('click',function(e){
					if(!$(this).hasClass('active')){
						var input_val = $('.js-modal-search-input').val();
						var page=$(this).data('page-num');
						search(input_val,page);
					}
				});
				
				//搜索
				$('.js-modal-search').live('click',function(e){
					var input_val = $('.js-modal-search-input').val();
					search(input_val);
					return false;
				});

				//回车提交搜索
				$(window).keydown(function(event){
					if (event.keyCode == 13 && $('.js-modal-search-input').is(':focus')) {
						var input_val = $('.js-modal-search-input').val();
						search(input_val);
						return false;
					}
				});
			});
		</script>
		<script type="text/javascript">
			/*搜索*/
			function search(val,page){
				page=page || 1;
				$('body').html('<div class="loading-more"><span></span></div>');
				$('body').load('?c=Diypage&a=ecard_list',{'keyword':val,p:page},function(){
					$('.js-modal iframe',parent.document).height($('body').height());
				});
			}
		</script>
		<style type="text/css">
		.table th, .table td{
			vertical-align: middle;
		}
		</style>
	</head>
	<body style="background-color:#ffffff;">
		<div class="modal-header">
			<a class="close js-news-modal-dismiss">×</a>
			<!-- 顶部tab -->
			<ul class="module-nav modal-tab">
				<li class="active"><a href="javascript:void(0);" class="js-modal-tab">已制卡{pigcms{$config.ecard_alias_name}列表</a> </li>
			</ul>
		</div>
		<div class="modal-body">
			<div class="tab-content">
				<div id="js-module-feature" class="tab-pane module-feature active">
					<table class="table">
						<colgroup>
							<col class="modal-col-title">
							<col class="modal-col-time" span="2">
							<col class="modal-col-action">
						</colgroup>
						<!-- 表格头部 -->
						<thead>
							<tr>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>标题</span> 
										<a class="js-update" href="javascript:window.location.reload();">刷新</a>
									</div>
								</th>
								<th class="time" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>面值</span>
									</div>
								</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" onsubmit="return false;">
											<div class="input-append">
												<input class="input-small js-modal-search-input" type="text" style="border-radius:4px 0px 0px 4px;text-indent:5px;"/>
												<a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" style="border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
											</div>
										</form>
									</div>
								</th>
							</tr>
						</thead>
						<!-- 表格数据区 -->
						<tbody>
							<volist name="list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">
											<a target="_blank" class="new_window" href="javascript:">{pigcms{$vo.card_name}</a>
										</div>
									</td>
									<td class="time">
										<div class="td-cont">
											<span>{pigcms{$config.Currency_symbol}{pigcms{$vo.card_price}</span>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
											<if condition="$vo['make_count'] eq 0">
												<button class="btn js-choose" disabled="disabled">未制卡成功</button>
											<elseif condition="$vo['bind_goods_id'] gt 0"/>
												<button class="btn js-choose" disabled="disabled">已绑定商品</button>
											<elseif condition="$vo['id'] eq $_GET['default_ecard_id']"/>
												<button class="btn js-choose" disabled="disabled">已绑定</button>
											<else/>
												<button class="btn js-choose2" data-ecard_id='{pigcms{$vo.id}' data-card_name="{pigcms{$vo.card_name}">选取</button>
											</if>
										</div>
									</td>
								</tr>
							</volist>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$pagebar}</div>
		</div>
	</body>
</html>