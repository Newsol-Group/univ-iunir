<?php
namespace siu;

use siu\extension_kernel\formularios\fabrica_formularios;
use siu\modelo\entidades\parametro;
use SIU\Chulupi\util\bcrypt;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;

class guarani
{
	const control_error = 'E';
	const control_advertencia = 'A';
	const control_info = 'M';
	const mensaje_tipo = 'mtipo';
	const mensaje_cuerpo = 'mcuerpo';
	const interfaz = 2; //WEB

	static protected $inscripcion_cursada;
	static protected $inscripcion_examen;
	static protected $datos_censales;
	static protected $usuario_cursos;
	static protected $ficha_alumno;
	static protected $historia_academica;
	static protected $plan_estudios;
	static protected $carga_notas_cursada;
	static protected $carga_notas_cursada_comision;
	static protected $carga_notas_examen;
	static protected $carga_notas_mesa_examen;
	static protected $carga_evaluaciones_parciales;
	static protected $carga_asistencias;
	static protected $carga_temas;
	static protected $mensajes;
	static protected $solicitud_certificados;
	static protected $solicitud_constancias;
	static protected $reinscripcion_carrera;
	static protected $readmision;
	static protected $orientacion;
	static protected $cierre_actas_cursada;
	static protected $admin_actas_examen_fc;
	static protected $inscriptos_cursadas;
	static protected $inscriptos_examenes;
	static protected $impresion_actas_examen;
	static protected $zona_examenes;
	static protected $zona_comisiones;
	static protected $zona_clases;
	static protected $cursos;
	static protected $constancias_cursos;
	static protected $info_institucion;
	static protected $pruebas_stress;
	static protected $fabrica_formularios;
	static protected $admin_derechos_comision;
	static protected $admin_derechos_mesa;
	static protected $preinscripcion;
	static protected $solicitar_certificacion;
	static protected $reasignar_optativa;
	static protected $cursos_virtuales;
	static protected $becas;
	static protected $sidcer;
	static protected $boleto_estudiantil;
	static protected $inscripciones;

	static function dir()
	{
		return \realpath(\dirname(__FILE__));
	}

	static function encriptar($texto)
	{
		$enc = new bcrypt(10);
		return $enc->hash(md5($texto));
	}

	static function chequear_clave($sin_encriptar, $encriptada)
	{
		$enc = new bcrypt(10);
		return $enc->verify(md5($sin_encriptar), $encriptada);
	}

	//--------------------------------------------------------------------------
	//---- Entidades
	//--------------------------------------------------------------------------
	
	/**
	 *
	 * @return \siu\modelo\entidades\persona
	 */
	static function persona()
	{
		return kernel::persona();
	}

	static function lista_ubicaciones()
	{
		return catalogo::consultar('sistema', 'ubicaciones');
	}

	static function anios_academicos()
	{
		return catalogo::consultar('unidad_academica', 'anios_academicos');
	}

	static function anio_academico_actual()
	{
		return catalogo::consultar('unidad_academica', 'get_anio_academico_actual');
	}

	static function set_info_institucion()
	{
		if (!isset(static::$info_institucion)) {
			static::$info_institucion = catalogo::consultar('unidad_academica', 'info_institucion');
		}
		return static::$info_institucion;
	}

	static function get_nombre_universidad()
	{
		// Setea la informacion de la institucion
		static::set_info_institucion();
		return static::$info_institucion['nombre'];
	}

	//--------------------------------------------------------------------------
	//---- Transacciones
	//--------------------------------------------------------------------------
	
	/**
	 * @return \siu\modelo\transacciones\inscripcion_cursada
	 */
	static function cursada()
	{
		if (!self::$inscripcion_cursada) {
			$persona = guarani::persona()->get_id();
			$carrera = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			$modalidad = null;
			// División del alumno
			$division = catalogo::consultar('alumno', 'get_division', array('alumno' => $alumno));
			
			self::$inscripcion_cursada = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\inscripcion_cursada", array(
				$persona, $carrera, $alumno, $plan, $plan_version, $ubicacion, $modalidad, $division
			));
		}
		return self::$inscripcion_cursada;
	}

	/**
	 * @return \siu\modelo\transacciones\reinscripcion_carrera
	 */
	static function reinscripcion_carerra()
	{
		if (!self::$reinscripcion_carrera) {
			$nro_inscripcion = guarani::persona()->get_id();
			$carrera = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$reinscripcion_carrera = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\reinscripcion_carrera", array(
				$nro_inscripcion, $carrera, $alumno, $plan, $version, $ubicacion
			));
		}
		return self::$reinscripcion_carrera;
	}

