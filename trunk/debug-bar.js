(function(c){var e,a,d,f,b;a={adminBarHeight:0,minHeight:0,marginBottom:0,inUpper:function(){return e.offset().top-f.scrollTop()>=a.adminBarHeight},inLower:function(){return e.outerHeight()>=a.minHeight&&f.height()>=a.minHeight},fix:function(){if(!a.inUpper()){e.height(f.height()-a.adminBarHeight)}if(!a.inLower()){e.height(a.minHeight)}b.css("margin-bottom",e.height()+a.marginBottom)}};d={init:function(){e=c("#querylist");f=c(window);b=c(document.body);a.minHeight=c("#debug-bar-handle").outerHeight()+c("#debug-bar-menu").outerHeight();a.adminBarHeight=c("#wpadminbar").outerHeight();a.marginBottom=parseInt(b.css("margin-bottom"),10);d.dock();d.toggle();d.tabs()},dock:function(){e.dockable({handle:"#debug-bar-handle",resize:function(h,g){return a.inUpper()&&a.inLower()},resized:function(h,g){a.fix()}});f.resize(function(){if(e.is(":visible")){a.fix()}})},toggle:function(){c("#wp-admin-bar-debug-bar").click(function(h){var g=e.is(":hidden");h.preventDefault();e.toggle(g);c(this).toggleClass("active",g);if(g){a.fix()}})},tabs:function(){var h=c(".debug-menu-link"),g=c(".debug-menu-target");h.click(function(j){var i=c(this);j.preventDefault();if(i.hasClass("current")){return}g.hide();h.removeClass("current");i.addClass("current");c("#"+this.href.substr(this.href.indexOf("#")+1)).show()})}};c(document).ready(d.init)})(jQuery);