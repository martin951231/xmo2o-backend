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
				
				// 新版框架
				if(typeof top.contentScrollToTop == 'function'){
					$.each($('.iframe-container iframe', parent.document), function(i, item){
						if($(item).css('display') != 'none'){
							console.log($(item).attr('src'), $(item).css('min-height'));
							if($(item).css('min-height') != '0' && $(item).css('min-height') != '0px'){
								$(item).css('min-height', $(parent.document).height() - 50);
								$(item).css('min-height', $('body').height() + 40 < $(parent.document).height() - 50 ? $(parent.document).height() - 50 : $('body').height() + 40);
							}
						}
					});
				}
				
				/* alert($(window.parent).scrollTop()); */
				/* parent.scrollTo(0,0); */
				// alert(parent.iframeRealHeight);
			});
		</script>
	</if>
	<script type="text/javascript">
		setInterval(function(){
			$.get("{pigcms{:U('loginKeep')}")
		},300000);
	</script>
</html>