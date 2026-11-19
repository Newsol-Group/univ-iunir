var info;

function cerrar_popup()
{
	$.facebox.close();
}

function filtrar_colegio()
{
	var filtro_nombre = $('#filtro_colegio-nombre').val();
	var localidad_id = $('#filtro_colegio-localidad_id').val();
	if (localidad_id === '') {
		$('#msg-filtro_colegio-localidad').html(info.mensaje_filtrar_colegio);
		$('#cg-filtro_colegio-localidad').addClass('error');
	} else {
		$('#msg-filtro_colegio-nombre').html('');
		$('#cg-filtro_colegio-localidad').removeClass('error');
		var filtro_localidad = $('#filtro_colegio-localidad_id').val();
		buscar_colegios(filtro_nombre, filtro_localidad);
	}
}

function inicializar_popup_colegios(_info)
{
	info = _info;

	$('#filtro_colegio').submit(function () {
		return false;
	});

	$('#filtro_colegio-localidad').focus();

	$('#btnFiltrar').on('click', function () {
		filtrar_colegio();
	});

	$('#btnLimpiarLocalidad').on('click', function () {
		$("#filtro_colegio-localidad_id").val("");
		$("#filtro_colegio-localidad").val("");
		set_solo_lectura("filtro_colegio-localidad", false);
		$("#btnLimpiarLocalidad").addClass("hide");
	});

	$('#filtro_colegio').contents().on('keypress', function (e) {
		if (e.which === 13) {
			filtrar_colegio();
		}
	});

	$("#filtro_colegio-localidad").autocomplete({
		source: function (request, response) {
			$.ajax({
				url: info.url_lista_localidades,
				dataType: "json",
				data: {
					term: request.term
				},
				success: function (data) {
					response(data);
				}
			});
		},
		minLength: 3,
		select: localidad_seleccionada,
		focus: localidad_foco
	});
}

function buscar_colegios(_colegio, _localidad)
{
	$.ajax({
		url: info.url_colegios,
		dataType: 'json',
		data: {colegio: _colegio,
			localidad: _localidad},
		type: 'post',
		success: function (data) {
			$('#lista_resultados').html(data.html);
			$('#tabla_resultado_colegios').jTPS({perPages: [5], scrollStep: 1, scrollDelay: 30,
				clickCallback: function () {
					var table = '#tabla_resultado_colegios';
					document.cookie = 'jTPS=sortasc:' + $(table + ' .sortableHeader').index($(table + ' .sortAsc')) + ',' +
							'sortdesc:' + $(table + ' .sortableHeader').index($(table + ' .sortDesc')) + ',' +
							'page:' + $(table + ' .pageSelector').index($(table + ' .hilightPageSelector')) + ';';
				}
			});
		}
	});
}

function seleccionar_colegio(_colegio)
{
	$('#' + info.retorno_valor).val(_colegio);
	buscar_colegio_por_id(_colegio);
	cerrar_popup();
}

function buscar_colegio_por_id(_id_colegio)
{
	$.ajax({
		url: info.url_buscar_colegio_por_id,
		dataType: 'json',
		data: {id_colegio: _id_colegio},
		type: 'post',
		async: true,
		success: function (data) {
			$('#' + info.retorno_descr).val(data.colegio_descr);
		}
	});
}

//------------------------------------------------------------------------------
//---- Funciones del autocomplete de localidades -------------------------------
//------------------------------------------------------------------------------

function localidad_seleccionada(event, ui)
{
	var localidad = ui.item.value.localidad;
	var localidad_descr = ui.item.value.localidad_descr;

	$("#filtro_colegio-localidad_id").val(localidad);
	$("#filtro_colegio-localidad").val(localidad_descr);
	set_solo_lectura("filtro_colegio-localidad", true);
	$("#btnLimpiarLocalidad").removeClass("hide");
	$('#cg-filtro_colegio-localidad').removeClass('error');

	event.preventDefault();
}

function localidad_foco(event)
{
	event.preventDefault();
}
