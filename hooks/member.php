//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class slack_hook_member extends _HOOK_CLASS_
{
    /**
     * Slack Configuration Settings
     *
     * Loads the slack config settings for the current user.
     */
    public function slackConfiguration()
    {
        // Fetch the settings
        return new \IPS\slack\Manager\Configuration( $this );
    }
}
