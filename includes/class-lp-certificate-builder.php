<?php

class LP_Certificate_Builder
{

    protected static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('learn_press_user_course_enrolled', array($this, 'generate_certificate'), 10, 3);
        add_shortcode('lp_user_certificates', array($this, 'display_user_certificates'));
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'learn_press',
            __('Certificate Builder', 'learnpress-certificate-builder'),
            __('Certificate Builder', 'learnpress-certificate-builder'),
            'manage_options',
            'lp_certificate_builder',
            array($this, 'create_admin_page')
        );
    }

    public function register_settings()
    {
        register_setting('lp_certificate_builder_group', 'lp_certificate_builder_settings');

        add_settings_section(
            'lp_certificate_builder_section',
            __('Certificate Settings', 'learnpress-certificate-builder'),
            null,
            'lp_certificate_builder'
        );

        add_settings_field(
            'lp_certificate_template',
            __('Certificate Template', 'learnpress-certificate-builder'),
            array($this, 'template_field_callback'),
            'lp_certificate_builder',
            'lp_certificate_builder_section'
        );
    }

    public function template_field_callback()
    {
        $options = get_option('lp_certificate_builder_settings');
?>
        <textarea name="lp_certificate_builder_settings[lp_certificate_template]" rows="10" cols="50"><?php echo isset($options['lp_certificate_template']) ? esc_textarea($options['lp_certificate_template']) : ''; ?></textarea>
    <?php
    }

    public function create_admin_page()
    {
    ?>
        <div class="wrap">
            <h1><?php _e('Certificate Builder', 'learnpress-certificate-builder'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('lp_certificate_builder_group');
                do_settings_sections('lp_certificate_builder');
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    public function create_certificate($user_id, $course_id)
    {
        $options = get_option('lp_certificate_builder_settings');
        $template = isset($options['lp_certificate_template']) ? $options['lp_certificate_template'] : '';

        $user_info = get_userdata($user_id);
        $course = learn_press_get_course($course_id);

        $certificate = str_replace(
            array('{user_name}', '{course_title}', '{date}'),
            array($user_info->display_name, $course->get_title(), date('F j, Y')),
            $template
        );

        return $certificate;
    }

    public function generate_certificate($user_id, $course_id, $status)
    {
        if ($status == 'completed') {
            $certificate = $this->create_certificate($user_id, $course_id);
            // Simpan atau kirim sertifikat ke pengguna
            update_user_meta($user_id, 'lp_certificate_' . $course_id, $certificate);
        }
    }

    public function display_user_certificates()
    {
        if (!is_user_logged_in()) {
            return __('You need to be logged in to view your certificates.', 'learnpress-certificate-builder');
        }

        $user_id = get_current_user_id();
        $certificates = array();

        $courses = learn_press_get_courses(array('status' => 'publish'));
        foreach ($courses as $course) {
            $certificate = get_user_meta($user_id, 'lp_certificate_' . $course->ID, true);
            if ($certificate) {
                $certificates[] = array(
                    'course_title' => $course->get_title(),
                    'certificate' => $certificate
                );
            }
        }

        ob_start();
        if (!empty($certificates)) {
            echo '<ul>';
            foreach ($certificates as $cert) {
                echo '<li><h3>' . esc_html($cert['course_title']) . '</h3><div>' . wp_kses_post($cert['certificate']) . '</div></li>';
            }
            echo '</ul>';
        } else {
            echo __('No certificates found.', 'learnpress-certificate-builder');
        }
        return ob_get_clean();
    }
}
?>