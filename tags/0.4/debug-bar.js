(function(){var c,a,b;c=function(f,e,d){if(f.addEventListener){f.addEventListener(e,d,false)}else{if(f.attachEvent){f.attachEvent("on"+e,function(){return d.call(f,window.event)})}}};a=function(f){var d=document.getElementById("querylist");if(d&&d.style.display=="block"){d.style.display="none"}else{d.style.display="block"}if(f.preventDefault){f.preventDefault()}f.returnValue=false};b=function(g,h){var f,e,d;f=document.getElementById(g).childNodes;for(e=0;e<f.length;e++){if(1!=f[e].nodeType){continue}f[e].style.display="none"}document.getElementById(h.href.substr(h.href.indexOf("#")+1)).style.display="block";for(d=0;d<h.parentNode.parentNode.childNodes.length;d++){if(1!=h.parentNode.parentNode.childNodes[d].nodeType){continue}h.parentNode.parentNode.childNodes[d].removeAttribute("class")}h.parentNode.setAttribute("class","current");return false};c(window,"load",function(){var d=document.getElementById("wp-admin-bar-queries");c(d,"click",a)})})();