<?php
class pant_resultados extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_destino()->generar_html();
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<hr/>';
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
		$this->dep('form_totales')->generar_html();
	}
	
	function extender_objeto_js()
	{
		$datos_destino = $this->controlador()->get_seleccion_valor('info_destino');
		$anio = $datos_destino['anio_academico'];
		$turno = $datos_destino['turno_examen_nombre'];
		$mensaje = guarani_mensajes::get('800exa_ok_mesas', array($anio, $turno));

                $btns_proceso_finalizo = ['volver'];
                $elementos = ['actividades', 'mesas', 'mesas_con_obs', 'actividades_sin_mesas'];
                $eventos = ['rep_actividades', 'rep_mesas', 'rep_mesas_con_obs', 'rep_actividades_sin_mesas'];
                $relacion_elementos_eventos = [
                    'actividades' => ['rep_actividades'],
                    'mesas' => ['rep_mesas'],
                    'mesas_con_obs' => ['rep_mesas_con_obs'],
                    'actividades_sin_mesas' => ['rep_actividades_sin_mesas'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre, $mensaje);
	}
}