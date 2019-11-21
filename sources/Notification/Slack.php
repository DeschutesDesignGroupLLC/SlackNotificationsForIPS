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
    protected $notification;

    /**
     * _Slack constructor.
     *
     * @param \IPS\Notification $notification
     * @param null $title
     * @param null $pretext
     * @param null $text
     * @param \IPS\Url|NULL $url
     * @param array $fields
     */
    public function __construct( \IPS\Notification $notification, $title=NULL, $pretext=NULL, $text=NULL, \IPS\Url $url=NULL, $fields=array() )
    {
        // Get application title
        $app_title = $notification->app->_title;
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $app_title );

        // Set class properties
        $this->title = $title == NULL ? $app_title : $title;
        $this->item = $notification->item;
        $this->pretext = $pretext == NULL ? \IPS\Member::loggedIn()->language()->get( 'slack_notifications_auto_pretext' ) : $pretext;
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
                foreach ( $preference as $member_id => $webhook )
                {
                    // Make sure we have a saved incoming webhook URL
                    if ( $webhook )
                    {
                        // Create the URL
                        $member = \IPS\Member::load( $member_id );
                        $hook = \IPS\Http\Url::external( $webhook );

                        // Try and post the notification
                        try
                        {
                            // Create the POST request
                            return $hook->request()->post( $this->composeNotificationPayload( $member->slackConfiguration(), $member ) );
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
    public function composeNotificationPayload( \IPS\slack\Manager\Configuration $configuration, \IPS\Member $member )
    {
        // Get our notification data
        $data = $this->_getNotificationData( $member );

        // Create post data
        $json = json_encode(
            array(
                'text' => $data['title'],
                'attachments' =>
                array(
                    array(
                        'color' => $configuration->data['color'],
                        'pretext' => $this->pretext,
                        'title' => $data['title'],
                        'title_link' => method_exists( $data['url'], '__toString' ) ? $data['url']->__toString() : NULL,
                        'author_name' => $data['author'],
                        'author_link' => method_exists( $data['author'], 'url' ) ? $data['author']->url()->__toString() : NULL,
                        'fields' => \count( $this->fields ) > 0 ? $this->fields : NULL,
                        'footer' => \IPS\Settings::i()->board_name,
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
     * Get data from extension
     *
     * @return	array
     * @throws	\RuntimeException
     */
    public function _getNotificationData( \IPS\Member $member)
    {
        // Make our make shift notification
        $inline = new \IPS\Notification\Inline;
        $inline->member = $member;
        $inline->notification_app = $this->notification->app;
        $inline->notification_key = $this->notification->key;

        // If we have a notification item
        if ( $this->notification->item )
        {
            // Add the notification item
            $inline->item = $this->notification->item;
        }

        // Get our email params
        foreach( $this->notification->emailParams AS $param )
        {
            // If they are a content item
            if ( $param instanceof \IPS\Content )
            {
                // Get their sub class item
                $subIdColumn = $param::$databaseColumnId;
                $inline->item_sub_class = \get_class( $param );
                $inline->item_sub_id = $param->$subIdColumn;
            }
        }

        // Return our data
        return $inline->getData();
    }
}