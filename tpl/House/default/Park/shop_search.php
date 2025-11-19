<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form id="add_info" class="form-horizontal" method="post" action="__SELF__">
                <div class="tab-content">
                    <div class="form-group not_coop">
                        <label class="col-sm-1"><label for="housesize">店铺名称：</label></label>
                        <input class="col-sm-2" size="20" id="shop_name" name="shop_name" placeholder="请输入店铺名称" type="text" value=""/>
                        <input class="col-sm-2" size="20" id="name"  type="hidden" value=""/>
                        <input class="col-sm-2" size="20" id="id"  type="hidden" value=""/>
                        <button class="btn btn-info" type="button" onclick="sub_shop()">
<!--                            <i class="ace-icon fa fa-check bigger-110"></i>-->
                            搜索
                        </button>
                    </div>
                    <div>
                        <table class="table table-striped table-bordered table-hover" style="margin-bottom:0px">
                            <thead>
                            <tr>
                                <th style="min-width:90px">店铺名称</th><th style="min-width:50px">操作</th>
                            </tr>
                            </thead>
                            <tbody id="tbody_shop">
                            </tbody>
                        </table>
                    </div>
                    <div id="barcon" class="form-group not_coop" style="margin-top: 10px;margin-left: 200px;"></div>
                </div>
                <div class="space"></div>
        </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    function sub_shop() {
        if(!$('#shop_name').val()){
            layer.alert('请输入店铺名称');
            return false;
        }
        var params = {
            'm_name':$('#shop_name').val(),
        };
        $.post("{pigcms{:U('shop_search')}", params, function (data) {
            //data = JSON.parse(data);
            console.log(data)
            if (data.status == 1) {
                $('#name').val(data.list[0].name);
                $('#id').val(data.list[0].mer_id);
                var len = data.list.length;
                console.log(len);
                console.log(data.list[0].name);
                var html = '';
                for (var i=0;i<len;i++){
                     html += '<tr><td>'+data.list[i].name+'</td><td><a href="javascript:;" onclick="choose_shop('+data.list[i].store_id+','+"'"+data.list[i].name+"'"+')"> 选择</a></td></tr>';
                }
                console.log(html);
                $('#tbody_shop').html(html);
                goPage(1,8);
            } else {
                layer.alert(data.info);
            }
        });
    }
    function choose_shop(id,name) {
        $('#name').val(name);
        $('#id').val(id);
        $('#shop_name').val(name);
        console.log(name+id);
    }
    function callBackMsg() {
        var name = $('#name').val();
        var id = $('#id').val();
        console.log(name+id);
        var newArr = {id:id,name:name}
        return newArr;
    }
</script>
<include file="Publics:footer"/>
<script>
    /**
     * 分页函数
     * pno--页数
     * psize--每页显示记录数
     * 分页部分是从真实数据行开始，因而存在加减某个常数，以确定真正的记录数
     * 纯js分页实质是数据行全部加载，通过是否显示属性完成分页功能
     **/
    function goPage(pno,psize){
        var itable = document.getElementById("tbody_shop");
        var num = itable.rows.length;//表格所有行数(所有记录数)
        var totalPage = 0;//总页数
        var pageSize = psize;//每页显示行数
        //总共分几页
        if(num/pageSize > parseInt(num/pageSize)){
            totalPage=parseInt(num/pageSize)+1;
        }else{
            totalPage=parseInt(num/pageSize);
        }
        var currentPage = pno;//当前页数
        var startRow = (currentPage - 1) * pageSize+1;//开始显示的行 31
        var endRow = currentPage * pageSize;//结束显示的行  40
        endRow = (endRow > num)? num : endRow;  //40
        //遍历显示数据实现分页
        for(var i=1;i<(num+1);i++){
            var irow = itable.rows[i-1];
            if(i>=startRow && i<=endRow){
                // irow.style.display = "block";
                irow.style = "";
            }else{
                irow.style.display = "none";
            }
        }
        var tempStr = "共"+num+"条记录 当前第"+currentPage+"页 &nbsp;";
        if(currentPage>1){
            tempStr += "<button class=\"btn btn-info\" style='margin-left: 3px;' onClick=\"goPage("+(currentPage-1)+","+psize+")\">上一页</button>"
        }
        if(currentPage<totalPage){
            tempStr += "<button class=\"btn btn-info\" style='margin-left: 3px;' onClick=\"goPage("+(currentPage+1)+","+psize+")\">下一页</button>";
        }
        document.getElementById("barcon").innerHTML = tempStr;
    }
</script>

