<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\modelo\g3\g3;
use siu\modelo\util;
use siu\modelo\comunes;

class opciones
{
	const NO_SELECCION = 'null';
	const otra_empresa = -1;

	protected static $instancia;

	/**
	 * 
	 * @return opciones
	 */
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\opciones");
		}
		return self::$instancia;
	}

	function armar_combo_opciones($datos, $campo_clave, $campo_valor, $con_no_seleccion = true, $omitir_no_seleccion_opcion_unica = true)
	{
		$combo = array();
		if (!empty($datos)) {
			if (($con_no_seleccion) && ((count($datos) > 1) || ((count($datos) <= 1) && !$omitir_no_seleccion_opcion_unica) )) {
				$combo[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
			}
			foreach ($datos as $dato) {
				$combo[$dato[$campo_clave]] = $dato[$campo_valor];
			}
		}
		return $combo;
	}

	function tipos_propuestas()
	{
		$datos = util::consultas()->get_propuestas_tipos();
		return static::armar_combo_opciones($datos, 'propuesta_tipo', 'descripcion', true, false);
	}

	//--------------------------------------------------------------------------
	//---- Opciones obtenidas desde G3 -----------------------------------------
	//--------------------------------------------------------------------------

	function get_responsables_academicas()
	{
		return g3::entidad('responsables_academicas')->get_opciones();
	}

	function get_propuestas_disponibles($ra, $excluir = array())
	{
		return g3::propuestas()->get_propuestas_disponibles_preinscripcion($ra, $excluir);
	}

	function get_ubicaciones_propuesta($propuesta)
	{
		return g3::propuestas()->get_ubicaciones_propuesta($propuesta);
	}

	function get_modalidades_propuesta($propuesta)
	{
		return g3::propuestas()->get_modalidades_propuesta($propuesta);
	}

	//--------------------------------------------------------------------------
	
	function get_tipos_vivienda()
	{
		return g3::entidad('tipos_vivienda')->get_opciones();
	}
	
	function get_tipos_allegado()
	{
		return g3::entidad('tipos_allegados')->get_opciones();
	}
	
	function get_condicion_discapacidad()
	{	
		return [
			comunes::no => kernel::traductor()->trans('sin_discapacidad_descr'),
			comunes::si => kernel::traductor()->trans('con_discapacidad_descr')
		];
	}
	
	function get_dificultades_discapacidad()
	{
		return g3::entidad('discapacidades_dificultad')->get_opciones();
	}
	function get_identidad_genero()
	{
		return g3::entidad('identidad_genero')->get_opciones(null, true);
	}
	function get_razones_abandono_estudio()
	{
		return g3::entidad('abandono_estudio')->get_opciones();
	}

	function get_pueblos_originarios()
	{
		return g3::entidad('pueblos_originarios')->get_opciones();
	}

	function get_orientacion_vocacional()
	{
		return g3::entidad('orientacion_vocacional')->get_opciones();
	}
	
	function get_obra_social()
	{
		return g3::entidad('cobertura_salud')->get_opciones();
	}

	//--------------------------------------------------------------------------

	function get_paises($con_no_seleccion = true, $condicion = null)
	{
		return g3::entidad('paises')->get_opciones($condicion, $con_no_seleccion);
	}

	function get_paises_nacionalidad( $con_no_seleccion = true)
	{
		$nacionalidad = kernel::persona()->datos()->get_nacionalidad();

		//2=Extranjeros 
		if ($nacionalidad == comunes::NACIONALIDAD_EXTRANJERO){
			return $this->get_paises($con_no_seleccion);
		}else{
			$condicion="pais = '54'";
			return $this->get_paises($con_no_seleccion, $condicion);
		}
	}

	function get_provincias($pais)
	{
		$condicion = 'mug_provincias.pais = ' . kernel::db()->quote($pais);
		return g3::entidad('provincias')->get_opciones($condicion);
	}

	function get_departamentos($provincia)
	{
		$condicion = 'mug_dptos_partidos.provincia = ' . kernel::db()->quote($provincia);
		return g3::entidad('dptos_partidos')->get_opciones($condicion);
	}

	function get_localidades($departamento)
	{
		$condicion = 'mug_localidades.dpto_partido = ' . kernel::db()->quote($departamento);
		return g3::entidad('localidades')->get_opciones($condicion);
	}

	function get_cod_postales($localidad)
	{
		$condicion = 'mug_cod_postales.localidad = ' . kernel::db()->quote($localidad);
		return g3::entidad('cod_postales')->get_opciones($condicion);
	}

	//--------------------------------------------------------------------------

	function get_nacionalidades()
	{
		return g3::entidad('nacionalidades')->get_opciones();
	}
	
	function get_tipos_documentos()
	{
		return g3::entidad('tipos_documentos')->get_opciones();
	}
	
	function get_tipos_documentos_pais()
	{

		//$tipo = kernel::persona()->datos()->get_tipo_doc();
		$pais_documento = kernel::persona()->datos()->get_pais_documento();
		
		$condicion="mdp_tipo_documento_pais.pais = '".$pais_documento."'";// and mdp_tipo_documento_pais.tipo_documento = '".$tipo."'"; 
		$datos = g3::entidad('tipos_documentos_pais')->get_opciones_pais($condicion);
		return $datos;
	}


	function get_entidades_emisoras_ci()
	{
		return g3::entidad('entes_emisores_ci')->get_opciones();
	}

	//--------------------------------------------------------------------------
	//---- Opciones propias (construidas en Preinscripción) --------------------
	//--------------------------------------------------------------------------

	function get_si_no_desconoce($con_no_seleccion = false)
	{
		$salida = array();
		if ($con_no_seleccion) {
			$salida[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
		}
		$salida = array_merge($salida, array(
			'S' => kernel::traductor()->trans('si'),
			'N' => kernel::traductor()->trans('no'),
			'D' => kernel::traductor()->trans('desconoce')));
		return $salida;
	}

	function get_si_no_no_desea_contestar($con_no_seleccion = true)
	{
		$salida = array();
		if ($con_no_seleccion) {
			$salida[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
		}
		$salida = array_merge($salida, array(
			'S' => kernel::traductor()->trans('si'),
			'N' => kernel::traductor()->trans('no'),
			'X' => kernel::traductor()->trans('no_desea_contestar')));
		return $salida;
	}
	function get_preguntas_secretas()
	{
		$salida =  array(
			'pregunta_mascota' => kernel::traductor()->trans('pregunta_mascota'),
			'pregunta_escuela' => kernel::traductor()->trans('pregunta_escuela'),
			'pregunta_vacaciones' => kernel::traductor()->trans('pregunta_vacaciones'));
		return $salida;
	}
	function get_anios($cantidad = '70')
	{
		if ($cantidad < 0) {
			return array();
		}
		$anio = date('Y');
		$salida = array();
		$salida[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
		for ($i = 0; $i < $cantidad; $i++) {
			$salida[$anio] = $anio;
			$anio--;
		}
		return $salida;
	}

	function get_generos()
	{
		return array(
			'null' => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('genero_masculino'),
			'2' => kernel::traductor()->trans('genero_femenino'),
			'3' => kernel::traductor()->trans('genero_no_binario'),
		);
	}
	
    function get_turnos()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'M' => kernel::traductor()->trans('turno_mañana'),
			'T' => kernel::traductor()->trans('turno_tarde'),
			'N' => kernel::traductor()->trans('turno_noche'),
		);
	}

	function get_tipos_visa()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'TEMPORAL' => kernel::traductor()->trans('temporal'),
			'PERMANENTE' => kernel::traductor()->trans('permanente'),
		);
	}

	function get_tipos_residencia()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'C' => kernel::traductor()->trans('si'),
			'R' => kernel::traductor()->trans('no'),
		);
	}

	function get_residencia()
	{
		$opciones = array();
		$tipo_doc = kernel::persona()->datos()->get_tipo_doc();
		// Las opciones de residencia dependen del tipo de documento principal
		$nacionalidad = kernel::persona()->datos()->get_nacionalidad();
		
		if($nacionalidad === comunes::NACIONALIDAD_EXTRANJERO){
			$opciones[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
			$opciones['4'] = kernel::traductor()->trans('residencia_4'); // Definitiva
			$opciones['1'] = kernel::traductor()->trans('residencia_1'); // Sin Residencia
			$opciones['2'] = kernel::traductor()->trans('residencia_2'); // Precaria
			$opciones['3'] = kernel::traductor()->trans('residencia_3'); // Temporal

		}
		return $opciones;
	}

	function get_existe_trab_alu()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('existe_trab_alum_1'),
			'2' => kernel::traductor()->trans('existe_trab_alum_2'),
			'3' => kernel::traductor()->trans('existe_trab_alum_3'),
		);
	}

	function get_alu_trab_hace()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('alu_trab_hace_1'),
			'2' => kernel::traductor()->trans('alu_trab_hace_2'),
			'3' => kernel::traductor()->trans('alu_trab_hace_3'),
			'4' => kernel::traductor()->trans('alu_trab_hace_4'),
		);
	}

	function get_padres_trab_hace()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('padre_trab_hace_1'),
			'2' => kernel::traductor()->trans('padre_trab_hace_2'),
			'3' => kernel::traductor()->trans('padre_trab_hace_3'),
			'4' => kernel::traductor()->trans('padre_trab_hace_4'),
		);
	}

	function get_trab_ocup($agregar_desconoce = false)
	{
		$salida = array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('alu_trab_ocup_1'),
			'2' => kernel::traductor()->trans('alu_trab_ocup_2'),
		);
		if ($agregar_desconoce) {
			$salida['3'] = kernel::traductor()->trans('desconoce');
		}
		return $salida;
	}

	function get_hora_sem_trab_alum()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('hora_sem_trab_alum_1'),
			'2' => kernel::traductor()->trans('hora_sem_trab_alum_2'),
			'3' => kernel::traductor()->trans('hora_sem_trab_alum_3'),
			'4' => kernel::traductor()->trans('hora_sem_trab_alum_4'),
		);
	}

	function get_rel_trab_propuesta()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('rel_trab_propuesta_1'),
			'2' => kernel::traductor()->trans('rel_trab_propuesta_2'),
			'3' => kernel::traductor()->trans('rel_trab_propuesta_3'),
		);
	}

	function get_niveles_estudio()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('nivel_estudio_1'),
			'2' => kernel::traductor()->trans('nivel_estudio_2'),
			'3' => kernel::traductor()->trans('nivel_estudio_3'),
			'4' => kernel::traductor()->trans('nivel_estudio_4'),
			'5' => kernel::traductor()->trans('nivel_estudio_5'),
			'8' => kernel::traductor()->trans('nivel_estudio_8'),
			'9' => kernel::traductor()->trans('nivel_estudio_9'),
			'10' => kernel::traductor()->trans('nivel_estudio_10'),
			'11' => kernel::traductor()->trans('nivel_estudio_11'),
			'12' => kernel::traductor()->trans('nivel_estudio_12'),
			'13' => kernel::traductor()->trans('nivel_estudio_13'),
		);
	}
	
	function get_titulos_sec_por_nombre($titulo_nombre)
	{
		return g3::titulos_secundario()->get_titulos($titulo_nombre);
	}

	function get_sit_laboral_padres()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('sit_laboral_1'),
			'2' => kernel::traductor()->trans('sit_laboral_2'),
			'3' => kernel::traductor()->trans('sit_laboral_3'),
			'4' => kernel::traductor()->trans('sit_laboral_4'),
		);
	}

	function get_otra_ocupacion()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('otra_ocup_1'),
			'2' => kernel::traductor()->trans('otra_ocup_2'),
			'3' => kernel::traductor()->trans('otra_ocup_3'),
			'4' => kernel::traductor()->trans('otra_ocup_4'),
			'5' => kernel::traductor()->trans('otra_ocup_5'),
			'6' => kernel::traductor()->trans('otra_ocup_6'),
		);
	}

	function get_tipos_estudios_superiores()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('alu_otestsup_tipo_1'),
			'2' => kernel::traductor()->trans('alu_otestsup_tipo_2'),
		);
	}

	function get_estados_estudios_sup()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('alu_otestsup_esta_1'),
			'2' => kernel::traductor()->trans('alu_otestsup_esta_2'),
			'3' => kernel::traductor()->trans('alu_otestsup_esta_3'),
		);
	}

	function get_areas_estudios_sup()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('alu_otestsup_area_1'),
			'2' => kernel::traductor()->trans('alu_otestsup_area_2'),
			'3' => kernel::traductor()->trans('alu_otestsup_area_3'),
			'4' => kernel::traductor()->trans('alu_otestsup_area_4'),
			'5' => kernel::traductor()->trans('alu_otestsup_area_5'),
			'6' => kernel::traductor()->trans('alu_otestsup_area_6'),
			'7' => kernel::traductor()->trans('alu_otestsup_area_7'),
			'8' => kernel::traductor()->trans('alu_otestsup_area_8'),
		);
	}

	function get_niveles_otestsup()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('alu_otestsup_niv_1'),
			'2' => kernel::traductor()->trans('alu_otestsup_niv_2'),
		);
	}

	function get_niveles_idiomas()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('idiomas_nivel_1'),
			'2' => kernel::traductor()->trans('idiomas_nivel_2'),
			'3' => kernel::traductor()->trans('idiomas_nivel_3'),
			'4' => kernel::traductor()->trans('idiomas_nivel_4'),
		);
	}

	function get_vive_con()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('vive_con_1'),
			'2' => kernel::traductor()->trans('vive_con_2'),
			'3' => kernel::traductor()->trans('vive_con_3'),
			'4' => kernel::traductor()->trans('vive_con_4'),
			'5' => kernel::traductor()->trans('vive_con_5'),
		);
	}

	function get_estados_civiles()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'1' => kernel::traductor()->trans('estado_civil_1'),
			'2' => kernel::traductor()->trans('estado_civil_2'),
			'3' => kernel::traductor()->trans('estado_civil_3'),
			'4' => kernel::traductor()->trans('estado_civil_4'),
			'6' => kernel::traductor()->trans('estado_civil_6'),
		);
	}

	function get_cant_hijos_o_fam_a_cargo()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'0' => kernel::traductor()->trans('no_tiene'),
			'1' => kernel::traductor()->trans('uno'),
			'2' => kernel::traductor()->trans('dos'),
			'3' => kernel::traductor()->trans('mas_de_dos')
		);
	}

	function get_categorias_coneau()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'A' => kernel::traductor()->trans('categ_coneau_a'),
			'B' => kernel::traductor()->trans('categ_coneau_b'),
			'C' => kernel::traductor()->trans('categ_coneau_c'),
			'T' => kernel::traductor()->trans('categ_coneau_t'),
			'N' => kernel::traductor()->trans('categ_coneau_n'),
		);
	}

	function get_niveles_estudio_fa()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'4' => kernel::traductor()->trans('antec_fa_nivel_estudio_4'),
			'5' => kernel::traductor()->trans('antec_fa_nivel_estudio_5'),
			'6' => kernel::traductor()->trans('antec_fa_nivel_estudio_6'),
		);
	}

	function get_estados_antecedente_fa()
	{
		return array(
			self::NO_SELECCION => kernel::traductor()->trans('descr_no_seleccion'),
			'F' => kernel::traductor()->trans('antec_fa_estado_F'),
			'A' => kernel::traductor()->trans('antec_fa_estado_A'),
			'C' => kernel::traductor()->trans('antec_fa_estado_C'),
		);
	}

}