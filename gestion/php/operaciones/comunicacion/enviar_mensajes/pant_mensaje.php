<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class pant_mensaje extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_mensaje')->generar_html();
		echo "	<div style='margin-left:112px; margin-right:10px;'>
					<fieldset>
		    			<legend>
		    				Adjuntos...
		    			</legend>
	    	";
		if ($this->existe_dependencia('form_adjuntos')) {
			$this->dep('form_adjuntos')->generar_html();
		}
		$this->dep('cuadro_adjuntos')->generar_html();
		echo "		</fieldset>
				</div>";
	}

}
?>