<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Plataformas Virtuales
 * @subpackage Plataformas Virtuales
 */
class act_plataformas_virtuales_nucleo
{
	
	function insertar_usuario_pv($plataforma, $persona, $id_usuario_externo)
	{
		$sql = "INSERT INTO int_pv_usuarios
				(
					plataforma,
					persona,
					id_usuario_externo
				)
				VALUES
				(
					$plataforma,
					$persona,
					$id_usuario_externo
				)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó en int_pv_usuarios con éxito. ************');
	}
	
	function insertar_usuarios_pv_masivo($usuarios)
	{
		
		$valores = "";
		$num_items = guarani_arreglos::count($usuarios);
		$i = 0;
		foreach ($usuarios as $key => $usuario) {
			$valores .= "(".implode(", ", $usuario).")"; 
			// Si no es el último item agrego una coma
			if(++$i != $num_items) {
				$valores .= ", "; 
			}
		}
		
		$sql = "INSERT INTO int_pv_usuarios 
				(
					plataforma,
					persona,
					id_usuario_externo
				) 
				VALUES
				$valores";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó en int_pv_usuarios con éxito. ************');
	}
	
	function borrar_usuarios_pv_masivo($plataforma, $usuarios)
	{
		
		$valores = implode(", ", $usuarios);
		
		$sql = "DELETE FROM int_pv_usuarios 
				WHERE plataforma = $plataforma 
				AND id_usuario_externo IN ($valores)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se borró en int_pv_usuarios con éxito. ************');
	}
	
	function insertar_curso_pv($plataforma, $id_curso_externo)
	{
		
		$sql = "INSERT INTO int_pv_cursos
				(
					plataforma,
					nombre,
					id_curso_externo
				)
				VALUES
				(
					$plataforma,
					'No aplica',
					$id_curso_externo
				)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó en int_pv_cursos con éxito. ************');
		return toba::db()->ultimo_insert_id('int_pv_cursos_seq');
	}
	
	function insertar_curso_comision_pv($curso, $comision)
	{
		
		$sql = "INSERT INTO int_pv_cursos_comisiones
				(
					curso,
					comision
				)
				VALUES
				(
					$curso,
					$comision
				)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó en int_pv_cursos_comisiones con éxito. ************');
	}

	function insertar_grupo_curso_comision_pv($grupo, $comision)
	{
		
		$sql = "UPDATE int_pv_cursos_comisiones SET grupo = $grupo where comision =$comision";
			
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó en int_pv_cursos_comisiones con Éxito. ************');
	}


	function insertar_curso_llamado_mesa_pv($curso, $llamado_mesa)
	{
		$curso = toba::db()->quote($curso);
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$sql = "INSERT INTO int_pv_cursos_llamados_mesa (curso, llamado_mesa)
				VALUES ($curso, $llamado_mesa)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó en int_pv_cursos_llamados_mesa con éxito. ************');
	}

	function borrar_curso_comision_pv($curso)
	{
		try {
			
			toba::db()->abrir_transaccion();
			
			$sql = "DELETE FROM int_pv_cursos_comisiones WHERE curso = $curso";
			guarani_db::ejecutar($sql);
			
			$sql = "DELETE FROM int_pv_cursos WHERE curso = $curso";
			guarani_db::ejecutar($sql);
			
			toba::db()->cerrar_transaccion();
			
			toba::logger()->info('************ Se eliminó en int_pv_cursos_comisiones y int_pv_cursos con éxito. ************');
		
		} catch (Exception $e) {
			
			toba::db()->abortar_transaccion();
			toba::logger()->error('************ Falló eliminación en int_pv_cursos_comisiones e int_pv_cursos. ************');
			toba::logger()->error("Archivo: '".$e->getFile()."', Línea: ".$e->getLine().", Mensaje: ".$e->getMessage());
		}
	}

	function borrar_curso_llamado_mesa_pv($curso)
	{
		try {
			toba::db()->abrir_transaccion();
			$sql = "DELETE FROM int_pv_cursos_llamados_mesa WHERE curso = $curso";
			guarani_db::ejecutar($sql);
			$sql = "DELETE FROM int_pv_cursos WHERE curso = $curso";
			guarani_db::ejecutar($sql);
			toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se eliminó en int_pv_cursos_llamados_mesa y int_pv_cursos con éxito. ************');
		} catch (Exception $e) {
			toba::db()->abortar_transaccion();
			toba::logger()->error('************ Falló eliminación en int_pv_cursos_llamados_mesa e int_pv_cursos. ************');
			toba::logger()->error("Archivo: '".$e->getFile()."', Línea: ".$e->getLine().", Mensaje: ".$e->getMessage());
		}
	}
	
	function borrar_cursos_pv_masivo($plataforma, $ids_curso_externo)
	{	
		try {
			
			$valores = implode(", ", $ids_curso_externo);
			
			toba::db()->abrir_transaccion();
			
			$sql = "DELETE FROM int_pv_cursos_comisiones WHERE curso IN (SELECT curso FROM int_pv_cursos WHERE plataforma = $plataforma AND id_curso_externo IN ($valores))";
			guarani_db::ejecutar($sql);
			
			$sql = "DELETE FROM int_pv_cursos_llamados_mesa WHERE curso IN (SELECT curso FROM int_pv_cursos WHERE plataforma = $plataforma AND id_curso_externo IN ($valores))";
			guarani_db::ejecutar($sql);
			
			$sql = "DELETE FROM int_pv_cursos WHERE plataforma = $plataforma AND id_curso_externo IN ($valores)";
			guarani_db::ejecutar($sql);
			
			toba::db()->cerrar_transaccion();
			
			toba::logger()->info('************ Se eliminó en int_pv_cursos_comisiones, int_pv_cursos_llamados_mesa e int_pv_cursos con éxito. ************');
		
		} catch (Exception $e) {
			
			toba::db()->abortar_transaccion();
			toba::logger()->error('************ Falló eliminación en int_pv_cursos_comisiones, int_pv_cursos_llamados_mesa e int_pv_cursos. ************');
			toba::logger()->error("Archivo: '".$e->getFile()."', Línea: ".$e->getLine().", Mensaje: ".$e->getMessage());
		}
	}
}