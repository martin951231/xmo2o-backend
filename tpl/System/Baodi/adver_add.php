<include file="Public:header"/>
<style type="text/css">
.color-box{
    float: left;
    width: 30px;
    height: 30px;
    margin: 5px;
    border: 1px solid white;
    background-color: #ff0080;
}
.show_box{
	display:none;
}
.mark_box{
	display:block;
	position: relative;
}
.mark_box img{
	width:36px;
	height: 36px;
}
.mark_box i.mark{
	display: block;
    position: absolute;
    line-height: 16px;
    top: -6px;
    background: #f00;
    color: #fff;
    padding: 0 5px;
    left: 18px;
    border-radius: 10px;
    border-bottom-left-radius: 0;
    font-size: 11px;
}
.effect_date_tr,.effect_time_tr{
    display: none;
}
</style>
	<form id="myform" method="post" action="{pigcms{:U('Baodi/modify')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$info.id}"/>
		<input type="hidden" name="block" value="{pigcms{$block}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">导航名称</th>
				<td><input type="text" class="input fl" value="{pigcms{$info.title}" name="title" size="20" placeholder="建议4字以内" validate="maxlength:20,required:true"/></td>
			</tr>
			<notempty name="plist">
			<tr>
				<th width="80">所属</th>
				<td>
					<select name="pid">
						<option value="0">我是一级</option>
						<volist name="plist" id="vo">
							<option value="{pigcms{$vo.id}" <if condition="$vo['id'] eq $info['pid']">selected="selected"</if>>{pigcms{$vo.title}</option>
						</volist>
					</select>
				</td>
			</tr>
			</notempty>
			<tr>
				<th width="80">导航图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:180px;" placeholder="请上传图片" tips="可不上传"  onchange="preview(this);"/></td>
			</tr>
			<if condition="$info['pic']">
				<tr>
					<th width="80">导航现图</th>
					<td><img src="{pigcms{$info.pic}" style="width:80px;height:80px;" class="view_msg"/></td>
				</tr>
			</if>
			<tr>
				<th width="80">背景颜色</th>
				<td><input type="text" tips="请点击右侧按钮选择颜色，用途为页面显示。" placeholder="可不填写" style="width:120px;" id="choose_color" value="{pigcms{$info.bgcolor}" name="font_color" class="input fl">&nbsp;&nbsp;<a style="line-height:28px;" id="choose_color_box" href="javascript:void(0);">点击选择颜色</a></td>
			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
				<input type="text" class="input fl" value="{pigcms{$info.link}" name="link" id="link" style="width:180px;" placeholder="请填写链接地址" validate="maxlength:200,url:true"/>
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('link', 0, 0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
			<tr>
				<th width="80">导航排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$info.sort}" validate="maxlength:10,number:true"/></td>
			</tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/colpick.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/colpick.css?111" />

<script>
    var ignoreValidate = ":hidden";

    $('input[name=effect_date_type]').on('click', function () {
        if ($(this).val() == 2) {
            $('.effect_date_tr').show();
        } else {
            $('.effect_date_tr').hide();
        }
    })

    $('input[name=effect_time_type]').on('click', function () {
        if ($(this).val() == 2) {
            $('.effect_time_tr').show();
        } else {
            $('.effect_time_tr').hide();
        }
    })

$("#yes").click(function () {
	$("#adver_region").hide();
})

$("#no").click(function () {
	$("#adver_region").show();
})

var admin_area_id = {pigcms{$system_session.area_id};
if(admin_area_id > 0){
	$("#adver_currency").hide();
	$("#no").trigger('click');
}
	
$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})

// APP中想要打开i切换
$("#app_open_type").change(function () {
    if ($("#app_open_type").val() == 1) {
    	// 打开小程序
        $(".app_open_wxapp").show();
        $(".app_open_app").hide();
    } else {
    	// 打开app
        $(".app_open_app").show();
        $(".app_open_wxapp").hide();
    }
})
$("#app_wxapp_id").change(function () {
    if ($("#app_wxapp_id").val()) {
        $(".app_wxapp_page").show();
    } else {
        $("#app_wxapp_page").val('');
        $(".app_wxapp_page").hide();
    }
})

var pageDomId = '';
function addLink(domid,iskeyword, type){
	pageDomId = domid;
	art.dialog.data('domid', domid);
	if (type == 1) {
		art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	} else {
		parent.setLinkBases(window, 'platform', 'h5');
	}
}
//链接库回调方法
function getLinkBasesUrl(url){
	console.log('url', url);
	$('#' + pageDomId).val(url);
}


</script>
<script type="text/javascript">
$('.color-box').colpick({
	colorScheme:'dark',
	layout:'rgbhex',
	color:'#ff8800',
	onSubmit:function(hsb,hex,rgb,el) {

		$(el).siblings('.color').val('#'+hex);
		$(el).css('background-color', '#'+hex);
		$(el).colpickHide();
		change_mark_box();
	}
});
var img_src="{pigcms{$now_slider.pic}";
if(img_src){
	img_src="{pigcms{:file_domain()}/upload/slider/"+img_src;
}
function preview(file){ 
	if (file.files && file.files[0]){  
		var reader = new FileReader();  
		reader.onload = function(evt){
			img_src=evt.target.result;
			change_mark_box();
		}    
		reader.readAsDataURL(file.files[0]);  
	}else{
		img_src=file.value;
		change_mark_box();
	}
}
function change_mark_box(){
	var mark_name=$('#mark_name').val();
	var mark_bg=$('#mark_bg').val();
	if(mark_name!='' && mark_bg!='' && img_src!=''){
		$('.mark_box').find('.mark').text(mark_name).css({'background-color':mark_bg}).show();
		$('.mark_box').find('img').attr('src',img_src);
		$('.show_box').show();	
	}else{
		$('.mark_box').find('.mark').hide();
		$('.show_box').hide();
	}
}
change_mark_box();
</script>
<include file="Public:footer"/>