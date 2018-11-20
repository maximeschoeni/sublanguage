
document.addEventListener("DOMContentLoaded", function() {
	
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var isRegistered;
	var attributes = {
		current_language: {
			type: "string"
		}
	};
	
	function getLanguage(slug) {
		for (var i = 0; i < sublanguage.languages.length; i++) {
			if (sublanguage.languages[i].slug === slug) {
				return sublanguage.languages[i];
			}
		}
	}
	
	function registerLanguageManager(props) {
		
		var store = {};
		var currentLanguage;
		var languageSwitchContainer;

		
		function init() {
			
			sublanguage.languages.forEach(function(language) {
				if (language.slug === sublanguage.current) {
					currentLanguage = language;
					saveAttributes();
				} else {
					store[language.prefix+"post_content"] = props.attributes[language.prefix+"post_content"];
					store[language.prefix+"post_excerpt"] = props.attributes[language.prefix+"post_excerpt"];
					store[language.prefix+"post_title"] = props.attributes[language.prefix+"post_title"];
					store[language.prefix+"post_name"] = props.attributes[language.prefix+"post_name"];
				}
			});
			props.setAttributes({
				current_language: sublanguage.current
			});
			updateSwitch();
		}
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
			if (!content || !content.match(new RegExp(sublanguage.gutenberg.reg))) {
				content += sublanguage.gutenberg.tag.replace("%s", language.slug);
			}
			var blocks = wp.blocks.parse( content );
			wp.data.dispatch("core/editor").resetBlocks([]);
			wp.data.dispatch("core/editor").insertBlocks( blocks );
			wp.data.dispatch("core/editor").editPost({
				excerpt: excerpt,
				title: title,
				slug: slug
			});
			var meta = {};
			meta[currentLanguage.prefix+"post_content"] = store[currentLanguage.prefix+"post_content"];
			meta[currentLanguage.prefix+"post_excerpt"] = store[currentLanguage.prefix+"post_excerpt"];
			meta[currentLanguage.prefix+"post_title"] = store[currentLanguage.prefix+"post_title"];
			meta[currentLanguage.prefix+"post_name"] = store[currentLanguage.prefix+"post_name"]
			props.setAttributes(meta);
			
			wp.data.dispatch("core/editor").clearSelectedBlock();
			
			currentLanguage = language;
			sublanguage.current = language.slug;
		}
		
		setTimeout(function() {
			init();
		}, 300);
		isRegistered = true;
	}
	
	sublanguage.languages.forEach(function(language) {
		["post_content", "post_excerpt", "post_title", "post_name"].forEach(function(field) {
			attributes[language.prefix + field] = {
				type: 'string',
				source: 'meta',
				meta: language.prefix + field
			};
		});
	});
	
	registerBlockType('sublanguage/language-manager', {
		title: 'Language Manager',
		icon: 'translation',
		category: 'widgets',
		attributes: attributes,
		edit: function( props ) {			
			if (!isRegistered) {
				registerLanguageManager(props);
			}
			return el("div", {
				className: "sublanguage-language-manager"
			}, 'This block is automatically added by Sublanguage for handling multi-language. It is only visible from admin side. Do not remove!');			
		},
		save: function(props) {
			return null;
		}
	});
});

