<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class pant_asignaciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		if ($this->dep('docentes_asociados')->get_cantidad_lineas() > 0) {
			$this->dep('docentes_asociados')->generar_html();
		} else {
			$this->eliminar_dep('docentes_asociados');
			echo '	<div class="ei-barra-sup ei-form-barra-sup ei-barra-sup-sin-botonera">
						<span class="ei-barra-sup-tit">Colaboración Docente</span>
					</div>';
			echo '	<div class="ei-cuerpo ei-ml-base" style="width: 100%; border: 1px inset; margin: 0px; padding: 0px; text-align:center;"> 
						<br/><br/>';
			echo guarani_mensajes::get('800_sin_registros');
			echo '		<br/><br/>
					</div>';
		}
		echo '<hr />';
		$this->dep('seleccion_docentes')->generar_botones();		
		$this->dep('seleccion_docentes')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_docentes')->generar_html();
	}
}

?>