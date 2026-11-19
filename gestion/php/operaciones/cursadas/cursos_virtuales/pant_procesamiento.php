<?php
class pant_procesamiento extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_filtro()->generar_html();
		//$this->dep('filtro')->generar_html();
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
				<p>Procesando ...<p>
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

		$this->dep('resultados')->generar_html();
	}

	function extender_objeto_js()
	{
                $btns_proceso_finalizo = ['volver'];
                $elementos = ['cursos_creados_vinculados', 'cursos_creados_vinculados_error', 'alumnos_sincronizados', 'alumnos_sincronizados_error', 'docentes_sincronizados', 'docentes_sincronizados_error'];
                $eventos = ['rep_cursos', 'rep_alumnos', 'rep_docentes'];
                $relacion_elementos_eventos = [
                    'cursos_creados_vinculados' => ['rep_cursos'],
                    'cursos_creados_vinculados_error' => ['rep_cursos'],
                    'alumnos_sincronizados' => ['rep_alumnos'],
                    'alumnos_sincronizados_error' => ['rep_alumnos'],
                    'docentes_sincronizados' => ['rep_docentes'],
                    'docentes_sincronizados_error' => ['rep_docentes'],
                ];
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}

}