<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pant_distribucion_alumnos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr />';
		$this->dep('cuadro_alumnos_a_distribuir')->generar_html();
		echo '<hr />';
		$this->dep('form_comision_destino')->generar_html();
		echo '<br>';
		$this->controlador()->generar_boton('verificar_reubicacion_alumnos');
		$this->controlador()->evento('verificar_reubicacion_alumnos')->set_en_botonera(false);
		if ($this->existe_dependencia('cuadro_alumnos_distribuidos_comision')) {
			echo '<hr />';
			$this->dep('cuadro_alumnos_distribuidos_comision')->generar_html();
		} elseif ($this->existe_dependencia('cuadro_alumnos_distribuidos_subcomision')) {
			echo '<hr />';
			$this->dep('cuadro_alumnos_distribuidos_subcomision')->generar_html();
		}
	}
}
?>