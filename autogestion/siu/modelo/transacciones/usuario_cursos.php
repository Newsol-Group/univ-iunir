<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\guarani;
use SIU\Chulupi\ErrorKernel;
use siu\errores\error_guarani;
use siu\modelo\entidades\parametro;

class usuario_cursos extends transaccion
{
	const NO_SELECCION = 'null';
	
	/**
	 * Consulta el parámetro que indica si se carga documentos digitales en el 
	 * alta de usuario.
	 * 
	 * @param	int		$propuesta
	 * @return	boolean
	 */
	function carga_documentacion($propuesta)
	{
		$ras = \toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);
		return parametro::get_valor('usuario_adjunta_doc_digital', $ras) == \comunes::si;
	}
	
	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Procesa el alta de una persona a partir de los datos recolectados en el 
	 * formulario de creación de usuario de cursos.
	 * 
	 * @param array $datos
	 * @throws \siu\modelo\transacciones\ErrorKernel
	 */
	function evt__nueva_persona($datos, $requisitos=array())
	{
		kernel::db()->abrir_transaccion();
		try {
            $validado_con_renaper = kernel::proyecto()->valida_con_renaper();

			//Se revisa la asignación de este indice porque es de tipo entero, no puede almacenar 'null'
			$datos['identidad_genero'] = $datos['identidad_genero'] == 'null' ? NULL :  $datos['identidad_genero'];
			// Se insertan los datos de la cabecera (mdp_personas)			
			$datos_persona = array(
				'apellido' => $datos['apellido'],
				'nombres' => $datos['nombres'],
				'apellido_elegido' => ($datos['apellido_elegido'] == 'null') ? null : $datos['apellido_elegido'],
				'nombres_elegido' => $datos['nombres_elegido'],
				'sexo' => $datos['sexo'],
				'identidad_genero' => $datos['identidad_genero'],
				'identidad_genero_otro' => $datos['identidad_genero_otro'],
				'fecha_nacimiento' => $datos['fecha_nacimiento'],
				'localidad_nacimiento' => $datos['localidad'],
				'nacionalidad' => $datos['nacionalidad'],
				'pais_origen' => $datos['pais'],
				'usuario' => $datos['usuario'],
				'clave' => guarani::encriptar($datos['password']),
			);
			$persona = catalogo::consultar('persona', 'insertar_cabecera_persona', $datos_persona);
			kernel::sesion()->set('id_persona',$persona);
			// Se inserta documento principal
			$datos_documento = array(
				'persona' => $persona,
				'pais_documento' => $datos['pais_documento'],
				'tipo_documento' => $datos['tipo_documento'],
				'nro_documento' => $datos['nro_documento'],
			);
            if($validado_con_renaper == \comunes_nucleo::si && !empty($datos['numero_cuil'])){
                // Se inserta documento principal
                $datos_documento = array(
                    'persona' => $persona,
                    'pais_documento' => $datos['pais_documento'],
                    'tipo_documento' => $datos['tipo_documento'],
                    'nro_documento' => $datos['nro_documento'],
                    'validado_con_renaper' => \comunes::si
                );
                $datos_documento_cuil = array(
                    'persona' => $persona,
                    'pais_documento' => $datos['pais_documento'],
                    'tipo_documento' => \persona::doc_CUIL,
                    'nro_documento' => $datos['numero_cuil'],
                    'validado_con_renaper' => \comunes::si
                );
                //El documento principal siempre sera el DNI porque es la unica manera que valida renaper
                catalogo::consultar('persona', 'insertar_documento_principal', $datos_documento);
                catalogo::consultar('persona', 'insertar_otro_documento', $datos_documento_cuil);
            }else{
                // Se inserta documento principal sin renaper
                $datos_documento = array(
                    'persona' => $persona,
                    'pais_documento' => $datos['pais_documento'],
                    'tipo_documento' => $datos['tipo_documento'],
                    'nro_documento' => $datos['nro_documento'],
                    'validado_con_renaper' => \comunes::no
                );
                
                catalogo::consultar('persona', 'insertar_documento_principal', $datos_documento);
            }
            
			
			
			// Se inserta Mail Principal
			$datos_mp = array(
				'persona' => $persona,
				'email' => $datos['email'],
			);
			catalogo::consultar('persona', 'insertar_mail_principal', $datos_mp);
			
            //Se agrega la direccion de la persona con los datos del renaper
            
			if (!empty($requisitos)) {
				foreach ($requisitos as $requisito) {
					// Se ubica el archivo temporal en el repositorio
					$path_temp = $requisito['path_fs'];
					$nombre_original = explode('.', $requisito['nombre_original']);
					$cant_partes_nombre_original = \guarani_arreglos::count($nombre_original);
					$nuevo_nombre = $requisito['requisito'] . '__' . date("Ymd_His___" . rand(1000, 9999));
					if ($cant_partes_nombre_original > 1) {
						$nuevo_nombre .= '.' . $nombre_original[$cant_partes_nombre_original - 1];
					}
					$subcarpeta = $persona;
					$path_repositorio = \guarani_repositorio::mover_temp_al_repositorio_doc_dig($path_temp, $nuevo_nombre, $subcarpeta);
					
					$datos_requisito_digital = array(
						'persona' => $persona,
						'requisito' => $requisito['requisito'],
						'id_documento' => $path_repositorio
					);
					catalogo::consultar('persona', 'insertar_documento_digital', $datos_requisito_digital);
				}
			}
			
			kernel::db()->cerrar_transaccion();
		} catch (error_guarani $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		} catch (ErrorKernel $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}
	
	/**
	 * Formatea una cadena para dejar uniforme la carga de nombre y apellido.
	 * Por defecto capitaliza la primera letra de cada palabra.
	 * Se deja modularizada por si se desea personalizar.
	 * 
	 * @param string $cadena
	 * @return string
	 */
	function formatear_nombre($cadena)
	{
		return capitalizar($cadena);
	}
	
	//--------------------------------------------------------------------------
	//----	INFO ---------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function info__existe_persona($datos)
	{
		return \toba::consulta_php('co_personas')->existe_persona($datos['pais_documento'], $datos['tipo_documento'], $datos['nro_documento']);
	}
	
	function info__descr_localidad($localidad)
	{
		$parametros = array('localidad' => $localidad);
		return catalogo::consultar('datos_censales', 'get_descr_localidad', $parametros);
	}
	
	function info__requisitos_cursos($propuesta)
	{
		return \toba::consulta_php('co_requisitos_ingreso')->get_requisitos_ingreso_cursos($propuesta);
	}
	
	//--------------------------------------------------------------------------
	//---- Opciones ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	protected function armar_combo_opciones($datos, $campo_clave, $campo_valor, $con_no_seleccion=true, $omitir_no_seleccion_opcion_unica=true)
	{
		$combo = array();
		if (!empty($datos)) {
			if (($con_no_seleccion) && ((\guarani_arreglos::count($datos) > 1) || ((\guarani_arreglos::count($datos) <= 1) && !$omitir_no_seleccion_opcion_unica) )) {
				$combo[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
			}
			foreach ($datos as $dato) {
				$combo[$dato[$campo_clave]] = $dato[$campo_valor];
			}
		}
		return $combo;
	}
	
	//--------------------------------------------------------------------------
	
	function opciones__generos()
	{
		$datos = \toba::consulta_php('co_personas')->get_sexos();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
	
	function opciones__nacionalidades()
	{
		$datos = \toba::consulta_php('co_personas')->get_nacionalidades();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
	
	function opciones__paises()
	{
		$datos = \toba::consulta_php('co_mug')->get_paises();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
	
	function opciones__tipos_documentos()
	{
		$datos = \toba::consulta_php('co_personas')->get_tipos_documentos();
		return $this->armar_combo_opciones($datos, 'valor', 'descr');
	}
	
	function opciones__localidades_autocomplete($localidad_nombre='', $pais=null)
	{
		$parametros = array('localidad_nombre' => $localidad_nombre, 'pais' => $pais);
		return catalogo::consultar('datos_censales', 'get_localidades_autocomplete', $parametros);
	}
		
	function opciones__provincias($pais)
	{
		$datos = \toba::consulta_php('co_mug')->get_provincias_pais($pais);
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}
	
	function opciones__departamentos($provincia)
	{
		$datos = \toba::consulta_php('co_mug')->get_departamentos_provincia($provincia);
		return $this->armar_combo_opciones($datos, 'dpto_partido', 'nombre', true, false);
	}
	
	function opciones__localidades($departamento)
	{
		$datos = \toba::consulta_php('co_mug')->get_localidades_departamento($departamento);
		return $this->armar_combo_opciones($datos, 'localidad', 'nombre', true, false);
	}
	
	function opciones__propuestas_cursos()
	{
		$datos = \toba::consulta_php('co_propuestas')->get_propuestas_administran_cursos();
		return $this->armar_combo_opciones($datos, 'valor', 'descr_con_ra', true, false);
	}
    function opciones__tipos_documentos_x_pais($pais = null)
	{
		$datos = \toba::consulta_php('co_personas')->get_datos_tipos_documentos_x_pais($pais, null, \comunes::si);
	
		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}
    function opciones__validacion_documento()
    {
        return \persona::get_datos_tipo_documento();
    }
	
}