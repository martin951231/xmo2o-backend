//解析淘宝、天猫地址
var parse_flag = true;

$('#parse_taobao_url').on('click', function () {
    if (parse_taobao_url == undefined) {
        return false;
    }
    if (!parse_flag) {
        alert('操作频繁，请稍后再试');
        return;
    }

    var txt = $(this).html();
    var url = $('input[name=taobao_url]').val();

    parse_flag = false;
    $(this).html('采集中...');
    $.post(parse_taobao_url, {'url': url}, function (rs) {
        parse_flag = true;
        $('#parse_taobao_url').html(txt);
        if (rs.error_no == 0) {
            $('#name').val(rs.info.goods_name);
            $('#describe').val(rs.info.describe);
            $('#price').val(rs.info.price);

            for(i in rs.info.pict_url){
                $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="' + rs.info.pict_url[i].url + '"/><input type="hidden" name="pic[]" value="' + rs.info.pict_url[i].title + '"/><br/><a href="#" onclick="deleteImage(\'' + rs.info.pict_url[i].title + '\',this);return false;">[ 删除 ]</a></li>')
            }

            if (rs.info.zhibo_url) {
                $('#video_div').show();
                $('#video_div video').attr('src', rs.info.zhibo_url);
                $('input[name=video_url]').val(rs.info.zhibo_url);
            }

            if (rs.info.pcDescContent) {
                $('#content').append(rs.info.pcDescContent)
                KindEditor.html("#content", rs.info.pcDescContent);
            }
        } else {
            alert(rs.error_msg);
        }
    })
})