<include file="Public:header"/>
	<style type="text/css">
		.tag {
			background: #fff;
		    padding: 4px;
		    border: 1px solid;
		    border-radius: 4px;
		    margin: 1px 2px 1px 2px;
		}
		.tag span{
			margin-right: 4px;
		}
		.tag a{
			color: #f13737;
		    border: 1px solid;
		    padding: 0px 3px;
		    border-radius: 50%;
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Live/edit_live_category')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" value="{pigcms{$info.id}" name="id">
			<tr>
				<th width="80">分类名称</th>
				<td><input type="text" class="input fl" name="name" size="75" value="{pigcms{$info['name']}" placeholder="分类名称" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr>
				<th width="80">类别</th>
				<td>
					<select name="type" id="type">
						<option value="1" <if condition="$info['type'] eq 1">selected</if>>直播</option>
						<option value="2" <if condition="$info['type'] eq 2">selected</if>>短视频</option>
					</select>
				</td>
			</tr>
			<tr class="live_show_category" <if condition="$info['type'] eq 2">style="display:none;"</if>>
				<th width="80">选择直播分类</th>
				<td>
					<button type="button" id="select_live_show_category" data-store_str=''>选择</button>
					<if condition="$categorys and ($info['type'] eq 1)">
						<p class="tag_box">
							<volist name="categorys" id="vo">
								<span class="tag tag_1" data-id="{pigcms{$vo.id}" data-k="{pigcms{$key}">
									<span>{pigcms{$vo.name}</span>
									<a class="del">X</a>
									<input type="hidden" value="{pigcms{$vo.id}" name="categorys[]">
								</span>
							</volist>
						</p>
					</if>
				</td>
			</tr>
			<tr class="live_video_category"  <if condition="$info['type'] eq 1">style="display:none;"</if>>
				<th width="80">选择短视频话题</th>
				<td>
					<button type="button" id="select_live_video_category" data-store_str=''>选择</button>
					<if condition="$categorys and ($info['type'] eq 2)">
						<p class="tag_box">
							<volist name="categorys" id="vo">
								<span class="tag tag_1" data-id="{pigcms{$vo.id}" data-k="{pigcms{$key}">
									<span>{pigcms{$vo.name}</span>
									<a class="del">X</a>
									<input type="hidden" value="{pigcms{$vo.id}" name="categorys[]">
								</span>
							</volist>
						</p>
					</if>
				</td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" value="{pigcms{$info['sort']}" size="10" placeholder="排序" validate="maxlength:20,required:true,digits:true" /></td>
			</tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable preference_btn"><label class="cb-enable <if condition="$info['status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status" value="1"  <if condition="$info['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable preference_btn"><label class="cb-disable <if condition="$info['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$info['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$('#type').change(function (argument) {
			if($(this).val() == 1){
				$('.live_show_category').show()
				$('.live_video_category').hide()
			}else{
				$('.live_video_category').show()
				$('.live_show_category').hide()
			}
			$('.tag_box').remove();
		})

		$('#select_live_video_category').click(function(){
			var obj=$(this);
			art.dialog.data('dom',obj);
			art.dialog.open("{pigcms{:U('select_live_video_category')}",{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'select_store',
				title:'选择短视频话题',
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
		$('#select_live_show_category').click(function(){
			var obj=$(this);
			art.dialog.data('dom',obj);
			art.dialog.open("{pigcms{:U('select_live_show_category')}",{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'select_store',
				title:'选择直播分类',
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
	</script>
<include file="Public:footer"/>