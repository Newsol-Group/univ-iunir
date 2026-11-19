kernel.renderer.registrar_pagelet('comision', function (info) {
	var id = '#' + info.id;
	
	return {
        onload: function (){
					
			$.each($('.js-btn-mostrar'), function() {
				inicializar_boton(this.id);
			});
			
			$('.js-btn-mostrar').on('click', function(event) {
				click_boton(this.id);
			});		
        }
    };

			function click_boton(id)
			{
				if (id === 'btn-evaluaciones')
				{				
					$('#btn-actas').removeClass('active');
					$('#btn-todo').removeClass('active');
					$('#btn-evaluaciones').addClass('active');
					$('.col-parciales').show();
					$('.col-actas').hide();
					$('.info-alumno').show();
					$('.sin-evaluaciones').hide();
					$('.sin-actas').hide();
					$('.sin-datos').hide();

					
					if(!info.tiene_evaluaciones)
					{
						$('.sin-evaluaciones').show();
						$('.sin-actas').hide();
						$('.sin-datos').hide();
						$('.info-alumno').hide();
					}

					if(info.tiene_evaluaciones || info.tiene_actas || info.tiene_actas_prom){
						actualizar_urls('E');
					}

				} else if (id === 'btn-actas') {			
					$('#btn-actas').addClass('active');
					$('#btn-todo').removeClass('active');
					$('#btn-evaluaciones').removeClass('active');
					$('.col-parciales').hide();
					$('.col-actas').show();
					$('.info-alumno').show();
					$('.sin-evaluaciones').hide();
					$('.sin-actas').hide();
					$('.sin-datos').hide();
					
					if(!info.tiene_actas && !info.tiene_actas_prom)
					{
						$('.sin-actas').show();
						$('.sin-evaluaciones').hide();
						$('.sin-datos').hide();
						$('.info-alumno').hide();
					}					
					
					if(info.tiene_evaluaciones || info.tiene_actas || info.tiene_actas_prom){
						actualizar_urls('A');
					}
					
					
				} else if (id === 'btn-todo') {
					$('#btn-actas').removeClass('active');
					$('#btn-evaluaciones').removeClass('active');
					$('#btn-todo').addClass('active');
					$('.info-alumno').show();
					$('.col-parciales').show();
					$('.col-actas').show();
					$('.sin-actas').hide();
					$('.sin-evaluaciones').hide();
					$('.sin-datos').show();
					
					if(info.tiene_evaluaciones || info.tiene_actas || info.tiene_actas_prom){
						actualizar_urls('T');	
					}						
				} 
			}
			
			function inicializar_boton(id)
			{
				click_boton('btn-todo');
			}
			
			function actualizar_urls(id)
			{
				var url_pdf				= $('#pdf_btn').attr('href').split("/");
				var ult_elem_pos		= url_pdf.length -1;
				url_pdf[ult_elem_pos]	= id;
				var url_pdf_new			= url_pdf.join("/"); 
				$('#pdf_btn').attr('href', url_pdf_new);

				var url_xls				= $('#xls_btn').attr('href').split("/");
				var ult_elem_pos		= url_xls.length -1;
				url_xls[ult_elem_pos]	= id;
				var url_xls_new			= url_xls.join("/"); 
				$('#xls_btn').attr('href', url_xls_new);
			}	
});