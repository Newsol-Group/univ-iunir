<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\lib\rest_validador;
use SIU\Guarani\php\rest\v2\convocatorias\recurso_convocatorias;
use SIUToba\rest\rest;

class rest_convocatorias_nucleo
{
    /**
     * @var \co_encuestas
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo = \guarani::co('co_encuestas');
		$this->modelo_act = \guarani::act('act_encuestas');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_convocatorias::_get_modelos();
        return $modelos[$nombre];
    }
	
    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base(){
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function get_list()
    {
		$filtro = $this->get_filtro_base()
				->agregar_campo('titulo', 'gde_habilitaciones.titulo');
		
		$where = $filtro->get_sql_where();
		$rs = $this->modelo->get_listado_convocatorias($where);

		$datos = rest_hidratador::hidratar($this->get_modelo('Convocatoria'), $rs);

		return $datos;
    }
	
	function get_aspirantes($id_convocatoria) 
	{
		if ($id_convocatoria == '') {
			throw new rest_error(400, "Parámetro 'id_convocatoria' requerido");
		}
		if (!preg_match('/^[0-9]+$/', $id_convocatoria)) {
			throw new rest_error(400, "Parámetro 'id_convocatoria' debe ser un número entero");
		}
		if (!\guarani::co('co_encuestas')->existe_convocatoria($id_convocatoria)) {
			throw new rest_error(400, "La convocatoria $id_convocatoria no existe");
		}

                $where = "";
                
                $cuil = trim(rest::request()->get('cuil'));
                // Si se filtra por cuil
                if (!empty($cuil)) {
                    // Si el cuil es inválido
                    if (!\persona::validarCUIT($cuil)) {
                        $mensaje = "Error en la validación del recurso";
                        $detalle = ["cuil" => "El parámetro 'cuil' debe ser un cuil válido. Se recibió '{$cuil}'."];
                        throw new rest_error(400, $mensaje, $detalle);
                    } else {// Si el cuil es válido
                        $where = "personas_documento_cuit.nro_documento = '{$cuil}'";
                    }
                }

                $opciones_si_no = [\comunes::si, \comunes::no];
                $con_reaperturas = trim(rest::request()->get('con_reaperturas'));
                // Valido el parámetro 'con_reaperturas'
                if (!empty($con_reaperturas) && !in_array($con_reaperturas, $opciones_si_no)) {
                    $mensaje = "Error en la validación del recurso";
                    $detalle = ["con_reaperturas" => "El parámetro 'con_reaperturas' no pertenece a la lista de opciones válidas. Se recibió '{$con_reaperturas}'. Parámetros: ".implode(', ', $opciones_si_no)];
                    throw new rest_error(400, $mensaje, $detalle);
                }

		$rs = $this->modelo->get_datos_aspirantes_convocatoria($id_convocatoria, $where, $con_reaperturas);
		$datos = rest_hidratador::hidratar($this->get_modelo('AspiranteConvocatoria'), $rs);
		return $datos;
	}
	
	function get_definicion($id_convocatoria) 
	{
		if ($id_convocatoria == '') {
			throw new rest_error(400, "Parámetro 'id_convocatoria' requerido");
		}
		if (!preg_match('/^[0-9]+$/', $id_convocatoria)) {
			throw new rest_error(400, "Parámetro 'id_convocatoria' debe ser un número entero");
		}
		$rs = $this->modelo->get_definicion_convocatoria($id_convocatoria);
		if (\guarani_arreglos::count($rs) == 1) {
			$datos = rest_hidratador::hidratar($this->get_modelo('DefinicionConvocatoria'), $rs);
			return $datos;
		} else {
			throw new rest_error(404, "La convocatoria $id_convocatoria no existe");
		}
	}

	function put($id_convocatoria, $data)
	{
		if ($id_convocatoria == '') {
			throw new rest_error(400, "Parámetro 'id_convocatoria' requerido");
		}
		if (!preg_match('/^[0-9]+$/', $id_convocatoria)) {
			throw new rest_error(400, "Parámetro 'id_convocatoria' debe ser un número entero");
		}
		$modelos = recurso_convocatorias::_get_modelos();
		$modelo_convocatoria_definicion = $modelos['DefinicionConvocatoriaPut'];
		rest_validador::validar($data, $modelo_convocatoria_definicion);

		// Si no existe la convocatoria
		if (!\toba::consulta_php('co_encuestas')->existe_convocatoria($id_convocatoria)) {
			$mensaje = "La convocatoria con el ID '{$id_convocatoria}' no existe.";
			$detalle = ["id_convocatoria" => $mensaje];
			throw new rest_error(404, $mensaje, $detalle);
		}

		try {
			\toba::db()->abrir_transaccion();

			// Modificar la fecha hasta, fecha de evaluación de historia académica hasta y la descripción del relevamiento.
			// La fecha desde se modifica sólo para convocatorias a futuro.
			$this->modelo_act->modificar_convocatoria($id_convocatoria, $data);

			// Recupera datos de la habilitación con las fechas ya modificadas
			$datos_habilitacion = \toba::consulta_php('co_encuestas')->get_datos_habilitacion_convocatoria($id_convocatoria);

			// Recalcular los encuestados.
			$this->modelo_act->generar_items($datos_habilitacion['habilitacion']);

			// Si tiene asociada una habilitación de Kolla (ya se hizo click en el botón Habilitar en » Encuestas » Definir Relevamientos)
			if (!is_null($datos_habilitacion['kolla_id_habilitacion'])) {

				// PUT a Kolla con la nueva fecha hasta de la habilitación.
				// si no se habilita el relevamiento $datos_habilitacion['kolla_id_habilitacion'] es null
				$datos_habilitacion['habilitacion'] = $datos_habilitacion['kolla_id_habilitacion'];
				unset($datos_habilitacion['kolla_id_habilitacion']);
				$ug = $datos_habilitacion['kolla_unidad_gestion'];
				unset($datos_habilitacion['kolla_unidad_gestion']);

				// Modifica los datos de una habilitación
				try {
					\encuesta::actualizar_habilitacion($datos_habilitacion, $ug);
				// Si falla la comunicación con Kolla
				} catch (\Exception $e) {
					$mensaje = 'Falló la comunicación con Kolla';
					throw new rest_error(500, $mensaje);
				}
				
			}

			\toba::db()->cerrar_transaccion(); 
		} catch (\Exception $e) {
			\toba::db()->abortar_transaccion();
			\toba::logger_ws()->error($e->getMessage());
			\toba::logger_ws()->error($e->getTraceAsString());
			throw $e;
		}
	}
}