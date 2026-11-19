var info;

function cerrar_popup()
{
	$.facebox.close();
}

function inicializar_popup_cps(_info)
{
	info = _info;
	inicializar_cps();
	
	$('#btnAceptar').on('click', function() {
		var valor_cp = $('#form_cp-codigo_postal').val();
		if (valor_cp === no_seleccion) {
			valor_cp = '';
		}
		$('#'+info.retorno_cp).val(valor_cp);

		cerrar_popup();
	});
}

function inicializar_cps()
{
	buscar_codigos_postales(info.localidad);
}

function buscar_codigos_postales(_localidad)
{
	$.ajax({
		url: info.url_cod_postales,
		dataType: 'json',
		data: {localidad: _localidad},
		type: 'post',
		success: function(data) {
			$('#form_cp-codigo_postal').children().remove();					
			$.each(data, function(key, value) {
				$('#form_cp-codigo_postal').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_cp-codigo_postal');
		}
	});	
}
