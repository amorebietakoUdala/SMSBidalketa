import '../../css/sending/sending_view.css';

import $ from 'jquery';

import 'bootstrap-table';
import 'tableexport.jquery.plugin/tableExport.min';
import 'bootstrap-table/dist/extensions/export/bootstrap-table-export'
import 'bootstrap-table/dist/locale/bootstrap-table-es-ES';
import 'bootstrap-table/dist/locale/bootstrap-table-eu-EU';
//import 'bootstrap-table/dist/extensions/multiple-selection-row/bootstrap-table-multiple-selection-row'
//import 'bootstrap-table/dist/extensions/select2-filter/bootstrap-table-select2-filter'
import 'jquery-ui';
import '../components/select2';

// There's a problem with dynamic import's in webpack and IE 11
// https://github.com/babel/babel/issues/10140
// Until it's fixed, this import is necesary.
import Swal from 'sweetalert2';

function fireAlert (title,html,confirmationButtonText, cancelButtonText, url) {
	import('sweetalert2').then((Swal) => {
		Swal.default.fire({
		  title: title,
		  html: html,
		  type: 'warning',
		  showCancelButton: true,
		  cancelButtonText: cancelButtonText,
		  confirmButtonColor: '#3085d6',
		  cancelButtonColor: '#d33',
		  confirmButtonText: confirmationButtonText,
		}).then((result) => {
			if (result.value) {
				var form = $('#form');
				var selections = $('#taula').bootstrapTable('getSelections');
				$('#sending_selected').val(JSON.stringify(selections));
				$(form).attr('action',url);
				form.submit();
		}	
		});
	});
}

function checkMessageLength(content) {
	let size = 0;
	for (var i = 0, len = content.length; i < len; i++) {
		var charcode = content.charCodeAt(i);
		// Those characters count double we add one more
		if (charcode == 10 || charcode == 12 || charcode == 94 || charcode == 123 || charcode == 125 || charcode == 92 || charcode == 91 || charcode == 126 || charcode == 93 || charcode == 124 || charcode == 8364 ){
			size+=1;
		}
		size+=1;
	}
	return size;
}

$(document).ready(function(){
   $('#sending_labels').select2();
	// Not needed stimulus controller makes the bootstraptable
	// $('#taula').bootstrapTable({
	// 	cache : false,
	// 	showExport: true,
	// 	exportTypes: ['excel'],
	// 	exportDataType: 'all',
	// 	exportOptions: {
	// 		fileName: "destinatarios",
	// 	},
	// 	pagination: false,
	// 	search: true,
	// 	striped: true,
	// 	sortStable: true,
	// 	sortable: true,
	// 	locale: $('html').attr('lang')+'-'+$('html').attr('lang').toUpperCase(),
	// 	multipleSelectRow: true,
	// });
	var $table = $('#taula');
	$(function () {
		$('#toolbar').find('select').change(function () {
			$table.bootstrapTable('destroy').bootstrapTable({
			exportDataType: $(this).val(),
			});
		});
	});
	var remaining_characters_text = $('#sending_message_help').text();
	let content = $('#sending_message').val();
	var remaining_characters = 160 - checkMessageLength(content);
	$('#sending_message_help').text($('#sending_message_help').text() + ": " + remaining_characters);

	$('#sending_message').on('keyup', function () {
		let content = $('#sending_message').val();
		let size = checkMessageLength(content);
		remaining_characters = 160 - size;
		$('#sending_message_help').text(remaining_characters_text + ": " + remaining_characters);
		if (remaining_characters <= 0) {
			$('#sending_message').attr('maxlength',content.length);
			$('#js-btn-send').prop("disabled",true);
		} else {
			$('#js-btn-send').prop("disabled",false);
		}
	});
	
	$('#js-btn-send').on('click',function(e){
		e.preventDefault();
		var message = $('#sending_message').val();
		if ( message.trim().length === 0 ) {
			var no_message = e.currentTarget.dataset.no_message;
			var error = e.currentTarget.dataset.error;
			import('sweetalert2').then((Swal) => {
				Swal.default.fire(
					error,
					no_message,
					'error'
				  )
			});
			return;
		}
		var telephone = $('#sending_telephone').val();
		var regExp = /^(71|72|73|74)\d{7}$|^6\d{8}$/gm;
		if (telephone.length > 0 && !regExp.test(telephone)) {
			import('sweetalert2').then((Swal) => {
				Swal.default.fire(
					error,
					'El teléfono introducido no es válido',
					'error'
				  )
			});
			$('#sending_telephone').focus();
			return;
		}
		var selections = $('#taula').bootstrapTable('getSelections');
		if ( selections.length === 0 && telephone.length === 0) {
			import('sweetalert2').then((Swal) => {
				Swal.default.fire(
					error,
					'No se han seleccionado destinatarios',
					'error'
				  )
			});
			return;
		}
		var url = e.currentTarget.dataset.url;
		var confirmation = e.currentTarget.dataset.confirmation;
		if ( telephone.length === 0 ) {
			var message = e.currentTarget.dataset.message.replace('%message_count%',selections.length);
		} else {
			var message = e.currentTarget.dataset.message.replace('%message_count%',selections.length + 1);
		}
		var confirm = e.currentTarget.dataset.confirm;
		var cancel = e.currentTarget.dataset.cancel;
		fireAlert(confirmation,message,confirm,cancel,url);
	});

	$('#js-btn-search').on('click',function(e){
		e.preventDefault();
		var form = $('#form');
		$(form).attr('action',e.currentTarget.dataset.url);
		form.submit();
	});

	$('#taula').bootstrapTable('checkAll');
});
