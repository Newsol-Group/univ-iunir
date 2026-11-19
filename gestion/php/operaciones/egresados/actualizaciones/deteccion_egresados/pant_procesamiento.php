<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Inscripciones
 * 
 */
class pant_procesamiento extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
				<p>Procesando...<p>
				<div>
					<div style='float:left;'>
						<img id='barra_progreso' src='$barra' alt='0%' class='barra-progreso' />
					</div>
					<div id='texto_progreso' style='margin:5px 5px; float:left;'>
						30%
					</div>
					<div style='clear:both;'></div>
				</div>
			</div>
		";
		echo '<hr/>';
		$this->dep('resultados')->generar_html();
	}
	
	function extender_objeto_js()
	{
                $btns_proceso_finalizo = ['volver'];
                $elementos = ['alumnos_en_condiciones'];
                $eventos = ['rep_en_condiciones'];
                $relacion_elementos_eventos = [
                    'alumnos_en_condiciones' => ['rep_en_condiciones'],
                ];
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}

}