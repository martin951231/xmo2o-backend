<include file="Public:header"/>
<style type="text/css">
.tips{
	color: red;
}
</style>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="90">抓取时间段</th>
			<td>
				<input type="text" class="input" name="sdate" value="" placeholder='开始时间' onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',maxDate:'{pigcms{$now_time|date="Y-m-d H:i",###}'})"/>
				——
				<input type="text" class="input" name="fdate" value="" placeholder='结束时间' onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',maxDate:'{pigcms{$now_time|date="Y-m-d H:i",###}'})"/>
				<p class="tips">
					该时间段为店铺商品最新的编辑时间的时间段，只可选择当前时间之前，不可选择当前时间之后
				</p>
			</td>
		</tr>
		<tr>
			<th width="90">抓取指定店铺</th>
			<td>
				<button type="button" id="select_store" data-store_str=''>选择店铺</button>
				<p class="tips">抓取店铺的商品会自动显示在平台商品中一个以店铺名命名的新分类下</p>
			</td>
		</tr>
	</table>
	<div class="btn hidden">
		<input type="submit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<style type="text/css">
	.tag_box .tag{
		display: inline-block;
		padding: 2px 2px;
		margin: 0px 5px 5px 0;
		border: 1px solid #ccc;
	}
	.tag_box .tag .del{
		display: inline-block;
		padding: 0 4px;
		color: #277fc1;
		font-weight: bold;
		cursor: pointer;
	}
</style>
<script type="text/javascript">
$(function(){
	$('#select_store').click(function(){
		var obj=$(this);
		art.dialog.data('dom',obj);
		art.dialog.open("{pigcms{:U('Shop/store_list')}",{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.o2oTop.art.dialog.data('iframe_handle',iframe);
			},
			id: 'select_store',
			title:'选择店铺',
			padding: 0,
			width: 600,
			height: 500,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4',
			close:function(){
				var json=[];
				$('.tag_box').find('.tag').each(function(){
					json.push($(this));
				});
				json.sort(function(b,a){
					if(a.data('k')>b.data('k')){
						return -1;
					}else{
						return 1;
					}
				});
				$('.tag_box').empty();
				$.each(json,function(n,v){
					$('.tag_box').append(v);
				})
			}
		});
	});
	$(document).on('click','.tag_box .tag .del',function(){
		$(this).parent().remove();
	});
	var capture_url="{pigcms{:U('Shop/capture_page')}";
	$('#dosubmit').click(function(){
		if(!$('.tag_box').length || !$('.tag_box').find('.tag').length){
			layer.alert('请先选择抓取商品的店铺！');
			return false;
		}
		var sdate=$('input[name=sdate]').val();
		var fdate=$('input[name=fdate]').val();
		if(sdate && fdate){
			var stime=Date.parse(sdate);
			var ftime=Date.parse(fdate);
			if(stime>ftime){
				layer.alert('抓取店铺商品的开始时间不能大于结束时间');
				return false;
			}
		}
		var store_arr=[];
		$('.tag_box').find('.tag').each(function(){
			store_arr.push($(this).data('store_id'));
		});
		var store_str=store_arr.join(',');
		capture_url+='&store_str='+store_str+'&sdate='+sdate+'&fdate='+fdate;
		capture_page(capture_url);
		return false;
	});
	function capture_page(url){
		art.dialog.open(url, {
	        init: function(){
	            var iframe = this.iframe.contentWindow;
	            window.o2oTop.art.dialog.data('iframe_handle', iframe);
	        },
	        id: null,
	        title: '',
	        padding: 0,
	        width: 600,
	        height: 600,
	        lock: true,
	        resize: false,
	        background: 'black',
	        button: null,
	        cancel:false,
	        fixed: false,
	        close: false,
	        left: '50%',
			top: '38.2%',
			opacity:'0.4',
			close:function(){
				console.log(123);
			}
	    });
	}
});
</script>
<include file="Public:footer"/>