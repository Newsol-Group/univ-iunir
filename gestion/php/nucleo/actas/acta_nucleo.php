<?php
/**
 * Constantes y operaciones comunes para Actas
 *
 * @package Cursadas
 * @subpackage Actas
 */

use SIU\DocumentosCli\Entities\Autorizacion;

class acta_nucleo
{	
    //Constante para usar cuando se usa firma digital.
    const libro_no_aplica = '-1';
    
	// Estados de Actas.
	const estado_cerrada = 'C';
	const estado_abierta = 'A';
	const estado_anulada = 'B';

	// Estados de Detalles de Actas.
	const detalle_estado_activo = 'A';
	const detalle_estado_baja = 'B';
	const detalle_estado_invalidado = 'I';

	// Aplicación para Tipos de Evaluaciones.
	const evaluacion_tipo_aplica_comision = 'C';
	const evaluacion_tipo_aplica_mesa = 'M';

	// Tipos de orígenes de actas (tabla sga_actas_origen).
	const origen_aprobacion_resolucion = 'A';
	const origen_aprobacion_resolucion_descr = 'Actas de Aprobación por Resolución';
	const origen_equivalencias = 'B';
	const origen_equivalencias_descr = 'Actas de Equivalencias Totales';
	const origen_equivalencias_regulares = 'C';
	const origen_equivalencias_regulares_descr = 'Actas de Equivalencias de Regularidad';
	const origen_equivalencias_parciales = 'D';
	const origen_equivalencias_parciales_descr = 'Actas de Equivalencias Parciales';
	const origen_examen = 'E';
	const origen_examen_descr = 'Actas de Examenes';
	const origen_promociones = 'P';
	const origen_promociones_descr = 'Actas de Promociones';
	const origen_regulares = 'R';
	const origen_regulares_descr = 'Actas de Regulares/Cursadas';
	
	// Tipos de actas.
	const tipo_normal = 'N';
	const tipo_normal_descr = 'Normal';
	const tipo_rectificativa = 'R';
	const tipo_rectificativa_descr = 'Rectificativa';
	
	// Obligatoriedad del ingreso de notas en actas.
	const ingreso_notas_no_obligatorio = 'N';
	const ingreso_notas_obligatorio = 'O';
	const ingreso_notas_sin_notas = 'S';
	const ingreso_notas_solo_promocion = 'P';

	// Parámetros Cursadas.
	const cur_agregar_alumnos_no_inscriptos = 'cur_agregar_alumnos_no_inscriptos';
	const cur_multiples_actas_comision = 'cur_multiples_actas_comision';
	const cur_carga_manual_nro_acta_regulares = 'cur_carga_manual_nro_acta_regulares';
	const cur_carga_manual_libro_regulares = 'cur_carga_manual_libro_regulares';
	const cur_lineas_folio_acta_regulares = 'cur_lineas_folio_acta_regulares';
	const cur_lineas_folio_acta_promocion = 'cur_lineas_folio_acta_promocion';
	const cur_forma_asignacion_folio_fisico_regul = 'cur_forma_asignacion_folio_fisico_regul';
	const cur_forma_asignacion_folio_fisico_promo = 'cur_forma_asignacion_folio_fisico_promo';
	const cur_agrega_folios_fisicos_extra_regul = 'cur_agrega_folios_fisicos_extra_regul';
	const cur_agrega_folios_fisicos_extra_promo = 'cur_agrega_folios_fisicos_extra_promo';
	const cur_carga_manual_nro_acta_promocion = 'cur_carga_manual_nro_acta_promocion';	
	const cur_carga_manual_libro_promocion = 'cur_carga_manual_libro_promocion';
	const cur_orden_detalle_actas = 'cur_orden_detalle_actas';
	const cur_asentar_notas_actas_regulares = 'cur_asentar_notas_actas_regulares';
	const cur_asentar_notas_actas_promocion = 'cur_asentar_notas_actas_promocion';
	const cur_imprimir_ultima_version_acta_regulares = 'cur_imprimir_ultima_version_acta_regulares';
	const cur_imprimir_ultima_version_acta_promocion = 'cur_imprimir_ultima_version_acta_promocion';
	const cur_imprimir_ultima_version_acta_regulares_web = 'cur_imprimir_ultima_version_acta_regulares_web';
	const cur_imprimir_ultima_version_acta_promo_web = 'cur_imprimir_ultima_version_acta_promo_web';
	const cur_inserta_alumnos_no_promocionales_en_acta = 'cur_inserta_alumnos_no_promocionales_en_acta';
	const cur_actas_regulares_cerradas_cnt_ejemplares = 'cur_actas_regulares_cerradas_cnt_ejemplares';
	const cur_actas_regulares_abiertas_cnt_ejemplares = 'cur_actas_regulares_abiertas_cnt_ejemplares';
	const cur_actas_promocion_cerradas_cnt_ejemplares = 'cur_actas_promocion_cerradas_cnt_ejemplares';
	const cur_actas_promocion_abiertas_cnt_ejemplares = 'cur_actas_promocion_abiertas_cnt_ejemplares';
	const cur_imprimir_acta_anulada = 'cur_imprimir_acta_anulada';
	const cur_cant_meses_vigencia_regularidad = 'cur_cant_meses_vigencia_regularidad';
	const cur_aplicar_controles_cierre_reg = 'cur_aplicar_controles_cierre_reg';
	const cur_aplicar_controles_cierre_promo = 'cur_aplicar_controles_cierre_promo';
	const cur_notificar_cierre_acta_regulares = 'cur_notificar_cierre_acta_regulares';
	const cur_notificar_cierre_acta_promocion = 'cur_notificar_cierre_acta_promocion';
	const exa_notificar_cierre_acta_examen = 'exa_notificar_cierre_acta_examen';
	
        const exa_utiliza_acta_digital = 'exa_utiliza_acta_digital';
        const cur_utiliza_acta_digital_regul = 'cur_utiliza_acta_digital_regul';
        const cur_utiliza_acta_digital_promo = 'cur_utiliza_acta_digital_promo';

	// Código de acta ficticia.
	const id_acta_ficticia = -1;
	const nro_acta_ficticio = -1;
	
	//Codigo de operacion
	const operacion_acta_regular = '800SIUCUR017';
	const operacion_acta_promocion= '800SIUCUR018';
	
