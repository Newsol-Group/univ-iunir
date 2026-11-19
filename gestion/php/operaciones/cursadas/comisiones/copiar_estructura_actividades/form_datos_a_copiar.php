<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class form_datos_a_copiar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset><legend>Comisiones:</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('datos_comisiones', '172px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('instancias', '142px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					if ($this->ef('docentes_comisiones')->es_solo_lectura()) {
						$ancho_etiqueta = '172px';
					} else {
						$ancho_etiqueta = '170px';
					}
					$this->generar_html_ef('docentes_comisiones', $ancho_etiqueta);
					echo '</td>';
					echo '<td>';
					if ($this->ef('bh_comisiones')->es_solo_lectura()) {
						$ancho_etiqueta = '142px';
					} else {
						$ancho_etiqueta = '140px';
					}
					$this->generar_html_ef('bh_comisiones', $ancho_etiqueta);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					if ($this->ef('copiar_subcomisiones')->es_solo_lectura()) {
						$ancho_etiqueta = '172px';
					} else {
						$ancho_etiqueta = '170px';
					}
					$this->generar_html_ef('copiar_subcomisiones', $ancho_etiqueta);
					echo '</td>';
					echo '<td>';
					if ($this->ef('copiar_division')->es_solo_lectura()) {
						$ancho_etiqueta = '142px';
					} else {
						$ancho_etiqueta = '140px';
					}
					$this->generar_html_ef('copiar_division', $ancho_etiqueta);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
	}
}
?>