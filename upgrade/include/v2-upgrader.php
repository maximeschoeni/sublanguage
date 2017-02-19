<div class="notice notice-info">
	<p><?php echo __( 'Sublanguage is upgrading database', 'sublanguage' ); ?></p>
	<pre id="sublanguage-upgrade-log">Updating Posts...</pre>
</div>
<script>
	jQuery(function() {
    var upgradePosts = function(callback) {
			jQuery.get(ajaxurl, {
				action: "sublanguage_upgrade_get_posts"
			}, function(results) {
				var total = results.length;
				var upgrade = function() {
					if (results.length > 0) {
						var post_ids = results.splice(0, 10);
						jQuery.post(ajaxurl, {
							action: "sublanguage_upgrade_posts",
							post_ids: post_ids
						}, function(r) {
							var percent = Math.ceil(100*(total - results.length)/total);
							document.getElementById("sublanguage-upgrade-log").innerHTML = "Updating Posts: " + percent + "%";
							upgrade();
						});
					} else {
						if (callback) callback();
					}
				}
				upgrade();
			}, "json");
		};
		var upgradeTerms = function(callback) {
			jQuery.get(ajaxurl, {
				action: "sublanguage_upgrade_get_terms"
			}, function(results) {
				var total = results.length;
				var upgrade = function() {
					if (results.length > 0) {
						var term_ids = results.splice(0, 10);
						jQuery.post(ajaxurl, {
							action: "sublanguage_upgrade_terms",
							term_ids: term_ids
						}, function(r) {
							var percent = Math.ceil(100*(total - results.length)/total);
							document.getElementById("sublanguage-upgrade-log").innerHTML = "Updating Terms: " + percent + "%";
							upgrade();
						});
					} else {
						if (callback) callback();
					}
				}
				upgrade();
			}, "json");
		};
		upgradePosts(function() {
			upgradeTerms(function() {
				jQuery.post(ajaxurl, {
					action: "sublanguage_upgrade_done",
				}, function(r) {
					document.getElementById("sublanguage-upgrade-log").innerHTML = "upgrade complete!";
				});
			});
		});
	});
</script>