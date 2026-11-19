<?php
class ci_resultados_x_area extends guarani_pers_ci
{
	protected $s__formulario;
	protected $s__filtro;
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if($this->s__formulario)
		{
			$form->set_datos($this->s__formulario);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->s__formulario = $datos;
	}
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if($this->s__filtro)
		{
			$filtro->set_datos($this->s__filtro);
		}
	}

	function evt__filtro__filtrar($datos)
	{
		$this->s__filtro = $datos;
	}
	
	function evt__filtro__cancelar()
	{
		$this->s__filtro = array();
	}
	
	function conf__disponibles(toba_ei_formulario_ml $form_ml)
	{
		if($this->s__formulario && $this->s__formulario['ambito'] && $this->s__formulario['campo_disciplinar'])
		{
			$datos = array();
			$res_ct = toba::consulta_php('co_resultados_de_aprendizaje')->get_listado($this->dep('filtro')->get_sql_where());
			$res_cert = $this->cn()->get_listado($this->s__formulario);
			$ekey = array();
			foreach($res_cert as $v)
			{
				$ekey[] = $v['resultado']; 
			}

			foreach($res_ct as $dt)
			{
				if(!in_array($dt['resultado'],$ekey))
				{
					$datos[]=$dt;
				}
			}
			//print_r($res_ct);
			if($datos)
			{
				$form_ml->set_datos($datos);
			}
		}
	}
	
	function evt__disponibles__agregar($datos)
	{
		if($this->s__formulario && $this->s__formulario['ambito'] && $this->s__formulario['campo_disciplinar'])
		{
			foreach($datos as $dat)
			{
				if($dat['asignado'] == 1)
				{
					$this->cn()->asignar(array('resultado'=>$dat['resultado'], 'ambito' => $this->s__formulario['ambito'], 'campo_disciplinar' => $this->s__formulario['campo_disciplinar'], 'orden_listado'=>$this->cn()->next_orden()));
				}
			}
		}
		
	}
	
	function conf__formulario_ml_asig(toba_ei_formulario_ml $form_ml)
	{
		if($this->s__formulario && $this->s__formulario['ambito'] && $this->s__formulario['campo_disciplinar'])
		{
			$resultados = $this->cn()->get_listado($this->s__formulario);
			if($resultados)
			{
				foreach($resultados as $k=>$v)
				{
					$r = toba::consulta_php('co_resultados_de_aprendizaje')->get_listado("sga_resultados_aprendizaje.resultado = ".$v['resultado']);
					$resultados[$k]['resultado_alcanzado'] = $r[0]['resultado_alcanzado'];
					$resultados[$k]['alcance_nombre'] = $r[0]['alcance_nombre'];
				}
				$form_ml->set_datos($resultados);
			}
		}
	}
	
	function evt__formulario_ml_asig__modificacion($datos)
	{
		if($this->s__formulario && $this->s__formulario['ambito'] && $this->s__formulario['campo_disciplinar'])
		{
			$this->cn()->procesar_datos($datos);
		}
	}

	function evt__formulario_ml_asig__eliminar($datos)
	{
		if($this->s__formulario && $this->s__formulario['ambito'] && $this->s__formulario['campo_disciplinar'])
		{
			$this->cn()->procesar_datos($datos, true);
		}
	}
/*	
	function conf__formulario_ml(toba_ei_formulario_ml $form)
	{
		if($this->s__formulario)
		{
			$datos = $this->cn()->get_listado("ambito = {$this->s__formulario['ambito']} and campo_disciplinar = {$this->s__formulario['campo_disciplinar']}");
			if($datos)
			{
				$form->set_datos($datos);
			}
		}
	}
	
	function evt__formulario_ml__modificacion($datos)
	{
		if($this->s__formulario)
		{
			$sd = array();
			foreach($datos as $k=>$fila)
			{
				$sd[$k] = (isset($fila['x_dbr_clave']))?array_merge($fila,$this->s__formulario):$fila;
			}
			$this->cn()->set_formulario($sd);
		}
	}
	*/
	function extender_objeto_js()
	{
		
		echo "
			if({$this->objeto_js}.dep('formulario')) {
				{$this->objeto_js}.dep('formulario').evt__campo_disciplinar__procesar = function(es_inicial) {
					if (!es_inicial  && this.ef('ambito').tiene_estado() && this.ef('campo_disciplinar').tiene_estado()) {
						{$this->objeto_js}.submit();
					}
				};
				{$this->objeto_js}.dep('formulario').evt__ambito__procesar = function(es_inicial) {
					if (!es_inicial && this.ef('campo_disciplinar').tiene_estado() && this.ef('ambito').tiene_estado()) {
						{$this->objeto_js}.submit();
					}
				};
			}
			
		";
	}
	
	function evt__guardar()
	{
		$this->cn()->sincronizar();
	}
}

?>