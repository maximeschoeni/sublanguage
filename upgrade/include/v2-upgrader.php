<div class="notice notice-info">
	<p><?php echo __( 'Sublanguage 2.0 needs to upgrade database.', 'sublanguage' ); ?></p>
	<pre id="sublanguage-upgrade-log">Verifying database...</pre>
</div>
<script>
	jQuery(function() {
		var all_post_ids = <?php echo json_encode($post_ids); ?>;
		var all_term_ids = <?php echo json_encode($term_ids); ?>;
		
    var upgradePosts = function(callback) {
			var total = all_post_ids.length;
			var upgrade = function() {
				if (all_post_ids.length > 0) {
					var post_ids = all_post_ids.splice(0, 10);
					jQuery.post(ajaxurl, {
						action: "sublanguage_upgrade_posts",
						post_ids: post_ids
					}, function(r) {
						var percent = Math.ceil(100*(total - all_post_ids.length)/total);
						document.getElementById("sublanguage-upgrade-log").innerHTML = "Updating Posts: " + percent + "%";
						upgrade();
					});
				} else {
					if (callback) callback();
				}
			}
			upgrade();
		};
		var upgradeTerms = function(callback) {
			var total = all_term_ids.length;
			var upgrade = function() {
				if (all_term_ids.length > 0) {
					var term_ids = all_term_ids.splice(0, 10);
					jQuery.post(ajaxurl, {
						action: "sublanguage_upgrade_terms",
						term_ids: term_ids
					}, function(r) {
						var percent = Math.ceil(100*(total - all_term_ids.length)/total);
						document.getElementById("sublanguage-upgrade-log").innerHTML = "Updating Terms: " + percent + "%";
						upgrade();
					});
				} else {
					if (callback) callback();
				}
			}
			upgrade();
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