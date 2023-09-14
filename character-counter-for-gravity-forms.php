<?php
/*
Plugin Name: Character Counter for Gravity Forms
Description: Adds a character count feature to Gravity Forms.
Version: 0.2.11
Author: Artur Nalobin
Plugin URI: https://github.com/scriptvoyager/character-counter-for-gravity-forms/
*/

// Adding backend settings in Gravity Forms for "Show Character Count" and the recommendation
add_action( 'gform_field_standard_settings', 'my_custom_field_standard_settings', 10, 2 );
function my_custom_field_standard_settings( $position, $form_id ) {
    if ( $position == 50 ) {
        ?>
        <li class="show_character_count_setting field_setting">
            <!-- Horizontal line before the new settings -->
            <hr>
            <!-- This is a manually added setting -->
            <p>Character Counter for Gravity Forms</p>
            <input type="checkbox" id="field_show_character_count" />
            <label for="field_show_character_count" class="inline">
                Show character count below the field
            </label>
            <br><br>
            <label for="field_character_count_recommendation">
                Recommendation for maximum character count (e.g., 1000)
            </label>
            <input type="text" id="field_character_count_recommendation" size="5" />
            <!-- Horizontal line after the new settings with greater top margin -->
            <hr style="margin-top: 20px;">
        </li>
        <?php
    }
}

// Adding JavaScript to the Gravity Forms editor to save field settings
add_action( 'gform_editor_js', 'my_custom_editor_js' );
function my_custom_editor_js() {
    ?>
    <script>
        fieldSettings.text += ', .show_character_count_setting';
        fieldSettings.textarea += ', .show_character_count_setting';

        jQuery(document).bind("gform_load_field_settings", function(event, field, form){
            var showCharacterCount = field.hasOwnProperty('showCharacterCount') ? field.showCharacterCount : false;
            jQuery("#field_show_character_count").prop("checked", showCharacterCount);

            var characterCountRecommendation = field.hasOwnProperty('characterCountRecommendation') ? field.characterCountRecommendation : '';
            jQuery("#field_character_count_recommendation").val(characterCountRecommendation);
        });

        jQuery(document).on("change", "#field_show_character_count", function(){
            SetFieldProperty("showCharacterCount", this.checked);
        });

        jQuery(document).on("input", "#field_character_count_recommendation", function(){
            SetFieldProperty("characterCountRecommendation", this.value);
        });
    </script>
    <?php
}

// Set the default value for showCharacterCount to "false"
add_action( 'gform_editor_js_set_default_values', 'my_gform_editor_js_set_default_values' );
function my_gform_editor_js_set_default_values() {
    ?>
    field.showCharacterCount = false; // Set default value to "false"
    <?php
}

// Display character count and change color on the frontend based on the specified recommendation
add_filter( 'gform_field_content', 'my_custom_gform_field_content', 10, 5 );
function my_custom_gform_field_content( $content, $field, $value, $lead_id, $form_id ) {
    if ( isset( $field['showCharacterCount'] ) && $field['showCharacterCount'] ) {
        $recommendation = isset($field['characterCountRecommendation']) ? $field['characterCountRecommendation'] : 0;

        $script = "
        <script>
        jQuery(document).ready(function($) {
            var inputField = $('#input_{$form_id}_{$field['id']}');
            var charCountSpan = $('#char_count_{$form_id}_{$field['id']}');

            function updateCharCount() {
                var length = inputField.val().length;
                charCountSpan.text(length + ' characters (Please max. ' + {$recommendation} + ' characters)');
                if(length > {$recommendation} && {$recommendation} > 0) {
                    charCountSpan.addClass('char-count-red');
                } else {
                    charCountSpan.removeClass('char-count-red');
                }
            }

            inputField.on('keyup', updateCharCount);
            updateCharCount();
        });
        </script>";

        $content .= $script . '<span id="char_count_' . $form_id . '_' . $field['id'] . '" class="char-count"></span>';
    }

    return $content;
}

// Settings menu and pages

function gforms_char_count_settings_page() {
    // Checks if the reset button has been pressed and resets the default CSS.
    if (isset($_POST['reset_css'])) {
		$default_css = get_default_css();  // Here we call the function
		update_option('gforms_char_count_css', $default_css);
    }

    ?>
    <div class="wrap">
        <h2>Character Counter for Gravity Forms</h2>

        <style>
            input[name="reset_css"] {
                margin-right: 10px;
            }
        </style>

        <!-- Settings main form -->
        <form method="post" action="options.php">
            <?php
            settings_fields("gforms_char_count_options");
            do_settings_sections("gforms-char-count");
            submit_button();
            ?>
        </form>

        <!-- Separate form for the reset button -->
        <form method="post">
            <input type="submit" name="reset_css" value="Reset to Default CSS" class="button button-secondary">
        </form>
    </div>
    <?php
}

function gforms_char_count_add_settings_page() {
    add_options_page(
        "Character Counter for Gravity Forms",
        "Character Counter for Gravity Forms",
        "manage_options",
        "gforms-char-count",
        "gforms_char_count_settings_page"
    );
}
add_action("admin_menu", "gforms_char_count_add_settings_page");

function gforms_char_count_register_settings() {
    register_setting(
        "gforms_char_count_options", 
        "gforms_char_count_css"
    );

    add_settings_section(
        "gforms_char_count_main", 
        "Main Settings", 
        null, 
        "gforms-char-count"
    );

    add_settings_field(
        "gforms-char-count-css", 
        "CSS", 
        "gforms_char_count_display_css", 
        "gforms-char-count", 
        "gforms_char_count_main"
    );
}
add_action("admin_init", "gforms_char_count_register_settings");

function gforms_char_count_display_css() {
    $css = get_option('gforms_char_count_css');  // Here we get the CSS from the database
    $default_css = get_default_css(); // Here we get the default CSS
    ?>
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <textarea name="gforms_char_count_css" rows="15" cols="50"><?php echo esc_textarea($css); ?></textarea>
        <div style="margin-left:20px; font-size:0.9em; flex: 1;">
            <strong>Default CSS Einstellungen:</strong><br>
            <pre style="white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html($default_css); ?></pre>
        </div>
    </div>
    <?php
}

function gforms_char_count_enqueue_styles() {
    $custom_css = get_option('gforms_char_count_css');  // Here we get the CSS from the database
    if(wp_style_is('wp-block-library', 'registered')) { 
        wp_enqueue_style('wp-block-library'); 
        wp_add_inline_style('wp-block-library', $custom_css); 
    }
}
add_action('wp_enqueue_scripts', 'gforms_char_count_enqueue_styles', 100);

function get_default_css() {
    $default_css .= "/* Basic style for character counter display */\n";
    $default_css .= ".char-count {\n";
    $default_css .= "    display: block;\n";
    $default_css .= "    margin-top: 5px;\n";
    $default_css .= "    font-size: 0.9em;\n";
    $default_css .= "    color: #666;\n";
    $default_css .= "}\n";
    $default_css .= "/* Character counter exceeds the threshold */\n";
    $default_css .= ".char-count-red {\n";
    $default_css .= "    color: red;\n";
    $default_css .= "}\n";
    
    return $default_css;
}
