<?php

if(!class_exists('HT_Gallery_Renderers')){
	class HT_Gallery_Renderers {
		//constructor
		function __construct(){


		}

		/**
		* Heroic Gallery get gallery function
		*
		* @param String $gallery_id The id of the gallery to fetch
		* @return Array  $gallery An array containing the gallery details
		*/
		static function ht_gallery_get_gallery($gallery_id){
			$gallery = array();

			$gallery_image_ids = HT_Gallery_Manager::get_hero_gallery_images($gallery_id);
			$starred_image = HT_Gallery_Manager::get_starred_image($gallery_id);


			foreach ($gallery_image_ids as $key => $id) {
				$gallery_image = array();
				$gallery_image['id'] = $id;

				$attachment_post = get_post($id);
				if(is_a($attachment_post, 'WP_Post')){
					$gallery_image['title'] = $attachment_post->post_title;
					$gallery_image['caption'] = $attachment_post->post_excerpt;
					
					//image alt
					$image_alt = get_post_meta($id, '_wp_attachment_image_alt', true);
					$gallery_image['alt'] = $image_alt;

					$gallery_image['description'] = $attachment_post->post_content;

					//video url
					$video_url = get_post_meta($id, HT_GALLERY_VIDEO_URL_META_KEY_VALUE, true);
					$gallery_image['video_url'] = $video_url;

					//starred
					$gallery_image['starred'] = $id == $starred_image ? true : false;					

				}
				
				//push object
				array_push($gallery, $gallery_image);

			}


			return $gallery;
		}

		/**
		* Heroic Gallery Get related galleries function
		*
		* @param String $post_id The post id to fetch realted galleries for
		* @param Int $no_of_related The number of related items to fetch
		* @param Mixed $src_size The size of thumbnail required, per WP
		* @return Array  $related_galleries The array of related galleries
		*/
		public static function ht_gallery_get_related($post_id = null, $no_of_related = 4, $src_size = null){
			$related_galleries = Array();
			if($no_of_related<1){
				return $related_galleries;
			}
			$post_id = empty($post_id)  ? get_the_ID() : $post_id;

			$related = HT_Gallery_Manager::ht_gallery_get_posts_related_by_taxonomy($post_id, 'ht_gallery_category', $no_of_related*2);
			$related_item_count = 0;
			while($related->have_posts() && $related_item_count<$no_of_related) {
				$related->the_post();
				//the empty array
				$related_post = array();
				//fill id
				$related_id = get_the_ID();
				$related_post['id'] = $related_id;


				//permalink
				$related_permalink = get_post_permalink($related_id);
				$related_post['permalink'] = $related_permalink;

				//get the starred image id
				$related_starred_id  = HT_Gallery_Manager::get_starred_image($related_id);
				$related_post['starred_id'] = $related_starred_id;

				//get the starred image src
				$related_starred_image = wp_get_attachment_image_src($related_starred_id, $src_size);
				$related_post['starred_image'] = $related_starred_image;

				//push the related_post if starred image id not empty
				if(!empty($related_starred_image)){
					array_push( $related_galleries, $related_post );
					$related_item_count = $related_item_count + 1;
				}				

			} 

			//do we need to reset the post here?
			return $related_galleries;
		}

	}
}


if(class_exists('HT_Gallery_Renderers')){
	$ht_gallery_renders_init = new HT_Gallery_Renderers();

	function ht_gallery_get_gallery($gallery_id=null){
		return HT_Gallery_Renderers::ht_gallery_get_gallery($gallery_id);
	}

	function ht_gallery_get_related($gallery_id = null, $no_of_related = 4, $src_size = null){
		return HT_Gallery_Renderers::ht_gallery_get_related($gallery_id, $no_of_related, $src_size);
	}
}