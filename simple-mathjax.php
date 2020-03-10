<?php
/*
 * Plugin Name: Simple Mathjax
 * Description: Load the mathjax scripts across your wordpress blog
 * Version: 2.0
 * Author: Samuel Coskey, Peter Krautzberger, Christian Lawson-Perfect
 * Author URI: https://boolesrings.org
*/


$default_options = array(
  'major_version' => 3,
  'mathjax_in_admin' => false,
  'custom_mathjax_cdn' => '',
  'custom_mathjax_config' => '',
  'latex_preamble' => ''
);
$default_configs = array(
  2 => "MathJax.Hub.Config({\n  tex2jax: {\n    inlineMath: [['$','$'], ['\\\\(','\\\\)']],\n    processEscapes: true,\n    ignoreHtmlClass: 'tex2jax_ignore|editor-rich-text'\n  }\n});\n",
  3 => "MathJax = {\n  tex: {\n    inlineMath: [['$','$'],['\\\\(','\\\\)']], \n    processEscapes: true\n  },\n  options: {\n    ignoreHtmlClass: 'tex2jax_ignore|editor-rich-text'\n  }\n};\n"
);
$default_cdns = array(
  2 => "//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-MML-AM_CHTML,Safe.js",
  3 => "//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"
);

function load_options() {
  global $default_options;
  $options = array_merge($default_options,array());

  // restore options from old versions of this plugin
  $old_keys = array('custom_mathjax_cdn', 'custom_mathjax_config', 'latex_preamble', 'mathjax_in_admin');
  $has_old_values = false;
  foreach($old_keys as $key) {
    if(($value = get_option($key)) !== false) {
      $options[$key] = $value;
      $has_old_values = true;
    }
  }


  // apply options set locally
  $set_options = get_option('simple_mathjax_options');
  if(is_array($set_options)) {
    foreach($set_options as $key=>$value) {
      if(array_key_exists($key,$options)) {
        $options[$key] = $value;
      }
    }
  }

  if($has_old_values) {
    $options['major_version'] = 2;
    foreach($old_keys as $key) {
      delete_option($key);
    }
    add_option('simple_mathjax_options',$options);
  }

  return $options;
}


/*
 * inserts the mathjax configuration
 */

add_action('wp_head','configure_mathjax',1);
function configure_mathjax() {
  global $default_configs;
  $options = load_options();
  $version = $options['major_version'];
  $custom_config = wp_kses( $options['custom_mathjax_config'], array() );
  $config = $custom_config ? $custom_config : $default_configs[$version];
  if($version==2) {
    echo "\n<script type='text/x-mathjax-config'>\n{$config}\n</script>\n";
  } else {
    echo "\n<script>\n{$config}\n</script>\n";
  }
}

/*
 * loads mathjax itself
*/
add_action('wp_enqueue_scripts', 'add_mathjax');
function add_mathjax() {
  global $default_cdns;
  $options = load_options();
  $version = $options['major_version'];
  $custom_cdn = esc_url( $options['custom_mathjax_cdn'] );
  $cdn = $custom_cdn ? $custom_cdn : $default_cdns[$version];
  wp_enqueue_script('mathjax', $cdn, array(), false, true);
}

/*
 * inserts the mathjax preamble inside the body and above the content
*/
add_action('wp_footer', 'add_preamble_adder');
function add_preamble_adder() {
  $options = load_options();
  $version = $options['major_version'];
  $preamble = $options['latex_preamble'];
  if ( $preamble ) {
    if($version==2) {
      $preamble = preg_replace('/\\\\/','\\\\\\\\',$preamble);

?>
<script>
  (function() {
    var newContainer = document.createElement('span');
    newContainer.style.setProperty("display","none","");
    var newNode = document.createElement('script');
    newNode.type = "math/tex";
    var preamble = '<?php echo esc_js($preamble); ?>';
    newNode.innerHTML = preamble;
    newContainer.appendChild(newNode);
    document.body.insertBefore(newContainer,document.body.firstChild);
  })();
</script>
<?php

    } else if($version==3) {

?>
<script>
  (function() {
    var newNode = document.createElement('span');
    newNode.style.setProperty("display","none","");
    var preamble = '\\( <?= esc_js(addslashes($preamble)); ?> \\)';
    newNode.innerHTML = preamble;
    document.body.insertBefore(newNode,document.body.firstChild);
  })();
</script>
<?php

    }
  }
}

/*
 * Perform all three actions in admin pages too, if the option is set (CP)
 */
$options = load_options();
if ( $options['mathjax_in_admin'] ) {
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
    'simple_mathjax_options',  // Menu slug, used to uniquely identify the page
    'simple_mathjax_options_page'    // Function that renders the options page
  );

  if ( ! $simple_mathjax_page )
    return;

  //call register settings function
  add_action( 'admin_init', 'register_simple_mathjax_settings' );
}

function simple_mathjax_options_page() {
?>
<div>
<h1>Simple Mathjax options</h1>
<form method="post" action="options.php">
  <?php settings_fields( 'simple_mathjax_options' ); ?>
  <?php do_settings_sections('simple_mathjax'); ?>

  <button type="submit"><?php _e('Save Changes'); ?></button>
</form>
</div>
<?php }

