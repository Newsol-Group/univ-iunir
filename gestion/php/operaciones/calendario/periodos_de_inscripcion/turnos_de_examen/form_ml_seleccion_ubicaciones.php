<?php
class form_ml_seleccion_ubicaciones extends form_ml_requerido
{
	function generar_layout($ancho='auto')
	{
		echo '<fieldset><legend>Ubicaciones:</legend>';
		parent::generar_layout('100%');
		echo '</fieldset>';
	}

}

?>