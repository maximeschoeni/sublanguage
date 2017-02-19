(function($) {
	
	var explorer = {
		inputKey: "option_explorer",
		isNode: function(node) {
			return $.isPlainObject(node) || $.isArray(node);
		},
		open: function($li, path, key, node) {
			var nodePath = path.concat([key]);
			var nodeName = this.inputKey + "[" + nodePath.join("][") + "]";
			if (this.isNode(node)) {
				$li.toggleClass("open").find(".handle.dashicons").toggleClass("dashicons-arrow-right dashicons-arrow-down");
				if ($li.children("ul").length == 0) {
					var $ul = $("<ul></ul>");
					jQuery.each(node, function(key, child) {
						var val = explorer.isNode(child) ? ($.isEmptyObject(child) ? 'EMPTY' : 'DATA') : child;
						var $handle = $("<span></span>").addClass("handle");
						var $label = $("<label></label>").text(key);
						var $input = $("<input/>").attr("type", "text").addClass("regular-text").attr("readonly", true).val(val);
						// var $link = $("<a></a>").attr("href", "#").append($handle).append($label).append($input);
						//var $li = $("<li></li>").append($handle).append($label).append($input);
						//var $li = $("<li></li>").append($link);
						var $li = $("<li></li>").append($handle).append($label).append($input);
						$li.on("explorer:open", function(e) {
							explorer.open($li, nodePath, key, child);
							return false;
						});
						if (val !== 'EMPTY' || val === child) $handle.addClass("dashicons dashicons-arrow-right");
						$ul.append($li);
					});
					$li.append($ul);
				}
			} else {
				$li.trigger("explorer:open:endpoint", {
					node: node,
					key: key,
					path: path,
				});
			}
		},
		load: function($ul, url, action) {
			$ul = $("ul.sublanguage-options");
			url = ajaxurl;
			action = "sublanguage_export_options";
			
			return $.ajax({
				url: url,
				data: {action: action},
				success: function(data) {
					$ul.on("click", ".handle, label", function() {
						$li = $(this).closest("li");
						//console.log($(this).parent());
						$li.trigger("explorer:open");
						return false;
					});
					$ul.children("li").each(function() {
						var optionName = $(this).find("label").text();
						$(this).on("explorer:open", function(e) {
							explorer.open($(this), [], optionName, data[optionName]);
							return false;
					
						});
					});
					$ul.addClass("loaded");
				},
				dataType: "json"
			});
			
		},
		
		translation: {},
		translationbaseName: "sublanguage_option_translation",
		formData: {action: "sublanguage_set_option_translation"},
		send: function(input) {
			var data = {
				action: "sublanguage_set_option_translation"
			};
			data[input.name] = input.value;
			var $loadBar = $('<span></span>').addClass("saving").text("...");
			$(input).parent().append($loadBar);
			$.ajax({
				url: ajaxurl,
				data: data,
				method: "POST",
				success: function(data) {
					$loadBar.text("saved").prepend('<span class="dashicons dashicons-yes"></span>');
					setTimeout(function(){ $loadBar.remove() }, 1500);
				}
			});
		},
		openTranslation: function($li, path, key, placeholder) {
			$li.toggleClass("open").find(".handle").toggleClass("dashicons-arrow-right dashicons-arrow-down");
			if (!$li.children("ul").length) {
				var $ul = $("<ul></ul>");
				jQuery.each(sublanguage.languages, function(i, language) {
					var nodePath = [language.id].concat(path, key);
					var id = nodePath.join("-");
					var nodeName = explorer.translationbaseName + "[" + nodePath.join("][") + "]";
					var val = explorer.getTranslation(nodePath);
					var $handle = $("<span></span>").addClass("handle");
					var $label = $("<label></label>").attr("for", id).text(language.name);
					var $input = $("<input/>").attr("type", "text").addClass("regular-text").attr("name", nodeName).attr("placeholder", placeholder).attr("id", id);
// 					.on("blur", function(){
// 						var newValue = $(this).val();
// 						if (val != newValue) {
// 							explorer.send(this);
// 							val = newValue;
// 						}
// 					});
					var $saveBtn = $("<button></button>").html("Save").addClass("button button-small").attr("disabled", true).on("click", function(){
						var newValue = $input.val();
						if (val != newValue) {
							explorer.send($input[0]);
							val = newValue;
							$saveBtn.attr("disabled", true);
						}
					});
					$input.on("change keydown paste input", function(e) {
						var newValue = $input.val();
						if (val != newValue) {
							$saveBtn.attr("disabled", false);
						} else {
							$saveBtn.attr("disabled", true);
						}
					});
					
					var $li = $("<li></li>").append($handle).append($label).append($input).append($saveBtn);
					if (val) $input.val(val);
					$(".handle, label", $li).on("click", function() {
						$(this).siblings("input").focus();
						return false;
					});
					$ul.append($li);
				
				});
				$li.append($ul);
			}
		},
		findTranslation: function(path, translations) {
			var key = path.shift();
			if (typeof translations == 'object' && key in translations) {
				if (path.length) {
					return this.findTranslation(path, translations[key]);
				} else {
					return translations[key];
				}
			}
			return false;
		},
		getTranslation: function(path) {
			return this.findTranslation(path, this.translation);
		},
		loadTranslations: function() {
			return $.ajax({
				url: ajaxurl,
				data: {action: "sublanguage_option_translations"},
				success: function(translationData) {
					explorer.translation = translationData;
					$("ul.sublanguage-options").on("explorer:open:endpoint", "li", function(e, args) {
						explorer.openTranslation($(this), args.path, args.key, args.node);
						return false;
					});
				},
				dataType: "json"
			});
		
		},
		init: function() {
			$.when(
				explorer.load()
			).then(function(optionData) {
				explorer.loadTranslations();
			});
		}
	}
	
	sublanguage.explorer = explorer;
	
	$(document).ready(function() {
    	explorer.init();
	});
	
})(jQuery);