	// Plantillas JASPER impresion actas
	const plantilla_regulares = 'ActaRegulares.jasper';
	const plantilla_promocion = 'ActaPromociones.jasper';
	const plantilla_examenes  = 'ActaExamenes.jasper';

	//----------------------------------------------------------------------------------------
	//---- Folio Físico ----------------------------------------------------------------------
	//----------------------------------------------------------------------------------------	
	
	// Constantes
	const forma_asignacion_folio_fisico_cierre = 'Cierre';
	const forma_asignacion_folio_fisico_impresion = 'Impresion';
	const forma_asignacion_folio_fisico_manual = 'Manual';

    const texto_acta_examen = 'Acta de Examen';
    const texto_acta_regulares = 'Acta de Regulares';
    const texto_acta_promocion = 'Acta de Promoción';
    const texto_mesa_dia = 'Mesa del día';
    const texto_comision = 'Comisión';
        
    protected static $docentes_autorizantes = [];
        
	/**
	 * Controla si es necesario realizar asignación de folio físico, y si corresponde lo efectúa.
	 * IMPORTANTE: enmarcar el llamado a esta función dentro de una transacción.
	 *
	 * @param array $datos_acta						Datos de la cabecera del acta
	 * @param int $cant_alumnos						Cantidad de detalles del acta
	 * @param string $forma_asignacion_ff			Cierre/Impresion
	 * @param string $carga_manual_libro			S/N
	 * @param string $agrega_folios_fisicos_extra	S/N
	 * @param int $libro_tomo						Libro/Tomo de actas (puede ser null)
	 * @param date $fecha							Fecha con la cual comparar la vigencia del libro de actas. Si es null, se comparará con CURRENT_DATE
	 * @return string								Código de mensaje de error o null si no hubo errores en la asignación
	 */
	static function asignar_folio_fisico($datos_acta, $cant_alumnos, $forma_asignacion_ff, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo=null, $fecha=null)
	{
		if ($carga_manual_libro == comunes::no) {
			// Se deberá obtener el libro_tomo activo según el método correspondiente.
			$libro_tomo = guarani::co('co_actas')->get_libro_de_actas($datos_acta, $fecha);
			if (empty($libro_tomo)) {
				return '800_sin_libro_activo';
			}
		}
				
		if ($forma_asignacion_ff == acta::forma_asignacion_folio_fisico_impresion && $datos_acta['version_impresa'] != 0) {
			return static::reasignacion($datos_acta['id_acta'], $cant_alumnos, $datos_acta['renglones_folio'], $forma_asignacion_ff, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo);
		} else {
			return static::primera_asignacion($datos_acta['id_acta'], $cant_alumnos, $datos_acta['renglones_folio'], $forma_asignacion_ff, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo);
		}
	}

	static function primera_asignacion($id_acta, $cant_alumnos, $lineas_folio_acta, $forma_asignacion_ff, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo)
	{
		// Se obtiene la máxima cantidad de folios del libro_tomo.
		$cant_max_folios = guarani::co('co_actas')->get_cant_max_folios_libro($libro_tomo);

		// Se obtiene el último folio físico asignado del libro_tipo (o cero si es un libro_tomo vacío).
		$folio_fisico = guarani::co('co_actas')->get_ultimo_folio_fisico($libro_tomo);

		// Se obtiene la cantidad de folios del acta.
		$cantidad_folios_acta = static::get_cantidad_folios_acta($cant_alumnos, $lineas_folio_acta);

		if (static::hay_folios_disponibles($cant_max_folios, $folio_fisico, $cantidad_folios_acta)) {
			static::actualizar_folios_fisicos($id_acta, $libro_tomo, $folio_fisico, $cantidad_folios_acta, $cant_max_folios);
		} else { // Los folios físicos disponibles no alcanzan a contener todos los folios del acta.
			if ($agrega_folios_fisicos_extra == comunes::si) {
				static::actualizar_folios_fisicos($id_acta, $libro_tomo, $folio_fisico, $cantidad_folios_acta, $cant_max_folios);
			} else { // No se permite agregar folios físicos extra.
				// Se abre un nuevo tomo				
				$libro_tomo = guarani::act('act_actas')->duplicar_libro_tomo_actas($libro_tomo);
				$folio_fisico = 0; // El libro se acaba de crear => no tiene folios físicos asignados.
				static::actualizar_folios_fisicos($id_acta, $libro_tomo, $folio_fisico, $cantidad_folios_acta, $cant_max_folios);
			}
		}
		
		return null;
	}
	
	static function actualizar_folios_fisicos($id_acta, $libro_tomo, $folio_fisico, $cantidad_folios_acta, $cant_max_folios)
	{
		for ($folio = 1; $folio <= $cantidad_folios_acta; $folio++) {
			$folio_fisico++;
			guarani::act('act_actas')->insertar_acta_folio($id_acta, $folio, $folio_fisico, $libro_tomo);
		}
		
		// Si los folios físicos asignados completan o superan la cantidad disponible en el tomo, se deberá abrir otro nuevo.
		if ((!empty($cant_max_folios) && $folio_fisico >= $cant_max_folios)) {
			$libro_tomo = guarani::act('act_actas')->duplicar_libro_tomo_actas($libro_tomo);
		}
	}

	static function reasignacion($id_acta, $cant_alumnos, $lineas_folio_acta, $forma_asignacion_ff, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo)
	{
		$cantidad_folios_acta = static::get_cantidad_folios_acta($cant_alumnos, $lineas_folio_acta);
		$cantidad_ff_asignados = guarani::co('co_actas')->get_cantidad_folios_fisicos_acta($id_acta);

		if ($cantidad_ff_asignados < $cantidad_folios_acta) {
			// Se obtiene la máxima cantidad de folios del libro.
			$cant_max_folios = guarani::co('co_actas')->get_cant_max_folios_libro($libro_tomo);

			// Se obtiene el último folio físico asignado del libro (o cero si es un libro vacío).
			$folio_fisico = guarani::co('co_actas')->get_ultimo_folio_fisico($libro_tomo);

			$folio = $cantidad_ff_asignados + 1;
			while ($folio <= $cantidad_folios_acta) {
				// Si los folios físicos asignados completan o superan la cantidad disponible en el tomo, se deberá abrir uno nuevo
				if (! is_null($cant_max_folios) && $folio_fisico == $cant_max_folios) {
					$libro_tomo = guarani::act('act_actas')->duplicar_libro_tomo_actas($libro_tomo);
					$folio_fisico = 0;
				}
				$folio_fisico++;
				guarani::act('act_actas')->insertar_acta_folio($id_acta, $folio, $folio_fisico, $libro_tomo);
				$folio++;
			}

			// Si los folios físicos asignados completan o superan la cantidad disponible en el tomo, se deberá abrir otro nuevo.
			if ((!empty($cant_max_folios) && $folio_fisico >= $cant_max_folios)) {
				$libro_tomo = guarani::act('act_actas')->duplicar_libro_tomo_actas($libro_tomo);
			}
		}

		return null;
	}

