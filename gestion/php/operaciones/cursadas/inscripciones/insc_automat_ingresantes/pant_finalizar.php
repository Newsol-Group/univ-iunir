<?php
class pant_finalizar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
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
                $btns_proceso_finalizo = ['finalizar'];
                $elementos = ['alumnos', 'inscripciones', 'insc_aceptadas', 'insc_pendientes', 'insc_rechazadas'];
                $eventos = ['rep_insc_aceptadas', 'rep_insc_pendientes', 'rep_insc_rechazadas'];
                $relacion_elementos_eventos = [
                    'insc_aceptadas' => ['rep_insc_aceptadas'],
                    'insc_pendientes' => ['rep_insc_pendientes'],
                    'insc_rechazadas' => ['rep_insc_rechazadas'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}
}