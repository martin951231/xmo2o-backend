<include file="Public:header"/>
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" 
codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" height="120" width="190"> 
<param name="movie" 
value="{pigcms{$config.site_url}/static/swf/vcastr22.swf?vcastr_file={pigcms{$video_url}"> 
<param name="quality" value="high"> 
<param name="allowFullScreen" value="false" /> 
<video
style="position: absolute;"
width="100%" height="100%" controls autoplay> 
<source src="{pigcms{$video_url}"  type="video/mp4">
  <object data="{pigcms{$video_url}" width="375" height="667">
    <embed src="{pigcms{$config.site_url}/static/swf/vcastr22.swf?vcastr_file={pigcms{$video_url}" width="375" height="667">
  </object> 
</video> 

<!-- quality="high"
flashvars="IsAutoPlay=1"
pluginspage="http://www.macromedia.com/go/getflashplayer" 
type="application/x-shockwave-flash"  -->
</object> 
<include file="Public:footer"/>