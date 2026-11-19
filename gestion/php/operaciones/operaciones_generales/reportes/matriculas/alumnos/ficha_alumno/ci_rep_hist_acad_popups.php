<?php
class ci_rep_hist_acad_popups extends ci_impresion
{
	protected $s__equiv;
	protected $s__op;
	protected $s__modulo_res;
	protected $s__equivalencia_tramite;
	protected $s__equivalencia;
	protected $s__actividad;
	protected $s__alumno_op;
	protected $s__elemento;
	protected $s__elemento_revision;
	protected $s__plan_version;
	protected $s__plan_version_origen;
	protected $s__modulo;

	function ini__operacion()
	{
		$this->s__equiv = toba::memoria()->get_parametro('es_equiv');
		if ($this->s__equiv) {
			$this->s__equivalencia_tramite = toba::memoria()->get_parametro('equiv_tramite');
			$this->s__equivalencia = toba::memoria()->get_parametro('equiv');
			$this->s__actividad = toba::memoria()->get_parametro('actividad');
		}

		$this->s__op = toba::memoria()->get_parametro('es_op');
		if ($this->s__op) {
			$this->s__elemento_revision = toba::memoria()->get_parametro('elemento_revision');
			$this->s__plan_version_origen = toba::memoria()->get_parametro('plan_version_origen');
		}
		
		$this->s__modulo_res = toba::memoria()->get_parametro('es_modulo_res');
		if ($this->s__modulo_res) {
			$this->s__elemento = toba::memoria()->get_parametro('elemento');
			$this->s__plan_version = toba::memoria()->get_parametro('plan_version');
			$this->s__modulo = toba::memoria()->get_parametro('modulo');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if ($this->s__equiv) {
			$pantalla->agregar_dep('cuadro_equivalencias');
		}
		if ($this->s__op) {
			$pantalla->agregar_dep('cuadro_otra_propuesta');
		}
		if ($this->s__modulo_res) {
			$pantalla->agregar_dep('cuadro_creditos');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_equivalencias ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_equivalencias(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos_equiv($cuadro);
		$cuadro->set_datos($datos);
	}

	function get_datos_equiv($cuadro)
	{
		$tipo_equivalencia = toba::consulta_php('co_alumnos')->get_tipo_equivalencia($this->s__equivalencia_tramite);
		$cuadro->set_titulo('Detalle Equivalencia ' . $tipo_equivalencia['tipo'] . ' para Actividad: ' . $this->s__actividad);

		if ($tipo_equivalencia['tipo'] == equivalencia::equiv_tramite_tipo_interna) {
			$cuadro->eliminar_columnas(array('libro', 'folio', 'acta'));
			return toba::consulta_php('co_alumnos')->get_equivalencia_interna($this->s__equivalencia);
		}

		if ($tipo_equivalencia['tipo'] == equivalencia::equiv_tramite_tipo_externa) {
			$cuadro->eliminar_columnas(array('propuesta_nombre', 'origen', 'resultado'));
			return toba::consulta_php('co_alumnos')->get_equivalencia_externa($this->s__equivalencia);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_otra_propuesta --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_otra_propuesta(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos_otra_prop();
		$cuadro->set_datos($datos);
	}

	function get_datos_otra_prop()
	{
		return array(toba::consulta_php('co_elementos')->get_datos_actividad_en_plan_version($this->s__elemento_revision, $this->s__plan_version_origen));
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_creditos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_creditos(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->get_datos_modulo_creditos($cuadro);
		$cuadro->set_datos($datos);
	}

	function get_datos_modulo_creditos($cuadro)
	{
		$cuadro->set_titulo('Componentes de módulo aprobado por resolución: ' . $this->s__modulo);
		return toba::consulta_php('co_alumnos')->get_componentes_modulos($this->s__elemento, $this->s__plan_version);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}

}
?>