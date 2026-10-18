<?php
// Bela Stock: somente referencias externas verificadas no Drive, sem transferir imagens.
if(!defined('ABSPATH') || !defined('WP_CLI'))exit(1);
if(!class_exists('WooCommerce'))WP_CLI::error('WooCommerce indisponivel');
$sets=[
 ['slug'=>'quarto-crescente-emblema-lua','name'=>'Quarto Crescente — Emblema Lua','variants'=>[
  'Preta'=>'1FjRiih47X9uQ0a1DB-7Pa4BVafNoxWt2',
  'Verde'=>'10m3ZhSWUMSng55VTk67XiSfXvmDbNY0o',
  'Rosa'=>'1qKJfBUT2pZp43OgDrPCXSGndBuvC75Hl'
 ]],
 ['slug'=>'quarto-crescente-4x4','name'=>'Quarto Crescente — Estampa 4x4','variants'=>[
  'Branca'=>'18QUE2PJ14PbQxAiEnGA5gMSWVKondfp6'
 ]],
 ['slug'=>'quarto-crescente-quatro-simbolos-lilas','name'=>'Quarto Crescente — Quatro Símbolos Lilás','variants'=>[
  'Branca'=>'1rKvsLe9uOMnnoPUnwTS-d8Tl4WG-1mzY'
 ]]
];
$parent=term_exists('Camisetas','product_cat');
if(!$parent)$parent=wp_insert_term('Camisetas','product_cat');
if(is_wp_error($parent))WP_CLI::error($parent->get_error_message());
$parentId=(int)(is_array($parent)?$parent['term_id']:$parent);
$cat=term_exists('Quarto Crescente','product_cat',$parentId);
if(!$cat)$cat=wp_insert_term('Quarto Crescente','product_cat',['parent'=>$parentId]);
if(is_wp_error($cat))WP_CLI::error($cat->get_error_message());
$catId=(int)(is_array($cat)?$cat['term_id']:$cat);
$tax='pa_cor';
if(!taxonomy_exists($tax)){
 $aid=wc_attribute_taxonomy_id_by_name('cor');
 if(!$aid)$aid=wc_create_attribute(['name'=>'Cor','slug'=>'cor','type'=>'select','order_by'=>'menu_order','has_archives'=>false]);
 if(is_wp_error($aid))WP_CLI::error($aid->get_error_message());
 delete_transient('wc_attribute_taxonomies');
 register_taxonomy($tax,['product'],['hierarchical'=>false,'show_ui'=>false,'query_var'=>true,'rewrite'=>false]);
}
foreach($sets as $set){
 $found=get_page_by_path($set['slug'],OBJECT,'product');
 if($found){
  $product=wc_get_product($found->ID);
  if(!$product || !$product->is_type('variable')){WP_CLI::warning('Slug ocupado: '.$set['slug']);continue;}
 }else{
  $product=new WC_Product_Variable();
  $product->set_name($set['name']);
  $product->set_slug($set['slug']);
 }
 $product->set_status('draft');
 $product->set_catalog_visibility('hidden');
 $product->set_category_ids([$catId]);
 $product->set_description('Camiseta da linha Quarto Crescente. A arte e a cor exibidas correspondem ao modelo escolhido. Composição, técnica de impressão, medidas, preço e disponibilidade dependem de confirmação do cadastro técnico. Confira o gabarito antes da produção.');
 $product->set_short_description('Camiseta da coleção Quarto Crescente com variação de cor conforme imagem.');
 $product->save();
 update_post_meta($product->get_id(),'_bs_drive_image_id',reset($set['variants']));
 $ids=[];
 foreach($set['variants'] as $color=>$fid){
  $term=term_exists($color,$tax);
  if(!$term)$term=wp_insert_term($color,$tax);
  if(is_wp_error($term)){WP_CLI::warning($term->get_error_message());continue;}
  $tid=(int)(is_array($term)?$term['term_id']:$term);
  $ids[]=$tid;
 }
 wp_set_object_terms($product->get_id(),$ids,$tax);
 $attribute=new WC_Product_Attribute();
 $attribute->set_id(wc_attribute_taxonomy_id_by_name('cor'));
 $attribute->set_name($tax);$attribute->set_options($ids);
 $attribute->set_visible(true);$attribute->set_variation(true);
 $product->set_attributes([$attribute]);$product->save();
 foreach($set['variants'] as $color=>$fid){
  $term=get_term_by('name',$color,$tax);if(!$term)continue;
  $variation=null;
  foreach($product->get_children() as $vid){
   $v=wc_get_product($vid);
   if($v && $v->get_attribute($tax)===$term->slug){$variation=$v;break;}
  }
  if(!$variation)$variation=new WC_Product_Variation();
  $variation->set_parent_id($product->get_id());
  $variation->set_attributes([$tax=>$term->slug]);
  $variation->set_status('private');
  $variation->save();
  update_post_meta($variation->get_id(),'_bs_drive_image_id',$fid);
  update_post_meta($variation->get_id(),'_bs_drive_source_url','https://drive.google.com/file/d/'.$fid.'/view');
 }
 WP_CLI::log('Referencias vinculadas: '.$set['name']);
}
WP_CLI::success('Vinculos com Google Drive cadastrados. Nenhuma imagem transferida.');
