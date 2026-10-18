<?php
// Cadastre imagens externas por ID do Google Drive, sem download.
if (!defined('ABSPATH') || !defined('WP_CLI')) exit(1);
if (!class_exists('WooCommerce')) WP_CLI::error('WooCommerce indisponivel');
$parent=term_exists('Camisetas','product_cat');
if (!$parent) $parent=wp_insert_term('Camisetas','product_cat');
if (is_wp_error($parent)) WP_CLI::error($parent->get_error_message());
$parentId=(int)(is_array($parent)?$parent['term_id']:$parent);
$category=term_exists('Quarto Crescente','product_cat',$parentId);
if (!$category) $category=wp_insert_term('Quarto Crescente','product_cat',['parent'=>$parentId]);
if (is_wp_error($category)) WP_CLI::error($category->get_error_message());
$categoryId=(int)(is_array($category)?$category['term_id']:$category);
$colors=[
 'Preta'=>'1FjRiih47X9uQ0a1DB-7Pa4BVafNoxWt2',
 'Verde'=>'10m3ZhSWUMSng55VTk67XiSfXvmDbNY0o',
 'Rosa'=>'1qKJfBUT2pZp43OgDrPCXSGndBuvC75Hl'
];
$slug='quarto-crescente-emblema-lua';
$existing=get_page_by_path($slug,OBJECT,'product');
if ($existing) {
 $product=wc_get_product($existing->ID);
 if (!$product || !$product->is_type('variable')) WP_CLI::error('Slug ja utilizado por outro tipo de produto');
} else {
 $product=new WC_Product_Variable();
 $product->set_name('Quarto Crescente — Emblema Lua');
 $product->set_slug($slug);
}
$product->set_status('draft');
$product->set_catalog_visibility('hidden');
$product->set_category_ids([$categoryId]);
$product->set_description('Camiseta da linha Quarto Crescente com estampa Emblema Lua. Escolha a cor desejada. Composição, tamanhos e técnica de impressão serão apresentados somente após confirmação das especificações do produto. Antes de produzir, confira o gabarito e a posição da arte.');
$product->set_short_description('Estampa Emblema Lua nas cores preta, verde e rosa.');
$product->save();
update_post_meta($product->get_id(),'_bs_drive_image_id',reset($colors));
$taxonomy='pa_cor';
if (!taxonomy_exists($taxonomy)) {
 $attributeId=wc_attribute_taxonomy_id_by_name('cor');
 if (!$attributeId) $attributeId=wc_create_attribute(['name'=>'Cor','slug'=>'cor','type'=>'select','order_by'=>'menu_order','has_archives'=>false]);
 if (is_wp_error($attributeId)) WP_CLI::error($attributeId->get_error_message());
 register_taxonomy($taxonomy,['product'],['hierarchical'=>false,'show_ui'=>false,'query_var'=>true,'rewrite'=>false]);
 delete_transient('wc_attribute_taxonomies');
}
$termIds=[];
foreach($colors as $color=>$fileId) {
 $term=term_exists($color,$taxonomy);
 if (!$term) $term=wp_insert_term($color,$taxonomy);
 if (is_wp_error($term)) { WP_CLI::warning($term->get_error_message());continue; }
 $termIds[]=(int)(is_array($term)?$term['term_id']:$term);
}
wp_set_object_terms($product->get_id(),$termIds,$taxonomy);
$attribute=new WC_Product_Attribute();
$attribute->set_id(wc_attribute_taxonomy_id_by_name('cor'));
$attribute->set_name($taxonomy);
$attribute->set_options($termIds);
$attribute->set_visible(true);
$attribute->set_variation(true);
$product->set_attributes([$attribute]);
$product->save();
foreach($colors as $color=>$fileId) {
 $term=get_term_by('name',$color,$taxonomy);
 if (!$term) continue;
 $variation=null;
 foreach($product->get_children() as $vid) {
  $v=wc_get_product($vid);
  if ($v && $v->get_attribute($taxonomy)===$term->slug) {$variation=$v;break;}
 }
 if (!$variation) $variation=new WC_Product_Variation();
 $variation->set_parent_id($product->get_id());
 $variation->set_attributes([$taxonomy=>$term->slug]);
 $variation->set_status('private');
 $variation->save();
 update_post_meta($variation->get_id(),'_bs_drive_image_id',$fileId);
 update_post_meta($variation->get_id(),'_bs_drive_source_url','https://drive.google.com/file/d/'.$fileId.'/view');
}
WP_CLI::success('Quarto Crescente: produto e 3 variacoes registrados sem transferencia de imagens.');
