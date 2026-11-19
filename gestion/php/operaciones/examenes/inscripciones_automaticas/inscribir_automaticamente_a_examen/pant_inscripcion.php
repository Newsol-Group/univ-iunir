<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Inscripciones Automáticas
 */
class pant_inscripcion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br>';
		$this->controlador()->ev_info_filtros()->generar_html();
	
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
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
			<br></br>
		";
		echo '<hr />';
		$this->controlador()->dependencia('form_resultados')->generar_html();
	}
	
	function extender_objeto_js()
	{
                $btns_proceso_finalizo = ['volver', 'reporte_insc'];
                $elementos = ['cant_com_procesadas', 'cant_insc_procesadas', 'cant_insc_aceptadas', 'cant_insc_rechazadas', 'cant_insc_pendientes'];
                $eventos = [];
                $relacion_elementos_eventos = [];
                $ei_formulario_nombre = 'form_resultados';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}

}