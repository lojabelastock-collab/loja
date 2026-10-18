<?php
/**
 * Plugin Name: Bela Stock - Midia Drive
 * Description: Exibe imagens vinculadas por ID do Drive sem salvar fotos na VPS ou na biblioteca de midia.
 * Version: 0.2.0
 */
defined('ABSPATH') || exit;
final class BS_Drive_Media {
 static function init():void {
  add_filter('woocommerce_product_get_image',[__CLASS__,'image'],20,5);
  add_filter('woocommerce_product_variation_get_image',[__CLASS__,'image'],20,5);
  add_filter('woocommerce_available_variation',[__CLASS__,'variation'],20,3);
  add_filter('woocommerce_cart_item_thumbnail',[__CLASS__,'cart'],20,3);
  add_filter('woocommerce_single_product_image_thumbnail_html',[__CLASS__,'single'],20,2);
  add_action('wp_head',[__CLASS__,'css']);
 }
 static function file_id($product):string {
  if(!is_object($product)||!method_exists($product,'get_id'))return '';
  $id=get_post_meta($product->get_id(),'_bs_drive_image_id',true);
  if(!$id && method_exists($product,'get_parent_id') && $product->get_parent_id()){
   $id=get_post_meta($product->get_parent_id(),'_bs_drive_image_id',true);
  }
  return is_string($id)&&preg_match('/^[a-zA-Z0-9_-]{20,}$/D',$id)?$id:'';
 }
 static function url($id):string {
  return $id?'https://drive.google.com/thumbnail?id='.rawurlencode($id).'&sz=w1200':'';
 }
 static function markup($product,$fallback,$class='attachment-woocommerce_thumbnail size-woocommerce_thumbnail'):string {
  $id=self::file_id($product);
  if(!$id)return $fallback;
  return '<img class="'.esc_attr($class).' bs-drive-img" src="'.esc_url(self::url($id)).'" alt="'.esc_attr($product->get_name()).'" loading="lazy" referrerpolicy="no-referrer" decoding="async">';
 }
 static function image($html,$product,$size,$attr,$placeholder){return self::markup($product,$html);}
 static function variation($data,$parent,$variation){
  $id=self::file_id($variation);
  if(!$id)return $data;
  $url=self::url($id);
  $data['image']=array_merge(isset($data['image'])&&is_array($data['image'])?$data['image']:[],[
   'src'=>$url,'full_src'=>$url,'thumb_src'=>$url,
   'srcset'=>'','sizes'=>'','full_src_w'=>1200,'full_src_h'=>1200,
   'thumb_src_w'=>600,'thumb_src_h'=>600,'title'=>$variation->get_name(),
   'alt'=>$variation->get_name(),
  ]);
  return $data;
 }
 static function cart($html,$cart_item,$cart_key){
  $product=$cart_item['data']??null;
  return $product?self::markup($product,$html):$html;
 }
 static function single($html,$attachment_id){
  global $product;
  if(!$product || $attachment_id)return $html;
  $id=self::file_id($product);
  if(!$id)return $html;
  $url=self::url($id);
  return '<div class="woocommerce-product-gallery__image"><a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">'.self::markup($product,'','wp-post-image').'</a></div>';
 }
 static function css():void {
  if(!function_exists('is_woocommerce')||!(is_woocommerce()||is_cart()))return;
  echo '<style>.bs-drive-img{max-width:100%;height:auto;object-fit:contain}.woocommerce-product-gallery .bs-drive-img{width:100%;display:block}</style>';
 }
}
BS_Drive_Media::init();
