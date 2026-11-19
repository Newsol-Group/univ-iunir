<?php
class pant_abm_detalle extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div id='div_f_actividad' style='float:left; width: 98%; overflow: auto'>";
		$this->dep('ct_form_actividad')->generar_html();
		echo "</div><div style='float:left; width: 2%; height: 400px;position:relative'>";
		echo "<div style='position: absolute; top: 48%; left: 0px; right: 0px;text-align: center'>
			 <img id='expandir_der' style='display:none' onclick='this.style.display=\"none\";document.getElementById(\"expandir_izq\").style.display=\"block\"; document.getElementById(\"div_f_seleccion\").style.display=\"none\"; document.getElementById(\"div_f_actividad\").style.width=\"98%\"; ' src='".toba_recurso::imagen_toba('nucleo/expandir_der.gif'). "'/>
			 <img id='expandir_izq' onclick='this.style.display=\"none\";document.getElementById(\"expandir_der\").style.display=\"block\"; document.getElementById(\"div_f_actividad\").style.width=\"49%\"; document.getElementById(\"div_f_seleccion\").style.display=\"block\"; ' src='".toba_recurso::imagen_toba('nucleo/expandir_izq.gif'). "'/></div>";
		echo "</div><div id='div_f_seleccion' style='float:right; width: 49%; overflow: auto; display: none'>";
		$this->dep('form_seleccion')->generar_html();
		echo "</div>";
	}

}

?>