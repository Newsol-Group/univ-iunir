var _info;

kernel.renderer.registrar_pagelet('documentacion', function (info)
{
	_info = info;
	var id = '#' + info.id;
	return {
		onload: function ()
		{
			$('.js-btnEliminar').on('click', function ()
			{
				if (confirm(info.msj_confirmar_eliminar)) {
					return true;
				}
				return false;
			});

			$('.js-upload-input').on('change', function ()
			{
				var fileSize = this.files[0].size / 1024 / 1024;
				if (fileSize > info.tamanio_maximo) {
					alert(info.error_tamanio_maximo);
					$(this).val('');
				}else{
                                    this.closest("form").submit();                                
                                }     
			});
		}
	};
});
