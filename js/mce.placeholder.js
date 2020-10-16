(function () {
	tinymce.PluginManager.add( 'placeholder', function ( editor ) {
		editor.on( 'init', function () {
			var label = new Label;

			tinymce.DOM.bind( label.el, 'click', onFocus );

			// When focus is in main editor window
			editor.on( 'focus', onFocus );

			// When focus is outside of main editor area
			editor.on( 'blur', onBlur );

			// Whenever content is changed, including when a toolbar item is pressed (bold, italic, bullets, etc)
			editor.on( 'change', onChange );

			// Called when switching between Visual/Text
			editor.on( 'setcontent', onSetContent );

			// Whenever advanced toolbar toggled on/off
			editor.on( 'wp-toolbar-toggle', onToolbarToggle );

			function onFocus() {
				label.hide();
				tinyMCE.execCommand( 'mceFocus', false, editor );
			}

			function onBlur() {
				label.check();
			}

			function onChange() {
				label.check();
			}

			function onToolbarToggle() {
				label.setPosition();
			}

			function onSetContent() {
				label.check();
			}

			// Add 1 second timeout to delay execution until after
			// WordPress adjusts the toolbars
			setTimeout( function () {
				label.check();
			}, 1000 );

		} );

		var Label = function () {
			// Create label el
			this.text = editor.getElement().getAttribute( "placeholder" );
			this.contentAreaContainer = editor.getContentAreaContainer();

			tinymce.DOM.setStyle( this.contentAreaContainer, 'position', 'relative' );
			
			attrs = {id: 'mce-placeholder-plugin-label', style: {position: 'absolute', top: '67px', left: 0, color: '#888', padding: '9px 10px', width: '98%', overflow: 'hidden', display: 'none' }};
			this.el = tinymce.DOM.add( this.contentAreaContainer, "label", attrs, this.text );
		};

		Label.prototype.check = function () {

			// Strip out any <p> or <br> HTML elements from iframe contents (to check if editor has value but not saved to mce)
			var actual_html = editor.getBody().innerHTML.replace( /<[\/]{0,1}(p|br)[^><]*>/ig, "" );
			
			// isDirty() only returns false when there are 0 undo actions available, meaning the user has done nothing with the editor.
			// The problem with that is the user could, as an example, add a bulleted list, then remove it.  Content area will be empty,
			// but there is now 1 undo action, which causes isDirty() to return TRUE.
			//
			// If you want to check just to see if there is a value in the content area, replace the if line with this:
			//if ( editor.getContent() == '' && ( typeof( actual_html ) == 'undefined' || actual_html == null || actual_html == '' ) ){
			if ( editor.getContent() == '' && editor.isDirty() == false ) {
				this.show();
			} else {
				this.hide();
			}

		}

		Label.prototype.setPosition = function () {
			var padding_top = tinymce.DOM.getStyle( this.contentAreaContainer, 'padding-top' );

			if ( padding_top ) {
				tinymce.DOM.setStyle( this.el, 'top', padding_top );
			}

		}

		Label.prototype.hide = function () {
			tinymce.DOM.setStyle( this.el, 'display', 'none' );
		};

		Label.prototype.show = function () {
			this.setPosition();
			tinymce.DOM.setStyle( this.el, 'display', '' );
		}

	} );
})();
