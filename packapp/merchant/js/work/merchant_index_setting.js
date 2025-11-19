var client = common.checkAndroidApp() ? 2 : common.checkIosApp() ? 1 : 0;
// 在 #app 标签下渲染一个按钮组件
new Vue({
  el: "#app",
  template: ``,
  data() {
    return {
      merchant_money_info: "",
      langValue: "",
      showPicker: false,
      columns: [],
      config: null,
      pageInfo: null,
      defaultIndex: 0,
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    this.getConfig();
      common.http("Merchantapp&a=index", {}, (res) => {
        this.pageInfo = res;
      });
  },
  methods: {
    setLang() {
      this.config = common.getCache("config", true);
      this.columns = [];
      if (this.config) {
        if (this.config.lang_config.lang_list.length > 0) {
          this.config.lang_config.lang_list.forEach((v,i) => {
            if (v.val == this.config.lang_config.now_lang) {
              this.langValue = v.display;
              this.defaultIndex = i;
            }
            this.columns.push(v.display);
          });
        }
      }
    },
    getConfig() {
      common.http(
        "Merchantapp&a=config",
        { client: client },
        (data) => {
          common.setCache("config", data, true);
          if (data.open_multilingual == '1') {
            $("#lang").show();
          }
          this.setLang();
        },
        function (data) {}
      );
    },
    onClickLeft() {
      if (
        (common.checkApp() && window.history.length <= 1) ||
        (common.checkApp() && index != undefined)
      ) {
        if (common.checkAndroidApp()) {
          window.pigcmspackapp.closewebview(2);
        } else {
          common.iosFunction("closewebview/2", {
            name: "closewebview",
            type: "2",
          });
        }
      } else {
        window.history.go(-1);
        window.document.execCommand("Refresh");
      }
    },
    goUrl(url) {
      openWebviewUrl(url);
    },
    //多语言切换
    langClick() {
      this.showPicker = true;
    },
    onConfirm(item, index) {
      this.langValue = item;
      this.showPicker = false;
      get_lang_list(
        this.config.lang_config.lang_list[index].val,
        "changeLanguageOk"
      );
      replace_html_lang();
      setTimeout(()=>{
        location.reload()
      },600)
    },
    //安全退出
    sign_out() {
      vant.Dialog.confirm({
        title: L_("提示"),
        message: L_("您确认要退出登录吗?"),
        confirmButtonColor: "#ffa200",
      })
        .then(() => {
          let all_lang = common.getCache("all_lang",true)
          common.removeAllCache(false);
          common.removeAllCache(true);
          //存储这三个字段数据，退出登录后也可以翻译
          if(this.config.lang_config.lang_list && this.config.lang_config.lang_list.length>0){
            common.setCache("lang_list", this.config.lang_config.lang_list, true); //多语言列表
            common.setCache("now_lang", this.config.lang_config.lang_list[this.defaultIndex].val, true);//语言类型
          }
          all_lang && common.setCache("all_lang", all_lang, true); //多语言匹配数据
          setTimeout(()=> {
            window.location.replace("login.html");
          }, 300);
        })
        .catch(() => {
        });
    },
    //注销商家
    cancel() {
      common.http("Merchantapp&a=merchant_money_info", {}, (res) => {
        console.log(res);
        this.merchant_money_info = res;
        vant.Dialog.confirm({
          title: L_("提示"),
          message:
            L_("您当前剩余余额为：") +
            this.merchant_money_info.merchant_money_num +
            L_("元，商家注销后不可恢复，确认是否注销？"),
          confirmButtonColor: "#ffa200",
        })
          .then(() => {
            common.http("Merchantapp&a=cancel", {}, function (data) {
              if (data) {
                common.removeCache("ticket");
                common.removeCache("ticket", true);
                common.removeCache("config");
                common.removeCache("config", true);
                redirect_self("login.html");
              } else {
                motify.log(L_("注销商家失败."));
              }
            });
          })
          .catch(() => {
            // on cancel
          });
      });
    },
  },
});
