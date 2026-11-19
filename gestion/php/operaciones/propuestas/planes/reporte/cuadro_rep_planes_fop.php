<?php
class cuadro_rep_planes_fop extends cuadro_salida_fop
{
	
	/**
	 * Genera el xml del componente
	 * @param boolean $inicial Si es el primer elemento llamado desde vista_xml
	 * @param string $xmlns Namespace para el componente
	 * @return string XML del componente
	 */
	function vista_xml($inicial=false, $xmlns=null)
	{
		if ($this->datos_cargados()) {
			$salida = parent::vista_xml($inicial, $xmlns);
		} else {
			if ($this->_info_cuadro["eof_invisible"]!=1){
				if(trim($this->_info_cuadro["eof_customizado"])!=""){
					$texto = $this->_info_cuadro["eof_customizado"];
				}else{
					$texto = "No hay datos cargados";
				}
			} else {
				$texto = '';
			}
			$salida = $this->xml_texto($texto);
		}
		return $salida;
	}
	
}
?>
