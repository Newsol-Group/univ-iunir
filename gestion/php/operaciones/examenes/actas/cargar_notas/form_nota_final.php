<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Actas
 */
class form_nota_final extends guarani_pers_ei_formulario
{
    
    function generar_layout()
	{        
        echo '<br><div class="ei-barra-sup ci-barra-sup ei-barra-sup-sin-botonera">
                <span class="ei-barra-sup-tit">
                Datos del Examen Final y de Evaluaciones
            </span></div>';
        parent::generar_layout();
	}
    
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_error = guarani_mensajes::get('800_nota_invalida', array('la nota final'));
		$mapeo_resultado_valor_desc = $this->controlador()->generar_mapeo_resultados_valor_desc();
		$js_validacion_nota = $this->controlador()->get_js_validar_nota_escalas();

		echo "
			$js_validacion_nota
		
			{$this->objeto_js}.resultados = {$mapeo_resultado_valor_desc['resultados_por_instancia']};
			
			{$this->objeto_js}.evt__resultado__procesar = function(es_inicial)
			{
				//this.ef('resultado').set_solo_lectura();
				this.ef('resultado').ocultar();
				this.ef('escala_nota').ocultar();
			}


			{$this->objeto_js}.evt__nota__procesar = function(es_inicial)
			{
				if (!es_inicial) {
					if (this.ef('nota').tiene_estado()) {
						var nota 	= this.ef('nota').get_estado();
						var escala 	= this.ef('escala_nota').get_estado();
						var es_nota_valida = eval('es_nota_valida_para_escala_'+escala+'(nota)');

						if(! es_nota_valida){
							
							//La nota no pertenece a la escala, se retorna el msj de error.
							notificacion.limpiar();
							notificacion.agregar('$msj_error');
							notificacion.mostrar();
							this.ef('nota').resetear_estado();
							this.ef('resultado_nombre').resetear_estado();
							this.ef('resultado').resetear_estado();
						} else {
							
							//La nota corresponde a la escala y se retorna el resultado por defecto obtenido a partir de ambas
							//Se setea un flag que permite distinguir el seteo por defecto del valor del resultado, de un cambio del usuario.
							var valor_resultado = eval('get_resultado_para_escala_'+escala+'(nota)');
							this.ef('resultado').set_estado(valor_resultado);
							this.ef('resultado_nombre').set_estado(this.resultados[valor_resultado]);
							this.ef('nota').set_estado(eval('formatear_nota_escala_'+escala+'(nota)'));
						}
					} else {
						this.ef('resultado').resetear_estado();
						this.ef('resultado_nombre').resetear_estado();
					}
				}
		 	}
		";
	}	

}
?>