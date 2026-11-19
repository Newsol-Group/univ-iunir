<?php
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
	    			<td colspan=2>';
		echo 			$this->generar_html_ef('disponible_autogestion', '120px');
		echo ' 		</td>
	    		</tr>	    			    		
	    	</table>';
	}

}

?>