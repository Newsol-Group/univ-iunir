<?php
class pant_impresion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_acta()->generar_html();
		echo "<hr />";

		if (acta::solicitar_libro_impresion($this->controlador()->get_acta_seleccionada(), $this->controlador()->get_cant_alumnos())) {
			$this->dep('form_libro')->generar_html();
			echo '<hr/>';
		} else {
			$this->eliminar_dep('form_libro');
		}
		// La posibilidad de definir si se Imprime o no el Acta como una Copia, es para Actas Cerradas y
		// usuarios que no tengan restringida dicha funcionalidad por perfil.
		if ($this->controlador()->usuario_puede_definir_es_copia()) {
			$this->dep('form_datos_imp')->generar_html();
			echo '<hr/>';
		} elseif ($this->existe_dependencia('form_datos_imp')) {
			$this->eliminar_dep('form_datos_imp');
		}

		if ($this->existe_dependencia('cuadro_alumnos')) {
			$this->dep('cuadro_alumnos')->generar_html();
		}
	}
}
?>