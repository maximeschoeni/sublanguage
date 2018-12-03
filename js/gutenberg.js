
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
	
		function build(tag) {
			var classes = tag.split(".");
			element = document.createElement(classes[0]);
			if (classes.length > 1) {
				element.className = classes.slice(1).join(" ");
			}
			for (var i = 1; i < arguments.length; i++) {
				if (typeof arguments[i] === "function") {
					arguments[i].call(element, element);
				} else if (Array.isArray(arguments[i])) {
					arguments[i].forEach(function(child) {
						element.appendChild(child);
					});
				} else if (arguments[i] && typeof arguments[i] === "object") {
					element.appendChild(arguments[i]);
				} else if (arguments[i]) {
					element.innerHTML = arguments[i].toString();
				} 
			}
			return element;
		}

		function updateSwitch() {
			var editor = document.getElementById("editor");
			var editorHeader = editor && editor.querySelector(".edit-post-header__settings");
		
			if (languageSwitchContainer && languageSwitchContainer.parentNode) {
				languageSwitchContainer.parentNode.removeChild(languageSwitchContainer);
			}
			if (editorHeader && editorHeader.parentNode) {
				languageSwitchContainer = build("ul.gutenberg-language-switch", 
					sublanguage.languages.map(function(language) {
						var isActive = language.slug === sublanguage.current || (!sublanguage.current && language.isMain);
						return build("li"+(isActive ? ".active" : ""),
							build("a.sublanguage", language.name, function() {
								this.href = wp.url.addQueryArgs(location.href, {language:language.slug})
								this.addEventListener("click", function(event) {								
									if (!sublanguage.post_type_options[wp.data.select("core/editor").getCurrentPostType()].gutenberg_metabox_compat) {
										event.preventDefault();
										saveAttributes();
										switchLanguage(language);
										updateSwitch();
									}
								}); 
							})
						)
					})
				);
				editorHeader.parentNode.insertBefore(languageSwitchContainer, editorHeader);
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
			// -> @todo: find a way to hook into "after-post-save" or append current_language directly to rest request
			var edits = wp.data.select("core/editor").getPostEdits();
			if (!edits.meta) {
				edits.meta = {};
			}
			if (!edits.meta.edit_language) {
				edits.meta.edit_language = currentLanguage.slug;
				wp.data.dispatch("core/editor").editPost(edits);
			}
			setTimeout(regenLanguage, 200);
		}
		function init() {
			var meta = wp.data.select("core/editor").getCurrentPostAttribute("meta");
			sublanguage.languages.forEach(function(language) {
				if (language.slug === meta.edit_language) {
					currentLanguage = language;
					saveAttributes();
					switchLanguage(language)
				} else {
					store[language.prefix+"post_content"] = meta[language.prefix+"post_content"];
					store[language.prefix+"post_excerpt"] = meta[language.prefix+"post_excerpt"];
					store[language.prefix+"post_title"] = meta[language.prefix+"post_title"];
					store[language.prefix+"post_name"] = meta[language.prefix+"post_name"];
				}
			});
		}
	
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