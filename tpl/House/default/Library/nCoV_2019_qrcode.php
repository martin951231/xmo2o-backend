<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
			<div style="text-align:center;">
				<img style="width:200px;" src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode&qrCon={pigcms{:urlencode($config['site_url'] . '/wap.php?g=Wap&c=House&a=nCoV_2019&village_id='.$house_session['village_id'])}"/>
			</div>
        </div>
    </div>
</div>
<include file="Public:footer"/>
<script>
	$(function(){
		$('#new_navbar').remove();
		$('#new_navbar-place').remove();
	
		$('#new-sidebar').remove();
		
		$('.zuoce').remove();
		
		$('.main-content').css('margin-left', '0px');
	})
</script>