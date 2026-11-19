<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class form_ml_suplemento extends guarani_pers_ei_formulario_ml
{
	protected function generar_formulario_encabezado(){}
	
	function generar_layout_fila($clave_fila)
	{
		$columnas = 2;
		$i = 0;
		foreach ($this->get_nombres_ef() as $ef) {
			$ultimo = ($i == $this->get_cantidad_efs());			
			if ($i % $columnas == 0) {
				echo "<td colspan='$columnas' class='". toba::escaper()->escapeHtmlAttr($this->estilo_celda_actual)."'>";
			}			
			$this->generar_html_ef($ef);
			$i++;			
			if ($i % $columnas == 0 || $ultimo) {
				echo '</td>';
			}			
		}
	}
}
?>