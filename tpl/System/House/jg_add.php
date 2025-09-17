<include file="Public:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    body fieldset {
        border: none;
    }
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form  id="add_info" class="form-horizontal" method="post" action="{pigcms{:U('House/jg_add')}">

                <div class="tab-content">
                    <div class="form-group" >
                        <label class="col-sm-2"><label for="jg_title"> 标 题 ：</label></label>
                        <label style="width: 320px;">
                            <input style="width:310px; height: 30px;" id="jg_title" name="jg_title" placeholder="请输入标题30字以内" validate="maxlength:20,required:true">
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2"><label for="floor_id">发送对象：</label></label>
                        <label> <fieldset id="choose_village_all" village_id="0" single_id="0" floor_id="0"></fieldset></label>
                    </div>

                    <div class="form-group" >
                        <label class="col-sm-2"><label for="send_content"> 内 容 ：</label></label>
                        <label style="width: 320px;">
                            <textarea style="width:320px;height: 100px;" id="send_content" name="send_content" placeholder="请输入发送的内容200字以内" validate="maxlength:20,required:true"></textarea>
                        </label>
                    </div>
                    <div class="form-group" >
                        <label class="col-sm-2"><label for="url">跳转链接：</label></label>
                            <input style="width:310px;height: 30px;" id="url" name="url" placeholder="">
                            <?php if(!C('butt_open')){ ?>
                            <if condition="$now_category['cat_type'] neq 1">
                                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
                                <else />
                                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 1)" data-toggle="modal">从功能库选择</a>
                            </if>
                        <?php } ?>
                     
                    </div>
                </div>
                <div class="btn hidden">
                    <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
                    <input type="reset" value="取消" class="button" />
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script>
    var choose_village = "{pigcms{:U('Area/ajax_all_house_village')}";
    var choose_single = "{pigcms{:U('Area/ajax_get_single')}";
    var choose_floor = "{pigcms{:U('Area/ajax_get_floor')}";
</script>

