<?php
class pant_asignaciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('formulario')->generar_html();
		echo '<hr />';
		if(!toba::memoria()->get_dato('asig_com_editar')) {
			$this->dep('formulario_ml_asig')->eliminar_evento('eliminar');
			$this->dep('formulario_ml_asig')->ef('porc_alcanzado')->set_solo_lectura(true);
		}
		$this->dep('formulario_ml_asig')->generar_html();
		echo '<hr />';
		if(toba::memoria()->get_dato('asig_com_editar')) {
			$this->dep('filtro')->generar_html();
			echo '<hr />';
			$this->dep('disponibles')->generar_botones();		
			$this->dep('disponibles')->evento('agregar')->set_en_botonera(false);	
		} else {
			$this->dep('disponibles')->set_titulo('Competencias Esperadas');
			$this->dep('disponibles')->eliminar_evento('agregar');
			$this->dep('disponibles')->eliminar_columnas(array('alcance_nombre'));
			$this->dep('disponibles')->eliminar_evento('seleccion');
		}
		$this->dep('disponibles')->generar_html();
	}

}

?>