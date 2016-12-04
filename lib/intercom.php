<?php
/**
 * Created by IntelliJ IDEA.
 * User: stijnbe
 * Date: 02/12/2016
 * Time: 18:57
 */
require WPCF7_INTERCOM_PLUGIN_DIR . "/vendor/autoload.php";

use Intercom\IntercomClient;

add_action('wpcf7_after_save', 'wpcf7_intercom_save');
add_action('wpcf7_before_send_mail', 'wpcf7_intercom_create_conversation');
add_action('wpcf7_editor_panels', 'show_wpcf7_intercom_metabox');

function wpcf7_intercom_create_conversation($obj)
{

    $cf7_intercom = get_option('cf7_intercom_' . $obj->id());
    $submission = WPCF7_Submission::get_instance();
    $posted_data = $submission->get_posted_data();

    if ($cf7_intercom && $cf7_intercom['active']) {

        $client = new IntercomClient($cf7_intercom["appId"], $cf7_intercom["apiKey"]);

        $regex = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/';
        $email = cf7_intercom_tag_replace($regex, $cf7_intercom['email'], $posted_data);
        $name = cf7_intercom_tag_replace($regex, $cf7_intercom['name'], $posted_data);
        $message_subject = cf7_intercom_tag_replace($regex, $cf7_intercom['subject'], $posted_data);
        $message = cf7_intercom_tag_replace($regex, $cf7_intercom['message'], $posted_data);
        $message = $message_subject . "\n" . $message;

        //make sure the lead exists
        $lead = null;
        $lead_results = $client->leads->getLeads(["email" => $email]);
        if(count($lead_results->contacts) > 0){
            $lead = $lead_results->contacts[0];
        } else {
            $lead = $client->leads->create([
                "email" => $email,
                "name" => $name
            ]);
        }
        $client->messages->create([
            "message_type" => "inapp",
            "body" => $message,
            "from" => [
                "type" => "user",
                "id" => $lead->id,
                "email" => $email,
            ]
        ]);

    }
}


function wpcf7_intercom_add_intercom($args)
{
    $cf7_intercom_defaults = array();
    $cf7_intercom = get_option('cf7_intercom_' . $args->id(), $cf7_intercom_defaults);
    ?>

    <div class="metabox-holder">

        <h3>Intercom Integration v.<?php echo WPCF7_INTERCOM_VERSION ?></h3>

        <div class="mce-main-fields">

            <p class="mail-field">
                <input type="checkbox" id="wpcf7-intercom-cf-active" name="wpcf7-intercom[active]"
                       value="1"<?php echo (isset($cf7_intercom['active'])) ? ' checked="checked"' : ''; ?> />
                <label for="wpcf7-intercom-active"><?php echo esc_html(__('Enable Intercom Integration', 'wpcf7')); ?> </label>
            </p>

            <p class="mail-field">
                <label for="wpcf7-intercom-email"><?php echo esc_html(__('User Email:', 'wpcf7')); ?> </label><br/>
                <input type="text" id="wpcf7-intercom-email" name="wpcf7-intercom[email]" class="wide" size="70"
                       placeholder="[your-email] <= Make sure this the email of your form field"
                       value="<?php echo (isset ($cf7_intercom['email'])) ? esc_attr($cf7_intercom['email']) : ''; ?>"/>
            </p>

            <p class="mail-field">
                <label for="wpcf7-intercom-name"><?php echo esc_html(__('User Name:', 'wpcf7')); ?> </label><br/>
                <input type="text" id="wpcf7-intercom-name" name="wpcf7-intercom[name]" class="wide" size="70"
                       placeholder="[full-name] <= Make sure this the name of your form field"
                       value="<?php echo (isset ($cf7_intercom['name'])) ? esc_attr($cf7_intercom['name']) : ''; ?>"/>
            </p>


            <p class="mail-field">
                <label for="wpcf7-intercom-appId"><?php echo esc_html(__('Intercom App Id:', 'wpcf7')); ?> </label><br/>
                <input type="text" id="wpcf7-intercom-appId" name="wpcf7-intercom[appId]" class="wide" size="70"
                       value="<?php echo (isset($cf7_intercom['appId'])) ? esc_attr($cf7_intercom['appId']) : ''; ?>"/>
            </p>

            <p class="mail-field">
                <label for="wpcf7-intercom-apiKey"><?php echo esc_html(__('Intercom API Key:', 'wpcf7')); ?> </label><br/>
                <input type="text" id="wpcf7-intercom-apiKey" name="wpcf7-intercom[apiKey]" class="wide" size="70"
                       value="<?php echo (isset($cf7_intercom['apiKey'])) ? esc_attr($cf7_intercom['apiKey']) : ''; ?>"/>
            </p>

            <p class="mail-field">
                <label for="wpcf7-intercom-subject"><?php echo esc_html(__('Intercom Subject:', 'wpcf7')); ?> </label><br/>
                <input type="text" id="wpcf7-intercom-subject" name="wpcf7-intercom[subject]" class="wide" size="70"
                       value="<?php echo (isset($cf7_intercom['subject'])) ? esc_attr($cf7_intercom['subject']) : ''; ?>"/>
            </p>

            <p class="mail-field">
                <label for="wpcf7-intercom-message"><?php echo esc_html(__('Intercom Message:', 'wpcf7')); ?> </label><br/>
                <textarea id="wpcf7-intercom-message" name="wpcf7-intercom[message]" cols="70" rows="10"
                          class="large-text code"><?php echo (isset($cf7_intercom['message'])) ? esc_attr($cf7_intercom['message']) : ''; ?></textarea>
            </p>
        </div>
    </div>
    <?php
}

function wpcf7_intercom_save($args)
{
    if (!empty($_POST)) {
        update_option('cf7_intercom_' . $args->id(), $_POST['wpcf7-intercom']);
    }
}

function show_wpcf7_intercom_metabox($panels)
{

    $new_page = array(
        'Intercom-Integration' => array(
            'title' => 'Intercom',
            'callback' => 'wpcf7_intercom_add_intercom'
        )
    );

    $panels = array_merge($panels, $new_page);

    return $panels;

}

function cf7_intercom_tag_replace($pattern, $subject, $posted_data, $html = false)
{
    return preg_replace_callback($pattern, function ($matches) use (&$posted_data) {
        return isset($posted_data[$matches[1]]) ? $posted_data[$matches[1]] : '';
    }, $subject);
}