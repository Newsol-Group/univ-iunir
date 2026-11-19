<?php
class ci_edi_asignacion_comision extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__from_ci;
	
	protected $seleccionados;

	function conf()
	{
		if(!$this->s__from_ci) {
			toba::memoria()->eliminar_dato('asig_com_editar');
		}
		$this->s__from_ci = true;
		if(toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$this->cn()->cargar(array('comision'=>toba::zona()->get_editable()));
		}
		if(toba::memoria()->get_dato('asig_com_editar')) {
			$this->evento('editar')->ocultar();
		} else {
			$this->evento('procesar')->ocultar();
			$this->evento('cancelar')->ocultar();
		}
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
		//obtener nombre del elemento
		$elem = $this->cn()->get_elemento();
		$form->set_datos($elem);
	}	
	
	//-----------------------------------------------------------------------------------
	//------- asignados -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario_ml_asig(toba_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_resultados();
		$form_ml->set_datos($datos);		
	}
	
	function evt__formulario_ml_asig__modificacion($datos)
	{	
		$elem = $this->cn()->entidad()->get_elemento();
		foreach ($datos as $k=>$dat) {
			$datos[$k] = array('resultado'=>$dat['resultado'], 'porc_alcanzado'=>$dat['porc_alcanzado'], 'comision'=>$elem['comision'], 'apex_ei_analisis_fila' => 'M');
		}		
		$this->cn()->entidad()->set_datos($datos);
	}

	function evt__formulario_ml_asig__eliminar($datos)
	{
		$this->cn()->entidad()->eliminar_fila($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}	
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$asignados = $this->cn()->entidad()->get_resultados();
		
		$filtro = 'sga_resultados_tipos.resultado_tipo = \'C\'';
		if (!empty($asignados)) {			
			$filtro .= ' AND '.guarani_sql::armar_condicion_in($asignados, 'sga_resultados_aprendizaje', 'resultado', 'resultado', true);
		}
		
		if (($filtro != '') && ($this->s__filtro != '')) {
			$filtro .= ' AND ';
		}
		
		$filtro .= $this->s__filtro;
		if(toba::memoria()->get_dato('asig_com_editar')) {
			$datos = toba::consulta_php('co_resultados_de_aprendizaje')->get_listado($filtro);
		} else {
			$e = toba::zona()->get_info();
			$filtro .= ' AND sga_elementos.elemento = '.$e['elemento'].' ';
			$datos = toba::consulta_php('co_competencias')->get_competencias_x_actividad($filtro);
			foreach($datos as $k=>$d) {
				$datos[$k]['resultado'] = 1;
			}
		}
		$cuadro->set_datos($datos);
	}

	function evt__disponibles__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

	function evt__disponibles__agregar()
	{
		$elem = $this->cn()->entidad()->get_elemento();
		if($this->seleccionados) {
			foreach ($this->seleccionados as $dato) {
				$this->cn()->entidad()->asignar(array('resultado'=>$dato['resultado'], 'porc_alcanzado'=>100, 'elemento'=>$elem['elemento']));
			}
		}
	}
	
	function evt__procesar() 
	{
		$this->cn()->entidad()->sincronizar();
		toba::memoria()->eliminar_dato('asig_com_editar');
	}

	function evt__cancelar()
	{
		toba::memoria()->eliminar_dato('asig_com_editar');
	}
	
	function evt__editar()
	{
		toba::memoria()->set_dato('asig_com_editar', true);
	}
}
?>