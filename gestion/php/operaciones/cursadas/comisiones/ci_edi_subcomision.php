<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_edi_subcomision extends ci_edicion_entidad
{
	// Datos temporales de los Formularios y Cuadros de selección
	protected $datos_generales_temp;
	protected $datos_docentes_temp;
	protected $seleccion_docentes_temp;
	protected $seleccion_bandas_horarias_temp;
	protected $indx_msg_eof;

	function ini()
	{
		$this->indx_msg_eof = $this->controlador()->get_indx_msg_eof();
	}

	function conf()
	{
		// Se desactivan los extras de la cabecera (link "Ver parámetros")
		$this->pantalla()->set_no_mostrar_extras_cabecera();
	}
	
	/**
	 * Se extiende la función del padre, ci_edicion_entidad, donde se verifica la editabilidad de la entidad, y en caso
	 * de no ser editable se setean de solo lectura las dependencias y se eliminan los eventos de modificación sobre la 
	 * misma. Se definen los eventos adicionales que en este caso particular también permiten modificar la entidad, y se
	 * habilita el evento del filtro.
	 */
	function post_configurar()
	{
		/**
		 * Para esta operación particular se desea que los filtros tengan activados sus eventos, ya que no modifican la
		 * entidad, sino que solo permiten visualizar información. 
		 */
		unset($this->componentes_eventos_a_inhabilitar['filtro']);
		parent::post_configurar();
		if (!$this->cn()->entidad()->es_editable()) {
			// Para el formulario multilínea de docentes asociados.
			$inhabilitar_eventos['formulario_ml'] = array('eliminar');
			// Para los cuadros de docentes y bandas horarias (a seleccionar y asociadas).
			$inhabilitar_eventos['cuadro'] = array('agregar', 'eliminar');
			$this->inhabilitar_componentes_eventos($inhabilitar_eventos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA GENERAL 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_generales(toba_ei_formulario $form)
	{
		$form->ef('letra_desde')->set_expreg(comunes::er_letra);
		$form->ef('letra_hasta')->set_expreg(comunes::er_letra);

		// Si la subcomisión tiene bandas horarias asociadas, no se permite modificar el tipo de clase.
		$bandas_horarias = $this->cn()->entidad()->get_datos_bandas_horarias();
		if (!empty($bandas_horarias)) {
			$form->set_solo_lectura(array('tipo_clase'));
		}

		$form->set_datos_defecto(array('inscripcion_habilitada' => comunes::si));

		if (isset($this->datos_generales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_generales());
		}
	}

	function evt__datos_generales__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_generales($datos);
		} catch (guarani_error $e) {
			$this->datos_generales_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCENTES 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- docentes_asociados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * En el caso de las subcomisiones, la lista de docentes a elegir es limitada ya que 
	 * sólo se mostrarán los que formen parte de la asignación realizada a la comisión. 
	 * Esta pantalla no tendrá filtros ya que el conjunto de docentes será reducido. 
	 * Las fechas Desde y Hasta, serán por defecto las fechas de inicio y fin de dictado del período lectivo. 
	 * En el caso particular de que se este agregando un docente ya comenzado el dictado de un período lectivo, 
	 * la fecha Desde no sera la fecha de inicio del dictado sino la fecha actual.
	 */
	function conf__docentes_asociados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_docentes_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_docentes_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_docentes_subcomision());
		}
	}

	function evt__docentes_asociados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_docentes_subcomision($datos);
		} catch (guarani_error $e) {
			$this->datos_docentes_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__docentes_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_docente_subcomision($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_docentes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_docentes(toba_ei_cuadro $cuadro)
	{
		// Setea el mensaje eof del cuadro de docentes de la comisión.
		// Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		// Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de docentes de la comisión.
		$etiqueta = guarani_etiquetas::get_tabla('sga_docentes_subcomision');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
		// Recupera los docentes ya asociados a la subcomisión para no mostrarlos dentro de los disponibles para seleccionar.
		$docentes_asociados_sc = $this->cn()->entidad()->get_datos_docentes_subcomision();
		$filtro = '';
		if (!empty($docentes_asociados_sc)) {
			$filtro = guarani_sql::armar_condicion_in($docentes_asociados_sc, 'sga_docentes_comision', 'docente', 'docente', true);
		}
		//Recupera los docentes de la comisión padre de esta subcomisión.
		$comision = $this->cn()->entidad()->get_comision_subcomision();
		$docentes_comision = toba::consulta_php('co_comisiones')->get_docentes_comision($comision, $filtro);
		$cuadro->set_datos($docentes_comision);
	}

	function evt__seleccion_docentes__seleccion($seleccion)
	{
		$this->seleccion_docentes_temp = $seleccion;
	}

	function evt__seleccion_docentes__agregar()
	{
		if (!empty($this->seleccion_docentes_temp)) {
			foreach ($this->seleccion_docentes_temp as $docente) {
				$this->cn()->entidad()->agregar_docente_subcomision($docente);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DIAS Y HORARIOS  
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- bandas_horarias_asociadas ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__bandas_horarias_asociadas(toba_ei_cuadro $cuadro)
	{
		$indx_msj = $this->indx_msg_eof;
		$etiqueta = guarani_etiquetas::get_tabla('sga_subcomisiones_bh');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta->get_enf('plural'))));

		$cuadro->set_datos($this->cn()->entidad()->get_datos_bandas_horarias());
	}

	function evt__bandas_horarias_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_banda_horaria($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_bandas_horarias ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_bandas_horarias(toba_ei_cuadro $cuadro)
	{
		$indx_msj = $this->indx_msg_eof;
		$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones_bh');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta->get_enf('plural'))));

		$bandas_horarias_asociadas = $this->cn()->entidad()->get_datos_bandas_horarias();
		$filtro = 'true';
		if (!empty($bandas_horarias_asociadas)) {
			$filtro = guarani_sql::armar_condicion_in($bandas_horarias_asociadas, 'sga_comisiones_bh', 'banda_horaria', 'banda_horaria', true);
		}

		$comision = $this->cn()->entidad()->get_comision_subcomision();
		$filtro .= " AND sga_comisiones_bh.comision = $comision";

		$datos_subcomision = $this->cn()->entidad()->get_datos_generales();
		$tipo_clase = $datos_subcomision['tipo_clase'];
		$filtro .= " AND sga_comisiones_bh.tipo_clase = $tipo_clase";

		// Se excluyen las asignaciones que corresponden a clases agregadas por fuera de las bandas horarias de la comisión.
		$filtro .= ' AND sga_asignaciones.fecha_desde <> sga_asignaciones.fecha_hasta';

		$bandas_horarias_comision = toba::consulta_php('co_bandas_horarias')->get_listado($filtro);
		$cuadro->set_datos($bandas_horarias_comision);
	}

	function evt__seleccion_bandas_horarias__seleccion($seleccion)
	{
		$this->seleccion_bandas_horarias_temp = $seleccion;
	}

	function evt__seleccion_bandas_horarias__agregar()
	{
		if (!empty($this->seleccion_bandas_horarias_temp)) {
			foreach ($this->seleccion_bandas_horarias_temp as $banda_horaria) {
				$this->cn()->entidad()->agregar_banda_horaria($banda_horaria);
			}
		}
	}

}
?>