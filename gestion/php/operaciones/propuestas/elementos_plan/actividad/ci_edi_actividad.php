<?php
/**
 *
 *
 * @package		Propuestas
 * @subpackage 	PlanElementos
 * 
 */
class ci_edi_actividad extends guarani_pers_ci
{
	protected $datos_materia_temp;
	protected $datos_elemento_temp;
	protected $datos_atributos_extra_temp;
	protected $datos_ra_temp;
	protected $datos_categorias_temp;
	protected $datos_extracurricular_temp;
	protected $es_extracurricular = false;

	function ini()
	{
		$this->dep('form_elemento_ra')->set_columna_ordenamiento('nombre');
	}

	function conf()
	{
		if (!$this->cn()->entidad_es_nueva()) {
			$this->dep('elemento')->set_solo_lectura('entidad_subtipo');
			if (! $this->controlador()->estoy_duplicando()) {
				$this->dep('elemento')->set_solo_lectura('codigo');
				if ($this->cn()->entidad()->pertenece_a_plan_activo()) {
					$this->dep('elemento')->set_solo_lectura('nombre');
				}
			}
			$subtipo = $this->cn()->entidad()->dep('datos')->tabla('elemento')->get_fila_columna(0, 'entidad_subtipo');
			$this->cn()->entidad()->set_subtipo($subtipo);
			if ($this->get_id_pantalla() == 'pant_inicial') {
				if ($subtipo == elemento::subtipo_actividad_extracurricular) {
					$this->es_extracurricular = true;
					$this->pantalla('pant_inicial')->eliminar_dep('materia');
				} else {
					$this->pantalla('pant_inicial')->eliminar_dep('extracurricular');
				}
			}
		}
	}
	
	//----------------------------------------------------------------------------------------------
	//---- pant_inicial ----------------------------------------------------------------------------
	//----------------------------------------------------------------------------------------------
	
	//---- Form elementos -------------
	
	function evt__elemento__modificacion($datos)
	{
		if ($datos['entidad_subtipo'] == elemento::subtipo_actividad_extracurricular) {
			$this->es_extracurricular = true;
		}
		
		//Si el estado es Baja se desetea el campo Disponible Para así se graba con su valor por default
		if ($datos['estado'] == elemento::estado_baja) {
			unset($datos['disponible_para']);
		}
		
		try {
			$this->cn()->entidad()->set_datos_elemento($datos, $this->controlador()->estoy_duplicando());
			$this->cn()->entidad()->set_datos_revision($datos);
		} catch (guarani_error $e) {
			$this->datos_elemento_temp = $datos;
		}
	}

	function conf__elemento(toba_ei_formulario $form)
	{
		$datos_defecto = array('disponible_para' => elemento::para_mesas_de_examen_y_comisiones);
		$form->set_datos_defecto($datos_defecto);
		
		if (isset($this->datos_elemento_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_elemento_temp);        
		} else {
			$datos_elemento = $this->cn()->entidad()->get_datos_elemento();
			$datos_revision = $this->cn()->entidad()->get_datos_revision();
			if (is_null($datos_elemento)) {
				$datos_elemento = array();
			}
			if (is_null($datos_revision)) {
				$datos_revision = array();
			}
			$datos = array_merge($datos_elemento, $datos_revision);
			
			if ((!is_null($datos)) && (!empty($datos))) {
				$form->set_datos($datos);
			}
		}
	}

	//---- materia -------------

	function evt__materia__modificacion($datos)
	{
		if ($this->es_extracurricular) {
			return;
		}
		try {
			$this->cn()->entidad()->set_datos_materia($datos);
		} catch ( guarani_error $e ) {
			$this->datos_materia_temp = $datos;
		}
	}

