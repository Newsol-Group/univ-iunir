<?php
class guarani_menu_2 extends toba_menu {
	protected $n=0;
	protected $item;
	
	function __construct() {
		parent::__construct();
		$this->item = toba::solicitud()->get_id_operacion();
		$this->set_codigos();
	}
	
	function set_codigos() {
		$db = toba_proyecto_db::get_db();
		$proyecto = $db->quote(toba_proyecto::get_id());
		$sql = "SELECT DISTINCT	i.item as item,
				i.parametro_a as codigo
				FROM 	apex_item i
				where	i.proyecto=$proyecto";
		$cod_items = $db->consultar($sql);
		//echo "<pre>",print_r($cod_items,true),print_r($this->items,true),"</pre>";
		foreach($this->items as $k=>$item) {
			foreach($cod_items as $cod_item) {
				if($item['item'] == $cod_item['item']) {
					$this->items[$k]['codigo']=$cod_item['codigo'];
					break;
				}
			}			
		}
	}
	
	function plantilla_css()
	{
		return "guarani_menu_2";
	}
	
	function mostrar() {
		$ops = array();
		for ($i=0;$i<guarani_arreglos::count($this->items);$i++) {
			//--- Se recorre el primer nivel
			if ($this->items[ $i ]['es_primer_nivel']) {
				$ops['raiz'][] = $this->items[$i];
			}
		}
		$path = null;
        if(isset($ops['raiz'])){
            foreach ($ops['raiz'] as $hijo) {
                if($hijo['item']==$this->item) {
                    $path = ' &raquo;';
                    break;
                }
                if($tpath = $this->set_path_item($hijo, ' &raquo; '.$hijo['nombre'].' &raquo;')) {
                    $path = $tpath;
                    break;
                }
            }
        }
		$niv = $this->crear_niveles($ops);
		echo toba_js::abrir();
		echo "
			function showMenu(event) {
				var menu = document.getElementById('ventana_menu');
				if (menu.style.display == 'block') {
					menu.style.display='none'; 
				} else {
					menu_slide(0);
					document.getElementById('buscar_text').value = '';
					cambiar_modo_busqueda(document.getElementById('toogle_modo'), true);
					menu.style.display='block';
                                        document.getElementById('buscar_text').focus();
				}
				if(event.stopPropagation) {
					event.stopPropagation();
				} else {
					event.cancelBubble = true;
				}
			}
			
			if(document.addEventListener) {
				document.addEventListener('click', chkClick, false);
			} else {
				document.attachEvent('onclick', chkClick);
			}
			
			function chkClick(event) {
				var target = event.target?event.target:event.srcElement;
				if(target.id != 'menu_img' && target.parentNode.id != 'menu_img') {
					var en_menu = false;
					var menu = document.getElementById('ventana_menu');
					//alert(event.target?event.target:event.srcElement); 
					var dvs = menu.getElementsByTagName(target.tagName);
					for(var i=0; i<dvs.length; i++) {
						if(dvs[i] === target) {
							en_menu = true;
							break;
						}
					}
					if(!en_menu) {
						menu.style.display='none';
					}
				}
			}
			
			function menu_slide(n, op, sn) {
				document.getElementById('menu_buscar').style.display='none';
				document.getElementById('menu_slider').style.display='block';
				if(op != null && op != '') {
				    var nv = document.getElementById('nivel_menu_'+n);
				    if(nv.getElementsByClassName) {
						var ops = nv.getElementsByClassName('toba_opciones_menu');
					} else {
						var ops = new Array();
						var dvs = nv.getElementsByTagName('div');
						for (var i=0; i<dvs.length; i++) {
							if(dvs[i].className == 'toba_opciones_menu') {
								ops.push(dvs[i]);
							}
						} 
					}
					for(var i=0; i<ops.length; i++) {
						if(ops[i].id == op) {
							ops[i].style.display='';
						} else {
							ops[i].style.display='none';
						}
					}
				}
				var step = document.getElementById('path_step_'+n);
				step.style.display='block';
				var spns;
				if(step.previousSibling) {
					spns = step.previousSibling.getElementsByTagName('span'); 
					spns[1].style.display='inline';
				}
				
				spns = step.getElementsByTagName('span');
				spns[1].style.display='none';

				if(sn != null && sn != '') {
					spns[0].innerHTML = sn;
				}
				
				var step_found = false;
				var w = 0;
				for(var i=0; i<step.parentNode.childNodes.length; i++) {
					if (step.parentNode.childNodes[i] === step) {
						step_found = true;
						w += step.parentNode.childNodes[i].clientWidth+1;
					} else if(step_found) {
						step.parentNode.childNodes[i].style.display='none';
					} else {
						w += step.parentNode.childNodes[i].clientWidth+1;
					}
				}
				
				if(w > 400) {
					document.getElementById('path_slider').style.width=w+'px';
				} else {
					document.getElementById('path_slider').style.width='400px';
				}
				var path = document.getElementById('path_menu');
				path.scrollLeft=path.scrollWidth;
				
				document.getElementById('menu_slider').style.left='-'+(n*400)+'px';
			}
			
			function buscar_menu(srchstr) {
				var msch = document.getElementById('menu_buscar');
				if(srchstr == '') {
					msch.style.display='none';
					document.getElementById('menu_slider').style.display='block';
				} else {
					var n = true;
					var c = true;
					if(document.getElementById('busqueda_filtro').style.display=='block') {
						n = document.getElementById('busc_nom').checked; 
						c = document.getElementById('busc_cod').checked;
					}
					var elems = msch.getElementsByTagName('div');
					var exp = new RegExp(normalizar_chars(srchstr),'i');
					for(var i=0; i<elems.length; i++) {
						if(	(n && elems[i].lastChild.nodeType == 3 && exp.test(normalizar_chars(elems[i].lastChild.nodeValue))) || 
								(c && hasAttribute(elems[i],'codigo') && exp.test(normalizar_chars(getAttribute(elems[i],'codigo'))))) {
							elems[i].style.display='block';
						} else {
							elems[i].style.display='none';
						}
					}
					document.getElementById('menu_slider').style.display='none';
					msch.style.display='block';
				}
			}
			
			function hasAttribute(elem, att) {
				if(elem.hasAttribute) {
					return elem.hasAttribute(att);
				} else {
					for(var i=0; i<elem.attributes.length; i++) {
						if(elem.attributes[i].name == att) {
							return true;
						}
					}
					return false; 
				}
			}
			
			function getAttribute(elem, att) {
				if(elem.getAttribute) {
					return elem.getAttribute(att);
				} else {
					for(var i=0; i<elem.attributes.length; i++) {
						if(elem.attributes[i].name == att) {
							return elem.attributes[i].nodeValue;
						}
					} 
					return '';
				}
			}
			
			function cambiar_modo_busqueda(img, reset) {
				var bf = document.getElementById('busqueda_filtro');
				var vm = document.getElementById('ventana_menu');
				var sm = document.getElementById('search_menu');
				if(!reset && bf.style.display == 'none') {
					bf.style.display = 'block';
					vm.style.height = '444px';
					sm.style.height = '46px';
					img.src = '".toba_recurso::imagen_proyecto('sentido_asc_menu.png')."';
				} else {
					bf.style.display = 'none';
					vm.style.height = '420px';
					sm.style.height = '23px';
					img.src = '".toba_recurso::imagen_proyecto('sentido_des_menu.png')."';
				}
				buscar_menu(document.getElementById('buscar_text').value);
			}
			
			function normalizar_chars(input) {
				return input.replace(/ñ/ig,'n').replace(/á/ig,'a').replace(/é/ig,'e').replace(/í/ig,'i').replace(/ó/ig,'o').replace(/ú/ig,'u').replace(/ü/ig,'u').replace(/^[ ]*|[ ]*$/g,'');
			}
			
			document.getElementById('barra_path_operacion').innerHTML = '".$path."';
		";
		if (toba::memoria()->get_celda_memoria_actual_id() != 'paralela') {
			echo '
				function on_menu_set_popup_on(e) {
				   	var id = (window.event) ? event.keyCode : e.keyCode;
					if (id == 16) {
						toba._menu_popup = true;
					}
				}
				function on_menu_set_popup_off(e) {
				   	var id = (window.event) ? event.keyCode : e.keyCode;
					if (id == 16) {
						toba._menu_popup = false;
					}			
				}
				agregarEvento(document, "keyup", on_menu_set_popup_off);
				agregarEvento(document, "keydown", on_menu_set_popup_on);
			';
		}
		echo toba_js::cerrar();
		echo "<div id='ventana_menu' class='toba_ventana_menu'>";
		echo "<div id='path_menu' class='toba_path_menu'><div id='path_slider'>{$niv['stp']}</div></div>";
		echo "<div class='toba_slider_menu' id='menu_slider' style='width: ".(400*($this->n+1))."px'>";
		echo $niv['niv'];
		echo "</div>";
		echo "<div class='toba_buscar_menu' id='menu_buscar'>".$niv['sch']."</div>";
		//echo "<div id='search_menu' class='toba_buscar_barra_menu'><span>Buscar: </span><input id='buscar_text' type='text' onKeyup='buscar_menu(this.value)'/> <img src='".toba_recurso::imagen_proyecto('clear_left.png')."' onClick='document.getElementById(\"buscar_text\").value=\"\"; buscar_menu(\"\");'/></div>";
		echo "<div id='search_menu' class='toba_buscar_barra_menu'>
					<div class='buscar_menu_2'>
						<img id='toogle_modo' src='".toba_recurso::imagen_proyecto('sentido_des_menu.png')."' onClick='cambiar_modo_busqueda(this)'/>
						<span>Buscar: </span>
						<input id='buscar_text' type='text' onKeyup='buscar_menu(this.value)'/> 
						<img src='".toba_recurso::imagen_proyecto('clear_left.png')."' onClick='document.getElementById(\"buscar_text\").value=\"\"; buscar_menu(\"\");'/>
					</div>
					<div id='busqueda_filtro' style='display: none; padding-left: 10px'>
						<input id='busc_cod' name='busc_cod_nom' type='radio' value='cod' onClick='buscar_menu(document.getElementById(\"buscar_text\").value)' checked='true'/> Código									 
						<input id='busc_nom' name='busc_cod_nom' type='radio' value='nom' onClick='buscar_menu(document.getElementById(\"buscar_text\").value)'/> Nombre
					</div>
				</div>";
		echo "</div>";
	}
	
