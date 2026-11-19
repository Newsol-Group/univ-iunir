<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani;

class mensajes extends transaccion
{
	protected $operacion = 'mat000xxxx';
	protected $evento = '2';
	protected $opcion_mesa = array();
	protected $opcion_comision = array();
	
	const TODA_COMISION = 'TC';
	const TODAS_SUBCOMISIONES = 'TS';

	//-------------------------------------------------------------------
	// RECIBIDOS
	//-------------------------------------------------------------------

	function get_mensajes_recibidos()
	{
		return catalogo::consultar(
			'mensajes',
			'get_mensajes_recibidos_vigentes',
			array('persona' => $this->persona)
		);
	}

	function evt__leer_mensaje($url)
	{
		$mensajes = $this->get_mensajes_recibidos();
		return catalogo::consultar(
			'mensajes',
			'leer_mensaje',
			array('persona' => $this->persona, 'mensaje' => $mensajes[$url]['mensaje'], 'enviado' => false)
		);
	}
	
	function get_personas_destinatarios_alumnos($mensaje)
	{
		return catalogo::consultar(
			'mensajes',
			'get_personas_destinatarios_alumnos',
			array('mensaje' => $mensaje)
		);
	}
	
	function get_anio_academico_mensaje($mensaje){
		return catalogo::consultar(
			'mensajes',
			'get_anio_academico_mensaje',
			array('mensaje' => $mensaje)
		);
	}

	function evt__leer_mensaje_enviado($url)
	{
		$mensajes = $this->get_mensajes_enviados($parametro);
		return catalogo::consultar(
			'mensajes',
			'leer_mensaje',
			array('persona' => $this->persona, 'mensaje' => $mensajes[$url]['mensaje'], 'enviado' => true)
		);
	}

	function get_datos_adjunto($url_anuncio, $url_adjunto)
	{
		$adjuntos = catalogo::consultar('mensajes', 'get_adjuntos');
		if (isset($adjuntos[$url_anuncio][$url_adjunto]))
			return $adjuntos[$url_anuncio][$url_adjunto];
		throw new error_guarani('No existe el adjunto solicitado.');
	}

	//-------------------------------------------------------------------
	// DOCENTE: ENVIADOS
	//-------------------------------------------------------------------

	function get_destinatarios($anioAcademico)
	{
		
		$datos['comisiones'] = catalogo::consultar(
			'docente',
			'lista_comisiones_docentes',
			array(
				'docente' => kernel::persona()->get_id_docente(),
				'anio_academico' => $anioAcademico
			)
		);

		$datos['mesas'] = catalogo::consultar(
			'docente',
			'lista_mesas',
			array(
				'docente' => kernel::persona()->get_id_docente(),
				'turno_examen' => null,
				'actividad'=> null,
				'ubicacion'=> null,
				'fecha_hasta'=> null,
				'fecha_desde'=> null,
				'anio_academico' => $anioAcademico,
				'mostrar_mesas_fuera_calendario' => \comunes::si,
				'mesas_con_inscriptos' => \comunes::si
			)
		);

		$nuevo = array();
		foreach ($datos['comisiones'] as $key => $dato) {
			if ($dato['cant_inscriptos_comision'] > 0) {
				$nuevo['comisiones'][$key] = $dato;	
			}
		}
		$nuevo['mesas'] = $datos['mesas'];
		$nuevo['alumnos'] = catalogo::consultar('alumno', 'get_cantidad_activos');

		return $nuevo;
	}
	
	function get_subcomisiones($comision)
	{
		$parametros = [
			'comision' => $comision,
			'docente' => kernel::persona()->get_id_docente(),
		];
		return catalogo::consultar('comisiones', 'get_subcomisiones_by_comision', $parametros);
	}
	
	function get_mensajes_enviados($parametros)
	{
                
                if(empty($parametros)){
                $parametros['ultimos'] = null;
                $parametros['fecha_hasta'] = null;
                $parametros['fecha_desde'] = null;
                }
                $parametros['persona']=$this->persona;
		return catalogo::consultar(
			'mensajes',
			'get_mensajes_enviados',
			$parametros
		);
	}

	function evt__guardar_mensaje(
		$alcance,
		$destino,
		$titulo,
		$cuerpo,
		$fecha_desde,
		$fecha_hasta,
		$enviar_por_mail,
		$enviar_por_twitter,
		$mensaje_twitter,
		$enviar_por_facebook,
		$activo,
		$persona_alta,
		$error_envio_mail,
		$archivos = array()
	)
	{
		kernel::db()->abrir_transaccion();
		try
		{
			$mensaje = catalogo::consultar(
				'mensajes',
				'guardar_mensaje',
				array(
					'alcance' => $alcance,
					'titulo' => $titulo,
					'cuerpo' => $cuerpo,
					'fecha_desde' => $fecha_desde,
					'fecha_hasta' => $fecha_hasta,
					'enviar_por_mail' => $enviar_por_mail,
					'enviar_por_twitter' => $enviar_por_twitter,
					'mensaje_twitter' => $mensaje_twitter,
					'enviar_por_facebook' => $enviar_por_facebook,
					'activo' => $activo,
					'persona_alta' => $persona_alta,
					'error_envio_mail' => $error_envio_mail
				)
			);

			switch($alcance)
			{
				//alumno
				case 8:
					catalogo::consultar('mensajes', 'guardar_destinatario_alumno', array(
						'mensaje' => $mensaje,
						'persona' => $destino,
					));
					break;
				//comision
				case 10:
					catalogo::consultar('mensajes', 'guardar_destinatario_alumnos_comision', array(
						'mensaje' => $mensaje,
						'comision' => $destino,
					));
					break;
				//mesa de examen
				case 11:
					catalogo::consultar('mensajes', 'guardar_destinatario_alumnos_mesa', array(
						'mensaje' => $mensaje,
						'mesa' => $destino,
					));
					break;
				default:
					throw new error_guarani('Tipo de anuncio desconocido: '.$tipo_anuncio.'.');
			}

			foreach($archivos as $archivo)
			{
				catalogo::consultar(
					'mensajes',
					'guardar_documento',
					array(
						'mensaje' => $mensaje,
						'path' => $archivo['path'],
						'nombre' => $archivo['nombre'],
						'tamanio' => $archivo['tamanio'],
						'extension' => $archivo['extension'],
						'tmp_name' => $archivo['tmp_name'],
					)
				);
			}
			kernel::db()->cerrar_transaccion();
		}
		catch(\Exception $e)
		{
			kernel::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	function get_comisiones($mensaje){
		return catalogo::consultar(
			'mensajes',
			'get_comisiones',
			array('mensaje' => $mensaje)
		);
	}
	
	function get_mesa($mensaje){
		return catalogo::consultar(
			'mensajes',
			'get_mesa',
			array('mensaje' => $mensaje)
		);
	}
	
	
}
?>