<?php
/**
 * @brief		Slack Notifications Application Class
 * @author		<a href='https://www.deschutesdesigngroup.com'>Deschutes Design Group LLC</a>
 * @copyright	(c) 2019 Deschutes Design Group LLC
 * @package		Invision Community
 * @subpackage	Slack Notifications
 * @since		19 Jun 2019
 * @version
 */

namespace IPS\slack\Manager;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Slack Configuration Class
 */
class _Configuration
{
    /**
     * The member the configuration belongs to
     *
     * @var \IPS\Member|null
     */
    public $member = NULL;

    /**
     * An array of webhooks the member has saved
     *
     * @var array|mixed
     */
    public $webhooks = array();

    /**
     * The complete configuration data
     *
     * @var array
     */
    public $data = array();

    /**
     * Configuration constructor.
     *
     * @param \IPS\Member $member   The member you want to load their config for.
     */
    public function __construct( \IPS\Member $member )
    {
        // Set the member
        $this->member = $member;

        // Fetch the config settings
        $data = $this->_fetchConfigurationSettings();

        // If we have data
        if ( $this->data['webhook'] )
        {
            // Set our webhooks
            $this->webhooks = json_decode( $this->data['webhook'], TRUE );
        }
    }

    /**
     * Fetch Configuration Settings
     *
     * Fetches the configuration settings for the member specified
     * in the constructor.
     *
     * @return array
     */
    protected function _fetchConfigurationSettings()
    {
        // Try and fetch the settings
        try
        {
            // Fetch the data and return it
            $this->data = \IPS\Db::i()->select( '*', 'slack_notification_settings', array( 'member_id=?', $this->member->member_id ) )->first();
        }

        // Catch any underflows
        catch ( \UnderflowException $e ) {}

        // Return the data
        return $this->data;
    }

    /**
     * Notification Settings Select Options
     *
     * Restructures the saved webhook URL/channels for use as options
     * inside a form select object.
     */
    public function notificationSettingsSelectOptions()
    {
        // Loop through data
        $options = array();
        foreach ( $this->webhooks as $webhook )
        {
            // Add to the options array
            $options[$webhook['webhook']] = $webhook['channel'];
        }

        // Add our none setting
        $options = array( 'disabled' => \IPS\Member::loggedIn()->language()->get( 'slack_webhook_none') ) + $options;

        // Return the select options
        return $options;
    }

    /**
     * Save Slack Configuration
     *
     * Saves the slack configuration using values from the account
     * settings form.
     *
     * @param null $webhook
     * @param \IPS\Member $member
     */
    public function saveConfiguration( $values=array() )
    {
        // If URL is not null and we have form values to save
        if ( \count( $values ) > 0 AND $this->member->member_id != 0 )
        {
            // Remove all previous slack config settings
            \IPS\Db::i()->delete( 'slack_notification_settings', array( 'member_id=?', $this->member->member_id ) );

            // Format the URL's
            foreach ( $values['slack_webhook_url'] as $key => $webhook )
            {
                // If a URL
                if ( $webhook['webhook'] )
                {
                    // Set as sting
                    $url = $webhook['webhook'];
                    $values['slack_webhook_url'][$key]['webhook'] = $url->__toString();
                }
            }

            // Insert new slack settings
            \IPS\Db::i()->insert( 'slack_notification_settings', array(
                'member_id' => $this->member->member_id,
                'webhook' => json_encode( $values['slack_webhook_url'] ),
                'color' => $values['slack_webhook_color'] ) );
        }
    }
}