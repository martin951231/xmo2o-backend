<include file="Public:header"/>
<div class="main-content">
	<iframe src="{pigcms{$config.site_url}/plugin.php?plugin=index&from=merchant" style="width:100%;border:0;" id="accessUrl"></iframe>
</div>
<script type="text/javascript">
	var winHeight = $(window).height()-$('#new_navbar').height()-8;
	var LeftHeight = $('#sidebar .nav-list').height() + $('#sidebar-collapse').height()+30;
	$('#accessUrl').height(winHeight > LeftHeight ? winHeight : LeftHeight);
</script>

<include file="Public:footer"/>