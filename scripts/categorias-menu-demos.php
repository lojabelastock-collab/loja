<?php
/**
 * Bela Stock: configura categorias, menu e demonstracoes com imagens reais.
 * Executar com wp eval-file sob o usuario do site.
 */
if (!defined('ABSPATH') || !defined('WP_CLI')) exit(1);
if (!class_exists('WooCommerce')) WP_CLI::error('WooCommerce necessario');
$categories = [
  'Camisetas' => ['Masculino','Feminino','Infantil','Unissex','Manga Curta','Manga Longa','Oversized','Cropped'],
  'Bonés' => [],
  'Bolsas' => [],
  'Produtos Gráficos' => [],
];
$categoryIds = [];
foreach($categories as $parent=>$children){
 $term=term_exists($parent,'product_cat');
 if(!$term){ $term=wp_insert_term($parent,'product_cat'); }
 if(is_wp_error($term)) {WP_CLI::warning($term->get_error_message());continue;}
 $parentId=(int)(is_array($term)?$term['term_id']:$term);
 $categoryIds[$parent]=$parentId;
 foreach($children as $child){
  $exists=term_exists($child,'product_cat',$parentId);
  if(!$exists) { $t=wp_insert_term($child,'product_cat',['parent'=>$parentId]);if(is_wp_error($t))WP_CLI::warning($t->get_error_message());}
 }
}
$menuName='Bela Stock | Menu principal';
$menu=wp_get_nav_menu_object($menuName);
$menuId=$menu?(int)$menu->term_id:wp_create_nav_menu($menuName);
if(!is_wp_error($menuId)){
 $existing=wp_get_nav_menu_items($menuId) ?: [];
 $has=[];
 foreach($existing as $item){ if($item->type==='taxonomy' && $item->object==='product_cat')$has[(int)$item->object_id]=true; }
 foreach($categoryIds as $name=>$id) {
  if(empty($has[$id]))wp_update_nav_menu_item($menuId,0,['menu-item-title'=>$name,'menu-item-object'=>'product_cat','menu-item-object-id'=>$id,'menu-item-type'=>'taxonomy','menu-item-status'=>'publish']);
 }
 $locations=get_theme_mod('nav_menu_locations',[]);
 $registered=get_registered_nav_menus();
 $prefer=['primary','menu-1','main','header','primary-menu'];
 foreach($prefer as $location){if(isset($registered[$location])){$locations[$location]=$menuId;set_theme_mod('nav_menu_locations',$locations);break;}}
}
$existingProducts=wc_get_products(['limit'=>-1,'status'=>['publish','draft'],'return'=>'objects']);
$images=[];
foreach($existingProducts as $p){
 $id=$p->get_image_id();
 if(!$id || !wp_get_attachment_url($id))continue;
 $terms=wp_get_post_terms($p->get_id(),'product_cat',['fields'=>'names']);
 foreach($categories as $category=>$children) {
  $termsLower=array_map('mb_strtolower',$terms);
  if(in_array(mb_strtolower($category),$termsLower,true) && !isset($images[$category]))$images[$category]=$id;
 }
}
foreach($categories as $category=>$children){
 $termId=$categoryIds[$category]??0;
 if(!$termId)continue;
 $thumb=(int)get_term_meta($termId,'thumbnail_id',true);
 if(!$thumb && isset($images[$category]))update_term_meta($termId,'thumbnail_id',$images[$category]);
}
// Os demos nunca são publicados como ofertas ou listados para compra.
foreach($categories as $category=>$children) {
 $termId=$categoryIds[$category]??0;
 if(!$termId)continue;
 $slug='demonstracao-'.sanitize_title($category);
 $existing=get_page_by_path($slug,OBJECT,'product');
 if($existing)continue;
 $product=new WC_Product_Simple();
 $product->set_name('Demonstração — '.$category);
 $product->set_slug($slug);
 $product->set_status('draft');
 $product->set_catalog_visibility('hidden');
 $product->set_description('Produto demonstrativo para configuração. Não disponível para venda.');
 $product->set_manage_stock(false);
 if(isset($images[$category]))$product->set_image_id($images[$category]);
 $id=$product->save();
 if($id)wp_set_object_terms($id,[$termId],'product_cat');
}
WP_CLI::success('Categorias, menu e demos rascunho configurados; produtos existentes preservados.');
