kernel.renderer.registrar_pagelet('menu_filtros', function(info) {
    var id = '#' + info.id;
	var mensajes = info.mensajes;
	
	return {
		get_params_filtro: function() {
			var checks = "";
			$('#form_filtros input').each(function(){
				if ($(this).is(':checked')){
					checks = checks + $(this).val()+",";
				}
			})
			return checks;
		},
		get_estados_filtro: function() {
			var estado_cursada = $(id+' a[filtro="cursada"]').attr('estado');
			var estado_examen = $(id+' a[filtro="examen"]').attr('estado');
			var estado_reconocimientos = $(id+' a[filtro="reconocimientos"]').attr('estado');
			return "e_cu="+estado_cursada+"&e_ex="+estado_examen+"&e_re="+estado_reconocimientos;
		},
		
		onload: function() {
			if (info.checks){
				$.each(info.checks, function(index, value) {
					$(id+' input[type="checkbox"][value="'+value+'"]').prop('checked',true);
				});
			}
			
            $(id + ' input').on("click", function() {
                kernel.evts.disparar('filtrar');
            });
            
            $(id + ' a').on("click", function() {
                var filtro = $(this).attr('filtro');
				var estado = $(this).attr('estado');
                if (filtro =="todo"){
					$(id + ' input').prop('checked', true);
					$(id + ' a[estado]').attr('estado', 'A');
                }
				
                if (filtro =="ninguno"){
					$(id + ' input').prop('checked', false);
					$(id + ' a[estado]').attr('estado', 'A');
                }
                
				// combinación de filtros para cursadas
				if (filtro =="cursada"){
					if (estado == "A"){
						$(id + ' input.'+filtro).prop('checked', false);
						$(id + ' input#cursadasAprobadas').prop('checked', true);
						$(id + ' input#cursadasPromocionadas').prop('checked', true);
						$(id + ' input#enCurso').prop('checked', true);
						$(this).attr('estado', 'T');
					}
					
					if (estado == "T"){
						$(id + ' input.'+filtro).prop('checked', true);
						$(this).attr('estado', 'N');
					}
					
					if (estado == "N"){
						$(id + ' input.'+filtro).prop('checked', false);
						$(this).attr('estado', 'A');
					}
                }
				
				// combinación de filtros para Exámenes
				if (filtro =="examen"){
					if (estado == "A"){
						$(id + ' input.'+filtro).prop('checked', false);
						$(id + ' input#examenesAprobados').prop('checked', true);
						$(this).attr('estado', 'T');
					}
					
					if (estado == "T"){
						$(id + ' input.'+filtro).prop('checked', true);
						$(this).attr('estado', 'N');
					}
					
					if (estado == "N"){
						$(id + ' input.'+filtro).prop('checked', false);
						$(this).attr('estado', 'A');
					}
                }
				
				// combinación de filtros para equivalancias
				if (filtro =="reconocimientos"){
					if (estado == "A"){
						$(id + ' input.'+filtro).prop('checked', false);
						$(id + ' input#equivalenciasAprobadas').prop('checked', true);
						$(id + ' input#resolucionesAprobadas').prop('checked', true);
						$(id + ' input#creditos').prop('checked', true);
						$(this).attr('estado', 'T');
					}
					
					if (estado == "T"){
						$(id + ' input.'+filtro).prop('checked', true);
						$(this).attr('estado', 'N');
					}
					
					if (estado == "N"){
						$(id + ' input.'+filtro).prop('checked', false);
						$(this).attr('estado', 'A');
					}
                }
				
				kernel.evts.disparar('filtrar');
				return false;
            });
			
			kernel.evts.escuchar('consulta_habitual', consulta_habitual);
			kernel.evts.escuchar('mostrar_filtros', mostrar_filtros);
		
			function mostrar_filtros() {
            	$(id+" #filtros").removeClass('visible-desktop');
            }
            
            function consulta_habitual(params){
				if (params.consulta == "mats_en_curso"){
					$(id+" input#enCurso").prop('checked', true);
				}

                if (params.consulta == "curs_aprobadas"){
                    $(id+" input#cursadasAprobadas").prop('checked', true);
                    $(id + ' input#cursadasPromocionadas').prop('checked', true);
                }
				
				if (params.consulta == "mats_aprobadas"){
					$(id + ' input#cursadasPromocionadas').prop('checked', true);
					$(id + ' input#examenesAprobados').prop('checked', true);
					$(id + ' input#equivalenciasAprobadas').prop('checked', true);
					$(id + ' input#resolucionesAprobadas').prop('checked', true);
				}
				
				if (params.consulta == "todo"){
					$(id + ' input').prop('checked', true);
				}
				kernel.evts.disparar('filtrar');
				return false;
			}
		}
	}
})