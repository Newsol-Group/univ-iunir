var info;

function cerrar_popup()
{
	$.facebox.close();
}

function filtrar_titulos()
{
	var filtro_nombre = $('#filtro_titulo_sec-nombre').val();
	if (filtro_nombre === '') {
		$('#msg-filtro_titulo_sec-nombre').html(info.mensaje_filtrar_titulo_sec);
		$('#cg-filtro_titulo_sec-nombre').addClass('error');
	} else if (filtro_nombre.length < 3) {
		$('#msg-filtro_titulo_sec-nombre').html(info.mensaje_filtrar_caract);
		$('#cg-filtro_titulo_sec-nombre').addClass('error');
	} else {
		$('#msg-filtro_titulo_sec-nombre').html('');
		$('#cg-filtro_titulo_sec-nombre').removeClass('error');
		buscar_titulo_sec(filtro_nombre);
	}
}

function inicializar_popup_titulos(_info)
{
	info = _info;

	$('#filtro_titulo_sec').submit(function () {
		return false;
	});

	$('#filtro_titulo_sec-nombre').focus();

	$('#btnFiltrarTitulos').on('click', function() {
		filtrar_titulos();
	});
	
	$('#filtro_titulo_sec-nombre').on('keypress', function(e) {
		if (e.which === 13) {
			filtrar_titulos();
		}
	});
}

function buscar_titulo_sec(_titulo)
{
	$.ajax({
		url: info.url_titulo_sec,
		dataType: 'json',
		data: {	titulo: _titulo},
		type: 'post',
		success: function(data) {
			$('#lista_resultado_titulos').html(data.html);
			$('#tabla_resultado_titulos').jTPS( {perPages:[5], scrollStep:1, scrollDelay:30,
				clickCallback:function () {
					var table = '#tabla_resultado_titulos';
					document.cookie = 'jTPS=sortasc:' + $(table + ' .sortableHeader').index($(table + ' .sortAsc')) + ',' +
							'sortdesc:' + $(table + ' .sortableHeader').index($(table + ' .sortDesc')) + ',' +
							'page:' + $(table + ' .pageSelector').index($(table + ' .hilightPageSelector')) + ';';
				}
			});
		}
	});
}

function seleccionar_titulo_sec(_titulo)
{
	$('#'+info.retorno_valor).val(_titulo);
	buscar_titulo_sec_por_id(_titulo);
	cerrar_popup();
}

function buscar_titulo_sec_por_id(_id_titulo)
{
	$.ajax({
		url: info.url_buscar_titulo_por_id,
		dataType: 'json',
		data: {	id_titulo: _id_titulo},
		type: 'post',
		async: true,
		success: function(data) {
			$('#'+info.retorno_descr).val(data.titulo_descr);
		}
	});
}
