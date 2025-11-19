<script type="text/javascript">
	var oldmd5langkey = localStorage.getItem('oldmd5langkey');
	if(typeof md5langkey != 'undefined'){
		if(oldmd5langkey != md5langkey && oldmd5langkey != null){
			localStorage.removeItem(oldmd5langkey);//去掉旧的，防止溢出
		}
		localStorage.setItem('oldmd5langkey',md5langkey);
		var _lang = localStorage.getItem(md5langkey);
		if(_lang == null){
			$.post("./appapi.php?c=Lang&a=js",{lang:'{pigcms{$config.system_lang}'},function(data){
				localStorage.setItem(data.key,data.lang);
				_lang = JSON.parse(data.lang);
			},'json');
		}
		_lang = JSON.parse(_lang);		
	}
	function L_(str){
		str = $.trim(str);
		if(typeof _lang == 'undefined'){
			return str;
		}
		if(str == ''){
		  return str;
		} else if(typeof _lang[str] != 'undefined'){
			return _lang[str] != '' && _lang[str] ? _lang[str] : str;	
		} else{
			$.post('./appapi.php?c=Lang&a=add',{key:str},function(data){});
			return str;
		}
	}
</script>