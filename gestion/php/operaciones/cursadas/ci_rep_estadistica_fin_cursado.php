<?php
class ci_rep_estadistica_fin_cursado extends ci_rep_sin_listado_inicial
{
	function conf()
	{
		if ($this->pantalla()->existe_evento('pdf2')) {
			$datos = $this->cuadro()->get_datos();
			if (empty($datos)) {
				$this->pantalla()->eliminar_evento('pdf2');
			}
		}
	}
	
	function get_datos($filtro)
	{
		return guarani::co('co_comisiones')->get_reporte_cursada($filtro);
	}

	function get_tabla_principal()
	{
		return 'sga_comisiones';
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = '';
			$filtro_propuesta = '';
			if (isset($this->s__datos['propuestas'])) {				
				$filtro_propuesta = " AND EXISTS (SELECT 1 FROM sga_comisiones_propuestas as cp WHERE cp.comision = vw_comisiones.comision AND cp.propuesta = ".$this->s__datos['propuestas']['valor']." )";
				$this->dep('filtro')->columna('propuestas')->resetear_estado();
			}
			$this->s__filtro = $this->dep('filtro')->get_sql_where().$filtro_propuesta;
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuestas')->set_condicion_fija('es_igual_a');
	}
	
	function vista_pdf(toba_vista_pdf $salida)
	{
		$pdf = $salida->get_pdf();
		$texto = $this->get_titulo();
		$salida->separacion();
		$salida->separacion();
		$salida->separacion();
		$pdf->ezText("<b>$texto</b>", 13, array( 'justification' => 'center' ));
		$this->cuadro()->vista_pdf($salida);
	}
	
}
?>