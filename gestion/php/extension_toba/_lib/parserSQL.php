<?php
class parserSQL 
{
	protected $entrada;
	protected $salida;
	protected $buffer;
	protected $manejador_interface=false;
	protected $objetos;
	protected $regexp = array();
    
	function __construct($entrada,$salida,$objetos,$manejador)
	{
		$this->parserSQL($entrada,$salida,$objetos,$manejador);
	}
    
    
	function parserSQL($entrada, $salida, $objetos, $manejador)
	{
		$this->entrada = $entrada;
		$this->salida = $salida;
		$this->objetos = $objetos;
		$this->manejador_interface = $manejador;

		//Expresiones Regulares
		
		// Delimitador de sentencias
		$this->regexp['sentencia'] = '/(?s).*?;/';
		//Sentencias a obviar
		$this->regexp['obviar'] = '(.*(DROP|REVOKE).*)sim';
		//Sentencia de creación de Tabla
		$this->regexp['tablas'] = "(( *Create +table *)(.*?)(\\(.*?;))si";
		//Sentencia de creación de Tipo
		$this->regexp['tipos'] = '';
		//Sentencia de creación de Secuencia
		$this->regexp['secuencias'] = "(( *CREATE +SEQUENCE +)(.*?)( +START +)(.*?)(.+))si";
		//Sentencia de creación de Constraint
		$this->regexp['ck'] = "(( *Alter +table +)(.*?)( *add +)( *constraint *)(.*?)( +check *)(\()(.+[\(\), '.]+ *)(\) *;))sim";
		//Sentencia de creación de Primary Key
		$this->regexp['pk'] = "(( *Alter +table +)(.*?)(add )(.*?)(constraint *)(.*?)( *primary +key *)(\\(.*?\\))(.*?))si";
		//Sentencia de creación de Foreign Key
		$this->regexp['fk'] = "((Alter +table )(.*?)(add )( *constraint *)*(.*?)( foreign key)(.*?)(\\(.*?\\))(.*?)(\\)))si";
		//Sentencia de creación de Función
		$this->regexp['funciones'] = "";
		//Sentencia de creación de Índice
		$this->regexp['indices'] = "(((Create +((unique ))*index )(.*?)( on )(.*?) (.*?)(\\((.*?)\\)));)si";
		//Sentencia de creación de Store Procedure
		$this->regexp['procedures'] = "";
		//Sentencia de creación de Trigger
		$this->regexp['triggers'] = "";
		//Sentencia de creación de Vista
		$this->regexp['vistas'] = "";
		//Sentencia de creación de Permiso
		$this->regexp['permisos'] = "((Grant +)(.*?)( +ON +)(.*?)( +TO +)(.+))si";
		//Sentencia de creación de Valores de Secuencias
		$this->regexp['valores_secuencias'] = "(( *SELECT +setval *)(\( *') *(.+)(' *, *)(.+)( *\) *;))sim";


		$this->getContents();
		$this->parseCompleto();

	}
    
	/*Mete todo lo del archivo de entrada en un StringBuffer*/
	function getContents()
	{
		try{   
			$this->buffer = file_get_contents($this->entrada);
			
		}catch(Exception $ioe){
			$this->msg("Error de IOE0: ".$ioe->getMessage());
		}
	}    

    /*parsea el StringBuffer por sentencias y llama al parser de sentencias para parsear y grabar cada una*/
    function parseCompleto()
    {
		//Recupera las sentencias;

    	$p_Sentencia = $this->regexp['sentencia'];
    	preg_match_all($p_Sentencia, $this->buffer, $matches);

    	for($i=0;$i<guarani_arreglos::count($matches[0]);$i++){
    		$this->parseSentencia($matches[0][$i]);
    	}

    }

