<?php

?>

<div class="wrap wpsisacm-wrap">

	<h2>Hide ACF Layout</h2>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables">
					<div class="postbox" style="background: none; border: none;">
						<div class="inside">
							<table class="form-table">
								<tbody>
									<tr>
										<th>
											<label>Getting Started</label>
										</th>
										<td>

											<?php
												if ( isset( $_POST['hide_acf_layout_show_module'] ) ) :
													update_option( 'hide_acf_layout_id', absint( $_POST['hide_acf_layout_show_module'] ) );
												endif;
											?>

											<form method='post'>
												<br>
												<br>
												<br>
												<label for="hide_acf_layout_show_module"> Show ACF Field when logged in :</label><br>
												<br>
												<input type="checkbox" id="hide_acf_layout_show_module" name="show_when_logged_in" value="show">
												<br>
												<br>
												<br>
												<br>
												<input type='hidden' name='hide_acf_layout_id' id='hide_acf_layout_id' value='<?php echo get_option( 'hide_acf_layout_id' ); ?>'>
												<input type="submit" name="hide_acf_layout_id" value="Save" class="button-primary">
											</form>
											<?php $my_saved_attachment_post_id = get_option( 'hide_acf_layout_id', 0 ); ?>
											<div id="attachment_id" data-id="<?= $my_saved_attachment_post_id; ?>"></div>

										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div><!-- .post-body-content -->
		</div>
	</div>
</div><!-- end .wpsisacm-wrap -->
