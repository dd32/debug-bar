var wpDebugBar;(function(b){var c,a,d;wpDebugBar=a={body:undefined,init:function(){c=b("#querylist");d=b(window);a.body=b(document.body);a.toggle.init();a.tabs();a.actions.init()},toggle:{init:function(){b("#wp-admin-bar-debug-bar").click(function(f){f.preventDefault();a.toggle.visibility()})},visibility:function(e){e=typeof e=="undefined"?!a.body.hasClass("debug-bar-visible"):e;a.body.toggleClass("debug-bar-visible",e);b(this).toggleClass("active",e)}},tabs:function(){var f=b(".debug-menu-link"),e=b(".debug-menu-target");f.click(function(h){var g=b(this);h.preventDefault();if(g.hasClass("current")){return}e.hide().trigger("debug-bar-hide");f.removeClass("current");g.addClass("current");b("#"+this.href.substr(this.href.indexOf("#")+1)).show().trigger("debug-bar-show")})},actions:{init:function(){var e=b("#debug-bar-actions");b(".maximize",e).click(a.actions.maximize);b(".restore",e).click(a.actions.restore);b(".close",e).click(a.actions.close)},maximize:function(){a.body.removeClass("debug-bar-partial");a.body.addClass("debug-bar-maximized")},restore:function(){a.body.removeClass("debug-bar-maximized");a.body.addClass("debug-bar-partial")},close:function(){a.toggle.visibility(false);console.log("boo")}}};wpDebugBar.Panel=function(){};b(document).ready(wpDebugBar.init)})(jQuery);