	/* Parse las sentencias, según sean tablas, fk, pk, etc. y las guarda en los archivos y carpetas respectivas*/
    function parseSentencia($sentencia)
    {
    	//Veo si no es un Drop o Revoke
    	$i = preg_match_all($this->regexp['obviar'],$sentencia,$match);
 		if ($i) return;
    	
		foreach ($this->objetos as $tipo => $objeto) {
			if ($objeto['parsear']) {
				$regexp = false;
				$posicion_nombre = -1;
				$metodo = 'grabaObjeto';
				$prefijo = '';
				$sufijo = '';
				if (isset($objeto['regexp'])) $regexp = $objeto['regexp'];
				if (isset($objeto['re_pos_nombre'])) $posicion_nombre = $objeto['re_pos_nombre'];
				if (isset($objeto['prefijo'])) $prefijo = $objeto['prefijo'];
				if (isset($objeto['sufijo'])) $sufijo = $objeto['sufijo'];
				switch(strtolower($tipo)) {
		        	case 'tablas':
		        		if (! $regexp) $regexp = $this->regexp[$tipo];
    	
				    	$i = preg_match_all($regexp,$sentencia,$match);
				    	if($i){
				    		$sentenciaM = $match[0][0];
				    		$this->grabaTabla(trim($match[2][0]),$sentenciaM,$tipo);  	
				    		return;	
				    	}
				    	break;
		        	case 'tipos':
						if (! $regexp) $regexp = "";
						if (! $posicion_nombre) $posicion_nombre = -1;
						break;
					case 'secuencias':
		        		if (! $regexp) {
		        				$regexp = $this->regexp[$tipo];
								$posicion_nombre = 2;
						}		
						break;
					case 'ck':
		        		if (! $regexp) {
		        			$regexp = $this->regexp[$tipo];
							$posicion_nombre = 5;		        		
						}
						break;
					case 'fk':
		        		if (! $regexp) {
		        			$regexp = $this->regexp[$tipo];
							$posicion_nombre = 5;
						}	
						break;
					case 'funciones':
		        		if (! $regexp) $regexp = $this->regexp[$tipo];
						if (! $posicion_nombre) $posicion_nombre = -1;
						break;
					case 'indices':
		        		if (! $regexp) {
		        			$regexp = $this->regexp[$tipo];
		        			$posicion_nombre = 5;
		        		}
		        		break;
					case 'pk':
						if (! $regexp) {
							$regexp = $this->regexp[$tipo];
							$posicion_nombre = 6;
						}	
						break;
					case 'procedures':
		        		if (! $regexp) $regexp = $this->regexp[$tipo];
						if (! $posicion_nombre) $posicion_nombre = -1;		        		
						break;
					case 'triggers':
		        		if (! $regexp) $regexp = $this->regexp[$tipo];
						if (! $posicion_nombre) $posicion_nombre = -1;		        		
						break;
					case 'vistas':
		        		if (! $regexp) $regexp = $this->regexp[$tipo];
						if (! $posicion_nombre) $posicion_nombre = -1;		        		
						break;
					case 'permisos':
		        		if (! $regexp) {
		        			$regexp = $this->regexp[$tipo];
		        			$posicion_nombre = 4;
		        		}	
		        		if ($prefijo == "") $prefijo = 'grants_';
		        		break;
					case 'valores_secuencias':
		        		if (! $regexp) {
		        			$regexp = $this->regexp[$tipo];
							$posicion_nombre = 3;
						}
						if ($prefijo == "") $prefijo = 'setval_';
						$metodo = 'grabaSetVal';
						break;
					default:
		        		break;
				}//End Switch

				if (! $regexp || $posicion_nombre < 0) continue;

				$res = preg_match_all($regexp,$sentencia,$match);
				if($res){
  					$this->$metodo($prefijo.trim($match[$posicion_nombre][0]).$sufijo,$sentencia,$tipo);
  					return;
				}
				
			}// End if cargar
		}//End for
				
		//Veo si no es un Drop o Revoke
   		$this->msg("La sentencia: ".$sentencia." no macheó nada!");
	}
    
 	/* Graba en los archivos correspondientes la sentencia */
	function grabaObjeto($objeto, $sentencia, $tipo)
	{
		$archivo = $this->salida . '/' . $this->objetos[$tipo]['dir'] . '/' . $objeto . ".sql";
        
		// si existe lo borro
		try{
			if(file_exists($archivo)){
				//$f = fopen($archivo,'a'); Antes se lo abría para hacer append
				unlink($archivo);
			}
			
			//Creo el archivo y lo abro para escritura
			$f = fopen($archivo,'x');
			
			fwrite($f,$sentencia);
			fclose($f);
		}catch(Exception $ioe){
			$this->msg($ioe->getMessage());
		}
		
		$this->msg('.');
	}

