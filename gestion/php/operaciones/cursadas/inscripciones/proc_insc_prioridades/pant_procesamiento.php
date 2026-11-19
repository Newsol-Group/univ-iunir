<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class pant_procesamiento extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
				<p>Procesando inscripciones...<p>
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
		echo '<p>Totales: <p>';
		$this->dep('resultados')->generar_html();
	}
	
	function extender_objeto_js()
	{
                $btns_proceso_finalizo = ['cancelar'];
                $elementos = ['aceptadas', 'fallo_controles'];
                $eventos = ['rep_aceptadas', 'rep_fallo_controles'];
                $relacion_elementos_eventos = [
                    'aceptadas' => ['rep_aceptadas'],
                    'fallo_controles' => ['rep_fallo_controles'],
                ];
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}
}