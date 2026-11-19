<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */
class pant_resultados extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br>';
		$this->controlador()->ev_info_origen()->generar_html();
		echo '<br>';
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<br>';
		$this->controlador()->ev_info_destino()->generar_html();
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
                $elementos = ['cant_activ_con_mesas', 'cant_mesas_copiadas', 'cant_mesas_no_copiadas', 'cant_mesas_con_observ'];
                $eventos = ['rep_cant_activ_con_mesas', 'rep_cant_mesas_copiadas', 'rep_cant_mesas_no_copiadas', 'rep_cant_mesas_con_observ'];
                $relacion_elementos_eventos = [
                    'cant_activ_con_mesas' => ['rep_cant_activ_con_mesas'],
                    'cant_mesas_copiadas' => ['rep_cant_mesas_copiadas'],
                    'cant_mesas_no_copiadas' => ['rep_cant_mesas_no_copiadas'],
                    'cant_mesas_con_observ' => ['rep_cant_mesas_con_observ'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}
}