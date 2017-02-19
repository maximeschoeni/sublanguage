(function($) {
   	
    $(document).ready(function() {
	
		$.fn.startLoadAnim = function(length) {
			length = 150;
			this.each(function() {
				var t = $(this);
				t.data('text', t.html());
				t.html(".");
				t.data("timer", window.setInterval(function(){
					if (t.html()=="...") t.html("");
					else t.html(t.html()+".");
				}, length));
			});
			return this;
		};
		$.fn.stopLoadAnim = function() {
			this.each(function() {
				var t = $(this);
				clearInterval(t.data("timer"));
				t.html(t.data('text'));
			});
			return this;
		};

		/* Register the buttons */
		tinymce.create('tinymce.plugins.sublanguage', {
			init : function(ed, url) {
				ed.addButton( 'sublanguage', {
					title : 'Show translations',
					icon : 'icon dashicons-translation dashicons-before',
					cmd : 'sublanguage_cmd'
				});
				ed.addCommand( 'sublanguage_cmd', function(ui, v) {
					var updateSlug = function(slug) {
						var old = $("#editable-post-name").text();
						$("#editable-post-name,#editable-post-name-full").text(slug); // whats about child pages?
						$("input[name=post_name]").val(slug);
						// need to update view post button...
						//$("#view-post-btn a").attr("href",... ) 
					}
					var updateContent = function(content) {
						ed.setContent(content.replace(/\n/g, "<br />"));
					}
					var updateTitle = function(title) {
						$("input[name=post_title]").val(title);
					}
					var updateExcerpt = function(excerpt) {
						$("textarea[name=excerpt]").text(excerpt);
					} 
					var activeTab = 0;
					var tabs = [];
					for (var i in sublanguageTranslations) {
						var t = sublanguageTranslations[i];
						if (t.ls == sublanguage.current) {
							t.c = ed.save();
							t.t = jQuery("input[name=post_title]").val();
							t.n = jQuery("#editable-post-name").text();
							activeTab = i;
						}
						var tab = {
							title: t.l,
							type: "form",
							name: t.ls,
							classes: "lng",
							minWidth: 500,
							padding: 0,
							spacing: 20,
							items: [
								{
									type: "label"							
								},
								{
									type: "textbox",
									name: "title",
									value: t.t,
									tooltip: "Title",
								},
								{
									type: "textbox",
									name: "content",
									value: t.c,
									multiline: true,
									minHeight: 250,
									tooltip: 'Content'
								},
								{
									type: "textbox",
									name: "slug",
									value: t.n,
									tooltip: "Slug"
								},
								{
									type: "button",
									text: "Save",
									maxWidth: 100,
									onClick: function(){
										
										var container = this.parent();
										var lng = container.name();
										var fields = container.toJSON(); //exportFields(container);
										if (lng == sublanguage.current) {
											updateSlug(fields.slug);
											updateContent(fields.content);
											updateTitle(fields.title);
											if ("excerpt" in fields) updateExcerpt(fields.excerpt);
										}
										var btn = this;
										var $btn = $(btn.getEl()).find("button").startLoadAnim();
										btn.disabled(true);
										var data = {
											"action": "sublanguage_save_translation", 
											"id": currentPostId, 
											"translations": [{
												"lng": lng,
												"fields": fields
											}]
										};
										$.post(ajaxurl, data, function(response) {
											console.log(response);
											$btn.stopLoadAnim();
											btn.disabled(false);
											for (i in response) {
												container.find("#slug").value(response[i].slug);
												if (response[i].lng == sublanguage.current) {
													updateSlug(response[i].slug);
												}
											}
										}, "json");
									}
								}
							]
						};
						if ("e" in t) {
							tab.items.splice(3, 0, {
								type: "textbox",
								name: "excerpt",
								value: t.e,
								multiline: true,
								minHeight: 50,
								tooltip: 'Excerpt'
							});
						}
						tabs.push(tab);
					}
					
					//open the popup
					ed.windowManager.open( {
						title: 'Translations',
						body: [{
							"title": "Settings",
							"type"      : 'tabpanel',
							"activeTab" : activeTab,
							"items": tabs
						}],
	// 					bodyType: 'tabpanel',
	// 					body:tabs,
						onsubmit: function( e ) { //when the ok button is clicked
							var translations = [];
							this.find(".lng").each(function(panel){
								var lng = panel.name();
								var fields = panel.toJSON(); //exportFields(panel);
								if (lng == sublanguage.current) {
									updateSlug(fields.slug);
									updateContent(fields.content);
									updateTitle(fields.title);
									if ("excerpt" in fields) updateExcerpt(fields.excerpt);
								}
								translations.push({
									"lng": lng,
									"fields": fields
								});
							});
							var data = {
								"action": "sublanguage_save_translation", 
								"id": currentPostId, 
								"translations": translations
							};
							$.post(ajaxurl, data, function(response) {
								for (i in response) {
									if (response[i].lng == sublanguage.current) {
										updateSlug(response[i].slug);
									}
								}
							}, "json");
						}
					});
				});
			},
			createControl : function(n, cm) {
			   return null;
			},
		});
	 
		/* Start the buttons */
		tinymce.PluginManager.add('sublanguage', tinymce.plugins.sublanguage);
	});
})(jQuery);