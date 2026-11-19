kernel.renderer.registrar_pagelet('lista_mensajes', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			inicializar();
                        
			$('#ultimos').blur( function() {
                        if ($('#ultimos').val()>100 ||$('#ultimos').val()<1){
                                error = 'Datos Inválidos: Últimos solo pueden ser números del 1 al 100';
                            kernel.ui.show_mensaje(error, {tipo: 'alert-error', timeout: 10000});
                           
                        }
			});
			$(id).on('click', 'table .adjunto', function(e) {
				e.stopPropagation();
			});
                
			$(id).on('click', 'table td.detalle h4.msg-title', function() {
				var td = $(this).parents("td.detalle");
				var id = $(td).attr('id');
				if ($('#tr' + id).hasClass('abierto'))
				{
					$('#tr' + id).removeClass('abierto');
					$('#tr' + id + ' h4').removeClass('abierto');
					$('#mensaje' + id).slideUp('slow');
					$('#tr' + id).removeClass('leido');
					$('#tr' + id + ' i.flecha').removeClass('icon-chevron-up').addClass('icon-chevron-down');
				}
				else
				{
                    if ($('#tr' + id).hasClass('cargado')){
                        mostrarMensaje(id);
                    }
                    else{
                        kernel.ajax.call($(td).data('url-leer'), {
                            type: 'get',
                            data: { id: id },
                            success: function(data) {
								$('#mensaje' + id).html('<pre>'+data.cont+'</pre>');
                                $('#tr' + id).addClass('cargado');
                                mostrarMensaje(id);
                            }
						});
                    }
				}
				return false;
			});
			
			// Si hay mensaje de envï¿½o exitoso lo muestro
			if(info.mensaje_enviado_ok){
				
				kernel.ui.show_mensaje(info.mensajes.mensaje_enviado_ok_msj, {
                    tipo: 'alert-success',
                    timeout: 10000
                });
				
			}
                        if(info.mensaje_fecha_error){
							error = 'Datos Inválidos: La fecha desde no puede ser mayor a la fecha hasta';
							kernel.ui.show_mensaje(error, {tipo: 'alert-error', until_interaction: true});
							return false;
						}
			var fi = getParameterByName("fecha_desde");
			var ff = getParameterByName("fecha_hasta");
			var ultimos = getParameterByName("ultimos");
			//dar valor a los input de fecha luego de realizar GET
			if(fi.lenght != ""){
				$('#fecha_desde').val(fi.replace(/^(\d{4})-(\d{2})-(\d{2})$/g,'$3/$2/$1'));
				$('#fecha_hasta').val(ff.replace(/^(\d{4})-(\d{2})-(\d{2})$/g,'$3/$2/$1'));
			}
			if(ultimos.length != ""){
				$('#ultimos').val(ultimos);
			}else{
				$('#ultimos').val(10);
			}

			$('#btn_filtrar').on('click',function(e){
				fecha_desde = $('#fecha_desde').val();
				fecha_hasta = $('#fecha_hasta').val();

				 if((fecha_desde == "") && (fecha_hasta =="")){
					 //ambos campos vacios
					error = 'Complete un campo para Continuar';
					kernel.ui.show_mensaje(error, {tipo: 'alert-error', until_interaction: true});
					return false;
				}else if((fecha_desde != "") && (fecha_hasta !="")){
					//ambos completos completos
					fecha_desde = cambiarFormato(fecha_desde);
					fecha_hasta = cambiarFormato(fecha_hasta);
						if(fecha_desde > fecha_hasta){
							console.log(fecha_desde,fecha_hasta);
							error = 'Datos Inválidos: La fecha desde no puede ser mayor a la fecha hasta';
							kernel.ui.show_mensaje(error, {tipo: 'alert-error', until_interaction: true});
							return false;
						}
						respuesta_fecha_desde = VerificarFecha(fecha_desde);
						respuesta_fecha_hasta = VerificarFecha(fecha_hasta)
						if((respuesta_fecha_hasta == false) || (respuesta_fecha_hasta == false) ){
							return false;
						}
						
				}
				if(fecha_desde != ""){
					if(VerificarFecha(fecha_desde) == false ){
						return false;
					}  ;
				}
				if(fecha_hasta != ""){
					if(VerificarFecha(fecha_hasta) == false ){
						return false;
					}  ;
				}

				
			})
			$('#recargar').on('click',function(){
				url=window.location.href.replace(window.location.search,'');
				window.location.href = url; 
			})
			
		}
	};

	function inicializar(){
		//---- Date Picker -------------------------------------------------

		$(function () {
			$.datepicker.setDefaults($.datepicker.regional["es"]);
			$("#fecha_hasta").datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
				defaultDate:0,
				yearRange: "-70:+0"
			});
			$("#fecha_desde").datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
				defaultDate:0,
				yearRange: "-70:+0"
			});
			$('#fecha_desde').attr('placeholder','dd/mm/aaaa');
			$('#fecha_hasta').attr('placeholder','dd/mm/aaaa');

		});
	}
	

    function mostrarMensaje(id){
        $('#tr' + id).addClass('leido');
        $('#imgestado' + id).removeClass('icon-envelope');
        $('#imgestado' + id).addClass('icon-ok');
        $('#mensaje' + id).slideDown('slow');
        $('#tr' + id).addClass('abierto');
        $('#tr' + id + ' h4').removeClass('abierto');
        $('#tr' + id + ' i.flecha').removeClass('icon-chevron-down').addClass('icon-chevron-up');
	}
	function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
		results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}
	function VerificarFecha(fecha){
        var Nfecha = fecha.split("/");
        var dia = Nfecha[0];
        var mes = Nfecha[1];
        var anio = Nfecha[2];
        var fechaReal = new Date(anio,mes,'0');
        if((dia-0)>(fechaReal.getDate()-0)){
            error = "El Formato o Fecha ingresada es inválida";
            kernel.ui.show_mensaje(error, {tipo: 'alert-error', until_interaction: true});
            return false;
        }
        return true;
	}
	function cambiarFormato(fecha){
		return fecha.replace(/^(\d{2})\/(\d{2})\/(\d{4})$/g,'$3-$2-$1');	
	}
});