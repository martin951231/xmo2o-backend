$(document).ready(function () {
  if (common.getVersion() >= 1300) {
    common.appFunction({ name: "closeStartupPage" });
  }
});
function appbackmonitor() {
  if (/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())) {
    window.lifepasslogin.closeWebView();
  } else {
    if (common.getVersion() >= 1300) {
      window.pigcmspackapp.returnToHomePage();
      return false;
    }
    if (exitLayer != -1) {
      window.pigcmspackapp.closewebview(2);
    } else {
      layer.closeAll();
      exitLayer = layer.open({
        content: L_("您确定要退出程序吗？再次按返回键将退出。"),
        btn: [L_("确定"), L_("取消")],
        yes: function (index) {
          window.pigcmspackapp.closewebview(2);
          layer.close(index);
        },
        end: function (index) {
          exitLayer = -1;
        },
      });
    }
  }
}
new Vue({
  el: "#app",
  template: ``,
  data() {
    return {
      form: {
        phone: "",
        pwd: "",
      },
      mainColor: "#ffa200",
      checkedPrivacy: false,
      config: null,
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    common.http("Merchantapp&a=config", {}, data=> {
      this.config = data;
      
      if(data.close_register){
        $('#register').remove();
        $('.div-line').remove();
      }

      $('#register').show();
      $('.div-line').show();
      if (data.open_change_zhu == "1") {
        $("#register").text(L_("我要入住"));
      } else {
        $("#register").text(L_("我要入驻"));
      }

      if (data.lang_config) {
        if (
          (!all_lang || !now_lang) &&
          data.lang_config.lang_list &&
          (data.lang_config.lang_list.length > 0 ||
            data.lang_config.now_lang != "chinese")
        ) {
          common.setCache("lang_list", data.lang_config.lang_list, true);
          get_lang_list(data.lang_config.now_lang, "lang_loaded");
        } else {
          replace_html_lang();
        }
      }
    });

    var ticket = common.getCache("ticket");
    if (ticket) {
      redirect_self("index.html");
      return false;
    }
    if (common.checkWeixin()) {
      common.fillPageBg(1, "#ebf3f8");
    }
    this.$nextTick(() => {
      $("#login_pwd").keyup((e) => {
        if (e.keyCode == 13) {
          this.loginClick();
          return false;
        }
      });
    });
  },
  methods: {
    goUrl(url) {
      openWebviewUrl(url);
    },
    loginClick() {
      if (!this.form.phone) {
        vant.Toast(L_("登录手机号/账号不能为空"));
        return;
      }
      if (!this.form.pwd) {
        vant.Toast(L_("登录密码不能为空"));
        return;
      }
      if (!this.checkedPrivacy) {
        vant.Toast(L_("请先阅读并勾选页面底部协议"));
        return;
      }
      var client = common.checkAndroidApp() ? 2 : common.checkIosApp() ? 1 : 0;
      if (common.checkWeixinApp()) {
        common.removeCache("ticket", true);
        common.removeCache("device_id", true);
      }
      common.http(
        "Merchantapp&a=login",
        { phone: this.form.phone, pwd: this.form.pwd, client: client },
        (data) => {
          // 判断密码是不是123456
          if (this.form.pwd == "123456") {
            common.setCache("is_Simple_Password", "1", true);
          }
          console.log(data);
          common.setCache("ticket", data.ticket, true);
          common.setCache("merchant_info", data.user, true);
          common.setCache("ticket", data.ticket);
          common.setCache("mer_id", data.mer_id);
          common.setCache("power", data.auth);
          common.setCache("card_recharge", data.auth.card_recharge);
          redirect_self((urlParam.back ? urlParam.back : "index") + ".html");
        },err=>{
          vant.Toast(err.errorMsg);
        }
      );
    },
  },
});
