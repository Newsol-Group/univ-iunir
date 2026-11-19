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
			var cant = data.length;
			if (cant === 0) {
				var html = "<div class='alert alert-info mensaje strong'>"+info.loc_sin_cp+"</div>";
				$('#contenido_cp').html(html);
				$('#contenido_cp').removeClass('hide');
			} else {
				$('#form_cp-codigo_postal').children().remove();					
				$.each(data, function(key, value) {
					$('#form_cp-codigo_postal').append(
						$('<option></option>').val(key).html(value)
					);
				});
				ordenar_combo('form_cp-codigo_postal');
				$('#contenido_cp').removeClass('hide');
			}
		}
	});	
}
