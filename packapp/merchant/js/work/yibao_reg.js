// 在 #app 标签下渲染一个按钮组件
new Vue({
  el: "#app",
  template: ``,
  data() {
    return {
      form: {
        phone: "", //子商户手机号
        userType: "", //子商户类型(0 小微 1个体工商户  2 企业)
        userName: "", //子商户名称 小微真实个人姓名，个体或公司填写营业执照名称
        certId: "", //身份证号码 小微：个人身份证号码；个体和企业：法人身份证号码
        businessScope: "", //经营范围 子商户类型非小微必填
        socialCreditCode: "", //统一社会信用码(子商户类型非小微必填)
        socialCreditCodeExpires: "", //统一社会信用证有效期(子商户类型非小微必填 格式：yyyyMMdd 长期的填20991231)
        certIdExpires: "", //法人身份证有效期 (子商户类型非小微必填)
        legalPhone: "", //法人手机号(小微填个人手机号子商户类型非小微必填)
        legalPerson: "", //法人姓名(子商户类型非小微必填)
        address: "", //企业地址(子商户类型非小微必填)
        provCode: "", //省份编码，子商户类型非小微必填
        areaCode: "", //地区编码，子商户类型非小微必填
        fileNo: "", //附件编号
        fileUrl: [],
        // 绑定结算账号数据
        cardName: "", //银行卡户名
        cardNo: "", //银行卡号
        bankAcctType: "", //银行账户类型
        bankName: "", //开户银行名称
        bankCode: "", //银行编码
        bankPhone: "", //银行预留手机号
        bankProvCode: "", //省
        bankAreaCode: "", //区域
      },
      pickerShow: false,
      popupShow: false,
      columns: [],
      form_value_name: {},
      form_value_type: "",
      minDate: "",
      maxDate: new Date(2025, 10, 1),
      currentDate: new Date(),
      uploader: [],
      bankCodeList: [],
      areaList: [],
      userTypeList: [
        { text: "小微", value: 0 },
        { text: "个体工商户", value: 1 },
        { text: "企业", value: 2 },
      ],
      bankAcctTypeList: [
        { text: "对公", value: 1 },
        { text: "对私", value: 2 },
      ],
      cascaderValue: "",
    };
  },
  created() {
    common.vue_render_show("", "#app");
    this.getAreaList();
    this.minDate = new Date(new Date().getFullYear() - 100, 0, 1);
    this.maxDate = new Date(new Date().getFullYear() + 100, 0, 1);
  },
  methods: {
    onClickLeft() {
      redirect("back");
    },
    onSubmit(e) {
      common.httpV20(
        "/merchant/merchant.MerchantYibao/applyYibao",
        this.form,
        (res) => {}
      );
    },
    formClick(type) {
      this.form_value_type = type;
      if (type == "userType") {
        this.columns = this.userTypeList;
      }
      if (this.form_value_type == "bankAcctType") {
        this.columns = this.bankAcctTypeList;
      }
      if (this.form_value_type == "bankCode") {
        this.columns = this.bankCodeList;
      }
      if (this.form_value_type == "area") {
        this.columns = this.areaList;
        this.cascaderValue = this.form.areaCode;
      }
      if (this.form_value_type == "bankArea") {
        this.columns = this.areaList;
        this.cascaderValue = this.form.bankAreaCode;
      }
      //日期选择器
      if (
        this.form_value_type == "certIdExpires" ||
        this.form_value_type == "socialCreditCodeExpires"
      ) {
        if (this.form[this.form_value_type]) {
          this.currentDate = new Date(this.form[this.form_value_type]);
        } else {
          this.currentDate = new Date();
        }
      }
      this.popupShow = true;
    },
    afterRead(file) {
      // 此时可以自行将文件上传至服务器
    },
    //点击上传
    upLoadClick(e) {
      e.preventDefault();
      let obj = {};
      this.uploader.forEach((v, index) => {
        obj["file" + index] = v.file;
      });

      common.uploadPic(
        "/merchant/merchant.MerchantYibao/uploadFile",
        obj,
        (res) => {
          this.form.fileNo = res.fileNo;
          this.form.fileUrl = res.fileUrl;
        }
      );
    },
    //确认事件
    onPickerConfirm(e, indexs) {
      console.log(e, indexs);
      if (
        this.form_value_type == "userType" ||
        this.form_value_type == "bankAcctType" ||
        this.form_value_type == "bankCode"
      ) {
        this.form_value_name[this.form_value_type + "_name"] = e.text;
        this.form[this.form_value_type] = e.value;
      }
      if (
        this.form_value_type == "certIdExpires" ||
        this.form_value_type == "socialCreditCodeExpires"
      ) {
        this.form[this.form_value_type] = this.getFormatter(e);
      }
      console.log(this.form);
      this.popupShow = false;

      setTimeout(() => {
        this.columns = [];
      }, 300);
    },
    cascaderFinish(e) {
      console.log(e);
      //选择省市区
      if (
        this.form_value_type == "area" ||
        this.form_value_type == "bankArea"
      ) {
        let item1 = e.selectedOptions[0];
        let item2 = e.selectedOptions[1];
        if (this.form_value_type == "bankArea") {
          if (item1) {
            this.form.bankProvCode = item1.huifu_code;
          }
          if (item2) {
            this.form.bankAreaCode = item2.huifu_code;
          }
        } else {
          if (item1) {
            this.form.provCode = item1.huifu_code;
          }
          if (item2) {
            this.form.areaCode = item2.huifu_code;
          }
        }

        this.form_value_name[this.form_value_type + "_name"] = `${
          item1.text + "/"
        }${item2 ? item2.text : ""}`;
      }
      this.popupShow = false;
    },
    //时间格式化 格式：yyyyMMdd
    getFormatter(date) {
      return (
        date.getFullYear() +
        "/" +
        (date.getMonth() + 1 < 10
          ? "0" + (date.getMonth() + 1)
          : date.getMonth() + 1) +
        "/" +
        (date.getDate() < 10 ? "0" + date.getDate() : date.getDate())
      );
    },
    //获取银行编号
    getBankCode() {
      common.httpV20(
        "/merchant/merchant.MerchantYibao/getBankCode",
        this.params,
        (res) => {
          this.bankCodeList = res;
          this.getDetail();
        }
      );
    },
    //获取区域列表
    getAreaList() {
      common.httpV20(
        "/merchant/merchant.MerchantYibao/getAreaList",
        this.params,
        (res) => {
          this.areaList = res;
          this.getBankCode();
        }
      );
    },
    //获取详情
    getDetail() {
      common.httpV20(
        "/merchant/merchant.MerchantYibao/getYibaoInfo",
        {},
        (res) => {
          if (res && res.constructor === Array) {
            return;
          }
          this.form = res;
          if (res.fileUrl) {
            this.uploader = res.fileUrl.map((v) => {
              return { url: v };
            });
          }
          this.form_value_name["userType_name"] = this.userTypeList.find(
            (v) => v.value == res.userType
          ).text;
          //省市区回显
          this.form_value_name.area_name = this.getAreaInfo(
            res.prov_id,
            res.city_id
          );
          this.form_value_name.bankArea_name = this.getAreaInfo(
            res.bank_prov_id,
            res.bank_city_id
          );
          let bankAcctTypeItem = this.bankAcctTypeList.find(
            (v) => v.value == res.bankAcctType
          );
          this.form_value_name["bankAcctType_name"] = bankAcctTypeItem
            ? bankAcctTypeItem.text
            : "";

          let bankCodeItem = this.bankCodeList.find(
            (v) => v.value == res.bankCode
          );
          this.form_value_name.bankCode_name = bankCodeItem
            ? bankCodeItem.text
            : "";
        }
      );
    },
    getAreaInfo(data1, data2) {
      let item1 = this.areaList.find((v) => v.value == data1);
      let item2 = item1 && item1.children?item1.children.find((v) => v.value == data2):'';
      return `${item1 ? item1.text : ""}${item2 ? "/" + item2.text : ""}`;
    },
    //绑定银行卡
    bindBank(e) {
      e.preventDefault();
      common.httpV20(
        "/merchant/merchant.MerchantYibao/bindBank",
        this.form,
        (res) => {
          vant.Toast.success(L_("绑定成功!"));
        }
      );
    },
  },
});
