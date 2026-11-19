<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Modificar Mesas Masivamente
 */
class pant_procesar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br>';
		$this->controlador()->ev_info()->generar_html();
		echo '<br>';
		
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
		$this->controlador()->dependencia('form_totales')->generar_html();
	}
	
	function extender_objeto_js()
	{
                $btns_proceso_finalizo = [];
                $elementos = ['cant_mesas_modificadas', 'cant_mesas_no_modificadas'];
                $eventos = ['rep_mesas_modificadas', 'rep_mesas_no_modificadas'];
                $relacion_elementos_eventos = [
                    'cant_mesas_modificadas' => ['rep_mesas_modificadas'],
                    'rep_mesas_modificadas' => ['rep_mesas_no_modificadas'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}
}