import $ from 'jquery';
import 'bootstrap';
import 'popper.js';

import '../css/app.css';
/* Global public directory for assets */
global.app_base = '/smsbidalketa';

$(document).ready(function(){
    $('#js-locale-es').on('click',function (e) {
		e.preventDefault();
		var current_locale = $('html').attr("lang");
		if ( current_locale === 'es') {
			return;
		}
		var location = window.location.href;
		var location_new = location.replace("/eu/","/es/");
		window.location.href=location_new;
    });
    $('#js-locale-eu').on('click',function (e) {
		e.preventDefault();
		var current_locale = $('html').attr("lang");
		if ( current_locale === 'eu') {
			return;
		}
		var location = window.location.href;
		var location_new = location.replace("/es/","/eu/");
		window.location.href=location_new;
    });
	$('.js-back').on('click',function(e){
		e.preventDefault();
		var url = e.currentTarget.dataset.url;
		document.location.href=url;
	});
});