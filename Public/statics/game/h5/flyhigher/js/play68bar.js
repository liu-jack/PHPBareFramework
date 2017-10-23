function isWeixin() {
	var e = navigator.userAgent.toLowerCase();
	if (e.match(/MicroMessenger/i) == "micromessenger") {
		return true
	} else {
		return false
	}
}
function toggle(e) {
	var t = document.getElementById(e);
	var n = document.getElementById("arrow");
	var r = t.getAttribute("class");
	if (r == "hide") {
		t.setAttribute("class", "show");
		delay(n, RESOURCE_IMG_PATH + "arrowright.png", 400)
	} else {
		t.setAttribute("class", "hide");
		delay(n, RESOURCE_IMG_PATH + "arrowleft.png", 400)
	}
}
function delay(e, t, n) {
	window.setTimeout(function() {
		e.setAttribute("src", t)
	},
	n)
}


if (getCookie("num")) {
		var nn = parseInt(getCookie("num"));
		setCookie("num", ++nn);
	} else {
		setCookie("num", 1);
	}
	function getCookie(name) 
	{ 
		var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)"); 
		if(arr=document.cookie.match(reg)) return unescape(arr[2]); 
		else return null; 
	} 
	function setCookie(name, value) {
		var Days = 30;
		var exp = new Date();
		exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
		document.cookie = name + "=" + escape(value) + ";expires" + exp.toGMTString();
	}
	

function isMobile() {
	return navigator.userAgent.match(/android|iphone|ipod|blackberry|meego|symbianos|windowsphone|ucbrowser/i)
}
function isIOS() {
	return navigator.userAgent.match(/iphone|ipod|ios/i)
}

if (getCookie("num") != 3){
	window.onbeforeunload=function(){return"离开此页面将会退出游戏哦"}
}