	function grabaSetVal($objeto, $sentencia, $tipo)
	{
		//Si no tiene el Coalesce el select max(), se lo agrego
		$regexp = "((.+)(select *)(max *\(.*\))( +from.*))sim";
		$i = preg_match_all($regexp,$sentencia,$match);
		
		if($i){
			$sentencia = $match[1][0].$match[2][0].' COALESCE('.$match[3][0].', 1) '.$match[4][0];
		}		
		
		
		$this->grabaObjeto($objeto, $sentencia, $tipo);
	}


	/* retoca la sentencia para crear la tabla en los casos que haya un tipo */
	/* rowID, generando una secuencia y asignando al campo por defecto el valor de la misma*/
	function grabaTabla($tabla, $sentencia, $tipo, $genera_log=false)
	{
		$p_sq = "((\w*?)( +(Serial|Integer))(NOT +NULL))si";
		$i = preg_match_all($p_sq,$sentencia,$match);
		$seq_name = $tabla.'_seq';
		$setval_name = 'setval_'.$seq_name;
		if($i){			
			$sentenciaS = "CREATE SEQUENCE ".$seq_name." ;";
			$this->grabaObjeto($seq_name,$sentenciaS,'secuencias');

			$sentencia = str_replace("Serial NOT NULL","INTEGER NOT NULL DEFAULT nextval('\"$seq_name\"'::text)",$sentencia);

			$sentenciaUs = "SELECT setval('$seq_name',(Select coalesce(max({$match[1][0]}), 1) FROM $tabla));\n";
			
			$this->grabaObjeto($setval_name, $sentenciaUs, 'valores_secuencias');	
		}
		$this->grabaObjeto($tabla,$sentencia, $tipo);


		if (! $genera_log) return;
		
		/*********************
		// Genera tabla de log, secuencia y actualización de secuencia
		/*********************/
		
		// Agrega el prefijo al nombre de la tabla y la secuencia
		$tabla_log = "his_".$tabla;
		$sq_tabla_log = $tabla_log."_seq";
		$sentencia_sq_tabla_log = "CREATE SEQUENCE ".$sq_tabla_log.";";
		
		//  reemplaza el nombre de la tabla original por el nuevo 
		$sentencia_tabla_log = $sentencia = str_replace("Create table $tabla","Create table $tabla_log",$sentencia);
		
		// Saca la secuencia del id de la tabla original
		$sentencia_tabla_log = str_replace("INTEGER NOT NULL DEFAULT nextval('\"sq_$tabla\"'::text)","INTEGER NOT NULL",$sentencia_tabla_log);

		// Saca el primary key de la tabla original, agrega los campos nuevos y lo pone como primary key
		$p_pk = "(primary key \\(.*)";
		$replacement = "log_id INTEGER NOT NULL DEFAULT nextval('\"sq_$tabla_log\"'::text),\n log_usuario INTEGER,\n log_area INTEGER, \n log_fecha TIMESTAMP,\n primary key (log_id)";
		$sentencia_tabla_log = preg_replace($p_pk, $replacement, $sentencia_tabla_log);

		// Graba la tabla
		$this->graba($tabla_log,$sentencia_tabla_log,'T');		
		
		// Graba la secuencia
		$this->graba($tabla_log,$sentencia_sq_tabla_log,'S');

		// Genera la sentencia de actualización de secuencia y la graba.
		$sentencia_actualiza_secuencia_log = "SELECT setval('sq_$tabla_log',(Select coalesce(max(log_id), 1) FROM $tabla_log));\n";
		$this->graba($tabla_log, $sentencia_actualiza_secuencia_log, 'U');
	}

	//Envia un mensaje a la consola 
	function msg($msg,$nueva_linea=true){
		if ($this->manejador_interface) {
			if ($msg != '.') {
				$this->manejador_interface->mensaje($msg,$nueva_linea);
			} else {	
				$this->manejador_interface->progreso_avanzar();
			}	
		} else {
			echo $msg;
		}	
		return;
	}
	
}
?>