	function conf__materia(toba_ei_formulario $form)
	{
		if (isset($this->datos_materia_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_materia_temp);        
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
	}
	
	//---- extracurricular --------------------------------------------------------------

	function evt__extracurricular__modificacion($datos)
	{
		if (! $this->es_extracurricular) {
			return;
		}
		try {
			$this->cn()->entidad()->set_datos_extracurricular($datos);
		} catch ( guarani_error $e ) {
			$this->datos_extracurricular_temp = $datos;
		}
	}
	
	function conf__extracurricular(toba_ei_formulario $form)
	{
		if (isset($this->datos_extracurricular_temp)) {
			$form->set_datos($this->datos_extracurricular_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
	}

	//---- Atributos extra ---------------------------------------------------------------------------

	function evt__atributos_extra__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_extra($datos);
		} catch ( guarani_error $e ) {
			$this->datos_atributos_extra_temp = $datos;
		}
	}

	function conf__atributos_extra($form)
	{
		$form->set_datos_defecto(array('insc_cursada_por_prioridad' => comunes::no));
		if (isset($this->datos_atributos_extra_temp)) {
			$form->set_datos($this->datos_atributos_extra_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			if (isset($datos['hora_inicio_examen'])) {
				$datos['hora_inicio_examen'] = substr($datos['hora_inicio_examen'], 0, 5);
			}
			if (isset($datos['hora_fin_examen'])) {
				$datos['hora_fin_examen'] = substr($datos['hora_fin_examen'], 0, 5);
			}
			$form->set_datos($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- responsables_academicas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_elemento_ra(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_ra_temp)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_ra_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_ra();
		}

		$filtro = guarani_sql::armar_condicion_in($datos, 'sga_responsables_academicas', 'responsable_academica', 'responsable_academica', true);
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos_posibles = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function conf_fila__form_elemento_ra($fila)
	{
		$datos_ml = $this->dep('form_elemento_ra')->get_datos();
		if (!empty($datos_ml[$fila]['requerido']) && $datos_ml[$fila]['requerido'] == 1) {
			$datos_responsable_academica = toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($datos_ml[$fila]['responsable_academica']);
			if (empty($datos_responsable_academica)) {
				$this->dep('form_elemento_ra')->ef('requerido')->set_solo_lectura(true);
			} else {
				$this->dep('form_elemento_ra')->ef('requerido')->set_solo_lectura(false);
			}
		} else {
			$this->dep('form_elemento_ra')->ef('requerido')->set_solo_lectura(false);
		}
	}

	function evt__form_elemento_ra__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_ra($datos);
		} catch (guarani_error $e) {
			$this->datos_ra_temp = $datos;
			throw $e;
		}
	}
	
	//---- categorías -------------------------------------------------------------------

	function evt__categorias__modificacion($datos)
	{
		if (isset($datos) && $this->cn()->entidad()->get_datos_elemento()) {
			$this->cn()->entidad()->set_datos_categorias($datos);
		} else {
			$this->datos_categorias_temp = $datos;
		}
	}

	function conf__categorias($form)
	{
		// Agrego ícono de utilería (popup detalle de categorías)
		$icono_informacion = new icono_info_categoria();
		$icono_informacion->set_ef_referencia('categoria');
		$form->ef('valor')->agregar_icono_utileria($icono_informacion);
		
		if (isset($this->datos_categorias_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_categorias_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_categorias());
		}       
	}

	//------------------------------------------------------------------------------------------

	function post_eventos()
	{
		if ($this->cn()->entidad()->hay_errores()) {
			throw guarani::error($this->cn()->entidad()->get_errores());
		}
	}
	
	function extender_objeto_js()
	{
		$subtipo = $this->cn()->entidad()->dep('datos')->tabla('elemento')->get_fila_columna(0, 'entidad_subtipo');
		if ($this->pantalla()->get_id() == 'pant_inicial' && $subtipo != elemento::subtipo_actividad_extracurricular) {
			$id_form_materia = $this->dependencia('materia')->get_id_objeto_js();
			$id_form_atributos_extra = $this->dependencia('atributos_extra')->get_id_objeto_js();
			$si = comunes::si;
			
			echo "
				//---- Procesamiento de EFs --------------------------------
				
				{$id_form_materia}.evt__requiere_examen__procesar = function(es_inicial)
				{
					var requiere_examen = this.ef('requiere_examen').get_estado();
					if (requiere_examen == '$si') {
						activar_atributos_extra(true);
						this.ef('escala_nota_examen').mostrar();
					} else {
						activar_atributos_extra(false);
						this.ef('escala_nota_examen').resetear_estado();
						this.ef('escala_nota_examen').ocultar();
					}
				}
				
				function activar_atributos_extra(activar)
				{
					if (activar == true) {
						{$id_form_atributos_extra}.ef('dia_examen').activar();
						{$id_form_atributos_extra}.ef('hora_inicio_examen').activar();
						{$id_form_atributos_extra}.ef('hora_fin_examen').activar();
					} else {
						{$id_form_atributos_extra}.ef('dia_examen').resetear_estado();
						{$id_form_atributos_extra}.ef('dia_examen').desactivar();
						
						{$id_form_atributos_extra}.ef('hora_inicio_examen').resetear_estado();
						{$id_form_atributos_extra}.ef('hora_inicio_examen').desactivar();
						
						{$id_form_atributos_extra}.ef('hora_fin_examen').resetear_estado();
						{$id_form_atributos_extra}.ef('hora_fin_examen').desactivar();
					}
				}
				";
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_prioridades()
	{
        $prioridades = [];
        $i=2;
        while ($i <= elemento::cantidad_prioridades) {
            $prioridades[] = ['valor'=> $i, 'descr'=> $i];
            $i++;
        }
        return $prioridades;
	}
}
?>