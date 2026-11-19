kernel.renderer.registrar_pagelet('info_historia', function(info) {
    var id = '#' + info.id;
    var mensajes = info.mensajes;

    return {
        onload: function() {
			$(id).on('change', '#anos',function (){
                aplicar_filtro();
            });

			$(id).on('keyup', '#actividad_reconocimiento', function () {

				var term = limpiarAcentos($(this).val());

				$('div.catedras, div.creditos').each(function () {

					var title = limpiarAcentos($(this).find('h3.titulo-corte').text());
					(title.indexOf(term) != -1) ? $(this).slideDown() : $(this).slideUp();

				});

			});

			$(id).on('click', '#orden_anio, #orden_materia',function (){
				$(id+' #filtro_modo').val($(this).data('valor'));
				aplicar_filtro();
				return false;
            });
			
			$(id).on('click', '#pdf_btn',function (){
				var checkboxes = kernel.renderer.pagelet('menu_filtros').get_params_filtro();
                var url = info.url_pdf+'/?checks='+checkboxes+'&modo='+get_modo()+'&param_modo='+get_param_modo();
				$(this).attr('href', url);
            });
			
			$(id).on('click', '#xls_btn',function (){
				var checkboxes = kernel.renderer.pagelet('menu_filtros').get_params_filtro();				
                var url = info.url_xls+'/?checks='+checkboxes+'&modo='+get_modo()+'&param_modo='+get_param_modo();
                $(this).attr('href', url);
            });

            $(id).on('click', '#flt_btn',function (){
                window.location.replace(info.url_flt);
            });

            $(id).on('click', '#ver_filtros', function (){
                kernel.evts.disparar('mostrar_filtros');
            });

			function get_param_modo(){
				if ($(id+' #filtro_modo').val()== "a"){
					if ($(id+' #anos').length > 0){
						return $(id+' #anos').val();
					}
				} else {
					if ($(id+' #materias-autocomplete').length > 0){
						return $(id+' #materias-autocomplete').val();
					}
				}
				return "";
			}
			
			function get_modo(){
				if ($(id+' #filtro_modo').val()!= "a"){
					return 'materia';
				}
				
				return 'anio';
			}
			
            kernel.evts.escuchar('filtrar', aplicar_filtro);
            
            function aplicar_filtro(){
				var checkboxes = kernel.renderer.pagelet('menu_filtros').get_params_filtro();
				var estados_filtro = kernel.renderer.pagelet('menu_filtros').get_estados_filtro();
				
				/*kernel.ajax.load(info.url_datos_historia_academica+'/?checks='+checkboxes+'&modo='+get_modo()+'&param_modo='+get_param_modo()+'&'+estados_filtro, id, {
					historia: true,
					type: 'get',
					show_loading: true,
					forzar_cambio_op: true
				});*/

                kernel.ajax.call(info.url_datos_historia_academica+'/?checks='+checkboxes+'&modo='+get_modo()+'&param_modo='+get_param_modo()+'&'+estados_filtro, {
                    type: 'get',
                    success: function(paquete) {
						$(id).html(paquete.cont);
					}
                });
			}
			

			// Detalles
                $('.catedra_nombre a').on("click", function(e){

				e.preventDefault();

				if ($('#mat_'+this.id).css('display') == 'none'){
                    var origen = $(this).data('origen');
                    var id_materia = $(this).attr('id');
                    var id_link = $('#'+id_materia);
					var cargado = id_link.data("cargado");
                    var div_materia = '#mat_'+id_materia;
                    var div_detalle = '#info_det_'+id_materia;

					switch(origen)
                    {
                        case "Promocion":
                          url = info.url_detalle_promocion;
                          break;
                        case "Examen":
                          url = info.url_detalle_examen;
                          break;
                        case "AprobRes":
                          url = info.url_detalle_examen;
                          break;
                        case "Regularidad":
                          url = info.url_detalle_cursada;
                          break;
                        case "Equivalencia":
                          url = info.url_detalle_equivalencia;
                          break;
                        case "EnCurso":
                          url = info.url_detalle_encurso;
                          break;                        
                    }

					//Si la info ya fue cargada
					if(cargado){
						$(div_materia).show('2000');
						id_link.text(info.mensajes.cerrar);
					}
					else{//sino

						kernel.ajax.call(url, {
							type: 'post',
							data: {id: id_materia},
							success: function(paquete) {
								$(div_detalle).html(paquete.cont);
								$(div_materia).show('2000');
								id_link.text(info.mensajes.cerrar);
								id_link.data("cargado", true);
							}
						});

					}

                }
                else
                {
                    $('#mat_'+this.id).hide('2000');
                    $('#'+this.id).text('Detalle');
                }
                return false;
            });

			// Detalles créditos
			$(id).on('click', 'a.detalle-credito', function (e){
                            
				e.preventDefault();
				var $link = $(this);
				var nro_tramite = $link.data("nroTramite");
				var identificador = $link.data("identificador");
				var cargado = $link.data("cargado");

				//Si el detalle esta oculto
				if ($("#cred_"+identificador).css('display') == 'none'){

					//Si la info ya fue cargada
					if(cargado){
						$('#cred_'+identificador).show('2000');
						$link.text(info.mensajes.cerrar);
					}
					else{//sino

						kernel.ajax.call(info.url_detalle_creditos, {
							 type: 'post',
							 data: {nro_tramite: nro_tramite},
							 success: function(paquete) {
								 $('#cred_'+identificador+' span.documentacion_presentada').html(paquete.cont);
								 $('#cred_'+identificador).show('2000');
								 $link.text(info.mensajes.cerrar);
								 $link.data("cargado", true);
							 }
						 });

					}


				}
				else{//sino

					$('#cred_'+identificador).hide('2000');
					$link.text(info.mensajes.detalle);

				}

			});

			$(id).on('click', '.js-consultas_habituales a', function (){
				kernel.evts.disparar('consulta_habitual', {
					consulta: $(this).attr('consulta')
				});
				return false;
			});
        }
		
    }
})