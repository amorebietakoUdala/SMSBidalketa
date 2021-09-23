import '../../css/label/list_view.css';

import $ from 'jquery';
import 'bootstrap-table';
import 'bootstrap-table/dist/extensions/export/bootstrap-table-export'
import 'bootstrap-table/dist/locale/bootstrap-table-eu-EU';
import 'bootstrap-table/dist/locale/bootstrap-table-es-ES';
import 'tableexport.jquery.plugin/tableExport.min';
import 'jquery-ui';
// import Swal from 'sweetalert2';

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
			document.location.href=url;
		}
		});
	});
}

$(document).ready(function(){
	$('#taula').bootstrapTable({
		cache : false,
		showExport: true,
		exportTypes: ['excel'],
		exportDataType: 'all',
		exportOptions: {
			fileName: "concepts",
			ignoreColumn: ['options']
		},
		showColumns: false,
		pagination: true,
		search: true,
		striped: true,
		sortStable: true,
		pageSize: 10,
		pageList: [10,25,50,100],
		sortable: true,
		locale: $('html').attr('lang')+'-'+$('html').attr('lang').toUpperCase(),
	});
	var $table = $('#taula');
	$(function () {
		$('#toolbar').find('select').change(function () {
			$table.bootstrapTable('destroy').bootstrapTable({
			exportDataType: $(this).val(),
			});
		});
	});
	$('#taula').on('click','.js-delete',function(e){
		e.preventDefault();
		var url = e.currentTarget.dataset.url;
		var confirmation = e.currentTarget.dataset.confirmation;
		var message = e.currentTarget.dataset.message;
		var confirm = e.currentTarget.dataset.confirm;
		var cancel = e.currentTarget.dataset.cancel;
		fireAlert(confirmation,message,confirm,cancel,url);
	});

});
