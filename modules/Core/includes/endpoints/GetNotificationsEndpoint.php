<?php

/**
 * Get notifications for a user
 * 
 * @see Alert
 * 
 * @param int $id The NamelessMC user to get notifications for
 *
 * @return string JSON Array
 */
class GetNotificationsEndpoint extends EndpointBase {

    public function __construct() {
        $this->_route = 'getNotifications';
        $this->_module = 'Core';
    }

    public function execute(Nameless2API $api) {
        if ($api->isValidated()) {
            if ($api->validateParams($_POST, ['id'])) {

                // Ensure the user exists
                $user = $api->getDb()->query('SELECT id FROM nl2_users WHERE id = ?', array($_GET['id']));

                if (!$user->count()) {
                    $api->throwError(16, $api->getLanguage()->get('api', 'unable_to_find_user'));
                }
                $user = $user->first()->id;

                $return = array('notifications' => array());

                // Get unread alerts
                $alerts = $api->getDb()->query('SELECT id, type, url, content_short FROM nl2_alerts WHERE user_id = ? AND `read` = 0', array($user));
                if ($alerts->count()) {
                    foreach ($alerts->results() as $result) {
                        $return['notifications'][] = array(
                            'type' => $result->type,
                            'message_short' => $result->content_short,
                            'message' => ($result->content) ? strip_tags($result->content) : $result->content_short,
                            'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/user/alerts/', 'view=' . $result->id)
                        );
                    }
                }

                // Get unread messages
                $messages = $api->getDb()->query('SELECT nl2_private_messages.id, nl2_private_messages.title FROM nl2_private_messages WHERE nl2_private_messages.id IN (SELECT nl2_private_messages_users.pm_id as id FROM nl2_private_messages_users WHERE user_id = ? AND `read` = 0)', array($user));

                if ($messages->count()) {
                    foreach ($messages->results() as $result) {
                        $return['notifications'][] = array(
                            'type' => 'message',
                            'url' => Util::getSelfURL() . ltrim(URL::build('/user/messaging/', 'action=view&message=' . $result->id), '/'),
                            'message_short' => $result->title,
                            'message' => $result->title
                        );
                    }
                }

                $api->returnArray($return);
            }
        } else $api->throwError(1, $api->getLanguage()->get('api', 'invalid_api_key'));
    }
}