	/**
	 * Obtiene la cantidad de folios de un acta dada.
	 *
	 * @param 	int	$cant_alumnos			Cantidad de detalles del acta.
	 * @param 	int	$lineas_folio_acta 		Cantidad de renglones por folio
	 * @return 	int							Cantidad de folios del acta.
	 */
	static function get_cantidad_folios_acta($cant_alumnos, $lineas_folio_acta)
	{
		return ceil($cant_alumnos/$lineas_folio_acta);
	}
	
	/**
	 * Determina si queda espacio en un libro de actas para asignar una determinada cantidad de folios.
	 *
	 * @param 	int	$cant_max_folios			Cantidad máxima de folios por libro (puede ser null)
	 * @param 	int $folios_fisicos_asignados	Cantidad de folios ya asignados en el libro (puede ser cero)
	 * @param 	int	$cantidad_folios_necesarios	Cantidad de folios que ocupa el acta
	 * @return 	boolean
	 */
	static function hay_folios_disponibles($cant_max_folios, $folios_fisicos_asignados, $cantidad_folios_necesarios)
	{
		if (is_null($cant_max_folios)) {
			return true;
		}
		return $cantidad_folios_necesarios <= ($cant_max_folios - $folios_fisicos_asignados);
	}

	/**
	 * Determina si existen más folios en el acta que folios físicos asignados (se necesita una reasignación).
	 *
	 * @param integer $id_acta
	 * @param integer $cant_alumnos
	 * @param integer $lineas_folio_acta
	 * @return boolean
	 */
	static function hay_folios_no_asignados($id_acta, $cant_alumnos, $lineas_folio_acta)
	{
		$cantidad_folios_acta = static::get_cantidad_folios_acta($cant_alumnos, $lineas_folio_acta);
		$cantidad_ff_asignados = guarani::co('co_actas')->get_cantidad_folios_fisicos_acta($id_acta);
		if ($cantidad_ff_asignados < $cantidad_folios_acta) {
			return true;
		}
		return false;
	}

