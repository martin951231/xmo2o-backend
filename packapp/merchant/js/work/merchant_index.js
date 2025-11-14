  if (common.getVersion() >= 1300) {
  common.appFunction({ name: "closeStartupPage" });
}
var client = common.checkAndroidApp() ? 2 : common.checkIosApp() ? 1 : 0;
var tickets = urlParam.ticket;
var forms = urlParam.from;
var store_device = urlParam.store_device_id
  ? urlParam.store_device_id
  : urlParam.store_device;
// 店员端打开app携带的店铺id
var store_idAll = urlParam.store_id ? urlParam.store_id : "";
if (forms == "storestaff") {
  if (store_idAll) {
    common.setCache("store_idAll", store_idAll);
  }
  common.setCache("ticket", tickets, true);
  common.setCache("ticket", tickets);
  common.setCache("device_id", store_device, true);
}

new Vue({
  el: "#app",
  template: ``,
  data() {
    return {
      menuList: [],
      //弹出层
      popupShow: false,
      sortableEl: null,
      pageInfo: null,
      merchant_money_info: null,
      customMenuList: [], //自定义菜单
      customAllMenuList: [], //全部菜单
      menuEdit: false,
      sortableObj: null,
      menuIdList: [],
      timer: null,
      mainColor: "#ffa200",
      callPhoneShow: false,
      config: null,
      site_phone: [],
      admin_realname: "",
      admin_invit_code: "",
      internal_membership_card:false,
      show_lakala_qianzhangtong:false,
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    this.getConfig();
  },
  methods: {
    getDetail() {
      common.http("Merchantapp&a=index", {}, (res) => {
        this.pageInfo = res;
        this.admin_realname = res.admin_name;
        this.admin_invit_code = res.admin_invit_code;
        //在线签署合同 contract_sign_alert 0-不弹出，1-弹出续费，2-已到期
        if (this.pageInfo.contract_sign_alert == 1 || this.pageInfo.contract_sign_alert == 2) {
          vant.Dialog.alert({
            title: L_("温馨提示"),
            message: this.pageInfo.contract_sign_tip,
            confirmButtonColor: this.mainColor,
            confirmButtonText: this.pageInfo.contract_sign_alert == 1?L_("去签署合同") : L_("去我的余额"),
          }).then(() => {
            if(this.pageInfo.contract_sign_alert == 1){
              openWebviewUrl("contract.html");
            }else{
              openWebviewUrl("my_balance.html");
            }
          });
        }
        common.setData(res.count_number);
        common.setCache("indexData", res, true);
        //缓存二维码
        common.setCache("qrcodeinfo", res.qrcodeinfo, true);
        //获取自定义菜单
        this.getCustomMenuList();
        //显示头部的金额订单统计
        common.http("Merchantapp&a=merchant_money_info", {}, (res) => {
          this.merchant_money_info = res;
          this.show_lakala_qianzhangtong = res.show_lakala_qianzhangtong;
        });
      });
    },
    getConfig() {
      /*先初始化判断有没有缓存，有的话先展示缓存内容*/
      var app_config = common.getCache("config", true);
      // 判断密码是123456 弹出提示
      var is_Simple_Password = common.getCache("is_Simple_Password", true);
      if (is_Simple_Password == 1) {
        vant.Dialog.confirm({
          title: L_("提示"),
          message: L_(
            "您的登录密码是系统默认密码,为了账户安全,请您设置新的登录密码"
          ),
          confirmButtonColor: this.mainColor,
          cancelButtonText: L_("跳过"),
          confirmButtonText: L_("去修改"),
        })
          .then(() => {
            openWebviewUrl("modify_password.html?back=2")
            common.setCache("is_Simple_Password", "0", true);
          })
          .catch(() => {
            common.setCache("is_Simple_Password", "0", true);
          });
      } else {
      }
      //消息未读
      this.getUnreadMsg();

      if (app_config) {
        this.internal_membership_card = app_config.internal_membership_card
        this.show_lakala_qianzhangtong = app_config.show_lakala_qianzhangtong
        common.setCache('ashop',app_config.shop_name || '');
        this.initialize();
        this.getDetail();
      } else {
        common.http(
          "Merchantapp&a=config",
          { client: client },
          (data) => {
            app_config = data;
            this.internal_membership_card = app_config.internal_membership_card
            this.show_lakala_qianzhangtong = app_config.show_lakala_qianzhangtong
            common.setCache("config", data, true);
            common.setCache('ashop',data.shop_name);
            this.initialize();
            this.getDetail();
          },
          function (data) {}
        );
      }
    },
    //获取自定义菜单
    getCustomMenuList() {
      common.httpV20(
        "/merchant/api.merchant/customMenuIndexList",
        {},
        (res) => {
          this.menuList = JSON.parse(JSON.stringify(res));
          this.customMenuList = JSON.parse(JSON.stringify(res));
        }
      );
    },
    //获取要编辑的菜单
    getCustomAllMenuList() {
      common.httpV20("/merchant/api.merchant/customMenuList", {}, (res) => {
        this.customAllMenuList = res;
        this.popupShow = true;
      });
    },
    //消息未读量
    getUnreadMsg() {
      let success = (res) => {
        if (res.num > 0) {
          $("#msg_list").addClass("mui-pull-msg-dot");
        } else {
          $("#msg_list").removeClass("mui-pull-msg-dot");
        }
        this.timer = null;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
          this.getUnreadMsg();
        }, 1000 * 60);
      };
      common.httpV20(
        "/warn/merchant.Notice/unreadNum",
        { noTip: true },
        success
      );
    },
    //初始化登录逻辑
    initialize() {
      if (common.checkLogin() == false) {
        return false;
      }
      var merchantArr = common.getCache("merchant_info", true);
      if (false && merchantArr) {
      } else {
        common.http(
          "Merchantapp&a=login",
          { noTip: true },
          function (data) {
            common.setCache("ticket", data.ticket, true);
            common.setCache("merchant_info", data.user, true);
            common.setCache("ticket", data.ticket);
            common.setCache("mer_id", data.mer_id);
            common.setCache("power", data.auth);
            common.setCache("card_recharge", data.auth.card_recharge);
          },
          function (data) {
            common.removeCache("ticket");
            common.removeCache("ticket", true);
            redirect_self("login.html");
          }
        );
      }
    },
    //用户协议
    goUserAgreement() {
      openWebviewUrl("userAgreement.html");
    },
    //隐私政策
    goAgreement() {
      openWebviewUrl("agreement.html");
    },
    //菜单点击
    toMenuUrl(item, index) {
      //点击更多
      if (item.type == "more") {
        this.getCustomMenuList();
        this.getCustomAllMenuList();
        return
      }

      //绑定业务员
      if (item.type == "invite_code") {
        if (
          this.admin_realname != undefined &&
          this.admin_invit_code != undefined &&
          this.admin_realname != "" &&
          this.admin_invit_code != ""
        ) {
          vant.Dialog.confirm({
            title: L_("提示"),
            message:
              L_("业务员名称：") +
              this.admin_realname +
              "\n" +
              L_("邀请码：") +
              this.admin_invit_code,
            confirmButtonColor: this.mainColor,
            confirmButtonText: L_("绑定业务员"),
            cancelButtonText: L_("知道了"),
          }).then(() => {
            openWindow({
              url: item.path,
              id: "invit_code",
            });
          });
        } else {
          openWindow({
            url: item.path,
            id: "invit_code",
          });
        }
        return;
      }

      //联系平台
      if (item.type == "site_phone") {
        this.config = common.getCache("config", true);
        if (
          this.config.site_phone &&
          this.config.site_phone.split(" ").length > 1
        ) {
          this.callPhoneShow = true;
          this.site_phone = this.config.site_phone.split(" ");
        } else {
          location.href = "tel:" + this.config.site_phone;
        }
        return
      }

      //页面跳转
      if (item.path && !this.menuEdit) {
        //path包含了http则直接跳转
        if (item.path.indexOf('http') !== -1) {
          openWebviewUrl(item.path);
        } else {
          openWebviewUrl(requestStaticDomain + item.path);
        }
      }
    },
    closed() {
      this.menuEdit = false;
      if (!this.sortableObj) return;
      this.sortableObj.options.disabled = true;
      this.customMenuList = [];
      setTimeout(() => {
        this.customMenuList = this.menuList;
      }, 300);
    },
    menuEditClick() {
      this.menuEdit = true;
      if (this.sortableObj) {
        if (this.menuEdit) {
          this.sortableObj.options.disabled = false;
        } else {
          this.sortableObj.options.disabled = true;
        }
      } else {
        this.$nextTick(() => {
          this.sortableFun();
        });
      }
    },
    //添加菜单
    addMenu(items, indexs, index) {
      if (items.is_select == 0) {
        this.customMenuList.push(items);
        this.$set(this.customAllMenuList[index].menus[indexs], "is_select", 1);
      }
    },
    //编辑删除菜单
    editMenu(items, indexs) {
      if(this.customMenuList.length == 1){
        vant.Toast(L_("至少保留一个菜单！"));
        return
      }
      this.$delete(
        this.customMenuList,
        this.customMenuList.findIndex((v) => v.menu_id == items.menu_id)
      );
      this.customAllMenuList.forEach((item, index) => {
        item.menus.forEach((v, i) => {
          if (v.menu_id == items.menu_id) {
            //跳出双层循环
            this.$set(this.customAllMenuList[index].menus[i], "is_select", 0);
            throw new Error("for循环end");
          }
        });
      });
    },
    //保存菜单
    addMenuSave() {
      this.menuIdList = [];
      // 拖拽结束
      if ($("#menu-home-box") && $("#menu-home-box").children())
        $("#menu-home-box")
          .children()
          .each((index, item) => {
            this.menuIdList.push(item.id);
          });
      common.httpV20(
        "/merchant/api.merchant/customMenuEdit",
        { menu_ids: this.menuIdList },
        (res) => {
          this.popupShow = false;
          this.getCustomMenuList();
        }
      );
    },
    //拖拽类---工具
    sortableFun() {
      this.sortableEl = document.getElementById("menu-home-box");
      //常用
      this.sortableObj = new Sortable(this.sortableEl, {
        // handle: ".my-handle", // 拖拽区域，默认为 items 的 子元素
        draggable: ".popup-menu-item",
        onStart: function (/**Event*/ evt) {
          // 拖拽开始
          var itemEl = evt.item; // 当前拖拽的html元素
        },

        onEnd: (evt) => {},
      });
    },
    goUrl(url,type) {
      if(type == 'setting'){
        window.location.href = url;
      }else{
        openWebviewUrl(url)
      }
    },
    callPhone(item){
      location.href = "tel:" + item;
    },
  },
});
