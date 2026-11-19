<?php
/**
 * Catálogo de entidades G3
 *
 *	Atencion: esta clase se instancia con: guarani::catalogo_g3entidades()
 *
 * @package ArquitecturaBase
 * @subpackage G3Entidades
 */
class g3entidad_nucleo
{
	// Tipos
	const tipo_modulo 					= 1;
	const tipo_actividad 				= 2;
	const tipo_subactividad 			= 3;
	const tipo_institucion				= 4;
	const tipo_responsable_academica	= 5;
	const tipo_propuesta				= 6;
	const tipo_plan_estudio				= 7;
	const tipo_plan_estudio_version		= 8;
	const tipo_propuesta_tipo			= 9;
	const tipo_certificado_titulo		= 10;
	const tipo_comision					= 11;
	const tipo_mesa_examen				= 12;
	const tipo_mesa_examen_tipo			= 13;
	
	// Subtipos	

	// del tipo elemento-módulo
	const subtipo_raiz							= 0;
	const subtipo_agrupador						= 1;
	const subtipo_materia_generica              = 2;
	const subtipo_orientacion                   = 3;
	const subtipo_agrupa_orientaciones          = 4;
	const subtipo_area_conocimiento             = 5;
	const subtipo_area_activ_extracurriculares  = 6;
	const subtipo_competencia                   = 7;
	const subtipo_agrupa_competencias           = 8;
	const subtipo_modulo_correlativas           = 9;
	const subtipo_modulo_certificados			= 10;

	// del tipo elemento-actividad
	const subtipo_materia                   	= 50;
	const subtipo_activ_extracurricular 		= 51;
	const subtipo_tesis                     	= 52;
	const subtipo_practica_profesional      	= 53;
	const subtipo_pasantia				    	= 54;
	const subtipo_seminario 					= 55;
	const subtipo_actividad_curso				= 56;
	const subtipo_taller						= 57;
	
	// otros tipos
	const subtipo_institucion					= 100;
	const subtipo_responsable_academica 		= 101;
	const subtipo_ra 							= self::subtipo_responsable_academica;
	const subtipo_resp_acad 					= self::subtipo_responsable_academica;
	const subtipo_plan_estudio 					= 102;
	const subtipo_plan_estudio_version			= 103;
	const subtipo_nacional						= 110;
	const subtipo_internacional					= 111;

	const subtipo_grado							= 200;
	const subtipo_pregrado						= 201;
	const subtipo_posgrado              		= 202;
	const subtipo_ciclo_basico          		= 203;
	const subtipo_curso_ingreso					= 204;
	const subtipo_postitulo						= 205;
	const subtipo_terciario						= 206;
	const subtipo_vocacional					= 207;
	const subtipo_propuesta_curso				= 208;
	const subtipo_convenio						= 209;

	const subtipo_certificado 					= 250;
	const subtipo_comisiones					= 300;
	const subtipo_mesas_examen					= 400;
	const subtipo_mesa_examen_regular			= 401;
	const subtipo_mesa_examen_libre		       	= 402;
	const subtipo_mesa_examen_regular_libre		= 403;
	const subtipo_mesa_examen_revalida		 	= 404;
	const subtipo_turnos_examen				 	= 420;
	const subtipo_llamados					 	= 430;
	const subtipo_mesa_examen_por_llamado	 	= 431;
	
	// Estados para una entidad
	const estado_aprobado			= 'A';
	const estado_cursado			= 'C';
	const estado_inscripto			= 'I';
	const estado_cumplido			= 'M';
	const estado_cursado_simultaneo	= 'X';

	// Tipos de niveles de aplicación.
	const nivel_aplicacion_tipo_total = 'T';
	const nivel_aplicacion_tipo_subtipo = 'S';
	const nivel_aplicacion_tipo_entidad = 'E';
	
	static protected $instancia;
	static protected $co;
	static protected $entidad_tipo_datos = array();
	static protected $entidad_subtipo_datos = array();

