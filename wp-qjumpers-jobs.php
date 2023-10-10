<?php
/**
 * Plugin Name: WP QJumpers Jobs MODIFIED
 * Plugin URI: https://github.com/angelacollings/wp-qjumpers-jobs
 * Description: A modified version of the WP QJumpers Jobs Wordpress Plugin to embed QJumpers Jobs in your site.
 * Version: 0.2.0
 * Author: Angela Collings, Andrew Ford 
 * Author URI: https://github.com/qjumpersnz/wp-qjumpers-jobs
 *
 * @package wp-qjumpers-jobs
 */

if (is_admin()) { // admin actions	
    add_action('admin_menu', 'qj_plugin_menu');
}

function qj_plugin_menu()
{

    //create new settings options page
    add_options_page('QJumpers Jobs Options', 'QJumpers Jobs', 'manage_options', 'wp-qjumpers-jobs', 'qj_plugin_options_page');

    //call register settings function
    add_action('admin_init', 'register_qj_jobs_plugin_settings');
}


function register_qj_jobs_plugin_settings()
{
    //register our settings
    register_setting('qj-jobs-settings-group', 'api_key');
    register_setting('qj-jobs-settings-group', 'api_url');
    register_setting('qj-jobs-settings-group', 'jobsite_url');
    register_setting('qj-jobs-settings-group', 'parent_page');
}

