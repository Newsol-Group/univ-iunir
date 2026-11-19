<?php
class pant_edicion_acta extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('form_libro')->generar_html();
		echo '<hr />';
		if ($this->existe_dependencia('form_acta')) {
			$this->dep('form_acta')->generar_html();
			echo '<hr />';
		}
		if ($this->existe_dependencia('form_datos_imp')) {
			// Para la operación de Impresión, la posibilidad de definir si se Imprime o no el Acta 
			// como una Copia, es para Actas Cerradas y usuarios que no tengan restringida dicha 
			// funcionalidad por perfil.
			if ($this->controlador()->usuario_puede_definir_es_copia()) {
				$this->dep('form_datos_imp')->generar_html();
				echo '<hr/>';
			} else {
				$this->eliminar_dep('form_datos_imp');
			}
		}
		$this->dep('alumnos')->generar_html();
	}
}
?>