	/**
	 * Determina si se debe solicitar al usuario la selección de un libro-tomo para poder imprimir el acta dada.
	 * 
	 * @param integer $id_acta
	 * @param boolean $origen_examen
	 * @return boolean
	 */
	static function solicitar_libro_impresion($id_acta, $cant_alumnos, $origen_examen=false)
	{
		if ($origen_examen) {
			$datos_acta = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($id_acta);
			$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($datos_acta['mesa_examen']), 'propuesta');
			$param_carga_manual_libro = 'exa_carga_manual_libro_examen';
			$param_forma_asignacion_folio_fisico = 'exa_forma_asignacion_folio_fisico';
		} else {
			$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($id_acta);
			$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
			$param_carga_manual_libro = ($datos_acta['origen'] == acta::origen_regulares) ? 'cur_carga_manual_libro_regulares' : 'cur_carga_manual_libro_promocion';
			$param_forma_asignacion_folio_fisico = ($datos_acta['origen'] == acta::origen_regulares) ? 'cur_forma_asignacion_folio_fisico_regul' : 'cur_forma_asignacion_folio_fisico_promo';
		}
		if ($datos_acta['digital'] == comunes::si) {
			return false;
		} else {
			$carga_manual_libro = guarani::parametros()->get_valor($param_carga_manual_libro, $propuestas, false);
			$forma_asignacion_folio_fisico = guarani::parametros()->get_valor($param_forma_asignacion_folio_fisico, $propuestas, false);
			return ($carga_manual_libro == comunes::si && // Carga MANUAL de libro.
					$forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_impresion && // Asignación de folio físico en la impresión.
					$datos_acta['estado'] == acta::estado_abierta && // Acta abierta.
					$datos_acta['version'] != $datos_acta['version_impresa'] && // Hubo cambios en el acta luego de su última impresión.
					static::hay_folios_no_asignados($id_acta, $cant_alumnos, $datos_acta['renglones_folio'])); // Hay folios en el acta que no tienen asignado su correspondiente folio físico.
		}
	}

	/**
	 * Determina si se debe solicitar al usuario la selección de un libro-tomo para poder cerrar el acta dada.
	 * 
	 * @param integer $id_acta
	 * @param boolean $origen_examen
	 * @return boolean
	 */
	static function solicitar_libro_cierre($id_acta, $origen_examen=false)
	{
		if ($origen_examen) {
			$datos_acta = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($id_acta);
			$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($datos_acta['mesa_examen']), 'propuesta');
			$param_carga_manual_libro = 'exa_carga_manual_libro_examen';
			$param_forma_asignacion_folio_fisico = 'exa_forma_asignacion_folio_fisico';
		} else {
			$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($id_acta);
			$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
			$param_carga_manual_libro = ($datos_acta['origen'] == acta::origen_regulares) ? 'cur_carga_manual_libro_regulares' : 'cur_carga_manual_libro_promocion';
			$param_forma_asignacion_folio_fisico = ($datos_acta['origen'] == acta::origen_regulares) ? 'cur_forma_asignacion_folio_fisico_regul' : 'cur_forma_asignacion_folio_fisico_promo';
		}
		if ($datos_acta['digital'] == comunes::si) {
			return false;
		} else {
			$carga_manual_libro = guarani::parametros()->get_valor($param_carga_manual_libro, $propuestas, false);
			$forma_asignacion_folio_fisico = guarani::parametros()->get_valor($param_forma_asignacion_folio_fisico, $propuestas, false);
			return ($carga_manual_libro == comunes::si && // Carga MANUAL de libro.
					$forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_cierre // Asignación de folio físico en el cierre.
			);
		}
	}

	//----------------------------------------------------------------------------------------
	//---- Validaciones ----------------------------------------------------------------------
	//----------------------------------------------------------------------------------------	

	/**
	 * Retorna el objeto validador de actas de cursada.
	 *
     * @return validador_actas_cursada
     *
	 */
	static function validador_actas_cursada()
	{
		return validador_actas_cursada::instancia();
	}
        
        /**
        * Envía un acta a Araí-Documentos.
        * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
        * @see https://redmine.siu.edu.ar/redmine/issues/30935 Ticket #30935
        * @see https://redmine.siu.edu.ar/redmine/issues/30959 Ticket #30959
        * @see https://redmine.siu.edu.ar/redmine/issues/42326 Ticket #42326
        * @param int $id_acta Id del acta en la tabla 'sga_actas'.
        * @param array $parametros Parámetros necesarios, consta del siguiente formato:
        * <code>
           $parametros['utiliza_acta_digital'] = 'S';
           $parametros['docentes_nivel_autorizacion'] = 'S';
           $parametros['autoridad_firma_acta_digital'] = 'S';
           
           $parametros['exa_actas_examen_cerradas_cnt_ejemplares'] = '4';
           $parametros['exa_actas_examen_abiertas_cnt_ejemplares'] = '3';
           $parametros['exa_orden_detalle_actas'] = '2';
         
           $parametros['cur_actas_cerradas_cnt_ejemplares'] = '4';
           $parametros['cur_actas_abiertas_cnt_ejemplares'] = '3';
           $parametros['cur_inserta_alumnos_no_promocionales_en_acta'] = '2';
           $parametros['cur_orden_detalle_actas'] = '2';
         
           // id_tramite de SUDOCU, si no se pasa no se asocia el documento a SUDOCU (EEI)
           $parametros['id_tramite'] = '901e7f46-4e07-42ae-addc-8f75b383aee9';
        * </code>
        * @param int[] $ras Array con los ids de las responsables académicas.
        * @param string|null $archivo Path del archivo PDF del acta, de esta forma no lo genera con Jasper.
        * @param bool $borrar_archivo Si el archivo debe ser borrado una vez que se lo envió a SIU-Araí Documentos o no.
        * @param bool $async Si la creación del documento en SIU-Araí es asincrónica o no.
        * @param bool $msg_error_generico Si muestra un mensaje de error genérico, o el error en concreto.
        * @throws \Exception Si falla algo en el proceso
        * @return void.
        */
        public static function enviar_acta_arai_documentos($id_acta, $parametros, $ras, $archivo = null, $borrar_archivo = true, $async = true, $msg_error_generico = true)
        {
            
            $utiliza_acta_digital = ($parametros['utiliza_acta_digital'] == comunes::si);
            // Si no se utiliza acta digital salgo
            if (!$utiliza_acta_digital) return false;
                
            try {
                
                $data = [];
                $autorizantes = [];
                $autoridades_firmantes = [];
                $nivel_autorizacion = Autorizacion::NIVEL_BASICA;
                $documento_titulo = '';
                $documento_descripcion = '';
                $datos_documento = [];
                $estado_documento_digital = arai_documentos::ESTADO_DOCUMENTO_DIGITAL_PENDIENTE;

                $rs = toba::consulta_php('co_actas')->get_origen_acta($id_acta);
                $rs = reset($rs);
                $origen_acta = $rs['origen'];
                
                $data['autorizacion']['modelo'] = Autorizacion::MODELO_MULTIPLE_CADENA;

                $data['documento']['tipo'] = arai_documentos::TIPO_DOCUMENTO_ACTA;

                $arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';

                // Si el origen viene de los parámetros (resincronización de actas fallidas)
                if(isset($parametros['origen'])){
                    $origen = $parametros['origen'];
                } else {// sino
                    $params['documento_tipo'] = $data['documento']['tipo'];
                    $params['id'] = $id_acta;
                    $params['ras'] = $ras;
                    $params['metodo'] = __METHOD__;
                    $origen = $arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params);
                    $parametros['origen'] = $origen;
                }

                // Si no se pasa el archivo 
                if (is_null($archivo)) {
                    
                    try {
                        $data['archivo'] = static::generar_pdf_acta($id_acta, $parametros, $origen_acta);
                    } catch (\Exception $e) {
                        $mensaje = guarani_mensajes::get('800_error_generar_pdf_acta', [$e->getMessage()]);
                        throw new \Exception($mensaje);
                    }

                } elseif (!file_exists($archivo)) {
                    // Si no existe el archivo
                    $mensaje = guarani_mensajes::get('800_pdf_acta_inexistente', [$archivo]);
                    throw new \Exception($mensaje);
                } else {
                    // Si existe el archivo
                    $data['archivo'] = $archivo;
                }
                
                // Si se trata de un acta de examen
                if ($origen_acta == static::origen_examen) {
                    $encabezado_acta = toba::consulta_php('co_actas')->get_encabezado_acta_examen_arai_documentos($id_acta);
                } elseif (in_array($origen_acta, [static::origen_regulares, static::origen_promociones])) {
                    // Si se trata de un acta de regulares o promociones
                    $encabezado_acta = toba::consulta_php('co_actas')->get_encabezado_acta_comision_arai_documentos($id_acta, $origen_acta);                    
                }
                
                //Seteo titulo del documento y su descripcion
                $documento_titulo = static::get_titulo_documento_arai($encabezado_acta);
                $documento_descripcion = static::get_descripcion_documento_arai($encabezado_acta);
                
                $data['documento']['titulo'] = $documento_titulo;
                $data['documento']['descripcion'] = $documento_descripcion;
                // Ver Ticket #42326 (https://redmine.siu.edu.ar/redmine/issues/42326)
                $data['documento']['id_tramite'] = ($parametros['id_tramite'] ?? null);
                /*
                 * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
                 * Este campo se utiliza para almacenar en formato json, los datos que requiera la aplicación que genera el documento.
                 */
                $data['documento']['referencia_interna'] = [
					'id_acta' => $id_acta,
					'sistema' => arai_documentos::ID_INSTALACION,
					'parametros' => $parametros,
					'ras' => $ras
				];
                $data['documento']['identificador'] = $id_acta;
                /*
                 * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
                 * Atributos adicionales del documento.
                 */
                $data['documento']['atributos'] = [];
                
				// Si la creación es de forma asincrónica y se trata de Autogestión
				// guardo la URL del documento para que se cree con una callback.
				if ($async && \comunes::es_interfaz_autogestion()) {
					$data['documento']['referencia_interna']['url_file'] = static::get_url_file($data['archivo']);
				}

                $autorizantes = static::get_docentes_autorizantes_acta($id_acta);
                
                if ($parametros['docentes_nivel_autorizacion'] == arai_documentos::DOCENTE_AUTORIZACION_SIMPLE) {
                    $nivel_autorizacion = Autorizacion::NIVEL_BASICA;
                } else {
                    $nivel_autorizacion = Autorizacion::NIVEL_FIRMA;
                }

                $autoridades_firmantes = $arai_documentos_class::get_autoridades_firmantes($parametros['autoridad_firma_acta_digital'], $ras);
            
                static::validar_autorizantes_asociados_acta($id_acta, $parametros['autoridad_firma_acta_digital'], $ras);

                // Limpio por si se repiten docentes autorizantes con autoridades firmantes
                $autorizantes = array_diff($autorizantes, $autoridades_firmantes);
                $autorizantes = array_unique($autorizantes);
                $autoridades_firmantes = array_unique($autoridades_firmantes);

                $secuencia = 1;
                foreach ($autorizantes as $autorizante) {
                    $data['autorizantes'][] = [
                        'usuario' => $autorizante,
                        'nivel_autorizacion' => $nivel_autorizacion,
                        'secuencia' => $secuencia
                    ];
                    $secuencia++;
                }

                foreach ($autoridades_firmantes as $autoridad_firmante) {
                    $data['autorizantes'][] = [
                        'usuario' => $autoridad_firmante,
                        'nivel_autorizacion' => Autorizacion::NIVEL_FIRMA,
                        'secuencia' => $secuencia
                    ];
                    $secuencia++;
                }

                $data['origen']['id_usuario_sso'] = $origen;
                $data['origen']['cuenta_usuario'] = $origen;
                // Se le debe pasar 'id_instalacion' ya que se muestra en la estampa
                $data['origen']['id_instalacion'] = arai_documentos::ID_INSTALACION_ESTAMPA;
                
                try {
					
                    $documento = $arai_documentos_class::armarDocumento($data, $async);
					
					// Si la creación es de forma asincrónica
					if ($async) {
						// Si se trata de Autogestión se genera el documento con callback
						if (\comunes::es_interfaz_autogestion()) {
							$arai_documentos_class::generateAndPostAsync($documento);
						} else {// Si se trata de Gestión se genera el documento sin callback
							$arai_documentos_class::postDocumentoAsync($documento);
						}
						
					} else {// Si la creación es de forma sincrónica
						$datos_documento = $arai_documentos_class::postDocumento($documento);
					}

                } catch (\Exception $e) {
                    $mensaje = $e->getMessage();
                    throw new \Exception(utf8_decode($mensaje));
                }
                
                if (!$async) {
                        guarani::act('act_actas')->actualizar_documento_digital($id_acta, $datos_documento['uid_documento']);
                        $estado_documento_digital = arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO;
                        // Marco el documento como creado en SIU-Araí
                        guarani::act('act_arai_documentos')->actualizar_estado_documento($datos_documento['uid_documento'], false, arai_documentos::SOLICITUD_AUTORIZADA);
                        // Recupero los movimientos del documento
                        $movimientos_documento = arai_documentos::getMovimientosDocumento($datos_documento['uid_documento']);
                        // Tomo las autorizaciones
                        $autorizaciones = arai_documentos::getAutorizacionesByMovimientos($movimientos_documento);

                        // Registro las autorizaciones del documento
                        if (!empty($autorizaciones)) {
                            guarani::act('act_arai_documentos')->eliminar_autorizaciones_documento($datos_documento['uid_documento']);
                            guarani::act('act_arai_documentos')->agregar_autorizaciones_documento($datos_documento['uid_documento'], $autorizaciones);
                        }
                }
                
                $params = [];
                $params['id_acta'] = $id_acta;
                guarani::notificador()->enviar(notificador::autorizar_acta_huarpe, $params, false);
                
            } catch (\Exception $e) {
                
				$estado_documento_digital = arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO;
				$parametros_documento_digital['parametros'] = $parametros;
				$parametros_documento_digital['ras'] = $ras;
				guarani::act('act_actas')->actualizar_parametros_documento_digital($id_acta, $parametros_documento_digital);
                toba::logger()->error($e);
				if ($msg_error_generico) {
					$mensaje = guarani_mensajes::get('800_fallo_creacion_documento_arai');
				} else {
					$mensaje = $e->getMessage();
				}
                throw guarani::error($mensaje);
                
            } finally {
                
                // Borro el PDF del acta
                if ($borrar_archivo && isset($data['archivo']) && file_exists($data['archivo'])) {
                    unlink($data['archivo']);
                }
                
		// Actualizo el estado del documento digital asociado al acta
                guarani::act('act_actas')->actualizar_estado_documento_digital($id_acta, $estado_documento_digital);
                
            }
            
	}
        
       /**
        * Obtiene los docentes autorizantes de un acta.
        * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
        * @see https://redmine.siu.edu.ar/redmine/issues/30935 Ticket #30935
        * @see https://redmine.siu.edu.ar/redmine/issues/30959 Ticket #30959
        * @param int $id_acta Id del acta en la tabla 'sga_actas'.
        * @throws \Exception Si existen docentes sin usuario vinculado en Araí.
        * @return array Docentes autorizantes.
        */
        public static function get_docentes_autorizantes_acta($id_acta)
        {
            if (!isset(static::$docentes_autorizantes[$id_acta])) {
                
                static::$docentes_autorizantes[$id_acta] = [];
                $docentes = [];
                $docentes_autorizantes = [];
                
                $rs = toba::consulta_php('co_actas')->get_origen_acta($id_acta);
                $rs = reset($rs);
                $origen_acta = $rs['origen'];
                
                if ($origen_acta == static::origen_examen) {
                    $docentes = toba::consulta_php('co_actas')->get_docentes_autorizantes_acta_examen($id_acta);
                } elseif (in_array($origen_acta, [static::origen_regulares, static::origen_promociones])) {
                    $docentes = toba::consulta_php('co_actas')->get_docentes_autorizantes_acta_comision($id_acta);
                }
                
                foreach ($docentes as $docente) {

                    // Si la columna mdp_personas.arai_identificador_sso es NULL o un string vacío con solo espacios
                    if (is_null($docente['arai_identificador_sso']) || trim($docente['arai_identificador_sso']) == '') {
                        throw guarani::error(guarani_mensajes::get('800_docente_no_vinculado_arai'));
                    } else {
                        $docentes_autorizantes[] = $docente['arai_identificador_sso'];
                    }
                }
                
                static::$docentes_autorizantes[$id_acta] = $docentes_autorizantes;
            }
            
            return static::$docentes_autorizantes[$id_acta];
        }
        
       /**
        * Valida que existan autorizantes asociados al acta.
        * @param int $id_acta                           Id del acta en la tabla 'sga_actas'.
        * @param string $autoridad_firma_acta_digital   Parámetro 'autoridad_firma_acta_digital'.
        * @param int[]  $ras                            Array con los ids de las responsables académicas.
        * @throws \Exception                            Si no existen autorizantes asociados al acta.
        */
        public static function validar_autorizantes_asociados_acta($id_acta, $autoridad_firma_acta_digital, $ras)
        {
            $autorizantes = static::get_docentes_autorizantes_acta($id_acta);
            $arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
            $autoridades_firmantes = $arai_documentos_class::get_autoridades_firmantes($autoridad_firma_acta_digital, $ras);
            
            // Si no existen autorizantes asociados al acta lazo una excepción 
            if (empty($autorizantes) && empty($autoridades_firmantes)) {
                throw guarani::error(guarani_mensajes::get('800_no_existen_autorizantes'));
            }
        }
        
       /**
        * Genera el PDF de un acta dada.
        * @param int $id_acta Id del acta en la tabla 'sga_actas'.
        * @param array $parametros Parámetros necesarios, consta del siguiente formato:
        * <code>
           $parametros['exa_actas_examen_cerradas_cnt_ejemplares'] = '4';
           $parametros['exa_actas_examen_abiertas_cnt_ejemplares'] = '3';
           $parametros['exa_orden_detalle_actas'] = '2';
        
           $parametros['cur_actas_cerradas_cnt_ejemplares'] = '4';
           $parametros['cur_actas_abiertas_cnt_ejemplares'] = '3';
           $parametros['cur_inserta_alumnos_no_promocionales_en_acta'] = '2';
           $parametros['cur_orden_detalle_actas'] = '2';
        * </code>
        * @param string $origen_acta Si se trata de un acta de examen, promoción, regular, etc.
        * @param bool $es_copia Si el acta es copia.
        * @throws \Exception Si falla algo en el proceso
        * @return string Path al PDF del acta de examen.
        */
        public static function generar_pdf_acta($id_acta, $parametros, $origen_acta, $es_copia = false)
        {
            if ($origen_acta == static::origen_examen) {
                return static::generar_pdf_acta_examen($id_acta, $parametros, $es_copia);
            } elseif (in_array($origen_acta, [static::origen_regulares, static::origen_promociones])) {
                return static::generar_pdf_acta_cursada($id_acta, $parametros);
            }
        }
        
       /**
        * Genera el PDF de un acta de examen dada.
        * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
        * @param int $id_acta Id del acta en la tabla 'sga_actas'.
        * @param array $parametros Parámetros necesarios, consta del siguiente formato:
        * <code>
           $parametros['exa_actas_examen_cerradas_cnt_ejemplares'] = '4';
           $parametros['exa_actas_examen_abiertas_cnt_ejemplares'] = '3';
           $parametros['exa_orden_detalle_actas'] = '2';
        * </code>
        * @param bool $es_copia Si el acta es copia.
        * @throws \Exception Si falla algo en el proceso
        * @return string Path al PDF del acta de examen.
        */
        public static function generar_pdf_acta_examen($id_acta, $parametros, $es_copia = false)
		{
            try {
                
                $report = new toba_vista_jasperreports();
                // Seteo la plantilla a usar
                $report->set_path_reporte(static::get_path_reporte_jasper(acta::plantilla_examenes));

                $filtro = "sga_actas.id_acta = $id_acta";
                $datos_acta = current(toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro));

                // Cantidad de Ejemplares
                $es_acta_cerrada = ($datos_acta['estado'] == acta::estado_cerrada);
                $cnt_ejemplares = $es_acta_cerrada ? $parametros['exa_actas_examen_cerradas_cnt_ejemplares'] : $parametros['exa_actas_examen_abiertas_cnt_ejemplares'];

                //Institucion
                $institucion = current(toba::consulta_php('co_instituciones')->get_institucion_gestionante());

                $actividad = $datos_acta['elemento'];
                $llamado = $datos_acta['llamado_mesa'];
                $datos = toba::consulta_php('co_responsables_academicas')->get_ras_actividad_llamado($actividad, $llamado);
                // Es copia o acta anulada?
                $es_acta_anulada = ($datos_acta['estado'] == acta::estado_anulada);
                $anulada = $es_acta_anulada ? 'ANULADA' : '';

                $copia = $es_copia ? 'ES COPIA' : '';
                $texto = $copia . $anulada;

                // Si se trata de un acta rectificativa
                $es_rectificativa = ($datos_acta['tipo_acta'] == acta::tipo_rectificativa);
                $titulo = 'ACTA';
                $rectifica_a = "null";
                if ($es_rectificativa) {
                    $rectifica_a = $datos_acta['rectifica_a'];
                    $titulo .= ' RECTIFICATIVA DE EXAMEN';
                } else {
                    $titulo .= ' DE EXAMEN';
                }

                $report->set_parametro('titulo', 'S', $titulo);
                $report->set_parametro('logo', 'S', static::get_path_logo_impresion('logo_comprobante_sin_bajada_nuevo.png'));
                $report->set_parametro('institucion', 'S', $institucion['nombre']);
                $report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
                $report->set_parametro('texto_aux', 'S', $texto);
                $str = (is_null($datos_acta['observaciones'])) ? '' : $datos_acta['observaciones'];
                $report->set_parametro('observaciones', 'S', $str);
                $report->set_parametro('nro_acta', 'S', $datos_acta['nro_acta']);
                $report->set_parametro('fecha', 'S', $datos_acta['fecha_formato_visual']);
                $report->set_parametro('rectifica_a', 'S', $rectifica_a);
                $report->set_parametro('version', 'S', $datos_acta['version']);
                $report->set_parametro('codigo_nombre_actividad', 'S', $datos_acta['cod_nombre_actividad']);
                $str = (is_null($datos_acta['turno_nombre'])) ? ' - ' : $datos_acta['turno_nombre'];
                $report->set_parametro('turno_examen', 'S', $str);
                $report->set_parametro('mesa', 'S', $datos_acta['mesa_nombre']);
                $str = (is_null($datos_acta['llamado_nombre'])) ? ' - ' : $datos_acta['llamado_nombre'];
                $report->set_parametro('nombre_llamado', 'S', $str);
                $report->set_parametro('anio_academico', 'E', $datos_acta['anio_academico']);
                $report->set_parametro('nombre_ubicacion', 'S', $datos_acta['ubicacion_nombre']);
		$report->set_parametro('acta_digital', 'S', $datos_acta['digital']);
                // Docentes y Roles (Maximo 3 por acta)
                // le agrego un parametro para saber que se va a imprimir en un documento oficial y debe resperat el parametro
				$docentes_actas = toba::consulta_php('co_mesas_de_examen')->get_docentes_mesa($datos_acta['llamado_mesa'], true);
                $docentes       = "";
                for ($i = 0; $i < 3; $i++) {
                    $rol            = '';
                    $docente_nombre = '';
                    if (isset($docentes_actas[$i]['docente_nombre'])) {
                        $docente_nombre = $docentes_actas[$i]['docente_nombre'];
                        $rol            = $docentes_actas[$i]['nombre'];
                        $docentes       .= "{$docente_nombre} ({$rol}) ";
                    }

                    $par = 'docente_'.($i + 1);
                    $report->set_parametro($par, 'S', $docente_nombre);
                    $par = 'rol_'.($i + 1);
                    $report->set_parametro($par, 'S', $rol);
                }
                $report->set_parametro('docentes', 'S', $docentes);
                //Se agregan los datos de los alumnos
                $xml = static::generar_xml_alumnos_acta_examen($datos_acta['id_acta'], $datos_acta['estado'], $parametros);
                $report->set_xml(utf8_e_seguro($xml));
                
                // Si hay que generar mas de una copia replico el reporte
                for ($i = 0; $i < $cnt_ejemplares; $i++) {
                        $report->completar_con_datos();
                }

                $report->generar_salida();

                return $report->get_nombre_archivo_generado();
            
            } catch (\Exception $e) {
                
                toba::logger()->error($e);
                throw guarani::error($e->getMessage());
                
            }
            
		}
        
       /**
        * Genera el XML con los alumnos de un acta de examen.
        * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
        * @param int $id_acta Id del acta de examen.
        * @param string $estado Estado del acta de examen.
        * @param array $parametros Parámetros necesarios, consta del siguiente formato:
        * <code>
           $parametros['exa_orden_detalle_actas'] = '2';
        * </code>
        * @return string XML del acta de examen.
        */
        protected static function generar_xml_alumnos_acta_examen($id_acta, $estado, $parametros)
        {
            
            $xml = '<tabla><datos>';
            
            $params_sistema = array('exa_orden_detalle_actas' => $parametros['exa_orden_detalle_actas']);
            $alumnos =  toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
            foreach ($alumnos as $key => $alumno) {
                
                if ((isset($alumno['estado'])) && ($alumno['estado'] == acta::detalle_estado_invalidado)) {
                    $alumno['persona'] .= ' === REGISTRO INVALIDADO ===';
                }
                $alumno['folio_fisico'] = acta::libro_no_aplica;
                $alumno['libro'] = acta::libro_no_aplica;
                $alumno['tomo'] = acta::libro_no_aplica;
                
                $xml .= '<fila>';
                
                foreach ($alumno as $clave => $valor) {
                    // Escape a caracteres especiales xml que generan error en el parser de jasper.
                    $valor = \guarani_texto::reemplazar_caracteres_especiales_xml($valor);
                    $valor = \guarani_texto::parsear_caracteres_xml($valor);
                    $xml .= '<dato clave="'.$clave.'" valor="'.$valor.'"/>';
                }
                
                $xml .= '</fila>';
                
            }
            
            $xml .= '<col titulo="Nº" text-align="left"/>';
            $xml .= '<col titulo="Apellido y Nombre" text-align="left"/>';
            $xml .= '<col titulo="Identificación" text-align="left"/>';
            $xml .= '<col titulo="Legajo" text-align="left"/>';
            $xml .= '<col titulo="Instancia" text-align="left"/>';
            $xml .= '<col titulo="Fecha" text-align="left"/>';
            $xml .= '<col titulo="Nota" text-align="left"/>';
            $xml .= '<col titulo="Letras" text-align="left"/>';
            $xml .= '<col titulo="Resultado" text-align="left"/>';
            $xml .= '</datos></tabla>';
            
            return $xml;
        }
        
       /**
        * Genera el PDF de un acta de cursada dada.
        * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
        * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
        * @param int $id_acta Id del acta en la tabla 'sga_actas'.
        * @param array $parametros Parámetros necesarios, consta del siguiente formato:
        * <code>
           $parametros['cur_actas_cerradas_cnt_ejemplares'] = '4';
           $parametros['cur_actas_abiertas_cnt_ejemplares'] = '3';
           $parametros['cur_inserta_alumnos_no_promocionales_en_acta'] = '2';
           $parametros['cur_orden_detalle_actas'] = '2';
        * </code>
        * @param bool $es_copia Si el acta es copia.
        * @throws \Exception Si falla algo en el proceso
        * @return string Path al PDF del acta de cursada.
        */
        public static function generar_pdf_acta_cursada($id_acta, $parametros)
        {
            
            try {
                
            
                $datos_acta = \toba::consulta_php('co_actas')->get_datos_acta($id_acta);
            
                // Cantidad de ejemplares a imprimir.
				$cnt_ejemplares = ($datos_acta['estado'] == \acta::estado_cerrada) ? $parametros['cur_actas_cerradas_cnt_ejemplares'] : $parametros['cur_actas_abiertas_cnt_ejemplares'];

				$titulo = 'ACTA';

                                // Si se trata de un acta rectificativa
                                $es_rectificativa = ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa);
                                if ($es_rectificativa) {
                                    $titulo .= ' RECTIFICATIVA ';
                                }

                                // Si se trata de acta de regulares
				if ($datos_acta['origen'] == \acta::origen_regulares) {
					$plantilla = \acta::plantilla_regulares;
					$titulo .= ' DE REGULARES ';
					$instancia_aux = \comision::inst_cursar;
                                // Si se trata de acta de promociones
				} elseif ($datos_acta['origen'] == \acta::origen_promociones) {
					$plantilla = \acta::plantilla_promocion;
					$titulo .= ' DE PROMOCIÓN ';
					$instancia_aux = \comision::inst_promocionar;
				}

				$report = new toba_vista_jasperreports();
				// Seteo la plantilla a usar
				$report->set_path_reporte(static::get_path_reporte_jasper($plantilla));

				$copia = ($datos_acta['version_impresa'] && $datos_acta['estado'] == \acta::estado_cerrada) ? 'ES COPIA' : '';
				$anulada = ($datos_acta['estado'] == \acta::estado_anulada) ? 'ANULADA' : '';
				//Institucion
				$institucion = current(\toba::consulta_php('co_instituciones')->get_institucion_gestionante());
				$actividad = $datos_acta['elemento'];
				$comision = $datos_acta['comision'];
				$datos = toba::consulta_php('co_responsables_academicas')->get_ras_actividad_comision($actividad, $comision);

				$text_aux = $copia . $anulada;
				$report->set_parametro('titulo', 'S', $titulo);
				$report->set_parametro('logo', 'S', static::get_path_logo_impresion('logo-transparente.png'));
				$report->set_parametro('institucion', 'S', $institucion['nombre']);
				$report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
				$report->set_parametro('texto_aux', 'S', $text_aux);
				$str = (is_null($datos_acta['observaciones'])) ? '' : $datos_acta['observaciones'];
				$report->set_parametro('observaciones', 'S', $str);
				$report->set_parametro('version', 'S', $datos_acta['version']);
				$report->set_parametro('comision_nombre', 'S', $datos_acta['comision_nombre']);
				$report->set_parametro('anio_academico_periodo', 'E', $datos_acta['anio_academico_periodo']);
				$report->set_parametro('nro_acta', 'S', $datos_acta['nro_acta']);
				$report->set_parametro('codigo_y_nombre_actividad', 'S', $datos_acta['codigo_y_nombre_actividad']);
				$report->set_parametro('comision_nombre', 'S', $datos_acta['comision_nombre']);
				$report->set_parametro('periodo_nombre', 'S', $datos_acta['periodo_lectivo_nombre']);
				$report->set_parametro('nombre_ubicacion', 'S', $datos_acta['nombre_ubicacion']);
				$report->set_parametro('acta_digital', 'S', $datos_acta['digital']);
				
				if ($es_rectificativa) {
					$nro_acta_rectificada = \toba::consulta_php('co_actas')->get_nro_acta_rectificada($datos_acta['acta_referencia']);
					if (!empty($nro_acta_rectificada)) {
						$report->set_parametro('acta_referencia', 'S', $nro_acta_rectificada[0]['nro_acta_rectificada']);
					}
				}
				$report->set_parametro('fecha_inicio', 'S', $datos_acta['fecha_inicio_periodo_visual']);
				$report->set_parametro('fecha_fin', 'S', $datos_acta['fecha_fin_periodo_visual']);
				$docentes = (is_null($datos_acta['docentes'])) ? '' : $datos_acta['docentes'];
				$report->set_parametro('docentes', 'S', $docentes);

				// Se agregan los datos de los alumnos.
				if (($datos_acta['estado'] == \acta::estado_anulada || $datos_acta['estado'] == \acta::estado_cerrada)) {
					$instancia = null;
				} else {
					$instancia = $instancia_aux;
				}

				$params_sistema = array(
					'cur_inserta_alumnos_no_promocionales_en_acta' => $parametros['cur_inserta_alumnos_no_promocionales_en_acta'],
					'cur_orden_detalle_actas' => $parametros['cur_orden_detalle_actas']
				);

				$datos_alumnos = \toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($datos_acta['id_acta'], $instancia, '', '', '', false, $params_sistema);
				foreach ($datos_alumnos as $clave => $valor) {
					$datos_alumnos[$clave]['folio_fisico'] = acta::libro_no_aplica;
					$datos_alumnos[$clave]['libro'] = acta::libro_no_aplica;
					$datos_alumnos[$clave]['tomo'] = acta::libro_no_aplica;
				}

				$columnas = array('renglon', 'documento_principal', 'apellido_y_nombre', 'fecha', 'cond_regularidad_nombre', 'nota_nombre', 'resultado_nombre', 'folio_fisico', 'libro', 'tomo', 'folio');
				$xml = "<tabla break-after='page'><datos>";
				foreach ($datos_alumnos as $datos_alumno) {
					$xml .= '<fila>';
					foreach ($columnas as $clave) {
						$valor = isset($datos_alumno[$clave]) ? $datos_alumno[$clave] : '';
                                                // Escape a caracteres especiales xml que generan error en el parser de jasper.
                                                $valor = \guarani_texto::reemplazar_caracteres_especiales_xml($valor);
                                                $valor = \guarani_texto::parsear_caracteres_xml($valor);
						$xml .= '<dato clave="' . $clave . '" valor="' . $valor . '"/>';
					}
					$xml .= '</fila>';
				}
				$xml .= '</datos></tabla>';

				$report->set_xml(utf8_e_seguro($xml));

				// Si hay que generar más de una copia se replica el reporte.
				for ($i = 0; $i < $cnt_ejemplares; $i++) {
					$report->completar_con_datos();
				}

				$report->generar_salida();

                return $report->get_nombre_archivo_generado();
                
            } catch (\Exception $e) {
                
                toba::logger()->error($e);
                throw guarani::error($e->getMessage());
                
            }
            
        }
        
        protected static function get_path_reporte_jasper($plantilla)
        {
            return guarani::get_path_reporte_jasper($plantilla);
        }
        
        protected static function get_path_logo_impresion($logo = '')
        {
            return guarani::get_path_logo_impresion();
        }

        public static function get_url_file($archivo)
		{
			return "";
		}

       /**
        * Devuelve el título para el documento de Araí.
        * @param array $encabezado_acta Encabezado del acta.
        * @return string Título para el documento de Araí.
        */
        public static function get_titulo_documento_arai($encabezado_acta)
        {
            return $encabezado_acta['titulo'];
        }

       /**
        * Devuelve la descripción para el documento de Araí.
        * @param array $encabezado_acta Encabezado del acta.
        * @return string Descripción para el documento de Araí.
        */
        public static function get_descripcion_documento_arai($encabezado_acta)
        {
            return $encabezado_acta['descripcion'];
        }
        
}