function qj_plugin_options_page()
{
    ?>
    <div class="wrap">
        <h1>QJumpers Jobs Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields('qj-jobs-settings-group'); ?>
            <?php do_settings_sections('qj-jobs-settings-group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="api_key" value="<?php echo esc_attr(get_option('api_key')); ?>" size="30" maxlength="30" placeholder="xxxxxxxxxxxxx" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API URL</th>
                    <td><input type="text" name="api_url" value="<?php echo esc_attr(get_option('api_url')); ?>" size="30" maxlength="2000" placeholder="https://qjumpers-api.qjumpers.co" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Job Site URL</th>
                    <td><input type="text" name="jobsite_url" value="<?php echo esc_attr(get_option('jobsite_url')); ?>" size="30" maxlength="2000" placeholder="https://qjumpersjobs.co" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Parent Page</th>
                    <td>
                        <?php
                        $args = array(
                            'selected'              => get_option('parent_page'),
                            'echo'                  => 1,
                            'name'                  => 'parent_page',
                        );
                        wp_dropdown_pages($args);
                        ?>
                    </td>

            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

// Make API request and save data in transient
function qj_jobs_save_data() {
    $apikey = get_option('api_key');
    $apiurl = get_option('api_url');

    $headers = array(
        'Authorization' => 'Basic ' . $apikey,
        'Accept'        => 'application/json;ver=1.0',
        'Content-Type'  => 'application/json; charset=UTF-8'
    );
    $request = array(
        'headers' => $headers
    );

    // Get data for API call
    $response = wp_remote_get($apiurl, $request);
    try {
        $jsonBody = wp_remote_retrieve_body($response);
        $data = json_decode($jsonBody, true);

        // Save the data in transient
        set_transient('qj_jobs_data', $data, DAY_IN_SECONDS);
    }
    catch (Exception $e) {
        // Handle exception
    }
}

// Schedule cron job on plugin activation
function qj_jobs_activate() {
    if ( ! wp_next_scheduled( 'qj_jobs_cron_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'qj_jobs_cron_hook' );
    }
}
register_activation_hook( __FILE__, 'qj_jobs_activate' );

// Unschedule cron job on plugin deactivation
function qj_jobs_deactivate() {
    wp_clear_scheduled_hook( 'qj_jobs_cron_hook' );
}
register_deactivation_hook( __FILE__, 'qj_jobs_deactivate' );

// Hook the cron job to update the data
function qj_jobs_cron_job() {
    qj_jobs_save_data();
}
add_action( 'qj_jobs_cron_hook', 'qj_jobs_cron_job' );

// Add shortcode to display the jobs
add_shortcode('qj_jobs', 'qj_jobs_shortcode');

function qj_jobs_shortcode()
{   
    $data = get_transient('qj_jobs_data');
    if (empty($data)) {
        qj_jobs_save_data();
        $data = get_transient('qj_jobs_data');
    }
    $parent_page = get_option('parent_page');

    try {
        if (empty($data['content'])) {
            throw new Exception('Something went wrong, Please try again.');
        }
        foreach ($data['content'] as $obj) {
            $address = $obj['address'];
            $state = $address['state'];
            $city = $address['city'];
            if ($city === $state) {
                $address = $city;
            } else {
                $address = $city . ', ' . $state;
            }

            $job_id = $obj['jobReferenceId'];
            $wp_url = get_site_url();
            $link = $wp_url . '/?page_id=' . $parent_page . '&job_id=' . $job_id;
            
            $expired = $obj['jobAdvertExpiryDate'];
            $expired_date = date("d-m-Y", strtotime($expired));
            $expired_date = str_replace('-', '/', $expired_date);

            $brand = $obj['brand'];
            $short_desc = $obj['shortDescription'];
            $job_type = $obj['jobType'];
            ?>

            <div class="qj-jobs">
                <div class="qj-jobs_row">
                    <div class="qj-jobs_col">
                        <h4><?php echo esc_attr($obj['title']); ?></h4>
                        <div class="qj-jobs_brand"><?php echo esc_attr($brand); ?></div>
                        <div class="qj-jobs_desc"><?php echo esc_attr($short_desc); ?></div>
                        <div class="qj-jobs-link">
                        <a href="<?php echo $link; ?>" >Read More</a>
                        </div>
                    </div>
                    <div class="qj-jobs_col">
                        <div class="qj-jobs-address"><?php echo $address ?></div>
                        <div class="qj-jobs-type"><?php echo esc_attr($job_type); ?></div>
                        <div class="qj-jobs-date">Closes: <?php echo esc_attr($expired_date); ?></div>
                    </div>
                </div>
            </div>
        <?php
            
    }
} catch (Exception $ex) {
    echo esc_attr("Oops.. Something went wrong, Please try again.");
} // end try/catch
}

// Add shortcode to display the job listing
add_shortcode('qj_job_listing', 'qj_job_listing_shortcode');

function qj_job_listing_shortcode(  )
{
    $data = get_transient('qj_jobs_data');
    if (empty($data)) {
        qj_jobs_save_data();
        $data = get_transient('qj_jobs_data');
    }
    $listing_id = $_GET['job_id'];
    $jobsiteurl = get_option('jobsite_url');

    try {
        $jobs = $data['content'];

        if (empty($jobs)) {
            throw new Exception();
        }
        foreach ($jobs as $job) {
            if ($job['jobReferenceId'] == $listing_id) {
                $job_title = $job['title'];
                $full_desc = $job['fullDescription'];

                $address = "";
                $state = $job['address']['state'];
                $city = $job['address']['city'];
                if ($city === $state) {
                    $address = $city;
                } else {
                    $address = $city . ', ' . $state;
                }

                $expired = $job['jobAdvertExpiryDate'];
                $expired_date = date("d-m-Y", strtotime($expired));
                $expired_date = str_replace('-', '/', $expired_date);

                $jobsite_url = $jobsiteurl ? $jobsiteurl : 'https://qjumpersjobs.co';
                $apply_url = $jobsite_url . '/quickapplications/add/' . $job['id'] . '?jobinvitationid=';

                $unique_class = explode(' ', $job['brand']);
                $unique_class = strtolower($unique_class[0]);

                ?>

                <div class="qj-job listing <?php echo esc_attr($unique_class); ?>">
                    <h1><?php echo esc_attr($job_title); ?></h1>
                    <div>
                        <div class="qj-job address"><?php echo esc_attr($address); ?></div>
                        <div class="qj-job type"><?php echo esc_attr($job['jobType']); ?></div>
                        <div class="qj-job date">Closes: <?php echo esc_attr($expired_date); ?></div>
                    </div>
                    <div class="qj-job brand"><?php echo esc_attr($job['brand']); ?></div>
                    <?php echo $full_desc; ?>

                    <div class="qj-job-link">
                        
                        <a href="<?php echo $apply_url; ?>" target="_blank">Apply Now</a>
                    </div>
                </div>
            <?php
        }

    }
} 
catch (Exception $ex) {
    echo esc_attr("Oops.. Something went wrong, Please try again.");
} // end try/catch
}
?>