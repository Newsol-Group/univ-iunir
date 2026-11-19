var info;
var no_seleccion = 'null';

function cerrar_popup()
{
	$.facebox.close();
}

function inicializar_popup_localidades(_info)
{
	info = _info;
	registrar_cascadas();
	inicializar();

	$('#btnAceptar').on('click', function() {
		if (validar()) {
			var valor_localidad = $('#form_localidad-localidad').val();
			
			if (info.ingresa_cp) {
				var valor_cp = $('#form_localidad-codigo_postal').val();
				if (valor_cp === no_seleccion) {
					valor_cp = ''
				}
				$('#'+info.retorno_cp).val(valor_cp);
			}
			
			$('#'+info.retorno_valor).val(valor_localidad);
			buscar_descr_localidad(valor_localidad, info)
			$("#"+info.ayuda).hide();
			$("#"+info.campo_pais).hide();
			set_solo_lectura(info.retorno_descr, true);
			
			cerrar_popup();
		} else {
			return false;
		}
	});
}

function buscar_descr_localidad(_localidad, info)
{
	$.ajax({
		url: info.url_descr_localidad,
		dataType: 'json',
		data: {localidad: _localidad},
		type: 'post',
		async: true,
		success: function(data) {
			$('#'+info.retorno_descr).val(data.localidad_descr);
		}
	});	
}
	
function validar()
{
	var valido;

	valido = validar_campo('pais');
	valido = valido & validar_campo('provincia');
	valido = valido & validar_campo('departamento');
	valido = valido & validar_campo('localidad');

	return valido;
}

function validar_campo(campo)
{
	var valor = $('#form_localidad-'+campo).val();
	if ((valor === no_seleccion) || (valor === null)) {
		$('#msg-'+campo).html(info.error_vacio);
		$('#cg-'+campo).addClass('error');
		return false;
	} else {
		$('#msg-'+campo).html('');
		$('#cg-'+campo).removeClass('error');
		return true;
	}
}

//--------------------------------------------------------------------------
//---- Manejo de cascadas --------------------------------------------------
//--------------------------------------------------------------------------

function registrar_cascadas()
{
	$('#form_localidad-pais').on('change', function() {
		cambio_pais();
	});
	
	$('#form_localidad-provincia').on('change', function() {
		cambio_provincia();
	});
	
	$('#form_localidad-departamento').on('change', function() {
		cambio_departamento();
	});
	
	$('#form_localidad-localidad').on('change', function() {
		cambio_localidad();
	});
}

function inicializar()
{
	$('#form_localidad-pais').val('54');
	cambio_pais();
	set_solo_lectura('form_localidad-departamento', true);
	set_solo_lectura('form_localidad-localidad', true);
	if (info.ingresa_cp) {
		set_solo_lectura('form_localidad-codigo_postal', true);
	} else {
		$('#cg-codigo_postal').addClass('hide');
	}
}

//------------------------------------------------------------------------------

function cambio_pais()
{
	var pais= $('#form_localidad-pais').val();
	resetear_provincias();
	if (pais != no_seleccion) {
		buscar_provincias(pais);
		set_solo_lectura('form_localidad-provincia', false);
	}
}

function cambio_provincia()
{
	var provincia= $('#form_localidad-provincia').val();
	resetear_departamentos();
	if (provincia !== no_seleccion) {
		buscar_departamentos(provincia);
		set_solo_lectura('form_localidad-departamento', false);
	}
}

function cambio_departamento()
{
	var departamento= $('#form_localidad-departamento').val();
	resetear_localidades();
	if (departamento !== no_seleccion) {
		buscar_localidades(departamento);
		set_solo_lectura('form_localidad-localidad', false);
	}
}

function cambio_localidad()
{
	if (info.ingresa_cp) {
		var localidad = $('#form_localidad-localidad').val();
		resetear_cod_postales();
		if (localidad !== no_seleccion) {
			buscar_cod_postales(localidad);
			set_solo_lectura('form_localidad-codigo_postal', false);
		}
	}
}

//------------------------------------------------------------------------------

function buscar_provincias(_pais)
{
	$.ajax({
		url: info.url_provincias,
		dataType: 'json',
		data: {pais: _pais},
		type: 'post',
		success: function(data) {
			$('#form_localidad-provincia').children().remove();					
			$.each(data, function(key, value) {
				$('#form_localidad-provincia').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_localidad-provincia');
		}
	});	
}

function buscar_departamentos(_provincia)
{
	$.ajax({
		url: info.url_departamentos,
		dataType: 'json',
		data: {provincia: _provincia},
		type: 'post',
		success: function(data) {
			$('#form_localidad-departamento').children().remove();					
			$.each(data, function(key, value) {
				$('#form_localidad-departamento').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_localidad-departamento');
		}
	});	
}

function buscar_localidades(_departamento)
{
	$.ajax({
		url: info.url_localidades,
		dataType: 'json',
		data: {departamento: _departamento},
		type: 'post',
		success: function(data) {
			$('#form_localidad-localidad').children().remove();					
			$.each(data, function(key, value) {
				$('#form_localidad-localidad').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_localidad-localidad');
		}
	});	
}

function buscar_cod_postales(_localidad)
{
	$.ajax({
		url: info.url_cod_postales,
		dataType: 'json',
		data: {localidad: _localidad},
		type: 'post',
		success: function(data) {
			$('#form_localidad-codigo_postal').children().remove();					
			$.each(data, function(key, value) {
				$('#form_localidad-codigo_postal').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_localidad-codigo_postal');
		}
	});	
}

//------------------------------------------------------------------------------

function resetear_provincias()
{
	set_solo_lectura('form_localidad-provincia', true);
	$('#form_localidad-provincia').children().remove();
	resetear_departamentos();
}

function resetear_departamentos()
{
	set_solo_lectura('form_localidad-departamento', true);
	$('#form_localidad-departamento').children().remove();
	resetear_localidades();
}

function resetear_localidades()
{
	set_solo_lectura('form_localidad-localidad', true);
	$('#form_localidad-localidad').children().remove();
	resetear_cod_postales();
}

function resetear_cod_postales()
{
	set_solo_lectura('form_localidad-codigo_postal', true);
	$('#form_localidad-codigo_postal').children().remove();
}