	protected function crear_niveles($ops) {
		$hijos=array();
		$ret['sch'] = '';
		$ret['niv'] = "<div class='toba_nivel_menu' id='nivel_menu_$this->n'>";
		$ret['stp'] = "<div class='toba_step_menu' id='path_step_$this->n' onClick='menu_slide(".($this->n).")'><span class='toba_next_step_menu' id='step_text_$this->n'".($this->n==0?'>Raiz':'>')."</span><span id='next_step_$this->n'>&raquo;</span></div>";
		foreach($ops as $k=>$op) {
			$ret['niv'] .= "<div class='toba_opciones_menu' id='opcion_menu_$k'>";
			if($this->n > 0) $ret['niv'] .= "<div style='background-image: url(".toba_recurso::imagen_proyecto('guarani_menu_volver.gif')."); background-position: 0% 50%; background-repeat: no-repeat;' class='toba_volver_menu' onClick='menu_slide(".($this->n-1).")'></div>";
			$ret['niv'] .= "<div class='toba_el_cont_menu'>";
			foreach($op as $elem) {
				$act = '';
				$style='';
				if($elem['carpeta']) {
					//Imagen default si el elemento es carpeta
					$url_img = toba_recurso::imagen_proyecto('carpeta.png');
					$hijos[$elem['item']]=$this->get_hijos($elem['item']);
					$act = "menu_slide(".($this->n+1).", \"opcion_menu_".$elem['item']."\", \"".$elem['nombre']."\")";
					$style = "style='background-image: url(".toba_recurso::imagen_toba('nucleo/menu_nodo_css.gif')."); background-position: 100% 50%; background-repeat: no-repeat;'";
				} else {
					//Imagen default si el elemento no es carpeta
					$url_img = toba_recurso::imagen_proyecto('item.png');
					if (isset($elem['js'])) {
						$act .= $elem['js']; 
					} else {
						$act .= "return toba.ir_a_operacion(\"{$elem['proyecto']}\", \"{$elem['item']}\", false)";
					}
				}
				
				$ret['niv'] .= "<div class='toba_elemento_menu' id='elemento_menu_{$elem['item']}' onClick='$act' $style>";
				if (isset($elem['imagen'])) {
					$url_img = toba_recurso::imagen_de_origen($elem['imagen'], $elem['imagen_recurso_origen']);
				} 	
				$ret['niv'] .= "<img src='$url_img' border=0 alt='' /> ".$elem['nombre'];
				$ret['niv'] .= "</div>";
				if(!$elem['carpeta']) {
					$ret['sch'] .= "<div class='toba_elemento_menu' id='elemento_buscar_menu_{$elem['item']}' onClick='$act' codigo='".(isset($elem['codigo'])?$elem['codigo']:'')."'>";
					$ret['sch'] .= "<img src='$url_img' border=0 alt='' /> ".$elem['nombre'];
					$ret['sch'] .= "</div>";
				}
			}
			$ret['niv'] .= "</div>";
			$ret['niv'] .= "</div>";
		}
		$ret['niv'] .= "</div>";
		if($hijos) {
			$this->n++;
			$tmp = $this->crear_niveles($hijos);
			$ret['niv'] .= $tmp['niv'];
			$ret['stp'] .= $tmp['stp'];
			$ret['sch'] .= $tmp['sch'];
		}
		return $ret;
	}
	
	protected function get_hijos($padre)
	{
		$hijos = array();
		for ($i=0;$i<guarani_arreglos::count($this->items);$i++) {
			if ($this->items[ $i ]['padre'] == $padre)	{
				$hijos[] = $this->items[ $i ];
			}
		}
		return $hijos;
	}
	
	protected function set_path_item($padre, $path) {
		$hijos = $this->get_hijos($padre['item']);
		foreach ($hijos as $hijo) {
			if($hijo['item']==$this->item) {
				return $path;
			}
			if($tpath = $this->set_path_item($hijo, $path.' '.$hijo['nombre'].' &raquo;')) {
				return $tpath;
			}
		}	
		return null;	
	}
}
?>