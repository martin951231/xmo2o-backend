
 
/**
* 录入完成后，输入模式失去焦点后对录入进行判断并强制更改，并对小数点进行0补全
* arg1 inputObject
**/
function overFormat(th){
    var v = th.value;
    if(v === ''){
        v = '0.00';
    }else if(v === '0'){
        v = '0.00';
    }else if(v === '0.'){
        v = '0.00';
    }else if(/^0+\d+\.?\d*.*$/.test(v)){
        v = v.replace(/^0+(\d+\.?\d*).*$/, '$1');
        v = inp.getRightPriceFormat(v).val;
    }else if(/^0\.\d$/.test(v)){
        v = v + '0';
    }else if(!/^\d+\.\d{2}$/.test(v)){
        if(/^\d+\.\d{2}.+/.test(v)){
            v = v.replace(/^(\d+\.\d{2}).*$/, '$1');
        }else if(/^\d+$/.test(v)){
            v = v + '.00';
        }else if(/^\d+\.$/.test(v)){
            v = v + '00';
        }else if(/^\d+\.\d$/.test(v)){
            v = v + '0';
        }else if(/^[^\d]+\d+\.?\d*$/.test(v)){
            v = v.replace(/^[^\d]+(\d+\.?\d*)$/, '$1');
        }else if(/\d+/.test(v)){
            v = v.replace(/^[^\d]*(\d+\.?\d*).*$/, '$1');
            ty = false;
        }else if(/^0+\d+\.?\d*$/.test(v)){
            v = v.replace(/^0+(\d+\.?\d*)$/, '$1');
            ty = false;
        }else{
            v = '0.00';
        }
    }
    th.value = v; 
}
 
 
 function check_form(){    
 	var name = $.trim($('input[name="name"]').val());
 	var number = $.trim($('input[name="number"]').val());
    var price = $.trim($('input[name="price"]').val()); 
 	
 	if(name != '' && number != '' && price != ''){ 
        weui.alert('<p>组别：33</p><p>性别：33</p><p>id：33</p><p style="margin-top:10px">充值金额：33</p>', { 
        	buttons: [{
        		label:L_('确定'),
        		type: 'primary',
        		onClick: function(){ console.log('ok') }
        	},
            {
            	label:L_('取消'),
            	type: 'default',
            	onClick: function(){ console.log('') }
            }]
        }); 
 	}else{ 
 		 weui.alert('<img src="'+path+'/images/tishi.png" style="width:30px"><br>填写的人员信息有误，请修改再试确定'); 
         return false;
 	}   
 	
 }
 
//60秒刷新
 refreshInt()
function refreshInt(){
    if ($('#code').html() != undefined) {
        $.post('/wap.php?c=Company_campus&a=ajaxBuildPayCode', function (data) {
            console.log(data)
            if (data.error_no == 0) {
                $('#pay_bar_code').attr('src', data.retval.pay_bar_code);
                $('#code').html(data.retval.code);
                $('#pay_qr_code').attr('src', data.retval.pay_qr_code);
            }
            is_load = false
        }, 'json')
    }
    setTimeout("refreshInt()",60000);
}
