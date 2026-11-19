<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class pant_resultados extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_matrices_hacia()->generar_html();
		$this->dep('cuadro_matrices_hacia')->generar_html();
		echo '<br>';
		
		/**
		 * Si el retorno de la función de copiado fue con errores, entonces
		 * agrego la dependencia para mostrar cuales matrices no se copiaron.
		 */
		$matrices_no_copiadas = $this->controlador()->get_matrices_no_copiadas();
		if (!empty($matrices_no_copiadas)) {
			echo '<hr />';
			echo '<table width=85%>';
			echo '<tr>';
				echo '<td>';
					$this->controlador()->ev_info_matrices_no_copiadas()->generar_html();
				echo '</td>';
				echo '<td>';
					echo '<b> que no pudieron ser copiadas</b>';
				echo '<td>';
			echo '</tr>';
			echo '</table>';
			$this->agregar_dep('cuadro_matrices_no_copiadas');
			$this->dep('cuadro_matrices_no_copiadas')->generar_html();
		}
	}
}
?>