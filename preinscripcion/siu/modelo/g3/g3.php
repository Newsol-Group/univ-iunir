<?php

namespace siu\modelo\g3;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_entidades_g3;
use siu\modelo\g3\interface_responsables_academicas;
use siu\modelo\g3\interface_propuestas;
use siu\modelo\g3\interface_personas;
use siu\modelo\g3\interface_paises;
use siu\modelo\g3\interface_localidades;
use siu\modelo\g3\interface_periodos;
use siu\modelo\g3\interface_colegios;
use siu\modelo\g3\interface_titulos_secundario;
use siu\modelo\g3\interface_parametros;
use siu\modelo\g3\interface_turnos;
use siu\modelo\g3\interface_requisitos;
use siu\modelo\g3\interface_extensiones;

class g3
{
	/**
	 * Instancia mediante el patrón Singleton una entidad de acceso a datos de G3
	 * 
	 * @param string $entidad_nombre
	 * @return interface_entidades_g3
	 */
	static function entidad($entidad_nombre)
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\$entidad_nombre");
	}

	/**
	 * Instancia el objeto de consultas de Responsables Académicas mediante el 
	 * patrón Singleton.
	 *
	 * Es equivalente a g3::entidad('responsables_academicas'), sólo que de esta 
	 * forma se indica el tipo que retorna, habilitando las funcionalidades de 
	 * autocompletado.
	 * 
	 * @return interface_responsables_academicas
	 */
	static function responsables_academicas()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\responsables_academicas");
	}

	/**
	 * Instancia el objeto de consultas de Propuestas mediante el patrón Singleton.
	 *
	 * Es equivalente a g3::entidad('propuestas'), sólo que de esta forma se indica
	 * el tipo que retorna, habilitando las funcionalidades de autocompletado.
	 * 
	 * @return interface_propuestas
	 */
	static function propuestas()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\propuestas");
	}
	
	/**
	 * Instancia el objeto de consultas de Personas mediante el patrón Singleton.
	 * 
	 * @return interface_personas
	 */
	static function personas()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\personas");
	}
	
	/**
	 * Instancia el objeto de consultas de Países mediante el patrón Singleton.
	 * 
	 * @return interface_paises
	 */
	static function paises()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\paises");
	}
	
	/**
	 * Instancia el objeto de consultas de Localidades mediante el patrón Singleton.
	 * 
	 * @return interface_localidades
	 */
	static function localidades()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\localidades");
	}
	
	/**
	 * Instancia el objeto de consultas de Períodos mediante el patrón Singleton.
	 * 
	 * @return interface_periodos
	 */
	static function periodos()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\periodos");
	}

	/**
	 * Instancia el objeto de consultas de Colegios Secundarios mediante el patrón Singleton.
	 * 
	 * @return interface_colegios
	 */
	static function colegios()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\colegios");
	}

	/**
	 * Instancia el objeto de consultas de Títulos Secundario mediante el patrón Singleton.
	 * 
	 * @return interface_titulos_secundario
	 */
	static function titulos_secundario()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\titulos_secundario");
	}

	/**
	 * Instancia el objeto de consultas de Parámetros mediante el patrón Singleton.
	 * 
	 * @return interface_parametros
	 */
	static function parametros()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\parametros");
	}

	/**
	 * Instancia el objeto de consultas de Parámetros mediante el patrón Singleton.
	 * 
	 * @return interface_turnos
	 */
	static function turnos()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\turnos");
	}
	
	/**
	 * Instancia el objeto de consultas de Requisitos mediante el patrón Singleton.
	 * 
	 * @return interface_requisitos
	 */
	static function requisitos()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\requisitos");
	}
	
	/**
	 * Instancia el objeto de consultas de Extensiones para los archivos RDI
	 * 
	 * @return interface_extensiones
	 */
	static function extensiones()
	{
		$modo_consultas = kernel::proyecto()->get_modo_consultas_g3();
		return kernel::localizador()->instanciar_singleton("modelo\\g3\\$modo_consultas\\extensiones");
	}
	
}
?>