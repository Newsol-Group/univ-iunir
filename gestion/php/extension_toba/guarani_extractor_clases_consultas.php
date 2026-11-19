<?php
/**
 * Description of guarani_extractor_clases_consultas
 *
 */

class guarani_extractor_clases_consultas
{
	
	protected $path_salida;
	protected $archivo_salida;
	protected $solo_personalizados;
	
	function  __construct($puntos_montaje, $base)
	{
		$this->base = $base;
		$this->path_salida = $puntos_montaje['path_salida'];
		$this->archivo_salida = $puntos_montaje['archivo_salida'];
		if (isset($puntos_montaje['solo_personalizados'])) {
			$this->solo_personalizados = $puntos_montaje['solo_personalizados'];
		}
	}
	
	
	function generar()
	{
		$arreglo = $this->generar_arreglo($this->solo_personalizados);
		$this->generar_archivo($this->path_salida . '/' . $this->archivo_salida, $arreglo);
	}
	
	
	protected function generar_archivo($path, $contenido)
	{
		$nombre_clase = basename($path, '.php');
		$comentario = "/**\n * Esta clase fue y será generada automáticamente. NO EDITAR A MANO.\n * @ignore\n */";
		$arreglo = sprintf("\tstatic \$clases = array(\n%s\t);", $contenido);
		$metodo_consultor = "\tstatic function existe_clase(\$nombre)\n\t{\n\t\treturn isset(self::\$clases[\$nombre]);\n\t}\n";
		
		$metodo_consulta = "\tstatic function consulta(\$nombre)\n\t{\n\t\tif (static::existe_clase(\$nombre)) {\n" ;
		$metodo_consulta .= "\t\t\t\$clase = self::\$clases[\$nombre]['clase'];\n";
		$metodo_consulta .= "\t\t\t\$path = self::\$clases[\$nombre]['path'];\n";
		$metodo_consulta .= "\t\t\treturn array(\$clase, \$path);\n";
		$metodo_consulta .= "\t\t} else { \n";
		$metodo_consulta .= "\t\t\tthrow new siu\\errores\\error_guarani(\"No existe la clase: \$nombre pude ser que tenga que volver a generar la clase g3_clases_consulta con el comando generar_autoload\");\n"; // @TO-DO excepcion no existe la consulta
		$metodo_consulta .= "\t\t}\n";
		$metodo_consulta .= "\t}\n";
		
		$clase = sprintf("<?php\n%s\nclass %s \n{\n%s\n%s\n%s\n}\n?>", $comentario, $nombre_clase, $metodo_consultor, $metodo_consulta, $arreglo);
 		file_put_contents($path, $clase);
	}
	
	protected function generar_arreglo($solo_personalizados=false)
	{
		
		$sql_schema = "SELECT	valor 
					   FROM		par_configuraciones
					   WHERE	nombre = 'schema_toba'";
		
		$rs = $this->base->consultar_fila($sql_schema);
		$proyecto = toba::proyecto()->get_id();
		$schema = isset($rs['valor']) ? $rs['valor'] : 'desarrollo';
		$filtro = '';
		
		if ($solo_personalizados) {
			$filtro = 'AND  apex_consulta_php.punto_montaje <> 1';
		}
		
		$sql = "SELECT clase, archivo as path, archivo_clase, path_pm
				FROM $schema.apex_consulta_php 
				LEFT JOIN $schema.apex_puntos_montaje ON (apex_consulta_php.punto_montaje=apex_puntos_montaje.id 
														  AND apex_consulta_php.proyecto=apex_puntos_montaje.proyecto) 
				WHERE apex_consulta_php.proyecto = '$proyecto'
				$filtro
                ORDER BY archivo_clase";

		$rs = $this->base->consultar($sql);
		$clases = '';
		foreach ($rs as $r) {
			// TOBA guarda de este manera los metadatos
			$identificador = $r['clase'];
			$clase = $r['archivo_clase'];
			if ($r['path_pm'] != 'php') {
				$path = "../../pers_gestion/php/{$r['path']}";
			} else {
				$path = $r['path'];
			}
			$clases .= sprintf("\t\t'%s' => array('clase'=>'%s','path'=>'%s'),\n", $identificador, $clase, $path);
		}
		
		return $clases;
		
	}
	
	
}


?>