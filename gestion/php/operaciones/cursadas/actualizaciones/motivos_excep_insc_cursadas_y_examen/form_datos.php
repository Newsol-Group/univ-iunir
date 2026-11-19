<?php
/**
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class form_datos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table border=0 width=100%>
	    		<tr>
	    			<td colspan=2>';
		echo 			$this->generar_html_ef('nombre');
		echo '		</td>
	    		</tr>
	    		<tr>
	    			<td colspan=2>';
		echo 			$this->generar_html_ef('descripcion');
		echo '		</td>
	    		</tr>
	    		<tr>
	    			<td>';
		echo 			$this->generar_html_ef('alcance');
		echo ' 		</td>
	    			<td>';
		echo 			$this->generar_html_ef('activo');
		echo ' 		</td>
	    		</tr>	    			    		
	    	</table>';
	}

}
?>