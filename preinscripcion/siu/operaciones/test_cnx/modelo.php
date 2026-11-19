<?php

namespace siu\operaciones\test_cnx;

use siu\modelo\g3\g3;

class modelo
{
	protected $entidades;
	
	public function __construct()
	{
		$this->inicializar_test();
	}

	function test()
	{
		$resultados = array();
		foreach ($this->entidades as $entidad) {
			$id = isset($entidad['clave_test']) ? $entidad['clave_test'] : 1;
			// Se instancia la entidad correspondiente, considerando los casos particulares
			if ($id == 'personas') {
				$objeto_cnx = g3::personas();
			} elseif ($id == 'responsables_academicas') {
				$objeto_cnx = g3::responsables_academicas();
			} elseif ($id == 'propuestas') {
				$objeto_cnx = g3::propuestas();
			} elseif ($id == 'periodos') {
				$objeto_cnx = g3::periodos();
			} elseif ($id == 'parametros') {
				$objeto_cnx = g3::parametros();
			} elseif ($id == 'titulos_secundario') {
				$objeto_cnx = g3::titulos_secundario();
			} elseif ($id == 'turnos') {
				$objeto_cnx = g3::turnos();
			} elseif ($id == 'requisitos') {
				$objeto_cnx = g3::requisitos();
			} else {
				$objeto_cnx = g3::entidad($entidad['id']);
			}
			// Ejecución del test
			$resultado = $objeto_cnx->test($id);
			$resultados[] = array(
				'entidad' => $entidad['descr'],	
				'resultado' => $resultado['resultado'],
				'resultado_msj' => $resultado['msj'],
			);
			\klog($resultado);
		}
		return $resultados;
	}
	
	//--------------------------------------------------------------------------
	//---- Funciones auxliares -------------------------------------------------
	//--------------------------------------------------------------------------
	
	protected function inicializar_test()
	{
		$this->entidades = array(
			// mug
			array('id' => 'continentes', 'descr' => 'Continentes', 'clave_test' => 'AM'),
			array('id' => 'paises', 'descr' => 'Paises', 'clave_test' => '54'),
			array('id' => 'provincias', 'descr' => 'Provincias', 'clave_test' => '5406'),
			array('id' => 'localidades', 'descr' => 'Localidades', 'clave_test' => '17159'),
			array('id' => 'dptos_partidos', 'descr' => 'Departamentos / Partidos', 'clave_test' => '5406056'),
			array('id' => 'cod_postales', 'descr' => 'Codigos Postales', 'clave_test' => array('localidad' => 1, 'codigo_postal' => 1001)),
			// mdp
			array('id' => 'nacionalidades', 'descr' => 'Nacionalidades', 'clave_test' => '1'),
			array('id' => 'tipos_allegados', 'descr' => 'Tipos de allegados', 'clave_test' => 'P'),
			array('id' => 'tipos_documentos', 'descr' => 'Tipos de documentos', 'clave_test' => '0'),
			array('id' => 'discapacidades_dificultad', 'descr' => 'Discapacidades  - Dificultades', 'clave_test' => '1'),
			array('id' => 'titulos_secundario', 'descr' => 'Títulos Secundarios', 'clave_test' => '1'),
			array('id' => 'pueblos_originarios', 'descr' => 'Pueblos Originarios', 'clave_test' => '1'),
			array('id' => 'tipos_vivienda', 'descr' => 'Tipos de vivienda', 'clave_test' => '5'),
			array('id' => 'abandono_estudio', 'descr' => 'Razones de abandono de estudios', 'clave_test' => '1'),
			array('id' => 'orientacion_vocacional', 'descr' => 'Orientación vocacional', 'clave_test' => '1'),
			array('id' => 'cobertura_salud', 'descr' => 'Cobertura de salud', 'clave_test' => '1'),
			// sga
			array('id' => 'responsables_academicas', 'descr' => 'Responsables Académicas', 'clave_test' => '1'),
			array('id' => 'ubicaciones', 'descr' => 'Ubicaciones', 'clave_test' => '1'),
			array('id' => 'propuestas', 'descr' => 'Propuestas', 'clave_test' => '1000'),
			array('id' => 'personas', 'descr' => 'Personas', 'clave_test' => array('pais_documento' => 54, 'tipo_documento' => 0, 'nro_documento' => 1)),
			array('id' => 'colegios', 'descr' => 'Colegios', 'clave_test' => '2'),
			array('id' => 'dias_no_laborables', 'descr' => 'Días no hábiles', 'clave_test' => '2017-05-25'),
			array('id' => 'entes_emisores_ci', 'descr' => 'Entes emisores CI', 'clave_test' => '1'),
			array('id' => 'periodos', 'descr' => 'Períodos de Preinscripción'),
			// Parámetros G3
			array('id' => 'parametros', 'descr' => 'Parámetros de G3', 'clave_test' => 'pre_meses_vigencia_preinsc'),
			// Turnos
			array('id' => 'turnos', 'descr' => 'Turnos para presentación', 'clave_test' => array('ra' => 1, 'fecha' => '10/10/2017')),
			// Requisitos
			array('id' => 'requisitos', 'descr' => 'Requisitos de ingreso', 'clave_test' => array('1000', '1')),
			// Extensiones
			array('id' => 'extensiones', 'descr' => 'Extensiones permitidas para archivos RDI'),
		);
	}
}
?>