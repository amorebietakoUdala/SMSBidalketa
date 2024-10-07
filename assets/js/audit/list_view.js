import '../../css/audit/list_view.css';

import $ from 'jquery';
import 'bootstrap-table';
import 'bootstrap-table/dist/extensions/export/bootstrap-table-export'
import 'bootstrap-table/dist/locale/bootstrap-table-es-ES';
import 'bootstrap-table/dist/locale/bootstrap-table-eu-EU';
import 'tableexport.jquery.plugin/tableExport.min';
import 'jquery-ui';
import 'select2';

// There's a problem with dynamic import's in webpack and IE 11
// https://github.com/babel/babel/issues/10140
// Until it's fixed, this import is necesary.
import Swal from 'sweetalert2';

function createContactListHtml (telephones) {
	var html = '<ul class="list-group">';
	var elements = '';
	var i;
	for (i=0; i < telephones.length; i++) {
		elements += '<li class="list-group-item">'+telephones[i]+'</li>';
	}
	return html+elements+'</ul>';
}

$(document).ready(function(){
	$('#audit_search_user').select2();
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
	$('#taula').on('click','.js-fireAlert',function(e){
		e.preventDefault();
		var url = e.currentTarget.dataset.url;
		var title = e.currentTarget.dataset.title;
		$.ajax({
			url: url,
			type: 'GET',
			dataType: 'json',
			success: function (json) {
				var html = createContactListHtml(json);
				import('sweetalert2').then((Swal) => {
					Swal.default.fire({
						title: title,
						html: html
					});
				});
			}
		});
		
		});
	});

