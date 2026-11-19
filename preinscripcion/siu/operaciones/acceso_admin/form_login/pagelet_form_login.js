kernel.renderer.registrar_pagelet('form_login', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#form_login_admin').submit(function () {
				
			});
			$('#usuario').focus();
		}
	}
})
