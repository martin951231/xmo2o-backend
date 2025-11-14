var _this = null;
setTitleStyle(L_("提现"), "#333333", "#ffffff");
var app = new Vue({
	el: "#app",
	data: {
		name: "",
		cashMoney: 0, //可提现金额
		inputMoney: "",
		allMoney: 0, //剩余金额
		rate: 0, //费率
		cashType: "", // 提现方式
		cashTypeTxt: "",
		showPicker: false,
		typeList: [],
		accountName: "",
		account: "", //支付宝账号
		isCanSubmit: true, // 限制重复点击
        currencyTxt: L_('元'),
        currency: '￥',
		bankCardShow: false,
		showBankCardPicker: false,
		showBankPopType: '',
		pickerColumns: [],
		form: {},
		bankCardList: [],
		bank_id: '',
		area_list: [],
		minimumWithdrawalAmount: 0, //最低提现金额
		withdrawPlaceholder: "", //提现输入框提示语
	},
	created() {
		_this = this;
		this.getCashInfo();
		getConfig(function (config) {
			_this.accountName = config.wechat_name;
			_this.minimumWithdrawalAmount = (config.deliver_minimum_withdrawal_amount * 1);
			if(_this.minimumWithdrawalAmount > 0){
				_this.withdrawPlaceholder = L_("最低金额X1元",{X1:_this.minimumWithdrawalAmount})
			}else{
				_this.withdrawPlaceholder = L_("请输入提现金额")
			}
			if (_this.typeList.length) {
				_this.cashType = _this.typeList[0].value;
				_this.cashTypeTxt = _this.typeList[0].name;
			}
            if (config) {
                const {currency_txt = L_('元'),currency = '￥'} = config
                _this.currencyTxt = currency_txt
                _this.currency = currency
            }
		});
	},
	methods: {
		getCashInfo() {
			common.http(
				"/deliver/User/wallet",
				{ noTip: true },
				function (data) {
					_this.allMoney = (data.money * 1).toFixed(2);
					_this.cashMoney = (data.can_withdraw_money * 1).toFixed(2);
					_this.rate = (data.service_charge_percent * 1).toFixed(2);
					_this.bankCardList = data.bank_list
					_this.area_list = data.area_list
					_this.typeList = data.select_type.map(v => {
						v.name = v.label;
						return v
					});
					data.bank_list.forEach(v=>{
						if(v.is_default == 1){
							_this.bank_id = v.id
						}
					})
				}
			);
		},
		accountClick() {
			this.$dialog
				.alert({
					title: L_("提示"),
					message:
                    L_("提现到支付宝为手动打款方式，请正确填写支付宝账号信息，以免影响正常提现。"),
					confirmButtonText: L_("我知道了"),
					confirmButtonColor: "#27A3F7",
				})
				.then(() => {
					// on close
				});
		},
		// 校验支付宝账号
		formatter(val) {
			return val.replace(/[\u4E00-\u9FA5]/g, "");
		},
		// 全部提现
		allCash() {
			if (this.cashMoney > 0) {
				this.inputMoney = this.cashMoney;
			} else {
				this.$toast(L_("暂无可提现的金额")+"~");
			}
		},
		onConfirm(value) {
			console.log(value);
			this.cashType = value.value;
			this.cashTypeTxt = value.name;
			this.showPicker = false;
		},
		// 提现
		cash() {
			if (!this.name) {
				this.$toast(L_("请填写提现人姓名"));
				return;
			}
			if (!this.inputMoney) {
				this.$toast(L_("请填写提现金额"));
				return;
			}
			if (Number(this.inputMoney) > Number(this.cashMoney)) {
				this.$toast(L_("当前提现金额大于可提现金额"));
				return;
			}
			if (Number(this.minimumWithdrawalAmount) > 0 && Number(this.inputMoney) < Number(this.minimumWithdrawalAmount)){
				this.$toast(L_("最低提现金额为：X1元",{X1:this.minimumWithdrawalAmount}));
				return;
			}
			var params = {
				truename: this.name,
				money: this.inputMoney,
				type: this.cashType,
			};
			if (this.cashType == "alipay") {
				if (this.account) {
					params.account = this.account;
				} else {
					this.$toast(L_("请填写您的支付宝账号"));
					return;
				}
			}

			if(this.cashType == 'bank'){
				if(!this.bank_id){
					this.$toast(L_("请选择银行卡"));
					return
				}
				_this.goCash(params);
				return
			}

			common.http(
				"/deliver/User/isBind",
				{ noTip: true, type: this.cashType },
				function (data) {
					if (!data.is_bind) {
						var msg = L_(`提现时请先至微信公众号搜索【X1】进行注册用户后才可提现哦~`,{X1:_this.accountName});
						_this.$dialog
							.confirm({
								message: msg,
								confirmButtonText: L_("复制公众号名称"),
								confirmButtonColor: "#27A3F7",
							})
							.then(function () {
								_this.copyTxt(_this.accountName);
							})
							.catch(function () {});
					} else {
						_this.goCash(params);
					}
				}
			);
		},
		goCash(params) {
			params.bank_id = this.bank_id;
			if (!_this.isCanSubmit) {
				return;
			}
			_this.isCanSubmit = false;

			common.http(
				"/deliver/User/applyWithdraw",
				params,
				function (data) {
					_this.isCanSubmit = true;

					_this.$dialog
						.alert({
							title: L_("提示"),
							message: L_("提现申请成功，请等待管理员审核")+"~",
							confirmButtonColor: "#27A3F7",
							confirmButtonText: L_("我知道了"),
						})
						.then(function () {
							redirect("back");
						})
						.catch(function () {});
				},
				function (err) {
					_this.isCanSubmit = true;
					_this.$dialog
						.alert({
							title: L_("提示"),
							message: err.msg,
							confirmButtonColor: "#27A3F7",
							confirmButtonText: L_("确定"),
						})
						.then(function () {})
						.catch(function () {});
				}
			);
		},
		// 复制
		copyTxt(text) {
			var oInput = document.createElement("input");
			oInput.value = text;
			document.body.appendChild(oInput);
			oInput.select(); // 选择对象
			document.execCommand("Copy"); // 执行浏览器复制命令
			oInput.className = "oInput";
			oInput.style.display = "none";
			oInput.blur();
			this.$toast(L_("复制成功"));
		},
		addBankCard(){
			this.form = {
				province_id: '',
				city_id: '',
				province_name: '',
				city_name: '',
				is_default: 0,
				account_name: '',
				account: '',
				remark: '',
				branch: '',
			}
			this.bankCardShow = true;
		},
		editBankCard(item){
			this.form = JSON.parse(JSON.stringify(item));
			if(this.form.province_id){
				const data = this.area_list.find(v=>v.value == this.form.province_id)
				this.form.province_name = data.title || ''
			}
			if(this.form.city_id){
				this.area_list.forEach(p=>{
					p.children.forEach(c=>{
						if(c.value == this.form.city_id){
							this.form.city_name = c.title || ''
						}
					})
				})
			}
			this.bankCardShow = true;
		},
		delBankCard(item){
			vant.Dialog.confirm({
				title: L_("提示"),
				message: L_("确认删除吗？"),
			  })
			.then(() => {
				common.http(
					"/deliver/User/deleteBankCard",
					{bank_id: item.id},
					data=> {
						setTimeout(()=>{
							this.$toast(L_('删除成功'));
						},150)
						this.getBankCardList();
					}
				);
			})
		},
		onSubmit(values){
			this.form.bank_id = this.form.id || ''
			common.http(
				"/deliver/User/createBankCard",
				this.form,
				data=> {
					this.bankCardShow = false;
					setTimeout(()=>{
						this.$toast(L_(this.form.bank_id?"编辑成功":'添加成功'));
					},150)
					this.getBankCardList();
				}
			);
		},
		onPickerConfirm(e){
			this.form[this.showBankPopType] = e.value;
			if(this.showBankPopType == 'province_id'){
				this.form.province_name = e.text;
			}
			if(this.showBankPopType == 'city_id'){
				this.form.city_name = e.text;
			}
			this.showBankCardPicker = false;
		},
		openBankCardPicker(type){
			this.showBankPopType = type;
			if(type == 'province_id'){
				this.pickerColumns = this.area_list.map(v=>{
					return {
						text: v.title,
						value: v.value
					}
				})
			}
			if(type == 'city_id'){
				let arr = []
				this.area_list.forEach(p=>{
					if(p.value == this.form.province_id){
						p.children.forEach(c=>{
							arr.push({
								text: c.title,
								value: c.value
							})
						})
					}
				})
				this.pickerColumns = arr
			}
			this.showBankCardPicker = true;
		},
		getBankCardList() {
			common.http(
				"/deliver/User/wallet",
				{ },
				function (data) {
					_this.bankCardList = data.bank_list
					data.bank_list.forEach(v=>{
						if(v.is_default == 1){
							_this.bank_id = v.id
						}
					})
				}
			);
		},
	},
});

function onInput(value) {
	var val = value.replace(/^\D*(\d*(?:\.\d{0,2})?).*$/g, "$1");
	_this.inputMoney = val;
	return val;
}
