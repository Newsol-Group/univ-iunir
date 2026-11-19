kernel.renderer.registrar_pagelet('formulario', function(info) {
    var id = '#' + info.id;
    
	var localidades = {
		pri_: {
			pais: 54,
			provincia: false,
			localidad: false
		}
	};
	
	function setup_principales() {
		var cBusquedaDestino = "";
		$('#btnLocPersonal').on("click", function() {
			cBusquedaDestino = "pri_";
			$('#cuadroBuscarLocalidad').show(500);
			$('#overlay').css('height',$('body').css('height'));
			$('#overlay').css('display','block');
			inicializoCuadroBusqueda();
		});
		
		$('#btnCerrarCuadroLocalidad').on("click", function (){
			$('#cuadroBuscarLocalidad').hide(500,function (){},$('#overlay').css('display','none'));			
		});
				
		$('#pais').change(function() {
			buscoProvincias($(this).val());
			$('#localidad').children().remove();
			$('#tablaResultados').css('display', 'none');
			$('#agregar').css('display', 'none');
		});
		
		$('#provincia').change(function() {
			$('#tablaResultados').css('display', 'none');
			$('#agregar').css('display', 'none');
		});		
		
		$('#principales').on('change', 'input', function (){
			kernel.evts.disparar('cambio_algo_status');
		});
		
		$('#principales').on('change', 'select', function (){
			kernel.evts.disparar('cambio_algo_status');
		});
		
		function buscoProvincias(pais_id){
			$.ajax({
				url: info.url_provincias,
				dataType: 'json',
				data: {pais: pais_id},
				type: 'post',
				success: function(data) {
					$('#provincia').children().remove();					
					$('#provincia').append(
						$('<option></option>').val('').html('Seleccione provincia')
					);
					$.each(data, function(key, value) {
						$('#provincia').append(
							$('<option></option>').val(value['ID']).html(value['DESC'])
						);
					});
				}
			});	
		}
		
		function inicializoCuadroBusqueda() {
			var pais_def = 54;
			pais_def = localidades[cBusquedaDestino]['pais'];
			
			var provincia_def = '';
			if (localidades[cBusquedaDestino]['provincia'] != ""){
				provincia_def = localidades[cBusquedaDestino]['provincia'];
			}
			$('#localidad').val('');
			buscoProvincias(pais_def);	
		   
			$('#pais option').each(
				function(key, value) 
				{
					if(value['value'] == pais_def){
						$(this).prop('selected', 'selected');

					}
				}
			);

			$('#provincia option').each(
				function(key, value) 
				{
					if (value['value'] == provincia_def){
						$(this).prop('selected', 'selected');
					}
				}
			);
			$('#tablaResultados').css('display', 'none');
			$('#agregar').css('display', 'none');
		};
		
		$('#cancelar').on("click", function() {			
			$('#provincia').children().remove();			
			$('#localidad').children().remove();
			$('#localidad').val('');
			$('#cuadroBuscarLocalidad').hide(500);
		});
		
		$('#buscar').on("click", function() {
			$('#error_busqueda_provincia').hide();
			$('#error_busqueda_localidad').hide();
			$('#error_busqueda_cp').hide();
			if ($('#pais').val() != '___' && $('#provincia').val() != '')
			{
				if ($('#localidad').val() != '')
				{
					$('#ajax-loader').css('display', 'block');
					$.ajax({				
						url: info.url_localidades,				
						dataType: 'json',
						data: {provincia: $('#provincia').val(), localidad: $('#localidad').val()},						
						type: 'post',
						success: function(data) {
							if (!(typeof data == "undefined") && !(data == false)){
								$('#cps').children().remove();
								var mostrarResultados = 0;								
								$.each(data, function(key, value) {									
									if (value['DESC'] != "")
									{
										mostrarResultados = 1;
										$('#cps').append(
											$('<option></option>').val(value['ID']).html(value['DESC'])
										);
									}
								});
								
								if (mostrarResultados == 1){
									$('#tablaResultados').css('display', 'block');
									$('#agregar').css('display', 'block');
									$('#tablaNoResultados').css('display', 'none');
								}
							}
							else
							{
								$('#tablaResultados').css('display', 'none');
								$('#agregar').css('display', 'none');								
								$('#tablaNoResultados').css('display', 'block');
							}
							$('#ajax-loader').css('display', 'none');
						}
					});
				}
				else
				{
					$('#error_busqueda_localidad').show();
				}
			}
			else
			{
				$('#error_busqueda_provincia').show();
			}
		});
		
		
		$('#agregar').on("click", function() {
			$('#error_busqueda_cp').hide();			
			if ($('#cps').val() != '' && $('#cps').val() != null)
			{
				localidades[cBusquedaDestino]['pais'] = $("#pais option:selected").val();
				localidades[cBusquedaDestino]['provincia'] = $("#provincia option:selected").val();
				
				var valor = jQuery.trim($("#cps option:selected").val());
				var pos = valor.search(new RegExp("_"));
				var largo = valor.length;
				var id_localidad = valor.substr(0,pos);
				var aux = valor.substr(pos, largo+1);
				largo = aux.length;
				pos = valor.search(new RegExp("_"));
				var cp = aux.substr(1,pos-1);
				var nombre_localidad = aux.substr(pos+1, largo);				
				var loc_result = nombre_localidad+', '+jQuery.trim($("#provincia option:selected").text())+', '+jQuery.trim($("#pais option:selected").text());
				
				$('#CENS_LOC_PER_LECT_TEXTO').val(loc_result);
				$('#CENS_CP_PER_LECT').val(cp);
				$('#CENS_LOC_PER_LECT').val(id_localidad);
				
				kernel.evts.disparar('cambio_algo_status');
				$('#cuadroBuscarLocalidad').hide(500);
			}
			else
			{
				$('#error_busqueda_cp').show();
			}
		});
		
		$('form').keypress(function(e){
			if(e.which == 13){
				return false; 
			}
		});
	}	
	
	function seteos(){
		setup_principales();		
		
		$('#columna_1').off('click', '#guardar');
		$('#columna_1').on('click', '#guardar', function (){
			kernel.evts.disparar('cambio_algo_guardar');
			var url = info.url_guardar;
			kernel.ajax.call(url, {
				type: 'POST',
				data: $('#'+$('#form_id').val()).serializeArray(),
				success: function(paquete) {
					$('#mensaje').show("slow");
					$('#mensaje').html(paquete['cont']);
					$('#mensaje').removeClass('alert-error');
					$('#mensaje').addClass('alert alert-success');
				},
				error: function (paquete) {
					$('#mensaje').html(paquete['cont']);
					$('#mensaje').removeClass('alert-success');
					$('#mensaje').addClass('alert alert-error');					
				}
			});
			return false;
		});
	}
	
	function cargar_formulario(params) {
		kernel.ajax.load(params.href, id, {
			success: function() {
				info.id_form = params.form;
				seteos();
				$('#mensaje').css('display', 'none');
				kernel.evts.disparar('info_formulario_cargado', params);
			}
		});
    }
	
	function registrar_listeners() {
		var id_evento = 'seleccion_formulario';
        kernel.evts.escuchar(id_evento, cargar_formulario);
    }
	
	return {
        onload: function() {
			registrar_listeners();
			jQuery(function (){				
				seteos();
			});
        }
    }
})