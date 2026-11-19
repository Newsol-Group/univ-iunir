<?php
/**
 * Catalogo de tipos de elementos
 *
 *	Atencion: esta clase se instancia con: guarani::catalogo_elementos_plan()
 *
 * @package Propuestas
 * @subpackage Plan
 */
class elemento_nucleo
{
	// Tipo de elementos
	const tipo_actividad = g3entidad::tipo_actividad ;
	const tipo_modulo = g3entidad::tipo_modulo ;
	
	// Origen de Créditos
	const creditos_componentes = 'A';
	const creditos_modulo = 'M';
	const creditos_no_informa = 'N';

	// Estado
	const estado_activo = 'A';
	const estado_activo_desc = 'Activo';
	const estado_baja = 'B';
	const estado_baja_desc = 'Baja';
	const estado_aprobado = 'A';
	const estado_aprobado_descr = 'Aprobada';
	const estado_regularizada_descr = 'Regularizada';
	
	// Subtipo Modulos  0 A 49
	const subtipo_raiz									= 0;
	const subtipo_agrupador_modulos						= 1;
	const subtipo_materia_generica						= 2;
	const subtipo_orientacion							= 3;
	const subtipo_agrupador_orientaciones				= 4;

	const subtipo_area_conocimiento						= 5;
	const subtipo_area_actividades_extracurriculares	= 6;
	const subtipo_compentencia							= 7;
	const subtipo_agrupador_compentencia				= 8;

	//const subtipo_raiz_certificado = 9;
	//const subtipo_agrupador_certificados = 10;


	const subtipo_agrupador_correlativas	= 10;
	
	const subtipo_modulo_rtf = 11;
	
	
	// Subtipo Actividades 50 A 99  
	const piso_subtipos_actividades			= 50;
	const subtipo_materia					= 50;
	const subtipo_actividad_extracurricular = 51;
	const subtipo_tesis						= 52;
	const subtipo_practica_profesional		= 53;
	const subtipo_pasantia					= 54;
	const subtipo_seminario					= 55;
	
	//const subtipo_requisito = 4;
	//const subtipo_certificacion = 5;
	
	// Disponibilidades para las actividades
	const para_mesas_de_examen_y_comisiones		= 'T';
	const solo_para_creacion_mesas_de_examen	= 'M';
	const solo_para_creacion_comisiones			= 'C';
	
	// Optativa compartida entre genéricas
	const no_es_optativa_compartida							= 0;
	const es_optativa_compartida_asignada_a_esa_generica	= 1;
	const es_optativa_compartida_asignada_a_otra_generica	= 2;
	const es_optativa_compartida_sin_generica_asignada		= -1;
    
    const cantidad_prioridades = 30;
	
	// Reglas
	const regla_default = regla::regla_default;
	const regla_parametros_default = regla::parametros_default;

	static protected $instancia;
	protected $info_elementos;
	
	protected $elementos_composicion;
	protected $modulos_composicion_modulos;
	protected $modulos_composicion_actividades;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new elemento();	
		}
		return self::$instancia;	
	}

	private function __construct()
	{
		// Cargo la definicion de los subtipos
		$subtipos = toba::consulta_php('co_elementos')->get_subtipos();
		foreach($subtipos as $id => $subtipo) {
				$this->info_elementos[$subtipo['entidad_subtipo']] = $subtipo;
		}
		// Cargo las reglas de composicion
		$composiciones = toba::consulta_php('co_elementos')->get_subtipos_reglas_composicion();
		foreach($composiciones as $composicion) {
			$this->elementos_composicion[$composicion['entidad_subtipo_padre']][] = $composicion['entidad_subtipo_hijo'];
		}
	}

	/**
	 * Chequea el subtipo recibido esta habilitado para tener hijos, No chequea si se le pueden Agregar hijos (es modificable)
	 *
	 * @param $subtipo Subtipo g3entidad
	 * @return Boolean
	 */
	static function puede_tener_hijos($subtipo)
	{
		//Se realiza utilizando la misma consulta que para hijos validos, si el subtipo tiene algún valor 
		//asociado, puede tener hijos.
		$hijos_validos = toba::consulta_php('co_elementos')->get_subtipos_reglas_composicion($subtipo);
		return !empty($hijos_validos);
	}
	
	/**
	 * Consulta a la base y Determina si un elemento es compartible o no
	 * @param $elemento
	 * @return unknown_type
	 */
	static function es_compartible($elemento) 
	{
	    $datos = toba::consulta_php('co_elementos')->get_info_elemento($elemento);
		return $datos['compartible'] == 'S';
	}


	//--------------------------------------------------------------------------------------
	//-- Informacion sobre clases
	//--------------------------------------------------------------------------------------

	function get_icono($entidad_subtipo)
	{
		$this->existe_subtipo($entidad_subtipo);
		return $this->info_elementos[$entidad_subtipo]['icono'];
	}
		
	//--------------------------------------------------------------------------------------
	//-- Acceso carga de clases -----------------------------------------------------------
	//--------------------------------------------------------------------------------------
	
	function get_clase_gui($entidad_subtipo)
	{
		$this->existe_subtipo($entidad_subtipo);
		return $this->info_elementos[$entidad_subtipo]['php_clase_gui'];
	}

	function get_clase_edicion($entidad_subtipo)
	{
		$this->existe_subtipo($entidad_subtipo);
		return $this->info_elementos[$entidad_subtipo]['php_clase_edicion'];
	}

	function get_clase_validacion($entidad_subtipo)
	{
		$this->existe_subtipo($entidad_subtipo);
		return $this->info_elementos[$entidad_subtipo]['php_clase_negocio'];
	}
	
	protected function existe_subtipo($subtipo)
	{
		if(!isset($this->info_elementos[$subtipo])){
			throw new guarani_error('El subtipo de elemento solicitado no existe!');
		}
	}


	//--------------------------------------------------------------------------------------
	//-- Reglas de composicion -------------------------------------------------------------
	//--------------------------------------------------------------------------------------
	
	function es_hijo_valido($padre_subtipo, $hijo_subtipo)
	{
		$this->existe_subtipo($padre_subtipo);
		$this->existe_subtipo($hijo_subtipo);
		return in_array($hijo_subtipo, $this->elementos_composicion[$padre_subtipo]);
	}

	/*  TODO ESTO NO SE ESTA USANDO

	
	function get_composicion_elemento_valida($elemento_subtipo)
	{
		if(isset($this->elementos_composicion[$elemento_subtipo])) {
			return $this->elementos_composicion[$elemento_subtipo];
		} else {
			throw new guarani_error('No hay reglas de composicion para el modulo seleccionado');	
		}
	}
	
	//--------------------------------------------------------------------------------------
	//-- Informacion sobre subcomponentes validos
	//--------------------------------------------------------------------------------------	

	//TODO NO SE USA
	function get_info_modulos_hijos_validos($modulo_subtipo)
	{
		$validos = $this->get_composicion_elemento_valida($modulo_subtipo);
		$temp = array();
		foreach($validos as $valido) {
			$temp[]	= $this->info_elemento[$valido];
		}
		return $temp;
	}
	*/
		
}