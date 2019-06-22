//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class slack_hook_notifications extends _HOOK_CLASS_
{
    /**
     * Build Matrix
     *
     * @param \IPS\Member $member The member
     *
     * @return    \IPS\Helpers\Form\Matrix
     */
    static public function buildMatrix( \IPS\Member $member )
    {
        // Get parent matrix
        $matrix = parent::buildMatrix( $member );

        // Get our notification preference data
        $preferences = iterator_to_array( \IPS\Db::i()->select( '*', 'slack_notification_preferences', array( 'member_id=?', $member->member_id ) )->setKeyField( 'notification_key' )->setValueField( 'preference' ) );

        // Insert new slack column into the notification matrix
        $matrix->columns[ 'member_notifications_slack' ] = function ( $key, $value, $data ) use ( $preferences )
        {
            // Get key name
            $column = trim( preg_replace('/[\[{\(].*[\]}\)]/U' , '', $key ) );

            // Return the yes no form checkbox
            return new \IPS\Helpers\Form\YesNo( $key, $preferences[ $column ] == 'slack' ? TRUE : FALSE, FALSE );
        };

        // Return the matrix
        return $matrix;
    }

    /**
     * Save Matrix
     *
     * @param \IPS\Member $member The member
     * @param array $values       Values from matrix
     *
     * @return    void
     */
    static public function saveMatrix( \IPS\Member $member, $values )
    {
        // Remove our current notification preferences
        \IPS\Db::i()->delete( 'slack_notification_preferences', array( 'member_id=?', $member->member_id ) );

        // Create our insert array
        $insert = array();

        // Loop over the notifications
        foreach ( $values['notifications'] as $k => $v )
        {
            // If the user selected slack
            if ( $v['member_notifications_slack'] )
            {
                // Add the preference to the insert array
                $insert[] = array(
                    'member_id'			=> $member->member_id,
                    'notification_key'	=> $k,
                    'preference'		=> 'slack'
                );
            }
        }

        // Insert the preference
        \IPS\Db::i()->insert( 'slack_notification_preferences', $insert );

        // Perform the native save for inline and email, which deletes the current settings
        return parent::saveMatrix( $member, $values );
    }

	/**
	 * Send Notification
	 *
	 * @param	array	$sentTo		Members who have already received a notification and how (same format as the return value) to prevent duplicates
	 * @return	array	The members that were notified and how they were notified
	 */
	public function send( $sentTo=array() )
    {
        // Get language strings and parse them for output
        $app = $this->app->_title;
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $app );

        // Send the slack notifications as well
        $slack = new \IPS\slack\Notification\Slack( $this, $app, \IPS\Member::loggedIn()->language()->get( 'slack_notifications_auto_pretext' ) );
        $slack->sendSlackNotifications();

        // Finish sending the notification
        return parent::send( $sentTo );
    }
}
