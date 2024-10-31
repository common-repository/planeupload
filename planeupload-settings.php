<?php

defined('ABSPATH') || exit;

function planeupload_settings_init() {

    register_setting( 'planeupload', 'planeupload_options' );

    add_settings_section(
        'planeupload_section_developers',
       "", 'planeupload_section_developers_callback',
        'planeupload'
    );

    add_settings_field(
        'planeupload_api_key',

        __( 'API Key', 'planeupload' ),
        'planeupload_api_key_cb',
        'planeupload',
        'planeupload_section_developers',
        array(
            'label_for'         => 'planeupload_api_key',
            'class'             => 'planeupload_row',
            'planeupload_custom_data' => 'custom',
        )
    );
}

add_action( 'admin_init', 'planeupload_settings_init' );


function planeupload_section_developers_callback( $args ) {
    $options = get_option( 'planeupload_options' );
    $apiKey = null != $options && isSet($options["planeupload_api_key"]) && strlen($options["planeupload_api_key"]) > 0;
    if (!$apiKey) {
        echo "<p class='notice notice-error'>Please enter your API Key. If you don't have any, please register on <a href='https://app.planeupload.com/' target='_blank'>app.planeupload.com</a>, and connect your first cloud</p>";
        return;
    }
    $fileProviders = PlaneUpload::planeuploadRequest("getFileProviders",array());
    if (null == $fileProviders || 0 == count($fileProviders)) {
        echo '<p class="notice notice-error">You don\'t have any clouds connected to your account, please connect them here: <a href="https://app.planeupload.com/" target="_blank">app.planeupload.com</a></p>';
        return;
    }
    $prototype = PlaneUpload::planeuploadGetPrototype();
    if (null == $prototype || null == $prototype->id) {
        echo "<p class='notice notice-error'>There was a problem with connecting to your account, please check your API key</p>";
        return;
    }

    echo '<p class="notice notice-success"><br/><b>Success.</b><br/>All upload buttons are <b>copies of the prototype button</b> that you can customize the looks, allowed file size, select cloud and many more here: <a target="_blank" class="button" href="https://app.planeupload.com/buttons/'.(int)$prototype->id.'">
            Upload button settings</a><br/><br/></p> ';

}


function planeupload_api_key_cb( $args ) {

    ?>
    <input type="text"
           value="<?php if (PlaneUpload::isApiKeySet()):?>****************************************<?php endif;?>"
           required="required"
           id="planeupload_api_key"
           onfocus="/\*/.test(this.value)?this.value='':null"
           onchange="(this.value.length > 0 ? this.name='planeupload_options[planeupload_api_key]' : this.name = '')"
        placeholder="Enter your API Key"
    />
    <p class="description">

        🔐 Enter your <a target="_blank" href="https://planeupload.com/">PlaneUpload</a> API key. It will be encrypted in your database.
    </p>
    <?php
}

function planeupload_options_page() {

    add_menu_page(
        'PlaneUpload Settings',
        'PlaneUpload Settings',
        'manage_options',
        'planeupload',
        'planeupload_options_page_html'
    );
}

add_action( 'admin_menu', 'planeupload_options_page' );


function planeupload_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $reqError = PlaneUpload::checkRequirements();
    if (null != $reqError) {
        add_settings_error( 'planeupload_messages', 'planeupload_message',$reqError, 'error' );
    }

    if ( isset( $_GET['settings-updated'] ) ) {

        add_settings_error( 'planeupload_messages', 'planeupload_message', __( 'Settings Saved', 'planeupload' ), 'updated' );
    }

    settings_errors( 'planeupload_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post"
            onsubmit="return !/\*/.test(this.querySelector('#planeupload_api_key').value);"
        >
            <?php

            settings_fields( 'planeupload' );

            do_settings_sections( 'planeupload' );

            submit_button( 'Save API Key' );
            ?>
        </form>
    </div>
    <?php
}