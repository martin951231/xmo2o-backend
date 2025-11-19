$('title').text(L_('在线签署合同'));
$('.mui-title').text(L_('在线签署合同'));

var left_second = 30;

//签字倒计时
function sign_timer() {
    var signTimer = setInterval(function () {
        left_second--;
        if (left_second == 0) {
            $('.sign_btn').addClass('yellow').html(L_('去签字'))
            clearInterval(signTimer);
        } else {
            $('.sign_btn').html(L_('去签字') + '（' + left_second + 's）')
        }
    }, 1000);
}

function get_contract() {
    common.http('Merchantapp&a=contract', {}, function (data) {
        laytpl(document.getElementById('contractTpl').innerHTML).render(data, function (html) {
            $('#contract_box').append(html);
            $('.loading').hide();
        });
    });
}

function is_sign(){
    common.http('Merchantapp&a=isSign', {}, function (data) {
        if(data.is_sign){
            $('.sign_btn').hide();
        }else{
            $('.sign_btn').show();
        }
    });
}

is_sign();
get_contract();
sign_timer();

if (urlParam['hideSign'] == 1) {
    $('.sign_btn').hide();
}


$('.sign_btn').on('click', function () {
    if ($(this).hasClass('yellow')) {
        if (common.checkApp()) {
            openWebviewUrl('sign_panel.html')
         } else {
            redirect_self('sign_panel.html');
         }
    }
})