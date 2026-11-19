var _info;

function handle_autocomplete(campo_id, campo_descr, boton_limpiar)
{
	if ($('#'+campo_id).val() !== '') {
		set_solo_lectura(campo_descr, true);
		mostrar(boton_limpiar);
	} else {
		ocultar(boton_limpiar);
		set_solo_lectura(campo_descr, false);
	}
}

function cambio_nivel_estudio()
{
	var nivel = $("#form_datos_estudios-nivel_estudio").val();
	
	if ((nivel === no_seleccion) || (nivel === _info.nivel_sin_estudios)){
		ocultar('div_datos_estudio', true);
	} else if (nivel === _info.nivel_secundario) {
		mostrar('div_datos_estudio');
		mostrar('div_colegio');
		ocultar('div_institucion', true);
	} else {
		mostrar('div_datos_estudio');
		mostrar('div_institucion');
		ocultar('div_colegio', true);
	}
	
	if ((nivel === _info.nivel_superior) || (nivel === _info.nivel_universitario) || (nivel === _info.nivel_posgrado)) {
		mostrar('div_datos_nivel_sup');
	} else {
		ocultar('div_datos_nivel_sup', true);
	}
}

function cambio_estado_estudio()
{
	var estado = $("#form_datos_estudios-estado").val();
	if (estado === _info.estado_termino) {
		mostrar('div_fecha_egreso');
	} else {
		ocultar('div_fecha_egreso', true);
	}
	
	if (estado === _info.estado_abandono) {
		mostrar('motivos-abandono');
	} else {
		ocultar('motivos-abandono', true);
	}
}

/**
 * Cambio del campo "Razón de abandono"
 */
function cambio__razon_abandono()
{
  if ($('#form_datos_estudios-razon_abandono').val() == _info.razon_abandono_OTRO) {
	  mostrar('motivo-abandono-otro');
  } else {
	  ocultar('motivo-abandono-otro', true);
  }
}

