<?php
class pant_resultados extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		echo '<br><br>';
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
		echo '<hr/>';
		$this->dep('form_totales')->generar_html();
	}

	function extender_objeto_js()
	{
                $btns_proceso_finalizo = ['finalizar'];
                $elementos = ['cant_alumnos'];
                $eventos = ['rep_alumnos'];
                $relacion_elementos_eventos = [
                    'cant_alumnos' => ['rep_alumnos'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}
	
}