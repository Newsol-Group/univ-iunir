kernel.renderer.registrar_pagelet('cambiar_email', function(info) {
	var id = '#' + info.id;
        
        var errores_formulario = false;
	return {
		onload: function() {	
                $('#formulario_codigo').submit(function ()
			{
                        if( $("#codigo").val() ==''){
                        
					kernel.ui.show_mensaje('Error en el formulario', {tipo: 'alert-error'});
					return false;
				}
			});
                $('#formulario_pregunta_secreta').submit(function ()
			{
                        if(( $("#respuesta_secreta").val() =='')   ||  ( $("#combo_propuestas").val() =='')  ||  ( $("#fecha_nacimiento").val() =='')){
                        
					kernel.ui.show_mensaje('Error en el formulario', {tipo: 'alert-error'});
					return false;
				}
			});
                 //---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults( $.datepicker.regional["es"] );
			$('input.date').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay:0,
                                dateFormat: 'dd/mm/yy',
				yearRange: "-150:+0"
			});
                //----Fin Date Picker -------------------------------------------------
                $('#formulario_codigo').hide();
                $('#formulario_pregunta_secreta').hide();
                $('#radio_codigo').click(function(){
                //oculto formulario
                $('#formulario_pregunta_secreta').hide(); 
                $('#formulario_codigo').show();
                $('#radio_pregunta_secreta').prop('checked', false);
                resetform()
                });
                $('#radio_pregunta_secreta').click(function(){
                //oculto formulario email y muestro docuemento
                $('#formulario_codigo').hide(); 
                $('#formulario_pregunta_secreta').show();
                $('#radio_codigo').prop('checked', false);
                resetform()              
                });


                function resetform() {
                $("form select").each(function() { this.selectedIndex = 0 });
                $("form input[type=text] , form textarea").each(function() { this.value = '' });
                           }
            }
	}
})
