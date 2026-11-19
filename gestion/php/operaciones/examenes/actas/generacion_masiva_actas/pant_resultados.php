<?php
class pant_resultados extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';

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
                $btns_proceso_finalizo = ['volver'];
                $elementos = ['cant_actas'];
                $eventos = ['rep_cantidad_actas', 'imprimir_actas'];
                $relacion_elementos_eventos = [
                    'cant_actas' => ['rep_cantidad_actas'],
                    'cant_actas' => ['imprimir_actas'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}

}