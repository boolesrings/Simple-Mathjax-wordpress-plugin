<?php
/*
 * Plugin Name: Simple Mathjax
 * Description: Load the mathjax scripts across your wordpress blog
 * Version: 1.0
 * Author: Samuel Coskey, Peter Krautzberger
 * Author URI: http://boolesrings.org
*/


/*
 * inserts the mathjax configuration
 */
add_action('wp_head','configure_mathjax',1);
function configure_mathjax() {
  $custom_config = wp_kses( get_option('custom_mathjax_config'), array() );
  $config = $custom_config ? $custom_config : "MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$'], ['\\\\(','\\\\)']], processEscapes: true}});\n";
  echo "\n<script type='text/x-mathjax-config'>\n" . $config . "</script>\n";
}

/*
 * loads mathjax itself
*/
add_action('wp_enqueue_scripts', 'add_mathjax');
function add_mathjax() {
  $custom_cdn = esc_url( get_option('custom_mathjax_cdn') );
  $cdn = $custom_cdn ? $custom_cdn : "//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.0/MathJax.js?config=TeX-MML-AM_CHTML,Safe.js";

  wp_enqueue_script('mathjax', $cdn);
}

/*
 * inserts the mathjax preamble inside the body and above the content
*/
add_action('wp_footer', 'add_preamble_adder');
function add_preamble_adder() {
  $preamble = get_option('latex_preamble');
  if ( $preamble ) {
    $preamble = preg_replace('/\\\\/','\\\\\\\\',$preamble);
?>
<script type='text/javascript'>
  newContainer = document.createElement('span');
  newContainer.style.setProperty("display","none","");
  newNode = document.createElement('script');
  newNode.type = "math/tex";
  newNode.innerHTML = '<?php echo esc_js($preamble); ?>';
  newContainer.appendChild(newNode);
  document.body.insertBefore(newContainer,document.body.firstChild);
</script>
<?php
  }
}

/*
 * Perform all three actions in admin pages too, if the option is set (CP)
*/
if ( get_option( 'mathjax_in_admin' ) ) {
	add_action('admin_head', 'configure_mathjax', 1);
	add_action('admin_enqueue_scripts', 'add_mathjax');
	add_action('admin_footer', 'add_preamble_adder');
}


/*
 * The options pane in the settings section
*/
add_action('admin_menu', 'mathjax_create_menu');
function mathjax_create_menu() {
  $simple_mathjax_page = add_options_page(
    'Simple MathJax options',  // Name of page
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
  register_setting( 'simple_mathjax_group', 'mathjax_in_admin' );
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
	<td><p>If you leave this blank, the default will be used: <code>http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML,Safe.js</code></p></td>
        </tr>
        <tr valign="top">
        <th scope="row">Custom mathjax config</th>
        <td><textarea name="custom_mathjax_config" cols="50" rows="10"/><?php echo esc_textarea(get_option('custom_mathjax_config')); ?></textarea></td>
	<td><p>This text will be placed inside the <code>&lt;script x-mathjax-config&gt;</code> tag</p><p>If you leave this blank, the default will be used: <code>MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]}});</code></p></td>
        </tr>
        <tr valign="top">
        <th scope="row">Custom LaTeX preamble</th>
        <td><textarea name="latex_preamble" cols="50" rows="10"/><?php echo esc_textarea(get_option('latex_preamble')); ?></textarea></td>
	<td><p>A good place to put \newcommand's and \renewcommand's</p><p><strong>Do not us $ signs</strong>, they will be added for you</p><p>E.g.<br/><code>\newcommand{\NN}{\mathbb N}<br/>\newcommand{\abs}[1]{\left|#1\right|}</code></p></td>
        </tr>
	<tr valign="top">
	<th scope="row">Load MathJax on admin pages</th>
	<td><input type="checkbox" name="mathjax_in_admin" value="yes" <?php if( get_option('mathjax_in_admin') ) { echo "checked"; } ?> /></td>
	<td><p>If you tick this box, MathJax will be loaded on admin pages as well as the actual site.</p></td>
	</tr>
      </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>
</div>
<?php }


?>
