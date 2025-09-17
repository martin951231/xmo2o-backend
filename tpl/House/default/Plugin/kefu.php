<include file="Public:header"/>
<div class="main-content">
	<iframe src="{pigcms{$_GET['url']}" style="width:100%;border:0;" id="accessUrl"></iframe>
</div>
<script type="text/javascript">
	var winHeight = $(window).height()-$('#new_navbar').height()-8;
	var LeftHeight = $('#sidebar .nav-list').height() + $('#sidebar-collapse').height()+900;
	$('#accessUrl').height(winHeight > LeftHeight ? winHeight : LeftHeight);
</script>

<include file="Public:footer"/>