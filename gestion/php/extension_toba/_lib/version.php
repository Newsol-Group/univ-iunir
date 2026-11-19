<?php
class version
{
	protected $url;
	protected $salida;
	protected $buffer;
    
	function __construct($salida,$url)
	{
		$this->version($salida,$url);
	}
    
	function version($salida,$url)
	{
		$this->url = $url;
		$this->salida = $salida;
		$this->parseCompleto();
	}
    
    function parseCompleto()
    {

		//arma archivo de secuencias
		$comando = "svn list ".$this->url."/sql/central/secuencias";
		$output = exec($comando,$secuencias);

		$archivo = $this->salida.'/01 - Secuencias.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($secuencias as $archivo_secuencia){
			$comando = "svn cat ".$this->url."/sql/central/secuencias/".$archivo_secuencia;
			$secuencia_renglones = null;
			$output = exec($comando,$secuencia_renglones);
			$secuencia = implode("\n",$secuencia_renglones);
			$secuencia = $secuencia."\n\n";
			fwrite($f,$secuencia);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}

		//arma archivo de tablas
		$comando = "svn list ".$this->url."/sql/central/tablas";
		$tablas = null;
		$output = exec($comando,$tablas);

		$archivo = $this->salida.'/02 - Tablas.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($tablas as $archivo_tabla){
			$comando = "svn cat ".$this->url."/sql/central/tablas/".$archivo_tabla;
			$tabla_renglones = null;
			$output = exec($comando,$tabla_renglones);
			$tabla = implode("\n",$tabla_renglones);
			$tabla = $tabla."\n\n";
			fwrite($f,$tabla);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}

		//arma archivo de pk
		$comando = "svn list ".$this->url."/sql/central/pk";
		$output = exec($comando,$pks);

		$archivo = $this->salida.'/03 - PK.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($pks as $archivo_pk){
			$comando = "svn cat ".$this->url."/sql/central/pk/".$archivo_pk;
			$pk_renglones = null;
			$output = exec($comando,$pk_renglones);
			$pk = implode("\n",$pk_renglones);
			$pk = $pk."\n\n";
			fwrite($f,$pk);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}

		//arma archivo de datos
		$comando = "svn list ".$this->url."/sql/central/datos_SIU";
		$datos = null;
		$output = exec($comando,$datos);

		$archivo = $this->salida.'/04 - Datos.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($datos as $archivo_dato){
			$comando = "svn cat ".$this->url."/sql/central/datos_SIU/".$archivo_dato;
			$dato_renglones = null;
			$output = exec($comando,$dato_renglones);
			$fk = implode("\n",$dato_renglones);
			/*$p_Sentencia = "(COPY (.*\n)*\\\.*\n)";
			preg_match_all($p_Sentencia, $dato, &$matches);
			print_r($matches[0][0]);
			fwrite($f,$matches[0][0]);
			fwrite($f,"\n");*/
			print_r($fk);
			$fk = $fk."\n\n";
			fwrite($f,$fk);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}

		//arma archivo de fk
		$comando = "svn list ".$this->url."/sql/central/fk";
		$fks = null;
		$output = exec($comando,$fks);

		$archivo = $this->salida.'/05 - FK.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($fks as $archivo_fk){
			$comando = "svn cat ".$this->url."/sql/central/fk/".$archivo_fk;
			$fk_renglones = null;
			$output = exec($comando,$fk_renglones);
			$fk = implode("\n",$fk_renglones);
			$fk = $fk."\n\n";
			fwrite($f,$fk);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}

		//arma archivo de indices
		$comando = "svn list ".$this->url."/sql/central/index";
		$indices = null;
		$output = exec($comando,$indices);

		$archivo = $this->salida.'/06 - Index.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($indices as $archivo_index){
			$comando = "svn cat ".$this->url."/sql/central/index/".$archivo_index;
			$index_renglones = null;
			$output = exec($comando,$index_renglones);
			$index = implode("\n",$index_renglones);
			$index = $index."\n\n";
			fwrite($f,$index);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}

		//arma archivo de actualiza secuencias
		$comando = "svn list ".$this->url."/sql/central/act_secuencias";
		$act_secuencias = null;
		$output = exec($comando,$act_secuencias);

		$archivo = $this->salida.'/07 - Actualiza_secuencias.sql';
		try{
			if(file_exists($archivo)){
				$f = fopen($archivo,'w');
			}else{			
				$f = fopen($archivo,'x');
			}

		foreach($act_secuencias as $archivo_act_secuencia){
			$comando = "svn cat ".$this->url."/sql/central/act_secuencias/".$archivo_act_secuencia;
			$act_secuencia_renglones = null;
			$output = exec($comando,$act_secuencia_renglones);
			$act_secuencia = implode("\n",$act_secuencia_renglones);
			$act_secuencia = $act_secuencia."\n";
			fwrite($f,$act_secuencia);
		}
			fclose($f);
		}catch(Exception $ioe){
			echo $ioe->getMessage();
		}
    }
}
?>