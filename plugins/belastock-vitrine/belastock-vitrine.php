<?php
/**
 * Plugin Name: Bela Stock - Vitrine de Categorias
 * Description: Destaques com imagens existentes no WooCommerce, sem fotos ficticias.
 * Version: 0.1.0
 */
defined('ABSPATH') || exit;
add_filter('do_shortcode_tag', function($output,$tag) {
 if($tag!=='belastock_home' || !taxonomy_exists('product_cat'))return $output;
 $names=['Camisetas','Bonés','Bolsas','Produtos Gráficos'];
 $html='<section class="bs-real-vitrine" aria-label="Nossas categorias"><h2>Encontre seu estilo</h2><div class="bs-real-vitrine-grid">';
 foreach($names as $name) {
  $term=get_term_by('name',$name,'product_cat');
  if(!$term || is_wp_error($term))continue;
  $link=get_term_link($term);if(is_wp_error($link))continue;
  $attachment=(int)get_term_meta($term->term_id,'thumbnail_id',true);
  $image=$attachment?wp_get_attachment_image($attachment,'large',false,['loading'=>'lazy','alt'=>$name]):'';
  if(!$image && function_exists('wc_get_products')) {
   $products=wc_get_products(['status'=>'publish','category'=>[$term->slug],'limit'=>8]);
   foreach($products as $p) {
    $attachment=$p->get_image_id();
    if($attachment){$image=wp_get_attachment_image($attachment,'large',false,['loading'=>'lazy','alt'=>$name]);break;}
   }
  }
  $html.='<a class="bs-real-vitrine-card" href="'.esc_url($link).'">';
  if($image)$html.='<span class="bs-real-vitrine-image">'.$image.'</span>';
  $html.='<strong>'.esc_html($name).'</strong></a>';
 }
 $html.='</div></section>';
 return $html.$output;
},20,2);
add_action('wp_enqueue_scripts',function(){
 $css='.bs-real-vitrine{margin:24px auto 36px;max-width:1400px;padding:0 14px}.bs-real-vitrine h2{font-size:clamp(24px,3vw,38px);margin:0 0 20px}.bs-real-vitrine-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.bs-real-vitrine-card{display:flex;flex-direction:column;justify-content:end;min-height:130px;background:#153e2f;color:#fff!important;border-radius:12px;overflow:hidden;text-decoration:none}.bs-real-vitrine-card strong{padding:16px;font-size:18px}.bs-real-vitrine-image{display:block;background:#f3f5ef;aspect-ratio:4/3}.bs-real-vitrine-image img{width:100%;height:100%;object-fit:contain}@media(max-width:760px){.bs-real-vitrine-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.bs-real-vitrine-card strong{font-size:15px;padding:12px}}';
 wp_register_style('bs-real-vitrine',false,[],'0.1.0');
 wp_enqueue_style('bs-real-vitrine');
 wp_add_inline_style('bs-real-vitrine',$css);
});