function register_simple_mathjax_settings() {
  global $default_options;
  register_setting(
    'simple_mathjax_options', 
    'simple_mathjax_options', 
    array(
      'sanitize_callback' => 'simple_mathjax_options_validate'
    )
  );

  add_settings_section(
    'simple_mathjax_main', 
    'Main Settings', 
    'simple_mathjax_main_text', 
    'simple_mathjax'
  );
  add_settings_section('simple_mathjax_config',
    'Configuration',
    'simple_mathjax_config_text',
    'simple_mathjax'
  );

  add_settings_field(
    'major_version',
    'MathJax major version',
    'simple_mathjax_major_version_input',
    'simple_mathjax',
    'simple_mathjax_main',
    array(
      'label_for' => 'major_version'
    )
  );
  add_settings_field(
    'mathjax_in_admin',
    'Load MathJax on admin pages?',
    'simple_mathjax_in_admin_input',
    'simple_mathjax',
    'simple_mathjax_main',
    array(
      'label_for' => 'mathjax_in_admin'
    )
  );

  add_settings_field(
    'custom_mathjax_cdn',
    'Custom MathJax CDN',
    'simple_mathjax_cdn_input',
    'simple_mathjax',
    'simple_mathjax_config',
    array(
      'label_for' => 'custom_mathjax_cdn'
    )
  );
  add_settings_field(
    'custom_mathjax_config',
    'Custom MathJax config',
    'simple_mathjax_config_input',
    'simple_mathjax',
    'simple_mathjax_config',
    array(
      'label_for' => 'custom_mathjax_config'
    )
  );
  add_settings_field(
    'latex_preamble',
    'Custom LaTeX preamble',
    'simple_mathjax_latex_preamble_input',
    'simple_mathjax',
    'simple_mathjax_config',
    array(
      'label_for' => 'latex_preamble'
    )
  );

}

function simple_mathjax_options_validate($options) {
  global $default_options;
  $cleaned = array();
  foreach($default_options as $key => $value) {
    $cleaned[$key] = $options[$key];
  }
  $cleaned['mathjax_in_admin'] = $cleaned['mathjax_in_admin'] ? true : false;
  return $cleaned;
}

function simple_mathjax_main_text() {
}

function simple_mathjax_config_text() {
}

function simple_mathjax_major_version_input() {
  $options = load_options();
?>
  <select id="major_version" name="simple_mathjax_options[major_version]">
    <option value="2" <?= $options['major_version']==2 ? 'selected' : '' ?>>2</option>
    <option value="3" <?= $options['major_version']==3 ? 'selected' : '' ?>>3</option>
  </select>
  <p>MathJax versions 2 and 3 work very differently. See the <a href="http://docs.mathjax.org/en/latest/upgrading/v2.html">MathJax documentation</a>.</p>
<?php
}

function simple_mathjax_in_admin_input() {
  $options = load_options();
?>
  <input type="checkbox" id="mathjax_in_admin" name="simple_mathjax_options[mathjax_in_admin]" <?= $options['mathjax_in_admin'] ? 'checked' : '' ?>>
  <p>If you tick this box, MathJax will be loaded on admin pages as well as the actual site.</p>
<?php
}

function simple_mathjax_cdn_input() {
  global $default_cdns;
  $options = load_options();
?>
  <input type="text" id="custom_mathjax_cdn" size="50" name="simple_mathjax_options[custom_mathjax_cdn]" value="<?= $options['custom_mathjax_cdn'] ?>">
  <p>If you leave this blank, the default will be used, depending on the major version of MathJax:</p>
  <dl>
    <dt>Version 2</dt>
    <dd><code><?= $default_cdns[2] ?></code></dd>
    <dt>Version 3</dt>
    <dd><code><?= $default_cdns[3] ?></code></dd>
  </dl>
<?php
}

function simple_mathjax_config_input() {
  global $default_configs;
  $options = load_options();
?>
  <textarea id="custom_mathjax_config" cols="50" rows="10" name="simple_mathjax_options[custom_mathjax_config]"><?= $options['custom_mathjax_config'] ?></textarea>
  <p>This text will be used to configure MathJax. See <a href="https://docs.mathjax.org/en/latest/options/index.html">the documentation on configuring MathJax</a>.</p>
  <p>If you leave this blank, the default will be used, according to the major version of MathJax:</p>
  <dl>
    <dt>Version 2</dt>
    <dd><pre><?= $default_configs[2] ?></pre></dd>
    <dt>Version 3</dt>
    <dd><pre><?= $default_configs[3] ?></pre></dd>
  </dl>
<?php
}

function simple_mathjax_latex_preamble_input() {
  $options = load_options();
?>
  <textarea id="latex_preamble" cols="50" rows="10" name="simple_mathjax_options[latex_preamble]"><?= $options['latex_preamble'] ?></textarea>
  <p>This LaTeX will be run invisibly before any other LaTeX on the page. A good place to put \newcommand's and \renewcommand's</p>
  <p><strong>Do not us $ signs</strong>, they will be added for you</p>
  <p>E.g.</p>
  <pre>\newcommand{\NN}{\mathbb N}
\newcommand{\abs}[1]{\left|#1\right|}</pre>
<?php
}
