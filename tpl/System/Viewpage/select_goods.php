<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("微页面")}</title>
		<link href="/tpl/Merchant/default/static/diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				
			});
		</script>
        <style type="text/css">
            #loading{
                z-index:99;
                padding:5px 0 5px 29px;
                background:url(tpl/System/Static/css/img/loading.gif) no-repeat 10px top;left:0;top:0;width:90px;
                position:fixed;
                margin-left: 400px;
                margin-top: 150px;
                height:21px
            }
        </style>
	</head>
	<body style="background-color:#ffffff;">
    <div id="loading"></div>
		<div class="modal-body">
			<div class="td-cont " style="padding:0px 0 10px 0px;">
				<form class="form-search" method="get" name="formsearch">
		            <select class="" id='mer' name="mer_id" style=" border: 1px solid #cccccc;height: 28px;border-radius: 2px;">
		                <option value="">全部商家</option>
		                <volist name='mer_list' id='vo'>
		                    <option value="{pigcms{$key}" <if condition="$mer_store_sel['mer_id'] eq $key">selected</if>>{pigcms{$vo}</option>
		                </volist>
		            </select>
		            <select class="" id="store" name="store_id" style=" border: 1px solid #cccccc;height: 28px;border-radius: 2px;">
		                <if condition="$mer_store_sel">
		                    <volist name="store_list" id="vv">
		                        <option value="{pigcms{$vv['store_id']}" <if condition="$mer_store_sel['store_id'] eq $vv['store_id']">selected</if>>{pigcms{$vv['name']}</option>
		                    </volist>
		                   <else />
		                    <option value="">全部店铺</option>
		                </if>
		            </select>
		            <input type="hidden" name="c" value="Viewpage" />
					<input type="hidden" name="a" value="select_goods" />
					<input type="hidden" name="selected" value="{pigcms{$selected}" />
					<input type="hidden" name="page" value="{pigcms{$page}" />

					<input class="input-small " name="keyword" value="{pigcms{$keyword}" type="text" style=" border: 1px solid #cccccc;height: 24px;border-radius: 2px;width: 200px;" placeholder="请输入商品名" />
					<a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" onclick="formsearch.submit();" style="margin-left:0px;">{pigcms{:L_("搜索")}</a>
					
		            <!-- <input type="submit" value="查询" class="button" style="margin-left: 30px;"/> -->
		        </form>
			</div>	
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
										<span>{pigcms{:L_("标题")}</span>
									</div>
								</th>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("店铺")}</span>
									</div>
								</th>
								<th class="time" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("最后修改时间")}</span>
									</div>
								</th>
								<th  width="95px" class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("选择商品")}</span>
									</div>
								</th>
							</tr>
						</thead>
						<!-- {pigcms{:L_("表格数据区")} -->
						<tbody>
							<volist name="good_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.name}</div>
									</td>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.store_name}</div>
									</td>
									<td class="time">
										<div class="td-cont">
											<span>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</span>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
											<eq name='vo.select' value='1'>
												<button class="btn js-choose2" disabled="true">{pigcms{:L_("已选择")}</button>
											<else/>
												<button class="btn js-choose2 selectThis" data-goods_id='{pigcms{$vo.goods_id}' data-type='{pigcms{$type}'>{pigcms{:L_("选取")}</button>
											</eq>
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
	<script type="text/javascript">
		function redirectUrl(t){
			var selected = $.trim($("[name='selected']").val());
			var href = $(t).attr('href');
			$(t).attr('href', href+"&selected="+selected);
			return true;
		}
		function in_array(search,array){
		    for(var i in array){
		        if(array[i]==search){
		            return true;
		        }
		    }
		    return false;
		}
		$(function(){
			$('.js-page-list a').live('click',function(e){
				if(!$(this).hasClass('active')){
					$('[name="page"]').val($(this).data('page-num'));
					$(".js-modal-search").trigger('click');
				}
			});

			$(".selectThis").bind("click", function(){
				var selected = $.trim($('[name="selected"]').val());
				var str = $(this).data('goods_id');
				var selected_arr = selected.split(",");
				if(!in_array(str, selected_arr)){
					selected_arr.push(str);
				}
				$('[name="selected"]').val(selected_arr.join(','));
				$(this).removeClass('selectThis').attr("disabled",true).text('{pigcms{:L_("已选择")}');
			})
		})
	</script>
    <script>
        $(function(){
            //初始化数据
            var url = '{pigcms{:U("getStoreList")}'; //后台地址
            $("#mer").change(function(){ //监听下拉列表的change事件
                var mer_id = $(this).val(); //获取下拉列表选中的值
                console.log(mer_id)
                if(mer_id != '') {
                    //发送一个post请求
                    $.ajax({
                        type:'post',
                        url:url,
                        data:{mer_id:mer_id},
                        dataType:'json',
                        success:function(data){ //请求成功回调函数
                            var status = data.status; //获取返回值
                            var store_list = data.data;
                            if(status == 200){ //判断状态码，200为成功
                                var option = '';
                                for(var i=0;i<store_list.length;i++){ //循环获取返回值，并组装成html代码
                                    option +="<option value='" + store_list[i]['store_id'] + "'>"+store_list[i]['name']+'</option>';
                                }
                            }else{
                                var option = '<option value="">全部店铺</option>'; //默认值
                            }
                            $("#store").html(option); //js刷新第二个下拉框的值
                        },
                    });
                } else {
                    $('[name="store_id"]').empty();
                    $('[name="store_id"]').append('<option value="" >全部店铺</option>');
                }

            });
        });
    </script>
    <script type="text/javascript">
        $("#loading").fadeOut(2000)
    </script>
</html>