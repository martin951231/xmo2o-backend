<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("商品列表")}</title>
    <link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript">
        $(function () {
            //设置高度
            $('.js-modal iframe', parent.document).height($('body').height() + 16);

            //关闭
            $('.modal-header .close').live("click", function () {
                // var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
				// parent.layer.close(index); //再执行关闭
				parent.layer.close()
            });

            //分页
            $('.js-page-list a').live('click', function (e) {
                if (!$(this).hasClass('active')) {
                    var input_val = $('.js-modal-search-input').val();
                    var page = $(this).data('page-num');
                    search(input_val, page);
                }
            });
			
			$('#first_sort_id').change(function(){
				$('#second_sort_id_value').val('0');
				$('#third_sort_id_value').val('0');
				$('#second_sort_id, #third_sort_id').empty().hide();
				
				var sort_id = $(this).val();
				if(sort_id != 0){
					selectSencondCat(sort_id);
				}else{
					$('#sort_id').val(sort_id);
				}
			});
			
			$('#second_sort_id').change(function(){
				$('#third_sort_id').empty().hide();
				
				var sort_id = $(this).val();
				if(sort_id != 0){
					selectThirdCat(sort_id);
				}else{
					$('#sort_id').val(sort_id);
				}
			});
			
			$('#third_sort_id').change(function(){
				var sort_id = $(this).val();
				$('#sort_id').val(sort_id);
			});
			
			if($('#first_sort_id_value').val() != 0){
				selectSencondCat($('#first_sort_id_value').val());
			}
        });
		
		//二级分类
		function selectSencondCat(sort_id){
			console.log('sort_id', sort_id);
			$.post("{pigcms{:U('shop_goods_group_sort')}", {store_id: {pigcms{$_GET.store_id}, sort_id: sort_id}, function(result){
				var html = '';
				if(result.info.length > 0){
					for(var i in result.info){
						html+= '<option value="' + result.info[i].sort_id + '" ' + (result.info[i].sort_id == $('#second_sort_id_value').val() ? selected="selected" : '') + '>' + result.info[i].sort_name + '</option>';
					}
					$('#second_sort_id').html(html).show();
					selectThirdCat($('#second_sort_id').val());
				}else{
					$('#sort_id').val(sort_id);
					$('#second_sort_id').empty().hide();
				}
			});
		}
		//三级分类
		function selectThirdCat(sort_id){
			console.log('sort_id', sort_id);
			$.post("{pigcms{:U('shop_goods_group_sort')}", {store_id: {pigcms{$_GET.store_id}, sort_id: sort_id}, function(result){
				var html = '';
				if(result.info.length > 0){
					for(var i in result.info){
						html+= '<option value="' + result.info[i].sort_id + '" ' + (result.info[i].sort_id == $('#third_sort_id_value').val() ? selected="selected" : '') + '>' + result.info[i].sort_name + '</option>';
					}
					$('#third_sort_id').html(html).show();
					$('#sort_id').val(result.info[0].sort_id);
				}else{
					$('#sort_id').val(sort_id);
					$('#third_sort_id').empty().hide();
				}
			});
		}
		
		//全选
		function selected_all_func(){
			console.log($('#selected_all').prop('checked'));
			if($('#selected_all').prop('checked')){
				$('.goods_checkbox').prop('checked', false);
			}else{
				$('.goods_checkbox').prop('checked', true);
			}
			$('#selected_all').prop('checked', !$('#selected_all').prop('checked'));
		}

        /*搜索*/
        function search(val, page) {
            page = page || 1;
            $('body').html('<div class="loading-more"><span></span></div>');
            location.href  = '{pigcms{:U("shop_goods_group_op_list",$_GET)}&keyword=' + val + '&p=' + page;
        }

        function subform() {
            var store_id = {pigcms{$_GET.store_id};
            var group_id = {pigcms{$_GET.group_id};
            var chenked = $("input[type='checkbox']:checked").val([]);

            if (chenked.length == 0) {
                alert('{pigcms{:L_("请选择商品")}');
                return false;
            }

            var goods_ids = "";
            for (var i = 0; i < chenked.length; i++) {
				if(chenked[i].value){
					if (i == chenked.length - 1) {
						goods_ids += chenked[i].value;
					} else {
						goods_ids += chenked[i].value + ",";
					}
				}
            }

            $.post("{pigcms{:U('Shop/shop_goods_group_checked')}", {
                store_id: store_id,
                group_id: group_id,
                goods_ids: goods_ids
            }, function (res) {
                if (res.code == 0) {
                    // var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
					// parent.layer.close(index); //再执行关闭
					parent.layer.close(true)
                    // location.reload(true); // 刷新
                } else {
                    alert(res.msg);
                }
            }, 'json')
        }
    </script>
    <style type="text/css">
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body style="background-color:#ffffff;">
	<div style="margin:15px 15px 0 15px;">
		<form class="form-search" action="{pigcms{:U('shop_goods_group_op_list')}">
			<input type="hidden" name="c" value="Shop" />
			<input type="hidden" name="a" value="shop_goods_group_op_list" />
			<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}" />
			<input type="hidden" name="group_id" value="{pigcms{$_GET.group_id}" />
			<input type="hidden" name="sort_id" id="sort_id" value="{pigcms{$_GET.sort_id}" />
			<input type="hidden" id="first_sort_id_value" value="{pigcms{$_GET.first_sort_id}" />
			<input type="hidden" id="second_sort_id_value" value="{pigcms{$_GET.second_sort_id}" />
			<input type="hidden" id="third_sort_id_value" value="{pigcms{$_GET.third_sort_id}" />
			
			<font color="#000">{pigcms{:L_("商品分类")}：</font>
			<select id="first_sort_id" name="first_sort_id">
				<option value="0">全部</option>
				<volist name="sort_list" id="vo">
					<option value="{pigcms{$vo.sort_id}" <if condition="$_GET['first_sort_id'] eq $vo['sort_id']">selected="selected"</if>>{pigcms{$vo.sort_name}</option>
				</volist>
			</select>
			<select id="second_sort_id" name="second_sort_id" style="display:none;"></select>
			<select id="third_sort_id" name="third_sort_id" style="display:none;"></select>
			<br/>
			<br/>
			{pigcms{:L_("商品名称")}: <input type="text" name="keyword" class="input-text js-modal-search-input" value="{pigcms{$_GET.keyword}"/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font color="#000">{pigcms{:L_("商品类别")}：</font>
			<select name="type">
				<option value="0" <if condition="$_GET['type'] eq 0">selected="selected"</if>>{pigcms{:L_("通用")}</option>
				<option value="1" <if condition="$_GET['type'] eq 1">selected="selected"</if>>{pigcms{:L_("只属于商城")}</option>
			</select>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font color="#000">{pigcms{:L_("上下架状态")}：</font>
			<select name="status">
				<option value="-1" <if condition="!isset($_GET['status']) || $_GET['status'] eq -1">selected="selected"</if>>{pigcms{:L_("全部")}</option>
				<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>{pigcms{:L_("正常")}</option>
				<option value="0" <if condition="isset($_GET['status']) && $_GET['status'] eq 0">selected="selected"</if>>{pigcms{:L_("停售")}</option>
			</select>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font color="#000">{pigcms{:L_("商品分组")}：</font>
			<select name="select_group_id">
				<option value="0">全部</option>
				<volist name="goods_group_list" id="vo">
					<option value="{pigcms{$vo.id}" <if condition="$_GET['select_group_id'] eq $vo['id']">selected="selected"</if>>{pigcms{$vo.name}</option>
				</volist>
			</select>
			<input type="submit" value="{pigcms{:L_('搜索')}" class="button" />
		</form>
	</div>
	<div class="modal-body">
		<div class="tab-content">
			<div id="js-module-feature" class="tab-pane module-feature active">
				<table class="table">
					<colgroup>
						<col class="modal-col-title">
						<col class="modal-col-time">
						<col class="modal-col-time">
						<col class="modal-col-time">
						<col class="modal-col-time">
						<col class="modal-col-action">
					</colgroup>
					<!-- 表格头部 -->
					<thead>
					<tr>
						<th class="title" style="background-color:#f5f5f5;">
							<div class="td-cont">
								<span>{pigcms{:L_("商品名称")}</span>
								<a class="js-update" href="javascript:window.location.reload();">{pigcms{:L_("刷新")}</a>
							</div>
						</th>
						<th class="time" style="background-color:#f5f5f5;">
							<div class="td-cont">
								<span>{pigcms{:L_("分类名称")}</span>
							</div>
						</th>
						<th class="time" style="background-color:#f5f5f5;">
							<div class="td-cont">
								<span>{pigcms{:L_("商城类别")}</span>
							</div>
						</th>
						<th class="time" style="background-color:#f5f5f5;">
							<div class="td-cont">
								<span>{pigcms{:L_("商品状态")}</span>
							</div>
						</th>
						<th class="time" style="background-color:#f5f5f5;">
							<div class="td-cont">
								<span>{pigcms{:L_("库存")}</span>
							</div>
						</th>
						<th class="opts" style="background-color:#f5f5f5;">
							<div class="td-cont">
								<span>{pigcms{:L_("选择")}</span>
							</div
						</th>
					</tr>
					</thead>
					<!-- 表格数据区 -->
					<tbody>
					<volist name="goods_list" id="vo">
						<tr>
							<td class="title" style="max-width:300px;">
								<div class="td-cont">
									<a href="javascript:">{pigcms{$vo.name}</a>
								</div>
							</td>
							<td class="time">
								<div class="td-cont">
									<span>{pigcms{$vo.sort_name}</span>
								</div>
							</td>
							<td class="time">
								<div class="td-cont">
									<span><if condition="$vo['goods_type']">{pigcms{:L_("只属于商城")}<else/>{pigcms{:L_("通用")}</if></span>
								</div>
							</td>
							<td class="time">
								<div class="td-cont">
									<if condition="$vo['status']">
										<span>{pigcms{:L_("正常")}</span>
									<else/>
										<span style="color:red;">{pigcms{:L_("停售")}</span>
									</if>
								</div>
							</td>
							<td class="time">
								<div class="td-cont">
									<span><if condition="$vo['stock_num'] neq -1">{pigcms{$vo.stock_num}<else/>{pigcms{:L_("无限")}</if></span>
								</div>
							</td>
							<td class="title" style="max-width:300px;padding-left:80px;">
								<div class="td-cont">
									<if condition="$vo['is_group']">
										<span><input type="checkbox" value="" checked="checked" disabled="disabled"/></span>
									<else/>
										<span><input type="checkbox" class="goods_checkbox" name="goods_ids[]" value="{pigcms{$vo.goods_id}"/></span>
									</if>
								</div>
							</td>
						</tr>
					</volist>
					</tbody>
				</table>
				{pigcms{$page_bar}
				<div style="position:fixed;bottom:0px;left:0;width:100%;padding:15px 0;background:white;border-top:1px solid #ccc;z-index: 9999;">
					<button class="btn btn-info" onclick="selected_all_func()" style="float:left;margin-left:20px;">
						<input type="checkbox" id="selected_all" value="" style="pointer-events:none;"/>
						{pigcms{:L_("全部选中")}
					</button>
					<button class="btn btn-info" onclick="subform()" style="float:right;margin-right:40px;">
						{pigcms{:L_("保存")}
					</button>
				</div>
			</div>
		</div>
	</div>
</body>
</html>