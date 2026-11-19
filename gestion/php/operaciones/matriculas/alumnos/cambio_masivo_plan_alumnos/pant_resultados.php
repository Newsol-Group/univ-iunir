<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */
class pant_resultados extends guarani_pers_ei_pantalla
{
	protected $msj_sin_matrices = '800EQU_no_matriz_automatica';
	
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		if ($this->existe_dependencia('cuadro_matrices_equiv_aplicadas')) {
			$this->dep('cuadro_matrices_equiv_aplicadas')->generar_html();
		}
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
                $elementos = ['cant_alumnos', 'cant_modificados', 'cant_no_modificados', 'cant_equiv_otorgadas'];
                $eventos = ['rep_cant_modificados', 'rep_cant_no_modificados', 'rep_equiv_por_alumno', 'rep_equiv_por_actividad'];
                $relacion_elementos_eventos = [
                    'cant_modificados' => ['rep_cant_modificados'],
                    'cant_no_modificados' => ['rep_cant_no_modificados'],
                    'cant_equiv_otorgadas' => ['rep_equiv_por_alumno', 'rep_equiv_por_actividad'],
                ];
                $ei_formulario_nombre = 'form_totales';
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos, $ei_formulario_nombre);
	}
}