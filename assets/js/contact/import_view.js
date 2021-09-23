import '../../css/contact/import_view.css';

import $ from 'jquery';
import 'devbridge-autocomplete';

const routes = require('../../../public/js/fos_js_routes.json');
import Routing from '../../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';


$(document).ready(function(){
	Routing.setRoutingData(routes);
	$('.js-file').on('change', function(e){
		e.preventDefault();
		var fullPath = $('#contact_import_file')[0].value;
		if (fullPath) {
			var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
			var filename = fullPath.substring(startIndex);
			if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
			filename = filename.substring(1);
			}
			$('#js-label').text(filename);
		}
	});
	var wrapper = $('.js-labels-wrapper');
	wrapper.on('click','.js-label-remove', function (e) {
		e.preventDefault();
		$(this).closest('.js-label-item').remove();
	});
	wrapper.on('click','.js-label-add', function (e) {
		e.preventDefault();
		var prototype = wrapper.data('prototype');
		var index = wrapper.data('index');
		var newForm = prototype;
		newForm = newForm.replace(/__name__/g, index);
		wrapper.data('index', index + 1);
		$(this).before(newForm);
		$('.js-autocomplete').autocomplete({
			minChars: 2,
			serviceUrl: global.app_base + Routing.generate('api_get_labels'),
			paramName: "name",
			transformResult: function(response) {
				var json_data = JSON.parse(response);
				return {
					suggestions: $.map(json_data.labels, function(dataItem) {
						// console.log(dataItem);
						return { value: dataItem.name, data: dataItem.id };
					})
				};
			},
		onSelect: function (suggestion) {
			var input_name = $(this).attr('id');
			var input_id = input_name.replace('name','id');
			$(document).find('#'+input_id).val(suggestion.data);
			
		}
		});
	});
	
});