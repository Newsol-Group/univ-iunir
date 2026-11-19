<?php
class form_upload extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$max_filesize = toba::consulta_php('co_configuracion')->get_tamanio_maximo_archivo();
		echo "
			$('.js-upload-input').bind('change', function ()
			{
				var fileSize = this.files[0].size / 1024 / 1024;
				if (fileSize > $max_filesize) {
					alert('El tamaño del archivo supera el máximo permitido');
					$(this).val('');
				}
			});
		";
	}

}
?>