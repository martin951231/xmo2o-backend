$(function(){
	console.log('2222', $(window).height())
	$('.list-wrapper').height($(window).height() - 65);
})

var oldKey = $.trim($('#searchTxt').val());

$('#searchTxt').keyup(function(e){
	var searchTxt = $.trim($('#searchTxt').val());
	if(searchTxt != ''){
		$('.icon-cancel').removeClass('hide');
	}else{
		$('.icon-cancel').addClass('hide');
	}
	if(e.keyCode == 13){
		search(false);
	}
});

$('.search-btn').click(function(e){
	search(false);
});
$('.icon-cancel').click(function(e){
	$('#searchTxt').val('');
	//不是搜索的页面，不刷新页面。
	if(oldKey != ''){
		search(true);
	}
});

// mode  true代表允许空值，false代码不允许
function search(mode){
	var searchTxt = $.trim($('#searchTxt').val());
	if(mode == false && searchTxt == ''){
		return 
	}
	if(searchTxt != ''){
		searchUrl+= '&key=' + encodeURIComponent(searchTxt);
	}
    var now_city = GetRequest().now_city || ''
    if(now_city) {
        searchUrl+= '&now_city=' + now_city;
    }
	var stateObj = { foo: searchUrl };
	history.replaceState(stateObj, "", searchUrl);
	location.reload();
}

function GetRequest() {
	var url = location.search;
	var theRequest = {};
    var strs = []
	if (url.indexOf("?") != -1) {
		var str = url.substr(1);
		strs = str.split("&");
		for (var i = 0; i < strs.length; i++) {
			theRequest[strs[i].split("=")[0]] = decodeURIComponent(strs[i].split("=")[1]);
		}
	}
	return theRequest;
}