/**
	 * @return \siu\modelo\transacciones\readmision
	 */
	static function readmision()
	{
		if (!self::$readmision) {
			$nro_inscripcion = guarani::persona()->get_id();
			$carrera = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$readmision = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\readmision", array(
				$nro_inscripcion, $carrera, $alumno, $plan, $version, $ubicacion
			));
		}
		return self::$readmision;
	}
	/**
	 *
	 * @return \siu\modelo\transacciones\inscripcion_examen
	 */
	static function examen()
	{
		if (!self::$inscripcion_examen) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			$modalidad = guarani::persona()->get_id_modalidad_activa();
			$division = guarani::persona()->get_id_division_activa();

			self::$inscripcion_examen = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\inscripcion_examen", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion, $modalidad, $division
			));
		}
		return self::$inscripcion_examen;
	}

	/**
	 * 
	 * @return modelo\transacciones\datos_censales
	 */
	static function datos_censales()
	{
		if (!self::$datos_censales) {
			$persona = guarani::persona()->get_id();
			self::$datos_censales = kernel::localizador()->instanciar("modelo\\transacciones\\datos_censales", $persona);
		}
		return self::$datos_censales;
	}
	
	/**
	 * 
	 * @return \siu\modelo\transacciones\inscripcion_examen
	 */
	static function examencurso()
	{
		if (!self::$inscripcion_examen) {
			$persona = guarani::persona()->get_id();
			$propuesta = '1';
			$alumno = '1'; 
			$plan = '1';
			$plan_version = '1';
			$ubicacion = '1'; 
			$modalidad = '1';
			$division = '1';

			self::$inscripcion_examen = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\inscripcion_examen", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion, $modalidad, $division
			));
		}
		return self::$inscripcion_examen;
	}
	
	/**
	 * 
	 * @return modelo\transacciones\preinscripcion
	 */
	static function preinscripcion()
	{
		if (!self::$preinscripcion) {
			$persona = guarani::persona()->get_id();
			self::$preinscripcion = kernel::localizador()->instanciar("modelo\\transacciones\\preinscripcion", $persona);
		}
		return self::$preinscripcion;
	}

	/**
	 * @return \siu\modelo\transacciones\usuario_cursos
	 */
	static function usuario_cursos()
	{
		if (!self::$usuario_cursos) {
			self::$usuario_cursos = kernel::localizador()->instanciar("modelo\\transacciones\\usuario_cursos");
		}
		return self::$usuario_cursos;
	}

	/**
	 * @return \siu\modelo\transacciones\ficha_alumno
	 */
	static function ficha_alumno()
	{
		if (!self::$ficha_alumno) {
			self::$ficha_alumno = kernel::localizador()->instanciar("modelo\\transacciones\\ficha_alumno");
		}
		return self::$ficha_alumno;
	}

	/**
	 * @return \siu\modelo\transacciones\historia_academica
	 */
	static function historia_academica()
	{
		if (!self::$historia_academica) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$historia_academica = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\historia_academica", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion
			));
		}
		return self::$historia_academica;
	}

	/**
	 * @return \siu\modelo\transacciones\plan_estudios
	 */
	static function plan_estudios()
	{
		if (!self::$plan_estudios) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$plan_estudios = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\plan_estudios", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion
			));
		}
		return self::$plan_estudios;
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_cursada
	 */
	static function carga_notas_cursada()
	{
		if (!self::$carga_notas_cursada) {
			$persona = guarani::persona()->get_id();
			self::$carga_notas_cursada = kernel::localizador()->instanciar("modelo\\transacciones\\carga_notas_cursada", $persona);
		}

		return self::$carga_notas_cursada;
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_cursada_comision
	 */
	static function carga_notas_cursada_comision()
	{
		if (!self::$carga_notas_cursada_comision) {
			$persona = guarani::persona()->get_id();
			self::$carga_notas_cursada_comision = kernel::localizador()->instanciar("modelo\\transacciones\\carga_notas_cursada_comision", $persona);
		}

		return self::$carga_notas_cursada_comision;
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_examen
	 */
	static function carga_notas_examen()
	{
		if (!self::$carga_notas_examen) {
			$nro_inscripcion = guarani::persona()->get_nro_inscripcion();
			self::$carga_notas_examen = kernel::localizador()->instanciar("modelo\\transacciones\\carga_notas_examen", $nro_inscripcion);
		}
		return self::$carga_notas_examen;
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_mesa_examen
	 */
	static function carga_notas_mesa_examen()
	{
		if (!self::$carga_notas_mesa_examen) {
			$persona = guarani::persona()->get_id();
			self::$carga_notas_mesa_examen = kernel::localizador()->instanciar("modelo\\transacciones\\carga_notas_mesa_examen", $persona);
		}
		return self::$carga_notas_mesa_examen;
	}

	/**
	 * @return \siu\modelo\transacciones\carga_evaluaciones_parciales
	 */
	static function carga_evaluaciones_parciales()
	{
		if (!self::$carga_evaluaciones_parciales)
			self::$carga_evaluaciones_parciales = kernel::localizador()->instanciar('modelo\\transacciones\\carga_evaluaciones_parciales', guarani::persona()->get_id());
		return self::$carga_evaluaciones_parciales;
	}

	/**
	 * @return \siu\modelo\transacciones\carga_asistencias
	 */
	static function carga_asistencias()
	{
		if (!self::$carga_asistencias) {
			$persona = guarani::persona()->get_id();
			self::$carga_asistencias = kernel::localizador()->instanciar("modelo\\transacciones\\carga_asistencias", $persona);
		}
                
		return self::$carga_asistencias;
	}
        
        /**
	 * @return \siu\modelo\transacciones\carga_temas
	 */
	static function carga_temas()
	{
		if (!self::$carga_temas) {
			$persona = guarani::persona()->get_id();
			self::$carga_temas = kernel::localizador()->instanciar("modelo\\transacciones\\carga_temas", $persona);
		}

		return self::$carga_temas;
	}
	/**
	 * @return \siu\modelo\transacciones\mensajes
	 */
	static function mensajes()
	{
		if (!self::$mensajes)
			self::$mensajes = kernel::localizador()->instanciar('modelo\transacciones\mensajes', guarani::persona()->get_id());
		return self::$mensajes;
	}

	/**
	 * @return \siu\modelo\transacciones\solicitud_certificados
	 */
	static function solicitud_certificados()
	{
		if (!self::$solicitud_certificados) {
			$nro_inscripcion = guarani::persona()->get_nro_inscripcion();
			$carrera = guarani::persona()->get_id_propuesta_activa();
			$legajo = guarani::persona()->get_legajo_activo();
			self::$solicitud_certificados = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\solicitud_certificados", array(
				$nro_inscripcion, $carrera, $legajo
			));
		}
		return self::$solicitud_certificados;
	}

	/**
	 * @return \siu\modelo\transacciones\solicitud_constancias
	 */
	static function solicitud_constancias()
	{
		if (!self::$solicitud_constancias) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$solicitud_constancias = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\solicitud_constancias", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion
			));
		}
		return self::$solicitud_constancias;
	}

	/**
	 * @return \siu\modelo\transacciones\orientacion
	 */
	static function orientacion()
	{
		if (!self::$orientacion) {
			$nro_inscripcion = guarani::persona()->get_nro_inscripcion();
			self::$orientacion = kernel::localizador()->instanciar("modelo\\transacciones\\orientacion", $nro_inscripcion);
		}

		return self::$orientacion;
	}

	/**
	 * @return \siu\modelo\transacciones\cierre_actas_cursada
	 */
	static function cierre_actas_cursada()
	{
		if (!self::$cierre_actas_cursada) {
			$persona = guarani::persona()->get_id();
			self::$cierre_actas_cursada = kernel::localizador()->instanciar("modelo\\transacciones\\cierre_actas_cursada", $persona);
		}
		return self::$cierre_actas_cursada;
	}

	/**
	 * @return \siu\modelo\transacciones\admin_actas_examen_fc
	 */
	static function admin_actas_examen_fc()
	{
		if (!self::$admin_actas_examen_fc) {
			$persona = guarani::persona()->get_id();
			self::$admin_actas_examen_fc = kernel::localizador()->instanciar("modelo\\transacciones\\admin_actas_examen_fc", $persona);
		}
		return self::$admin_actas_examen_fc;
	}

	/**
	 * @return \siu\modelo\transacciones\admin_derechos_comision
	 */
	static function admin_derechos_comision()
	{
		if (!self::$admin_derechos_comision) {
			$persona = guarani::persona()->get_id();
			self::$admin_derechos_comision = kernel::localizador()->instanciar("modelo\\transacciones\\admin_derechos_comision", $persona);
		}
		return self::$admin_derechos_comision;
	}

	/**
	 * @return \siu\modelo\transacciones\admin_derechos_mesa
	 */
	static function admin_derechos_mesa()
	{
		if (!self::$admin_derechos_mesa) {
			$persona = guarani::persona()->get_id();
			self::$admin_derechos_mesa = kernel::localizador()->instanciar("modelo\\transacciones\\admin_derechos_mesa", $persona);
		}
		return self::$admin_derechos_mesa;
	}

	/**
	 * @return \siu\modelo\transacciones\inscriptos_cursadas
	 */
	static function inscriptos_cursadas()
	{
		if (!self::$inscriptos_cursadas) {
			$persona = guarani::persona()->get_id();
			self::$inscriptos_cursadas = kernel::localizador()->instanciar("modelo\\transacciones\\inscriptos_cursadas", $persona);
		}
		return self::$inscriptos_cursadas;
	}

	/**
	 * @return \siu\modelo\transacciones\inscriptos_examenes
	 */
	static function inscriptos_examenes()
	{
		if (!self::$inscriptos_examenes) {
			$persona = guarani::persona()->get_id();
			self::$inscriptos_examenes = kernel::localizador()->instanciar("modelo\\transacciones\\inscriptos_examenes", $persona);
		}
		return self::$inscriptos_examenes;
	}

	/**
	 * @return \siu\modelo\transacciones\impresion_actas_examen
	 */
	static function impresion_actas_examen()
	{
		if (!self::$impresion_actas_examen) {
			$persona = guarani::persona()->get_id();
			self::$impresion_actas_examen = kernel::localizador()->instanciar("modelo\\transacciones\\impresion_actas_examen", $persona);
		}
		return self::$impresion_actas_examen;
	}

	/**
	 * @return \siu\modelo\transacciones\zona_examenes
	 */
	static function zona_examenes()
	{
		if (!self::$zona_examenes) {
			$persona = guarani::persona()->get_id();
			self::$zona_examenes = kernel::localizador()->instanciar("modelo\\transacciones\\zona_examenes", $persona);
		}
		return self::$zona_examenes;
	}

	/**
	 * @return \siu\modelo\transacciones\zona_comisiones
	 */
	static function zona_comisiones()
	{
		if (!self::$zona_comisiones) {
			$persona = guarani::persona()->get_id();
			self::$zona_comisiones = kernel::localizador()->instanciar("modelo\\transacciones\\zona_comisiones", $persona);
		}
		return self::$zona_comisiones;
	}

	/**
	 * @return \siu\modelo\transacciones\zona_clases
	 */
	static function zona_clases()
	{
		if (!self::$zona_clases) {
			$persona = guarani::persona()->get_id();
			self::$zona_clases = kernel::localizador()->instanciar("modelo\\transacciones\\zona_clases", $persona);
		}
		return self::$zona_clases;
	}

	/**
	 * @return \siu\modelo\transacciones\cursos
	 */
	static function cursos()
	{
		if (!self::$cursos) {
			$persona = guarani::persona()->get_id();
			self::$cursos = kernel::localizador()->instanciar("modelo\\transacciones\\cursos", $persona);
		}
		return self::$cursos;
	}

	/**
	 * @return \siu\modelo\transacciones\constancias_cursos
	 */
	static function constancias_cursos()
	{
		if (!self::$constancias_cursos) {
			$persona = guarani::persona()->get_id();
			self::$constancias_cursos = kernel::localizador()->instanciar("modelo\\transacciones\\constancias_cursos", $persona);
		}
		return self::$constancias_cursos;
	}

	/**
	 * @return \siu\modelo\transacciones\pruebas_stress Retorna la transacción 'pruebas_stress'.
	 */
	static function pruebas_stress()
	{
		if (!static::$pruebas_stress) {
			static::$pruebas_stress = kernel::localizador()->instanciar("modelo\\transacciones\\pruebas_stress");
		}
		return static::$pruebas_stress;
	}

	/**
	 * @return \siu\modelo\transacciones\solicitar_certificacion
	 */
	static function solicitar_certificacion()
	{
		if (!self::$solicitar_certificacion) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$solicitar_certificacion = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\solicitar_certificacion", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion
			));
		}
		return self::$solicitar_certificacion;
	}
	
	
	/**
	 * @return \siu\modelo\transacciones\cursos_virtuales
	 */
	static function cursos_virtuales()
	{
		if (!self::$cursos_virtuales) {
			self::$cursos_virtuales = kernel::localizador()->instanciar("modelo\\transacciones\\cursos_virtuales");
		}
		return self::$cursos_virtuales;
	}
	
    /**
	 * @return \siu\modelo\transacciones\becas
	 */
	static function becas()
	{
		if (!self::$becas) {
			$persona = guarani::persona()->get_id();
			self::$becas = kernel::localizador()->instanciar("modelo\\transacciones\\becas", $persona);
		}
		return self::$becas;
	}
	
	/**
	 * @return \siu\modelo\transacciones\reasignar_optativa
	 */
	static function reasignar_optativa()
	{
		if (!self::$reasignar_optativa) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$reasignar_optativa = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\reasignar_optativa", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion
			));
		}
		return self::$reasignar_optativa;
	}
	
	/**
	 * @return \siu\modelo\transacciones\sidcer
	 */
	static function sidcer()
	{
		if (!self::$sidcer) {
			$persona = guarani::persona()->get_id();
			$propuesta = guarani::persona()->get_id_propuesta_activa();
			$alumno = guarani::persona()->get_id_alumno_activo();
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
			$ubicacion = guarani::persona()->get_id_ubicacion_activa();
			self::$sidcer = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\sidcer", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion
			));
		}
		return self::$sidcer;
	}
	
	/**
	 * @return \siu\modelo\transacciones\boleto_estudiantil
	 */
	static function boleto_estudiantil()
	{

		if (!static::$boleto_estudiantil) {

			$persona = static::persona()->get_id();
			$propuesta = static::persona()->get_id_propuesta_activa();
			$alumno = static::persona()->get_id_alumno_activo();
			$plan = static::persona()->get_id_plan_activo();
			$plan_version = static::persona()->get_id_plan_version_activo();
			$ubicacion = static::persona()->get_id_ubicacion_activa();
			$modalidad = static::persona()->get_id_modalidad_activa();
			$division = static::persona()->get_id_division_activa();
			
			static::$boleto_estudiantil = kernel::localizador()->instanciar_params_array("modelo\\transacciones\\boleto_estudiantil", array(
				$persona, $propuesta, $alumno, $plan, $plan_version, $ubicacion, $modalidad, $division
			));
		}

		return static::$boleto_estudiantil;
	}

	/**
	 * @return \siu\modelo\transacciones\inscripciones
	 */
	static function inscripciones()
	{
		if (!static::$inscripciones) {
			$persona = guarani::persona()->get_id();
			static::$inscripciones = kernel::localizador()->instanciar("modelo\\transacciones\\inscripciones", $persona);
		}
		return static::$inscripciones;
	}
	
	static function validador_puntos_de_control($puntos, $parametros, $operacion, $detener_controles_en_rechazo = null)
	{
		$notificador = new \notificacion_controles();
		$interfaz = kernel::persona()->es_perfil_docente() ? \comunes::interfaz_gestion : \comunes::interfaz_autogestion;
		$validador_controles = new \validador_puntos_de_control($notificador, $puntos, $parametros, $interfaz, $operacion);
		if (!isset($detener_controles_en_rechazo)) {
			$param_estado = parametro::get_valor('par_detener_ejecucion_en_rechazo');
			$validador_controles->set_detener_en_rechazo($param_estado == 'S');
		} else {
			$validador_controles->set_detener_en_rechazo($detener_controles_en_rechazo);
		}
		return $validador_controles;
	}

	//--------------------------------------------------------------------------
	//---- Derechos
	//--------------------------------------------------------------------------
	
	static $derechos = array();

	static function derechos($nombre)
	{
		if (!isset(self::$derechos[$nombre])) {
			self::$derechos[$nombre] = kernel::localizador()->instanciar("modelo\\derechos\\$nombre");
		}

		return self::$derechos[$nombre];
	}

	//--------------------------------------------------------------------------
	//---- Componentes interfaz
	//--------------------------------------------------------------------------
	
	/**
	 * @return fabrica_formularios
	 */
	static function formularios()
	{
		if (!self::$fabrica_formularios)
			self::$fabrica_formularios = kernel::localizador()->instanciar('extension_kernel\\formularios\\fabrica_formularios');
		return self::$fabrica_formularios;
	}

	//--------------------------------------------------------------------------
	//---- Paths comunes
	//--------------------------------------------------------------------------

	static function dir_fonts()
	{
		return bootstrap::get_dir_raiz() . '/vendor/rebuy/ezpdf/src/ezpdf/fonts';
	}

	static function path_escalas_js()
	{
		return \siu\bootstrap::get_dir_siu() . '/www/js/escalas/' . bootstrap::instancia()->get_id_bloque_acceso();
	}

	static function get_path_rep_jasper($archivo)
	{
		// Si existe el reporte personalizado devuelvo esa plantilla, sino la original
		$path_jasper = "jasper/";
		$path = \siu\bootstrap::get_dir_siu() . "/../pers_gestion/exportaciones/" . $path_jasper;
		if (!file_exists($path . $archivo)) {
			$path = \siu\bootstrap::get_dir_siu() . "/exportaciones_g3/" . $path_jasper;
		}
		return $path . $archivo;
	}

	/**
	 * Mapeo de letra => resultado.
	 * @return array el mapeo de resultados
	 */
	static function mapeo_resultados()
	{
		return array(
			"A" => "Aprobado",
			"P" => "Promocionado",
			"U" => "Ausente",
			"R" => "Desaprobado"
		);
	}

	/**
	 * Mapeo de letra => resultado.
	 * @return array el mapeo de resultados
	 */
	static function mapeo_tipo_actas()
	{
		return array(
			"A" => "Abierta",
			"C" => "Cerrada",
			"I" => "Impresa sin notas",
			"N" => "Impresa con notas",
			"B" => "Anulada"
		);
	}

	static function mapeo_estado_actas()
	{
		return array(
			"N" => "Normal",
			"R" => "Rectificativa",
		);
	}

	static function crear_mensaje($tipo, $cuerpo)
	{
		return array(
			guarani::mensaje_cuerpo => $cuerpo,
			guarani::mensaje_tipo => $tipo
		);
	}

	static function agregar_texto_inicio_mensajes($mensajes, $texto)
	{
		foreach ($mensajes as $key => $mensaje) {
			$mensajes[$key][guarani::mensaje_cuerpo] = $texto . ' ' . $mensaje[guarani::mensaje_cuerpo];
		}
		return $mensajes;
	}

	/**
	 * Traduce los mensajes devueltos de la BD (comienzan con el código '800').
	 *
	 * @param string $msg_clave Clave del mensaje en el archivo mensajes.es.php
	 * @param array $parametros Parámetros a reemplazar en el mensaje
	 *
	 * @return string Mensaje con los parámetros seteados.
	 */
	static function traducir_mensaje_BD($msg_clave, $parametros = array())
	{

		if (!empty($parametros)) {
			foreach ($parametros as $key => $parametro) {
				$new_key = "%" . ($key + 1) . "%";
				unset($parametros[$key]);
				$parametros[$new_key] = $parametro;
			}
		}

		return kernel::traductor()->trans($msg_clave, $parametros);
	}

}