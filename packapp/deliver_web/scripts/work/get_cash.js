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
        currency: '￥'
	},
	created() {
		_this = this;
		this.getCashInfo();
		getConfig(function (config) {
			_this.typeList = config.withdraw_type;
			_this.accountName = config.wechat_name;
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
	},
});

function onInput(value) {
	var val = value.replace(/^\D*(\d*(?:\.\d{0,2})?).*$/g, "$1");
	_this.inputMoney = val;
	return val;
}
