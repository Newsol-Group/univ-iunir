<?php
class ci_edi_catedras_equivalententes extends ci_edicion_simple
{
	protected $datos_temp;
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$catedra_original = $this->cn()->entidad()->get_datos();
		$catedra_original_actividades = toba::consulta_php('co_catedras_equivalentes')->get_catedra_actividades($catedra_original['catedra']);
		$cuadro->set_datos($catedra_original_actividades);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		$catedra_original = $this->cn()->entidad()->get_datos();
		$catedra_original_docentes = toba::consulta_php('co_catedras_equivalentes')->get_catedra_docentes($catedra_original['catedra']);
		$cuadro->set_datos($catedra_original_docentes);
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
		$catedra_original = $this->cn()->entidad()->get_datos();
		
		$catedra_original_nombre = $catedra_original['nombre'];
		$catedra_original_tipo_nombre = toba::consulta_php('co_catedras_equivalentes')->get_catedra_tipo_nombre($catedra_original['catedra']);
		$catedra_original_estado = toba::consulta_php('co_catedras_equivalentes')->get_catedra_estado($catedra_original['catedra']);
		
		$form->set_datos(array('catedra' => $catedra_original_nombre));
		$form->set_datos(array('catedra_tipo_nombre' => $catedra_original_tipo_nombre[0]['nombre']));
		$form->set_datos(array('catedra_estado' => $catedra_original_estado[0]['catedra_estado']));
		$form->set_datos(array('catedra_codigo' => $catedra_original['catedra']));
		
		if (isset($this->datos_temp)) {
			$datos = $this->datos_temp;
			$this->controlador->set_hubo_cambios();
			$form->set_datos(array('catedra_equivalente' => $datos['catedra_equivalente']));
		} else {
			
			$catedra_equivalente = $this->cn()->entidad()->get_datos_catedras_equivalentes();
			if (isset($catedra_equivalente[0])) {
				$form->set_datos(array('catedra_equivalente' => $catedra_equivalente[0]['catedra_equivalente']));
			}
		}
		$form->set_solo_lectura(array('catedra', 'catedra_tipo_nombre', 'catedra_estado'));
	}
	
	function evt__formulario__modificacion($datos)
	{
		try {
			$catedra_original = $this->cn()->entidad()->get_datos();
			$catedra_original_clave = $catedra_original['catedra'];
			$datos['catedra'] = $catedra_original_clave;
			$this->cn()->entidad()->set_datos_catedras_equivalentes($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function get_catedras_perfil_de_datos($catedra_original)
	{
		$filtro = "sga_catedras.catedra <> $catedra_original";
		return toba::consulta_php('co_catedras')->get_catedras_perfil_de_datos($filtro);
	}
}
?>