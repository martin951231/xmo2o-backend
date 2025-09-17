<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Lang/index')}" class="on">语言列表</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td style="width:60%;">
						<form action="{pigcms{:U('Lang/index')}" method="get" name="myForm" style="width: 500px; float: left;">
							<input type="hidden" name="c" value="Lang"/>
                    		<input type="hidden" name="a" value="index"/>
							筛选: 
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="模糊匹配" />
							端口: <select name="from">
								<option value="-1">所有端口</option>
								<volist name="from" id="vo">
								<option value="{pigcms{$key}" <if condition="isset($_GET['from']) && $_GET['from'] eq $key">selected="selected"</if>>{pigcms{$vo}</option>
								</volist>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
						<div style="width: 500px; float: right; margin-right: 100px;">
							<div style="width: 100px; float: left;"><a href="{pigcms{:U('Lang/download_translate')}" class="button">导出</a></div>
							<div style="width: 400px; float: left;">
							<input type="file" id="translate_file" data-name="translate" />
							</div>
						</div>
					</td>
				</tr>
			</table>
			
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th width="60">编号</th>
								<th width="300">目标词语</th>
								<volist name="service_lang" id="service_l">
								<th width="350">{pigcms{$service_l.display}</th>
								</volist>
								<th width="250" class="textcenter">所属板块</th>
								<th width="250" class="textcenter">上报时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($data)">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.key}<a href="{pigcms{:U('Lang/del',array('id'=>$vo['id']))}" style="color:red;" onclick="delThis(this); return false;">[删]</a></td>
										<volist name="service_lang" id="service_l">
										<td class="clickChange {pigcms{$service_l.val}_{pigcms{$vo.id}" data-lang="{pigcms{$service_l.val}" data-id="{pigcms{$vo.id}">
											<notempty name="vo[$service_l['val']]">{pigcms{$vo[$service_l['val']]}<else /><span style="color:#ccc;">点击此处翻译</span></notempty>
										</td>
										</volist>
										<td class="textcenter">{pigcms{$vo.module}</td>
										<td class="textcenter">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="<php> echo count($service_lang)+3;</php>">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="<php> echo count($service_lang)+3;</php>">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
		
		</div>
		<div id="sames_words" class="table-list" style="display: none;">
			<table width="100%" cellspacing="0" id="sames_words_table">
			</table>
		</div>
		<style type="text/css">
			#sames_words_table td{
				text-align: center;
			}
		</style>
		<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/fileinput.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/locales/zh.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}bootstrap-fileinput-master/css/fileinput.css" />
<script type="text/javascript">
	initUpload_config("translate_file");
	function initUpload_config(ctrlId){
		$("#"+ctrlId).fileinput({
			uploadUrl : '{pigcms{:U("translate_import_upload")}',
			language: 'zh', //设置语言
			showBrowse : true,
			showPreview : false,
			showRemove : false,
			showUpload : true,
			showCancel : false,
			showClose : true,
			showCaption : true,
			dropZoneEnabled : false,
			allowedFileExtensions : ['xls']
		}).on("fileuploaded", function (event, data, previewId, index) {
	        if(data.response.status == 1)
	        {
	            layer.msg(data.files[index].name + data.response.info,{offset:'300px'});
	        	//关闭
	            $(".close").click();
	        }
	        else{
	            layer.msg(data.files[index].name + "{pigcms{:L_("导入失败")} " + data.response.info, {icon: 5,offset:'300px'});
		        //重置
		        $("#"+ctrlId).fileinput("clear");
		        $("#"+ctrlId).fileinput("reset");
		        $('#'+ctrlId).fileinput('refresh');
		        $('#'+ctrlId).fileinput('enable');
		        $(".close").click();
	        }
	    });
	}
	var _click = null;
	var _ajax = false;
	function ajaxUpdate(t){
		var _this = $(t);
		var _parent = _this.parent();
		var _val = $.trim(_this.val());
		var _id = _this.data('id');
		var _field = _this.data('lang');
		_ajax = true;
		$.post("{pigcms{:U('update')}",{id:_id,field:_field,val:_val},function(data){
			if(data.status == 1){
				if(_val == ''){
					_val = '<span style="color:#ccc;">点击此处翻译</span>';
					_parent.removeClass('edit').html(_val);
				}
				else{
					_parent.removeClass('edit').html(htmlEscape(_val));
				}				
				_ajax = false;
			}
			else if(data.status == 2){
				//参数不规范
				layer.tips(data.msg, t, {time:5000});
				_ajax = false;
				return ;
			}
			else if(data.status == 4 && data.words.length > 0){
				//多个雷同词语
				$("#sames_words_table").empty();
				$("#sames_words_table").append('<tr>'+
							'<th>选中</th>'+
							'<th>词语</th>'+
							'<th>当前翻译</th>'+
							'<th>所属板块</th>'+
						'</tr>');
				for(var i = 0;i< data.words.length; i++){
					$("#sames_words_table").append('<tr>'+
							'<td><input type="checkbox" name="sames[]" checked value="'+data.words[i].id+'"></td>'+
							'<td>'+data.words[i].key+'</td>'+
							'<td>'+data.words[i].val+'</td>'+
							'<td>'+data.words[i].module+'</td>'+
						'</tr>');
				}
				layer.open({
				  type: 1,
				  title: data.msg,
				  closeBtn: 0,
				  area: '516px',
				  offset: _parent.offset().top+"px",
				  btn : ['替换选中','不替换了'],
				  yes : function(index){
				  	//获取用户选中
				  	var _checked = [];
				  	$("#sames_words_table").find(":checked").each(function(){
				  		_checked.push($(this).val());
				  	});
				  	if(_checked.length == 0){
				  		alert("至少需要选中一条记录！");
				  		return false;
				  	}
				  	$.post("{pigcms{:U('update_batch')}", {ids:_checked,field:_field,val:_val}, function(r){
				  		if(r.status == 1){
					  		for(var k = 0;k < _checked.length; k++){
					  			if(typeof $("."+_field+"_"+_checked[k])[0] != 'undefined'){
					  				$("."+_field+"_"+_checked[k]).html(_val);
					  			}
					  		}
					  		_parent.removeClass('edit').html(htmlEscape(_val));
					  		layer.close(index);	
					  	}
					  	else{
					  		alert(r.msg);
					  	}
					  	_ajax = false;
				  	},'json');
				  	
				  },
				  btn2 : function(index){
				  	_parent.removeClass('edit').html(htmlEscape(_val));
					_ajax = false;
				  	layer.close(index);
				  },
				  content: $('#sames_words')
				});
				_ajax = false;
				return ;
			}
			else{
				//修改失败
				layer.tips(data.msg, t);
				_ajax = false;
				return ;
			}
		},'json')
	}
	$(function(){
		$(".clickChange").bind("click",function(){
			if(_ajax){
				return false;
			}
			var _this = $(this);
			if(_click && isObjectValueEqual(_this,_click) && _click.html().indexOf("<input") !== -1) return false;
			if(_click){
				if(_click.html().indexOf("<input") !== -1){
					var __v = $.trim(_click.find("input").val());
					if(__v == ''){
						__v = '<span style="color:#ccc;">点击此处翻译</span>';
						_click.removeClass('edit').html(__v);
					}
					else{
						_click.removeClass('edit').html(htmlEscape(__v));
					}
				}
			}
			_click = _this;
			if(_this.hasClass('edit')) return ;
			var _val = $.trim(_this.text());
			if(_this.html().indexOf("<span") !== -1){
				_val = "";
			}
			var _field = _this.data('lang');
			var _id = _this.data('id');
			_this.addClass('edit');
			_this.html('<input type="text" class="input-text" style="width:100%;" onChange="ajaxUpdate(this);" data-lang="'+_field+'" data-id="'+_id+'" value="'+htmlEscape(_val)+'" />');
			_this.find(":input").focus();
		});

	})

	function delThis(t){
		if(confirm('你确定要删除该词语吗？')){
			var _this = $(t);
			$.post(_this.attr("href"),{},function(data){
				if(data.status == 1){
					_this.parents('tr').remove();
				}
			},'json')
		}
	}
	function htmlEscape(text){ 
	  return text.replace(/[<>"&\\]/g, function(match, pos, originalText){
	    switch(match){
	    case "<": return "&lt;"; 
	    case ">":return "&gt;";
	    case "&":return "&amp;"; 
	    case "\"":return "&quot;";
	  } 
	  }); 
	}

	function isObjectValueEqual(a, b) {
	    // Of course, we can do it use for in 
	    // Create arrays of property names
	    var aProps = Object.getOwnPropertyNames(a);
	    var bProps = Object.getOwnPropertyNames(b);
	 
	    // If number of properties is different,
	    // objects are not equivalent
	    if (aProps.length != bProps.length) {
	        return false;
	    }
	 
	    for (var i = 0; i < aProps.length; i++) {
	        var propName = aProps[i];
	 
	        // If values of same property are not equal,
	        // objects are not equivalent
	        if (a[propName] !== b[propName]) {
	            return false;
	        }
	    }
	 
	    // If we made it this far, objects
	    // are considered equivalent
	    return true;
	}
</script>
<include file="Public:footer"/>