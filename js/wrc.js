"use strict";
function isJson(str) {
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	return true;
}
function setCookie(cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
	var expires = "expires=" + d.toUTCString();
	document.cookie = cname + "=" + cvalue + "; " + expires;
}
jQuery(document).ready(function () {
	jQuery('#post_feed_exclude_author').select2();
	jQuery('select').select2();
	jQuery('#wrc_selected_posttype').live('change', function () {
		jQuery('#wrc_' + this.value).show();
		var list = document.getElementById('wrc_selected_posttype').options;
		console.log(list);
		var selected_posttype = jQuery('#wrc_selected_posttype').val();
		for (var i = 0; i < list.length; i++) {
			if (list[i].value != selected_posttype) {
				jQuery('#wrc_' + list[i].value).hide();
			}
		}
	}).change();

});