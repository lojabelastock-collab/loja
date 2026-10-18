<?php
/**
 * Plugin Name: Bela Stock - Midia Drive
 * Description: Associa IDs de arquivos do Google Drive a imagens externas do catalogo sem baixar os originais.
 * Version: 0.1.0
 */
defined('ABSPATH') || exit;
final class BS_Drive_Media {
 static function init():void {
  add_filter('woocommerce_product_get_image_id',[__CLASS__,'nochange'],10,2);
  add_filter('woocommerce_product_get_image',[__CLASS__,'image'],20,5);
  add_filter('woocommerce_product_variation_get_image',[__CLASS__,'variation_image'],20,5);
  add_filter('woocommerce_single_product_image_thumbnail_html',[__CLASS__,'single'],20,2);
 }
 static function nochange($id,$product){return $id;}
 static function url($id):string {
  if(!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{20,}$/',$id))return '';
  return 'https://drive.google.com/thumbnail?id='.rawurlencode($id).'&sz=w1200';
 }
 static function img($product,$fallback):string {
  if(!is_object($product)||!method_exists($product,'get_id'))return $fallback;
  $id=get_post_meta($product->get_id(),'_bs_drive_image_id',true);
  $url=self::url($id);if(!$url)return $fallback;
  return '<img class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail bs-drive-img" src="'.esc_url($url).'" alt="'.esc_attr($product->get_name()).'" loading="lazy" referrerpolicy="no-referrer">';
 }
 static function image($html,$product,$size,$attr,$placeholder){return self::img($product,$html);}
 static function variation_image($html,$product,$size,$attr,$placeholder){return self::img($product,$html);}
 static function single($html,$attachment_id){
  global $product;
  if(!$product||$attachment_id)return $html;
  return self::img($product,$html);
 }
}
BS_Drive_Media::init();
