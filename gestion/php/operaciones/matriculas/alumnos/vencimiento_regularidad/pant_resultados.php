<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class pant_resultados extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
				<p>Procesando Alumnos...<p>
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
            $btns_proceso_finalizo = ['finalizar'];
            $elementos = ['modificados', 'no_modificados'];
            $eventos = ['rep_modificados', 'rep_no_modificados'];
            $relacion_elementos_eventos = [
                'modificados' => ['rep_modificados'],
                'no_modificados' => ['rep_no_modificados']
            ];
            $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}

}