<include file="Public:header"/>
<style type="text/css">
.importexcel{height: 60px;line-height: 60px;}
.importexcel a{ cursor: pointer;}
</style>
		<div class="mainbox">
            <include file="batch_create_card_header"/>

            <table class="search_table" width="100%">
				<tr>
					<td>
						<div class="importexcel">
							&nbsp;&nbsp;&nbsp;示例表格：
							<a target="_blank" href="{pigcms{$config.site_url}/static/file/user/import_card.xls?20231012">点击下载</a>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<div class="importexcel">
						  <span>Excel导入： </span>
						  <a href="javascript:;"><input type="file" title="Excel导入"  id="excelimport"  name="excelfile" value="选择文件上传"/></a>
						</div>
					</td>
				</tr>
			</table>
            <if condition="$batch_no">
                <iframe frameborder="no" width="100%" height="600" scrolling="yes" src="{pigcms{:U('InternalCard/importBatchCards',['batch_no'=>$batch_no])}"></iframe>
            </if>
		</div>

<script type="text/javascript" src="{pigcms{$static_public}js/ajaxfileupload.js"></script>
<script type='text/javascript'>
// 上传excel 表格
function ExcelImport(){
       $.ajaxFileUpload({
       url:"{pigcms{:U('InternalCard/import')}",    //需要链接到服务器地址
       secureuri:false,
       fileElementId:'excelimport',                 //文件选择框的id属性
       dataType: 'json',
       success: function (data) {
       if(data.error){
		   alert(data.msg);
	    }else{
			 alert('导入成功！');
			 window.location.href = '/admin.php?c=InternalCard&a=import_card&batch_no=' + data.batch_no;
		   }
		   $("#excelimport").change(function(){ 
                ExcelImport();            
           });
        } 
     }); 
  }

  $("#excelimport").change(function(){ 
          ExcelImport();            
     });
</script>
<include file="Public:footer"/>