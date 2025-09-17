<?php if(empty($_GET['not_first'])){ ?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("微页面")}</title>
		<link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			var pageForm = '{pigcms{$_GET.pageFrom}';
			var max_num  = {pigcms{$_GET.max_num|intval=###};
			$(function(){
				$('.js-modal iframe',parent.document).height($('body').height()+16);
				$('.modal-header .close').live('click',function(){
					parent.login_box_close();
				});

				$('button.js-choose').live('click',function(){
					if($(this).hasClass('btn-primary')){
						$(this).removeClass('btn-primary').html('{pigcms{:L_("选取")}');
					}else{
						$(this).addClass('btn-primary').html('{pigcms{:L_("取消")}');
					}
					if($('.js-choose.btn-primary').size() > 0){
						$('.js-confirm-choose').show();
					}else{
						$('.js-confirm-choose').hide();
					}
				});

				// 点击分页
				$('.js-page-list a').live('click',function(e){
					if(!$(this).hasClass('active')){
						var input_val = $('.js-modal-search-input').val();
						$('body').html('<div class="loading-more"><span></span></div>');
						$('body').load("{pigcms{:U('select_store',array('selecteditems'=>$_GET['selecteditems'],'store_id'=>$now_store['store_id'],'number'=>$_GET['number'],'cate_num'=>$_GET['cate_num'],'not_first'=>1))}",{page:$(this).data('page-num'),'keyword':input_val},function(){
							$('.js-modal iframe',parent.document).height($('body').height());
						});
					}
				});

				// 搜索
				$('.js-modal-search').live('click',function(e){
					var input_val = $('.js-modal-search-input').val();
					$('body').html('<div class="loading-more"><span></span></div>');
					$('body').load("{pigcms{:U('select_store',array('selecteditems'=>$_GET['selecteditems'],'store_id'=>$now_store['store_id'],'number'=>$_GET['number'],'cate_num'=>$_GET['cate_num'],'not_first'=>1))}",{'keyword':input_val},function(){
						$('.js-modal iframe',parent.document).height($('body').height());
					});
					return false;
				});

				// 确定使用
				$('.js-confirm-choose').live('click',function(){
					var data_arr = [];
					var goods_ids = [];
					
					$.each($('.js-choose.btn-primary'),function(i,item){
						id = $(item).data('id');
						name = $(item).data('name');
						image = $(item).data('image');
						url = $(item).data('url');
                        var store_detail = {'id':id,'title':name,image:image,url:url};
                        goods_ids.push(id);
                        data_arr.push( store_detail );
					});
					parent.widget_box_after('{pigcms{$_GET.number}',data_arr,'{pigcms{$_GET.cate_num}');
					
				});
			});
		</script>
	</head>
	<body style="background-color:#ffffff;">
<?php } ?>
		<div class="modal-header">
			<a class="close js-news-modal-dismiss">×</a>
			<!-- {pigcms{:L_("顶部")}tab -->
			<ul class="module-nav modal-tab">
				<li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("店铺列表")}</a> 
			</ul>
		</div>
		<div class="modal-body">
			<div class="tab-content">
				<div id="js-module-feature" class="tab-pane module-feature active">
					<table class="table">
						<colgroup>
							<col class="modal-col-title">
							<col class="modal-col-time">
							<col class="modal-col-action">
						</colgroup>
						<!-- {pigcms{:L_("表格头部")} -->
						<thead>
							<tr>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("名称")}</span> <a class="js-update" href="javascript:window.location.reload();">{pigcms{:L_("刷新")}</a>
									</div>
								</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" onsubmit="return false;">
											<div class="input-append">
												<input class="input-small js-modal-search-input" value="{pigcms{$keyword}" type="text" style="border-radius:4px 0px 0px 4px;"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" style="border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
											</div>
										</form>
									</div>
								</th>
							</tr>
						</thead>
						<!-- {pigcms{:L_("表格数据区")} -->
						<tbody>
							<volist name="store_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">
											<a target="_blank" class="new_window" href="javascript:viod(0);">{pigcms{$vo.name}</a>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
                                            <if condition="$vo['is_choose']">
                                                <button class="btn disabled" data-id="{pigcms{$vo.store_id}"   data-name="{pigcms{$vo.name}"  data-image="{pigcms{$vo.image}"  data-url="{pigcms{$vo.url}">{pigcms{:L_("已选")}</button>
                                                <else/>
                                                <button class="btn js-choose" data-id="{pigcms{$vo.store_id}"  data-name="{pigcms{$vo.name}"  data-image="{pigcms{$vo.image}"  data-url="{pigcms{$vo.url}"  >{pigcms{:L_("选取")}</button>
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
			<div <if condition="!$is_choose">style="display:none;"</if> class="js-confirm-choose left">
				<input type="button" class="btn btn-primary" value="{pigcms{:L_("确定使用")}">
			</div>
			<div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$pagebar}</div>
		</div>
<?php if(empty($_GET['not_first'])){ ?>
	</body>
</html>
<?php } ?>