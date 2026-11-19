kernel.renderer.registrar_pagelet('cabecera', function(info) {
    var id = '#' + info.id;
	
	function bind_escala_notas(btn_id, btn_url) {
		var escala_notas_cargada = false;
		var tabla_notas = $("<table style='margin-top: 16px'><thead><th>"+info.msg_valor +"</th><th>"+info.msg_descripcion +"</th><th>"+info.msg_resultado +"</th></thead><tbody></tbody></table>");
		tabla_notas.addClass('table').addClass('table-condensed');
		$(btn_id).on("click", function() {
			if (!escala_notas_cargada) {
				kernel.ui.show_loading();
				$.ajax({
					url: btn_url,
					success: function(res) {
						$(JSON.parse(res)).each(function() {
							var escala = $(this)[0];
							var fila = $('<tr>');
							fila.append($('<td>' + escala.nota + '</td>'));
							fila.append($('<td>' + escala.descripcion + '</td>'));
							fila.append($('<td>' + escala.resultado + '</td>'));
							tabla_notas.find('tbody').append(fila);
						});
						escala_notas_cargada = true;
						kernel.ui.hide_loading();
						$.facebox(tabla_notas);
					}
				});
			} else {
				$.facebox(tabla_notas);
			}
		});
	}
	
    return {
        onload: function() {
			var instancias = info.instancias;
			var length = instancias.length;
			var elemento;
			for (var i = 0; i < length; i++) {
				elemento = instancias[i];
				bind_escala_notas('#ver_escala_'+elemento['instancia'], elemento['url']);
			}
			
			$(id).find('#js-colapsar-info-mesa').on("click", function() {
				var barra = $('.js-barra-herramientas');
				console.log(barra);
				if (barra.is(':visible')) {
					barra.slideUp();
				}
				
				var actas = $('.js-detalle-actas');
				if (actas.is(':visible')) {
					actas.slideUp();
				}
				
				var detalle = $('.js-detalle-materia');
				
				if (detalle.is(':visible')) {
					detalle.slideUp();
				} else {
					detalle.slideDown();
				}
			});
			
			$(id).find('#js-colapsar-herramientas').on("click", function() {
				var detalle = $('.js-detalle-materia');
				
				if (detalle.is(':visible')) {
					detalle.slideUp();
				}
				
				var actas = $('.js-detalle-actas');
				if (actas.is(':visible')) {
					actas.slideUp();
				}
				
				var barra = $('.js-barra-herramientas');
				
				if (barra.is(':visible')) {
					barra.slideUp();
				} else {
					barra.slideDown();
				}
			});
			
			$(id).find('#js-colapsar-actas').on("click", function() {
				var detalle = $('.js-detalle-materia');
				if (detalle.is(':visible')) {
					detalle.slideUp();
				}
				var barra = $('.js-barra-herramientas');
				if (barra.is(':visible')) {
					barra.slideUp();
				}
				var actas = $('.js-detalle-actas');
				if (actas.is(':visible')) {
					actas.slideUp();
				} else {
					actas.slideDown();
				}
			});
			
			
			/* Se deshabilita el botón -Ver actas- cuando la mesa no tiene actas */
			if ($('.js-detalle-actas table tbody').length <=0) {
				$('#js-colapsar-actas').prop("disabled", true);
			}
        }
    }
})