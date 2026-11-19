<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
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
                $btns_proceso_finalizo = ['volver'];
                $elementos = ['procesados', 'perdieron_regularidad', 'recuperaron_regularidad'];
                $eventos = ['rep_perdieron_regularidad', 'rep_recuperaron_regularidad'];
                $relacion_elementos_eventos = [
                    'perdieron_regularidad' => ['rep_perdieron_regularidad'],
                    'recuperaron_regularidad' => ['rep_recuperaron_regularidad'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}

}