kernel.renderer.registrar_pagelet('datos_estudios', function(info) {
    var id = '#' + info.id;
	_info = info;

	return {
        onload: function() {
			inicializar();
			var perfil_actual = $('#js-selector-perfiles').find('.js-seleccion-perfil').data('perfilnombre');
			if(perfil_actual === 'Alumno'){
				$('.js-btnEditar').attr('disabled', true);
				$('.js-btnEliminar').attr('disabled', true);
				$('.js-btnEditar').click(function(e) {
					e.stopPropagation();
					e.preventDefault();
				});
			}

			$('.js-btnEliminar').on('click', function() {
			if(perfil_actual === 'Alumno'){
				return false;
			}else{
				return accion_eliminar();
			}
			});
			
			cambio_nivel_estudio();
			$('#form_datos_estudios-nivel_estudio').on('change', function() {
				cambio_nivel_estudio();
			});
			
			cambio_estado_estudio();
			$('#form_datos_estudios-estado').on('change', function() {
				cambio_estado_estudio();
			});
			
			cambio__razon_abandono();
			$('#form_datos_estudios-razon_abandono').on('change', function() {
				cambio__razon_abandono();
			});
			
			//---- Manejo de instituciones -------------------------------------
			handle_autocomplete('form_datos_estudios-institucion', 'form_datos_estudios-institucion_descr', 'btnLimpiarInst');
			$("#form_datos_estudios-institucion_descr").attr('campo_id', 'form_datos_estudios-institucion');
			$("#form_datos_estudios-institucion_descr").attr('boton_limpiar', 'btnLimpiarInst');
			$("#form_datos_estudios-institucion_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_instituciones,
					  dataType: "json",
					  data: {
						term : request.term
					  },
					  success: function(data) {
						response(data);
					  }, 
                                          // A value of 0 means there will be no timeout
                                          timeout: 0 
					});
				},
				minLength: $("#js-ui-autocomplete-min-length").val(),	
				select: autocomplete_select,
				focus: autocomplete_focus
				
			});
			
			$('#btnLimpiarInst').on('click', function() {
				$('#form_datos_estudios-institucion').val('');
				set_solo_lectura('form_datos_estudios-institucion_descr', false);
				$('#form_datos_estudios-institucion_descr').val('');
				ocultar('btnLimpiarInst');
			});
			
			//---- Manejo de colegios -------------------------------------------
			handle_autocomplete('form_datos_estudios-colegio', 'form_datos_estudios-colegio_descr', 'btnLimpiarColegio');
			$("#form_datos_estudios-colegio_descr").attr('campo_id', 'form_datos_estudios-colegio');
			$("#form_datos_estudios-colegio_descr").attr('boton_limpiar', 'btnLimpiarColegio');
			$("#form_datos_estudios-colegio_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_colegios,
					  dataType: "json",
					  data: {
						term : request.term
					  },
					  success: function(data) {
                                            response(data);
					  },
                                          // A value of 0 means there will be no timeout
                                          timeout: 0 
                                          });
				},
				minLength: $("#js-ui-autocomplete-min-length").val(),	
				select: autocomplete_select,
				focus: autocomplete_focus
				
			});
			
			$('#btnLimpiarColegio').on('click', function() {
				$('#form_datos_estudios-colegio').val('');
				set_solo_lectura('form_datos_estudios-colegio_descr', false);
				$('#form_datos_estudios-colegio_descr').val('');
				ocultar('btnLimpiarColegio');
			});
			
			//---- Manejo de titulos -------------------------------------------
			handle_autocomplete('form_datos_estudios-titulo', 'form_datos_estudios-titulo_descr', 'btnLimpiarTitulo');
			$("#form_datos_estudios-titulo_descr").attr('campo_id', 'form_datos_estudios-titulo');
			$("#form_datos_estudios-titulo_descr").attr('boton_limpiar', 'btnLimpiarTitulo');
			$("#form_datos_estudios-titulo_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_titulos,
					  dataType: "json",
					  data: {
						term : request.term,
						tipo : $("#form_datos_estudios-nivel_estudio").val()
					  },
					  success: function(data) {
						response(data);
					  },
                                          // A value of 0 means there will be no timeout
                                          timeout: 0 
					});
				},
				minLength: $("#js-ui-autocomplete-min-length").val(),	
				select: autocomplete_select,
				focus: autocomplete_focus
				
			});
			
			$('#btnLimpiarTitulo').on('click', function() {
				$('#form_datos_estudios-titulo').val('');
				set_solo_lectura('form_datos_estudios-titulo_descr', false);
				$('#form_datos_estudios-titulo_descr').val('');
				ocultar('btnLimpiarTitulo');
			});
			
        }
    }
	
	//--------------------------------------------------------------------------
	//---- Funciones de autocompletado -----------------------------------------
	//--------------------------------------------------------------------------
			
	function autocomplete_select(event, ui)
	{
		var campo_id = event.target.attributes.campo_id.nodeValue;
		var campo_descr = event.target.attributes.id.nodeValue;
		var boton_limpiar = event.target.attributes.boton_limpiar.nodeValue;

		var valor = ui.item.value.valor;
		var descr = ui.item.value.descr;

		$("#"+campo_id).val(valor);
		$("#"+campo_descr).val(descr);
		event.preventDefault();
		
		set_solo_lectura(campo_descr, true);
		mostrar(boton_limpiar);
	}
	
	function autocomplete_focus(event, ui)
	{
		var campo_descr = event.target.attributes.id.nodeValue;
		var descr = ui.item.value.descr;
		$("#"+campo_descr).val(descr);
		
		event.preventDefault();
	}
	
	//--------------------------------------------------------------------------
	
	function inicializar()
	{
		//---- Date Picker -------------------------------------------------
		$.datepicker.setDefaults( $.datepicker.regional["es"] );
		$(id).find('input.date').datepicker({
			changeMonth: true,
			changeYear: true,
			firstDay:0,
			yearRange: "-70:+0"
		});
		
	}
	
	function accion_eliminar()
	{
		if (confirm(info.msj_confirmar_eliminar)) {
			submit();
			return true;
		}
		return false;
	}
	
});