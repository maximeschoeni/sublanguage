<?php

class Sublanguage_ajax extends Sublanguage_admin {

	/**
	 * @override Sublanguage_admin::load
	 *
	 * @from 2.0
	 */
	public function load() {

		parent::load();

		// disabled in 2.10
		// Option translation
		// register ajax hooks for getting/setting options
		// add_action( 'wp_ajax_sublanguage_export_options', array($this, 'ajax_export_options') );
		// add_action( 'wp_ajax_sublanguage_option_translations', array($this, 'ajax_get_option_translations') );
		// add_action( 'wp_ajax_sublanguage_set_option_translation', array($this, 'ajax_set_option_translation') );

		// disabled in 2.10
		// save post from ajax (for editor button)
		// add_action( 'wp_ajax_sublanguage_save_translation', array($this, 'ajax_save_translation'));

		// Save terms when quick editing (ajax).
		add_filter( 'wp_update_term_data', array($this, 'update_term_data'), 10, 4);

		// Translate home url -> !! Fluching Rules !!
		add_filter('home_url', array($this,'translate_home_url'), 10, 4);

	}



	/**
	 * Save terms when quick editing (ajax).
	 *
	 * @filter 'wp_update_term_data' (WP 4.7)
	 *
	 * @from 2.0
	 */
	public function update_term_data($data, $term_id, $taxonomy, $args) {

		if ($this->is_sub() && $this->is_taxonomy_translatable($taxonomy)) {

			$fields = $this->get_taxonomy_fields($taxonomy);

			foreach ($data as $field => $value) {

				if (in_array($field, $fields)) {

					if ($field === 'slug') {

						$value = sanitize_title($value);

					}

					update_term_meta($term_id, $this->get_prefix().$field, $value);

				}

			}

			// restore original data, because we can't stop term edition process.

			$this->set_language($this->get_main_language());

			$term = get_term_by('id', $term_id, $taxonomy);

			if ($term) {

				foreach ($data as $field => $value) {

					if (in_array($field, $fields)) {

						$data[$field] = $term->$field;

					}

				}

			}

			$this->restore_language(); // -> for ajax callback

		}

		return $data;

	}

	/**
	 * Save Post from Ajax (for editor button)
	 *
	 * @hook 'wp_ajax_sublanguage_save_translation'
	 *
	 * @from 1.3
	 * @removed from 2.10
	 */
	public function ajax_save_translation() {

		trigger_error('feature is removed and function has been disabled, Sublanguage v2.10');

	}


}
