<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Actualizaciones
 * 
 */
class pant_procesar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
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
		echo '<p>Totales: <p>';
		$this->dep('resultados')->generar_html();
	}

	function extender_objeto_js()
	{
                $btns_proceso_finalizo = ['volver'];
                $elementos = ['aceptadas', 'rechazadas', 'pendientes'];
                $eventos = ['rep_aceptadas', 'rep_rechazadas', 'rep_pendientes'];
                $relacion_elementos_eventos = [
                    'aceptadas' => ['rep_aceptadas'],
                    'rechazadas' => ['rep_rechazadas'],
                    'pendientes' => ['rep_pendientes'],
                ];
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}

}