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

namespace IPS\slack\Notification;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Slack Notification Class
 */
class _Slack extends \IPS\Notification
{
    /**
     * @var The notification title.
     */
    protected $title;

    /**
     * @var The notification pretext.
     */
    protected $pretext;

    /**
     * @var The notification text.
     */
    protected $text;

    /**
     * @var \IPS\Url The url the slack notification will open to.
     */
    protected $url;

    /**
     * @var array Additional Slack fields to include in the notification payload.
     */
    protected $fields = array();

    /**
     * @var The OG Notification
     */
    protected $noticiation;

    /**
     * Constructor
     *
     * @param	\IPS\Application	$app			The application the notification belongs to
     * @param	string				$key			Notification key
     * @param	object|NULL			$item			The thing the notification is about
     * @param	array				$emailParams	Data for notification emails
     * @param	array				$inlineExtra	Extra data to save with inline notifications. Use sparingly: only in cases where it is not possible to obtain the same data later. Will be merged for duplicate notifications.
     * @param	bool				$allowMerging	Allow two identical notification types to be merged
     * @param	string|NULL			$emailKey		Custom email template to use, or NULL to use default
     * @return	void
     */
    public function __construct( \IPS\Notification $notification, $title=NULL, $pretext=NULL, $text=NULL, \IPS\Url $url=NULL, $fields=array() )
    {
        // Set class properties
        $this->title = $title;
        $this->pretext = $pretext;
        $this->text = $text;
        $this->url = $url;
        $this->fields = $fields;
        $this->notification = $notification;
    }

    /**
     * Send Slack Notification
     *
     * @return bool|\IPS\Http\Response
     */
    public function sendSlackNotifications()
    {
        // If we have recipients
        if ( \count( $this->notification->recipients ) > 0 )
        {
            // Get member ids from recipients list
            $member_ids = array_map( function ( $member ) {
                return $member->member_id;
            }, iterator_to_array( $this->notification->recipients ) );

            // Create where statement
            $where = array();
            $where[] = array( 'notification_key=?', $this->notification->key );
            $where[] = \IPS\Db::i()->in( 'member_id', $member_ids );

            // Fetch the user's preferences for the notification key within our own preference settings
            $preference = iterator_to_array( \IPS\Db::i()->select( '*', 'slack_notification_preferences', $where )->setKeyField( 'member_id' )->setValueField( 'preference' ) );

            // If we have slack recipients
            if ( \count( $preference > 0 ) )
            {
                // Loop through our recipients
                foreach ( array_keys( $preference ) as $recipient )
                {
                    // Fetch the members webhook URL
                    $configuration = \IPS\Member::load( $recipient )->slackConfiguration();

                    // Make sure we have a saved incoming webhook URL
                    if ( $configuration['webhook'] AND $configuration['webhook'] != NULL )
                    {
                        // Try and post the notification
                        try
                        {
                            // Create the POST request
                            return \IPS\Http\Url::external( $configuration['webhook'] )->request()->post( $this->composeNotificationPayload( $configuration ) );
                        }

                        // Catch any exceptions
                        catch ( \IPS\Http\Request\CurlException $e )
                        {
                            // Log the errors
                            \IPS\Log::log( $e, 'SLACK NOTIFICATION ERROR: ' . $e->getMessage()  );
                        }

                        // Catch other errors
                        catch ( \Exception $e )
                        {
                            // Log the errors
                            \IPS\Log::log( $e, 'SLACK NOTIFICATION ERROR: ' . $e->getMessage()  );
                        }
                    }
                }
            }
        }

        // Return nothing
        return FALSE;
    }

    /**
     * Compose Notification Payload
     *
     * Creates our slack notification payload using the data
     * parameters available within the class.
     *
     * @param array $configuration
     */
    protected function composeNotificationPayload( $configuration=array() )
    {
        // Create post data
        $json = json_encode(
            array(
                'text' => $this->text,
                'attachments' =>
                array(
                    array(
                        'color' => $configuration['color'],
                        'pretext' => $this->pretext,
                        'title' => $this->notification->app->_title,
                        'title_link' => $this->notification->item ? $this->notification->item->url()->__toString() : NULL,
                        'author_name' => $this->notification->item ? $this->notification->item->author()->name : NULL,
                        'author_link' => $this->notification->item ? $this->notification->item->author()->url()->__toString() : NULL,
                        'fields' => \count( $this->fields ) > 0 ? $this->fields : NULL,
                        'footer' => "Deschutes Design Group LLC",
                        'footer_icon' => 'https://www.deschutesdesigngroup.com/images/deschutesdesigngroupllc-180.png',
                        'ts' => \IPS\DateTime::create()->getTimestamp()
                    )
                )
            )
        );

        // Parse output for display
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $json );

        // Return the json
        return $json;
    }

    /**
     * Save Slack Configuration
     *
     * @param null $webhook
     * @param \IPS\Member $member
     */
    public static function saveConfiguration( $values=array(), \IPS\Member $member )
    {
        // If URL is not null and we have form values to save
        if ( \count( $values ) > 0 AND $member->member_id != 0 )
        {
            // Remove all previous slack config settings
            \IPS\Db::i()->delete( 'slack_notification_settings', array( 'member_id=?', $member->member_id ) );

            // Insert new slack settings
            \IPS\Db::i()->insert( 'slack_notification_settings', array(
                'member_id' => $member->member_id,
                'webhook' => $values['slack_webhook_url'],
                'color' => $values['slack_webhook_color'] ) );
        }
    }
}