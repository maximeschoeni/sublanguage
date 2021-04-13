
document.addEventListener("DOMContentLoaded", function() {
	var currentLanguage;
	function getMainLanguage() {
		for (var i = 0; i < sublanguage.languages.length; i++) {
			if (sublanguage.languages[i].isMain) {
				return sublanguage.languages[i];
			}
		}
	}
	function registerLanguageManager() {
		var store = {};
		var languageSwitchContainer;

		function createLanguageSwitch() {
			var postType = wp.data.select("core/editor").getCurrentPostType();
			var updateCallbacks = [];
			var ul = document.createElement("ul");
			ul.className = "gutenberg-language-switch";
			sublanguage.languages.forEach(function(language) {
				var li = document.createElement("li");
				var a = document.createElement("a");
				function update() {
					li.className = language.slug === sublanguage.current || (!sublanguage.current && language.isMain) ? "active" : "";
				}
				updateCallbacks.push(update);
				update();
				a.className = "sublanguage";
				a.textContent = language.name;
				a.href = wp.url.addQueryArgs(location.href, {language:language.slug});
				a.addEventListener("click", function(event) {
					if (!sublanguage.post_type_options[postType].gutenberg_metabox_compat) {
						event.preventDefault();
						var isSaving = wp.data.select('core/editor').isSavingPost();
						if (!isSaving) {
							saveAttributes();
							switchLanguage(language);
							updateSwitch();
							updateCallbacks.forEach(function(updateItem) {
								updateItem();
							});
						}
					}
				});
				li.appendChild(a);
				ul.appendChild(li);
			});
			return ul;
		}

		function updateSwitch() {
			var editor = document.getElementById("editor");
			var editorHeader = editor && editor.querySelector(".edit-post-header__settings");
			if (editorHeader && editorHeader.parentNode) {
				if (!languageSwitchContainer) {
					languageSwitchContainer = createLanguageSwitch();
				}
				if (languageSwitchContainer.parentNode !== editorHeader.parentNode) {
					editorHeader.parentNode.insertBefore(languageSwitchContainer, editorHeader);
				}
			} else {
				setTimeout(function() {
					updateSwitch();
				}, 500);
			}
		}

		function saveAttributes() {
			var content = wp.data.select("core/editor").getEditedPostContent();
			var excerpt = wp.data.select("core/editor").getEditedPostAttribute("excerpt");
			var title = wp.data.select("core/editor").getEditedPostAttribute("title");
			var slug = wp.data.select("core/editor").getEditedPostAttribute("slug");
			var prefix = "_"+currentLanguage.slug+"_";
			store[currentLanguage.prefix+"post_content"] = content;
			store[currentLanguage.prefix+"post_excerpt"] = excerpt;
			store[currentLanguage.prefix+"post_title"] = title;
			store[currentLanguage.prefix+"post_name"] = slug;
		}

		function switchLanguage(language) {
			var content = store[language.prefix+"post_content"];
			var excerpt = store[language.prefix+"post_excerpt"];
			var title = store[language.prefix+"post_title"];
			var slug = store[language.prefix+"post_name"];

			var meta = wp.data.select("core/editor").getPostEdits().meta || {};

			meta[currentLanguage.prefix+"post_content"] = store[currentLanguage.prefix+"post_content"];
			meta[currentLanguage.prefix+"post_excerpt"] = store[currentLanguage.prefix+"post_excerpt"];
			meta[currentLanguage.prefix+"post_title"] = store[currentLanguage.prefix+"post_title"];
			meta[currentLanguage.prefix+"post_name"] = store[currentLanguage.prefix+"post_name"];
			meta["edit_language"] = language.slug;

			wp.data.dispatch("core/editor").resetBlocks([]);
			if (content) {
				var blocks = wp.blocks.parse( content );
				if (blocks.length) {
					wp.data.dispatch("core/editor").insertBlocks( blocks );
				}
			}
			wp.data.dispatch("core/editor").editPost({
				excerpt: excerpt,
				title: title,
				slug: slug,
				meta: meta
			});
			wp.data.dispatch("core/editor").clearSelectedBlock();

			currentLanguage = language;
			sublanguage.current = language.slug;
		}


		function regenLanguage() {
			// -> force edit_language in edits data
			// -> spoiler: this is ugly!

			var meta = wp.data.select("core/editor").getPostEdits().meta || {};
			if (!meta.edit_language) {
				meta.edit_language = currentLanguage.slug;
				meta.force_update = Date.now();
				wp.data.dispatch("core/editor").editPost({meta: meta});
			}

			setTimeout(regenLanguage, 200);
		}


		function init() {
			var meta = wp.data.select("core/editor").getCurrentPostAttribute("meta");

			sublanguage.languages.forEach(function(language) {
				if (language.slug === meta.edit_language) {
					currentLanguage = language;
					saveAttributes();
					switchLanguage(language);
				} else {
					store[language.prefix+"post_content"] = meta[language.prefix+"post_content"];
					store[language.prefix+"post_excerpt"] = meta[language.prefix+"post_excerpt"];
					store[language.prefix+"post_title"] = meta[language.prefix+"post_title"];
					store[language.prefix+"post_name"] = meta[language.prefix+"post_name"];
				}
			});
		}

		// disable autosave
		wp.data.dispatch('core/editor').updateEditorSettings({
			autosaveInterval: 99999999,
		});

		init();
		regenLanguage();
		updateSwitch();
	}
	function tryRegister() {
		var post = wp.data.select("core/editor").getCurrentPost();
		if (post && post.id) {
			registerLanguageManager();
		} else {
			setTimeout(tryRegister, 200);
		}
	}
	tryRegister();

	// monkey patch permalinks
	var getPermalinkParts = wp.data.select("core/editor").getPermalinkParts;

	wp.data.select("core/editor").getPermalinkParts = function() {
		var parts = getPermalinkParts();
		var mainLanguage = getMainLanguage();
		if (mainLanguage && currentLanguage && mainLanguage !== currentLanguage) {
			parts.prefix = parts.prefix.replace(mainLanguage.home_url.replace(/\/$/, ""), currentLanguage.home_url.replace(/\/$/, ""));
		}
		return parts;
	}



});