	private function __construct() {}

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new g3entidad();	
		}
		return self::$instancia;
	}
	
	static function co() {
		if (! isset(self::$co) ) {
			self::$co = toba::consulta_php('co_g3entidades');
		}
		return self::$co;
	}
	
	/*
		Devuelve los datos correspondientes
		del tipo de g3entidad recibido
	*/
	static function get_datos_tipo($tipo) 
	{
		if (! isset(self::$entidad_tipo_datos[$tipo])) {
			self::$entidad_tipo_datos[$tipo] = toba::consulta_php('co_g3entidades')->get_datos_tipo($tipo);
			self::$entidad_tipo_datos[$tipo]['tabla'] = static::get_tabla_tipo($tipo);
		}
		return self::$entidad_tipo_datos[$tipo];
	}
	
	/*
		Devuelve los datos correspondientes
		del subtipo de g3entidad recibido
	*/
	static function get_datos_subtipo($subtipo) 
	{
		if (! isset(self::$entidad_subtipo_datos[$subtipo])) {
			self::$entidad_subtipo_datos[$subtipo] = toba::consulta_php('co_g3entidades')->get_datos_subtipo($subtipo);
		}
		return self::$entidad_subtipo_datos[$subtipo];
	}	
	
	/**
        Obtiene los datos de una g3entidad. 
    */	
	static function get_datos_entidad($entidad)
	{
		return static::co()->get_datos_entidad($entidad);
	}
	
	/*
		Devuelve el nombre de la tabla 
		correspondiente del subtipo de g3entidad recibido
	*/
	static function get_tabla_subtipo($subtipo) 
	{
		$datos_subtipo = static::get_datos_subtipo($subtipo);
		return $datos_subtipo['tabla'];
	}

	/*
		Devuelve el nombre de la clase gui correspondiente al subtipo de g3entidad recibido
	*/
	static function get_clase_gui_subtipo($subtipo) 
	{
		$datos_subtipo = static::get_datos_subtipo($subtipo);
		return $datos_subtipo['php_clase_gui'];
	}
	
	/*
		Devuelve el subtipo de entidad correspondiente a un id de g3entidad dado
	*/	
	static function get_subtipo_entidad($entidad)
	{
		return toba::consulta_php('co_g3entidades')->get_subtipo_entidad($entidad);
	}
	
	/*
		Devuelve el tipo de entidad correspondiente a un id de g3entidad dado
	*/	
	static function get_tipo_entidad($entidad)
	{
		$subtipo = toba::consulta_php('co_g3entidades')->get_subtipo_entidad($entidad);
		return static::get_tipo($subtipo);
	}
	
	/*
		Devuelve el tipo de entidad correspondiente a un subtipo de entidad dado
	*/	
	static function get_tipo($subtipo)
	{
		$datos = static::get_datos_subtipo($subtipo);
		return $datos['entidad_tipo'];
	}
		
	/*
		Devuelve el nombre del campo que es clave en la tabla 
		correspondiente del subtipo de g3entidad recibido
	*/
	static function get_campo_clave_subtipo($subtipo) 
	{
		if (! isset(self::$entidad_subtipo_datos[$subtipo]['clave'])) {
			$tabla = static::get_tabla_subtipo($subtipo);
			$claves = toba::tabla($tabla)->get_clave();
			self::$entidad_subtipo_datos[$subtipo]['clave'] = $claves[0];	
		}
		return self::$entidad_subtipo_datos[$subtipo]['clave'];
	}

	/*
		Devuelve los estados posibles, con sus reglas, para un subtipo de g3entidad dado
	*/	
	static function get_subtipo_estados($subtipo)
	{
		return toba::consulta_php('co_g3entidades')->get_subtipo_estados($subtipo);
	}
	
	static function get_subtipo_estado_default($subtipo)
	{
		return toba::consulta_php('co_g3entidades')->get_subtipo_estado_default($subtipo);
	}
	
	static function get_clase_consulta_subtipo($subtipo)
	{
		switch ($subtipo) {
			case self::subtipo_competencia :
			case self::subtipo_raiz :
			case self::subtipo_agrupador :
			case self::subtipo_orientacion :
			case self::subtipo_agrupa_orientaciones :
			case self::subtipo_area_conocimiento :
			case self::subtipo_area_activ_extracurriculares :
			case self::subtipo_agrupa_competencias :
			case self::subtipo_modulo_correlativas :
			case self::subtipo_modulo_certificados :
			case self::subtipo_materia_generica :
			case self::subtipo_materia :
			case self::subtipo_activ_extracurricular :
			case self::subtipo_tesis :
			case self::subtipo_practica_profesional :
			case self::subtipo_pasantia :
			case self::subtipo_seminario :
			case self::subtipo_actividad_curso :
			case self::subtipo_taller :
				return 'co_elementos';
			case self::subtipo_institucion :
				return 'co_instituciones';
				break;
			case self::subtipo_responsable_academica :
			case self::subtipo_ra :
			case self::subtipo_resp_acad :
				return 'co_responsables_academicas';			
			case self::subtipo_grado :
			case self::subtipo_pregrado :
			case self::subtipo_posgrado :
			case self::subtipo_ciclo_basico :
			case self::subtipo_curso_ingreso :
			case self::subtipo_postitulo :
			case self::subtipo_terciario :
			case self::subtipo_vocacional :
			case self::subtipo_propuesta_curso :
			case self::subtipo_convenio :
				return 'co_propuestas';
			case self::subtipo_plan_estudio  :
			case self::subtipo_plan_estudio_version :
				return 'co_planes';
			case self::subtipo_nacional :
				break;
			case self::subtipo_internacional :
				break;
			case self::subtipo_certificado :
				return 'co_certificados';
			case self::subtipo_comisiones :
				return 'co_comisiones';
			case self::subtipo_mesas_examen :				
			case self::subtipo_mesa_examen_regular :
			case self::subtipo_mesa_examen_libre :
			case self::subtipo_mesa_examen_regular_libre :
			case self::subtipo_mesa_examen_revalida :
				return 'co_mesas_de_examen';
			case self::subtipo_turnos_examen :
				return 'co_turnos_de_examen';
			case self::subtipo_llamados :
				break;
			case self::subtipo_mesa_examen_por_llamado :
				break;
		} 
	}
	
	static function get_clase_consulta_tipo($entidad_tipo)
	{
		switch ($entidad_tipo) {
			case self::tipo_institucion :
				return 'co_instituciones';
			case self::tipo_responsable_academica :
				return 'co_responsables_academicas';
			case self::tipo_propuesta :
			case self::tipo_propuesta_tipo :
				return 'co_propuestas';
			case self::tipo_plan_estudio :
			case self::tipo_plan_estudio_version :
				return 'co_planes';
		}
	}
	
	static function get_metodo_consulta_tipo($entidad_tipo)
	{
		switch ($entidad_tipo) {
			case self::tipo_institucion :
			case self::tipo_responsable_academica :
			case self::tipo_propuesta :
			case self::tipo_plan_estudio :
				return 'get_listado';
			case self::tipo_plan_estudio_version :
				return 'get_lista_planes';
			case self::tipo_propuesta_tipo :
				return 'get_propuestas_tipos_reporte';
		}
	}
	
	/*
		Devuelve el nombre de la tabla 
		correspondiente del tipo de g3entidad recibido
	*/
	static function get_tabla_tipo($entidad_tipo) 
	{
		switch ($entidad_tipo) {
			case self::tipo_institucion :
				return 'sga_instituciones';
			case self::tipo_responsable_academica :
				return 'sga_responsables_academicas';
			case self::tipo_propuesta :
				return 'sga_propuestas';
			case self::tipo_plan_estudio :
				return 'sga_planes';
			case self::tipo_plan_estudio_version :
				return 'sga_planes_versiones';
		}
	}
	
	static function get_entidad_id_nombre($entidad)
	{

		$data = toba::consulta_php('co_g3entidades', true)->get_entidad_id_nombre($entidad);
		$array = explode(",", $data['get_entidad_id_nombre']);

		$retorno['nombre']			= $array[0];
		$retorno['id']				= $array[1];
		$retorno['nombre_entidad']	= $array[2];
		
		return $retorno;
	}

	static function get_tipos_niveles_perfil_datos()
	{
		$tipos = array(g3entidad::tipo_institucion, g3entidad::tipo_responsable_academica, g3entidad::tipo_propuesta, g3entidad::tipo_plan_estudio, g3entidad::tipo_plan_estudio_version);
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			$perfil_datos_restricciones = current(toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos));
			$dominio_unidad_gestion = toba::consulta_php('co_unidades_gestion')->get_dominio_unidad_gestion($perfil_datos_restricciones['clave']);
			switch ($dominio_unidad_gestion) {
				case unidades_gestion::dominio_institucion:
					break;
				case unidades_gestion::dominio_tipo_prop:
				case unidades_gestion::dominio_ra:
					$tipos = array(g3entidad::tipo_responsable_academica, g3entidad::tipo_propuesta, g3entidad::tipo_plan_estudio, g3entidad::tipo_plan_estudio_version);
					break;
				case unidades_gestion::dominio_prop:
					$tipos = array(g3entidad::tipo_propuesta, g3entidad::tipo_plan_estudio, g3entidad::tipo_plan_estudio_version);
			}
		}
		return $tipos;
	}

	static function get_subtipos_niveles_perfil_datos()
	{
		$subtipos = array(g3entidad::subtipo_grado, g3entidad::subtipo_pregrado, g3entidad::subtipo_posgrado, g3entidad::subtipo_ciclo_basico, g3entidad::subtipo_curso_ingreso, g3entidad::subtipo_postitulo, g3entidad::subtipo_terciario, g3entidad::subtipo_vocacional, g3entidad::subtipo_propuesta_curso, g3entidad::subtipo_convenio);
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			$perfil_datos_restricciones = current(toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos));
			$dominio_unidad_gestion = toba::consulta_php('co_unidades_gestion')->get_dominio_unidad_gestion($perfil_datos_restricciones['clave']);
			switch ($dominio_unidad_gestion) {
				case unidades_gestion::dominio_institucion:
				case unidades_gestion::dominio_tipo_prop:
					break;
				case unidades_gestion::dominio_ra:
				case unidades_gestion::dominio_prop:
					$subtipos = array();
			}
		}
		return $subtipos;
	}
}
?>