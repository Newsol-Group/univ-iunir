<?php
/**
 *
 * 
 * @package 	Constancias
 * @subpackage 	Emitir Constancias de Cursos
 * 
 */
class pant_procesar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');
		echo "
			<div id='detalle_proceso'>
				<p>Procesando alumnos...<p>
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
                $elementos = ['emitidos', 'no_pasaron'];
                $eventos = ['rep_no_pasaron'];
                $relacion_elementos_eventos = [
                    'no_pasaron' => ['rep_no_pasaron']
                ];
                $this->generar_js_procesos_bk($btns_proceso_finalizo, $elementos, $eventos, $relacion_elementos_eventos);
	}

}