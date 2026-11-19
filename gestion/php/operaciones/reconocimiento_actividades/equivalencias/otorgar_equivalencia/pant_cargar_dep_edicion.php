<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Otorgar Equivalencia
 * 
 */
class pant_cargar_dep_edicion extends guarani_pers_ei_pantalla
{
	/**
	 * Permite modificar la forma en que se grafica la pantalla, por defecto un componente sobre el otro
	 */
	function generar_layout()
	{
        echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
            echo '<tbody>';
                echo '<tr>';
                    echo '<td>';
						$this->dep('form_actividad')->generar_html();
                    echo '</td>';
                echo '</tr>';
                if ($this->existe_dependencia('form_ml_origen_ext')) {
	                echo '<tr>';
	                    echo '<td>';
							$this->dep('form_ml_origen_ext')->generar_html();
	                    echo '</td>';
	                echo '</tr>';
                }
                if ($this->existe_dependencia('form_ml_origen_int')) {
                	echo '<tr>';
                        echo '<td>';
							$this->dep('form_ml_origen_int')->generar_html();
                    	echo '</td>';
                	echo '</tr>';
                }
            echo '</tbody>';
        echo '</table>';
	}

}
?>