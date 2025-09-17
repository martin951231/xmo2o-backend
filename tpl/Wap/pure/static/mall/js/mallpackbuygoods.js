/***************一口价活动页面js,此页面购物车cookie相关信息来自于mallbuygoods.js***************/

//加入购物车
$(document).on('click', '.pack_add_cart', function() {
	var goods_id = $(this).attr('data-id');
	have_num +=1;
	$.post(ajax_cart_save_url,{cart_cookie:cart_cookie,goods_id:goods_id},function(res){
		res = eval('(' + res + ')');
		if(res.status==1){
			set_selected(have_num);
			location.reload();
		}else{
			alert(res.msg);
		}
    });
	
});

function set_selected(num){
	var _text = '';
	if(num>0){
		_text += '已选'+num+'件';
	}
	if(num >= pack_total){
		_text += ',满足条件啦！';
	}
	$("#selected").text(_text);
}
set_selected(have_num);