<script type="text/javascript">
    function addLink(domid, iskeyword, type) {
        art.dialog.data('domid', domid);
        if (type == 1) {
            art.dialog.open('?g=Admin&c=LinkPC&a=visualize&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        } else {
            art.dialog.open('?g=Admin&c=Link&a=visualize&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        }
    
    }
function show_village(){

	$.post(choose_village,{},function(result){
		result = $.parseJSON(result);
		var title = '全部小区';
		if(result.status == 1){
			var area_dom = '<select id="choose_village" name="village_id" class="col-sm-1" style="margin-right:10px;min-width: 18rem;" validate="maxlength:20,required:true">';
			area_dom+= '<option value="0" '+(0==$('#choose_village').attr('village_id') ? 'selected="selected"' : '')+'>'+title+'</option>';
			$.each(result.cat_list,function(i,item){

			  area_dom+= '<option value="'+item.village_id+'" '+(item.village_id==$('#choose_village').attr('village_id') ? 'selected="selected"' : '')+'>'+item.village_name+'</option>';
				
			});
			area_dom+= '</select>';
			$('#choose_village_all').prepend(area_dom);
			$('#choose_village').change(function(){
				show_single($(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
			});

		}else{
			layer.msg('请联系管理员添加小区信息');
		}
	});
}

function show_single(village_id,vname){

	$.post(choose_single,{village_id: village_id,vname:vname},function(result){

		result = $.parseJSON(result);
		var title = '全部楼栋';
		if(result.status == 1){
			var area_dom = '<select id="choose_single" name="single_id" class="col-sm-1" style="margin-right:10px;min-width: 18rem;">';
			area_dom+= '<option value="0" '+(0==$('#choose_village').attr('single_id') ? 'selected="selected"' : '')+'>'+title+'</option>';
			$.each(result.cat_list,function(i,item){
			    area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_village').attr('single_id') ? 'selected="selected"' : '')+'>'+item.single_name+'</option>';
				
			});
			area_dom+= '</select>';

            if(document.getElementById('choose_single')){
				$('#choose_single').replaceWith(area_dom);
			}else if(document.getElementById('choose_village')){
				$('#choose_village').after(area_dom);
			}else{
				$('#choose_village_all').prepend(area_dom);
			}
			$('#choose_single').change(function(){
				show_floor(village_id,$(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
			});

		}else{
            var area_dom='<select id="choose_single" name="single_id" class="col-sm-1" style="margin-right:10px;min-width: 18rem;"><option value="0" '+(0==$('#choose_village').attr('single_id') ? 'selected="selected"' : '')+'>'+title+'</option></select>';
           if(document.getElementById('choose_single')){
				$('#choose_single').replaceWith(area_dom);
			}else if(document.getElementById('choose_village')){
				$('#choose_village').after(area_dom);
			}else{
				$('#choose_village_all').prepend(area_dom);
			}
            if($('#choose_floor').size()>0){
                var area_dom='<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;"><option value="0" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>全部单元</option></select>';
                if(document.getElementById('choose_floor')){
                    $('#choose_floor').replaceWith(area_dom);
                }else if(document.getElementById('choose_single')){
                    $('#choose_single').after(area_dom);
                }else{
                    $('#choose_village_all').prepend(area_dom);
                }
            }
			layer.msg('请联系管理员添加楼栋信息');
		}
	});
}
//显示单元
function show_floor(village_id,id,name){
	$.post(choose_floor,{village_id:village_id,cat_id:id,name:name},function(result){
		result = $.parseJSON(result);
		var title = '全部单元';
		if(result.status == 1){
			var area_dom = '<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
			area_dom+= '<option value="0" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>'+title+'</option>';
			$.each(result.cat_list,function(i,item){
				area_dom+= '<option value="'+item.floor_id+'" '+(item.floor_id==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>'+item.floor_name+'</option>';
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_floor')){
				$('#choose_floor').replaceWith(area_dom);
			}else if(document.getElementById('choose_single')){
				$('#choose_single').after(area_dom);
			}else{
				$('#choose_village_all').prepend(area_dom);
			}
		}else{
            var area_dom='<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;"><option value="0" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>'+title+'</option></select>';
			if(document.getElementById('choose_floor')){
				$('#choose_floor').replaceWith(area_dom);
			}else if(document.getElementById('choose_single')){
				$('#choose_single').after(area_dom);
			}else{
				$('#choose_village_all').prepend(area_dom);
			}
            layer.msg('请联系管理员添加单元信息');
		}
	});
}

$(function(){
	//检测是否需要显示楼栋
	if(document.getElementById('choose_village_all')){
		show_village();
	}
});

    var sms_param_length=[];

    function word_num() {
        var val = $('#template_content').val();
        var font_num = 0; // 字数数量
        if (val && sms_param_length) {
            for (var i in sms_param_length) {
                if (sms_param_length[i] && sms_param_length[i]['key']) {
                    var n = (val.split(sms_param_length[i]['key'])).length-1;
                    font_num += n*2;// 1个汉字2个字符
                    var re = new RegExp(sms_param_length[i]['key'],"g"); //定义正则表达式
                    val = val.replace(re,"");
                }
            }
        }
        var word_info = val.replace(/[^\x00-\xff]/g,'01');
        var word_length = word_info.length;
        font_num += word_length;
        $('.word_length').html(font_num);
    }

    $(document).ready(function (){

        $('#add_info').submit(function(){
            $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
                console.log('result', result);
                if (result && result.status==1) {
                    layer.open({
                        content: '操作成功！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.parent.closeiframe();
                            window.parent.main_refresh();
                        }
                    })
                } else if (result.info) {
                    layer.open({
                        content: result.info,
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['知道了']
                    })
                } else {
                    layer.open({content: '操作失败'});
                }
            });
            return false;
        });
    });

</script>
<include file="Publics:footer"/>