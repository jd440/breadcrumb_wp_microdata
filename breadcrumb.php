<?php
// Création d'um fil d'ariane en HTML5 avec microdata
// ___________________________________________________
// Fonction à insérere dans function.php
// s'appel dans le theme via :  if (function_exists('breadcrumb_wp_microdata')){breadcrumb_wp_microdata();}
// _______________________________________________
// @jd440 @https://github.com/jd440
// source https://github.com/jd440/breadcrumb_wp_microdata
// inspiration http://www.seomix.fr/fil-dariane-chemin-navigation/
//
//

//***Fonction pour récupérer les catégories parentes
function myget_category_parents($id, $nicename = false, $visited = array()) {
	$breadcrumbLevelcat = '';
	$parent = &get_category($id);
	if (is_wp_error($parent)) {
		return $parent;
	}

	if ($nicename) {
		$name = $parent->slug;
	} else {
		$name = $parent->cat_name;
	}

	if ($parent->parent && ($parent->parent != $parent->term_id) && !in_array($parent->parent, $visited)) {
		$visited[] = $parent->parent;
		$breadcrumbLevelcat = myget_category_parents($parent->parent, $nicename, $visited);}

	//Ne pas affiché la category si c'est la non classé
	if (count(get_all_category_ids()) > 1) {
		$breadcrumbLevelcat[$name] = get_category_link($parent->term_id);
	}

	return $breadcrumbLevelcat;
}

function formBreadcrumb($breadcrumbLevel, $type, $separator = " › ") {

	$out = '<nav' . (($type == "ol") || ($type == "ul") ? "><" . $type : "") . ' itemscope itemtype="http://schema.org/BreadcrumbList">';
	foreach ($breadcrumbLevel as $key => $value) {
		if (isset($n)) {$out .= $separator;}
		$n++;
		$out .= '<' . (($type == "ol") || ($type == "ul") ? "li" : "span") . ' itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a itemscope itemtype="http://schema.org/Thing" itemprop="item" href="' . $value . '	"><span itemprop="name">' . $key . '</span>';

		//Todo
		// <img itemprop="image" src="http://example.com/images/icon-bookicon.png" alt="Books"/>
		$out .= '</a><meta itemprop="position" content="' . $n . '" /></' . (($type == "ol") || ($type == "ul") ? "li" : "span") . '>';
	}
	$out .= '</' . (($type == "ol") || ($type == "ul") ? $type . "></" : "") . 'nav>';
	return $out;
}

//***Fonction de chemin de navigation
function breadcrumb_wp_microdata($type = "ol") {

	global $wp_query;
	$breadcrumbLevel = array(get_bloginfo('name') => home_url() . '/');
	$breadcrumbLevel['blog'] = home_url() . '/';
	if (is_category()) {
		$cat_obj = $wp_query->get_queried_object();
		$thisCat = $cat_obj->term_id;
		$thisCat = get_category($thisCat);
		$parentCat = get_category($thisCat->parent);
		if ($thisCat->parent != 0) {
			$listcat = myget_category_parents($parentCat);
			$breadcrumbLevel = array_merge($breadcrumbLevel, $listcat);
		}

		$breadcrumbLevel[single_cat_title("", false)] = get_category_link($cat_obj->term_id);

	} elseif (is_author()) {
		global $author;
		$user_info = get_userdata($author);

		$author_link = get_the_author_meta('user_url', $author);

		$breadcrumbLevel["Articles de l'auteur " . $user_info->display_name] = $author_link;

	} elseif (is_tag()) {
		$tags = get_the_tags();
		$tag = single_tag_title("", FALSE);
		$tag_id = $tags[0]->term_id;
		$breadcrumbLevel["Articles sur le Th&egrave;me " . $tag] = get_tag_link($tag_id);

	} elseif (is_archive() && !is_category()) {
		$rendu .= "Archives";
	} elseif (is_search()) {
		$breadcrumbLevel["R&eacute;sultats de votre recherche " . get_search_query()] = get_permalink();
	} elseif (is_404()) {
		$breadcrumbLevel["404 Page non trouv&eacute;e"] = "";
	} elseif (is_single()) {
		$category = get_the_category();
		$category_id = get_cat_ID($category[0]->cat_name);
		$listcat = myget_category_parents($category_id);

		$breadcrumbLevel = array_merge($breadcrumbLevel, $listcat);

		$breadcrumbLevel[the_title('', '', FALSE)] = get_permalink();

	} elseif (is_page()) {
		$post = $wp_query->get_queried_object();
		if ($post->post_parent == 0) {
			$rendu .= the_title('', '', FALSE) . "";} elseif ($post->post_parent != 0) {
			$title = the_title('', '', FALSE);
			$ancestors = array_reverse(get_post_ancestors($post->ID));
			array_push($ancestors, $post->ID);
			foreach ($ancestors as $ancestor) {
				$breadcrumbLevel[strip_tags(apply_filters('single_post_title', get_the_title($ancestor)))] = get_permalink($ancestor);
			}
		}
	}

	$ped = get_query_var('paged');
	if ($ped >= 1) {$rendu .= ' (Page ' . $ped . ')';}
	$rendu = formBreadcrumb($breadcrumbLevel, $type);
	echo $rendu;
}
