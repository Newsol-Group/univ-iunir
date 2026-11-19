kernel.renderer.registrar_pagelet('notificaciones', function(info) {
    var id = '#' + info.id;
	var h;
	
	function refresh(es_cursada) {
		$.ajax({
			url: info.url_refresh,
			dataType: 'json',
			success: function(datos) {
				if(es_cursada) {
					h = new horario.cursada(datos, 'asignaciones_cursada', info.banda_horaria_cursado, info.persona);
					$('#eleccion-periodo').change();
				} else {
					var $ls = $('ul.notif_examenes_listado').html('');
					$(datos).each(function() {
						$ls.append('<li \
								class="notif_examen_inscripto" \
								data-title="&lt;span style=\'font-weight: bold\'&gt;('+this.actividad_codigo+') '+this.actividad_nombre_abreviado+'&lt;/span&gt;" \
								data-content="Fecha de Examen: '+this.mesa_examen_fecha+'"> \
								('+this.actividad_codigo+') '+this.actividad_nombre_abreviado+' - '+this.mesa_examen_fecha+' \
								</li>');
					});
					$('.notif_examen_inscripto').popover({ 
						html: true,
						placement: 'top',
						container: 'body',
						trigger: 'hover'
					});
				}
			}
		});
	}
	
	function alta_examen(params) {
		alert(params);
	}
	
	function baja_examen(params) {
		alert(params);
	}
	
    return {
        onload: function() {
			h = new horario.cursada(info.asignaciones, 'asignaciones_cursada', info.banda_horaria_cursado, info.persona);

			$('#eleccion-periodo').change(function() {
				h[$(this).val()]();
			});
			
			kernel.evts.escuchar('info_materia_inscripto', function() {
				refresh(true);
			});
			kernel.evts.escuchar('info_materia_baja', function() {
				refresh(true);
			});
			
			kernel.evts.escuchar('info_examen_inscripto', function() {
				refresh(false);
			});
			kernel.evts.escuchar('info_examen_baja', function() {
				refresh(false);
			});

			kernel.evts.escuchar('info_curso_notificaciones_inscripto', function() {
				refresh(true);
			});
			kernel.evts.escuchar('info_curso_notificaciones_baja', function() {
				refresh(true);
			});
        }
    }
}, '_comun/pagelets')