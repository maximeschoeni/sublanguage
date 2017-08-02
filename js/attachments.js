(function($) {
   
    $(document).ready(function() {
    	
		var languageSelector = {
			originalLanguage: sublanguage.current,
			create: function (model) {
				if (this.model) {
					this.save(this.model);
				}
				this.model = model;
				if (!this.$el) {
					this.$el = $('<div></div>').addClass("sublanguage-selector");
					for (i in sublanguage.languages) {
						var classes = sublanguage.languages[i].slug==sublanguage.current ? "active" : "";
						$('<a></a>').text(sublanguage.languages[i].name).attr("href", "#").attr("data-language", sublanguage.languages[i].slug).addClass(classes).appendTo(this.$el);
					}
				}
				var selector = this;
				this.$el.on("click", "a", function(){
					$(this).siblings().removeClass("active");
					$(this).addClass("active");
					selector.update($(this).data("language"));
					return false;
				});
				return this;
			},
			init: function() {
				this.$el.find("a").removeClass("active").filter(function(){
					return sublanguage.current == $(this).data("language");
				}).addClass("active");
				this.model.set(this.model.translations[sublanguage.current]);
			},
			save: function(model) {
				model.translations[sublanguage.current] = {
					"title": model.get("title"),
					"alt": model.get("alt"),
					"caption": model.get("caption"),
					"description": model.get("description"),
				};
			},
			update: function(language) {
				this.save(this.model);
				sublanguage.current = language;
				this.init();
				return this;
			}
		}
	
		// Add language switch on attachment details
		if (wp.media.view.Attachment.Details) {
			var DetailsAncestor = wp.media.view.Attachment.Details;
			wp.media.view.Attachment.Details = DetailsAncestor.extend({
				render: function() {
					DetailsAncestor.prototype.render.apply(this, arguments);
					languageSelector.create(this.model).init();
					this.$el.find(".attachment-info").after(languageSelector.$el);
				}
			});
		}
    
		// Add language switch on upload.php page
		if (wp.media.view.Attachment.Details.TwoColumn) {
			var renderTwoColumn = wp.media.view.Attachment.Details.TwoColumn.prototype.render;
			_.extend(wp.media.view.Attachment.Details.TwoColumn.prototype, {
				render: function() {
					renderTwoColumn.apply(this, arguments);
					languageSelector.create(this.model).init();
					this.$el.find(".details").after(languageSelector.$el);
				}
			});
		}
    	
    	// initialize attachment
    	if (wp.media.model.Attachment) {
				var initializeAttachment = wp.media.model.Attachment.prototype.initialize;
				_.extend( wp.media.model.Attachment.prototype, {
					initialize: function( data, options ) {
						initializeAttachment.apply(this, arguments);
						this.translations = {};
						
						for (i in sublanguage.languages) {
							this.translations[sublanguage.languages[i].slug] = {
								"title": "",
								"alt": "",
								"caption": "",
								"description": "",							
							};
						}
						var onLoad = function (model) {
							model.off("change", onLoad);
							if (model.has("sublanguage")) {
								this.translations = model.get("sublanguage");
								model.unset("sublanguage"); 
							}
						}
						this.on('change', onLoad);
					}
				});
    	}
    	
    	// Reset language before sending to editor
    	if (wp.media.editor.send) {
			var sendAttachment = wp.media.editor.send.attachment;
			_.extend(wp.media.editor.send, {
				attachment: function() {
					languageSelector.update(languageSelector.originalLanguage);
					return sendAttachment.apply(this, arguments);
				}
			});
		}
	});
    
})(jQuery);