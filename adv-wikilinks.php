<?php
/*
Plugin Name: Advanced Wiki Links
Plugin URI: http://blog.chuckles34.net/advanced-wiki-links-01/
Description: Create links out of words that match post titles and content tags to generate quick cross-links. Based on Wiki Links by Vlad Babii
Version: 0.3
Author: Chuckles
Author URI: http://blog.chuckles34.net
*/

function get_localpermalink($id = 0) {
        $rewritecode = array(
                '%year%',
                '%monthnum%',
                '%day%',
                '%hour%',
                '%minute%',
                '%second%',
                '%postname%',
                '%post_id%',
                '%category%',
                '%author%',
                '%pagename%'
        );

        $post = &get_post($id);
        if ( $post->post_type == 'page' )
                return get_page_link($post->ID);
        elseif ($post->post_type == 'attachment')
                return get_attachment_link($post->ID);

        $permalink = get_option('permalink_structure');

        if ( '' != $permalink && 'draft' != $post->post_status ) {
                $unixtime = strtotime($post->post_date);

                $category = '';
                if (strpos($permalink, '%category%') !== false) {
                        $cats = get_the_category($post->ID);
                        if ( $cats )
                                usort($cats, '_get_the_category_usort_by_ID'); // order by ID
                        $category = $cats[0]->category_nicename;
                        if ( $parent=$cats[0]->category_parent )
                                $category = get_category_parents($parent, FALSE, '/', TRUE) . $category;
                }

                $authordata = get_userdata($post->post_author);
                $author = $authordata->user_nicename;
                $date = explode(" ",date('Y m d H i s', $unixtime));
                $rewritereplace =
                array(
                        $date[0],
                        $date[1],
                        $date[2],
                        $date[3],
                        $date[4],
                        $date[5],
                        $post->post_name,
                        $post->ID,
                        $category,
                        $author,
                        $post->post_name,
                );
                $permalink = str_replace($rewritecode, $rewritereplace, $permalink);
                $permalink = user_trailingslashit($permalink, 'single');
                return apply_filters('post_link', $permalink, $post);
        } else { // if they're not using the fancy permalink option
                $permalink = '/?p=' . $post->ID;
                return apply_filters('post_link', $permalink, $post);
        }
}

function get_category_locallink($category_id) {
        global $wp_rewrite;
        $catlink = $wp_rewrite->get_category_permastruct();

        if ( empty($catlink) ) {
                $catlink = '?cat=' . $category_id;
        } else {
                $category = &get_category($category_id);
                $category_nicename = $category->category_nicename;

                if ( $parent = $category->category_parent )
                        $category_nicename = get_category_parents($parent, false, '/', true) . $category_nicename;

                $catlink = str_replace('%category%', $category_nicename, $catlink);
                $catlink = user_trailingslashit($catlink, 'category');
        }
        return apply_filters('category_link', $catlink, $category_id);
}

function wiki_links($content) {

  $post_name_list=array();
  $post_category_list=array();

  $myposts = get_posts('order=ASC');
  foreach($myposts as $post)
  {
    $post_name_list[ $post->post_title ] = get_localpermalink($post->ID);
  }

  $mycategories=get_categories();
  foreach($mycategories as $category)
  {
    $post_category_list[ $category->cat_name ] = get_category_locallink($category->cat_ID);
  }

  preg_match_all("/(<([a]+)[^>]*>)(.*)(<\/\\2>)/", $content, $matches, PREG_SET_ORDER);
  $content = preg_replace("/(<([a]+)[^>]*>)(.*)(<\/\\2>)/", '_WIKI_META_HOLDER_', $content);

  foreach($post_name_list as $article_name => $article_link)
  {
    if(
      isset($content) &&  strlen($content)>0 && 
      isset($article_name) && strlen($article_name)>0 &&
      strpos($content,$article_name)) 
    {
      $content = preg_replace("/$article_name/", "<a href=\"$article_link\">$article_name</a>", $content);
    }
  }

  foreach ($matches as $val) {
    $content = preg_replace('/_WIKI_META_HOLDER_/',$val[0], $content, 1);
  }
  unset($matches);
  preg_match_all("/(<([a]+)[^>]*>)(.*)(<\/\\2>)/", $content, $matches, PREG_SET_ORDER);
  $content = preg_replace("/(<([a]+)[^>]*>)(.*)(<\/\\2>)/", '_WIKI_META_HOLDER_', $content);

  foreach($post_category_list as $category_name => $category_link)
  {
    if(
      isset($content) &&  strlen($content)>0 && 
      isset($category_name) && strlen($category_name)>0 &&
      strpos($content,$category_name)) 
    {
      $content = preg_replace("/$category_name/", "<a href=\"$category_link\" title=\"View all posts filed under $category_name\">$category_name</a>", $content);
    }
  } 

  foreach ($matches as $val) {
    $content = preg_replace('/_WIKI_META_HOLDER_/',$val[0], $content, 1);
  }
  return $content;
}

add_filter('the_content' ,'wiki_links');
?>
