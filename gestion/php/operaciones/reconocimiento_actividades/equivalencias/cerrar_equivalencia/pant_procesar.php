<?php
/**
 *
 * 
 * @package 	Ex?menes
 * @subpackage 	Inscripciones
 * 
 */
class pant_procesar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
				<p>Procesando cierre de Trámites de equivalencias ...<p>
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
                $btns_proceso_finalizo = ['volver_inicio'];
                $elementos = ['reporte_cerrados', 'reporte_no_pasaron'];
                $eventos = ['reporte_cerrados', 'reporte_no_pasaron'];
                $relacion_elementos_eventos = [
                    'reporte_cerrados' => ['reporte_cerrados'],
                    'reporte_no_pasaron' => ['reporte_no_pasaron'],
                ];
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}

}