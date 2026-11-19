<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class ci_edi_optativa_de_actividad_generica extends guarani_pers_ci
{
	//Datos temporales de los Formularios y Cuadros de selección
	protected $datos_generales_temp;
	protected $seleccion_modalidades_temp;
	
	// Filas agregadas por defecto.
	protected $s__modalidades_defecto_agregadas = false;

	//Generales
	protected $s__generica_seleccionada;
	protected $s__optativa_seleccionada;
	
	function ini()
	{
		$this->dep('seleccion_modalidades')->set_columna_ordenamiento('modalidad_nombre');
	}
	
	/**
	 * Se resetean las variables de sesión utilizadas para mantener el estado de los filtros y otra información 
	 * para poder limpiarlas desde el ci padre o desde el ci de navegación, ante el cambio de nodo en el árbol, o el
	 * evento cancelar edición de un nodo o volver a la pantalla de selección.
	 */
	function resetear_vars()
	{
		unset($this->s__generica_seleccionada);
		unset($this->s__optativa_seleccionada);
		unset($this->datos_generales_temp);
		unset($this->seleccion_modalidades_temp);
	
		$this->s__modalidades_defecto_agregadas = false;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		/**
		 * En caso en que los atributos de la optativa en el plan versión no sean editables, porque ya optativa
		 * ya existe en el plan, entonces deben setearse de sólo lectura los formularios.
		 */
		if ($this->cn()->entidad()->modo_optativas_temporales() && $this->cn()->entidad()->optativa_seleccionada_existe_en_plan()) {
			$this->dep('form_datos_generales')->set_solo_lectura();
			$this->dep('seleccion_modalidades')->set_solo_lectura();
		}
		//Se obtienen los datos del Filtro y la selección del usuario en la pantalla inicial (ci de navegación)
		$this->s__generica_seleccionada = $this->controlador()->get_actividad_generica();
		$this->s__optativa_seleccionada = $this->controlador()->get_actividad_optativa();
	}

 	//-----------------------------------------------------------------------------------
	//---- PANTALLA GENERAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_generales(toba_ei_formulario $form)
	{
		$form->ef('vigencia')->set_expreg(comunes::er_numero_entero);
		$form->ef('plazo')->set_expreg(comunes::er_numero_entero);
		$form->ef('cantidad_de_aplazos')->set_expreg(comunes::er_numero_entero);
		if (isset($this->datos_generales_temp)) {
			$this->controlador()->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_optativa_seleccionada());
		}
	}

	function evt__form_datos_generales__modificacion($datos)
	{
		$this->datos_generales_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_modalidades --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_modalidades(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_modalidades_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_modalidades_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_optativas_modalidad();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);
		$datos_posibles = toba::consulta_php('co_planes')->get_modalidades($filtro);

		$form_ml->set_datos($datos);

		// Por defecto deberán aparecer seleccionadas todas las modalidades.
		if ($this->cn()->entidad_es_nueva() && !$this->s__modalidades_defecto_agregadas) {
			$this->s__modalidades_defecto_agregadas = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	function evt__seleccion_modalidades__modificacion($datos)
	{
		$this->seleccion_modalidades_temp = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	// Se define un evento para poder Cancelar la edición o el agregado de un Nodo en el árbol.
	function evt__limpiar()
	{
		$this->resetear_vars();
		$this->controlador()->evt__resetear();
	}

	// Se confirmar los cambios realizados en la edición del nodo actual.
	function evt__confirmar()
	{
		try {
			if (isset($this->datos_generales_temp)) {
				$this->cn()->entidad()->set_datos_optativa_seleccionada($this->datos_generales_temp);
				unset($this->datos_generales_temp);
			}
			if (isset($this->seleccion_modalidades_temp)) {
				$this->cn()->entidad()->set_datos_optativas_modalidad($this->seleccion_modalidades_temp);
				unset($this->seleccion_modalidades_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
		$this->evt__limpiar();
	}
}
?>