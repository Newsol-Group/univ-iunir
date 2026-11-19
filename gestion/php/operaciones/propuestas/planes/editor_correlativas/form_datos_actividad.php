<?php
class form_datos_actividad extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table class="tabla-0" width="100%" border="0" >';
		echo '<tbody>';
		echo '<tr>';
		echo '<td class="ei-cuadro-cc-tit-nivel-0" colspan="6">'.$this->label('nombre');
		echo '<strong>('.$this->value('codigo').')&nbsp;'.$this->value('nombre').'</strong>';
		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}
	
	function label($campo) 
	{
		return $this->ef($campo)->get_etiqueta().':&nbsp;';
	}
	
	function value($campo) 
	{
		return $this->ef($campo)->get_estado();
	}
	
	
}
?>