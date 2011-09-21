<?php
/*
 * Plugin Name: Simple Mathjax
 * Description: Load the mathjax scripts across your wordpress blog
 * Version: 0.0
 * Author: Samuel Coskey, Peter Krautzberger
 * Author URI: http://boolesrings.org
*/

/*
 * inserts the mathjax configuration and script call
*/
function add_mathjax() {
  $custom_cdn = esc_url( get_option('custom_mathjax_cdn') );
  $custom_config = wp_kses( get_option('custom_mathjax_config'), array() );
  echo "\n<script type='text/x-mathjax-config'>\n";
  if ( $custom_config ) {
    echo $custom_config;
  } else {
    // note that's supposed to be \\ but since we're in "" we need to redouble
    echo "MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$'], ['\\\\(','\\\\)']]}});\n";
  }
  echo "</script>\n";
  echo "<script type='text/javascript' src='";
  if ( $custom_cdn ) {
    echo $custom_cdn;
  } else {
    echo "http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML";
  }
  echo "'></script>\n";
}

/*
 * inserts an invisible math-mode span at the top of the page.  good place for newcommands.
*/
function add_preamble() {
  $preamble = get_option('latex_preamble');
  if ( $preamble ) {
    echo '<span style="display:none">';
    echo '\(';
    echo esc_html($preamble);
    echo '\)';
    echo '</span>';
  }
}

/*
 * The options pane in the settings section
*/
add_action('admin_menu', 'mathjax_create_menu');
function mathjax_create_menu() {
  $simple_mathjax_page = add_options_page(
    'simple_mathjax_prefpane',  // Name of page
    'Simple Mathjax',           // Label in menu
    'manage_options',           // Capability required
    'simple_mathjax_identifier',  // Menu slug, used to uniquely identify the page
    'simple_mathjax_options'    // Function that renders the options page
  );

  if ( ! $simple_mathjax_page )
    return;

  //call register settings function
  add_action( 'admin_init', 'register_simple_mathjax_settings' );
}
function register_simple_mathjax_settings() {
  register_setting( 'simple_mathjax_group', 'custom_mathjax_cdn' );
  register_setting( 'simple_mathjax_group', 'custom_mathjax_config' );
  register_setting( 'simple_mathjax_group', 'latex_preamble' );
}
function simple_mathjax_options() {
?>
<div class="wrap">
<h2>Simple Mathjax options</h2>
<p>
  (Please note that this still needs to be tested.  There may be a problem with string-escaping.  For instance, I'm not sure if special characters such as &lt; will be properly processed.)
</p>
<form method="post" action="options.php">
    <?php settings_fields( 'simple_mathjax_group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Custom mathjax CDN</th>
        <td><input type="text" name="custom_mathjax_cdn" size="50" value="<?php echo esc_url( get_option('custom_mathjax_cdn') ); ?>" /></td>
	<td><p>If you leave this blank, the default will be used: <code>http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML</code></p></td>
        </tr>
        <tr valign="top">
        <th scope="row">Custom mathjax config</th>
        <td><textarea name="custom_mathjax_config" cols="50" rows="10"/><?php echo esc_textarea(get_option('custom_mathjax_config')); ?></textarea></td>
	<td><p>This text will be placed inside the <code>&lt;script x-mathjax-config&gt;</code> tag (see <a href="http://www.mathjax.org/docs/1.1/configuration.html#using-in-line-configuration-options">here</a> for details)</p><p>If you leave this blank, the default will be used: <code>MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]}});</code></p></td>
        </tr>
        <tr valign="top">
        <th scope="row">Custom LaTeX preamble</th>
        <td><textarea name="latex_preamble" cols="50" rows="10"/><?php echo esc_textarea(get_option('latex_preamble')); ?></textarea></td>
	<td><p>A good place to put \newcommand's and \renewcommand's</p><p><strong>Do not us $ signs</strong>, they will be added for you</p><p>E.g.<br/><code>\newcommand{\NN}{\mathbb N}<br/>\newcommand{\abs}[1]{\left|#1\right|}</code></p></td>
        </tr>
    </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>
</div>
<?php }

add_action('wp_footer', 'add_mathjax');
add_action('loop_start', 'add_preamble');

?>
