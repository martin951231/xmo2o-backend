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
			var page = 1;
			$(function(){
				$('.js-modal iframe',parent.document).height($('body').height()+16);
				$('.modal-header .close').live('click',function(){
					if(pageForm == 'shop_fitment'){
						var index = parent.layer.getFrameIndex(window.name); //"先得到当前iframe层的索引")}
						parent.layer.close(index); //"再执行关闭")}
					}else{
						parent.login_box_close();
					}
				});
				$('button.js-choose').live('click',function(){
					<?php if($_GET['type'] == 'more'){ ?>
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
					<?php }else{ ?>
						parent.login_box_after('{pigcms{$_GET.number}','good_group',$(this).data('title'),'{pigcms{$config.site_url}/wap.php?c=Mall&a=detail&goods_id='+$(this).data('id'));
					<?php } ?>
				});
				$('.js-page-list a').live('click',function(e){
					if(!$(this).hasClass('active')){
						var input_val = $('.js-modal-search-input').val();
						page = $(this).data('page-num');
						$('body').html('<div class="loading-more"><span></span></div>');
						$('body').load("{pigcms{:U('good_group',array('type'=>$_GET['type'],'selecteditems'=>$_GET['selecteditems'],'store_id'=>$now_store['store_id'],'shop_type'=>$_GET['shop_type'],'number'=>$_GET['number'],'not_first'=>1))}",{page:$(this).data('page-num'),'keyword':input_val},function(){
							$('.js-modal iframe',parent.document).height($('body').height());
						});
					}
				});
				$('.js-modal-search').live('click',function(e){
					var input_val = $('.js-modal-search-input').val();
					$('body').html('<div class="loading-more"><span></span></div>');
					$('body').load("{pigcms{:U('good_group',array('type'=>$_GET['type'],'selecteditems'=>$_GET['selecteditems'],'store_id'=>$now_store['store_id'],'shop_type'=>$_GET['shop_type'],'number'=>$_GET['number'],'not_first'=>1))}",{'keyword':input_val},function(){
						$('.js-modal iframe',parent.document).height($('body').height());
					});
					return false;
				});
				$('.js-confirm-choose').live('click',function(){
					var data_arr = [];
					var goods_ids = [];

                    $.post("{pigcms{:U('good_group_detail')}", {store_id: "{pigcms{$_GET['store_id']}", selecteditems: "{pigcms{$_GET['selecteditems']}", shop_type: "{pigcms{$_GET['shop_type']}", keyword: "{pigcms{$_GET['keyword']}",'page': page}, function (res) {
                        var goods_list =  res;
                        var only = '{pigcms{$only}';
                        console.log('only', only);
                        console.log('pageForm', pageForm);

                        console.log('goods_list', goods_list);
                        $.each($('.js-choose.btn-primary'),function(i,item){
                            id = $(item).data('id');
                            var goods = goods_list[id];
                            if (only==1) {
                                data_arr.push( goods['group_info'] );
                            } else {
                                console.log('goods', goods);
                                if (goods.goods_ids.length>0) {
                                    for (var j = 0; j < goods.goods_ids.length; j++) {
                                        var goods_detail = {'id':goods.goods_ids[j],'title':goods.title[j],'image':goods.image[j],'price':goods.price[j],'url':'{pigcms{$config.site_url}/wap.php?c=Mall&a=detail&goods_id='+goods.goods_ids[j]};
                                        var result = $.inArray(goods.goods_ids[j], goods_ids);
                                        if (result==-1) {
                                            goods_ids.push(goods.goods_ids[j]);
                                            data_arr.push( goods_detail );
                                        }
                                    }
                                }
                            }
                        });
                        if(pageForm == 'shop_fitment'){
                            var selected = '{pigcms{$_GET.selecteditems}';
                            selectedArr = selected.split(',');
                            var selectedAllNum = selectedArr.length + data_arr.length;
                            if(max_num > 0 && selectedAllNum > max_num){
                                parent.layer.msg('{pigcms{:L_("最多仅能添加")}'+max_num+'{pigcms{:L_("个商品。")}<br/>{pigcms{:L_("您现在已经添加了")} ' + selectedAllNum + ' {pigcms{:L_("个，请先取消再进行操作。")}');
                                return false;
                            }
                            console.log(data_arr);
                            parent.frames[parent.subject_win_name].build_good_save(data_arr);
                            $('.modal-header .close').trigger('click');
                        }else{
                            parent.widget_box_after('{pigcms{$_GET.number}',data_arr);
                        }


                    }, 'json');
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
				<li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("商品分组")}</a> 
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
							<volist name="goods_group_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">
											<a target="_blank" class="new_window" href="javascript:viod(0);">{pigcms{$vo.name}</a>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
                                            <if condition="$vo['is_choose']">
                                                <button class="btn" data-id="{pigcms{$vo.id}"  >{pigcms{:L_("取消")}</button>
                                                <else/>
                                                <button class="btn js-choose" data-id="{pigcms{$vo.id}"  >{pigcms{:L_("选取")}</button>
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
			<div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$page_bar}</div>
		</div>
<?php if(empty($_GET['not_first'])){ ?>
	</body>
</html>
<?php } ?>