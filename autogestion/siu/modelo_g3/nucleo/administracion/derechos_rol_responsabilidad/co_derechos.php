<?php
/**
 * 
 * @package Administración
 */
class co_derechos
{
	function get_derechos($para = null)
	{
		$where = '';
		if(null !== $para){
			$para = quote($para);
			$where = "WHERE gdu_derechos.derecho_para = $para";
		}
		$sql = "SELECT		gdu_derechos.derecho as valor,
							gdu_derechos.nombre as descr
				FROM		gdu_derechos
				$where
				ORDER BY	gdu_derechos.nombre

		";
		return guarani_db::consultar($sql);
	}

	function get_derechos_comision()
	{
		return $this->get_derechos('C');
	}

	function get_derechos_mesa_examen()
	{
		return $this->get_derechos('M');
	}

}
?>