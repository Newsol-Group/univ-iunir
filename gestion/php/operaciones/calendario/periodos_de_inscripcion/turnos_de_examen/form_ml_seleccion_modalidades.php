<?php
class form_ml_seleccion_modalidades extends form_ml_requerido
{
	function generar_layout($ancho='auto')
	{
		echo '<hr />';
		echo '<fieldset><legend>Modalidades:</legend>';
		parent::generar_layout('100%');
		echo '</fieldset>';
	}

}

?>