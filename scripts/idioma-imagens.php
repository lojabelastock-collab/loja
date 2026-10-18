<?php
/**
 * Aplicação de pt_BR e catalogação de mídias reais já importadas no WordPress.
 * Uso: wp eval-file scripts/idioma-imagens.php
 */
if(!defined('ABSPATH') || !defined('WP_CLI'))exit(1);
update_option('WPLANG','pt_BR');
$categories=['Camisetas'=>['camiseta','camisa','shirt','tshirt','t-shirt'],'Bonés'=>['boné','bone','cap'],'Bolsas'=>['bolsa','ecobag','bag'],'Produtos Gráficos'=>['grafico','gráfico','adesivo','banner','flyer','cartao']];
$attached=get_posts(['post_type'=>'attachment','post_mime_type'=>'image','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids']);
$count=0;
foreach($categories as $name=>$keywords){
 $term=get_term_by('name',$name,'product_cat');if(!$term)continue;
 if((int)get_term_meta($term->term_id,'thumbnail_id',true))continue;
 $matches=[];
 foreach($attached as $aid) {
  if(!wp_attachment_is_image($aid))continue;
  $source=(string)get_post_meta($aid,'_wp_attached_file',true);
  $search=mb_strtolower(get_the_title($aid).' '.basename($source),'UTF-8');
  foreach($keywords as $word){if(preg_match('/(^|[^\\pL])'.preg_quote(mb_strtolower($word,'UTF-8'),'/').'([^\\pL]|$)/u',$search)){$matches[]=$aid;break;}}
 }
 if(count($matches)===1){update_term_meta($term->term_id,'thumbnail_id',(int)$matches[0]);$count++;WP_CLI::log('Imagem real associada: '.$name);}
 else WP_CLI::log('Categoria '. $name . ': '.count($matches).' imagens candidatas, associação manual pendente.');
}
WP_CLI::success('pt_BR configurado. Capas reais associadas com correspondência inequívoca: '.$count);
