/**
 * Created by win7 on 2018-10-20.
 */
(function(){
    var catImg={};
    (function(){
        var Uploader=function(options){
            this.options= $.extend({}, Uploader.default,options)
            this.$fileInput = $(this.options.element);
            this.fileNode = this.$fileInput.selfHtml();

            this.$parent = this.$fileInput.parent();

            this.selectedFiles = [];

            this.init();
        };
        Uploader.default={
            $container :'',
           $avatarForm :'',
           $avatarSrc :'',
           $avatarData :'',
           $avatarHeight:'',
           $avatarWidth :'',
           $avatarY :'',
           $avatarX :'',
           $avatarRoute :'',
           $avatarInput :'',
           $avatarBtns :'',
           $clone :'',
           $zoom:'',
           $avatarWrapper:''
        };
        Uploader.prototype.init=function(){

        }
    }())
    window.catImg=catImg;
    return catImg
})()

