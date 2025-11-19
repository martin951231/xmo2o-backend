	</body>
	<if condition="empty($_GET['frame'])">
		<script type="text/javascript">
			if(typeof parent.showHelpText == 'function'){
				parent.showHelpText(parentShowHelpParam);
			}
			if(typeof parent.showHelpType == 'function'){
				parent.showHelpType(parentShowIndex,'{pigcms{:GROUP_NAME}','{pigcms{:MODULE_NAME}','{pigcms{:ACTION_NAME}');
			}
			$(function(){
				parent.iframeRealHeight = $('body').height() + 40;

				if(typeof parent.setMainHeight == 'function'){
					parent.setMainHeight({iframeHeight:true});
				}
				/* alert($(window.parent).scrollTop()); */
				/* parent.scrollTo(0,0); */
				// alert(parent.iframeRealHeight);
			});
		</script>
	</if>
	<script type="text/javascript">
		$('#choose_color_box').click(function(){
			window.top.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_color')
		});
	</script>
</html>