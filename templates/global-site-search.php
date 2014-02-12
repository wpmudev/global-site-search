<?php global $current_site, $members_directory_base ?>

<?php global_site_search_form() ?>

<?php if ( network_have_posts() ) : ?>
	<div class="gssnav"><?php echo global_site_search_get_pagination() ?></div>

	<style type="text/css">
		.gss-results tr:first-child td {
			border-bottom-style: solid;
			border-bottom-color: <?php echo global_site_search_get_border_color() ?>;
			border-bottom-width: 1px;
		}

		.gss-results tr:nth-child(odd) td {
			background-color: <?php echo global_site_search_get_background_color() ?>;
		}

		.gss-results tr:not(:first-child) td {
			padding-top: 10px;
			text-align: left;
			vertical-align: top;
		}

		.gss-results tr:not(:first-child) td:first-child {
			text-align: center;
		}

		.gss-results a {
			text-decoration: none;
		}
	</style>

	<div style="float:left; width:100%">
		<table class="gss-results" border="0" width="100%">
			<tr>
				<td width="10%">&nbsp;</td>
				<td style="text-align: center;" width="90%">
					<strong><?php esc_html_e( 'Posts', 'globalsitesearch' ) ?></strong>
				</td>
			</tr>

			<?php $tic_toc = 'toc'; ?>
			<?php $substr = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr'; ?>

			<?php while ( network_have_posts() ) : network_the_post(); ?>
				<?php $author_id = network_get_the_author_id(); ?>
				<?php $the_author = get_user_by( 'id', $author_id ); ?>
				<?php $post_author_display_name = $the_author ? $the_author->display_name : __( 'Unknown', 'globalsitesearch' ); ?>

				<tr>
					<td>
						<a href="<?php echo network_get_permalink() ?>"><?php echo get_avatar( $author_id, 32 ) ?></a>
					</td>
					<td>
						<?php if ( function_exists( 'members_directory_site_admin_options' ) ) : ?>
							<strong>
								<a href="http://<?php echo $current_site->domain . $current_site->path . $members_directory_base . '/' . $the_author->user_nicename ?>/">
									<?php echo $post_author_display_name ?>
								</a>
								<?php _e( 'wrote', 'globalsitesearch' ) ?>:
							</strong>
						<?php else : ?>
							<strong><?php echo $post_author_display_name ?> <?php _e( 'wrote', 'globalsitesearch' ) ?>:</strong>
						<?php endif; ?>

						<a href="<?php echo network_get_permalink() ?>">
							<strong><?php network_the_title() ?></strong>
						</a><br>
						<?php $the_content = preg_replace( "~(?:\[/?)[^/\]]+/?\]~s", '', network_get_the_content() ); ?>
						<?php echo $substr( strip_tags( $the_content ), 0, 250 ) ?> (<a href="<?php echo network_get_permalink() ?>"><?php _e( 'More', 'globalsitesearch' ) ?></a>)
					</td>
				</tr>
			<?php endwhile; ?>
		</table>
	</div>

	<div class="gssnav"><?php echo global_site_search_get_pagination() ?></div>
<?php else : ?>
	<p style="text-align:center"><?php _e( 'Nothing found for search term(s).', 'globalsitesearch' ) ?></p>
<?php endif; ?>